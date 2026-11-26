<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const LOW_STOCK = 10;

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $this->authorizeReportType($filters['type'], $request->user());

        $data = [
            'type' => $filters['type'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'period' => $filters['period'],
            'filters' => $filters,
            'lowStockLimit' => self::LOW_STOCK,
            'users' => collect(),
            'customers' => collect(),
            'sales' => null,
            'summary' => null,
            'medicines' => null,
            'bySeller' => null,
        ];

        if ($filters['type'] === 'sales') {
            $salesQuery = $this->salesQuery($filters);
            $aggregate = (clone $salesQuery)
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total, COALESCE(SUM(igv), 0) as igv')
                ->first();

            $data['summary'] = [
                'count' => (int) $aggregate->count,
                'total' => (float) $aggregate->total,
                'igv' => (float) $aggregate->igv,
                'subtotal' => (float) $aggregate->total - (float) $aggregate->igv,
                'average' => $aggregate->count ? (float) $aggregate->total / (int) $aggregate->count : 0,
            ];
            $data['sales'] = (clone $salesQuery)->orderByDesc('date')->paginate(25)->withQueryString();
$data['users'] = User::whereHas('sales')->orderBy('name')->get(['id', 'name']);
$data['customers'] = DB::table('customers')
    ->whereIn('id', (clone $salesQuery)->reorder()->select('customer_id')->distinct())
    ->orderBy('name')
    ->get(['id', 'name']);


        } elseif ($filters['type'] === 'medicines') {
            $data['medicines'] = Medicine::with('category')->orderBy('name')->paginate(25)->withQueryString();
        } else {
            $data['bySeller'] = DB::table('sales')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->whereBetween('sales.date', [$filters['from'], $filters['to']])
                ->when($filters['user_id'], fn ($query) => $query->where('sales.user_id', $filters['user_id']))
                ->when($filters['customer_id'], fn ($query) => $query->where('sales.customer_id', $filters['customer_id']))
                ->groupBy('users.id', 'users.name')
                ->selectRaw('users.id, users.name, COUNT(sales.id) as sales_count, SUM(sales.total) as total, AVG(sales.total) as average')
                ->orderByDesc('total')
                ->paginate(25)
                ->withQueryString();
        }

        return view('reports.index', $data);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $this->authorizeReportType($filters['type'], $request->user());
        $filename = 'reporte-'.$filters['type'].'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Reporte de '.ucfirst($filters['type'])], ';', '"', '\\');
            fputcsv($output, ['Generado el', now()->format('d/m/Y H:i:s')], ';', '"', '\\');
            fputcsv($output, ['Periodo', $filters['from']->format('d/m/Y').' - '.$filters['to']->format('d/m/Y')], ';', '"', '\\');
            fputcsv($output, [
                'Vendedor',
                $filters['user_id'] ? User::find($filters['user_id'])?->name ?? 'No encontrado' : 'Todos',
            ], ';', '"', '\\');
            fputcsv($output, [
                'Cliente',
                $filters['customer_id'] ? DB::table('customers')->where('id', $filters['customer_id'])->value('name') ?? 'No encontrado' : 'Todos',
            ], ';', '"', '\\');
            fputcsv($output, [], ';', '"', '\\');

            if ($filters['type'] === 'sales') {
                fputcsv($output, ['Código', 'Fecha', 'Cliente', 'Vendedor', 'Subtotal', 'IGV', 'Total', 'Estado'], ';', '"', '\\');
                $this->salesExportQuery($filters)->chunk(500, function ($sales) use ($output): void {
                    foreach ($sales as $sale) {
                        fputcsv($output, [
                            '#'.$sale->id,
                            Carbon::parse($sale->date)->format('d/m/Y H:i'),
                            $sale->customer_name,
                            $sale->seller_name,
                            number_format((float) $sale->total - (float) $sale->igv, 2, '.', ''),
                            number_format((float) $sale->igv, 2, '.', ''),
                            number_format((float) $sale->total, 2, '.', ''),
                            'Registrada',
                        ], ';', '"', '\\');
                    }
                });
            } elseif ($filters['type'] === 'medicines') {
                fputcsv($output, ['Medicamento', 'Categoría', 'Precio de compra', 'Precio de venta', 'Stock', 'Stock mínimo', 'Vencimiento', 'Estado de stock'], ';', '"', '\\');
                Medicine::with('category')->orderBy('id')->chunkById(500, function ($medicines) use ($output): void {
                    foreach ($medicines as $medicine) {
                        fputcsv($output, [
                            $medicine->name,
                            $medicine->category->name,
                            'No registrado',
                            number_format((float) $medicine->price, 2, '.', ''),
                            $medicine->stock,
                            'No registrado',
                            'No registrado',
                            $this->stockStatus($medicine->stock),
                        ], ';', '"', '\\');
                    }
                });
            } else {
                fputcsv($output, ['Vendedor', 'Cantidad de ventas', 'Total vendido', 'Promedio por venta'], ';', '"', '\\');
                DB::table('sales')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->whereBetween('sales.date', [$filters['from'], $filters['to']])
                    ->when($filters['user_id'], fn ($query) => $query->where('sales.user_id', $filters['user_id']))
                    ->when($filters['customer_id'], fn ($query) => $query->where('sales.customer_id', $filters['customer_id']))
                    ->groupBy('users.id', 'users.name')
                    ->selectRaw('users.name, COUNT(sales.id) as sales_count, SUM(sales.total) as total, AVG(sales.total) as average')
                    ->orderByDesc('total')
                    ->get()
                    ->each(function ($seller) use ($output): void {
                        fputcsv($output, [
                            $seller->name,
                            $seller->sales_count,
                            number_format((float) $seller->total, 2, '.', ''),
                            number_format((float) $seller->average, 2, '.', ''),
                        ], ';', '"', '\\');
                    });
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:sales,medicines,sellers'],
            'period' => ['nullable', 'in:today,week,month,custom'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        $period = $validated['period'] ?? ((! empty($validated['from']) || ! empty($validated['to'])) ? 'custom' : 'month');

        if ($period === 'custom') {
            $request->validate([
                'from' => ['required', 'date'],
                'to' => ['required', 'date', 'after_or_equal:from'],
            ]);
            $from = Carbon::parse($validated['from'])->startOfDay();
            $to = Carbon::parse($validated['to'])->endOfDay();
        } else {
            $start = now();
            [$from, $to] = match ($period) {
                'today' => [$start->copy()->startOfDay(), $start->copy()->endOfDay()],
                'week' => [$start->copy()->startOfWeek(), $start->copy()->endOfWeek()],
                default => [$start->copy()->startOfMonth(), $start->copy()->endOfDay()],
            };
        }

        $user = $request->user();
        $defaultType = $user->hasRole('Químico farmacéutico') ? 'medicines' : 'sales';

        return [
            'type' => $validated['type'] ?? $defaultType,
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'user_id' => $validated['user_id'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
        ];
    }

    private function authorizeReportType(string $type, User $user): void
    {
        abort_unless(
            $user->hasRole('Administrador') || ($user->hasRole('Químico farmacéutico') && $type === 'medicines'),
            403
        );
    }

    private function salesQuery(array $filters): Builder
    {
        return Sale::with(['customer', 'user'])
            ->whereBetween('date', [$filters['from'], $filters['to']])
            ->when($filters['user_id'], fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when($filters['customer_id'], fn ($query) => $query->where('customer_id', $filters['customer_id']));
    }

    private function salesExportQuery(array $filters): Builder
    {
        return Sale::query()
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->whereBetween('sales.date', [$filters['from'], $filters['to']])
            ->when($filters['user_id'], fn ($query) => $query->where('sales.user_id', $filters['user_id']))
            ->when($filters['customer_id'], fn ($query) => $query->where('sales.customer_id', $filters['customer_id']))
            ->select([
                'sales.id',
                'sales.date',
                'sales.total',
                'sales.igv',
                'customers.name as customer_name',
                'users.name as seller_name',
            ])
            ->orderBy('sales.date');
    }

    private function stockStatus(int $stock): string
    {
        return match (true) {
            $stock === 0 => 'Agotado',
            $stock <= self::LOW_STOCK => 'Stock bajo',
            default => 'Suficiente',
        };
    }
}
