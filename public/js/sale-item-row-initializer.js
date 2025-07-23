// public/js/sale-item-row-initializer.js

// Assumes: $, resetItemDetails, validateQuantity, calculateTotals, fetchBatches, isEditMode, saleId, EXTRA_DISCOUNT_PERCENTAGE are accessible

// --- Populates input fields of a sale item row with batch-specific data (after batch selection) ---
function populateBatchDetails(wrapper, data) {
    const salePriceInput = wrapper.querySelector('.sale-price-input');
    const mrpInputDisplay = wrapper.querySelector('.mrp-input');
    const gstPercentDisplay = wrapper.querySelector('.gst-percent-input');
    const gstRateInputHidden = wrapper.querySelector('.gst-rate-input');
    const expiryDateInput = wrapper.querySelector('.expiry-date-input');
    const ptrInputHidden = wrapper.querySelector('.mrp-input-hidden');
    const availableQuantityDisplay = wrapper.querySelector('.available-quantity');
    const quantityInput = wrapper.querySelector('.quantity-input');
    const freeQuantityInput = wrapper.querySelector('.free-qty-input');
    const discountInput = wrapper.querySelector('.discount-percentage-input');
    const extraDiscountCheckbox = wrapper.querySelector('.extra-discount-checkbox');
    const appliedExtraDiscountInput = wrapper.querySelector('.applied-extra-discount-percentage');
    const packInput = wrapper.querySelector('.pack-input'); // Target the text input for pack

    if (salePriceInput) salePriceInput.value = parseFloat(data.sale_price || 0).toFixed(2);
    if (mrpInputDisplay) mrpInputDisplay.value = data.ptr || 'N/A';
    if (gstRateInputHidden) gstRateInputHidden.value = parseFloat(data.gst || 0).toFixed(2);
    if (gstPercentDisplay) gstPercentDisplay.value = `${parseFloat(data.gst || 0).toFixed(2)}%`;
    if (expiryDateInput) expiryDateInput.value = data.expiry_date ? new Date(data.expiry_date).toISOString().split('T')[0] : '';
    if (ptrInputHidden) ptrInputHidden.value = data.ptr || '';

    // --- Calculate effective available quantity ---
    let effectiveAvailableQuantity = parseFloat(data.quantity);
    if (isEditMode && data.existing_sale_item) {
        effectiveAvailableQuantity += (parseFloat(data.existing_sale_item.quantity || 0) + parseFloat(data.existing_sale_item.free_quantity || 0));
    }

    wrapper.dataset.availableQuantity = effectiveAvailableQuantity;
    if (availableQuantityDisplay) availableQuantityDisplay.textContent = `Available: ${effectiveAvailableQuantity}`;

    if (data.existing_sale_item) {
        if (quantityInput) quantityInput.value = parseFloat(data.existing_sale_item.quantity ?? 0).toFixed(2);
        if (freeQuantityInput) freeQuantityInput.value = parseFloat(data.existing_sale_item.free_quantity ?? 0).toFixed(2);
        if (salePriceInput) salePriceInput.value = parseFloat(data.existing_sale_item.sale_price || 0).toFixed(2);
        if (discountInput) discountInput.value = parseFloat(data.existing_sale_item.discount_percentage || 0);

        if (extraDiscountCheckbox && appliedExtraDiscountInput) {
            extraDiscountCheckbox.checked = !!data.existing_sale_item.is_extra_discount_applied;
            appliedExtraDiscountInput.value = parseFloat(data.existing_sale_item.applied_extra_discount_percentage || 0).toFixed(2);
        }
    } else {
        if (quantityInput) {
            if (parseInt(quantityInput.value, 10) === 0 && effectiveAvailableQuantity > 0) {
                quantityInput.value = 1;
            } else if (effectiveAvailableQuantity === 0) {
                quantityInput.value = 0;
            }
        }
        if (freeQuantityInput) freeQuantityInput.value = 0;
        if (discountInput) discountInput.value = 0;
        if (extraDiscountCheckbox) extraDiscountCheckbox.checked = false;
        if (appliedExtraDiscountInput) appliedExtraDiscountInput.value = parseFloat(0).toFixed(2);
    }

    if (quantityInput) quantityInput.disabled = false;
    if (salePriceInput) salePriceInput.disabled = false;
    if (discountInput) discountInput.disabled = false;
    if (quantityInput) quantityInput.setAttribute('max', effectiveAvailableQuantity);

    validateQuantity(quantityInput);
    calculateTotals();
}

