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
        $sales = Sale::with('customer')
                     ->withoutTrashed()
                     ->latest()
                     ->paginate(10);
        
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
            return redirect()->route('sales.index')->with('success', 'Sale created successfully. You can now print the bill.');

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
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'notes' => 'nullable',
        ]);

        $saleData = $request->except(['existing_sale_items', 'new_sale_items', '_token', '_method']);
        $saleData['customer_name'] = Customer::find($request->customer_id)?->name ?? 'Unknown';
        $sale->update($saleData);

        if ($request->has('existing_sale_items')) {
            $this->validateSale($request, 'existing_sale_items');
            foreach ($request->input('existing_sale_items') as $itemData) {
                $item = SaleItem::findOrFail($itemData['id']);
                $diff = $itemData['quantity'] - $item->quantity;
                $this->adjustInventory($itemData, -$diff);
                $item->update(Arr::except($itemData, ['id']));
            }
        }

        if ($request->has('new_sale_items')) {
            $this->validateSale($request, 'new_sale_items');
            foreach ($request->input('new_sale_items') as $item) {
                $this->adjustInventory($item, -$item['quantity']);
                $sale->saleItems()->create($item);
            }
        }
        
        if ($request->has('deleted_items')) {
            foreach ($request->deleted_items as $itemId) {
                $item = SaleItem::find($itemId);
                if ($item) {
                    $this->adjustInventory($item, $item->quantity);
                    $item->delete();
                }
            }
        }

        $sale->load('saleItems');
        $totals = $this->calculateTotals($sale->saleItems);
        $sale->update([
            'total_amount' => $totals['total'],
            'total_gst_amount' => $totals['gst'],
        ]);

        return redirect()->route('sales.index')->with('success', 'Sale updated successfully.');
    }

    public function print($id)
    {
        $sale = Sale::with(['customer', 'saleItems.medicine'])->findOrFail($id);
        return view('sales.bill', compact('sale'));
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

    public function getAvailableQuantity($medicineId, $batch, $expiry)
    {
        $query = Inventory::where('medicine_id', $medicineId)
            ->where('batch_number', $batch);
            
        if ($expiry !== 'null' && $expiry) {
            $query->whereDate('expiry_date', $expiry);
        } else {
            $query->whereNull('expiry_date');
        }

        return response()->json([
            'available_quantity' => $query->value('quantity') ?? 0
        ]);
    }

    public function getBatchesForMedicine($medicineId)
    {
        $batches = Inventory::where('medicine_id', $medicineId)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get([
                'batch_number',
                'expiry_date',
                'ptr',
                'sale_price',
                'quantity'
            ]);

        return response()->json($batches);
    }

    private function generateBillNumber(): string
    {
        return 'SALE-' . now()->format('YmdHis') . '-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT);
    }

    private function calculateTotals(iterable $items): array
    {
        $subtotal = 0;
        $gst = 0;

        foreach ($items as $item) {
            $line = ($item['sale_price'] ?? 0) * ($item['quantity'] ?? 0);
            $discount = ($item['discount_percentage'] ?? 0);
            $lineAfterDiscount = $line - ($line * $discount / 100);
            $gstAmount = ($lineAfterDiscount * ($item['gst_rate'] ?? 0)) / 100;

            $subtotal += $lineAfterDiscount;
            $gst += $gstAmount;
        }

        return [
            'total' => round($subtotal + $gst, 2),
            'gst' => round($gst, 2),
        ];
    }

    private function adjustInventory(array|SaleItem $item, int $adjustQty): void
    {
        $medicineId = is_array($item) ? $item['medicine_id'] : $item->medicine_id;
        $batchNumber = is_array($item) ? $item['batch_number'] : $item->batch_number;
        $expiryDate = is_array($item) ? ($item['expiry_date'] ?: null) : $item->expiry_date;

        $inventoryQuery = Inventory::where('medicine_id', $medicineId)
            ->where('batch_number', $batchNumber);

        if ($expiryDate) {
            $inventoryQuery->where('expiry_date', $expiryDate);
        } else {
            $inventoryQuery->whereNull('expiry_date');
        }

        $inventory = $inventoryQuery->first();

        if (!$inventory) {
            throw new \Exception("Inventory not found for medicine ID $medicineId, batch $batchNumber.");
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
            "$key.*.expiry_date" => 'nullable|date',
            "$key.*.quantity" => 'required|integer|min:1',
            "$key.*.sale_price" => 'required|numeric|min:0',
            "$key.*.ptr" => 'nullable|numeric|min:0',
            "$key.*.gst_rate" => 'nullable|numeric|min:0|max:100',
            "$key.*.discount_percentage" => 'nullable|numeric|min:0|max:100',
        ]);
    }
    
    public function getDetails(Medicine $medicine)
    {
        return response()->json([
            'name_and_company' => $medicine->name . ' (' . ($medicine->company_name ?? 'Generic') . ')',
            'name_and_company_value' => $medicine->name . '|' . ($medicine->company_name ?? ''),
        ]);
    }
}