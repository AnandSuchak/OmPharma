<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use App\Models\Sale;
use App\Models\PurchaseBill;
use App\Models\SaleItem;
use App\Models\Inventory;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfMonth();

        $totalSales = Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('total_amount');
        $totalGstReceived = Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('total_gst_amount');

        $totalPurchases = PurchaseBill::whereBetween('bill_date', [$startDate, $endDate])->sum('total_amount');
        $totalGstPaid = PurchaseBill::whereBetween('bill_date', [$startDate, $endDate])->sum('total_gst_amount');
        $totalPurchaseItems = PurchaseBill::whereBetween('bill_date', [$startDate, $endDate])
            ->withCount('purchaseBillItems')
            ->get()
            ->sum('purchase_bill_items_count');

        $mostSellingProducts = SaleItem::with('medicine')
            ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('sale_date', [$startDate, $endDate]);
            })
            ->selectRaw('medicine_id, SUM(quantity) as total_quantity_sold')
            ->groupBy('medicine_id')
            ->orderBy('total_quantity_sold', 'desc')
            ->limit(10)
            ->get();

        $topSellingProducts = \App\Models\SaleItem::with('medicine')
            ->whereHas('sale', function ($query) {
                $query->where('sale_date', '>=', now()->subDays(27)->startOfDay());
            })
            ->selectRaw('medicine_id, SUM(quantity) as total_quantity')
            ->groupBy('medicine_id')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->medicine->name,
                'quantity' => (int) $item->total_quantity,
            ]);

            // Top 5 purchased products (last 28 days)
        $topPurchasedProducts = \App\Models\PurchaseBillItem::with('medicine')
            ->whereHas('purchaseBill', function ($query) {
                $query->where('bill_date', '>=', now()->subDays(27)->startOfDay());
            })
            ->selectRaw('medicine_id, SUM(quantity) as total_quantity')
            ->groupBy('medicine_id')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->medicine->name,
                'quantity' => (int) $item->total_quantity,
            ]);

        $expiryDateLimit = now()->addMonth();
        $expiringSoon = Inventory::with('medicine')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $expiryDateLimit)
            ->where('expiry_date', '>=', now())
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->limit(10)
            ->get();

        // Purchase trends for last 28 days
        $purchaseTrends = PurchaseBill::selectRaw('DATE(bill_date) as date, SUM(total_amount) as total')
            ->where('bill_date', '>=', now()->subDays(27)->startOfDay())
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(fn($row) => [
                'date' => Carbon::parse($row->date)->format('d M'),
                'total' => (float) $row->total,
            ]);

        // Sales trends for last 28 days
        $salesTrends = Sale::selectRaw('DATE(sale_date) as date, SUM(total_amount) as total')
            ->where('sale_date', '>=', now()->subDays(27)->startOfDay())
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(fn($row) => [
                'date' => Carbon::parse($row->date)->format('d M'),
                'total' => (float) $row->total,
            ]);

            $totalBillsGenerated = Sale::whereBetween('sale_date', [$startDate, $endDate])->count();


   return view('dashboard.index', compact(
    'totalSales',
    'totalGstReceived',
    'totalPurchases',
    'totalGstPaid',
    'totalPurchaseItems',
    'totalBillsGenerated',
    'mostSellingProducts',
    'expiringSoon',
    'purchaseTrends',
    'salesTrends',
    'topSellingProducts',
    'topPurchasedProducts'
));
    }
}
