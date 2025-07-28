<?php

// File: app/Http/Controllers/MedicineController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicineRequest;
use App\Http\Requests\UpdateMedicineRequest;
use App\Models\Medicine;
use App\Services\MedicineService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles all HTTP requests for the Medicine module.
 * Delegates all business logic to the MedicineService.
 */
class MedicineController extends Controller
{
    protected MedicineService $medicineService;

    /**
     * Inject the MedicineService into the controller.
     */
    public function __construct(MedicineService $medicineService)
    {
        $this->medicineService = $medicineService;
    }

    /**
     * Display a listing of the medicines.
     */
    public function index(Request $request): View|JsonResponse
    {
        $medicines = $this->medicineService->getAllMedicines($request->all());

        if ($request->ajax()) {
            /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $medicines */
            return response()->json([
                'html' => view('medicines.partials.medicine_table', compact('medicines'))->render(),
                'pagination' => $medicines->links()->toHtml(),
            ]);
        }

        return view('medicines.index', compact('medicines'));
    }

    /**
     * Show the form for creating a new medicine.
     */
    public function create(): View
    {
        return view('medicines.create');
    }

    /**
     * Store a newly created medicine in storage.
     * Validation is handled by StoreMedicineRequest.
     */
    public function store(StoreMedicineRequest $request): RedirectResponse
    {
        $this->medicineService->createMedicine($request->validated());
        return redirect()->route('medicines.index')->with('success', 'Medicine added successfully.');
    }

    /**
     * Display the specified medicine.
     * Route model binding is used here.
     */
    public function show(Medicine $medicine): View
    {
        return view('medicines.show', compact('medicine'));
    }

    /**
     * Show the form for editing the specified medicine.
     */
    public function edit(Medicine $medicine): View
    {
        return view('medicines.edit', compact('medicine'));
    }

    /**
     * Update the specified medicine in storage.
     * Validation is handled by UpdateMedicineRequest.
     */
    public function update(UpdateMedicineRequest $request, Medicine $medicine): RedirectResponse
    {
        $this->medicineService->updateMedicine($medicine->id, $request->validated());
        return redirect()->route('medicines.index')->with('success', 'Medicine updated successfully.');
    }

    /**
     * Remove the specified medicine from storage.
     */
    public function destroy(Medicine $medicine): RedirectResponse
    {
        try {
            $this->medicineService->deleteMedicine($medicine->id);
            return redirect()->route('medicines.index')->with('success', 'Medicine deleted successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // --- API Methods ---

    public function getBatches(Request $request, Medicine $medicine): JsonResponse
    {
        $batches = $this->medicineService->getFormattedBatches($medicine->id, $request->query('sale_id'));
        return response()->json($batches);
    }

    public function searchWithQty(Request $request): JsonResponse
    {
        $results = $this->medicineService->getFormattedSearchWithStock($request->input('q', ''));
        return response()->json($results);
    }

    public function getGstRate(Medicine $medicine): JsonResponse
    {
        return response()->json(['gst_rate' => (float)($medicine->gst_rate ?? 0.0)]);
    }

    public function search(Request $request): JsonResponse
    {
        $results = $this->medicineService->getFormattedSearchByNameOrCompany($request->input('q', ''));
        return response()->json($results);
    }

    public function searchNames(Request $request): JsonResponse
    {
        $results = $this->medicineService->getFormattedSearchByName($request->input('q', ''));
        return response()->json($results);
    }

    public function getPacksForName(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string', 'company_name' => 'nullable|string']);
        $packs = $this->medicineService->getPacksForName($request->name, $request->company_name);
        return response()->json($packs);
    }

    public function getDetails(Medicine $medicine): JsonResponse
    {
        return response()->json([
            'name_and_company' => $medicine->name . ' (' . ($medicine->company_name ?? 'Generic') . ')',
            'name_and_company_value' => $medicine->name . '|' . ($medicine->company_name ?? ''),
            'pack' => $medicine->pack,
        ]);
    }
}