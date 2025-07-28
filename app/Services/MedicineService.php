<?php

// File: app/Services/MedicineService.php

namespace App\Services;

use App\Interfaces\MedicineRepositoryInterface;
use App\Models\SaleItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

/**
 * Handles the business logic for the Medicine module.
 * It acts as an intermediary between the controller and the repository.
 */
class MedicineService
{
    protected MedicineRepositoryInterface $medicineRepository;

    public function __construct(MedicineRepositoryInterface $medicineRepository)
    {
        $this->medicineRepository = $medicineRepository;
    }

    /**
     * Get all medicines for the index page.
     */
    public function getAllMedicines(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->medicineRepository->getAllPaginated($filters);
    }

    /**
     * Create a new medicine.
     */
    public function createMedicine(array $data): \App\Models\Medicine
    {
        return $this->medicineRepository->create($data);
    }

    /**
     * Get a single medicine by its ID.
     */
    public function getMedicineById(int $id): ?\App\Models\Medicine
    {
        return $this->medicineRepository->findById($id);
    }

    /**
     * Update an existing medicine.
     */
    public function updateMedicine(int $id, array $data): bool
    {
        return $this->medicineRepository->update($id, $data);
    }

    /**
     * Delete a medicine, checking for business rule constraints first.
     */
    public function deleteMedicine(int $id): bool
    {
        if ($this->medicineRepository->hasRelatedTransactions($id)) {
            throw new Exception('Cannot delete medicine that has related transactions.');
        }
        return $this->medicineRepository->delete($id);
    }

    /**
     * Get and format batches for the sales form.
     */
    public function getFormattedBatches(int $medicineId, ?int $saleId): Collection
    {
        $batches = $saleId
            ? $this->medicineRepository->findBatchesFromPastSale($medicineId, $saleId)
            : $this->medicineRepository->findBatchesForSale($medicineId);

        // If editing a sale, we need to attach the previously sold items to their batches
        if ($saleId) {
            $existingSaleItems = SaleItem::where('sale_id', $saleId)
                ->where('medicine_id', $medicineId)
                ->get()
                ->keyBy('batch_number');

            foreach ($batches as $batch) {
                $batch->existing_sale_item = $existingSaleItems->get($batch->batch_number);
            }
        }

        // Map the raw data to the format needed by the frontend
        return $batches->map(function ($batch) {
            return [
                'batch_number'       => $batch->batch_number,
                'expiry_date'        => $batch->expiry_date ? Carbon::parse($batch->expiry_date)->format('Y-m-d') : '',
                'quantity'           => (float)($batch->quantity ?? 0.0),
                'sale_price'         => (float)($batch->sale_price ?? 0.0),
                'ptr'                => (float)($batch->ptr ?? 0.0),
                'gst'                => (float)($batch->gst_rate ?? 0.0),
                'existing_sale_item' => $batch->existing_sale_item ?? null
            ];
        })->values();
    }

    /**
     * Get and format search results for medicines with available stock.
     */
    public function getFormattedSearchWithStock(string $query): array
    {
        $medicinesWithStock = $this->medicineRepository->searchWithStock($query);
        $groupedByName = $medicinesWithStock->groupBy('name');

        $results = [];
        foreach ($groupedByName as $name => $packs) {
            $first = $packs->first();
            $companyName = $first->company_name ?? 'Generic';

            if ($packs->count() == 1) {
                $results[] = [
                    'id' => $name, // Using name as ID for the group
                    'text' => "{$name} ({$companyName}) - {$first->pack}",
                    'packs' => [[
                        'medicine_id' => $first->id,
                        'pack' => $first->pack,
                        'text' => $first->pack,
                    ]]
                ];
            } else {
                $results[] = [
                    'id' => $name,
                    'text' => "{$name} ({$companyName}) - Multiple Packs",
                    'packs' => $packs->map(function($pack) {
                        return [
                            'medicine_id' => $pack->id,
                            'pack' => $pack->pack,
                            'text' => $pack->pack,
                        ];
                    })->values()->all()
                ];
            }
        }
        return $results;
    }

    /**
     * Get and format search results by name or company.
     */
    public function getFormattedSearchByNameOrCompany(string $query): Collection
    {
        return $this->medicineRepository->searchByNameOrCompany($query)->map(function ($item) {
            $companyName = $item->company_name ?? 'Generic';
            $packDisplay = $item->pack ? " - {$item->pack}" : '';
            return [
                'id' => $item->id,
                'text' => "{$item->name} ({$companyName}){$packDisplay}",
                'pack' => $item->pack
            ];
        });
    }

    /**
     * Get and format search results by name for the purchase flow.
     */
    public function getFormattedSearchByName(string $query): Collection
    {
        return $this->medicineRepository->searchByName($query)->map(function ($medicine) {
            $company = $medicine->company_name ? " ({$medicine->company_name})" : '';
            $pack = $medicine->pack ? " - {$medicine->pack}" : '';
            return [
                'id'   => $medicine->id,
                'text' => "{$medicine->name}{$pack}{$company}",
            ];
        });
    }

    /**
     * Get all packs for a given medicine name.
     */
    public function getPacksForName(string $name, ?string $companyName): Collection
    {
        return $this->medicineRepository->findPacksByName($name, $companyName);
    }
}
