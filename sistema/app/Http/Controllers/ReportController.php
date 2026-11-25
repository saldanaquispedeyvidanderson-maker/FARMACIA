<?php

namespace App\Http\Controllers;

use App\Models\DetailSale;
use App\Models\Medicine;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /** Se considera stock bajo cuando quedan estas unidades o menos. */
    private const LOW_STOCK = 10;

    public function index(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $sales = Sale::with(['customer', 'user'])
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        $summary = [
            'count' => $sales->count(),
            'total' => $sales->sum('total'),
            'igv' => $sales->sum('igv'),
            'average' => $sales->count() ? $sales->sum('total') / $sales->count() : 0,
        ];

        $topMedicines = DetailSale::query()
            ->join('sales', 'sales.id', '=', 'detail_sales.sale_id')
            ->join('medicines', 'medicines.id', '=', 'detail_sales.medicine_id')
            ->whereBetween('sales.date', [$from, $to])
            ->groupBy('medicines.id', 'medicines.name')
            ->selectRaw('medicines.name, SUM(detail_sales.amount) as units, SUM(detail_sales.subtotal) as revenue')
            ->orderByDesc('units')
            ->limit(10)
            ->get();

        $bySeller = Sale::query()
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->whereBetween('sales.date', [$from, $to])
            ->groupBy('users.id', 'users.name')
            ->selectRaw('users.name, COUNT(*) as sales_count, SUM(sales.total) as total')
            ->orderByDesc('total')
            ->get();

        $lowStock = Medicine::with('category')
            ->where('stock', '<=', self::LOW_STOCK)
            ->orderBy('stock')
            ->get();

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'summary' => $summary,
            'topMedicines' => $topMedicines,
            'bySeller' => $bySeller,
            'lowStock' => $lowStock,
            'lowStockLimit' => self::LOW_STOCK,
        ]);
    }
}
