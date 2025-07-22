{{-- resources/views/purchase_bills/partials/purchase_bill_table_rows.blade.php --}}

@forelse ($purchaseBills as $purchaseBill)
    <tr>
        <td>{{ $purchaseBill->bill_number }}</td>
        <td>{{ $purchaseBill->supplier->name ?? 'N/A' }}</td>
        <td>{{ \Carbon\Carbon::parse($purchaseBill->bill_date)->format('d M Y') }}</td>
        <td>{{ number_format($purchaseBill->total_amount, 2) }}</td>
        <td>{{ $purchaseBill->status }}</td>
        <td>
            <a href="{{ route('purchase_bills.show', $purchaseBill->id) }}" class="btn btn-sm btn-outline-info me-1" title="View">
                <i class="fa fa-eye"></i>
            </a>
            <a href="{{ route('purchase_bills.edit', $purchaseBill->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                <i class="fa fa-edit"></i>
            </a>
            <form action="{{ route('purchase_bills.destroy', $purchaseBill->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this purchase bill?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                    <i class="fa fa-trash"></i>
                </button>
            </form>
        </td>
    </tr>
@empty
    <tr id="no_results_row">
        <td colspan="6" class="text-center text-muted">No purchase bills found.</td>
    </tr>
@endforelse