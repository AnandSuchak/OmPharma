<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use App\Models\Sale;
use App\Models\PurchaseBill;
use App\Models\SaleItem;
use App\Models\Inventory;
use App\Models\Customer;
use App\Models\Supplier;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // Date range filter setup
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfMonth();

        // --- Existing KPI Calculations ---
        $totalSales = Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('total_amount');
        $totalGstReceived = Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('total_gst_amount');
        $totalPurchases = PurchaseBill::whereBetween('bill_date', [$startDate, $endDate])->sum('total_amount');
        $totalGstPaid = PurchaseBill::whereBetween('bill_date', [$startDate, $endDate])->sum('total_gst_amount');
        $totalPurchaseItems = PurchaseBill::whereBetween('bill_date', [$startDate, $endDate])
            ->withCount('purchaseBillItems')
            ->get()
            ->sum('purchase_bill_items_count');
        $totalBillsGenerated = Sale::whereBetween('sale_date', [$startDate, $endDate])->count();

        // --- Existing Lists & Charts Data ---
        $mostSellingProducts = SaleItem::with('medicine')
            ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('sale_date', [$startDate, $endDate]);
            })
            ->selectRaw('medicine_id, SUM(quantity) as total_quantity_sold')
            ->groupBy('medicine_id')
            ->orderBy('total_quantity_sold', 'desc')
            ->limit(10)
            ->get();

        $expiringSoon = Inventory::with('medicine')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addMonth())
            ->where('expiry_date', '>=', now())
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->limit(10)
            ->get();

        // --- Chart Data (Last 28 days, independent of filter) ---
        $topSellingProductsChart = SaleItem::with('medicine')
            ->whereHas('sale', fn($q) => $q->where('sale_date', '>=', now()->subDays(27)->startOfDay()))
            ->selectRaw('medicine_id, SUM(quantity) as total_quantity')
            ->groupBy('medicine_id')->orderByDesc('total_quantity')->limit(5)->get()
            ->map(fn($item) => ['name' => $item->medicine->name, 'quantity' => (int) $item->total_quantity]);

        $topPurchasedProductsChart = \App\Models\PurchaseBillItem::with('medicine')
            ->whereHas('purchaseBill', fn($q) => $q->where('bill_date', '>=', now()->subDays(27)->startOfDay()))
            ->selectRaw('medicine_id, SUM(quantity) as total_quantity')
            ->groupBy('medicine_id')->orderByDesc('total_quantity')->limit(5)->get()
            ->map(fn($item) => ['name' => $item->medicine->name, 'quantity' => (int) $item->total_quantity]);

        $purchaseTrends = PurchaseBill::selectRaw('DATE(bill_date) as date, SUM(total_amount) as total')
            ->where('bill_date', '>=', now()->subDays(27)->startOfDay())->groupBy('date')->orderBy('date', 'asc')->get()
            ->map(fn($row) => ['date' => Carbon::parse($row->date)->format('d M'), 'total' => (float) $row->total]);

        $salesTrends = Sale::selectRaw('DATE(sale_date) as date, SUM(total_amount) as total')
            ->where('sale_date', '>=', now()->subDays(27)->startOfDay())->groupBy('date')->orderBy('date', 'asc')->get()
            ->map(fn($row) => ['date' => Carbon::parse($row->date)->format('d M'), 'total' => (float) $row->total]);

        // --- NEW: Top 5 Buyers (Customers) ---
        $topBuyers = Sale::with('customer')
            ->selectRaw('customer_id, SUM(total_amount) as total_spent')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // --- NEW: Top 5 Sellers (Suppliers) ---
        $topSellers = PurchaseBill::with('supplier')
            ->selectRaw('supplier_id, SUM(total_amount) as total_purchased_from')
            ->whereBetween('bill_date', [$startDate, $endDate])
            ->whereNotNull('supplier_id')
            ->groupBy('supplier_id')
            ->orderByDesc('total_purchased_from')
            ->limit(5)
            ->get();


        return view('dashboard.index', [
            'totalSales' => $totalSales,
            'totalGstReceived' => $totalGstReceived,
            'totalPurchases' => $totalPurchases,
            'totalGstPaid' => $totalGstPaid,
            'totalPurchaseItems' => $totalPurchaseItems,
            'totalBillsGenerated' => $totalBillsGenerated,
            'mostSellingProducts' => $mostSellingProducts,
            'expiringSoon' => $expiringSoon,
            'purchaseTrends' => $purchaseTrends,
            'salesTrends' => $salesTrends,
            'topSellingProducts' => $topSellingProductsChart,
            'topPurchasedProducts' => $topPurchasedProductsChart,
            'topBuyers' => $topBuyers,
            'topSellers' => $topSellers
        ]);
    }
}
