document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('purchase_items_container');
    if (!container) return;
    
    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('purchase_item_template').content;
    const medicineSearchUrl = container.dataset.searchUrl;
    let itemCount = 0;
    let isManualMode = false;

    const subtotalInput = document.getElementById('subtotal_amount');
    const gstInput = document.getElementById('total_gst_amount');
    const totalInput = document.getElementById('total_amount');

    function calculateTotals() {
        if (isManualMode) return;
        let subtotal = 0, totalGst = 0;

        document.querySelectorAll('.purchase-item').forEach(item => {
            const qty = parseFloat(item.querySelector('[name*="[quantity]"]').value) || 0;
            const price = parseFloat(item.querySelector('[name*="[purchase_price]"]').value) || 0;
            const disc = parseFloat(item.querySelector('[name*="[discount_percentage]"]').value) || 0;
            const gstRate = parseFloat(item.querySelector('[name*="[gst_rate]"]').value) || 0;

            const base = qty * price;
            const afterDisc = base - (base * disc / 100);
            const gst = afterDisc * (gstRate / 100);

            subtotal += afterDisc;
            totalGst += gst;
        });

        subtotalInput.value = subtotal.toFixed(2);
        gstInput.value = totalGst.toFixed(2);
        totalInput.value = (subtotal + totalGst).toFixed(2);
    }

    function attachListeners(wrapper) {
        const removeBtn = wrapper.querySelector('.remove-item');
        if(removeBtn) {
            removeBtn.addEventListener('click', () => {
                wrapper.remove();
                calculateTotals();
            });
        }
        
        $(wrapper).find('.medicine-name-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Search Medicine Name',
            ajax: {
                url: medicineSearchUrl,
                dataType: 'json',
                delay: 250,
                processResults: data => ({ results: data }),
                cache: true
            }
        });

        wrapper.querySelectorAll('.item-calc').forEach(el => {
            el.addEventListener('input', calculateTotals);
        });
    }

    function addItem(data = {}) {
        const clone = template.cloneNode(true);
        let content = new XMLSerializer().serializeToString(clone);
        content = content.replace(/__INDEX__/g, itemCount);
        
        const newWrapper = document.createElement('div');
        newWrapper.innerHTML = content;
        const newElement = newWrapper.firstElementChild;
        
        container.appendChild(newElement);
        attachListeners(newElement);

        if (Object.keys(data).length > 0) {
            // Repopulate fields from old input
            const packSelect = newElement.querySelector('.pack-select');
            packSelect.name = `purchase_items[${itemCount}][medicine_id]`;
            if (data.medicine_id && data.medicine_text) {
                var option = new Option(data.medicine_text, data.medicine_id, true, true);
                const nameSelect = $(newElement).find('.medicine-name-select');
                nameSelect.append(option).trigger('change');
                packSelect.innerHTML = `<option value="${data.medicine_id}" selected>${data.pack || 'Standard'}</option>`;
            }
            newElement.querySelector('[name$="[batch_number]"]').value = data.batch_number || '';
            newElement.querySelector('[name$="[expiry_date]"]').value = data.expiry_date || '';
            newElement.querySelector('[name$="[quantity]"]').value = data.quantity || 1;
            newElement.querySelector('[name$="[purchase_price]"]').value = data.purchase_price || '';
            newElement.querySelector('[name$="[ptr]"]').value = data.ptr || '';
            newElement.querySelector('[name$="[sale_price]"]').value = data.sale_price || '';
            newElement.querySelector('[name$="[discount_percentage]"]').value = data.discount_percentage || 0;
            newElement.querySelector('[name$="[gst_rate]"]').value = data.gst_rate || '';
        }

        itemCount++;
    }

    $(document).on('select2:select', '.medicine-name-select', function(e) {
        const selectedData = e.params.data.id;
        const [name, company] = selectedData.split('|');
        const currentRow = this.closest('.purchase-item');
        if (!name || !currentRow) return;

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
        const currentRow = this.closest('.purchase-item');
        if (!medicineId || !currentRow) return;

        const gstRateField = currentRow.querySelector('.gst-rate');
        
        fetch(`/api/medicines/${medicineId}/gst`)
            .then(res => res.json())
            .then(data => {
                gstRateField.value = data.gst_rate ?? 0;
                calculateTotals();
            })
            .catch(() => gstRateField.value = 0);
    });

    addItemBtn.addEventListener('click', () => addItem());
    
    document.getElementById('toggle_manual_edit')?.addEventListener('click', function () {
        isManualMode = !isManualMode;
        [subtotalInput, gstInput, totalInput].forEach(field => field.readOnly = !isManualMode);
        this.innerHTML = isManualMode
            ? '<i class="fa fa-lock"></i> Lock Totals'
            : '<i class="fa fa-pencil-alt"></i> Manual Edit';
        if (!isManualMode) calculateTotals();
    });

    $('#supplier_id').select2({ theme: 'bootstrap-5' });
    $('.select2-basic').select2({ theme: 'bootstrap-5', width: '100%' });

    // Restore old input data on validation failure
    if (window.oldPurchaseItems && window.oldPurchaseItems.length > 0) {
        window.oldPurchaseItems.forEach(itemData => {
            addItem(itemData);
        });
    } else {
        const existingItems = document.querySelectorAll('.purchase-item').length;
        if (existingItems === 0) {
            addItem();
        } else {
            document.querySelectorAll('.purchase-item').forEach(item => attachListeners(item));
        }
    }
    calculateTotals();
});