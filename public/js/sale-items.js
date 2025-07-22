document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('sale_items_container');
    if (!container) return;

    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('sale_item_template');
    const medicineSearchUrl = container.dataset.searchUrl;
    const batchApiUrlBase = container.dataset.batchBaseUrl;

    // IMPORTANT: Read the new data attributes from the container
    const isEditMode = container.dataset.isEdit === 'true'; // Converts "true"|"false" string to boolean true|false
    const saleId = container.dataset.saleId; // Will be the actual ID string or an empty string ""

    let itemCount = document.querySelectorAll('.sale-item-wrapper').length;
    const saleForm = document.querySelector('form');

    // --- Constants ---
    const EXTRA_DISCOUNT_PERCENTAGE = 3;

    // --- Reset item details globally ---
    function resetItemDetails(wrapper, resetQuantity = true) {
        // MODIFIED: Replaced optional chaining for assignment with explicit null checks
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
        const extraDiscountCheckbox = wrapper.querySelector('.extra-discount-checkbox');
        const appliedExtraDiscountInput = wrapper.querySelector('.applied-extra-discount-percentage');

        if (salePriceInput) salePriceInput.value = parseFloat(0).toFixed(2);
        if (mrpInputDisplay) mrpInputDisplay.value = 'N/A';
        if (gstPercentDisplay) gstPercentDisplay.value = '0%';
        if (gstAmountDisplay) gstAmountDisplay.value = parseFloat(0).toFixed(2);
        if (discountInput) discountInput.value = 0;

        if (gstRateInputHidden) gstRateInputHidden.value = 0;
        if (expiryDateInput) expiryDateInput.value = '';
        if (ptrInputHidden) ptrInputHidden.value = '';
        if (packInputHidden) packInputHidden.value = '';

        if (extraDiscountCheckbox) extraDiscountCheckbox.checked = false;
        if (appliedExtraDiscountInput) appliedExtraDiscountInput.value = parseFloat(0).toFixed(2);

        if (resetQuantity) {
            if (quantityInput) {
                quantityInput.value = 0;
                quantityInput.disabled = true;
                quantityInput.setAttribute('max', '0');
                quantityInput.classList.remove('is-invalid');
            }
            if (freeQuantityInput) freeQuantityInput.value = 0;
            wrapper.dataset.availableQuantity = 0;
            if (availableQuantityDisplay) availableQuantityDisplay.textContent = '';
            const existingWarning = wrapper.querySelector('.qty-warning');
            if (existingWarning) existingWarning.remove();
        } else {
            if (quantityInput) quantityInput.disabled = true;
        }

        if (salePriceInput) salePriceInput.disabled = true;
        if (discountInput) discountInput.disabled = true;
    }

    // --- Initialize Row (Sets up Select2 and event listeners for a new/existing row) ---
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
        const availableQuantityDisplay = wrapper.querySelector('.available-quantity');
        const extraDiscountCheckbox = wrapper.querySelector('.extra-discount-checkbox');
        const appliedExtraDiscountInput = wrapper.querySelector('.applied-extra-discount-percentage');

        const packContainer = wrapper.querySelector('.pack-selector-container');

        medicineNameSelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Search for medicine...',
            allowClear: true,
            ajax: {
                url: medicineSearchUrl,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term }),
                processResults: data => ({ results: data }),
                cache: true
            }
        });

        packSelect.select2({ theme: 'bootstrap-5', placeholder: 'Select pack...', allowClear: true }).prop('disabled', true);
        batchSelect.select2({ theme: 'bootstrap-5', placeholder: 'Select batch...', allowClear: true }).prop('disabled', true);

        // Remove button
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                const deletedInput = document.getElementById('deleted_items');
                if (deletedInput) {
                    if (wrapper.dataset.itemId) {
                        deletedInput.value += (deletedInput.value ? ',' : '') + wrapper.dataset.itemId;
                    }
                }
                wrapper.remove();
                calculateTotals();
            });
        }

        // Quantity & discount inputs
        wrapper.querySelectorAll('.item-calc').forEach(el => el.addEventListener('input', calculateTotals));

        if (extraDiscountCheckbox) {
            extraDiscountCheckbox.addEventListener('change', () => {
                if (appliedExtraDiscountInput) {
                    appliedExtraDiscountInput.value = extraDiscountCheckbox.checked ? EXTRA_DISCOUNT_PERCENTAGE.toFixed(2) : parseFloat(0).toFixed(2);
                }
                calculateTotals();
            });
        }

        // Medicine selection (User picks a medicine name)
     medicineNameSelect.on('select2:select', e => {
            const medicineId = e.params.data.id;
            const medicinePack = e.params.data.pack;

            const medicineIdInput = wrapper.querySelector('.medicine-id-input'); // Added explicit query for this
            if (medicineIdInput) { // Added null check
                medicineIdInput.value = medicineId;
            }
            // START OF CHANGES
            const gstRateInputHidden = wrapper.querySelector('.gst-rate-input');
            const gstPercentDisplay = wrapper.querySelector('.gst-percent-input');


            // This is the CONSOLIDATED and CORRECTED pack selection logic
           // MODIFIED: Consolidated and corrected pack selection logic
            const packContainer = wrapper.querySelector('.pack-selector-container');
            const packSelectElement = wrapper.querySelector('.pack-select'); // Get native element for non-jQuery ops
            const packInputHidden = wrapper.querySelector('.pack-input'); // Ensure this is also in scope

            // Reset pack select and state
            $(packSelectElement).empty().prop('disabled', false); // Enable pack select
            if (packInputHidden) packInputHidden.value = ''; // Reset hidden pack input
            if (packContainer) packContainer.style.display = 'none'; // Default to hidden

            if (medicinePack) {
                // If a specific pack is returned by search, set it and hide the dropdown
                $(packSelectElement).append(new Option(medicinePack, medicineId, true, true)).trigger('change');
                if (packInputHidden) packInputHidden.value = medicinePack;
            } else {
                // If no specific pack, or generic medicine, still set a default option
                $(packSelectElement).append(new Option('N/A Pack', medicineId, true, true)).trigger('change');
                if (packInputHidden) packInputHidden.value = '';
            }
            $(packSelectElement).prop('disabled', true); // Disable pack select after initial set
            
            // Fetch batches after medicineId (and implicitly pack) is set
            fetchBatches(medicineId, wrapper, null); 
            
            batchSelect.empty().trigger('change').prop('disabled', true); // Clear batch select before new batches load
            
            resetItemDetails(wrapper, false); // Keep quantities, reset other details
            calculateTotals();
        });

        medicineNameSelect.on('select2:clear', () => {
            if (packContainer) packContainer.style.display = 'block'; 
            packSelect.empty().trigger('change').prop('disabled', true);
            batchSelect.empty().trigger('change').prop('disabled', true);
            if (packInputHidden) packInputHidden.value = '';
            resetItemDetails(wrapper);
            calculateTotals();
        });

        // Pack selection
        packSelect.on('select2:select', e => {
            if (packInputHidden) packInputHidden.value = e.params.data.text;
            fetchBatches(e.params.data.id, wrapper, null);
        });

        packSelect.on('select2:clear', () => {
            batchSelect.empty().trigger('change').prop('disabled', true);
            if (packInputHidden) packInputHidden.value = '';
            resetItemDetails(wrapper);
            calculateTotals();
        });

        // Batch selection
        batchSelect.on('select2:select', e => {
            const data = $(e.params.data.element).data('batch-data');
            if (data) {
                populateBatchDetails(wrapper, data);
                // Enable relevant inputs after a batch is selected
                if (quantityInput) quantityInput.disabled = false;
                if (salePriceInput) salePriceInput.disabled = false;
                if (discountInput) discountInput.disabled = false;
                if (quantityInput) quantityInput.setAttribute('max', data.quantity);
                if (availableQuantityDisplay) availableQuantityDisplay.textContent = `Available: ${data.quantity}`;
            }
        });

        // Quantity & Free Quantity listeners
        if (quantityInput) quantityInput.addEventListener('input', () => validateQuantity(quantityInput));
        if (freeQuantityInput) freeQuantityInput.addEventListener('input', calculateTotals);
    }

    // --- Validation for pack selection (remains the same) ---
    function validateRow(wrapper) {
        const errors = [];
        const packSelect = $(wrapper).find('.pack-select');
        const medicineIdInput = wrapper.querySelector('.medicine-id-input'); // Explicit query
        const medicineId = medicineIdInput ? medicineIdInput.value : ''; // Null check

        if (medicineId && !packSelect.prop('disabled') && packSelect.find('option').length > 0 && !packSelect.val()) {
            errors.push("Pack not selected");
        }
        return errors;
    }

    // --- Add new item to the form dynamically ---
   function addItem(initialData = {}) {
    if (!template) {
        console.error('The sale_item_template was not found!');
        return;
    }

    const clone = template.content.cloneNode(true);
    let content = new XMLSerializer().serializeToString(clone);
    content = content.replace(/__INDEX__/g, itemCount);

    const newWrapper = document.createElement('div');
    newWrapper.innerHTML = content;
    const newElement = newWrapper.firstElementChild;

    container.appendChild(newElement);
    initializeRow(newElement);

    if (Object.keys(initialData).length > 0) {
        const nameSelect = $(newElement).find('.medicine-name-select');
        // Ensure medicine_text is passed for Select2 re-population
        if (initialData.medicine_id && (initialData.medicine_text || initialData.medicine_name)) {
            var option = new Option(initialData.medicine_text || initialData.medicine_name, initialData.medicine_id, true, true);
            nameSelect.append(option).trigger('change');
        }

        // MODIFIED: MOVED THESE LINES INSIDE THIS BLOCK
        const medicineIdInput = newElement.querySelector('.medicine-id-input');
        if (medicineIdInput) {
            medicineIdInput.value = initialData.medicine_id ?? '';
        }
        // END MODIFIED

        const packSelect = newElement.querySelector('.pack-select');
        if (packSelect) packSelect.innerHTML = `<option value="${initialData.medicine_id}" selected>${initialData.pack || 'Standard'}</option>`;

        if (newElement.querySelector('[name$="[batch_number]"]')) newElement.querySelector('[name$="[batch_number]"]').value = initialData.batch_number || '';
        if (newElement.querySelector('[name$="[expiry_date]"]')) newElement.querySelector('[name$="[expiry_date]"]').value = initialData.expiry_date || '';
        
        // Ensure quantity and free_quantity are correctly set for new items
        if (newElement.querySelector('[name$="[quantity]"]')) newElement.querySelector('[name$="[quantity]"]').value = parseFloat(initialData.quantity || 1).toFixed(2);
        if (newElement.querySelector('[name$="[free_quantity]"]')) newElement.querySelector('[name$="[free_quantity]"]').value = parseFloat(initialData.free_quantity || 0).toFixed(2);

        if (newElement.querySelector('[name$="[sale_price]"]')) newElement.querySelector('[name$="[sale_price]"]').value = parseFloat(initialData.sale_price || 0).toFixed(2);
        if (newElement.querySelector('[name$="[discount_percentage]"]')) newElement.querySelector('[name$="[discount_percentage]"]').value = parseFloat(initialData.discount_percentage || 0).toFixed(2);
        
        // Set extra discount fields based on initialData
        const extraDiscountCheckbox = newElement.querySelector('.extra-discount-checkbox');
        const appliedExtraDiscountInput = newElement.querySelector('.applied-extra-discount-percentage');
        if (extraDiscountCheckbox) extraDiscountCheckbox.checked = initialData.is_extra_discount_applied === true; // Ensure boolean comparison
        if (appliedExtraDiscountInput) appliedExtraDiscountInput.value = parseFloat(initialData.applied_extra_discount_percentage || 0).toFixed(2);

        if (newElement.querySelector('[name$="[gst_rate]"]')) newElement.querySelector('[name$="[gst_rate]"]').value = parseFloat(initialData.gst_rate || 0).toFixed(2);
        if (newElement.querySelector('[name$="[ptr]"]')) newElement.querySelector('[name$="[ptr]"]').value = initialData.ptr || ''; // PTR is often text, not number

        // Trigger the two-way update for the newly added item
     

    } else {
        // For new, empty rows, open select2 directly
        $(newElement).find('.medicine-name-select').select2('open');
    }

    itemCount++;
    calculateTotals();
}
    // --- fetchBatches function (Unified for Create and Edit) ---
    // Pass selectedBatch to pre-select if loading an existing item
    function fetchBatches(medicineId, wrapper, selectedBatch = null) {
        const batchSelect = $(wrapper).find('.batch-number-select');

        batchSelect.empty().trigger('change').prop('disabled', true);
        resetItemDetails(wrapper, false); // Don't reset quantity here; populateBatchDetails will handle true value

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

                // --- START MODIFICATION: Batch Sorting Logic ---
                batches.sort(function(a, b) {
                    const expiryA = a.expiry_date ? new Date(a.expiry_date) : null;
                    const expiryB = b.expiry_date ? new Date(b.expiry_date) : null;

                    // Prioritize batches with actual expiry dates over null ones
                    if (expiryA === null && expiryB !== null) return 1; // 'a' is null, 'b' (valid) comes first
                    if (expiryA !== null && expiryB === null) return -1; // 'a' (valid) comes first, 'b' (null) comes after

                    // If both are null, sort by batch number (alphanumeric, simulates "oldest by ID")
                    if (expiryA === null && expiryB === null) {
                        return (a.batch_number || '').localeCompare(b.batch_number || '');
                    }

                    // For valid expiry dates, sort by nearest date (ascending order)
                    return expiryA.getTime() - expiryB.getTime();
                });
                // --- END MODIFICATION: Batch Sorting Logic ---

                let initialBatchValue = null;
                let initialBatchData = null;

                // 1. Try to pre-select the batch if 'selectedBatch' is provided (from populateRow, for existing sale items)
                if (selectedBatch) {
                    const preselectedBatch = batches.find(batch => batch.batch_number === selectedBatch);
                    if (preselectedBatch) {
                        initialBatchValue = preselectedBatch.batch_number;
                        initialBatchData = preselectedBatch;
                    }
                }

                // 2. If no specific batch was pre-selected, determine the default for new items or re-selection
                if (!initialBatchValue) {
                    // For new sale items or when re-selecting, find the first available batch with quantity > 0
                    const firstAvailableBatch = batches.find(batch => batch.quantity > 0);
                    if (firstAvailableBatch) {
                        initialBatchValue = firstAvailableBatch.batch_number;
                        initialBatchData = firstAvailableBatch;
                    } else if (batches.length > 0) {
                        // If no batch has quantity > 0 but there are batches, select the first one in the sorted list (might have 0 qty)
                        initialBatchValue = batches[0].batch_number;
                        initialBatchData = batches[0];
                    }
                }

                // Populate the dropdown with all sorted batches
                batches.forEach((batch) => {
                    const expiry = batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: '2-digit' }) : 'N/A';
                    let text = `${batch.batch_number} (Avl: ${batch.quantity}, Exp: ${expiry})`;

                    if (isEditMode && batch.existing_sale_item) {
                        text += ` (Sold: ${batch.existing_sale_item.quantity}, Free: ${batch.existing_sale_item.free_quantity})`;
                    }

                    const option = new Option(text, batch.batch_number);
                    $(option).data('batch-data', batch); // Store full batch data for later use
                    batchSelect.append(option);
                });

                batchSelect.prop('disabled', false);

                // Finally, set the selected value and trigger change to populate details
                if (initialBatchValue) {
                    batchSelect.val(initialBatchValue).trigger('change');
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
                    // If no initialBatchValue could be determined (e.g., no batches at all, or all batches have 0 quantity and it's a new sale)
                    batchSelect.append(new Option('No usable stock', '', true, true)).trigger('change');
                    batchSelect.prop('disabled', true);
                    resetItemDetails(wrapper); // Fully reset if no usable stock
                }
                calculateTotals(); // Recalculate totals after batch selection (or reset)
            })
            .catch(err => {
                console.error("Error fetching batches:", err);
                batchSelect.empty().append(new Option('Error loading batches', '', true, true)).trigger('change');
                batchSelect.prop('disabled', true);
                resetItemDetails(wrapper);
                calculateTotals();
            });
    }

    
    // --- Populates input fields of a sale item row with batch-specific data (after batch selection) ---
    function populateBatchDetails(wrapper, data) {
        // MODIFIED: Replaced optional chaining for assignment with explicit null checks
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
        
        if (salePriceInput) salePriceInput.value = parseFloat(data.sale_price || 0).toFixed(2);
        if (mrpInputDisplay) mrpInputDisplay.value = data.ptr || 'N/A';
  if (gstRateInputHidden) gstRateInputHidden.value = parseFloat(data.gst || 0).toFixed(2); // Use data.gst
        if (gstPercentDisplay) gstPercentDisplay.value = `${parseFloat(data.gst || 0).toFixed(2)}%`; // Use data.gst
        if (expiryDateInput) expiryDateInput.value = data.expiry_date ? new Date(data.expiry_date).toISOString().split('T')[0] : '';
        if (ptrInputHidden) ptrInputHidden.value = data.ptr || '';

        if (availableQuantityDisplay) availableQuantityDisplay.textContent = `Available: ${data.quantity}`;
        wrapper.dataset.availableQuantity = data.quantity;

        if (data.existing_sale_item) {
            if (quantityInput) quantityInput.value = data.existing_sale_item.quantity ?? 0;
            if (freeQuantityInput) freeQuantityInput.value = data.existing_sale_item.free_quantity ?? 0;
            if (salePriceInput) salePriceInput.value = parseFloat(data.existing_sale_item.sale_price || 0).toFixed(2);
            if (discountInput) discountInput.value = parseFloat(data.existing_sale_item.discount_percentage || 0);

            if (extraDiscountCheckbox) {
                extraDiscountCheckbox.checked = !!data.existing_sale_item.is_extra_discount_applied;
            }
            if (appliedExtraDiscountInput) {
                appliedExtraDiscountInput.value = parseFloat(data.existing_sale_item.applied_extra_discount_percentage || 0).toFixed(2);
            }

        } else {
            if (quantityInput) {
                if (parseInt(quantityInput.value, 10) === 0 && data.quantity > 0) {
                    quantityInput.value = 1;
                } else if (data.quantity === 0) {
                    quantityInput.value = 0;
                }
            }
            if (freeQuantityInput) freeQuantityInput.value = 0;
            if (discountInput) discountInput.value = 0;

            if (extraDiscountCheckbox) {
                extraDiscountCheckbox.checked = false;
            }
            if (appliedExtraDiscountInput) {
                appliedExtraDiscountInput.value = parseFloat(0).toFixed(2);
            }
        }

        if (quantityInput) quantityInput.disabled = false;
        if (salePriceInput) salePriceInput.disabled = false;
        if (discountInput) discountInput.disabled = false;
        if (quantityInput) quantityInput.setAttribute('max', data.quantity);

        validateQuantity(quantityInput);
        calculateTotals();
    }

    // --- Populates an entire row with existing data (used when loading an edit form or old input) ---
    function populateRow(wrapper, data) {
        const medicineNameSelect = $(wrapper).find('.medicine-name-select');
        const packSelect = $(wrapper).find('.pack-select');
        const batchSelect = $(wrapper).find('.batch-number-select');
        
        // MODIFIED: Replaced optional chaining for assignment with explicit null checks
         const idInput = wrapper.querySelector('input[name*="[id]"]');
        if (idInput) idInput.value = data.id;
              const medicineIdInput = wrapper.querySelector('.medicine-id-input'); // Get the hidden input element
        if (medicineIdInput) { // Check if it exists
            medicineIdInput.value = data.medicine_id ?? ''; // Set its value from data
        }
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
        const extraDiscountCheckbox = wrapper.querySelector('.extra-discount-checkbox');
        const appliedExtraDiscountInput = wrapper.querySelector('.applied-extra-discount-percentage');

        if (quantityInput) quantityInput.value = data.quantity ?? 0;
        if (freeQuantityInput) freeQuantityInput.value = data.free_quantity ?? 0;
        if (salePriceInput) salePriceInput.value = parseFloat(data.sale_price || 0).toFixed(2);
        if (discountInput) discountInput.value = data.discount_percentage || 0;

        if (gstRateInputHidden) gstRateInputHidden.value = data.gst_rate || 0;
        if (expiryDateInput) expiryDateInput.value = data.expiry_date ? new Date(data.expiry_date).toISOString().split('T')[0] : '';
        if (ptrInputHidden) ptrInputHidden.value = data.ptr || '';
        if (packInputHidden) packInputHidden.value = data.pack || '';

        if (mrpInputDisplay) mrpInputDisplay.value = data.ptr || 'N/A';
        if (gstPercentDisplay) gstPercentDisplay.value = `${data.gst_rate || 0}%`;

        if (extraDiscountCheckbox) {
            extraDiscountCheckbox.checked = String(data.is_extra_discount_applied).toLowerCase() === 'true';
            if (appliedExtraDiscountInput) appliedExtraDiscountInput.value = parseFloat(data.applied_extra_discount_percentage || 0).toFixed(2);
        }

        if (quantityInput) quantityInput.disabled = false;
        if (salePriceInput) salePriceInput.disabled = false;
        if (discountInput) discountInput.disabled = false;

        wrapper.dataset.availableQuantity = data.available_quantity || data.quantity || 0;
        if (quantityInput) quantityInput.setAttribute('max', wrapper.dataset.availableQuantity);
        if (availableQuantityDisplay) availableQuantityDisplay.textContent = `Available: ${wrapper.dataset.availableQuantity}`;

        if (data.medicine_id && data.medicine_name) {
            const medicineOption = new Option(data.medicine_name, data.medicine_id, true, true);
            $(medicineOption).data('pack', data.pack);
            medicineNameSelect.append(medicineOption).trigger('change');

            if (data.pack) {
                packSelect.prop('disabled', false)
                    .append(new Option(data.pack, data.medicine_id, true, true))
                    .val(data.medicine_id)
                    .trigger('change.select2');
            }

            fetchBatches(data.medicine_id, wrapper, data.batch_number);
        }
        validateQuantity(quantityInput);
        calculateTotals();
    }

    // --- Validates the entered quantity against available stock, and corrects it if over ---
    function validateQuantity(quantityInput) {
        const wrapper = quantityInput.closest('.sale-item-wrapper');
        const available = parseFloat(wrapper.dataset.availableQuantity, 10);
        let requested = parseFloat(quantityInput.value, 10);
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
            quantityInput.value = available; // Set to available if over
        } else {
            quantityInput.classList.remove('is-invalid');
            quantityInput.value = requested; // Set to requested if valid
        }
    }

    // --- Calculates and updates the subtotal, total GST, and grand total ---
    function calculateTotals() {
        let subtotal = 0;
        let totalGst = 0;

        $('.sale-item-wrapper').each(function () {
            const $row = $(this);

            const qty = parseFloat($row.find('.quantity-input').val()) || 0;
            const price = parseFloat($row.find('.sale-price-input').val()) || 0;
            const discount = parseFloat($row.find('.discount-percentage-input').val()) || 0;
            const gstRate = parseFloat($row.find('.gst-rate-input').val()) || 0;
            const appliedExtraDiscount = parseFloat($row.find('.applied-extra-discount-percentage').val()) || 0;

            let lineTotal = qty * price;
            lineTotal *= (1 - discount / 100);
            lineTotal *= (1 - appliedExtraDiscount / 100);

            const gstAmount = lineTotal * (gstRate / 100); // Corrected GST calculation

            subtotal += lineTotal;
            totalGst += gstAmount;

            $row.find('.gst-amount-input').val(gstAmount.toFixed(2));
        });

        const grandTotal = subtotal + totalGst;

        $('#subtotal').text(subtotal.toFixed(2));
        $('#total_gst').text(totalGst.toFixed(2));
        $('#grand_total').text(grandTotal.toFixed(2));
    }

    // --- Initialization (Runs when the page first loads) ---
    addItemBtn.addEventListener('click', () => addItem());

    if (window.oldInput && (window.oldInput.new_items || window.oldInput.existing_items)) {
        const existingItems = Object.entries(window.oldInput.existing_items || {});
        const newItems = Object.entries(window.oldInput.new_items || {});

        // Handle old input for existing items, ensuring boolean conversion for is_extra_discount_applied
        existingItems.forEach(([id, data]) => addItem({
            ...data,
            id,
            // Correctly parse the string 'true'/'false' from old input to boolean
            is_extra_discount_applied: String(data.is_extra_discount_applied).toLowerCase() === 'true',
            applied_extra_discount_percentage: data.applied_extra_discount_percentage
        }));
        newItems.forEach(([, data]) => addItem(data));
    } else if (document.querySelectorAll('.sale-item-wrapper').length === 0) {
        addItem();
    } else {
        // This block is for existing sale items loaded directly from the database (first page load of edit form)
        document.querySelectorAll('.sale-item-wrapper').forEach(wrapper => {
            initializeRow(wrapper); // MODIFIED: Call initializeRow first to set up Select2 and listeners

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
                pack: wrapper.dataset.pack,
                // CRITICAL FIX: Read from data-attributes and convert string to boolean
                is_extra_discount_applied: String(wrapper.dataset.isExtraDiscountApplied).toLowerCase() === 'true',
                applied_extra_discount_percentage: wrapper.dataset.appliedExtraDiscountPercentage,
            };
            populateRow(wrapper, data); // Call populateRow with the converted data
        });
        calculateTotals();
    }

    // --- Form Submission Validation ---
    saleForm.addEventListener('submit', function (event) {
        let isValid = true;

        document.querySelectorAll('.sale-item-wrapper').forEach((wrapper) => {
            const medicineNameSelect = $(wrapper).find('.medicine-name-select');
            const packSelect = $(wrapper).find('.pack-select');
            const batchSelect = $(wrapper).find('.batch-number-select');
            const quantityInput = wrapper.querySelector('.quantity-input');

            validateQuantity(quantityInput); // Ensure latest validation state

            let errors = [];

            if (!medicineNameSelect.find(':selected').val()) {
                errors.push("Medicine name not selected");
            }
            // Only validate pack if the select is enabled and has options
            if (!packSelect.prop('disabled') && packSelect.find('option').length > 1 && !packSelect.val()) {
                errors.push("Pack not selected");
            }


            if (!batchSelect.find(':selected').val()) {
                errors.push("Batch not selected");
            }
            if (quantityInput && quantityInput.classList.contains('is-invalid')) { // MODIFIED: Added null check
                errors.push("Quantity marked invalid (red border)");
            }
            const qtyValue = parseFloat(quantityInput ? quantityInput.value : 0); // MODIFIED: Added null check
            if (isNaN(qtyValue) || qtyValue < 0) {
                errors.push(`Quantity is invalid: ${quantityInput ? quantityInput.value : 'N/A'}`); // MODIFIED: Added null check
            }


            if (errors.length > 0) {
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

    // --- Debug Validation Function (for development/testing, not used in production flow) ---
    function debugValidation() {
        let globalErrors = [];
        document.querySelectorAll('.sale-item-wrapper').forEach((wrapper, index) => {
            const medicineNameSelect = $(wrapper).find('.medicine-name-select');
            const packSelect = $(wrapper).find('.pack-select');
            const batchSelect = $(wrapper).find('.batch-number-select');
            const quantityInput = wrapper.querySelector('.quantity-input');

            let itemErrors = [];
            let itemIsValid = true;

            // 1. Check Medicine Name Select2 value
            if (!medicineNameSelect.val()) {
                itemErrors.push('Medicine Name is not selected.');
                itemIsValid = false;
            }

            // 2. Check Pack Select2 value
            if (!packSelect.val()) {
                itemErrors.push('Pack is not selected.');
                itemIsValid = false;
            }

            // 3. Check Batch Select2 value
            if (!batchSelect.val()) {
                itemErrors.push('Batch is not selected.');
                itemIsValid = false;
            }

            // 4. Check Quantity validity (is-invalid class)
            if (quantityInput && quantityInput.classList.contains('is-invalid')) { // MODIFIED: Added null check
                itemErrors.push('Quantity is invalid (red border, likely due to stock limit).');
                itemIsValid = false;
            }

            // 5. Check Quantity value (< 1)
            const currentQty = parseFloat(quantityInput ? quantityInput.value : 0); // MODIFIED: Added null check
            if (isNaN(currentQty) || currentQty < 1) {
                itemErrors.push(`Quantity value is ${currentQty}, which is less than 1 or not a number.`);
                itemIsValid = false;
            }

            if (!itemIsValid) {
                globalErrors.push(`Item ${index + 1} has errors: ${itemErrors.join('; ')}`);
                wrapper.classList.add('border', 'border-danger', 'border-2');
            } else {
                wrapper.classList.remove('border', 'border-danger', 'border-2');
            }
        });

        if (globalErrors.length > 0) {
            return false;
        } else {
            return true;
        }
    }
});