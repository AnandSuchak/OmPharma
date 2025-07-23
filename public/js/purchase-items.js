document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('purchase_items_container');
    if (!container) return;

    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('purchase_item_template')?.content;
    const medicineSearchUrl = container.dataset.searchUrl;
    let itemCount = document.querySelectorAll('.purchase-item').length;
    let isManualMode = false;

    const subtotalInput = document.getElementById('subtotal_amount');
    const gstInput = document.getElementById('total_gst_amount');
    const totalInput = document.getElementById('total_amount');
    const extraDiscountInput = document.getElementById('extra_discount_amount');
    const purchaseItemCountDisplay = document.getElementById('purchase_item_count_display');
    const originalGrandTotalInput = document.getElementById('original_grand_total_amount');
    const roundingOffInput = document.getElementById('rounding_off_amount');

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
                rowTotalField.value = afterDisc.toFixed(2);
            }
        });

        const extraDiscount = parseFloat(extraDiscountInput?.value) || 0;
        subtotal = Math.max(subtotal - extraDiscount, 0);

        const calculatedGrandTotal = subtotal + totalGst;
        const roundedGrandTotal = Math.round(calculatedGrandTotal);
        const roundingOffAmount = roundedGrandTotal - calculatedGrandTotal;

        if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);
        if (gstInput) gstInput.value = totalGst.toFixed(2);
        if (originalGrandTotalInput) originalGrandTotalInput.value = calculatedGrandTotal.toFixed(2);
        if (roundingOffInput) roundingOffInput.value = roundingOffAmount.toFixed(2);
        if (totalInput) totalInput.value = roundedGrandTotal.toFixed(2);
    }

    function updateDiscountFields(currentRow, changedField) {
        const qty = parseFloat(currentRow.querySelector('[name*="[quantity]"]')?.value) || 0;
        const purchasePrice = parseFloat(currentRow.querySelector('[name*="[purchase_price]"]')?.value) || 0;
        const baseValue = qty * purchasePrice;

        const ourDiscPercentageInput = currentRow.querySelector('.our-discount-percentage-input');
        const ourDiscAmountInput = currentRow.querySelector('.our-discount-amount-input');

        if (!ourDiscPercentageInput || !ourDiscAmountInput) return;
        if (currentRow.dataset.updatingDiscount) return;
        currentRow.dataset.updatingDiscount = 'true';

        if (changedField === 'percentage') {
            const percentage = parseFloat(ourDiscPercentageInput.value) || 0;
            ourDiscAmountInput.value = baseValue > 0 ? ((baseValue * percentage) / 100).toFixed(2) : '0.00';
        } else if (changedField === 'amount') {
            const amount = parseFloat(ourDiscAmountInput.value) || 0;
            ourDiscPercentageInput.value = baseValue > 0 ? ((amount / baseValue) * 100).toFixed(2) : '0.00';
        }

        delete currentRow.dataset.updatingDiscount;
        calculateTotals();
    }

    function fetchGstForMedicine(medicineId, currentRow) {
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
            input.removeAttribute('name');
            input.closest('div')?.appendChild(hiddenInput);
            hiddenInput.value = converted;
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
                    const itemId = wrapper.dataset.itemId;
                    if (itemId) {
                        deletedInput.value += (deletedInput.value ? ',' : '') + itemId;
                    }
                }
                wrapper.remove();
                calculateTotals();
                updateItemCountDisplay();
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
                packSelect.innerHTML = `<option value="${selectedId}" selected>${medicineSelect.data('selected-pack') || 'Pack'}</option>`;
            }
        }

        wrapper.querySelectorAll('.item-calc').forEach(el => {
            el.addEventListener('input', calculateTotals);
        });

        const ourDiscPercentageInput = wrapper.querySelector('.our-discount-percentage-input');
        if (ourDiscPercentageInput) {
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
            if (data.medicine_id && (data.medicine_text || data.medicine_name)) {
                var option = new Option(data.medicine_text || data.medicine_name, data.medicine_id, true, true);
                nameSelect.append(option).trigger('change');
            }

            const packSelect = newElement.querySelector('.pack-select');
            if (packSelect) packSelect.innerHTML = `<option value="${data.medicine_id}" selected>${data.pack || 'Standard'}</option>`;

            if (newElement.querySelector('[name$="[batch_number]"]')) newElement.querySelector('[name$="[batch_number]"]').value = data.batch_number || '';
            if (newElement.querySelector('[name$="[expiry_date]"]')) newElement.querySelector('[name$="[expiry_date]"]').value = data.expiry_date || '';

            if (newElement.querySelector('[name$="[quantity]"]')) newElement.querySelector('[name$="[quantity]"]').value = parseFloat(data.quantity || 1).toFixed(2);
            if (newElement.querySelector('[name$="[free_quantity]"]')) newElement.querySelector('[name$="[free_quantity]"]').value = parseFloat(data.free_quantity || 0).toFixed(2);

            if (newElement.querySelector('[name$="[purchase_price]"]')) newElement.querySelector('[name$="[purchase_price]"]').value = parseFloat(data.purchase_price || 0).toFixed(2);
            if (newElement.querySelector('[name$="[ptr]"]')) newElement.querySelector('[name$="[ptr]"]').value = parseFloat(data.ptr || 0).toFixed(2);
            if (newElement.querySelector('[name$="[sale_price]"]')) newElement.querySelector('[name$="[sale_price]"]').value = parseFloat(data.sale_price || 0).toFixed(2);

            const ourDiscPercentageInput = newElement.querySelector('.our-discount-percentage-input');
            if (ourDiscPercentageInput) ourDiscPercentageInput.value = parseFloat(data.our_discount_percentage || 0).toFixed(2);
            if (newElement) updateDiscountFields(newElement, 'percentage');

            if (newElement.querySelector('[name$="[gst_rate]"]')) newElement.querySelector('[name$="[gst_rate]"]').value = parseFloat(data.gst_rate || 0).toFixed(2);
            if (data.medicine_id) {
                fetchGstForMedicine(data.medicine_id, newElement);
            }
        }

        itemCount++;
        if (Object.keys(data).length === 0) {
            $(newElement).find('.medicine-name-select').select2('open');
        }
        updateItemCountDisplay();
    }

