@extends('layouts.print')

@section('title', 'Tax Invoice')

@section('content')
<div class="invoice-container">

@php
    // --- Data Preparation ---
    $chunks = $sale->saleItems->chunk(10); // Still allows for multi-page invoices if needed
    $totalDiscount = $sale->saleItems->sum(fn($item) => ($item->quantity * $item->sale_price * $item->discount_percentage) / 100);
    $subtotal = $sale->subtotal_amount ?? 0;
    $gstAmount = $sale->total_gst_amount ?? 0;
    $cgst = round($gstAmount / 2, 2);
    $sgst = round($gstAmount / 2, 2);
    $totalBeforeRoundOff = $sale->total_amount ?? 0;
    $roundedTotal = round($totalBeforeRoundOff);
    $roundOff = round($roundedTotal - $totalBeforeRoundOff, 2);
    $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
    $amountInWords = ucfirst($formatter->format($roundedTotal));
@endphp

@foreach($chunks as $chunk)
    {{-- This single div now correctly manages page breaks --}}
    <div class="printable-page">
        {{-- Header: Reduced padding (p-1) and margin (mb-1) for a tighter layout --}}
        <div class="container border p-1 mb-1" style="font-size: 10px;">
            <div class="row align-items-center">
                <div class="col-4">
                    <h6 class="fw-bold text-uppercase text-primary mb-1">OM PHARMA</h6>
                    <div>3rd Floor, Shop 330, Jasal Complex, Rajkot</div>
                    <div>📞 7046016960 &nbsp; | &nbsp; GST: <strong>--</strong></div>
                    <div>DLN: <strong>--</strong> &nbsp; | &nbsp; FSSAI: <strong>--</strong></div>
                </div>
                <div class="col-4 text-center">
                    <h6 class="bg-light text-dark py-1 border fw-bold mb-1">TAX INVOICE</h6>
                    <div><strong>Bill:</strong> {{ $sale->bill_number }}</div>
                    <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</div>
                </div>
                <div class="col-4 text-end">
                    <h6 class="fw-bold text-uppercase mb-1">👤 {{ $sale->customer->name }}</h6>
                    <div>{{ $sale->customer->address ?? '-' }}</div>
                    <div>📞 {{ $sale->customer->phone_number ?? '-' }} &nbsp; | &nbsp; GST: <strong>{{ $sale->customer->gst_number ?? '-' }}</strong></div>
                </div>
            </div>
        </div>

        {{-- Items Table: Also has reduced padding and margin --}}
        <div class="container border p-1 mb-1">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th>#</th>
                        <th>Medicine</th>
                        <th>Pack</th>
                        <th>Batch</th>
                        <th>Exp</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Disc%</th>
                        <th>Disc ₹</th>
                        <th>GST%</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chunk as $index => $item)
                        <tr>
                            <td>{{ $loop->parent->index * 10 + $index + 1 }}</td>
                            <td>{{ $item->medicine->name }}</td>
                            <td>{{ $item->medicine->pack ?? '-' }}</td>
                            <td>{{ $item->batch_number }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->expiry_date)->format('M Y') }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>₹{{ number_format($item->sale_price, 2) }}</td>
                            <td>{{ $item->discount_percentage }}%</td>
                            <td>₹{{ number_format(($item->quantity * $item->sale_price * $item->discount_percentage) / 100, 2) }}</td>
                            <td>{{ $item->gst_rate }}%</td>
                            <td class="text-end">
                                ₹{{ number_format(($item->quantity * $item->sale_price) - (($item->quantity * $item->sale_price * $item->discount_percentage) / 100), 2) }}
                            </td>
                        </tr>
                    @endforeach

                    {{-- Empty rows to fill space, with reduced height for compactness --}}
                    @for ($i = $chunk->count(); $i < 10; $i++)
                        <tr><td colspan="11" style="height: 23.5px; padding: 0; border-left: 1px solid #fff; border-right: 1px solid #fff;"></td></tr>
                    @endfor
                </tbody>
            </table>
        </div>

        {{-- Totals and Footer (only appears on the last page of the loop) --}}
        @if ($loop->last)
        <div class="container border p-1">
            <div class="row">
                <div class="col-6">
                    <div class="mb-1">
                        <strong>Amount in Words:</strong>
                        <em>{{ $amountInWords }} rupees only.</em>
                    </div>
                    <div style="height: 45px; border-top: 1px dashed #ccc; margin-top: 5px; padding-top: 3px;">
                        <small class="text-muted">For OM PHARMA (Stamp / Signature)</small>
                    </div>
                </div>
                <div class="col-6">
                    <table class="table table-sm w-100 mb-0">
                        <tbody>
                            <tr><td class="text-end border-0 w-75">Total Discount</td><td class="text-end border-0">₹{{ number_format($totalDiscount, 2) }}</td></tr>
                            <tr><td class="text-end border-0">Subtotal (w/o GST)</td><td class="text-end border-0">₹{{ number_format($subtotal, 2) }}</td></tr>
                            <tr><td class="text-end border-0">CGST</td><td class="text-end border-0">₹{{ number_format($cgst, 2) }}</td></tr>
                            <tr><td class="text-end border-0">SGST</td><td class="text-end border-0">₹{{ number_format($sgst, 2) }}</td></tr>
                            <tr><td class="text-end border-0">Round Off</td><td class="text-end border-0">₹{{ number_format($roundOff, 2) }}</td></tr>
                            <tr class="table-light border-top border-2 border-dark">
                                <td class="text-end fw-bold">Grand Total</td>
                                <td class="text-end fw-bold">₹{{ number_format($roundedTotal, 2) }}</td>
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
