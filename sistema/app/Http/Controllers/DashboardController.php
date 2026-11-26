<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const LOW_STOCK = 10;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:7,30'],
        ]);

        $period = (int) ($validated['period'] ?? 7);
        $user = $request->user();
        $isAdministrator = $user->hasRole('Administrador');
        $isCashier = $user->hasRole('Cajero');
        $isPharmacist = $user->hasRole('Químico farmacéutico');
        $today = now();
        $todayStart = $today->copy()->startOfDay();
        $todayEnd = $today->copy()->endOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $previousMonthStart = $monthStart->copy()->subMonth();
        $previousMonthEnd = $previousMonthStart->copy()
            ->addDays($today->day - 1)
            ->endOfDay();
        if ($previousMonthEnd->greaterThan($previousMonthStart->copy()->endOfMonth()->endOfDay())) {
            $previousMonthEnd = $previousMonthStart->copy()->endOfMonth()->endOfDay();
        }
        $salesScope = fn () => $this->salesQuery($user, $isCashier);
        $hasInventoryAccess = $isAdministrator || $isPharmacist;

        $medicineCount = $hasInventoryAccess ? Medicine::count() : 0;
        $lowStockCount = $hasInventoryAccess ? Medicine::whereBetween('stock', [1, self::LOW_STOCK])->count() : 0;
        $outOfStockCount = $hasInventoryAccess ? Medicine::where('stock', 0)->count() : 0;

        if ($isAdministrator) {
            $todayRevenue = (float) $salesScope()->whereBetween('date', [$todayStart, $todayEnd])->sum('total');
            $previousDayRevenue = (float) $salesScope()
                ->whereBetween('date', [$todayStart->copy()->subDay(), $todayStart->copy()->subSecond()])
                ->sum('total');
            $todaySalesCount = $salesScope()->whereBetween('date', [$todayStart, $todayEnd])->count();
            $previousDaySalesCount = $salesScope()
                ->whereBetween('date', [$todayStart->copy()->subDay(), $todayStart->copy()->subSecond()])
                ->count();
            $monthRevenue = (float) $salesScope()->whereBetween('date', [$monthStart, $todayEnd])->sum('total');
            $previousMonthRevenue = (float) $salesScope()
                ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
                ->sum('total');
            $newCustomers = DB::table('customers')
                ->whereBetween('created_at', [$monthStart, $todayEnd])
                ->count();
            $previousNewCustomers = DB::table('customers')
                ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
                ->count();
            $newMedicines = Medicine::whereBetween('created_at', [$monthStart, $todayEnd])->count();
            $previousNewMedicines = Medicine::whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])->count();

            $stats = [
                $this->stat('Ventas de hoy', $todayRevenue, 'currency', 'bi-currency-dollar', 'Ingresos registrados hoy', $this->comparison($todayRevenue, $previousDayRevenue)),
                $this->stat('Operaciones de hoy', $todaySalesCount, 'number', 'bi-receipt', 'Ventas realizadas hoy', $this->comparison($todaySalesCount, $previousDaySalesCount)),
                $this->stat('Ingresos del mes', $monthRevenue, 'currency', 'bi-graph-up-arrow', 'Acumulado del mes actual', $this->comparison($monthRevenue, (float) $previousMonthRevenue)),
                $this->stat('Clientes registrados', DB::table('customers')->count(), 'number', 'bi-people', 'Total de clientes', $this->comparison($newCustomers, $previousNewCustomers, 'Altas del mes')),
                $this->stat('Medicamentos', $medicineCount, 'number', 'bi-capsule', 'Productos registrados', $this->comparison($newMedicines, $previousNewMedicines, 'Altas del mes')),
                $this->stat('Stock bajo', $lowStockCount, 'number', 'bi-exclamation-triangle', 'Existencias de 1 a '.self::LOW_STOCK, null),
                $this->stat('Agotados', $outOfStockCount, 'number', 'bi-box-seam', 'Sin unidades disponibles', null),
            ];
        } elseif ($isCashier) {
            $todayRevenue = (float) $salesScope()->whereBetween('date', [$todayStart, $todayEnd])->sum('total');
            $previousDayRevenue = (float) $salesScope()
                ->whereBetween('date', [$todayStart->copy()->subDay(), $todayStart->copy()->subSecond()])
                ->sum('total');
            $todaySalesCount = $salesScope()->whereBetween('date', [$todayStart, $todayEnd])->count();
            $previousDaySalesCount = $salesScope()
                ->whereBetween('date', [$todayStart->copy()->subDay(), $todayStart->copy()->subSecond()])
                ->count();
            $monthSales = $salesScope()->whereBetween('date', [$monthStart, $todayEnd]);
            $monthRevenue = (float) (clone $monthSales)->sum('total');
            $previousMonthRevenue = (float) $salesScope()
                ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
                ->sum('total');
            $monthCount = (clone $monthSales)->count();

            $stats = [
                $this->stat('Mis ventas de hoy', $todayRevenue, 'currency', 'bi-currency-dollar', 'Ingresos registrados hoy', $this->comparison($todayRevenue, $previousDayRevenue)),
                $this->stat('Operaciones de hoy', $todaySalesCount, 'number', 'bi-receipt', 'Ventas realizadas hoy', $this->comparison($todaySalesCount, $previousDaySalesCount)),
                $this->stat('Mis ingresos del mes', $monthRevenue, 'currency', 'bi-graph-up-arrow', 'Acumulado del mes actual', $this->comparison($monthRevenue, $previousMonthRevenue)),
                $this->stat('Ticket promedio', $monthCount ? $monthRevenue / $monthCount : 0, 'currency', 'bi-basket', 'Promedio de mis ventas del mes', null),
            ];
        } else {
            $stats = [
                $this->stat('Medicamentos', $medicineCount, 'number', 'bi-capsule', 'Productos registrados', null),
                $this->stat('Stock bajo', $lowStockCount, 'number', 'bi-exclamation-triangle', 'Existencias de 1 a '.self::LOW_STOCK, null),
                $this->stat('Agotados', $outOfStockCount, 'number', 'bi-box-seam', 'Sin unidades disponibles', null),
                $this->stat('Categorías', DB::table('categories')->count(), 'number', 'bi-grid', 'Categorías con productos', null),
            ];
        }

        $inventoryAlerts = $isAdministrator || $isPharmacist
            ? Medicine::with('category')
                ->where('stock', '<=', self::LOW_STOCK)
                ->orderBy('stock')
                ->orderBy('name')
                ->limit(8)
                ->get()
            : collect();

        $recentSales = $isPharmacist
            ? collect()
            : $salesScope()->with(['customer', 'user'])->orderByDesc('date')->limit(5)->get();

        $charts = $isPharmacist
            ? $this->inventoryCharts()
            : $this->salesCharts($user, $isCashier, $period, $today);

        return view('principal.dashboard.index', [
            'stats' => $stats,
            'period' => $period,
            'isAdministrator' => $isAdministrator,
            'isCashier' => $isCashier,
            'isPharmacist' => $isPharmacist,
            'inventoryAlerts' => $inventoryAlerts,
            'recentSales' => $recentSales,
            'charts' => $charts,
            'lowStockLimit' => self::LOW_STOCK,
        ]);
    }

    private function salesQuery(User $user, bool $isCashier)
    {
        return Sale::query()->when($isCashier, fn ($query) => $query->where('user_id', $user->id));
    }

    private function stat(string $label, int|float $value, string $format, string $icon, string $description, ?string $comparison): array
    {
        return compact('label', 'value', 'format', 'icon', 'description', 'comparison');
    }

    private function comparison(int|float $current, int|float $previous, string $prefix = 'Periodo anterior'): ?string
    {
        if ($previous <= 0) {
            return null;
        }

        $change = (($current - $previous) / $previous) * 100;
        $direction = $change >= 0 ? 'subió' : 'bajó';

        return $prefix.': '.$direction.' '.number_format(abs($change), 1).'%';
    }

    private function salesCharts(User $user, bool $isCashier, int $period, \Illuminate\Support\Carbon $today): array
    {
        $firstDay = $today->copy()->subDays($period - 1)->startOfDay();
        $dailyRows = $this->salesQuery($user, $isCashier)
            ->whereBetween('date', [$firstDay, $today->copy()->endOfDay()])
            ->selectRaw('DATE(date) as sale_day, SUM(total) as revenue')
            ->groupByRaw('DATE(date)')
            ->get()
            ->keyBy('sale_day');

        $daily = collect(range(0, $period - 1))->map(function (int $offset) use ($firstDay, $dailyRows) {
            $day = $firstDay->copy()->addDays($offset);

            return [
                'label' => $day->format('d/m'),
                'value' => (float) ($dailyRows->get($day->toDateString())->revenue ?? 0),
            ];
        });

        $yearStart = $today->copy()->startOfMonth()->subMonths(11);
        $monthlyRows = $this->salesQuery($user, $isCashier)
            ->whereBetween('date', [$yearStart, $today->copy()->endOfDay()])
            ->selectRaw('DATE(date) as sale_day, SUM(total) as revenue')
            ->groupByRaw('DATE(date)')
            ->get();
        $monthlyTotals = $monthlyRows->groupBy(fn ($row) => substr($row->sale_day, 0, 7))
            ->map(fn (Collection $rows) => (float) $rows->sum('revenue'));
        $monthly = collect(range(0, 11))->map(function (int $offset) use ($yearStart, $monthlyTotals) {
            $month = $yearStart->copy()->addMonths($offset);

            return [
                'label' => $month->translatedFormat('M y'),
                'value' => $monthlyTotals->get($month->format('Y-m'), 0),
            ];
        });

        $detailQuery = DB::table('detail_sales')
            ->join('sales', 'sales.id', '=', 'detail_sales.sale_id')
            ->whereBetween('sales.date', [$firstDay, $today->copy()->endOfDay()])
            ->when($isCashier, fn ($query) => $query->where('sales.user_id', $user->id));

        $topMedicines = (clone $detailQuery)
            ->join('medicines', 'medicines.id', '=', 'detail_sales.medicine_id')
            ->groupBy('medicines.id', 'medicines.name')
            ->selectRaw('medicines.name as label, SUM(detail_sales.amount) as value')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (float) $row->value]);

        $categories = (clone $detailQuery)
            ->join('medicines', 'medicines.id', '=', 'detail_sales.medicine_id')
            ->join('categories', 'categories.id', '=', 'medicines.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('categories.name as label, SUM(detail_sales.subtotal) as value')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (float) $row->value]);

        return [
            'daily' => $daily,
            'monthly' => $monthly,
            'topMedicines' => $topMedicines,
            'categories' => $categories,
            'inventoryCategories' => [],
        ];
    }

    private function inventoryCharts(): array
    {
        $inventoryCategories = DB::table('medicines')
            ->join('categories', 'categories.id', '=', 'medicines.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('categories.name as label, SUM(medicines.stock) as value')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (float) $row->value]);

        return [
            'daily' => [],
            'monthly' => [],
            'topMedicines' => [],
            'categories' => [],
            'inventoryCategories' => $inventoryCategories,
        ];
    }
}
