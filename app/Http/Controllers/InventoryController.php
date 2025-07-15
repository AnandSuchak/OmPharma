<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Medicine;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of the inventory, grouped by medicine.
     */
    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $query = Inventory::query()
            ->join('medicines', 'inventories.medicine_id', '=', 'medicines.id')
            ->select(
                'inventories.medicine_id',
                'medicines.name', // Select the medicine name
                'medicines.pack', // NEW: Select the pack
                DB::raw('SUM(inventories.quantity) as total_quantity')
            )
            ->groupBy('inventories.medicine_id', 'medicines.name', 'medicines.pack') // NEW: Group by pack as well
            ->orderBy('medicines.name', 'asc'); // Order alphabetically by name

        if ($request->filled('search')) {
            $query->where('medicines.name', 'like', '%' . $request->search . '%');
        }

        $inventories = $query->paginate(10);

        if ($request->ajax()) {
            return response()->view('inventories.partials.table', compact('inventories'));
        }

        return view('inventories.index', compact('inventories'));
    }

    /**
     * Display the detailed inventory for a specific medicine.
     */
    public function show(int $medicineId): View
    {
        $inventoryDetails = Inventory::where('medicine_id', $medicineId)
            ->with('medicine') // Eager load the medicine relationship to access its properties
            ->orderBy('expiry_date')
            ->get();

        // Get medicine details from the collection, or query if the collection is empty
        // Accessing medicine from the first item in the collection is efficient.
        $medicine = $inventoryDetails->first()->medicine ?? Medicine::findOrFail($medicineId);

        return view('inventories.show', compact('inventoryDetails', 'medicine'));
    }
}