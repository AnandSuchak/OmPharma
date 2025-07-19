document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('sale_items_container');
    if (!container) return;

    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('sale_item_template');
    const medicineSearchUrl = container.dataset.searchUrl;
    const batchBaseUrl = container.dataset.batchBaseUrl;

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

// --- Initialize Row ---
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
            url: container.dataset.searchUrl,
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
    wrapper.querySelectorAll('.item-calc').forEach(el => el.addEventListener('input', calculateTotals));

    if (extraDiscountCheckbox) {
        extraDiscountCheckbox.addEventListener('change', () => {
            appliedExtraDiscountInput.value = extraDiscountCheckbox.checked ? EXTRA_DISCOUNT_PERCENTAGE.toFixed(2) : parseFloat(0).toFixed(2);
            calculateTotals();
        });
    }

    // Medicine selection
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

        fetchBatches(medicineId, wrapper);
        batchSelect.empty().trigger('change').prop('disabled', true);
        resetItemDetails(wrapper, false);
        calculateTotals();
    });

    medicineNameSelect.on('select2:clear', () => {
        packSelect.empty().trigger('change').prop('disabled', true);
        batchSelect.empty().trigger('change').prop('disabled', true);
        packInputHidden.value = '';
        resetItemDetails(wrapper);
        calculateTotals();
    });

    // Pack selection
    packSelect.on('select2:select', e => {
        packInputHidden.value = e.params.data.text;
        fetchBatches(e.params.data.id, wrapper);
    });

    packSelect.on('select2:clear', () => {
        batchSelect.empty().trigger('change').prop('disabled', true);
        packInputHidden.value = '';
        resetItemDetails(wrapper);
        calculateTotals();
    });

    // Batch selection
    batchSelect.on('select2:select', e => {
        const data = $(e.params.data.element).data('batch-data');
        if (data) {
            populateBatchDetails(wrapper, data);
            quantityInput.disabled = false;
            salePriceInput.disabled = false;
            discountInput.disabled = false;
            quantityInput.setAttribute('max', data.quantity);
            availableQuantityDisplay.textContent = `Available: ${data.quantity}`;
        }
    });

    // Quantity & Free Quantity listeners
    quantityInput.addEventListener('input', () => validateQuantity(quantityInput));
    freeQuantityInput.addEventListener('input', calculateTotals);
}

// --- Validation for pack selection ---
function validateRow(wrapper) {
    const errors = [];
    const packSelect = $(wrapper).find('.pack-select');
    const medicineId = wrapper.querySelector('.medicine-id-input').value;

    if (medicineId && !packSelect.prop('disabled') && packSelect.find('option').length > 0 && !packSelect.val()) {
        errors.push("Pack not selected");
    }
    return errors;
}

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
        resetItemDetails(wrapper, false); // Pass 'false' to preserve quantity inputs here

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
                    resetItemDetails(wrapper); // Reset fully if no batches
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
                resetItemDetails(wrapper); // Reset fully on error
                calculateTotals();
            });
    }
    // Populates the input fields of a sale item row with batch-specific data.
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

        // FIX: Correctly populate quantity, allowing 0 to be a valid value
        quantityInput.value = data.quantity ?? 1; // Use nullish coalescing operator (??)
        freeQuantityInput.value = data.free_quantity ?? 0; // Corrected to ?? 0

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

        if (extraDiscountCheckbox) {
            extraDiscountCheckbox.checked = data.is_extra_discount_applied;
        }
        if (appliedExtraDiscountInput) {
            appliedExtraDiscountInput.value = parseFloat(data.applied_extra_discount_percentage || 0).toFixed(2);
        }

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
            const appliedExtraDiscount = parseFloat($row.find('.applied-extra-discount-percentage').val()) || 0;


            console.log(`Item ${index + 1}:`);
            console.log(`  Quantity (qty): ${qty}`);
            console.log(`  Price (price): ${price}`);
            console.log(`  Customer Discount (discount): ${discount}%`);
            console.log(`  GST Rate (gstRate): ${gstRate}%`);
            console.log(`  Applied Extra Discount: ${appliedExtraDiscount}%`);


            const lineTotal = qty * price;
            console.log(`  Line Total (qty * price): ${lineTotal}`);

            let currentAmount = lineTotal;
            currentAmount = currentAmount * (1 - discount / 100);
            currentAmount = currentAmount * (1 - appliedExtraDiscount / 100);

            const finalDiscountedAmount = currentAmount;
            console.log(`  Final Discounted Amount (after all discounts): ${finalDiscountedAmount}`);


            const gstAmount = finalDiscountedAmount * (gstRate / 100);
            console.log(`  GST Amount for item: ${gstAmount}`);

            subtotal += finalDiscountedAmount;
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

        existingItems.forEach(([id, data]) => addItem({ ...data, id, is_extra_discount_applied: data.is_extra_discount_applied, applied_extra_discount_percentage: data.applied_extra_discount_percentage }));
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
                pack: wrapper.dataset.pack,
                is_extra_discount_applied: wrapper.dataset.isExtraDiscountApplied === 'true',
                applied_extra_discount_percentage: wrapper.dataset.appliedExtraDiscountPercentage,
            };
            initializeRow(wrapper);
            populateRow(wrapper, data);
        });
        calculateTotals();
    }

   saleForm.addEventListener('submit', function(event) {
    let isValid = true;

    document.querySelectorAll('.sale-item-wrapper').forEach((wrapper, index) => {
        const medicineNameSelect = $(wrapper).find('.medicine-name-select');
        const packSelect = $(wrapper).find('.pack-select');
        const batchSelect = $(wrapper).find('.batch-number-select');
        const quantityInput = wrapper.querySelector('.quantity-input');

        validateQuantity(quantityInput);

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
            console.error(`Item ${index + 1} errors:`, errors);
            isValid = false;
            wrapper.classList.add('border', 'border-danger', 'border-2');
        } else {
            wrapper.classList.remove('border', 'border-danger', 'border-2');
        }
    });

    if (!isValid) {
        event.preventDefault();
        alert('Please complete all item details and correct quantities before submitting. Check console for details.');
    }
});


