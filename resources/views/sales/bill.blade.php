@extends('layouts.print')

@section('title', 'Tax Invoice')

@section('content')
<div class="invoice-container">


    {{-- Header Section --}}
<div class="container border p-1 mb-2" style="font-size: 14px;">
    <div class="row align-items-start">

        {{-- LEFT: Company Info --}}
        <div class="col-4">
            <h5 class="fw-bold text-uppercase mb-1">Om Pharma</h5>
            <div class="small">3rd Floor, Shop 330, Jasal Complex</div>
            <div class="small">Nanavati Chowk, Rajkot</div>
            <div class="small">Ph: 7046016960 &nbsp; | &nbsp; GST: <strong>--</strong></div>
            <div class="small">DLN: <strong>--</strong> &nbsp; | &nbsp; FSSAI: <strong>--</strong></div>
        </div>

        {{-- CENTER: Title --}}
        <div class="col-4 text-center">
            <h6 class="bg-light text-dark py-1 border fw-bold mb-1">TAX INVOICE</h6>
            <div class="small"><strong>Bill:</strong> {{ $sale->bill_number }}</div>
            <div class="small"><strong>Date:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</div>
        </div>

        {{-- RIGHT: Customer Info --}}
        <div class="col-4 text-end">
            <h6 class="fw-bold text-uppercase mb-1">👤{{ $sale->customer->name }}</h6>
            <div class="small">{{ $sale->customer->address ?? '-' }}</div>
            <div class="small">Ph: {{ $sale->customer->phone_number ?? '-' }} &nbsp; | &nbsp; GST: <strong>{{ $sale->customer->gst_number ?? '-' }}</strong></div>
            <div class="small">DLN: <strong>{{ $sale->customer->dln ?? '-' }}</strong> &nbsp; | &nbsp; PAN: <strong>{{ $sale->customer->pan_number ?? '-' }}</strong></div>
        </div>
    </div>
</div>


    {{-- Items Table --}}
    <div class="container border p-1">
        <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Medicine</th>
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
    {{-- Real sale items (with full borders) --}}
    @foreach($sale->saleItems as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->medicine->name }}</td>
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

    {{-- Blank spacer rows without border --}}
    @for($i = $sale->saleItems->count(); $i < 10; $i++)
        <tr style="border: none;">
            <td colspan="10" style="border: none; height: 32px;"></td>
        </tr>
    @endfor
</tbody>

        </table>
    </div>

    {{-- Totals --}}
    @php
        $totalDiscount = $sale->saleItems->sum(function ($item) {
            return ($item->quantity * $item->sale_price * $item->discount_percentage) / 100;
        });
    @endphp

    <table class="table table-bordered table-sm w-100 mb-0 mt-1">
        <thead class="table-light">
            <tr>
                <th class="text-end">Total Discount</th>
                <th class="text-end">Subtotal (w/o GST)</th>
                <th class="text-end">Total GST</th>
                <th class="text-end">Grand Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-end">₹{{ number_format($totalDiscount, 2) }}</td>
                <td class="text-end">₹{{ number_format($sale->subtotal_amount, 2) }}</td>
                <td class="text-end">₹{{ number_format($sale->total_gst_amount, 2) }}</td>
                <td class="text-end fw-bold">₹{{ number_format($sale->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="text-center mt-2">
        <small class="text-muted">Thank you for your business!</small>
    </div>
</div>
@endsection
