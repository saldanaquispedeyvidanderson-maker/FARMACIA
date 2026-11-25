<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['customer', 'user'])->orderByDesc('date')->get();
        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $medicines = Medicine::where('stock', '>', 0)->orderBy('name')->get();

        return view('sales.create', compact('customers', 'users', 'medicines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|distinct|exists:medicines,id',
            'items.*.amount' => 'required|integer|min:1',
        ]);

        $sale = DB::transaction(function () use ($data) {
            $medicines = Medicine::whereIn('id', collect($data['items'])->pluck('medicine_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            $rows = [];

            foreach ($data['items'] as $i => $item) {
                $medicine = $medicines[$item['medicine_id']];

                if ($medicine->stock < $item['amount']) {
                    throw ValidationException::withMessages([
                        "items.$i.amount" => "Stock insuficiente de {$medicine->name} (disponible: {$medicine->stock}).",
                    ]);
                }

                // El precio se toma de la base de datos, nunca del formulario.
                $subtotal = round($medicine->price * $item['amount'], 2);
                $total += $subtotal;

                $rows[] = [
                    'medicine_id' => $medicine->id,
                    'amount' => $item['amount'],
                    'price_unit' => $medicine->price,
                    'subtotal' => $subtotal,
                ];

                $medicine->decrement('stock', $item['amount']);
            }

            $total = round($total, 2);
            // Los precios incluyen IGV: se separa del total.
            $igv = round($total - $total / (1 + Sale::IGV_RATE), 2);

            $sale = Sale::create([
                'customer_id' => $data['customer_id'],
                'user_id' => $data['user_id'],
                'date' => $data['date'],
                'total' => $total,
                'igv' => $igv,
            ]);

            $sale->details()->createMany($rows);

            return $sale;
        });

        return to_route('sales.show', $sale)->with('ok', 'Venta registrada correctamente.');
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'user', 'details.medicine']);
        return view('sales.show', compact('sale'));
    }

    /**
     * Anula la venta: devuelve el stock y elimina el registro.
     */
    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {
            foreach ($sale->details()->get() as $detail) {
                Medicine::whereKey($detail->medicine_id)->increment('stock', $detail->amount);
            }

            $sale->delete(); // los detalles se eliminan en cascada
        });

        return to_route('sales.index')->with('ok', 'Venta anulada y stock devuelto correctamente.');
    }
}
