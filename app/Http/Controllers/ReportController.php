<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Medicine;
use App\Models\SaleItem;
use App\Models\PurchaseBillItem;
use App\Models\Inventory;
use App\Models\Customer; // Added for Customer reports
use App\Models\Supplier; // Added for Supplier reports
use App\Models\Sale;     // Added for Customer reports
use App\Models\PurchaseBill; // Added for Supplier reports
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
class ReportController extends Controller
{
    /**
     * Display the main reports page.
     * This method just returns the view, all data is loaded via AJAX.
     */
    public function index(): View
    {
        return view('reports.index');
    }

    /**
     * Fetch data for the "Top Medicines" report.
     * Responds to an AJAX request.
     */
    public function fetchTopMedicines(Request $request)
    {
        $request->validate([
            'basis' => 'required|in:sale,purchase',
            'limit' => 'required|integer|min:1',
        ]);

        $basis = $request->input('basis');
        $limit = $request->input('limit');

        if ($basis === 'sale') {
            $topMedicines = SaleItem::with('medicine')
                ->select('medicine_id', DB::raw('SUM(quantity) as total_quantity'))
                ->groupBy('medicine_id')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit)
                ->get();
        } else { // purchase
            $topMedicines = PurchaseBillItem::with('medicine')
                ->select('medicine_id', DB::raw('SUM(quantity) as total_quantity'))
                ->groupBy('medicine_id')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit)
                ->get();
        }

        // We return a partial view to easily render the table on the frontend
        return view('reports.partials.top_medicines_table', compact('topMedicines', 'basis'));
    }

    /**
     * Fetch data for the "Medicine Comparison" chart.
     * Responds to an AJAX request.
     */
    public function fetchMedicineComparison(Request $request)
    {
        $request->validate([
            'medicine_id_1' => 'required|exists:medicines,id',
            'medicine_id_2' => 'required|exists:medicines,id|different:medicine_id_1',
            'period' => 'required|in:6,12,24', // months
        ]);

        $medicine1 = Medicine::findOrFail($request->medicine_id_1);
        $medicine2 = Medicine::findOrFail($request->medicine_id_2);
        $months = $request->period;

        $labels = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $labels[] = Carbon::now()->subMonths($i)->format('M Y');
        }

        $salesData1 = $this->getMonthlyData(SaleItem::class, $medicine1->id, $months);
        $purchaseData1 = $this->getMonthlyData(PurchaseBillItem::class, $medicine1->id, $months);
        
        $salesData2 = $this->getMonthlyData(SaleItem::class, $medicine2->id, $months);
        $purchaseData2 = $this->getMonthlyData(PurchaseBillItem::class, $medicine2->id, $months);

        return response()->json([
            'labels' => $labels,
            'medicine1' => [
                'name' => $medicine1->name,
                'sales' => $salesData1,
                'purchases' => $purchaseData1,
            ],
            'medicine2' => [
                'name' => $medicine2->name,
                'sales' => $salesData2,
                'purchases' => $purchaseData2,
            ],
        ]);
    }

        public function fetchMedicineDetails(Request $request)
    {
        $request->validate(['medicine_id' => 'required|exists:medicines,id']);
        $medicineId = $request->medicine_id;

        $medicine = Medicine::findOrFail($medicineId);

        // Get total current stock across all batches
        $totalStock = Inventory::where('medicine_id', $medicineId)->sum('quantity');

        // Get complete purchase history for this medicine
        $purchaseHistory = PurchaseBillItem::with('purchaseBill.supplier')
            ->where('medicine_id', $medicineId)
            ->orderByDesc('created_at')
            ->get();

        // Get all inventory batches
        $inventoryBatches = Inventory::where('medicine_id', $medicineId)
            ->where('quantity', '>', 0) // Only show batches with stock
            ->orderBy('expiry_date', 'asc')
            ->get();

        // Get all purchase items for this medicine in one query to optimize
        $allPurchaseItems = PurchaseBillItem::where('medicine_id', $medicineId)
            ->get()
            ->keyBy('batch_number'); // Key by batch_number for easy lookup

        foreach ($inventoryBatches as $batch) {
            // Find the original purchase for this batch from our collection
            $purchaseItem = $allPurchaseItems->get($batch->batch_number);
            
            // Safely check if purchaseItem exists before accessing properties
            if ($purchaseItem) {
                $batch->initial_quantity = $purchaseItem->quantity + $purchaseItem->free_quantity;
            } else {
                // Handle cases where there's no purchase record (e.g., opening stock)
                $batch->initial_quantity = 'N/A'; 
            }

            // Get all sales for this specific batch
            $batch->sales = SaleItem::with('sale.customer')
                ->where('medicine_id', $medicineId)
                ->where('batch_number', $batch->batch_number)
                ->get();
            
            // Calculate total sold from this batch
            $batch->total_sold = $batch->sales->sum('quantity');
        }

        return view('reports.partials.medicine_details', compact(
            'medicine',
            'totalStock',
            'purchaseHistory',
            'inventoryBatches'
        ));
    }

    
    /**
     * NEW: Fetch all details for a single customer report.
     */
    public function fetchCustomerDetails(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);
        $customerId = $request->customer_id;

        $customer = Customer::findOrFail($customerId);

        // Get all sales records for this customer
        $sales = Sale::with('saleItems.medicine')
            ->where('customer_id', $customerId)
            ->orderBy('sale_date', 'desc')
            ->get();
            
        $totalBusiness = $sales->sum('total_amount');

        return view('reports.partials.customer_details', compact('customer', 'sales', 'totalBusiness'));
    }

    /**
     * NEW: Fetch all details for a single supplier report.
     */
    public function fetchSupplierDetails(Request $request)
    {
        $request->validate(['supplier_id' => 'required|exists:suppliers,id']);
        $supplierId = $request->supplier_id;

        $supplier = Supplier::findOrFail($supplierId);

        // Get all purchase records from this supplier
        $purchases = PurchaseBill::with('purchaseBillItems.medicine')
            ->where('supplier_id', $supplierId)
            ->orderBy('bill_date', 'desc')
            ->get();
            
        $totalBusiness = $purchases->sum('total_amount');

        return view('reports.partials.supplier_details', compact('supplier', 'purchases', 'totalBusiness'));
    }

    
    /**
     * Helper function to get monthly sales or purchase data for a medicine.
     */
    private function getMonthlyData($model, $medicineId, $months)
    {
        $data = $model::select(
                DB::raw('SUM(quantity) as total'),
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month")
            )
            ->where('medicine_id', $medicineId)
            ->where('created_at', '>=', Carbon::now()->subMonths($months))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->all();

        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKey = Carbon::now()->subMonths($i)->format('Y-m');
            $result[] = $data[$monthKey] ?? 0;
        }
        return $result;
    }
}
