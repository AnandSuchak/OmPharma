document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('sale_items_container');
    if (!container) return;

    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('sale_item_template');
    const medicineSearchUrl = container.dataset.searchUrl;
    const batchApiUrlBase = container.dataset.batchBaseUrl;
    const isEditMode = container.dataset.isEdit === 'true';
    const saleId = container.dataset.saleId;
    let itemCount = document.querySelectorAll('.sale-item-wrapper').length;
    const saleForm = document.querySelector('form');

    const EXTRA_DISCOUNT_PERCENTAGE = 3;

    function clearAndDisable(wrapper, selector) {
        const select = $(wrapper).find(selector);
        select.empty().trigger('change').prop('disabled', true);
    }

    function resetItemDetails(wrapper) {
        wrapper.querySelector('.sale-price-input').value = '0.00';
        wrapper.querySelector('.mrp-input').value = 'N/A';
        wrapper.querySelector('.gst-percent-input').value = '0%';
        wrapper.querySelector('.gst-amount-input').value = '0.00';
        wrapper.querySelector('.discount-percentage-input').value = '0';
        wrapper.querySelector('.gst-rate-input').value = '0';
        wrapper.querySelector('.expiry-date-input').value = '';
        wrapper.querySelector('.mrp-input-hidden').value = '';
        if (wrapper.querySelector('.pack-name-hidden')) {
            wrapper.querySelector('.pack-name-hidden').value = '';
        }
        wrapper.querySelector('.extra-discount-checkbox').checked = false;
        wrapper.querySelector('.applied-extra-discount-percentage').value = '0.00';
        const quantityInput = wrapper.querySelector('.quantity-input');
        quantityInput.value = '0';
        quantityInput.disabled = true;
        quantityInput.removeAttribute('max');
        wrapper.querySelector('.free-qty-input').value = '0';
        wrapper.setAttribute('data-available-quantity', '0');
        wrapper.querySelector('.available-quantity').textContent = '';
    }

    function initializeRow(wrapper) {
        $(wrapper).find('.medicine-name-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Search for medicine...',
            allowClear: true,
            ajax: {
                url: medicineSearchUrl,
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term }),
                processResults: data => ({
                    results: data.map(item => ({
                        id: item.id,
                        text: item.text,
                        original_data: item
                    }))
                })
            }
        });
        $(wrapper).find('.pack-select').select2({ theme: 'bootstrap-5', placeholder: 'Select pack', allowClear: true });
        $(wrapper).find('.batch-number-select').select2({ theme: 'bootstrap-5', placeholder: 'Select batch', allowClear: true });

        clearAndDisable(wrapper, '.pack-select');
        clearAndDisable(wrapper, '.batch-number-select');

        $(wrapper).find('.medicine-name-select').on('select2:select', function (e) {
            const selectedData = e.params.data.original_data;
            resetItemDetails(wrapper);
            if (selectedData && selectedData.packs && selectedData.packs.length > 0) {
                const packSelect = $(wrapper).find('.pack-select');
                packSelect.prop('disabled', false).append(new Option('', ''));
                selectedData.packs.forEach(pack => {
                    const option = new Option(pack.text, pack.medicine_id);
                    $(option).data('pack-name', pack.pack);
                    packSelect.append(option);
                });
                if (selectedData.packs.length === 1) {
                    const pack = selectedData.packs[0];
                    packSelect.val(pack.medicine_id).trigger('change');
                    packSelect.trigger({
                        type: 'select2:select',
                        params: { data: { id: pack.medicine_id, text: pack.text, element: packSelect.find(`option[value="${pack.medicine_id}"]`)[0] } }
                    });
                } else {
                    packSelect.trigger('change').select2('open');
                }
            }
        });

        $(wrapper).find('.pack-select').on('select2:select', function (e) {
            const medicineId = e.params.data.id;
            if (!medicineId) return;
            const packName = $(e.params.data.element).data('pack-name') || e.params.data.text;
            wrapper.querySelector('.medicine-id-input').value = medicineId;
            wrapper.querySelector('.pack-name-hidden').value = packName;
            fetchBatches(medicineId, wrapper, null);
        });

        $(wrapper).find('.batch-number-select').on('select2:select', function (e) {
            const data = $(e.params.data.element).data('batch-data');
            if (data) populateBatchDetails(wrapper, data);
        });

        wrapper.querySelector('.remove-new-item').addEventListener('click', () => {
            const existingId = wrapper.dataset.itemId;
            const isExisting = wrapper.dataset.existingItem === 'true';
            if (isExisting && existingId) {
                const deletedField = document.getElementById('deleted_items');
                let current = deletedField.value ? deletedField.value.split(',') : [];
                if (!current.includes(existingId)) current.push(existingId);
                deletedField.value = current.join(',');
            }
            wrapper.remove();
            calculateTotals();
        });

        wrapper.querySelectorAll('.item-calc, .extra-discount-checkbox').forEach(el => {
            el.addEventListener('input', calculateTotals);
            el.addEventListener('change', calculateTotals);
        });
        wrapper.querySelector('.quantity-input').addEventListener('input', e => {
            const availableQty = parseFloat(wrapper.getAttribute('data-available-quantity') || 0);
            validateQuantity(e.target, availableQty);
        });
    }

    function fetchBatches(medicineId, wrapper, selectedBatch = null) {
        const batchSelect = $(wrapper).find('.batch-number-select');
        clearAndDisable(wrapper, '.batch-number-select');
        let url = batchApiUrlBase.replace('PLACEHOLDER', medicineId);
        if (isEditMode && saleId) url += `?sale_id=${saleId}`;

        fetch(url)
            .then(res => res.ok ? res.json() : Promise.reject(res.statusText))
            .then(batches => {
                if (batches.length === 0) {
                    batchSelect.append(new Option('No stock available', '', true, true)).trigger('change');
                    return;
                }
                batches.sort((a, b) => new Date(a.expiry_date) - new Date(b.expiry_date));
                let preselectedBatchData = null;
                batches.forEach(batch => {
                    const expiry = batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString('en-IN') : 'N/A';
                    const text = `${batch.batch_number} (Avl: ${batch.quantity}, Exp: ${expiry})`;
                    const option = new Option(text, batch.batch_number);
                    $(option).data('batch-data', batch);
                    batchSelect.append(option);
                    if (selectedBatch && batch.batch_number === selectedBatch) preselectedBatchData = batch;
                });
                batchSelect.prop('disabled', false).trigger('change');
                const batchToSelect = preselectedBatchData || batches.find(b => b.quantity > 0) || batches[0];
                if (batchToSelect) {
                    batchSelect.val(batchToSelect.batch_number).trigger('change');
                    batchSelect.trigger({
                        type: 'select2:select',
                        params: { data: { element: batchSelect.find(`option[value="${batchToSelect.batch_number}"]`)[0] } }
                    });
                }
            })
            .catch(err => {
                console.error("Fetch batches failed:", err);
                batchSelect.empty().append(new Option('Error loading batches', '', true, true)).trigger('change');
            });
    }

    function populateBatchDetails(wrapper, data) {
        wrapper.querySelector('.sale-price-input').value = parseFloat(data.sale_price || 0).toFixed(2);
        wrapper.querySelector('.mrp-input').value = data.ptr || 'N/A';
        wrapper.querySelector('.gst-rate-input').value = parseFloat(data.gst || 0).toFixed(2);
        wrapper.querySelector('.gst-percent-input').value = `${parseFloat(data.gst || 0).toFixed(2)}%`;
        wrapper.querySelector('.expiry-date-input').value = data.expiry_date;
        wrapper.querySelector('.mrp-input-hidden').value = data.ptr || '';

        let effectiveAvailable = parseFloat(data.quantity);
        if (isEditMode && data.existing_sale_item) {
            effectiveAvailable += parseFloat(data.existing_sale_item.quantity || 0) + parseFloat(data.existing_sale_item.free_quantity || 0);
            wrapper.querySelector('.quantity-input').value = parseFloat(data.existing_sale_item.quantity || 0).toFixed(2);
        } else {
            wrapper.querySelector('.quantity-input').value = '1.00';
        }

        const quantityInput = wrapper.querySelector('.quantity-input');
        quantityInput.disabled = false;
        quantityInput.setAttribute('max', effectiveAvailable);
        wrapper.setAttribute('data-available-quantity', effectiveAvailable);
        wrapper.querySelector('.available-quantity').textContent = `Available: ${effectiveAvailable}`;
        validateQuantity(quantityInput, effectiveAvailable);
        calculateTotals();
    }

    function validateQuantity(quantityInput, available) {
        if (!quantityInput) return;
        quantityInput.classList.remove('is-invalid');
        const requested = parseFloat(quantityInput.value || 0);
        if (isNaN(available) || requested > available) {
            quantityInput.classList.add('is-invalid');
        }
    }

    function calculateTotals() {
        let subtotal = 0;
        let totalGst = 0;
        document.querySelectorAll('.sale-item-wrapper').forEach(wrapper => {
            const qty = parseFloat(wrapper.querySelector('.quantity-input').value) || 0;
            if (qty === 0) {
                wrapper.querySelector('.gst-amount-input').value = '0.00';
                return;
            }
            const price = parseFloat(wrapper.querySelector('.sale-price-input').value) || 0;
            const discount = parseFloat(wrapper.querySelector('.discount-percentage-input').value) || 0;
            const gstRate = parseFloat(wrapper.querySelector('.gst-rate-input').value) || 0;
            const extraDiscountChecked = wrapper.querySelector('.extra-discount-checkbox').checked;
            const extraDiscount = extraDiscountChecked ? EXTRA_DISCOUNT_PERCENTAGE : 0;
            wrapper.querySelector('.applied-extra-discount-percentage').value = extraDiscount.toFixed(2);
            let lineTotal = qty * price;
            lineTotal *= (1 - discount / 100);
            lineTotal *= (1 - extraDiscount / 100);
            const gstAmount = lineTotal * (gstRate / 100);
            subtotal += lineTotal;
            totalGst += gstAmount;
            wrapper.querySelector('.gst-amount-input').value = gstAmount.toFixed(2);
        });
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('total_gst').textContent = totalGst.toFixed(2);
        document.getElementById('grand_total').textContent = Math.round(subtotal + totalGst).toFixed(2);
    }

    function addItem(initialData = {}) {
        const prefix = initialData.id
            ? `existing_sale_items[${initialData.id}]`
            : `new_sale_items[${itemCount}]`;

        const templateContent = template.innerHTML
            .replace(/__INDEX__/g, itemCount)
            .replace(/__PREFIX__/g, prefix);

        const newWrapper = document.createElement('div');
        newWrapper.classList.add('sale-item-wrapper');
        newWrapper.innerHTML = templateContent;

        if (initialData.itemId) {
            newWrapper.dataset.itemId = initialData.itemId;
        }

        container.appendChild(newWrapper);
        initializeRow(newWrapper);

        if (Object.keys(initialData).length > 0) {
            populateExistingRow(newWrapper, initialData);
        } else {
            $(newWrapper).find('.medicine-name-select').select2('open');
        }

        itemCount++;
    }

    function populateExistingRow(wrapper, data) {
        const medicineId = data.medicineId || data.medicine_id;
        if (!medicineId) return;

        const medicineName = data.medicineName || data.medicine_name;
        const packName = data.pack;
        const batchNumber = data.batchNumber || data.batch_number;

        wrapper.querySelector('.medicine-id-input').value = medicineId;
        wrapper.querySelector('.pack-name-hidden').value = packName;
        const medSelect = $(wrapper).find('.medicine-name-select');
        medSelect.append(new Option(medicineName, medicineName, true, true)).trigger('change');
        const packSelect = $(wrapper).find('.pack-select');
        const packOption = new Option(packName, medicineId, true, true);
        $(packOption).data('pack-name', packName);
        packSelect.append(packOption).trigger('change').prop('disabled', false);
        fetchBatches(medicineId, wrapper, batchNumber);
    }

    if (isEditMode) {
        document.querySelectorAll('.sale-item-wrapper[data-existing-item="true"]').forEach(wrapper => {
            initializeRow(wrapper);
            populateExistingRow(wrapper, wrapper.dataset);
        });
    } else if (container.children.length === 0) {
        addItem();
    }

    addItemBtn.addEventListener('click', () => addItem());

    saleForm.addEventListener('submit', function (event) {
        let isValid = true;
        document.querySelectorAll('.sale-item-wrapper').forEach(wrapper => {
            wrapper.classList.remove('border-danger');
            let hasError = false;
            if (!wrapper.querySelector('.medicine-id-input').value) hasError = true;
            if (!wrapper.querySelector('.batch-number-select').value) hasError = true;
            const quantityInput = wrapper.querySelector('.quantity-input');
            if (parseFloat(quantityInput.value || 0) <= 0) hasError = true;
            if (quantityInput.classList.contains('is-invalid')) hasError = true;
            if (hasError) {
                isValid = false;
                wrapper.classList.add('border-danger');
            }
        });
        if (!isValid) {
            event.preventDefault();
            alert('Please fill all required fields for each item and ensure quantity is valid.');
        }
    });

    if (isEditMode) {
        calculateTotals();
    }
});
