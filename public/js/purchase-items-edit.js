// public/js/purchase-items-edit.js

document.addEventListener('DOMContentLoaded', function () {
    console.log("Purchase items EDIT script initialized.");

    const container = document.getElementById('purchase_items_container');
    if (!container) {
        console.error("Initialization failed: purchase_items_container not found.");
        return;
    }

    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('purchase_item_template')?.content;
    const medicineSearchUrl = container.dataset.searchUrl;
    const deletedItemsInput = document.getElementById('deleted_items');
    let newItemCount = 0;
    let isManualMode = false;

    // === Totals fields ===
    const subtotalInput = document.getElementById('subtotal_amount');
    const gstInput = document.getElementById('total_gst_amount');
    const totalInput = document.getElementById('total_amount');
    const extraDiscountInput = document.getElementById('extra_discount_amount');
    const originalGrandTotalInput = document.getElementById('original_grand_total_amount');
    const roundingOffInput = document.getElementById('rounding_off_amount');
    const purchaseItemCountDisplay = document.getElementById('purchase_item_count_display');

    function updateItemCountDisplay() {
        const count = document.querySelectorAll('.purchase-item').length;
        if (purchaseItemCountDisplay) purchaseItemCountDisplay.textContent = count;
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
            if (rowTotalField) rowTotalField.value = afterDisc.toFixed(2);
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

        if (!ourDiscPercentageInput || !ourDiscAmountInput || currentRow.dataset.updatingDiscount) return;
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
            });
    }

    function convertExpiryToDate(mmYY) {
        if (!mmYY || !mmYY.includes('/')) return '';
        const [month, year] = mmYY.split('/');
        return year && month ? `20${year}-${month.padStart(2, '0')}-01` : '';
    }

    document.querySelector('form')?.addEventListener('submit', function () {
        document.querySelectorAll('.expiry-date').forEach(input => {
            const converted = convertExpiryToDate(input.value);
            let hiddenInput = input.parentElement.querySelector(`input[type="hidden"][name="${input.getAttribute('name')}"]`);
            if (hiddenInput) {
                hiddenInput.value = converted;
                input.removeAttribute('name');
            }
        });
    });

    document.addEventListener('input', function (e) {
        const input = e.target;
        if (input.classList.contains('expiry-date')) {
            let value = input.value.replace(/[^\d]/g, '');
            if (value.length >= 2) value = value.slice(0, 2) + '/' + value.slice(2, 4);
            input.value = value;
        }
        if (input.classList.contains('our-discount-percentage-input')) {
            updateDiscountFields(input.closest('.purchase-item'), 'percentage');
        } else if (input.classList.contains('our-discount-amount-input')) {
            updateDiscountFields(input.closest('.purchase-item'), 'amount');
        }
    });

    function attachListeners(wrapper) {
        $(wrapper).find('.medicine-name-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Search Medicine Name',
            ajax: { url: medicineSearchUrl, dataType: 'json', delay: 250, data: params => ({ q: params.term }), processResults: data => ({ results: data }) }
        });

        const medicineSelect = $(wrapper).find('.medicine-name-select');
        const selectedId = medicineSelect.data('selected-id');
        const selectedText = medicineSelect.data('selected-text');
        if (selectedId && selectedText) {
            medicineSelect.append(new Option(selectedText, selectedId, true, true)).trigger('change');
        }

        wrapper.querySelector('.remove-item')?.addEventListener('click', () => {
            const itemId = wrapper.dataset.itemId;
            if (itemId && deletedItemsInput) {
                const deletedIds = deletedItemsInput.value ? deletedItemsInput.value.split(',') : [];
                if (!deletedIds.includes(itemId)) {
                    deletedIds.push(itemId);
                    deletedItemsInput.value = deletedIds.join(',');
                }
            }
            wrapper.remove();
            calculateTotals();
            updateItemCountDisplay();
        });

        wrapper.querySelectorAll('.item-calc').forEach(el => el.addEventListener('input', calculateTotals));
    }

    function addNewItem(data = {}) {
        if (!template) {
            console.error('CRITICAL: purchase_item_template not found!');
            return;
        }
        const newElement = template.cloneNode(true).firstElementChild;
        newElement.innerHTML = newElement.innerHTML.replace(/__INDEX__/g, `new_${newItemCount}`);
        container.appendChild(newElement);
        attachListeners(newElement);

        newItemCount++;
        updateItemCountDisplay();
    }

    $(document).on('select2:select', '.medicine-name-select', function (e) {
        const { id, text } = e.params.data;
        const currentRow = this.closest('.purchase-item');
        if (!currentRow) return;

        const medicineNameInput = currentRow.querySelector('.medicine-name-hidden-input');
        if (medicineNameInput) medicineNameInput.value = text;

        const packSelect = currentRow.querySelector('.pack-select');
        const packContainer = currentRow.querySelector('.pack-selector-container');
        
        fetch(`/api/medicines/packs?name=${encodeURIComponent(text.split('|')[0].trim())}`)
            .then(response => response.json())
            .then(packs => {
                packSelect.innerHTML = '<option value="">Select Pack</option>';
                if (packs && packs.length > 0) {
                    packs.forEach(pack => packSelect.add(new Option(pack.pack || 'Standard', pack.id)));
                    packContainer.style.display = 'block';
                    packSelect.value = packs[0].id;
                    $(packSelect).trigger('change');
                } else {
                    packContainer.style.display = 'none';
                    const medicineIdInput = currentRow.querySelector('.medicine-id-hidden-input');
                    if (medicineIdInput) medicineIdInput.value = id;
                    fetchGstForMedicine(id, currentRow);
                }
            });
    });

    $(document).on('change', '.pack-select', function () {
        const selectedPackId = this.value;
        const currentRow = this.closest('.purchase-item');
        if (!currentRow) return;

        const medicineIdInput = currentRow.querySelector('.medicine-id-hidden-input');
        if (medicineIdInput) medicineIdInput.value = selectedPackId;
        fetchGstForMedicine(selectedPackId, currentRow);
    });

    if (addItemBtn) addItemBtn.addEventListener('click', () => addNewItem());

    document.querySelectorAll('#purchase_items_container .purchase-item').forEach(wrapper => attachListeners(wrapper));

    if (window.oldNewPurchaseItems && window.oldNewPurchaseItems.length > 0) {
        window.oldNewPurchaseItems.forEach(itemData => addNewItem(itemData));
    }

    calculateTotals();
    $('#supplier_id').select2({ theme: 'bootstrap-5' });
});
