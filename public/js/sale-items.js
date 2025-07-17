document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('sale_items_container');
    if (!container) return;

    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('sale_item_template');
    const medicineSearchUrl = container.dataset.searchUrl;
    const batchBaseUrl = container.dataset.batchBaseUrl;

    let itemCount = document.querySelectorAll('.sale-item-wrapper').length;
    const saleForm = document.querySelector('form');

    // Function to initialize event listeners and Select2 for a row
    function initializeRow(wrapper) {
        const medicineNameSelect = $(wrapper).find('.medicine-name-select');
        const packSelect = $(wrapper).find('.pack-select');
        const batchSelect = $(wrapper).find('.batch-number-select');
        const removeBtn = wrapper.querySelector('.remove-new-item');
        const quantityInput = wrapper.querySelector('.quantity-input');
        const freeQuantityInput = wrapper.querySelector('.free-qty-input');
        const salePriceInput = wrapper.querySelector('.sale-price-input');
        const discountInput = wrapper.querySelector('.discount-percentage-input');
        const packInputHidden = wrapper.querySelector('.pack-input');
        const mrpInputDisplay = wrapper.querySelector('.mrp-input');
        const gstPercentDisplay = wrapper.querySelector('.gst-percent-input');
        const gstAmountDisplay = wrapper.querySelector('.gst-amount-input');
        const expiryDateInput = wrapper.querySelector('.expiry-date-input');
        const gstRateInputHidden = wrapper.querySelector('.gst-rate-input');
        const ptrInputHidden = wrapper.querySelector('.mrp-input-hidden');
        const availableQuantityDisplay = wrapper.querySelector('.available-quantity');


        // Initialize Select2 for Medicine Name selection
        medicineNameSelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Search for medicine...',
            allowClear: true,
            ajax: {
                url: medicineSearchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data };
                },
                cache: true
            }
        });

        // Initialize Select2 for Pack selection (initially disabled)
        packSelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select pack...',
            allowClear: true
        }).prop('disabled', true);

        // Initialize Select2 for Batch selection (initially disabled)
        batchSelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select batch...',
            allowClear: true
        }).prop('disabled', true);


        // Event listener for removing an item row
        removeBtn.addEventListener('click', () => {
            const deletedInput = document.getElementById('deleted_items');
            const existingId = wrapper.dataset.itemId;
            if (existingId) {
                deletedInput.value += (deletedInput.value ? ',' : '') + existingId;
            }
            wrapper.remove();
            calculateTotals();
        });

        // Event listeners for recalculating totals on input changes
        wrapper.querySelectorAll('.item-calc').forEach(el => el.addEventListener('input', calculateTotals));


        // When medicine NAME is selected
        medicineNameSelect.on('select2:select', function(e) {
            const medicineId = e.params.data.id;
            const medicinePack = e.params.data.pack;
            
            packSelect.empty().prop('disabled', false);
            if (medicinePack) {
                packSelect.append(new Option(medicinePack, medicineId)).trigger('change');
                packSelect.val(medicineId).trigger('change');
                packInputHidden.value = medicinePack;
                fetchBatches(medicineId, wrapper);
            } else {
                 packSelect.append(new Option('N/A Pack', medicineId)).trigger('change');
                 packSelect.val(medicineId).trigger('change');
                 packInputHidden.value = '';
                 fetchBatches(medicineId, wrapper);
            }

            batchSelect.empty().trigger('change').prop('disabled', true);
            resetItemDetails(wrapper);
            calculateTotals();
        });
        
        // Clear Select2 for Pack selection when medicine NAME selection is cleared
        medicineNameSelect.on('select2:clear', function() {
            packSelect.empty().trigger('change').prop('disabled', true);
            batchSelect.empty().trigger('change').prop('disabled', true);
            packInputHidden.value = '';
            resetItemDetails(wrapper);
            calculateTotals();
        });

        // When pack is selected
        packSelect.on('select2:select', function(e) {
            const selectedMedicineId = e.params.data.id;
            const selectedPackText = e.params.data.text;
            packInputHidden.value = selectedPackText;
            fetchBatches(selectedMedicineId, wrapper);
        });
        packSelect.on('select2:clear', function() {
            batchSelect.empty().trigger('change').prop('disabled', true);
            packInputHidden.value = '';
            resetItemDetails(wrapper);
            calculateTotals();
        });


        // When batch is selected
        batchSelect.on('select2:select', function (e) {
            const selectedElement = e.params.data.element;
            if (selectedElement) {
                const data = $(selectedElement).data('batch-data');
                if (data) {
                    populateBatchDetails(wrapper, data);
                    quantityInput.disabled = false;
                    salePriceInput.disabled = false;
                    discountInput.disabled = false;
                    quantityInput.setAttribute('max', data.quantity);
                    availableQuantityDisplay.textContent = `Available: ${data.quantity}`;
                }
            }
        });

        // Validate quantity whenever the quantity input changes
        quantityInput.addEventListener('input', () => validateQuantity(quantityInput));
        // Free quantity just triggers recalculation
        freeQuantityInput.addEventListener('input', calculateTotals);
    }

    // Helper function to reset all item details (sale price, gst, etc.)
    function resetItemDetails(wrapper) {
        // Re-query elements from the wrapper argument inside this function's scope
        const salePriceInput = wrapper.querySelector('.sale-price-input');
        const mrpInputDisplay = wrapper.querySelector('.mrp-input');
        const gstPercentDisplay = wrapper.querySelector('.gst-percent-input');
        const gstAmountDisplay = wrapper.querySelector('.gst-amount-input');
        const discountInput = wrapper.querySelector('.discount-percentage-input');
        const quantityInput = wrapper.querySelector('.quantity-input');
        const freeQuantityInput = wrapper.querySelector('.free-qty-input');
        const gstRateInputHidden = wrapper.querySelector('.gst-rate-input');
        const expiryDateInput = wrapper.querySelector('.expiry-date-input');
        const ptrInputHidden = wrapper.querySelector('.mrp-input-hidden');
        const packInputHidden = wrapper.querySelector('.pack-input');
        const availableQuantityDisplay = wrapper.querySelector('.available-quantity');


        salePriceInput.value = parseFloat(0).toFixed(2);
        mrpInputDisplay.value = 'N/A';
        gstPercentDisplay.value = '0%';
        gstAmountDisplay.value = parseFloat(0).toFixed(2);
        discountInput.value = 0;
        quantityInput.value = 0;
        freeQuantityInput.value = 0;

        gstRateInputHidden.value = 0;
        expiryDateInput.value = '';
        ptrInputHidden.value = '';
        packInputHidden.value = '';

        quantityInput.disabled = true;
        salePriceInput.disabled = true;
        discountInput.disabled = true;
        quantityInput.setAttribute('max', '0');
        wrapper.dataset.availableQuantity = 0;
        availableQuantityDisplay.textContent = '';

        quantityInput.classList.remove('is-invalid');
        const existingWarning = wrapper.querySelector('.qty-warning');
        if (existingWarning) existingWarning.remove();
    }


    // Adds a new item row to the form.
    function addItem(initialData = {}) {
        const clone = template.content.cloneNode(true);
        const newElement = clone.querySelector('.sale-item-wrapper');

        const itemIndex = itemCount;

        const nameAttributeReplacementValue = initialData.id
            ? `existing_sale_items[${initialData.id}]`
            : itemIndex;

        newElement.querySelectorAll('[name*="__PREFIX__"]').forEach(input => {
            input.name = input.name.replace('__PREFIX__', nameAttributeReplacementValue);
        });

        newElement.querySelectorAll('[id*="__INDEX__"]').forEach(input => {
            input.id = input.id.replace('__INDEX__', itemIndex);
        });
        newElement.querySelectorAll('[for*="__INDEX__"]').forEach(label => {
            label.setAttribute('for', label.getAttribute('for').replace('__INDEX__', itemIndex));
        });

        container.appendChild(newElement);
        initializeRow(newElement);

        if (Object.keys(initialData).length > 0) {
            populateRow(newElement, initialData);
        } else {
            $(newElement).find('.medicine-name-select').select2('open');
        }

        itemCount++;
        calculateTotals();
    }


    function fetchBatches(medicineId, wrapper, selectedBatch = null) {
        const batchSelect = $(wrapper).find('.batch-number-select');

        console.log("DEBUG: fetchBatches called for medicineId:", medicineId);
        batchSelect.empty().trigger('change').prop('disabled', true);
        resetItemDetails(wrapper);

        batchSelect.append(new Option('Loading batches...', '', false, false)).trigger('change');

        const url = batchBaseUrl.replace('PLACEHOLDER', medicineId);
        console.log("DEBUG: Fetching batches from URL:", url);

        fetch(url)
            .then(res => {
                console.log("DEBUG: Batch fetch response status:", res.status);
                return res.ok ? res.json() : Promise.reject(res.statusText);
            })
            .then(batches => {
                console.log("DEBUG: Batches received and parsed:", batches);
                batchSelect.empty();

                if (batches.length === 0) {
                    console.log("DEBUG: No batches found.");
                    batchSelect.append(new Option('No stock available', '', true, true)).trigger('change');
                    resetItemDetails(wrapper);
                    calculateTotals();
                    return;
                }

                let selectedOption = null;
                batches.forEach((batch, index) => {
                    console.log("DEBUG: Processing batch:", batch);
                    const expiry = batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: '2-digit' }) : 'N/A';
                    const text = `${batch.batch_number} (Avl: ${batch.quantity}, Exp: ${expiry})`;
                    const option = new Option(text, batch.batch_number);
                    $(option).data('batch-data', batch);
                    batchSelect.append(option);
                    console.log("DEBUG: Appended option:", text, "with value:", batch.batch_number);

                    if (selectedBatch && batch.batch_number === selectedBatch) {
                        selectedOption = option;
                        console.log("DEBUG: Matched selectedBatch:", selectedBatch);
                    } else if (!selectedBatch && index === 0) {
                        selectedOption = option;
                        console.log("DEBUG: Auto-selecting first batch.");
                    }
                });

                batchSelect.prop('disabled', false);
                if (selectedOption) {
                    console.log("DEBUG: Attempting to select and trigger batch:", selectedOption.value);
                    batchSelect.val(selectedOption.value).trigger('change');
                    batchSelect.trigger({
                        type: 'select2:select',
                        params: {
                            data: {
                                id: selectedOption.value,
                                element: selectedOption
                            }
                        }
                    });
                } else {
                    console.log("DEBUG: No specific batch selected, just triggering change.");
                    batchSelect.trigger('change');
                }
            })
            .catch(err => {
                console.error('Error fetching batches (CATCH BLOCK):', err);
                batchSelect.empty().append(new Option('Error loading batches', '', true, true)).trigger('change');
                batchSelect.prop('disabled', true);
                resetItemDetails(wrapper);
                calculateTotals();
            });
    }
    // Populates the input fields of a sale item row with batch-specific data.
    function populateBatchDetails(wrapper, data) {
        // Re-query elements from the wrapper argument inside this function's scope
        const quantityInput = wrapper.querySelector('.quantity-input');
        const freeQuantityInput = wrapper.querySelector('.free-qty-input');
        const salePriceInput = wrapper.querySelector('.sale-price-input');
        const discountInput = wrapper.querySelector('.discount-percentage-input');
        const mrpInputDisplay = wrapper.querySelector('.mrp-input');
        const gstPercentDisplay = wrapper.querySelector('.gst-percent-input');
        const expiryDateInput = wrapper.querySelector('.expiry-date-input');
        const gstRateInputHidden = wrapper.querySelector('.gst-rate-input');
        const ptrInputHidden = wrapper.querySelector('.mrp-input-hidden');
        const availableQuantityDisplay = wrapper.querySelector('.available-quantity');

        salePriceInput.value = parseFloat(data.sale_price || 0).toFixed(2);
        mrpInputDisplay.value = data.ptr || 'N/A';
        gstPercentDisplay.value = `${data.gst_rate || 0}%`;

        gstRateInputHidden.value = data.gst_rate || 0;
        expiryDateInput.value = data.expiry_date ? new Date(data.expiry_date).toISOString().split('T')[0] : '';
        ptrInputHidden.value = data.ptr || '';

        wrapper.dataset.availableQuantity = data.quantity;
        availableQuantityDisplay.textContent = `Available: ${data.quantity}`;

        quantityInput.disabled = false;
        salePriceInput.disabled = false;
        discountInput.disabled = false;
        quantityInput.setAttribute('max', data.quantity);
        if (parseInt(quantityInput.value, 10) === 0 && data.quantity > 0) {
            quantityInput.value = 1;
        }

        validateQuantity(quantityInput);
        calculateTotals();
    }

    // Populates an entire row with existing data (used when loading an edit form or old input).
    function populateRow(wrapper, data) {
        // Re-query elements from the wrapper argument inside this function's scope
        const medicineNameSelect = $(wrapper).find('.medicine-name-select');
        const packSelect = $(wrapper).find('.pack-select');
        const batchSelect = $(wrapper).find('.batch-number-select');
        const quantityInput = wrapper.querySelector('.quantity-input');
        const freeQuantityInput = wrapper.querySelector('.free-qty-input');
        const salePriceInput = wrapper.querySelector('.sale-price-input');
        const discountInput = wrapper.querySelector('.discount-percentage-input');
        const packInputHidden = wrapper.querySelector('.pack-input');
        const mrpInputDisplay = wrapper.querySelector('.mrp-input');
        const gstPercentDisplay = wrapper.querySelector('.gst-percent-input');
        const expiryDateInput = wrapper.querySelector('.expiry-date-input');
        const gstRateInputHidden = wrapper.querySelector('.gst-rate-input');
        const ptrInputHidden = wrapper.querySelector('.mrp-input-hidden');
        const availableQuantityDisplay = wrapper.querySelector('.available-quantity');


        if (data.id) {
            const idInput = wrapper.querySelector('input[name*="[id]"]');
            if (idInput) idInput.value = data.id;
        }

        quantityInput.value = data.quantity || 1;
        freeQuantityInput.value = data.free_quantity || 0;
        salePriceInput.value = parseFloat(data.sale_price || 0).toFixed(2);
        discountInput.value = data.discount_percentage || 0;

        gstRateInputHidden.value = data.gst_rate || 0;
        expiryDateInput.value = data.expiry_date ? new Date(data.expiry_date).toISOString().split('T')[0] : '';
        ptrInputHidden.value = data.ptr || '';
        packInputHidden.value = data.pack || '';

        mrpInputDisplay.value = data.ptr || 'N/A';
        gstPercentDisplay.value = `${data.gst_rate || 0}%`;

        quantityInput.disabled = false;
        salePriceInput.disabled = false;
        discountInput.disabled = false;

        wrapper.dataset.availableQuantity = data.available_quantity || data.quantity || 0;
        quantityInput.setAttribute('max', wrapper.dataset.availableQuantity);
        availableQuantityDisplay.textContent = `Available: ${wrapper.dataset.availableQuantity}`;

        if (data.medicine_id && data.medicine_name) {
            const medicineOption = new Option(data.medicine_name, data.medicine_id, true, true);
            $(medicineOption).data('pack', data.pack);
            medicineNameSelect.append(medicineOption).trigger('change');

            if (data.pack) {
                packSelect.append(new Option(data.pack, data.medicine_id, true, true)).trigger('change');
                packSelect.val(data.medicine_id).trigger('change');
                packSelect.prop('disabled', false);
            }

            fetchBatches(data.medicine_id, wrapper, data.batch_number);
        }
        validateQuantity(quantityInput);
        calculateTotals();
    }

    // Validates the entered quantity against available stock, and corrects it if over.
    function validateQuantity(quantityInput) {
        const wrapper = quantityInput.closest('.sale-item-wrapper');
        const available = parseInt(wrapper.dataset.availableQuantity, 10);
        let requested = parseInt(quantityInput.value, 10);
        const existingWarning = wrapper.querySelector('.qty-warning');

        if (existingWarning) existingWarning.remove();

        if (isNaN(requested) || requested < 0) {
            requested = quantityInput.disabled ? 0 : 1;
        }

        if (!isNaN(available) && requested > available) {
            quantityInput.classList.add('is-invalid');
            const warning = document.createElement('div');
            warning.className = 'qty-warning text-danger small mt-1';
            warning.textContent = `Stock limit: ${available}. Quantity adjusted.`;
            quantityInput.parentNode.appendChild(warning);

            quantityInput.value = available;
            requested = available;
        } else {
            quantityInput.classList.remove('is-invalid');
        }
        quantityInput.value = requested;
        calculateTotals();
    }

    // Calculates and updates the subtotal, total GST, and grand total.
    function calculateTotals() {
        let subtotal = 0;
        let totalGst = 0;

        console.log('--- Starting calculateTotals ---');

        $('.sale-item-wrapper').each(function (index) {
            const $row = $(this);

            const qty = parseFloat($row.find('.quantity-input').val()) || 0;
            const price = parseFloat($row.find('.sale-price-input').val()) || 0;
            const discount = parseFloat($row.find('.discount-percentage-input').val()) || 0;
            const gstRate = parseFloat($row.find('.gst-rate-input').val()) || 0;

            console.log(`Item ${index + 1}:`);
            console.log(`  Quantity (qty): ${qty}`);
            console.log(`  Price (price): ${price}`);
            console.log(`  Discount (discount): ${discount}%`);
            console.log(`  GST Rate (gstRate): ${gstRate}%`);

            const lineTotal = qty * price;
            console.log(`  Line Total (qty * price): ${lineTotal}`);

            const discountedAmount = lineTotal * (1 - discount / 100);
            console.log(`  Discounted Amount: ${discountedAmount}`);

            const gstAmount = discountedAmount * (gstRate / 100);
            console.log(`  GST Amount for item: ${gstAmount}`);

            subtotal += discountedAmount;
            totalGst += gstAmount;

            console.log(`  Running Subtotal: ${subtotal.toFixed(2)}`);
            console.log(`  Running Total GST: ${totalGst.toFixed(2)}`);

            $row.find('.gst-amount-input').val(gstAmount.toFixed(2));
        });

        const grandTotal = subtotal + totalGst;

        $('#subtotal').text(subtotal.toFixed(2));
        $('#total_gst').text(totalGst.toFixed(2));
        $('#grand_total').text(grandTotal.toFixed(2));

        console.log(`Final Subtotal: ${subtotal.toFixed(2)}`);
        console.log(`Final Total GST: ${totalGst.toFixed(2)}`);
        console.log(`Final Grand Total: ${grandTotal.toFixed(2)}`);
        console.log('--- Finished calculateTotals ---');
    }

    // --- Initialization ---
    addItemBtn.addEventListener('click', () => addItem());

    if (window.oldInput && (window.oldInput.new_items || window.oldInput.existing_items)) {
        const existingItems = Object.entries(window.oldInput.existing_items || {});
        const newItems = Object.entries(window.oldInput.new_items || {});

        existingItems.forEach(([id, data]) => addItem({ ...data, id }));
        newItems.forEach(([index, data]) => addItem(data));
    } else if (document.querySelectorAll('.sale-item-wrapper').length === 0) {
        addItem();
    } else {
        document.querySelectorAll('.sale-item-wrapper').forEach(wrapper => {
            const data = {
                id: wrapper.dataset.itemId,
                medicine_id: wrapper.dataset.medicineId,
                medicine_name: wrapper.dataset.medicineName,
                batch_number: wrapper.dataset.batchNumber,
                quantity: wrapper.dataset.quantity,
                free_quantity: wrapper.dataset.freeQuantity,
                sale_price: wrapper.dataset.salePrice,
                gst_rate: wrapper.dataset.gstRate,
                discount_percentage: wrapper.dataset.discountPercentage,
                ptr: wrapper.dataset.ptr,
                pack: wrapper.dataset.pack
            };
            initializeRow(wrapper);
            populateRow(wrapper, data);
        });
        calculateTotals();
    }

    saleForm.addEventListener('submit', function(event) {
        let isValid = true;
        document.querySelectorAll('.sale-item-wrapper').forEach(wrapper => {
            const medicineNameSelect = $(wrapper).find('.medicine-name-select');
            const packSelect = $(wrapper).find('.pack-select');
            const batchSelect = $(wrapper).find('.batch-number-select');
            const quantityInput = wrapper.querySelector('.quantity-input');

            validateQuantity(quantityInput);

            if (!medicineNameSelect.val() || !packSelect.val() || !batchSelect.val() || quantityInput.classList.contains('is-invalid') || parseFloat(quantityInput.value) < 1) {
                isValid = false;
                wrapper.classList.add('border', 'border-danger', 'border-2');
            } else {
                wrapper.classList.remove('border', 'border-danger', 'border-2');
            }
        });

        if (!isValid) {
            event.preventDefault();
            alert('Please complete all item details and correct quantities before submitting.');
        }
    });

    calculateTotals();
});