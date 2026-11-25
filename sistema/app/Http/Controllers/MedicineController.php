<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Medicine;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index()
    {
        $medicines = Medicine::with('category')->orderBy('name')->get();
        return view('medicines.index', compact('medicines'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('medicines.create', compact('categories'));
    }

    public function store(Request $request)
    {
        Medicine::create($this->validated($request));

        return to_route('medicines.index')->with('ok', 'Medicamento registrado correctamente.');
    }

    public function edit(Medicine $medicine)
    {
        $categories = Category::orderBy('name')->get();
        return view('medicines.edit', compact('medicine', 'categories'));
    }

    public function update(Request $request, Medicine $medicine)
    {
        $medicine->update($this->validated($request));

        return to_route('medicines.index')->with('ok', 'Medicamento actualizado correctamente.');
    }

    public function destroy(Medicine $medicine)
    {
        try {
            $medicine->delete();
        } catch (QueryException $e) {
            return to_route('medicines.index')->with('error', 'No se puede eliminar el medicamento porque tiene ventas registradas.');
        }

        return to_route('medicines.index')->with('ok', 'Medicamento eliminado correctamente.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|max:120',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'stock' => 'required|integer|min:0',
        ]);
    }
}
