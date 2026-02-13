<?php

namespace App\Http\Controllers;

use App\Services\MockDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    /**
     * Display a listing of the inventory.
     */
    public function index(Request $request)
    {
        $query = $request->input('search');
        $items = $query ? MockDataService::searchInventory($query) : MockDataService::getInventory();

        return Inertia::render('Inventory/Index', [
            'inventory' => array_values($items),
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Store a newly created item in the JSON file.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'hsn_code' => 'required|string',
            'unit' => 'required|string',
        ]);

        MockDataService::addInventoryItem($validated);

        return redirect()->route('inventory.index')->with('message', 'Item added to inventory.');
    }

    /**
     * Update the specified item.
     */
    public function update(Request $request, string $name)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'hsn_code' => 'required|string',
            'unit' => 'required|string',
        ]);

        MockDataService::updateInventoryItem($name, $validated);

        return redirect()->route('inventory.index')->with('message', 'Inventory item updated.');
    }

    /**
     * Remove the specified item from the JSON file.
     */
    public function destroy(string $name)
    {
        MockDataService::deleteInventoryItem($name);

        return redirect()->route('inventory.index')->with('message', 'Item removed.');
    }
}
