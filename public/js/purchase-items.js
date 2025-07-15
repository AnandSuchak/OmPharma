document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('purchase_items_container');
    if (!container) return;
    
    // --- Configuration ---
    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('purchase_item_template')?.content;
    const medicineSearchUrl = container.dataset.searchUrl;
    let itemCount = document.querySelectorAll('.purchase-item').length;
    let isManualMode = false;

    const subtotalInput = document.getElementById('subtotal_amount');
    const gstInput = document.getElementById('total_gst_amount');
    const totalInput = document.getElementById('total_amount');

    // --- Core Functions ---

    function calculateTotals() {
        if (isManualMode) return;
        let subtotal = 0, totalGst = 0;

        document.querySelectorAll('.purchase-item').forEach(item => {
            const qty = parseFloat(item.querySelector('[name*="[quantity]"]').value) || 0;
            const price = parseFloat(item.querySelector('[name*="[purchase_price]"]').value) || 0;
            
            // **CHANGE 1**: Use the new 'our_discount_percentage' for the main calculation
            const ourDisc = parseFloat(item.querySelector('[name*="[our_discount_percentage]"]').value) || 0; 
            
            const gstRate = parseFloat(item.querySelector('[name*="[gst_rate]"]').value) || 0;

            const base = qty * price;
            const afterDisc = base * (1 - ourDisc / 100); 
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

        wrapper.querySelectorAll('.item-calc').forEach(el => {
            el.addEventListener('input', calculateTotals);
        });
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
            if (data.medicine_id && data.medicine_text) {
                var option = new Option(data.medicine_text, data.medicine_id, true, true);
                nameSelect.append(option).trigger('change');
            }
            const packSelect = newElement.querySelector('.pack-select');
            packSelect.innerHTML = `<option value="${data.medicine_id}" selected>${data.pack || 'Standard'}</option>`;
            
            newElement.querySelector('[name$="[batch_number]"]').value = data.batch_number || '';
            newElement.querySelector('[name$="[expiry_date]"]').value = data.expiry_date || '';
            newElement.querySelector('[name$="[quantity]"]').value = data.quantity || 1;
               newElement.querySelector('[name$="[free_quantity]"]').value = data.free_quantity || 0; 
            newElement.querySelector('[name$="[purchase_price]"]').value = data.purchase_price || '';
            newElement.querySelector('[name$="[ptr]"]').value = data.ptr || '';
            newElement.querySelector('[name$="[sale_price]"]').value = data.sale_price || '';
            newElement.querySelector('[name$="[discount_percentage]"]').value = data.discount_percentage || 0;

            // **CHANGE 2**: Repopulate the new discount field if there's a validation error
            newElement.querySelector('[name$="[our_discount_percentage]"]').value = data.our_discount_percentage || 0;

            newElement.querySelector('[name$="[gst_rate]"]').value = data.gst_rate || '';
        }

        itemCount++;
        if (Object.keys(data).length === 0) { 
             $(newElement).find('.medicine-name-select').select2('open');
        }
    }

    // --- Event Listeners & Initialization ---

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
                        packSelect.appendChild(new Option(packInfo.pack || 'Standard', packInfo.id));
                    });
                    packContainer.style.display = 'block';
                } else if (packs.length === 1) {
                    packContainer.style.display = 'none';
                    packSelect.appendChild(new Option(packs[0].pack || 'Standard', packs[0].id, true, true));
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

    if(addItemBtn) {
        addItemBtn.addEventListener('click', () => addItem());
    }
    
    document.getElementById('toggle_manual_edit')?.addEventListener('click', function () {
        isManualMode = !isManualMode;
        [subtotalInput, gstInput, totalInput].forEach(field => field.readOnly = !isManualMode);
        this.innerHTML = isManualMode
            ? '<i class="fa fa-lock"></i> Lock Totals'
            : '<i class="fa fa-pencil-alt"></i> Manual Edit';
        if (!isManualMode) calculateTotals();
    });
    
    document.querySelectorAll('.purchase-item').forEach(item => attachListeners(item));

    const oldItems = window.oldPurchaseItems || window.oldNewPurchaseItems || [];
    if (oldItems.length > 0) {
        oldItems.forEach(itemData => {
            addItem(itemData);
        });
    }
    
    if (document.querySelectorAll('.purchase-item').length === 0) {
        addItem();
    }
    
    calculateTotals();
    $('#supplier_id').select2({ theme: 'bootstrap-5' });
});