$(document).on('select2:select', '.medicine-name-select', function (e) {
    const selectedId = e.params.data.id;
    const selectedStr = String(selectedId || '');
    const currentRow = this.closest('.purchase-item');
    if (!currentRow) return;

    // Extract name and company only if format matches
    let name = '', company = '';
    if (selectedStr.includes('|')) {
        [name, company] = selectedStr.split('|');
    } else {
        name = e.params.data.text; // fallback: use text as name
    }

    const packContainer = currentRow.querySelector('.pack-selector-container');
    const packSelect = currentRow.querySelector('.pack-select');

    fetch(`/api/medicines/packs?name=${encodeURIComponent(name)}&company_name=${encodeURIComponent(company)}`)
        .then(response => response.json())
        .then(packs => {
            packSelect.innerHTML = '<option value="">Select Pack</option>';
            if (packs.length > 1) {
                packs.forEach(packInfo => {
                    const option = new Option(packInfo.pack || 'Standard', packInfo.id);
                    packSelect.appendChild(option);
                });
                packContainer.style.display = 'block';

                // Fetch GST for the first pack by default
                fetch(`/api/medicines/${packs[0].id}/gst`)
                    .then(res => res.json())
                    .then(data => {
                        currentRow.querySelector('.gst-rate').value = data.gst_rate ?? 0;
                        calculateTotals();
                    });
            } else if (packs.length === 1) {
                packContainer.style.display = 'none';
                const option = new Option(packs[0].pack || 'Standard', packs[0].id);
                packSelect.appendChild(option);
                packSelect.value = packs[0].id;
                $(packSelect).trigger('change'); // triggers GST fetch
            } else {
                packContainer.style.display = 'none';
                // If no packs, fetch GST using medicine ID
                fetch(`/api/medicines/${selectedId}/gst`)
                    .then(res => res.json())
                    .then(data => {
                        currentRow.querySelector('.gst-rate').value = data.gst_rate ?? 0;
                        calculateTotals();
                    });
            }
        })
        .catch(err => console.error('Error fetching packs:', err));
});




    $(document).on('change', '.pack-select', function () {
        const medicineId = this.value;
        const currentRow = this.closest('.purchase-item');
        fetchGstForMedicine(medicineId, currentRow);
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

    document.querySelectorAll('.purchase-item').forEach(item => {
        attachListeners(item);
        updateDiscountFields(item, 'percentage');
    });

    const oldItemsToProcess = [];
    if (window.oldPurchaseItems && window.oldPurchaseItems.length > 0) {
        window.oldPurchaseItems.forEach(item => oldItemsToProcess.push(item));
    }
    if (window.oldNewPurchaseItems && window.oldNewPurchaseItems.length > 0) {
        window.oldNewPurchaseItems.forEach(item => oldItemsToProcess.push(item));
    }
    if (window.oldExistingPurchaseItems && Object.keys(window.oldExistingPurchaseItems).length > 0) {
        Object.entries(window.oldExistingPurchaseItems).forEach(([id, itemData]) => {
            oldItemsToProcess.push({ ...itemData, id: id });
        });
    }

    if (oldItemsToProcess.length > 0) {
        oldItemsToProcess.forEach(itemData => {
            itemData.quantity = parseFloat(itemData.quantity || 0);
            itemData.free_quantity = parseFloat(itemData.free_quantity || 0);
            itemData.purchase_price = parseFloat(itemData.purchase_price || 0);
            itemData.ptr = parseFloat(itemData.ptr || 0);
            itemData.sale_price = parseFloat(itemData.sale_price || 0);
            itemData.discount_percentage = parseFloat(itemData.discount_percentage || 0);
            itemData.our_discount_percentage = parseFloat(itemData.our_discount_percentage || 0);
            itemData.gst_rate = parseFloat(itemData.gst_rate || 0);
            addItem({ ...itemData, medicine_text: itemData.medicine_name || itemData.text });
        });
    } else if (document.querySelectorAll('.purchase-item').length === 0) {
        addItem();
    }

    calculateTotals();
    $('#supplier_id').select2({ theme: 'bootstrap-5' });
});
