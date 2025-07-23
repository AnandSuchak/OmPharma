<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryLogController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMedicineId = $request->query('medicine_id');
        $selectedBatchNumber = $request->query('batch_number');
        $showOnlyUnmatched = $request->query('show_only_unmatched', 'false') === 'true'; // Get filter state

        // Fetch all relevant current inventory data, keyed by medicine_id and batch_number
        // This is efficient to lookup current quantities for logs
        $currentInventories = Inventory::query()
            ->when($selectedMedicineId, fn($q) => $q->where('medicine_id', $selectedMedicineId))
            ->when($selectedBatchNumber, fn($q) => $q->where('batch_number', 'like', '%' . $selectedBatchNumber . '%'))
            ->get()
            ->keyBy(function($item) {
                return $item->medicine_id . '-' . $item->batch_number;
            });

        // Fetch Inventory Log Data
        $inventoryLogsQuery = InventoryLog::query()
            ->with('medicine'); // Eager load medicine for name display in logs

        if ($selectedMedicineId) {
            $inventoryLogsQuery->where('medicine_id', $selectedMedicineId);
        }
        if ($selectedBatchNumber) {
            $inventoryLogsQuery->where('batch_number', 'like', '%' . $selectedBatchNumber . '%');
        }

        // Add the current inventory quantity to each log entry for easy comparison in Blade
        $inventoryLogs = $inventoryLogsQuery
            ->orderBy('created_at', 'desc') // Order logs chronologically
            ->get() // Get all logs for filtering before pagination if needed, or paginate here
            ->map(function ($log) use ($currentInventories) {
                $key = $log->medicine_id . '-' . $log->batch_number;
                $currentInv = $currentInventories->get($key);
                $log->current_inventory_qty = $currentInv ? (float)$currentInv->quantity : 0.0;
                $log->is_matched = ($log->new_quantity_on_hand == $log->current_inventory_qty);
                return $log;
            });

        // Apply "show only unmatched" filter if requested
        if ($showOnlyUnmatched) {
            $inventoryLogs = $inventoryLogs->filter(fn($log) => !$log->is_matched);
        }

        // Manually paginate the collection after filtering and mapping
        $perPage = 20;
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $inventoryLogs->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $inventoryLogs = new \Illuminate\Pagination\LengthAwarePaginator($currentPageItems, $inventoryLogs->count(), $perPage, $currentPage, [
            'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
            'query' => $request->query(),
        ]);


        // 2. Fetch Inventory (Current Stock) Data (This table is still useful as a summary)
        // This section will remain the same as before.
        $inventoriesQuery = Inventory::query()
            ->with('medicine');

        if ($selectedMedicineId) {
            $inventoriesQuery->where('medicine_id', $selectedMedicineId);
        }
        if ($selectedBatchNumber) {
            $inventoriesQuery->where('batch_number', 'like', '%' . $selectedBatchNumber . '%');
        }

        $inventories = $inventoriesQuery->orderBy('medicine_id')->orderBy('batch_number')->get();

        // 3. Get a list of all medicines for the filter dropdown
        $medicines = Medicine::orderBy('name')->get(['id', 'name']);


        return view('inventory_logs.index', compact(
            'inventoryLogs',
            'inventories',
            'medicines',
            'selectedMedicineId',
            'selectedBatchNumber',
            'showOnlyUnmatched' // Pass filter state to view
        ));
    }
}