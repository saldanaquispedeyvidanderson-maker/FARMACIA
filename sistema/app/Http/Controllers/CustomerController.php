<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('name')->get();
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:50',
            'dni' => 'required|digits:8|unique:customers,dni',
            'phone' => 'nullable|digits:9',
            'age' => 'nullable|integer|min:0|max:120',
        ]);

        Customer::create($validated);

        return to_route('customers.index')->with('ok', 'Cliente registrado correctamente.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|max:50',
            'dni' => [
                'required',
                'digits:8',
                Rule::unique('customers', 'dni')->ignore($customer->id),
            ],
            'phone' => 'nullable|digits:9',
            'age' => 'nullable|integer|min:0|max:120',
        ]);

        $customer->update($validated);

        return to_route('customers.index')->with('ok', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer)
    {
        try {
            $customer->delete();
        } catch (QueryException $e) {
            return to_route('customers.index')->with('error', 'No se puede eliminar el cliente porque tiene ventas registradas.');
        }

        return to_route('customers.index')->with('ok', 'Cliente eliminado correctamente.');
    }
}