// --- Fetch batches from API ---
// Assumes: $, resetItemDetails, populateBatchDetails, isEditMode, saleId are accessible
function fetchBatches(medicineId, wrapper, selectedBatch = null) {
    const batchSelect = $(wrapper).find('.batch-number-select');
    const medicineSearchUrl = wrapper.closest('#sale_items_container').dataset.searchUrl; // Get from container if not global
    const batchApiUrlBase = wrapper.closest('#sale_items_container').dataset.batchBaseUrl; // Get from container if not global

    batchSelect.empty().trigger('change').prop('disabled', true);
    resetItemDetails(wrapper, false);

    batchSelect.append(new Option('Loading batches...', '', false, false)).trigger('change');

    let url = batchApiUrlBase.replace('PLACEHOLDER', medicineId);

    if (isEditMode && saleId) {
        url += `?sale_id=${saleId}`;
    }

    fetch(url)
        .then(res => {
            return res.ok ? res.json() : Promise.reject(res.statusText);
        })
        .then(batches => {
            batchSelect.empty();

            if (batches.length === 0) {
                batchSelect.append(new Option('No stock available', '', true, true)).trigger('change');
                resetItemDetails(wrapper);
                calculateTotals();
                return;
            }

            batches.sort(function(a, b) {
                const expiryA = a.expiry_date ? new Date(a.expiry_date) : null;
                const expiryB = b.expiry_date ? new Date(b.expiry_date) : null;

                if (expiryA === null && expiryB !== null) return 1;
                if (expiryA !== null && expiryB === null) return -1;
                if (expiryA === null && expiryB === null) {
                    return (a.batch_number || '').localeCompare(b.batch_number || '');
                }
                return expiryA.getTime() - expiryB.getTime();
            });

            let initialBatchValue = null;

            if (selectedBatch) {
                const preselectedBatch = batches.find(batch => batch.batch_number === selectedBatch);
                if (preselectedBatch) {
                    initialBatchValue = preselectedBatch.batch_number;
                    populateBatchDetails(wrapper, preselectedBatch);
                }
            }

            if (!initialBatchValue) {
                const firstAvailableBatch = batches.find(batch => batch.quantity > 0);
                if (firstAvailableBatch) {
                    initialBatchValue = firstAvailableBatch.batch_number;
                    populateBatchDetails(wrapper, firstAvailableBatch);
                } else if (batches.length > 0) {
                    initialBatchValue = batches[0].batch_number;
                    populateBatchDetails(wrapper, batches[0]);
                }
            }

            batches.forEach((batch) => {
                const expiry = batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: '2-digit' }) : 'N/A';
                let text = `${batch.batch_number} (Avl: ${batch.quantity}, Exp: ${expiry})`;

                if (isEditMode && batch.existing_sale_item) {
                    text += ` (Sold: ${batch.existing_sale_item.quantity}, Free: ${batch.existing_sale_item.free_quantity})`;
                }

                const option = new Option(text, batch.batch_number);
                $(option).data('batch-data', batch);
                batchSelect.append(option);
            });

            batchSelect.prop('disabled', false);

            if (initialBatchValue) {
                batchSelect.val(initialBatchValue).trigger('change');
                // The select2:select trigger is often needed if .val().trigger('change') doesn't trigger it fully
                batchSelect.trigger({
                    type: 'select2:select',
                    params: {
                        data: {
                            id: initialBatchValue,
                            element: batchSelect.find(`option[value="${initialBatchValue}"]`)[0]
                        }
                    }
                });
            } else {
                batchSelect.append(new Option('No usable stock', '', true, true)).trigger('change');
                batchSelect.prop('disabled', true);
                resetItemDetails(wrapper);
            }
            calculateTotals();
        })
        .catch(err => {
            console.error("Error fetching batches:", err);
            batchSelect.empty().append(new Option('Error loading batches', '', true, true)).trigger('change');
            batchSelect.prop('disabled', true);
            resetItemDetails(wrapper);
            calculateTotals();
        });
}


