<h3 class="mb-3">Top {{ count($topMedicines) }} {{ ucfirst($basis) }}d Medicines</h3>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Medicine Name</th>
                <th>Total Quantity {{ ucfirst($basis) }}d</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topMedicines as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->medicine->name ?? 'N/A' }}</td>
                    <td>{{ $item->total_quantity }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">No data found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
