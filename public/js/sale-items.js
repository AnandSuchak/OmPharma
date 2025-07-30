/**
 * sale-items.js
 * This script handles all the dynamic functionality for the sales create/edit page.
 * It manages adding/removing sale items, searching for medicines, fetching batches,
 * and calculating totals in real-time.
 */

document.addEventListener('DOMContentLoaded', function () {
    // --- 1. INITIAL SETUP & VARIABLE DECLARATION ---
    const container = document.getElementById('sale_items_container');
    if (!container) {
        console.error("Sale items container not found!");
        return;
    }

    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('sale_item_template');
    const saleForm = document.querySelector('form');

    // Get data from the main container's data attributes
    const medicineSearchUrl = container.dataset.searchUrl;
    const batchApiUrlBase = container.dataset.batchBaseUrl;
    const isEditMode = container.dataset.isEdit === 'true';
    const saleId = container.dataset.saleId;

    let itemCount = document.querySelectorAll('.sale-item-wrapper').length;
 const EXTRA_DISCOUNT_PERCENTAGE = 3.00;
    // --- 2. HELPER FUNCTIONS ---

    /**
     * Resets all input fields in a given item row to their default state.
     * @param {HTMLElement} wrapper The .sale-item-wrapper element for the row.
     */
    function resetItemDetails(wrapper) {
        const inputs = {
            salePrice: wrapper.querySelector('.sale-price-input'),
            mrpDisplay: wrapper.querySelector('.mrp-input'),
            gstPercent: wrapper.querySelector('.gst-percent-input'),
            gstAmount: wrapper.querySelector('.gst-amount-input'),
            discount: wrapper.querySelector('.discount-percentage-input'),
            quantity: wrapper.querySelector('.quantity-input'),
            freeQuantity: wrapper.querySelector('.free-qty-input'),
            gstRateHidden: wrapper.querySelector('.gst-rate-input'),
            expiryDate: wrapper.querySelector('.expiry-date-input'),
            ptrHidden: wrapper.querySelector('.mrp-input-hidden'),
            packHidden: wrapper.querySelector('.pack-name-hidden'),
            availableQty: wrapper.querySelector('.available-quantity'),
        };

        if (inputs.salePrice) inputs.salePrice.value = '0.00';
        if (inputs.mrpDisplay) inputs.mrpDisplay.value = 'N/A';
        if (inputs.gstPercent) inputs.gstPercent.value = '0%';
        if (inputs.gstAmount) inputs.gstAmount.value = '0.00';
        if (inputs.discount) inputs.discount.value = '0';
        if (inputs.gstRateHidden) inputs.gstRateHidden.value = '0';
        if (inputs.expiryDate) inputs.expiryDate.value = '';
        if (inputs.ptrHidden) inputs.ptrHidden.value = '';
        if (inputs.packHidden) inputs.packHidden.value = '';
        if (inputs.quantity) {
            inputs.quantity.value = '0';
            inputs.quantity.disabled = true;
            inputs.quantity.classList.remove('is-invalid');
        }
        if (inputs.freeQuantity) inputs.freeQuantity.value = '0';
        if (inputs.availableQty) inputs.availableQty.textContent = '';
        wrapper.dataset.availableQuantity = '0';

        calculateTotals();
    }
    
    /**
     * Populates price-related fields from data.
     * @param {HTMLElement} wrapper The .sale-item-wrapper element for the row.
     * @param {object} data The data object containing pricing info.
     */
    function populatePriceDetails(wrapper, data) {
        const salePriceInput = wrapper.querySelector('.sale-price-input');
        const mrpInputDisplay = wrapper.querySelector('.mrp-input');
        const gstPercentDisplay = wrapper.querySelector('.gst-percent-input');
        const gstRateInputHidden = wrapper.querySelector('.gst-rate-input');
        const ptrInputHidden = wrapper.querySelector('.mrp-input-hidden');
        const discountInput = wrapper.querySelector('.discount-percentage-input');

        if (salePriceInput) salePriceInput.value = parseFloat(data.sale_price || 0).toFixed(2);
        if (mrpInputDisplay) mrpInputDisplay.value = data.ptr || 'N/A';
        if (gstRateInputHidden) gstRateInputHidden.value = parseFloat(data.gst_rate || 0).toFixed(2);
        if (gstPercentDisplay) gstPercentDisplay.value = `${parseFloat(data.gst_rate || 0).toFixed(2)}%`;
        if (ptrInputHidden) ptrInputHidden.value = data.ptr || '';
        if (discountInput) discountInput.value = parseFloat(data.discount_percentage || 0).toFixed(2);
    }

    /**
     * Populates a row's fields with data from a selected batch.
     * @param {HTMLElement} wrapper The .sale-item-wrapper element for the row.
     * @param {object} batchData The data object for the selected batch.
     */
    function populateBatchDetails(wrapper, batchData) {
        const expiryDateInput = wrapper.querySelector('.expiry-date-input');
        const availableQuantityDisplay = wrapper.querySelector('.available-quantity');
        const quantityInput = wrapper.querySelector('.quantity-input');

        if (expiryDateInput) expiryDateInput.value = batchData.expiry_date || '';

        let effectiveAvailable = parseFloat(batchData.quantity || 0);
        if (isEditMode && batchData.existing_sale_item) {
            effectiveAvailable += parseFloat(batchData.existing_sale_item.quantity || 0);
            quantityInput.value = parseFloat(batchData.existing_sale_item.quantity || 0).toFixed(2);
        } else {
            quantityInput.value = '1.00';
        }

        wrapper.dataset.availableQuantity = effectiveAvailable;
        if(availableQuantityDisplay) availableQuantityDisplay.textContent = `Available: ${effectiveAvailable}`;
        
        if (quantityInput) {
            quantityInput.disabled = false;
            quantityInput.setAttribute('max', effectiveAvailable);
        }
        
        calculateTotals();
    }
    
    /**
     * Fetches available batches for a given medicine ID from the server.
     * @param {string} medicineId The ID of the selected medicine.
     * @param {HTMLElement} wrapper The .sale-item-wrapper element for the row.
     * @param {string|null} selectedBatch The batch number to pre-select (for edit mode).
     */
    function fetchBatches(medicineId, wrapper, selectedBatch = null) {
        const batchSelect = $(wrapper).find('.batch-number-select');
        batchSelect.empty().trigger('change').prop('disabled', true).append(new Option('Loading...', ''));

        let url = batchApiUrlBase.replace('PLACEHOLDER', medicineId);
        if (isEditMode && saleId) {
            url += `?sale_id=${saleId}`;
        }

        fetch(url)
            .then(response => response.ok ? response.json() : Promise.reject(response.statusText))
            .then(batches => {
                batchSelect.empty().append(new Option('', '')); // Add a placeholder
                if (batches.length > 0) {
                    batches.forEach(batch => {
                        const expiry = batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString('en-IN') : 'N/A';
                        const text = `${batch.batch_number} (Avl: ${batch.quantity}, Exp: ${expiry})`;
                        const option = new Option(text, batch.batch_number);
                        $(option).data('batch-data', batch);
                        batchSelect.append(option);
                    });
                    batchSelect.prop('disabled', false);

                    if (selectedBatch) {
                        batchSelect.val(selectedBatch).trigger('change');
                        const preselectedData = $(batchSelect.find(`option[value="${selectedBatch}"]`)).data('batch-data');
                        if(preselectedData) {
                            populatePriceDetails(wrapper, preselectedData);
                            populateBatchDetails(wrapper, preselectedData);
                        }
                    } else {
                        // Auto-select the first batch
                        const firstBatch = batches[0];
                        batchSelect.val(firstBatch.batch_number).trigger('change');
                        populatePriceDetails(wrapper, firstBatch); 
                        populateBatchDetails(wrapper, firstBatch);
                    }
                } else {
                    batchSelect.append(new Option('No stock available', '', true, true));
                }
                batchSelect.trigger('change');
            })
            .catch(error => {
                console.error('Error fetching batches:', error);
                batchSelect.empty().append(new Option('Error loading', '', true, true));
            });
    }

    /**
     * Calculates all totals for the entire form and updates the display.
     */
    function calculateTotals() {
        let subtotal = 0;
        let totalGst = 0;

        document.querySelectorAll('.sale-item-wrapper').forEach((wrapper, index) => {
            const qty = parseFloat(wrapper.querySelector('.quantity-input').value) || 0;
            if (qty === 0) {
                wrapper.querySelector('.gst-amount-input').value = '0.00';
                return;
            }
            
            const price = parseFloat(wrapper.querySelector('.sale-price-input').value) || 0;
            const discount = parseFloat(wrapper.querySelector('.discount-percentage-input').value) || 0;
            const gstRate = parseFloat(wrapper.querySelector('.gst-rate-input').value) || 0;
            
            // This reads the value from the hidden input
            const appliedExtraDiscount = parseFloat(wrapper.querySelector('.applied-extra-discount-percentage').value) || 0;

            // --- CONSOLE LOG ADDED HERE ---
            console.log(`Item #${index + 1}: Applying extra discount of ${appliedExtraDiscount}%`);
            // -----------------------------

            let lineTotal = qty * price;
            lineTotal *= (1 - discount / 100);
            lineTotal *= (1 - appliedExtraDiscount / 100); // Apply extra discount here

            const gstAmount = lineTotal * (gstRate / 100);
            
            subtotal += lineTotal;
            totalGst += gstAmount;

            wrapper.querySelector('.gst-amount-input').value = gstAmount.toFixed(2);
        });

        const grandTotal = subtotal + totalGst;

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('total_gst').textContent = totalGst.toFixed(2);
        document.getElementById('grand_total').textContent = grandTotal.toFixed(2);
    }

    /**
     * Initializes all event listeners for a given item row.
     */
/**
 * Initializes all event listeners for a given item row.
 * @param {HTMLElement} wrapper The .sale-item-wrapper element for the row.
 */
function initializeRow(wrapper) {
    const medicineSelect = $(wrapper).find('.medicine-name-select');
    const batchSelect = $(wrapper).find('.batch-number-select');

    // Initialize Select2
    medicineSelect.select2({
        theme: 'bootstrap-5',
        placeholder: 'Search for medicine...',
        ajax: {
            url: medicineSearchUrl,
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({
                // We map the results to a flat list, since each is a unique choice
                results: data.flatMap(item => 
                    item.packs.map(pack => ({
                        id: pack.medicine_id,
                        text: item.text, // The display text is the main group text
                        // We attach all the necessary data to the result object
                        original_pack_data: pack 
                    }))
                )
            })
        }
    });
    batchSelect.select2({ theme: 'bootstrap-5', placeholder: 'Select batch' });
    
    // --- EVENT LISTENERS ---

    // When a medicine/pack is selected from the main search
    medicineSelect.on('select2:select', function(e) {
        const packData = e.params.data.original_pack_data;
        if (!packData) return;

        // 1. Set the hidden input values
        wrapper.querySelector('.medicine-id-input').value = packData.medicine_id;
        wrapper.querySelector('.pack-name-hidden').value = packData.pack;

        // 2. Populate prices immediately from the search data
        populatePriceDetails(wrapper, packData);

        // 3. Fetch all available batches for that specific medicine ID
        fetchBatches(packData.medicine_id, wrapper);
    });

    // When a specific batch is chosen from the second dropdown
    batchSelect.on('select2:select', function(e) {
        const batchData = $(e.params.data.element).data('batch-data');
        if (batchData) {
            // Repopulate all details, as this specific batch might have different pricing
            populatePriceDetails(wrapper, batchData);
            populateBatchDetails(wrapper, batchData);
        }
    });
    
    // Add listeners to all inputs that should trigger a recalculation of totals
    wrapper.querySelectorAll('.item-calc').forEach(el => el.addEventListener('input', calculateTotals));
    const checkbox = wrapper.querySelector('.extra-discount-checkbox');
    if (checkbox) {
        checkbox.addEventListener('change', calculateTotals);
    }
    
    // Add listener for the remove button
    wrapper.querySelector('.remove-new-item').addEventListener('click', () => {
        const isExisting = wrapper.dataset.existingItem === 'true';
        if (isExisting) {
            const deletedInput = document.getElementById('deleted_items');
            deletedInput.value += (deletedInput.value ? ',' : '') + wrapper.dataset.itemId;
        }
        wrapper.remove();
        calculateTotals();
    });
}
    /**
     * Creates a new item row from the template and adds it to the container.
     */
    function addNewItem() {
        const templateContent = template.innerHTML
            .replace(/__INDEX__/g, itemCount)
            .replace(/__PREFIX__/g, `new_sale_items[${itemCount}]`);
        
        const newWrapper = document.createElement('div');
        newWrapper.innerHTML = templateContent;
        const itemRow = newWrapper.firstElementChild; 
        itemRow.classList.add('sale-item-wrapper');
        
        container.appendChild(itemRow);
        initializeRow(itemRow);
        itemCount++;
    }

    // --- 3. SCRIPT INITIALIZATION ---
    document.querySelectorAll('.sale-item-wrapper').forEach(wrapper => {
        initializeRow(wrapper);
        if (isEditMode && wrapper.dataset.medicineId) {
            const { medicineId, batchNumber, medicineName, pack } = wrapper.dataset;
            
            $(wrapper).find('.medicine-name-select').append(new Option(medicineName, medicineId, true, true)).trigger('change');
            fetchBatches(medicineId, wrapper, batchNumber);
        }
    });

    if (!isEditMode && itemCount === 0) {
        addNewItem();
    }

    addItemBtn.addEventListener('click', addNewItem);
    
    if (isEditMode) {
        calculateTotals();
    }
});
