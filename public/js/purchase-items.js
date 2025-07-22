document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('purchase_items_container');
    if (!container) return;

    // --- Configuration ---
    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('purchase_item_template')?.content;
    const medicineSearchUrl = container.dataset.searchUrl;
    let itemCount = document.querySelectorAll('.purchase-item').length; // Initial count of existing items
    let isManualMode = false;

    const subtotalInput = document.getElementById('subtotal_amount');
    const gstInput = document.getElementById('total_gst_amount');
    const totalInput = document.getElementById('total_amount');
    const extraDiscountInput = document.getElementById('extra_discount_amount');
    
    // Reference to the item count display span
    const purchaseItemCountDisplay = document.getElementById('purchase_item_count_display');
     // MODIFIED: References for new rounding fields
const originalGrandTotalInput = document.getElementById('original_grand_total_amount');
const roundingOffInput = document.getElementById('rounding_off_amount');    
// This line ensures it's displayed
    // --- Core Functions ---

    function updateItemCountDisplay() {
        if (purchaseItemCountDisplay) {
            purchaseItemCountDisplay.textContent = document.querySelectorAll('.purchase-item').length;
        }
    }

    function calculateTotals() {
        if (isManualMode) return;

        let subtotal = 0, totalGst = 0;

        document.querySelectorAll('.purchase-item').forEach(item => {
            const qty = parseFloat(item.querySelector('[name*="[quantity]"]')?.value) || 0;
            const price = parseFloat(item.querySelector('[name*="[purchase_price]"]')?.value) || 0;
            const ourDisc = parseFloat(item.querySelector('[name*="[our_discount_percentage]"]')?.value) || 0;
            const gstRate = parseFloat(item.querySelector('[name*="[gst_rate]"]')?.value) || 0;

            const base = qty * price;
            const afterDisc = base * (1 - ourDisc / 100);
            const gst = afterDisc * (gstRate / 100);

            subtotal += afterDisc;
            totalGst += gst;

            const rowTotalField = item.querySelector('.row-total');
            if (rowTotalField) {
                rowTotalField.value = afterDisc.toFixed(2); // Ensure consistent decimal display
            }
        });

        // Apply extra discount before calculating total
        const extraDiscount = parseFloat(document.getElementById('extra_discount_amount')?.value) || 0;
        subtotal = Math.max(subtotal - extraDiscount, 0);
// MODIFIED: Rounding Off Logic for Frontend Display - MOVED HERE
        const calculatedGrandTotal = subtotal + totalGst; // Calculate total before rounding
        const roundedGrandTotal = Math.round(calculatedGrandTotal); // Round to nearest whole number
        const roundingOffAmount = roundedGrandTotal - calculatedGrandTotal; // Calculate the difference

        if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
        if (gstInput) gstInput.value = totalGst.toFixed(2);

        // MODIFIED: Update new rounding fields - MOVED HERE
        if (originalGrandTotalInput) originalGrandTotalInput.value = calculatedGrandTotal.toFixed(2); // Display original total
        if (roundingOffInput) roundingOffInput.value = roundingOffAmount.toFixed(2); // Display rounding off amount
        if (totalInput) totalInput.value = roundedGrandTotal.toFixed(2); // Display the final rounded total

    }

    function updateDiscountFields(currentRow, changedField) {
        const qty = parseFloat(currentRow.querySelector('[name*="[quantity]"]')?.value) || 0;
        const purchasePrice = parseFloat(currentRow.querySelector('[name*="[purchase_price]"]')?.value) || 0;
        const baseValue = qty * purchasePrice;

        const ourDiscPercentageInput = currentRow.querySelector('.our-discount-percentage-input');
        const ourDiscAmountInput = currentRow.querySelector('.our-discount-amount-input');

        if (!ourDiscPercentageInput || !ourDiscAmountInput) return; // Ensure both fields exist

        // Use a flag to prevent circular updates
        if (currentRow.dataset.updatingDiscount) return;
        currentRow.dataset.updatingDiscount = 'true'; // Set flag

        if (changedField === 'percentage') {
            const percentage = parseFloat(ourDiscPercentageInput.value) || 0;
            if (baseValue > 0) {
                const amount = (baseValue * percentage) / 100;
                ourDiscAmountInput.value = amount.toFixed(2);
            } else {
                ourDiscAmountInput.value = '0.00';
            }
        } else if (changedField === 'amount') {
            const amount = parseFloat(ourDiscAmountInput.value) || 0;
            if (baseValue > 0) {
                const percentage = (amount / baseValue) * 100;
                ourDiscPercentageInput.value = percentage.toFixed(2); // Store percentage with 2 decimals
            } else {
                ourDiscPercentageInput.value = '0.00';
            }
        }

        delete currentRow.dataset.updatingDiscount; // Clear flag after update
        calculateTotals(); // Recalculate totals after discount fields are updated
    }


    function convertExpiryToDate(mmYY) {
        if (!mmYY) return '';
        const [month, year] = mmYY.split('/');
        if (!month || !year) return '';
        return `20${year}-${month}-01`;
    }

    document.querySelector('form').addEventListener('submit', function () {
        document.querySelectorAll('.expiry-date').forEach(function (input) {
            const converted = convertExpiryToDate(input.value);
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = input.name;
            input.removeAttribute('name'); // Remove name from original input
            input.closest('div')?.appendChild(hiddenInput); // Append to parent with optional chaining
            hiddenInput.value = converted; // Set value after appending to ensure it's part of the DOM
        });
    });

    document.addEventListener('input', function (e) {
        const input = e.target;
        if (input.classList.contains('expiry-date')) {
            let value = input.value.replace(/[^\d]/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            input.value = value;
        }
        if (input === extraDiscountInput) {
            calculateTotals();
        }
        // Add listeners for new discount fields
        if (input.classList.contains('our-discount-percentage-input')) {
            updateDiscountFields(input.closest('.purchase-item'), 'percentage');
        } else if (input.classList.contains('our-discount-amount-input')) {
            updateDiscountFields(input.closest('.purchase-item'), 'amount');
        }
    });

    function attachListeners(wrapper) {
        const removeBtn = wrapper.querySelector('.remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                const deletedInput = document.getElementById('deleted_items');
                if (deletedInput) {
                    // For edit mode, mark item for deletion if it has an ID
                    const itemId = wrapper.dataset.itemId; // Assuming data-item-id exists on wrapper
                    if (itemId) {
                        deletedInput.value += (deletedInput.value ? ',' : '') + itemId;
                    }
                }
                wrapper.remove();
                calculateTotals();
                updateItemCountDisplay(); // Update count on remove
            });
        }

        const medicineSelect = $(wrapper).find('.medicine-name-select');
        const selectedId = medicineSelect.data('selected-id');
        const selectedText = medicineSelect.data('selected-text');
        const packSelect = wrapper.querySelector('.pack-select');

        medicineSelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Search Medicine Name',
            ajax: {
                url: medicineSearchUrl,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term }),
                processResults: data => ({ results: data }),
                cache: true
            }
        });

        if (selectedId && selectedText) {
            var displayOption = new Option(selectedText, selectedId, true, true);
            medicineSelect.append(displayOption).trigger('change');
            if (packSelect) {
                packSelect.innerHTML = `<option value="${selectedId}" selected></option>`;
            }
        }

        // This already handles inputs with class 'item-calc' including quantity and free_quantity
        wrapper.querySelectorAll('.item-calc').forEach(el => {
            el.addEventListener('input', calculateTotals);
        });

        // Add initial population for our discount fields when attaching listeners (for existing items)
        const ourDiscPercentageInput = wrapper.querySelector('.our-discount-percentage-input');
        if (ourDiscPercentageInput) {
            // Trigger initial calculation from percentage to amount
            updateDiscountFields(wrapper, 'percentage');
        }
    }

    function addItem(data = {}) {
        if (!template) {
            console.error('The purchase_item_template was not found!');
            return;
        }

        const clone = template.cloneNode(true);
        let content = new XMLSerializer().serializeToString(clone);
        content = content.replace(/__INDEX__/g, itemCount);

        const newWrapper = document.createElement('div');
        newWrapper.innerHTML = content;
        const newElement = newWrapper.firstElementChild;

        container.appendChild(newElement);
        attachListeners(newElement);

        if (Object.keys(data).length > 0) {
            const nameSelect = $(newElement).find('.medicine-name-select');
            // MODIFIED: Simplified oldInput handling for medicine_text
            if (data.medicine_id && (data.medicine_text || data.medicine_name)) {
                var option = new Option(data.medicine_text || data.medicine_name, data.medicine_id, true, true);
                nameSelect.append(option).trigger('change');
            }

            const packSelect = newElement.querySelector('.pack-select');
            if (packSelect) packSelect.innerHTML = `<option value="${data.medicine_id}" selected>${data.pack || 'Standard'}</option>`;

            if (newElement.querySelector('[name$="[batch_number]"]')) newElement.querySelector('[name$="[batch_number]"]').value = data.batch_number || '';
            if (newElement.querySelector('[name$="[expiry_date]"]')) newElement.querySelector('[name$="[expiry_date]"]').value = data.expiry_date || '';
            
            // Ensure quantity and free_quantity are correctly set for new items
            if (newElement.querySelector('[name$="[quantity]"]')) newElement.querySelector('[name$="[quantity]"]').value = parseFloat(data.quantity || 1).toFixed(2);
            if (newElement.querySelector('[name$="[free_quantity]"]')) newElement.querySelector('[name$="[free_quantity]"]').value = parseFloat(data.free_quantity || 0).toFixed(2);

            if (newElement.querySelector('[name$="[purchase_price]"]')) newElement.querySelector('[name$="[purchase_price]"]').value = parseFloat(data.purchase_price || 0).toFixed(2);
            if (newElement.querySelector('[name$="[ptr]"]')) newElement.querySelector('[name$="[ptr]"]').value = parseFloat(data.ptr || 0).toFixed(2);
            if (newElement.querySelector('[name$="[sale_price]"]')) newElement.querySelector('[name$="[sale_price]"]').value = parseFloat(data.sale_price || 0).toFixed(2);
            
            // Set both discount fields based on percentage
            const ourDiscPercentageInput = newElement.querySelector('.our-discount-percentage-input');
            const ourDiscAmountInput = newElement.querySelector('.our-discount-amount-input');
            if (ourDiscPercentageInput) ourDiscPercentageInput.value = parseFloat(data.our_discount_percentage || 0).toFixed(2);
            // Trigger the two-way update for the newly added item
            if (newElement) updateDiscountFields(newElement, 'percentage');

            if (newElement.querySelector('[name$="[gst_rate]"]')) newElement.querySelector('[name$="[gst_rate]"]').value = parseFloat(data.gst_rate || 0).toFixed(2);
        }

        itemCount++;
        if (Object.keys(data).length === 0) {
            $(newElement).find('.medicine-name-select').select2('open');
        }
        updateItemCountDisplay(); // Update count on add
    }

    $(document).on('select2:select', '.medicine-name-select', function (e) {
        const selectedData = e.params.data.id;
        const [name, company] = selectedData.split('|');
        const currentRow = this.closest('.purchase-item');
        if (!name || !currentRow) return;

        const packContainer = currentRow.querySelector('.pack-selector-container');
        const packSelect = currentRow.querySelector('.pack-select');

        fetch(`/api/medicines/packs?name=${encodeURIComponent(name)}&company_name=${encodeURIComponent(company)}`)
            .then(response => response.json())
            .then(packs => {
                if (packSelect) packSelect.innerHTML = '<option value="">Select Pack</option>';
                if (packs.length > 1) {
                    packs.forEach(packInfo => {
                        const option = new Option(packInfo.pack || 'Standard', packInfo.id);
                        if (packSelect) packSelect.appendChild(option);
                    });
                    if (packContainer) packContainer.style.display = 'block';
                } else if (packs.length === 1) {
                    const singlePack = packs[0];
                    const option = new Option(singlePack.pack || 'Standard', singlePack.id, true, true);
                    if (packSelect) packSelect.appendChild(option);
                    if (packSelect) $(packSelect).trigger('change');
                    if (packContainer) packContainer.style.display = 'none';
                } else {
                    if (packContainer) packContainer.style.display = 'none';
                    if (packSelect) packSelect.innerHTML = '';
                }
            })
            .catch(() => {
                console.error("Error fetching packs for medicine name:", name);
            });
    });

    $(document).on('change', '.pack-select', function () {
        const medicineId = this.value;
        const currentRow = this.closest('.purchase-item');
        if (!medicineId || !currentRow) return;

        const gstRateField = currentRow.querySelector('.gst-rate');

        fetch(`/api/medicines/${medicineId}/gst`)
            .then(res => res.json())
            .then(data => {
                if (gstRateField) gstRateField.value = data.gst_rate ?? 0;
                calculateTotals();
            })
            .catch(() => {
                if (gstRateField) gstRateField.value = 0;
                console.error("Error fetching GST rate for medicine ID:", medicineId);
            });
    });

    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => addItem());
    }

    document.getElementById('toggle_manual_edit')?.addEventListener('click', function () {
        isManualMode = !isManualMode;
        [subtotalInput, gstInput, totalInput].forEach(field => {
            if (field) field.readOnly = !isManualMode;
        });
        this.innerHTML = isManualMode
            ? '<i class="fa fa-lock"></i> Lock Totals'
            : '<i class="fa fa-pencil-alt"></i> Manual Edit';
        if (!isManualMode) calculateTotals();
    });

    // Loop through existing items on page load to attach listeners and update discount fields
    document.querySelectorAll('.purchase-item').forEach(item => {
        attachListeners(item);
        // Initial population of discount amount for existing items
        updateDiscountFields(item, 'percentage');
    });

    // MODIFIED: Reverted oldInput processing to simpler version (no specific existing vs new item loops)
