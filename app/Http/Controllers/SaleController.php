<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\PurchaseBillItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SaleController extends Controller
{
    public function index(): View
    {
        $sales = Sale::with('customer')->latest()->get();
        return view('sales.index', compact('sales'));
    }

    public function create(): View
    {
        $medicines = Medicine::all();
        $customers = Customer::all();
        return view('sales.create', compact('medicines', 'customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validateSale($request);

        try {
            DB::beginTransaction();

            $billNumber = $this->generateBillNumber();
            $request->merge(['bill_number' => $billNumber]);

            $saleData = $request->except('sale_items');
            $saleData['customer_name'] = $request->filled('customer_id')
                ? Customer::find($request->customer_id)?->name ?? 'Unknown'
                : 'Guest Customer';

            $totals = $this->calculateTotals($request->sale_items);
            $saleData['total_amount'] = $totals['total'];
            $saleData['total_gst_amount'] = $totals['gst'];

            $sale = Sale::create($saleData);

            foreach ($request->sale_items as $item) {
                $this->adjustInventory($item, -$item['quantity']);
                $sale->saleItems()->create($item);
            }

            DB::commit();
            return redirect()->route('sales.bill', $sale->id)
                ->with('success', "Sale created successfully. Bill: $billNumber");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Sale creation failed: ' . $e->getMessage()]);
        }
    }

    public function show(Sale $sale): View
    {
        $sale->load('saleItems.medicine', 'customer');
        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale): View
    {
        $sale->load('saleItems.medicine');
        $medicines = Medicine::all();
        $customers = Customer::all();
        return view('sales.create', compact('sale', 'medicines', 'customers'));
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $request->validate([
            'customer_name' => 'required',
            'sale_date' => 'required|date',
            'bill_number' => 'required|unique:sales,bill_number,' . $sale->id,
            'status' => 'nullable|in:Pending,Completed,Cancelled',
            'notes' => 'nullable',
        ]);

        $sale->update($request->except(['existing_sale_items', 'sale_items']));

        // Update existing items
        if ($request->has('existing_sale_items')) {
            $this->validateSale($request, 'existing_sale_items');

            foreach ($request->input('existing_sale_items') as $itemData) {
                $item = SaleItem::findOrFail($itemData['id']);
                $diff = $itemData['quantity'] - $item->quantity;

                $this->adjustInventory($itemData, -$diff);
                $item->update(Arr::except($itemData, ['id']));
            }
        }

        // Add new items
        if ($request->has('sale_items')) {
            $this->validateSale($request, 'sale_items');

            foreach ($request->input('sale_items') as $item) {
                $this->adjustInventory($item, -$item['quantity']);
                $sale->saleItems()->create($item);
            }
        }

        // Recalculate totals
        $totals = $this->calculateTotals($sale->saleItems);
        $sale->update([
            'total_amount' => $totals['total'],
            'total_gst_amount' => $totals['gst'],
        ]);

        return redirect()->route('sales.bill', $sale->id)->with('success', 'Sale updated successfully.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $sale->load('saleItems');

            foreach ($sale->saleItems as $item) {
                $this->adjustInventory($item, $item->quantity);
            }

            $sale->delete();
            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Sale deleted and inventory restored.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Delete failed: ' . $e->getMessage()]);
        }
    }

    public function getBatchesForMedicine($medicineId)
    {
        $batches = PurchaseBillItem::where('medicine_id', $medicineId)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get([
                'batch_number',
                'expiry_date',
                'ptr',
                'gst_rate',
                'discount_percentage',
                'sale_price',
            ]);

        return response()->json($batches);
    }

    public function generateBill(Sale $sale): View
    {
        $sale->load('saleItems.medicine', 'customer');
        return view('sales.bill', compact('sale'));
    }

    private function generateBillNumber(): string
    {
        return 'SALE-' . now()->format('YmdHis') . '-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT);
    }

    private function calculateTotals(iterable $items): array
    {
        $total = 0;
        $gst = 0;

        foreach ($items as $item) {
            $line = $item['sale_price'] * $item['quantity'];
            $gst += ($line * ($item['gst_rate'] ?? 0)) / 100;
            $total += $line;
        }

        return [
            'total' => round($total, 2),
            'gst' => round($gst, 2),
        ];
    }

 private function adjustInventory(array|SaleItem $item, int $adjustQty): void
{
    $medicineId = is_array($item) ? $item['medicine_id'] : $item->medicine_id;
    $batchNumber = is_array($item) ? $item['batch_number'] : $item->batch_number;
    $expiryDate = is_array($item) ? $item['expiry_date'] : $item->expiry_date;

    $inventory = Inventory::where('medicine_id', $medicineId)
        ->where('batch_number', $batchNumber)
        ->where('expiry_date', $expiryDate)
        ->first();

    if (!$inventory) {
        throw new \Exception("Inventory not found for medicine ID $medicineId, batch $batchNumber, expiry $expiryDate.");
    }

    if ($inventory->quantity + $adjustQty < 0) {
        throw new \Exception("Insufficient stock for adjustment of medicine ID $medicineId, batch $batchNumber.");
    }

    $inventory->increment('quantity', $adjustQty);
}


    private function validateSale(Request $request, string $key = 'sale_items'): void
    {
        $request->validate([
            "$key" => 'required|array|min:1',
            "$key.*.medicine_id" => 'required|exists:medicines,id',
            "$key.*.batch_number" => 'required',
            "$key.*.expiry_date" => 'required|date|after_or_equal:today',
            "$key.*.quantity" => 'required|integer|min:1',
            "$key.*.sale_price" => 'required|numeric|min:0',
            "$key.*.ptr" => 'nullable|numeric|min:0',
            "$key.*.gst_rate" => 'nullable|numeric|min:0|max:100',
            "$key.*.discount_percentage" => 'nullable|numeric|min:0|max:100',
        ]);
    }
}
