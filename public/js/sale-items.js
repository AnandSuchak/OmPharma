document.addEventListener('DOMContentLoaded', function () {
    const itemsContainer = document.getElementById('sale_items_container');
    const addItemButton = document.getElementById('add_new_item');
    const itemTemplate = document.getElementById('sale_item_template')?.content?.firstElementChild?.cloneNode(true);
    
    // Read the search URL from the data attribute
    const medicineSearchUrl = itemsContainer.dataset.searchUrl;

    let itemCount = document.querySelectorAll('.sale-item').length;

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
            const discount = parseFloat(item.querySelector('.discount-input')?.value || 0);

            const itemTotal = quantity * price;
            const discountedAmount = itemTotal * (discount / 100);
            const itemTotalAfterDiscount = itemTotal - discountedAmount;
            const itemGst = (itemTotalAfterDiscount * gstRate) / 100;

            subtotal += itemTotalAfterDiscount;
            totalGst += itemGst;
        });

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('total_gst').textContent = totalGst.toFixed(2);
        document.getElementById('grand_total').textContent = (subtotal + totalGst).toFixed(2);
    };

    const attachItemListeners = (itemElement) => {
        // Initialize Select2 for medicine name search
        $(itemElement).find('.medicine-name-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Search Medicine Name',
            ajax: {
                url: medicineSearchUrl, // Use the URL from the data attribute
                dataType: 'json',
                delay: 250,
                processResults: data => ({ results: data }),
                cache: true
            }
        });

        $(itemElement).find('.remove-new-item, .remove-existing-item').on('click', function() {
            $(this).closest('.sale-item-wrapper, .sale-item').remove();
            calculateTotals();
        });

        $(itemElement).find('input').on('input', calculateTotals);
    };

    // --- Event Listeners using Delegation ---

    $(document).on('select2:select', '.medicine-name-select', function() {
        const selectedData = $(this).select2('data')[0].id;
        const [name, company] = selectedData.split('|');
        const currentRow = this.closest('.sale-item');
        if (!name || !currentRow) return;

        const packContainer = currentRow.querySelector('.pack-selector-container');
        const packSelect = currentRow.querySelector('.pack-select');

        fetch(`/api/medicines/packs?name=${encodeURIComponent(name)}&company_name=${encodeURIComponent(company)}`)
            .then(response => response.json())
            .then(packs => {
                packSelect.innerHTML = '<option value="">Select Pack</option>'; // Clear previous options
                
                if (packs.length > 1) {
                    packs.forEach(packInfo => {
                        const option = new Option(packInfo.pack || 'Standard', packInfo.id);
                        packSelect.appendChild(option);
                    });
                    packContainer.style.display = 'block';
                } else if (packs.length === 1) {
                    packContainer.style.display = 'none';
                    const option = new Option(packs[0].pack || 'Standard', packs[0].id);
                    packSelect.appendChild(option);
                    packSelect.value = packs[0].id;
                    $(packSelect).trigger('change');
                } else {
                    packContainer.style.display = 'none';
                }
            }).catch(error => console.error('Error fetching packs:', error));
    });

    $(document).on('change', '.pack-select', function() {
        const medicineId = this.value;
        const currentRow = this.closest('.sale-item');
        if (!medicineId || !currentRow) return;

        const batchSelect = currentRow.querySelector('.batch-select');
        
        fetch(`/api/medicines/${medicineId}/batches`)
            .then(response => response.json())
            .then(data => {
                batchSelect.innerHTML = '<option value="">Select Batch</option>';
                
                // **THE FIX IS HERE:** No longer filtering. Now handles null expiry dates gracefully.
                data.forEach(batch => {
                    const expiryShort = batch.expiry_date ? batch.expiry_date.slice(0, 10) : '';
                    const optionText = batch.expiry_date
                        ? `${batch.batch_number} - qty(${batch.quantity}) - exp(${expiryShort})`
                        : `${batch.batch_number} - qty(${batch.quantity}) - (No Expiry)`;

                    const option = new Option(optionText, batch.batch_number);
                    
                    option.dataset.expiry = expiryShort; // Will be an empty string if expiry is null
                    option.dataset.ptr = batch.ptr ?? '';
                    option.dataset.sale_price = batch.sale_price ?? '';
                    option.dataset.gst_rate = batch.gst_rate ?? '';
                    option.dataset.quantity = batch.quantity;
                    batchSelect.appendChild(option);
                });

                $(batchSelect).select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Select Batch' });
                
                if (data.length > 0) {
                    $(batchSelect).val(data[0].batch_number).trigger('change');
                }
            }).catch(error => console.error('Error fetching batches:', error));
    });

    $(document).on('change', '.batch-select', function() {
        const selectedOption = $(this).find('option:selected');
        const currentRow = this.closest('.sale-item');
        if (!selectedOption.val() || !currentRow) return;

        currentRow.querySelector('.expiry-date').value = selectedOption.data('expiry');
        currentRow.querySelector('.ptr-input').value = selectedOption.data('ptr');
        currentRow.querySelector('.selling-price-input').value = selectedOption.data('sale_price');
        currentRow.querySelector('.gst-input').value = selectedOption.data('gst_rate');
        currentRow.querySelector('.available-quantity').textContent = `Available: ${selectedOption.data('quantity')}`;
        currentRow.querySelector('.quantity-input').max = selectedOption.data('quantity');
        
        calculateTotals();
    });

    const addNewItem = () => {
        const newItemWrapper = itemTemplate.cloneNode(true);
        newItemWrapper.innerHTML = newItemWrapper.innerHTML.replace(/__INDEX__/g, itemCount);
        const newItemElement = newItemWrapper.firstElementChild;
        itemsContainer.appendChild(newItemElement);
        attachItemListeners(newItemElement);
        itemCount++;
    };

    addItemButton?.addEventListener('click', addNewItem);

    document.querySelectorAll('.sale-item').forEach(item => {
        attachItemListeners(item);
    });

    calculateTotals();
});
