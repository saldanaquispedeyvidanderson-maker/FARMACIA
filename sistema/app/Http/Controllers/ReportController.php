<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Sale;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
            $data['summary'] = $this->salesSummary($salesQuery);
            $data['sales'] = (clone $salesQuery)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString();
            $data['users'] = User::whereHas('sales')->orderBy('name')->get(['id', 'name']);
            $data['customers'] = DB::table('customers')
                ->whereIn('id', (clone $salesQuery)->reorder()->select('customer_id')->distinct())
                ->orderBy('name')
                ->get(['id', 'name']);
        } elseif ($filters['type'] === 'medicines') {
            $data['medicines'] = Medicine::with('category')->orderBy('name')->paginate(25)->withQueryString();
        } else {
            $data['bySeller'] = $this->sellersQuery($filters)
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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = match ($filters['type']) {
            'sales' => ['Código', 'Fecha', 'Cliente', 'Vendedor', 'Subtotal', 'IGV', 'Total', 'Estado'],
            'medicines' => ['Medicamento', 'Categoría', 'Precio de compra', 'Precio de venta', 'Stock', 'Estado'],
            default => ['Vendedor', 'Cantidad de ventas', 'Total vendido', 'Promedio por venta'],
        };
        $sheet->setTitle(match ($filters['type']) {
            'sales' => 'Ventas',
            'medicines' => 'Medicamentos',
            default => 'Vendedores',
        });
        $sheet->fromArray($headers, null, 'A1');

        $lastRow = match ($filters['type']) {
            'sales' => $this->writeSalesSheet($sheet, $filters),
            'medicines' => $this->writeMedicinesSheet($sheet),
            default => $this->writeSellersSheet($sheet, $filters),
        };

        $this->formatWorksheet($sheet, count($headers), $lastRow, $filters['type']);
        $spreadsheet->getProperties()
            ->setCreator('FARM SJB')
            ->setTitle('Reporte de '.$this->reportName($filters['type']));

        $writer = new Xlsx($spreadsheet);
        $filename = 'reporte-'.$filters['type'].'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($writer, $spreadsheet): void {
            try {
                $writer->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function pdf(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $this->authorizeReportType($filters['type'], $request->user());

        $data = [
            'type' => $filters['type'],
            'title' => $this->reportName($filters['type']),
            'period' => $this->periodName($filters['period']),
            'from' => $filters['from'],
            'to' => $filters['to'],
            'userName' => $filters['user_id'] ? User::findOrFail($filters['user_id'])->name : null,
            'customerName' => $filters['customer_id']
                ? DB::table('customers')->where('id', $filters['customer_id'])->value('name')
                : null,
            'generatedAt' => now(),
            'summary' => null,
            'sales' => collect(),
            'medicines' => collect(),
            'bySeller' => collect(),
            'lowStockLimit' => self::LOW_STOCK,
        ];

        if ($filters['type'] === 'sales') {
            $salesQuery = $this->salesQuery($filters);
            $data['summary'] = $this->salesSummary($salesQuery);
            $data['sales'] = (clone $salesQuery)
                ->orderBy('date')
                ->orderBy('id')
                ->get();
        } elseif ($filters['type'] === 'medicines') {
            $data['medicines'] = Medicine::with('category')->orderBy('name')->get();
        } else {
            $data['bySeller'] = $this->sellersQuery($filters)->orderByDesc('total')->get();
        }

        $orientation = $filters['type'] === 'sales' ? 'landscape' : 'portrait';
        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', $orientation);
        $pdf->render();

        $canvas = $pdf->getCanvas();
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $footerY = $canvas->get_height() - 24;
        $canvas->page_text(
            36,
            $footerY,
            'Generado '.$data['generatedAt']->format('d/m/Y H:i').' | FARM SJB - Sistema de Venta de Medicamentos',
            $font,
            8,
            [90, 100, 110]
        );
        $canvas->page_text(
            $canvas->get_width() - 118,
            $footerY,
            'Página {PAGE_NUM} de {PAGE_COUNT}',
            $font,
            8,
            [90, 100, 110]
        );

        return $pdf->download('reporte-'.$filters['type'].'-'.now()->format('Ymd-His').'.pdf');
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

    private function salesSummary(Builder $salesQuery): array
    {
        $aggregate = (clone $salesQuery)
            ->reorder()
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total, COALESCE(SUM(igv), 0) as igv')
            ->first();

        return [
            'count' => (int) $aggregate->count,
            'total' => (float) $aggregate->total,
            'igv' => (float) $aggregate->igv,
            'subtotal' => (float) $aggregate->total - (float) $aggregate->igv,
            'average' => $aggregate->count ? (float) $aggregate->total / (int) $aggregate->count : 0,
        ];
    }

    private function sellersQuery(array $filters): QueryBuilder
    {
        return DB::table('sales')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->whereBetween('sales.date', [$filters['from'], $filters['to']])
            ->when($filters['user_id'], fn ($query) => $query->where('sales.user_id', $filters['user_id']))
            ->when($filters['customer_id'], fn ($query) => $query->where('sales.customer_id', $filters['customer_id']))
            ->groupBy('users.id', 'users.name')
            ->selectRaw('users.id, users.name, COUNT(sales.id) as sales_count, SUM(sales.total) as total, AVG(sales.total) as average');
    }

    private function writeSalesSheet(Worksheet $sheet, array $filters): int
    {
        $row = 2;

        $this->salesQuery($filters)
            ->orderBy('date')
            ->orderBy('id')
            ->chunk(500, function ($sales) use ($sheet, &$row): void {
                foreach ($sales as $sale) {
                    $this->writeText($sheet, "A{$row}", '#'.$sale->id);
                    $this->writeText($sheet, "B{$row}", $sale->date->format('d/m/Y H:i'));
                    $this->writeText($sheet, "C{$row}", $sale->customer?->name ?? '');
                    $this->writeText($sheet, "D{$row}", $sale->user?->name ?? '');
                    $sheet->setCellValue("E{$row}", (float) $sale->total - (float) $sale->igv);
                    $sheet->setCellValue("F{$row}", (float) $sale->igv);
                    $sheet->setCellValue("G{$row}", (float) $sale->total);
                    $this->writeText($sheet, "H{$row}", 'Registrada');
                    $row++;
                }
            });

        return $row - 1;
    }

    private function writeMedicinesSheet(Worksheet $sheet): int
    {
        $row = 2;

        Medicine::with('category')->orderBy('id')->chunkById(500, function ($medicines) use ($sheet, &$row): void {
            foreach ($medicines as $medicine) {
                $this->writeText($sheet, "A{$row}", $medicine->name);
                $this->writeText($sheet, "B{$row}", $medicine->category?->name ?? '');
                $this->writeText($sheet, "C{$row}", 'No registrado');
                $sheet->setCellValue("D{$row}", (float) $medicine->price);
                $sheet->setCellValue("E{$row}", (int) $medicine->stock);
                $this->writeText($sheet, "F{$row}", $this->stockStatus((int) $medicine->stock));
                $row++;
            }
        });

        return $row - 1;
    }

    private function writeSellersSheet(Worksheet $sheet, array $filters): int
    {
        $row = 2;

        $this->sellersQuery($filters)->orderByDesc('total')->chunk(500, function ($sellers) use ($sheet, &$row): void {
            foreach ($sellers as $seller) {
                $this->writeText($sheet, "A{$row}", $seller->name);
                $sheet->setCellValue("B{$row}", (int) $seller->sales_count);
                $sheet->setCellValue("C{$row}", (float) $seller->total);
                $sheet->setCellValue("D{$row}", (float) $seller->average);
                $row++;
            }
        });

        return $row - 1;
    }

    private function writeText(Worksheet $sheet, string $address, string $value): void
    {
        $sheet->setCellValueExplicit($address, $value, DataType::TYPE_STRING);
    }

    private function formatWorksheet(Worksheet $sheet, int $columnCount, int $lastRow, string $type): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DCE3EA'],
                ],
            ],
        ]);
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '315B72'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        for ($row = 2; $row <= $lastRow; $row += 2) {
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()->setFillType(Fill::FILL_SOLID);
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()->getStartColor()->setRGB('F3F7F9');
        }

        if ($lastRow > 1) {
            $currencyColumns = match ($type) {
                'sales' => ['E', 'F', 'G'],
                'medicines' => ['D'],
                default => ['C', 'D'],
            };

            foreach ($currencyColumns as $column) {
                $sheet->getStyle("{$column}2:{$column}{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('"S/ "#,##0.00');
                $sheet->getStyle("{$column}2:{$column}{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
        $sheet->getPageSetup()->setOrientation('landscape');

        for ($column = 1; $column <= $columnCount; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }

    private function reportName(string $type): string
    {
        return match ($type) {
            'sales' => 'Ventas',
            'medicines' => 'Medicamentos',
            default => 'Vendedores',
        };
    }

    private function periodName(string $period): string
    {
        return match ($period) {
            'today' => 'Hoy',
            'week' => 'Esta semana',
            'month' => 'Este mes',
            default => 'Periodo personalizado',
        };
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
