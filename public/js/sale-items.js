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

        salePriceInput.value = parseFloat(0).toFixed(2);
        mrpInputDisplay.value = 'N/A';
        gstPercentDisplay.value = '0%';
        gstAmountDisplay.value = parseFloat(0).toFixed(2);
        discountInput.value = 0;

        gstRateInputHidden.value = 0;
        expiryDateInput.value = '';
        ptrInputHidden.value = '';
        packInputHidden.value = '';

        // Reset extra discount fields
        if (extraDiscountCheckbox) extraDiscountCheckbox.checked = false;
        if (appliedExtraDiscountInput) appliedExtraDiscountInput.value = parseFloat(0).toFixed(2);

        if (resetQuantity) {
            quantityInput.value = 0;
            freeQuantityInput.value = 0;
            quantityInput.disabled = true;
            quantityInput.setAttribute('max', '0');
            wrapper.dataset.availableQuantity = 0;
            availableQuantityDisplay.textContent = '';
            quantityInput.classList.remove('is-invalid');
            const existingWarning = wrapper.querySelector('.qty-warning');
            if (existingWarning) existingWarning.remove();
        } else {
            quantityInput.disabled = true;
        }

        salePriceInput.disabled = true;
        discountInput.disabled = true;
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
        removeBtn.addEventListener('click', () => {
            const deletedInput = document.getElementById('deleted_items');
            if (wrapper.dataset.itemId) {
                deletedInput.value += (deletedInput.value ? ',' : '') + wrapper.dataset.itemId;
            }
            wrapper.remove();
            calculateTotals();
        });

        // Quantity & discount inputs
        // This listener will trigger calculateTotals for all item-calc inputs
        wrapper.querySelectorAll('.item-calc').forEach(el => el.addEventListener('input', calculateTotals));

        if (extraDiscountCheckbox) {
            extraDiscountCheckbox.addEventListener('change', () => {
                appliedExtraDiscountInput.value = extraDiscountCheckbox.checked ? EXTRA_DISCOUNT_PERCENTAGE.toFixed(2) : parseFloat(0).toFixed(2);
                calculateTotals();
            });
        }

        // Medicine selection (User picks a medicine name)
        medicineNameSelect.on('select2:select', e => {
            const medicineId = e.params.data.id;
            const medicinePack = e.params.data.pack;

            wrapper.querySelector('.medicine-id-input').value = medicineId;

            packSelect.empty().prop('disabled', false);
            if (medicinePack) {
                packSelect.append(new Option(medicinePack, medicineId, true, true)).trigger('change');
                packInputHidden.value = medicinePack;
            } else {
                packSelect.append(new Option('N/A Pack', medicineId, true, true)).trigger('change');
                packInputHidden.value = '';
            }

            // Call fetchBatches without specific selectedBatch (it will auto-select first available or none)
            fetchBatches(medicineId, wrapper, null);
            batchSelect.empty().trigger('change').prop('disabled', true);
            resetItemDetails(wrapper, false); // Don't reset quantity here; fetchBatches will handle initial quantity from API
            calculateTotals();
        });

        medicineNameSelect.on('select2:clear', () => {
            packSelect.empty().trigger('change').prop('disabled', true);
            batchSelect.empty().trigger('change').prop('disabled', true);
            packInputHidden.value = '';
            resetItemDetails(wrapper);
            calculateTotals();
        });

        // Pack selection (If your flow allows picking different packs for the same medicine, call fetchBatches)
        packSelect.on('select2:select', e => {
            packInputHidden.value = e.params.data.text;
            fetchBatches(e.params.data.id, wrapper, null);
        });

        packSelect.on('select2:clear', () => {
            batchSelect.empty().trigger('change').prop('disabled', true);
            packInputHidden.value = '';
            resetItemDetails(wrapper);
            calculateTotals();
        });

        // Batch selection (User picks a batch number)
        batchSelect.on('select2:select', e => {
            const data = $(e.params.data.element).data('batch-data'); // Get the full batch data attached to the option
            if (data) {
                populateBatchDetails(wrapper, data);
                // Enable relevant inputs after a batch is selected
                quantityInput.disabled = false;
                salePriceInput.disabled = false;
                discountInput.disabled = false;
                quantityInput.setAttribute('max', data.quantity); // Set max based on available inventory
                availableQuantityDisplay.textContent = `Available: ${data.quantity}`;
            }
        });

        // Quantity & Free Quantity listeners
        // Only validate quantity here, calculation is handled by the 'item-calc' listener
        quantityInput.addEventListener('input', () => validateQuantity(quantityInput));
        freeQuantityInput.addEventListener('input', calculateTotals); // Free quantity also affects totals
    }

    // --- Validation for pack selection (remains the same) ---
    function validateRow(wrapper) {
        const errors = [];
        const packSelect = $(wrapper).find('.pack-select');
        const medicineId = wrapper.querySelector('.medicine-id-input').value;

        if (medicineId && !packSelect.prop('disabled') && packSelect.find('option').length > 0 && !packSelect.val()) {
            errors.push("Pack not selected");
        }
        return errors;
    }

    // --- Add new item to the form dynamically ---
    function addItem(initialData = {}) {
        const clone = template.content.cloneNode(true);
        const newElement = clone.querySelector('.sale-item-wrapper');

        const itemIndex = itemCount; // Use a simple counter for new items' names/ids

        // Determine the correct name prefix for form submission (existing_sale_items[id] or new_sale_items[index])
        const nameAttributeReplacementValue = initialData.id
            ? `existing_sale_items[${initialData.id}]`
            : itemIndex;

        // Update name and id attributes to be unique
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

    // --- fetchBatches function (Unified for Create and Edit) ---
    // Pass selectedBatch to pre-select if loading an existing item
    function fetchBatches(medicineId, wrapper, selectedBatch = null) {
        const batchSelect = $(wrapper).find('.batch-number-select');

        batchSelect.empty().trigger('change').prop('disabled', true);
        // Pass 'false' to preserve quantity inputs here; populateBatchDetails will handle true value
        resetItemDetails(wrapper, false);

        batchSelect.append(new Option('Loading batches...', '', false, false)).trigger('change');

        let url = batchApiUrlBase.replace('PLACEHOLDER', medicineId);

        // <<< THIS IS THE CRITICAL PART FOR JS: Append sale_id as a query parameter ONLY if in edit mode and saleId exists
        if (isEditMode && saleId) { // Check both `isEditMode` boolean AND if `saleId` is a non-empty string
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
                    resetItemDetails(wrapper); // Reset fully if no batches
                    calculateTotals();
                    return;
                }

                let initialBatchValue = null;
                let initialBatchData = null;

                batches.forEach((batch, index) => {
                    // Corrected expiry_date field name, assuming it's `expiry_date` in your Inventory model/API response
                    const expiry = batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: '2-digit' }) : 'N/A';
                    let text = `${batch.batch_number} (Avl: ${batch.quantity}, Exp: ${expiry})`;

                    // Display sold quantity for existing items in edit mode for clarity in dropdown
                    if (isEditMode && batch.existing_sale_item) {
                        text += ` (Sold: ${batch.existing_sale_item.quantity}, Free: ${batch.existing_sale_item.free_quantity})`;
                    }

                    const option = new Option(text, batch.batch_number);
                    $(option).data('batch-data', batch); // Store full batch data for later use
                    batchSelect.append(option);

                    // Logic to pre-select a batch if `selectedBatch` is provided (from populateRow for existing items)
                    // or auto-select the first available batch if in create mode.
                    if (selectedBatch && batch.batch_number === selectedBatch) {
                        initialBatchValue = batch.batch_number;
                        initialBatchData = batch;
                    } else if (!selectedBatch && index === 0 && !isEditMode) { // Auto-select first batch only if not pre-selecting and not in edit mode
                        initialBatchValue = batch.batch_number;
                        initialBatchData = batch;
                    }
                });

                batchSelect.prop('disabled', false);
                if (initialBatchValue) {
                    batchSelect.val(initialBatchValue).trigger('change');
                    batchSelect.trigger({
                        type: 'select2:select',
                        params: {
                            data: {
                                id: initialBatchValue,
                                // Pass the actual DOM element for Select2 to work correctly
                                element: batchSelect.find(`option[value="${initialBatchValue}"]`)[0]
                            }
                        }
                    });
                    // Manually call populateBatchDetails with the initialBatchData
                    if (initialBatchData) {
                        populateBatchDetails(wrapper, initialBatchData);
                    }
                } else {
                    batchSelect.trigger('change');
                }
            })
            .catch(err => {
                batchSelect.empty().append(new Option('Error loading batches', '', true, true)).trigger('change');
                batchSelect.prop('disabled', true);
                resetItemDetails(wrapper);
                calculateTotals();
            });
    }

    // --- Populates input fields of a sale item row with batch-specific data (after batch selection) ---
    function populateBatchDetails(wrapper, data) {
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
        const extraDiscountCheckbox = wrapper.querySelector('.extra-discount-checkbox');
        const appliedExtraDiscountInput = wrapper.querySelector('.applied-extra-discount-percentage');

        // These fields always come from the 'batch' data (i.e., from Inventory/PurchaseBillItem)
        salePriceInput.value = parseFloat(data.sale_price || 0).toFixed(2);
        mrpInputDisplay.value = data.ptr || 'N/A';
        gstPercentDisplay.value = `${data.gst_rate || 0}%`;

        gstRateInputHidden.value = data.gst_rate || 0;
        expiryDateInput.value = data.expiry_date ? new Date(data.expiry_date).toISOString().split('T')[0] : '';
        ptrInputHidden.value = data.ptr || '';

        // Set available quantity from the current inventory.quantity for this batch.
        wrapper.dataset.availableQuantity = data.quantity; // This is the inventory quantity
        availableQuantityDisplay.textContent = `Available: ${data.quantity}`;

        // <<< IMPORTANT LOGIC: Prioritize values from existing_sale_item if it exists (for editing)
        if (data.existing_sale_item) {
            // Use quantities, prices, and discounts from the *existing sale item*
            quantityInput.value = data.existing_sale_item.quantity ?? 0;
            freeQuantityInput.value = data.existing_sale_item.free_quantity ?? 0;
            salePriceInput.value = parseFloat(data.existing_sale_item.sale_price || 0).toFixed(2);
            discountInput.value = parseFloat(data.existing_sale_item.discount_percentage || 0); // Use parseFloat

            // Handle the extra discount fields (CRITICAL for 3% discount on edit)
            if (extraDiscountCheckbox) {
                // !! converts any truthy/falsy value (like 0, 1, null, undefined) to true/false boolean
                extraDiscountCheckbox.checked = !!data.existing_sale_item.is_extra_discount_applied;
            }
            if (appliedExtraDiscountInput) {
                appliedExtraDiscountInput.value = parseFloat(data.existing_sale_item.applied_extra_discount_percentage || 0).toFixed(2);
            }

        } else {
            // If no existing_sale_item, this is a new item being added or a fresh batch selection in edit mode.
            // Reset to default new item values.
            if (parseInt(quantityInput.value, 10) === 0 && data.quantity > 0) {
                quantityInput.value = 1;
            } else if (data.quantity === 0) {
                quantityInput.value = 0; // If inventory quantity is 0, set requested quantity to 0
            }
            freeQuantityInput.value = 0; // Reset free quantity for new selection
            discountInput.value = 0; // Reset discount for new selection

            // Ensure extra discount fields are reset for new items
            if (extraDiscountCheckbox) {
                extraDiscountCheckbox.checked = false;
            }
            if (appliedExtraDiscountInput) {
                appliedExtraDiscountInput.value = parseFloat(0).toFixed(2);
            }
        }

        // Enable inputs and set max attribute based on current inventory quantity
        quantityInput.disabled = false;
        salePriceInput.disabled = false;
        discountInput.disabled = false;
        quantityInput.setAttribute('max', data.quantity); // Max is always the current inventory quantity

        // Validate and recalculate totals
        validateQuantity(quantityInput);
        calculateTotals();
    }

    // --- Populates an entire row with existing data (used when loading an edit form or old input) ---
    function populateRow(wrapper, data) {
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
        const extraDiscountCheckbox = wrapper.querySelector('.extra-discount-checkbox');
        const appliedExtraDiscountInput = wrapper.querySelector('.applied-extra-discount-percentage');


        if (data.id) {
            const idInput = wrapper.querySelector('input[name*="[id]"]');
            if (idInput) idInput.value = data.id;
        }

        // Populate common fields directly from 'data' (which is the SaleItem data from DB attributes)
        quantityInput.value = data.quantity ?? 0;
        freeQuantityInput.value = data.free_quantity ?? 0;
        salePriceInput.value = parseFloat(data.sale_price || 0).toFixed(2);
        discountInput.value = data.discount_percentage || 0;

        gstRateInputHidden.value = data.gst_rate || 0;
        expiryDateInput.value = data.expiry_date ? new Date(data.expiry_date).toISOString().split('T')[0] : '';
        ptrInputHidden.value = data.ptr || '';
        packInputHidden.value = data.pack || '';

        mrpInputDisplay.value = data.ptr || 'N/A';
        gstPercentDisplay.value = `${data.gst_rate || 0}%`;

        // *** CRITICAL FIX FOR EXTRA DISCOUNT CHECKBOX: Convert string 'true'/'false' from data attributes to boolean ***
        if (extraDiscountCheckbox) {
            extraDiscountCheckbox.checked = String(data.is_extra_discount_applied).toLowerCase() === 'true';
            appliedExtraDiscountInput.value = parseFloat(data.applied_extra_discount_percentage || 0).toFixed(2);
        }

        quantityInput.disabled = false;
        salePriceInput.disabled = false;
        discountInput.disabled = false;

        // availableQuantityDisplay text is temporary here, will be updated correctly by fetchBatches
        // data.quantity here is the quantity sold in THIS sale, not the current available stock.
        wrapper.dataset.availableQuantity = data.available_quantity || data.quantity || 0;
        quantityInput.setAttribute('max', wrapper.dataset.availableQuantity);
        availableQuantityDisplay.textContent = `Available: ${wrapper.dataset.availableQuantity}`;


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

            // CRITICAL: Pass data.batch_number to fetchBatches to pre-select the correct batch.
            fetchBatches(data.medicine_id, wrapper, data.batch_number);
        }
        validateQuantity(quantityInput);
        calculateTotals();
    }

    // --- Validates the entered quantity against available stock, and corrects it if over ---
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
            quantityInput.value = available; // Set to available if over
        } else {
            quantityInput.classList.remove('is-invalid');
            quantityInput.value = requested; // Set to requested if valid
        }
        // Removed calculateTotals() from here. It will be triggered by the 'item-calc' listener or explicitly elsewhere.
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
        // If no existing items from DB or old input, add one empty row for new sale.
        addItem();
    } else {
        // This block is for existing sale items loaded directly from the database (first page load of edit form)
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
                pack: wrapper.dataset.pack,
                // CRITICAL FIX: Read from data-attributes and convert string to boolean
                is_extra_discount_applied: String(wrapper.dataset.isExtraDiscountApplied).toLowerCase() === 'true',
                applied_extra_discount_percentage: wrapper.dataset.appliedExtraDiscountPercentage,
            };
            initializeRow(wrapper);
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
            if (quantityInput.classList.contains('is-invalid')) {
                errors.push("Quantity marked invalid (red border)");
            }
            const qtyValue = parseFloat(quantityInput.value);
            if (isNaN(qtyValue) || qtyValue < 0) {
                errors.push(`Quantity is invalid: ${quantityInput.value}`);
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
            if (quantityInput.classList.contains('is-invalid')) {
                itemErrors.push('Quantity is invalid (red border, likely due to stock limit).');
                itemIsValid = false;
            }

            // 5. Check Quantity value (< 1)
            const currentQty = parseFloat(quantityInput.value);
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
