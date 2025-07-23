<div class="card mb-3 sale-item sale-item-wrapper" 
     data-available-quantity="{{ $item->available_quantity ?? 0 }}" 
     data-original-sold-qty="{{ $item->quantity ?? 0 }}">
    <div class="card-body p-3">
        @if(isset($item))
            <input type="hidden" name="{{ $prefix }}[id]" value="{{ $item->id }}">
        @endif

        <input type="hidden" class="medicine-id-input" name="{{ $prefix }}[medicine_id]" 
               value="{{ old("{$prefix}.medicine_id", $item->medicine_id ?? '') }}">
        <input type="hidden" class="gst-rate-input" name="{{ $prefix }}[gst_rate]" value="{{ old("{$prefix}.gst_rate", $item->gst_rate ?? 0) }}">
        <input type="hidden" class="mrp-input-hidden" name="{{ $prefix }}[ptr]" value="{{ old("{$prefix}.ptr", $item->ptr ?? '') }}">

        {{-- rest of your form fields unchanged --}}
        {{-- ... --}}
    </div>
    <div class="card-footer text-end">
        <button type="button" class="btn btn-danger remove-new-item">Remove</button>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/sale-item-utils.js') }}"></script>
<script src="{{ asset('js/sale-item-edit-utils.js') }}"></script>
@endpush
