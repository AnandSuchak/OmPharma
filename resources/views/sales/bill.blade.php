@extends('layouts.print')

@section('title', 'Tax Invoice')

@section('content')
<div class="invoice-container">

@php
    // This creates pages for every 10 items
    $chunks = $sale->saleItems->chunk(10);
    $totalDiscount = $sale->saleItems->sum(fn($item) => ($item->quantity * $item->sale_price * $item->discount_percentage) / 100);
    $subtotal = $sale->subtotal_amount ?? 0;
    $gstAmount = $sale->total_gst_amount ?? 0;
    $cgst = round($gstAmount / 2, 2);
    $sgst = round($gstAmount / 2, 2);
    $totalBeforeRoundOff = $sale->total_amount ?? 0;
    $roundedTotal = round($totalBeforeRoundOff);
    $roundOff = round($roundedTotal - $totalBeforeRoundOff, 2);

    // --- NEW: Logic to calculate GST Summary ---
    $gstSummary = [];
    foreach ($sale->saleItems as $item) {
        $rate = $item->gst_rate;
        if (!isset($gstSummary[$rate])) {
            $gstSummary[$rate] = ['taxable_value' => 0, 'cgst' => 0, 'sgst' => 0, 'total_gst' => 0];
        }
        // Calculate taxable value for the item (after discount)
        $itemSubtotal = $item->quantity * $item->sale_price;
        $itemDiscountAmount = ($itemSubtotal * $item->discount_percentage) / 100;
        $taxableValue = $itemSubtotal - $itemDiscountAmount;
        
        // Calculate GST for the item
        $itemGst = $taxableValue * ($rate / 100);

        // Add to the summary array
        $gstSummary[$rate]['taxable_value'] += $taxableValue;
        $gstSummary[$rate]['cgst'] += $itemGst / 2;
        $gstSummary[$rate]['sgst'] += $itemGst / 2;
        $gstSummary[$rate]['total_gst'] += $itemGst;
    }
    ksort($gstSummary); // Sorts the array by GST rate (e.g., 5%, 12%, 18%)
@endphp

@foreach($chunks as $chunk)
    <div class="printable-page">
        {{-- Header Box --}}
        <div class="container border p-1" style="font-size: 11px;">
            <div class="row align-items-center">
                <div class="col-4 text-center border-end">
                    <h6 class="fw-bold text-uppercase text-primary mb-1">OM PHARMA</h6>
                    <div>3rd Floor, Shop 330, Jasal Complex, Rajkot</div>
                    <div>📞 7046016960 &nbsp; | &nbsp; GST: <strong>24IPLPS7448N1ZS</strong></div>
                    <div>DLN &nbsp;| 20B : <strong> 252923</strong> &nbsp; | &nbsp; 21B : <strong>252924</strong></div>
                </div>
                <div class="col-4 text-center border-end">
                    <h6 class="bg-light text-dark py-1 border fw-bold mb-1">TAX INVOICE</h6>
                    <div><strong>Bill:</strong> {{ $sale->bill_number }}</div>
                    <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</div>
                    @if($loop->count > 1)
                        <div><strong>Page:</strong> {{ $loop->iteration }} of {{ $loop->count }}</div>
                    @endif
                </div>
                <div class="col-4 text-center">
                    <h6 class="fw-bold text-uppercase mb-1">👤 {{ $sale->customer->name }}</h6>
                    <div>{{ $sale->customer->address ?? '-' }}</div>
                    <div>GST: <strong>{{ $sale->customer->gst_number ?? '-' }}</strong> &nbsp; | &nbsp; PAN: <strong>{{ $sale->customer->pan_number ?? '-' }}</strong></div>
                    <div>DL no: <strong>{{ $sale->customer->dln ?? '-' }}</strong></div>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="container border p-1">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th class="text-center-column">Pack</th>
                        <th class="text-center-column">Batch</th>
                        <th class="text-center-column">Exp</th>
                        <th class="text-center-column">Qty</th>
                        <th class="text-center-column">FQ</th>
                        <th class="text-center-column">Rate</th>
                        <th class="text-center-column">Disc%</th>
                        <th class="text-center-column">Disc ₹</th>
                        <th class="text-center-column">GST%</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chunk as $index => $item)
                        <tr>
                            <td class="text-center-column">{{ $loop->parent->index * 10 + $loop->iteration }}</td>
                            <td>{{ $item->medicine->name }}</td>
                            <td class="text-center-column">{{ $item->medicine->pack ?? '-' }}</td>
                            <td class="text-center-column">{{ $item->batch_number }}</td>
                            <td class="text-center-column">{{ \Carbon\Carbon::parse($item->expiry_date)->format('M Y') }}</td>
                            <td class="text-center-column">{{ $item->quantity }}</td>
                            <td class="text-center-column">{{ $item->free_quantity }}</td>
                            <td class="text-center-column">{{ number_format($item->sale_price, 2) }}</td>
                            <td class="text-center-column">{{ $item->discount_percentage }}%</td>
                            <td class="text-center-column">{{ number_format(($item->quantity * $item->sale_price * $item->discount_percentage) / 100, 2) }}</td>
                            <td class="text-center-column">{{ $item->gst_rate }}%</td>
                            <td class="text-end">{{ number_format(($item->quantity * $item->sale_price) - (($item->quantity * $item->sale_price * $item->discount_percentage) / 100), 2) }}</td>
                        </tr>
                    @endforeach

                    @for ($i = $chunk->count(); $i < 10; $i++)
                        <tr>
                            <td colspan="11" style="height: 24px; border: none !important;"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        {{-- Totals and Footer --}}
        @if ($loop->last)
        <div class="container border p-1">
            <div class="row">
                {{-- UPDATED: This whole 'col-7' div has been replaced --}}
                <div class="col-7">
                    <table class="table table-sm table-bordered">
                        <thead class="text-center">
                            <tr>
                                <th>GST%</th>
                                <th>Taxable Value</th>
                                <th>CGST</th>
                                <th>SGST</th>
                                <th>Total Tax</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @foreach($gstSummary as $rate => $summary)
                            <tr>
                                <td>{{ $rate }}%</td>
                                <td>{{ number_format($summary['taxable_value'], 2) }}</td>
                                <td>{{ number_format($summary['cgst'], 2) }}</td>
                                <td>{{ number_format($summary['sgst'], 2) }}</td>
                                <td>{{ number_format($summary['total_gst'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="col-5">
                    <table class="table table-sm w-100 mb-0">
                        <tbody>
                            <tr><td class="text-end border-0 w-75">Total Discount</td><td class="text-end border-0">{{ number_format($totalDiscount, 2) }}</td></tr>
                            <tr><td class="text-end border-0">Subtotal (w/o GST)</td><td class="text-end border-0">{{ number_format($subtotal, 2) }}</td></tr>
                            <tr><td class="text-end border-0">CGST</td><td class="text-end border-0">{{ number_format($cgst, 2) }}</td></tr>
                            <tr><td class="text-end border-0">SGST</td><td class="text-end border-0">{{ number_format($sgst, 2) }}</td></tr>
                            <tr><td class="text-end border-0">Round Off</td><td class="text-end border-0">{{ number_format($roundOff, 2) }}</td></tr>
                            <tr class="table-light">
                                <td class="text-end fw-bold border-top border-2 border-dark">Grand Total</td>
                                <td class="text-end fw-bold border-top border-2 border-dark">{{ number_format($roundedTotal, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
@endforeach
</div>
@endsection