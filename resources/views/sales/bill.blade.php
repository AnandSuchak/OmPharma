<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Bill - {{ $sale->bill_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
        }
        .bill-container {
            width: 80%;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .customer-details, .bill-details {
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .total-section {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="bill-container">
        <div class="header">
            <h2>Your Company Name</h2>
            <p>Your Company Address</p>
            <p>Contact: Your Phone Number</p>
        </div>

        <div class="bill-details">
            <strong>Bill Number:</strong> {{ $sale->bill_number }}<br>
<strong>Sale Date:</strong> {{ \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d H:i:s') }}
        </div>

        <div class="customer-details">
            <strong>Customer:</strong> {{ $sale->customer->name ?? 'N/A' }}<br>
            <strong>DLN:</strong> {{ $sale->customer->dln ?? 'N/A' }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Batch</th>
                    <th>Expiry</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>GST Rate</th>
                    <th>Discount</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->saleItems as $item)
                    <tr>
                        <td>{{ $item->medicine->name }}</td>
                        <td>{{ $item->batch_number }}</td>
                        <td>{{ $item->expiry_date->format('Y-m-d') }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->sale_price }}</td>
                        <td>{{ $item->gst_rate }}%</td>
                        <td>{{ $item->discount_percentage }}%</td>
                        <td>{{ number_format($item->quantity * $item->sale_price * (1 + ($item->gst_rate / 100)) * (1 - ($item->discount_percentage / 100)), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <p><strong>Subtotal:</strong> ₹{{ number_format($sale->total_amount - $sale->total_gst_amount, 2) }}</p>
            <p><strong>Total GST:</strong> ₹{{ number_format($sale->total_gst_amount, 2) }}</p>
            <p><strong>Grand Total:</strong> ₹{{ number_format($sale->total_amount, 2) }}</p>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <p>Thank you for your business!</p>
        </div>
    </div>
</body>
</html>