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
    public function index(Request $request): View
    {
        $query = Inventory::with('medicine')
            ->select('medicine_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('medicine_id');

        if ($request->filled('search')) {
            $query->whereHas('medicine', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $inventories = $query->get();

        return view('inventories.index', compact('inventories'));
    }

    /**
     * Display the detailed inventory for a specific medicine.
     */
    public function show(int $medicineId): View
    {
        $inventoryDetails = Inventory::where('medicine_id', $medicineId)
            ->with('medicine')
            ->orderBy('expiry_date')
            ->get();

        $medicine = Medicine::findOrFail($medicineId);

        return view('inventories.show', compact('inventoryDetails', 'medicine'));
    }
}
