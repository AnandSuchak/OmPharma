<?php

// File: app/Http/Controllers/SaleController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\SaleService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Handles all HTTP requests for the Sale module.
 * Delegates all business logic to the SaleService.
 */
class SaleController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * Display a listing of the sales.
     */
    public function index(): View
    {
        $sales = $this->saleService->getAllSales();
        return view('sales.index', compact('sales'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create(): View
    {
        return view('sales.create', [
            'customers' => Customer::all(),
            'sale' => null,
        ]);
    }

    /**
     * Store a newly created sale in storage.
     * Validation is handled by StoreSaleRequest.
     */
    public function store(StoreSaleRequest $request): RedirectResponse
    {
        try {
            $validated = $request->safe()->all();

            // Transform extra discount fields to proper types
            foreach ($validated['new_sale_items'] as &$item) {
                $item['is_extra_discount_applied'] = !empty($item['is_extra_discount_applied']); // cast to bool
                $item['applied_extra_discount_percentage'] = isset($item['applied_extra_discount_percentage'])
                    ? (float)$item['applied_extra_discount_percentage']
                    : 0.0;
            }

            $this->saleService->createSale($validated);

            return redirect()->route('sales.index')->with('success', 'Sale created successfully.');
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Sale creation failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale): View
    {
        $sale->load('saleItems.medicine', 'customer');
        return view('sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified sale.
     */
    public function edit(Sale $sale): View
    {
        $sale->load('saleItems.medicine', 'customer');
        return view('sales.create', [
            'sale' => $sale,
            'customers' => Customer::all()
        ]);
    }

    /**
     * Update the specified sale in storage.
     * Validation is handled by UpdateSaleRequest.
     */
    public function update(UpdateSaleRequest $request, Sale $sale): RedirectResponse
    {
        try {
            $this->saleService->updateSale($sale->id, $request->validated());
            return redirect()->route('sales.index')->with('success', 'Sale updated successfully.');
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Sale update failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified sale from storage.
     */
    public function destroy(Sale $sale): RedirectResponse
    {
        try {
            $this->saleService->deleteSale($sale->id);
            return redirect()->route('sales.index')->with('success', 'Sale deleted and inventory restored.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Delete failed: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Generates a PDF of the sale bill.
     */
    public function printPdf(Sale $sale): \Illuminate\Http\Response
    {
        $sale->load('saleItems.medicine', 'customer');
        $pdf = PDF::loadView('sales.bill', compact('sale'))->setPaper('a5', 'landscape');
        return $pdf->stream('invoice-' . $sale->bill_number . '.pdf');
    }
}
