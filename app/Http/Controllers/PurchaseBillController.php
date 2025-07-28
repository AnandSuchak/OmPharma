<?php

// File: app/Http/Controllers/PurchaseBillController.php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseBillRequest;
use App\Http\Requests\UpdatePurchaseBillRequest;
use App\Models\PurchaseBill;
use App\Models\Supplier;
use App\Models\Medicine;
use App\Services\PurchaseBillService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles all HTTP requests for the PurchaseBill module.
 * Delegates all business logic to the PurchaseBillService.
 */
class PurchaseBillController extends Controller
{
    protected PurchaseBillService $purchaseBillService;

    public function __construct(PurchaseBillService $purchaseBillService)
    {
        $this->purchaseBillService = $purchaseBillService;
    }

    /**
     * Display a listing of the purchase bills.
     */
    public function index(Request $request): View|JsonResponse
    {
        $purchaseBills = $this->purchaseBillService->getAllPurchaseBills($request->all());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('purchase_bills.partials.purchase_bill_table_rows', compact('purchaseBills'))->render(),
                'pagination' => $purchaseBills->links()->toHtml()
            ]);
        }

        return view('purchase_bills.index', compact('purchaseBills'));
    }

    /**
     * Show the form for creating a new purchase bill.
     */
    public function create(): View
    {
        return view('purchase_bills.create', [
            'suppliers' => Supplier::all(),
            'medicines' => Medicine::all()
        ]);
    }

    /**
     * Store a newly created purchase bill in storage.
     * Validation is handled by StorePurchaseBillRequest.
     */
    public function store(StorePurchaseBillRequest $request): RedirectResponse
    {
        try {
            $this->purchaseBillService->createPurchaseBill($request->validated());
            return redirect()->route('purchase_bills.index')->with('success', 'Purchase bill created and inventory updated.');
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified purchase bill.
     */
    public function show(PurchaseBill $purchaseBill): View
    {
        // Eager load relations for the view
        $purchaseBill->load('supplier', 'purchaseBillItems.medicine');
        return view('purchase_bills.show', compact('purchaseBill'));
    }

    /**
     * Show the form for editing the specified purchase bill.
     */
    public function edit(PurchaseBill $purchaseBill): View
    {
        $purchaseBill->load('purchaseBillItems.medicine');
        return view('purchase_bills.edit', [
            'purchaseBill' => $purchaseBill,
            'suppliers'    => Supplier::all(),
            'medicines'    => Medicine::all()
        ]);
    }

    /**
     * Update the specified purchase bill in storage.
     * Validation is handled by UpdatePurchaseBillRequest.
     */
    public function update(UpdatePurchaseBillRequest $request, PurchaseBill $purchaseBill): RedirectResponse
    {
        try {
            $this->purchaseBillService->updatePurchaseBill($purchaseBill->id, $request->validated());
            return redirect()->route('purchase_bills.index')->with('success', 'Purchase bill updated successfully.');
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Update error: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified purchase bill from storage.
     */
    public function destroy(PurchaseBill $purchaseBill): RedirectResponse
    {
        try {
            $this->purchaseBillService->deletePurchaseBill($purchaseBill->id);
            return redirect()->route('purchase_bills.index')->with('success', 'Purchase bill deleted and inventory adjusted.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Deletion error: ' . $e->getMessage()]);
        }
    }
}
