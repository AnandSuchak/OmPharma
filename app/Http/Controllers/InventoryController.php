<?php

// File: app/Http/Controllers/InventoryController.php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles all HTTP requests for the Inventory module.
 * Delegates all business logic to the InventoryService.
 */
class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    /**
     * Inject the InventoryService into the controller.
     */
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of the inventory, grouped by medicine.
     */
    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $inventories = $this->inventoryService->getGroupedInventory($request->search);

        if ($request->ajax()) {
            return response()->view('inventories.partials.table', compact('inventories'));
        }

        return view('inventories.index', compact('inventories'));
    }

    /**
     * Display the detailed inventory for a specific medicine.
     * We use Route Model Binding to automatically fetch the Medicine model.
     */
    public function show(int $medicineId): View
    {
        // The service returns an array with both 'inventoryDetails' and 'medicine' keys.
        $data = $this->inventoryService->getInventoryDetailsForMedicine($medicineId);

        return view('inventories.show', $data);
    }
}