// Add this new function somewhere in sale-items.js, e.g., below calculateTotals
function debugValidation() {
    let globalErrors = [];
    document.querySelectorAll('.sale-item-wrapper').forEach((wrapper, index) => {
        const medicineNameSelect = $(wrapper).find('.medicine-name-select');
        const packSelect = $(wrapper).find('.pack-select');
        const batchSelect = $(wrapper).find('.batch-number-select');
        const quantityInput = wrapper.querySelector('.quantity-input');
        
        let itemErrors = [];
        let itemIsValid = true;

        console.log(`--- Debugging Item ${index + 1} (ID: ${wrapper.dataset.itemId || 'NEW'}) ---`); // Log item ID or 'NEW'
        
        // 1. Check Medicine Name Select2 value
        if (!medicineNameSelect.val()) {
            itemErrors.push('Medicine Name is not selected.');
            itemIsValid = false;
        } else {
            console.log(`Medicine Name Selected: ${medicineNameSelect.val()}`);
        }

        // 2. Check Pack Select2 value
        if (!packSelect.val()) {
            itemErrors.push('Pack is not selected.');
            itemIsValid = false;
        } else {
            console.log(`Pack Selected: ${packSelect.val()}`);
        }

        // 3. Check Batch Select2 value
        if (!batchSelect.val()) {
            itemErrors.push('Batch is not selected.');
            itemIsValid = false;
        } else {
            console.log(`Batch Selected: ${batchSelect.val()}`);
        }

        // 4. Check Quantity validity (is-invalid class)
        if (quantityInput.classList.contains('is-invalid')) {
            itemErrors.push('Quantity is invalid (red border, likely due to stock limit).');
            itemIsValid = false;
        } else {
            console.log(`Quantity has is-invalid class: false`);
        }

        // 5. Check Quantity value (< 1)
        const currentQty = parseFloat(quantityInput.value);
        if (isNaN(currentQty) || currentQty < 1) { // This will fail if qty is 0 and min="1"
            itemErrors.push(`Quantity value is ${currentQty}, which is less than 1 or not a number.`);
            itemIsValid = false;
        } else {
            console.log(`Quantity value: ${currentQty}`);
        }

        if (!itemIsValid) {
            globalErrors.push(`Item ${index + 1} has errors: ${itemErrors.join('; ')}`);
            wrapper.classList.add('border', 'border-danger', 'border-2'); // Add red border for visibility
        } else {
            wrapper.classList.remove('border', 'border-danger', 'border-2');
        }
    });

    console.log('--- Overall Validation Summary ---');
    if (globalErrors.length > 0) {
        console.error('Validation FAILED for the following reasons:');
        globalErrors.forEach(err => console.error(err));
        return false;
    } else {
        console.log('Validation PASSED for all items.');
        return true;
    }
    }
})