document.addEventListener('DOMContentLoaded', function () {
    const itemsContainer = document.getElementById('sale_items_container');
    const addItemButton = document.getElementById('add_new_item');
    const itemTemplate = document.getElementById('sale_item_template')?.content?.firstElementChild?.cloneNode(true);
    let itemCount = 0;

    if (!itemTemplate) {
        console.error("Missing template with ID 'sale_item_template'");
        return;
    }

    const calculateTotals = () => {
        let subtotal = 0;
        let totalGst = 0;

        document.querySelectorAll('.sale-item').forEach(item => {
            const quantity = parseFloat(item.querySelector('.quantity-input')?.value || 0);
            const price = parseFloat(item.querySelector('input[name*="[sale_price]"]')?.value || 0);
            const gstRate = parseFloat(item.querySelector('input[name*="[gst_rate]"]')?.value || 0);

            const itemTotal = quantity * price;
            const itemGst = (itemTotal * gstRate) / 100;

            subtotal += itemTotal;
            totalGst += itemGst;
        });

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('total_gst').textContent = totalGst.toFixed(2);
        document.getElementById('grand_total').textContent = (subtotal + totalGst).toFixed(2);
    };

    const fetchAvailableQuantity = (medicineId, batchNumber, expiryDate, quantityDisplay, quantityInput) => {
        if (medicineId && batchNumber && expiryDate) {
            fetch(`/sales/medicines/${medicineId}/batches/${batchNumber}/expiry/${expiryDate}/quantity`)
                .then(response => response.json())
                .then(data => {
                    quantityDisplay.textContent = `Available: ${data.available_quantity}`;
                    quantityInput.max = data.available_quantity;
                    calculateTotals();
                })
                .catch(error => {
                    console.error('Error fetching available quantity:', error);
                    quantityDisplay.textContent = 'Error fetching quantity.';
                });
        } else {
            quantityDisplay.textContent = '';
        }
    };

    const fetchBatchInfo = (medicineId, batchNumber, expiryDate, ptrInput, sellingPriceInput, discountInput, gstInput, quantityDisplay, quantityInput) => {
        if (medicineId && batchNumber && expiryDate) {
            fetch(`/api/medicines/${medicineId}/batches`)
                .then(response => response.json())
                .then(data => {
                    const match = data.find(b => b.batch_number === batchNumber && b.expiry_date.startsWith(expiryDate));
                    if (match) {
                        ptrInput.value = match.ptr ?? '';
                        sellingPriceInput.value = match.sale_price ?? '';
                        discountInput.value = match.discount_percentage ?? '0';
                        gstInput.value = match.gst_rate ?? '';
                        quantityDisplay.textContent = `Available: ${match.quantity}`;
                        quantityInput.max = match.quantity;
                        calculateTotals();
                    }
                })
                .catch(error => console.error('Error fetching batch info:', error));
        }
    };

    const addNewItem = () => {
        const newItem = itemTemplate.cloneNode(true);

        newItem.querySelectorAll('select, input').forEach(element => {
            const originalName = element.getAttribute('name');
            if (originalName && originalName.includes('__INDEX__')) {
                element.setAttribute('name', originalName.replace('__INDEX__', itemCount));
            }
        });

        itemsContainer.appendChild(newItem);

        $(newItem).find('.select2-medicine').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Select Medicine' });
        $(newItem).find('.select2-batch').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Select Batch' });

        itemCount++;
        calculateTotals();
    };

    addItemButton?.addEventListener('click', addNewItem);

    $(document).on('change', '.medicine-select', function () {
        const medicineId = $(this).val();
        const currentRow = this.closest('.sale-item');
        if (!medicineId || !currentRow) return;

        const batchSelect = currentRow.querySelector('.batch-select');
        const expiryInput = currentRow.querySelector('.expiry-date');
        const quantityInput = currentRow.querySelector('.quantity-input');
        const quantityDisplay = currentRow.querySelector('.available-quantity');
        const ptrInput = currentRow.querySelector('input[name*="[ptr]"]');
        const sellingPriceInput = currentRow.querySelector('input[name*="[sale_price]"]');
        const discountInput = currentRow.querySelector('input[name*="[discount_percentage]"]');
        const gstInput = currentRow.querySelector('input[name*="[gst_rate]"]');

        fetch(`/api/medicines/${medicineId}/batches`)
            .then(response => response.json())
            .then(data => {
                batchSelect.innerHTML = '<option value="">Select Batch</option>';
                data.forEach(batch => {
                    const expiryShort = batch.expiry_date.slice(0, 10);
                    const option = document.createElement('option');
                    option.value = batch.batch_number;
                    option.textContent = `${batch.batch_number} - qty(${batch.quantity}) - expiry(${expiryShort})`;
                    option.dataset.expiry = expiryShort;
                    option.dataset.ptr = batch.ptr ?? '';
                    option.dataset.sellingPrice = batch.sale_price ?? '';
                    option.dataset.gstRate = batch.gst_rate ?? '';
                    option.dataset.quantity = batch.quantity;
                    batchSelect.appendChild(option);
                });

                if (data.length > 0) {
                    const firstBatch = data[0];
                    batchSelect.value = firstBatch.batch_number;
                    expiryInput.value = firstBatch.expiry_date.slice(0, 10);
                    ptrInput.value = firstBatch.ptr ?? '';
                    sellingPriceInput.value = firstBatch.sale_price ?? '';
                    discountInput.value = firstBatch.discount_percentage ?? '0';
                    gstInput.value = firstBatch.gst_rate ?? '';
                    quantityDisplay.textContent = `Available: ${firstBatch.quantity}`;
                    quantityInput.max = firstBatch.quantity;
                    calculateTotals();
                }
            })
            .catch(error => console.error('Error fetching batches:', error));
    });

    $(document).on('change', '.batch-select, .expiry-date', function () {
        const row = this.closest('.sale-item');
        const medicineId = row.querySelector('.medicine-select')?.value;
        const batchNumber = row.querySelector('.batch-select')?.value;
        const expiryDate = row.querySelector('.expiry-date')?.value;
        const ptrInput = row.querySelector('input[name*="[ptr]"]');
        const sellingPriceInput = row.querySelector('input[name*="[sale_price]"]');
        const discountInput = row.querySelector('input[name*="[discount_percentage]"]');
        const gstInput = row.querySelector('input[name*="[gst_rate]"]');
        const quantityDisplay = row.querySelector('.available-quantity');
        const quantityInput = row.querySelector('.quantity-input');

        fetchAvailableQuantity(medicineId, batchNumber, expiryDate, quantityDisplay, quantityInput);
        fetchBatchInfo(medicineId, batchNumber, expiryDate, ptrInput, sellingPriceInput, discountInput, gstInput, quantityDisplay, quantityInput);
    });

    $(document).on('input', '.quantity-input, .selling-price-input, .gst-input', calculateTotals);

    $(document).on('click', '.remove-new-item', function () {
        this.closest('.sale-item-wrapper').remove();
        calculateTotals();
    });
});
