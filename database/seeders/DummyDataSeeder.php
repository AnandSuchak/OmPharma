<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data to avoid conflicts
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PurchaseBill::truncate();
        PurchaseBillItem::truncate();
        Sale::truncate();
        SaleItem::truncate();
        Inventory::truncate();
        Medicine::truncate();
        Supplier::truncate();
        Customer::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Create a Supplier
        $supplier = Supplier::create([
            'name' => 'Global Pharma Distributors',
            'phone_number' => '9876543210',
            'email' => 'contact@globalpharma.com',
            'gst' => '27ABCDE1234F1Z5',
            'dln' => 'DLN-GP-54321',
            'address' => '123 Pharma Lane, Industrial Area, Mumbai, Maharashtra',
        ]);

        // 2. Create a Customer
        $customer = Customer::create([
            'name' => 'City Central Pharmacy',
            'contact_number' => '8765432109',
            'email' => 'orders@citycentralpharmacy.com',
            'dln' => 'DLN-CC-12345',
            'gst_number' => '29ZYXWV9876G1Z4',
            'address' => '456 Health St, Downtown, Bangalore, Karnataka',
        ]);

        // 3. Create a Purchase Bill
        $purchaseBill = PurchaseBill::create([
            'supplier_id' => $supplier->id,
            'bill_date' => now()->subDays(10),
            'bill_number' => 'PB-'.time(),
            'total_amount' => 0, // Will be updated later
            'total_gst_amount' => 0, // Will be updated later
            'status' => 'Received',
        ]);
        
        // 4. Create a Sale
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'sale_date' => now(),
            'bill_number' => 'SALE-'.time(),
            'status' => 'Completed',
            'total_amount' => 0, // Will be updated later
            'total_gst_amount' => 0, // Will be updated later
        ]);

        $purchaseTotal = 0;
        $purchaseGst = 0;
        $saleTotal = 0;
        $saleGst = 0;

        // 5. Create 15 Medicines, Purchase Items, Sale Items, and Inventory
        for ($i = 1; $i <= 15; $i++) {
            $medicine = Medicine::create([
                'name' => 'Medicine No. ' . $i,
                'company_name' => 'PharmaCo ' . chr(64 + $i),
                'pack' => '10 Tabs',
                'quantity' => 100, // This is just a base value, inventory will handle the real stock
                'gst_rate' => 12.00,
            ]);

            $purchasePrice = 100 + $i;
            $salePrice = 120 + $i;
            $quantity = 10 + $i;
            $gstRate = 12;

            // Create Purchase Bill Item
            $purchaseItem = $purchaseBill->purchaseBillItems()->create([
                'medicine_id' => $medicine->id,
                'batch_number' => 'B' . (1000 + $i),
                'expiry_date' => now()->addYears(2),
                'quantity' => $quantity * 2, // Purchase more than we sell
                'purchase_price' => $purchasePrice,
                'sale_price' => $salePrice,
                'ptr' => $purchasePrice + 10,
                'gst_rate' => $gstRate,
            ]);

            $purchaseTotal += ($purchasePrice * $quantity * 2);
            $purchaseGst += ($purchasePrice * $quantity * 2 * ($gstRate/100));

            // Create Sale Item
            $saleItem = $sale->saleItems()->create([
                'medicine_id' => $medicine->id,
                'batch_number' => 'B' . (1000 + $i),
                'expiry_date' => now()->addYears(2),
                'quantity' => $quantity,
                'sale_price' => $salePrice,
                'ptr' => $purchasePrice + 10,
                'gst_rate' => $gstRate,
            ]);

            $saleTotal += ($salePrice * $quantity);
            $saleGst += ($salePrice * $quantity * ($gstRate/100));

            // Create Inventory
            Inventory::create([
                'medicine_id' => $medicine->id,
                'batch_number' => 'B' . (1000 + $i),
                'expiry_date' => now()->addYears(2),
                'quantity' => ($quantity * 2) - $quantity, // Remaining stock
            ]);
        }
        
        // 6. Update totals on the bills
        $purchaseBill->update([
            'total_amount' => $purchaseTotal + $purchaseGst,
            'total_gst_amount' => $purchaseGst
        ]);

        $sale->update([
            'total_amount' => $saleTotal + $saleGst,
            'total_gst_amount' => $saleGst
        ]);
    }
}