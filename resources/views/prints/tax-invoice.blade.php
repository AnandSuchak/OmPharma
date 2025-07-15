<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tax Invoice</title>
    <style>
        /* Basic styles for the PDF */
        @page {
            margin: 5mm; /* Set page margins */
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9.5px;
            color: #000;
        }
        .invoice-container {
            width: 100%;
        }
        .printable-page {
            page-break-inside: avoid; /* Prevents breaking a section in the middle of a page */
        }
        .printable-page + .printable-page {
            page-break-before: always; /* Adds a page break before the next invoice chunk */
        }
        .border {
            border: 1px solid #dee2e6;
        }
        .p-1 {
            padding: 0.25rem;
        }
        .mb-1 {
            margin-bottom: 0.25rem;
        }
        .text-center {
            text-align: center;
        }
        .text-end {
            text-align: right;
        }
        .fw-bold {
            font-weight: bold;
        }
        .text-uppercase {
            text-transform: uppercase;
        }
        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 4px;
            vertical-align: middle;
            text-align: left;
        }
        thead th {
            background-color: #f8f9fa;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        // --- Data Preparation ---
        $chunks = $sale->saleItems->chunk(10);
        $totalDiscount = $sale->saleItems->sum(fn($item) => ($item->quantity * $item->sale_price * $item->discount_percentage) / 100);
        $subtotal = $sale->subtotal_amount ?? 0;
        $gstAmount = $sale->total_gst_amount ?? 0;
        $cgst = round($gstAmount / 2, 2);
        $sgst = round($gstAmount / 2, 2);
        $totalBeforeRoundOff = $sale->total_amount ?? 0;
        $roundedTotal = round($totalBeforeRoundOff);
        $roundOff = round($roundedTotal - $totalBeforeRoundOff, 2);
    @endphp

<div class="invoice-container">
    @foreach($chunks as $chunk)
        <div class="printable-page">
            {{-- Header --}}
            <div class="border p-1 mb-1">
                <table style="width:100%; border:0;">
                    <tr style="border:0;">
                        <td style="width:33.33%; border:0; vertical-align:top;">
                            <h6 class="fw-bold text-uppercase" style="margin:0; color:#0d6efd;">OM PHARMA</h6>
                            <div>3rd Floor, Shop 330, Jasal Complex, Rajkot</div>
                            <div>📞 7046016960 &nbsp; | &nbsp; GST: <strong>--</strong></div>
                            <div>DLN: <strong>--</strong> &nbsp; | &nbsp; FSSAI: <strong>--</strong></div>
                        </td>
                        <td style="width:33.33%; text-align:center; border:0; vertical-align:top;">
                            <h6 style="background-color:#f8f9fa; padding: 4px; border: 1px solid #dee2e6; margin:0;" class="fw-bold">TAX INVOICE</h6>
                            <div><strong>Bill:</strong> {{ $sale->bill_number }}</div>
                            <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</div>
                        </td>
                        <td style="width:33.33%; text-align:right; border:0; vertical-align:top;">
                            <h6 class="fw-bold text-uppercase" style="margin:0;">{{ $sale->customer->name }}</h6>
                            <div>{{ $sale->customer->address ?? '-' }}</div>
                            <div>
                                📞 {{ $sale->customer->contact_number ?? '-' }} &nbsp; | &nbsp;
                                GST: <strong>{{ $sale->customer->gst_number ?? '-' }}</strong> &nbsp; | &nbsp;
                                PAN: <strong>{{ $sale->customer->pan_number ?? '-' }}</strong>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Items Table --}}
            <div class="border p-1 mb-1">
                <table>
                    <thead>
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
                        @foreach($chunk as $item)
                            <tr>
                                <td class="text-center">{{ $loop->parent->index * 10 + $loop->iteration }}</td>
                                <td>{{ $item->medicine->name }}</td>
                                <td class="text-center">{{ $item->medicine->pack ?? '-' }}</td>
                                <td class="text-center">{{ $item->batch_number }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($item->expiry_date)->format('M Y') }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">₹{{ number_format($item->sale_price, 2) }}</td>
                                <td class="text-center">{{ $item->discount_percentage }}%</td>
                                <td class="text-end">₹{{ number_format(($item->quantity * $item->sale_price * $item->discount_percentage) / 100, 2) }}</td>
                                <td class="text-center">{{ $item->gst_rate }}%</td>
                                <td class="text-end">
                                    ₹{{ number_format(($item->quantity * $item->sale_price) - (($item->quantity * $item->sale_price * $item->discount_percentage) / 100), 2) }}
                                </td>
                            </tr>
                        @endforeach
                        {{-- Empty rows to fill space --}}
                        @for ($i = $chunk->count(); $i < 10; $i++)
                            <tr>
                                <td colspan="11" style="height: 23.5px; padding: 0; border-left: 1px solid #fff; border-right: 1px solid #fff;">&nbsp;</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            {{-- Totals and Footer (only on the last page) --}}
            @if ($loop->last)
            <div class="border p-1">
                 <table style="width:100%; border:0;">
                    <tr style="border:0;">
                        <td style="width:60%; border:0; vertical-align: bottom;">
                            <div style="height: 45px; border-top: 1px dashed #ccc; margin-top: 5px; padding-top: 3px;">
                                <small>For OM PHARMA (Stamp / Signature)</small>
                            </div>
                        </td>
                        <td style="width:40%; border:0;">
                            <table style="border:0;">
                                <tr style="border:0;"><td style="border:0;" class="text-end">Total Discount</td><td style="border:0;" class="text-end">₹{{ number_format($totalDiscount, 2) }}</td></tr>
                                <tr style="border:0;"><td style="border:0;" class="text-end">Subtotal (w/o GST)</td><td style="border:0;" class="text-end">₹{{ number_format($subtotal, 2) }}</td></tr>
                                <tr style="border:0;"><td style="border:0;" class="text-end">CGST</td><td style="border:0;" class="text-end">₹{{ number_format($cgst, 2) }}</td></tr>
                                <tr style="border:0;"><td style="border:0;" class="text-end">SGST</td><td style="border:0;" class="text-end">₹{{ number_format($sgst, 2) }}</td></tr>
                                <tr style="border:0;"><td style="border:0;" class="text-end">Round Off</td><td style="border:0;" class="text-end">₹{{ number_format($roundOff, 2) }}</td></tr>
                                <tr style="border-top:2px solid #000;">
                                    <td style="border:0;" class="text-end fw-bold">Grand Total</td>
                                    <td style="border:0;" class="text-end fw-bold">₹{{ number_format($roundedTotal, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            @endif
        </div>
    @endforeach
</div>

</body>
</html>