// Corrected oldInput processing to handle all three oldInput variables
    const oldItemsToProcess = [];
    if (window.oldPurchaseItems && window.oldPurchaseItems.length > 0) {
        // For create page, when old('purchase_items') is present
        window.oldPurchaseItems.forEach(item => oldItemsToProcess.push(item));
    }
    if (window.oldNewPurchaseItems && window.oldNewPurchaseItems.length > 0) {
        // For edit page, when old('new_purchase_items') is present
        window.oldNewPurchaseItems.forEach(item => oldItemsToProcess.push(item));
    }
    if (window.oldExistingPurchaseItems && Object.keys(window.oldExistingPurchaseItems).length > 0) {
        // For edit page, when old('existing_items') is present
        Object.entries(window.oldExistingPurchaseItems).forEach(([id, itemData]) => {
            oldItemsToProcess.push({ ...itemData, id: id }); // Add ID for existing items
        });
    }

    if (oldItemsToProcess.length > 0) {
        oldItemsToProcess.forEach(itemData => {
            // Ensure data types are floats for calculations
            itemData.quantity = parseFloat(itemData.quantity || 0);
            itemData.free_quantity = parseFloat(itemData.free_quantity || 0);
            itemData.purchase_price = parseFloat(itemData.purchase_price || 0);
            itemData.ptr = parseFloat(itemData.ptr || 0);
            itemData.sale_price = parseFloat(itemData.sale_price || 0);
            itemData.discount_percentage = parseFloat(itemData.discount_percentage || 0);
            itemData.our_discount_percentage = parseFloat(itemData.our_discount_percentage || 0);
            itemData.gst_rate = parseFloat(itemData.gst_rate || 0);
            
            // Ensure medicine_text is passed for Select2 re-population
            addItem({ ...itemData, medicine_text: itemData.medicine_name || itemData.text }); // Use itemData.text as fallback for Select2 display
        });
    } else if (document.querySelectorAll('.purchase-item').length === 0) {
        // Only add an empty row if no items are loaded from DB and no old input
        addItem();
    }

    calculateTotals();
    $('#supplier_id').select2({ theme: 'bootstrap-5' });
});