// --- Initialize Row (Sets up Select2 and event listeners for a new/existing row) ---
// Assumes: $, resetItemDetails, validateQuantity, calculateTotals, fetchBatches, EXTRA_DISCOUNT_PERCENTAGE are accessible
function initializeRow(wrapper) {
    const medicineNameSelect = $(wrapper).find('.medicine-name-select');
    // Removed packSelect as it's now a text input. This variable should no longer be declared or used.
    // const packSelect = $(wrapper).find('.pack-select'); 
    const batchSelect = $(wrapper).find('.batch-number-select');
    const removeBtn = wrapper.querySelector('.remove-new-item');
    const quantityInput = wrapper.querySelector('.quantity-input');
    const freeQuantityInput = wrapper.querySelector('.free-qty-input');
    const salePriceInput = wrapper.querySelector('.sale-price-input');
    const discountInput = wrapper.querySelector('.discount-percentage-input');
    const packInput = wrapper.querySelector('.pack-input'); // Target the text input for pack
    const availableQuantityDisplay = wrapper.querySelector('.available-quantity');
    const extraDiscountCheckbox = wrapper.querySelector('.extra-discount-checkbox');
    const appliedExtraDiscountInput = wrapper.querySelector('.applied-extra-discount-percentage');

    if (extraDiscountCheckbox && appliedExtraDiscountInput) {
        appliedExtraDiscountInput.value = extraDiscountCheckbox.checked
            ? EXTRA_DISCOUNT_PERCENTAGE.toFixed(2)
            : parseFloat(0).toFixed(2);
    }

    // packContainer might still be used for layout, but packSelect/Select2 is gone
    const packContainer = wrapper.querySelector('.pack-selector-container'); 

    medicineNameSelect.select2({
        theme: 'bootstrap-5',
        placeholder: 'Search for medicine...',
        allowClear: true,
        ajax: {
            url: medicineSearchUrl, // This needs to be available in global scope or passed
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data }),
            cache: true
        }
    });

    batchSelect.select2({ theme: 'bootstrap-5', placeholder: 'Select batch...', allowClear: true }).prop('disabled', true);

    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            const deletedInput = document.getElementById('deleted_items');
            if (deletedInput && wrapper.dataset.itemId) {
                deletedInput.value += (deletedInput.value ? ',' : '') + wrapper.dataset.itemId;
            }
            wrapper.remove();
            calculateTotals();
        });
    }

    wrapper.querySelectorAll('.item-calc').forEach(el => el.addEventListener('input', calculateTotals));

    if (extraDiscountCheckbox) {
        extraDiscountCheckbox.addEventListener('change', () => {
            if (appliedExtraDiscountInput) {
                appliedExtraDiscountInput.value = extraDiscountCheckbox.checked
                    ? EXTRA_DISCOUNT_PERCENTAGE.toFixed(2)
                    : parseFloat(0).toFixed(2);
            }
            calculateTotals();
        });
    }

    medicineNameSelect.on('select2:select', e => {
        const medicineId = e.params.data.id;
        const medicinePack = e.params.data.pack;

        const medicineIdInput = wrapper.querySelector('.medicine-id-input');
        if (medicineIdInput) medicineIdInput.value = medicineId;

        // Update the editable pack input value
        if (packInput) packInput.value = medicinePack || '';

        fetchBatches(medicineId, wrapper, null);
        batchSelect.empty().trigger('change').prop('disabled', true);

        resetItemDetails(wrapper, false);
        calculateTotals();
    });

    medicineNameSelect.on('select2:clear', () => {
        if (packInput) packInput.value = ''; // Clear pack input
        batchSelect.empty().trigger('change').prop('disabled', true);
        resetItemDetails(wrapper);
        calculateTotals();
    });

    // Batch selection
    batchSelect.on('select2:select', e => {
        const data = $(e.params.data.element).data('batch-data');
        if (data) {
            populateBatchDetails(wrapper, data);
        }
    });

    if (quantityInput) quantityInput.addEventListener('input', () => validateQuantity(quantityInput));
    if (freeQuantityInput) freeQuantityInput.addEventListener('input', calculateTotals);
}
