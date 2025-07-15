document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('sale_items_container');
    if (!container) return;

    const addItemBtn = document.getElementById('add_new_item');
    const template = document.getElementById('sale_item_template');
    const medicineSearchUrl = container.dataset.searchUrl;
    const batchBaseUrl = container.dataset.batchBaseUrl; 

    let itemCount = document.querySelectorAll('.sale-item-wrapper').length;
    const saleForm = document.querySelector('form');

    // Function to initialize event listeners and Select2 for a row
    function initializeRow(wrapper) {
       const medicineSelect = $(wrapper).find('.medicine-name-select');
        const batchSelect = $(wrapper).find('.batch-number-select');
        const removeBtn = wrapper.querySelector('.remove-item');
        const quantityInput = wrapper.querySelector('.quantity-input');
        const freeQuantityInput = wrapper.querySelector('.free-quantity-input');
        const salePriceInput = wrapper.querySelector('.sale-price-input');
        // FIX HERE: Changed from discount_percentage-input to discount-percentage-input
        const discountInput = wrapper.querySelector('.discount-percentage-input'); 
        const packInput = wrapper.querySelector('.pack-input'); 
        const correctMedicineSearchUrl = medicineSearchUrl;

        // Initialize Select2 for Medicine selection
        medicineSelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Search for medicine...',
            allowClear: true,
            ajax: {
                url: correctMedicineSearchUrl,
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    // Assumes backend (MedicineController::search) returns {id, text, pack}
                    return {
                        results: data.map(item => ({
                            id: item.id,
                            text: item.text,
                            pack: item.pack
                        }))
                    };
                },
                cache: true
            }
        });

        // Initialize Select2 for Batch selection (initially disabled)
        batchSelect.select2({ theme: 'bootstrap-5', placeholder: 'Select batch...' }).prop('disabled', true);


        // Event listener for removing an item row
        removeBtn.addEventListener('click', () => {
            const deletedInput = document.getElementById('deleted_items');
            const existingId = wrapper.querySelector('input[name*="[id]"]')?.value;
            if (existingId) {
                deletedInput.value += (deletedInput.value ? ',' : '') + existingId;
            }
            wrapper.remove();
            calculateTotals();
        });

        // Event listeners for recalculating totals on input changes
        wrapper.querySelectorAll('.item-calc').forEach(el => el.addEventListener('input', calculateTotals));

        // When medicine is selected
        medicineSelect.on('select2:select', e => {
            const medicineId = e.params.data.id;
            const medicinePack = e.params.data.pack;
            packInput.value = medicinePack || '';
            
            fetchBatches(medicineId, wrapper);
        });

        // Clear Batch select, pack input, and item details when medicine selection is cleared
        medicineSelect.on('select2:clear', () => {
            batchSelect.empty().trigger('change').prop('disabled', true);
            packInput.value = '';
            resetItemDetails(wrapper);
            calculateTotals();
        });


        // When batch is selected
        batchSelect.on('select2:select', function (e) {
            const selectedElement = e.params.data.element;
            if (selectedElement) {
                const data = $(selectedElement).data('batch-data');
                if (data) {
                    populateBatchDetails(wrapper, data);
                    quantityInput.disabled = false;
                    salePriceInput.disabled = false;
                    discountInput.disabled = false;
                    quantityInput.setAttribute('max', data.quantity);
                }
            }
        });

        // Validate quantity whenever the quantity input changes
        quantityInput.addEventListener('input', () => validateQuantity(quantityInput));
        // Free quantity just triggers recalculation
        freeQuantityInput.addEventListener('input', calculateTotals);
    }

    // Helper function to reset all item details (sale price, gst, etc.)
    function resetItemDetails(wrapper) {
        // Clear visible fields
        wrapper.querySelector('.sale-price-input').value = parseFloat(0).toFixed(2);
        wrapper.querySelector('.mrp-input').value = 'N/A';
        wrapper.querySelector('.gst-percent-input').value = '0%';
        wrapper.querySelector('.gst-amount-input').value = parseFloat(0).toFixed(2);
          wrapper.querySelector('.discount-percentage-input').value = 0; 
        wrapper.querySelector('.quantity-input').value = 0;
        wrapper.querySelector('.free-quantity-input').value = 0;

        // Clear hidden fields
        wrapper.querySelector('.gst-rate-input').value = 0;
        wrapper.querySelector('.expiry-date-input').value = '';
        wrapper.querySelector('.mrp-input-hidden').value = '';

        // Disable and reset quantity-related attributes
        wrapper.querySelector('.quantity-input').disabled = true;
        wrapper.querySelector('.sale-price-input').disabled = true;
        wrapper.querySelector('.discount-percentage-input').disabled = true;
        wrapper.querySelector('.quantity-input').setAttribute('max', '0');
        wrapper.dataset.availableQuantity = 0;

        // Remove any validation warnings
        wrapper.querySelector('.quantity-input').classList.remove('is-invalid');
        const existingWarning = wrapper.querySelector('.qty-warning');
        if (existingWarning) existingWarning.remove();
    }


    // Adds a new item row to the form.
    function addItem(initialData = {}) {
        const clone = template.content.cloneNode(true);
        const newElement = clone.querySelector('.sale-item-wrapper');

        const itemIndex = itemCount; // Use a consistent index for this new row

        // This determines the value to replace __PREFIX__ in name attributes
        // For existing items, it's `existing_sale_items[ID]`.
        // For new items, it's just the numeric index (e.g., `0`, `1`).
        const nameAttributeReplacementValue = initialData.id
            ? `existing_sale_items[${initialData.id}]`
            : itemIndex; 

        // CRITICAL: Replace '__PREFIX__' in 'name' attributes
        // Example: `name="new_sale_items[__PREFIX__][medicine_id]"` becomes `new_sale_items[0][medicine_id]`
        newElement.querySelectorAll('[name*="__PREFIX__"]').forEach(input => {
            input.name = input.name.replace('__PREFIX__', nameAttributeReplacementValue);
        });
        
        // Replace '__INDEX__' in 'id' and 'for' attributes with the numeric itemIndex
        // Example: `id="medicine___INDEX__"` becomes `id="medicine_0"`
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

    console.log("DEBUG: fetchBatches called for medicineId:", medicineId); // Added DEBUG
    batchSelect.empty().trigger('change').prop('disabled', true);
    resetItemDetails(wrapper);

    batchSelect.append(new Option('Loading batches...', '', false, false)).trigger('change');

    const url = batchBaseUrl.replace('PLACEHOLDER', medicineId); 
    console.log("DEBUG: Fetching batches from URL:", url); // Added DEBUG

    fetch(url)
        .then(res => {
            console.log("DEBUG: Batch fetch response status:", res.status); // Added DEBUG
            return res.ok ? res.json() : Promise.reject(res.statusText);
        })
        .then(batches => {
            console.log("DEBUG: Batches received and parsed:", batches); // Added DEBUG
            batchSelect.empty();

            if (batches.length === 0) {
                console.log("DEBUG: No batches found."); // Added DEBUG
                batchSelect.append(new Option('No stock available', '', true, true)).trigger('change');
                resetItemDetails(wrapper);
                calculateTotals();
                return;
            }

            let selectedOption = null;
            batches.forEach((batch, index) => {
                console.log("DEBUG: Processing batch:", batch); // Added DEBUG
                const expiry = batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: '2-digit' }) : 'N/A';
                const text = `${batch.batch_number} (Avl: ${batch.quantity}, Exp: ${expiry})`;
                const option = new Option(text, batch.batch_number);
                $(option).data('batch-data', batch);
                batchSelect.append(option);
                console.log("DEBUG: Appended option:", text, "with value:", batch.batch_number); // Added DEBUG

                if (selectedBatch && batch.batch_number === selectedBatch) {
                    selectedOption = option;
                    console.log("DEBUG: Matched selectedBatch:", selectedBatch); // Added DEBUG
                } else if (!selectedBatch && index === 0) {
                    selectedOption = option;
                    console.log("DEBUG: Auto-selecting first batch."); // Added DEBUG
                }
            });

            batchSelect.prop('disabled', false); // Enable batch select
            if (selectedOption) {
                console.log("DEBUG: Attempting to select and trigger batch:", selectedOption.value); // Added DEBUG
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
                console.log("DEBUG: No specific batch selected, just triggering change."); // Added DEBUG
                batchSelect.trigger('change');
            }
        })
        .catch(err => {
            console.error('Error fetching batches (CATCH BLOCK):', err); // Modified DEBUG
            batchSelect.empty().append(new Option('Error loading batches', '', true, true)).trigger('change');
            batchSelect.prop('disabled', true);
            resetItemDetails(wrapper);
            calculateTotals();
        });
}
    // Populates the input fields of a sale item row with batch-specific data.
    function populateBatchDetails(wrapper, data) {
        const quantityInput = wrapper.querySelector('.quantity-input');
        const freeQuantityInput = wrapper.querySelector('.free-quantity-input');
        const salePriceInput = wrapper.querySelector('.sale-price-input');
        const discountInput = wrapper.querySelector('.discount-percentage-input');

        salePriceInput.value = parseFloat(data.sale_price || 0).toFixed(2);
        wrapper.querySelector('.mrp-input').value = data.ptr || 'N/A';
        wrapper.querySelector('.gst-percent-input').value = `${data.gst_rate || 0}%`;
        
        wrapper.querySelector('.gst-rate-input').value = data.gst_rate || 0;
        wrapper.querySelector('.expiry-date-input').value = data.expiry_date ? new Date(data.expiry_date).toISOString().split('T')[0] : '';
        wrapper.querySelector('.mrp-input-hidden').value = data.ptr || '';

        wrapper.dataset.availableQuantity = data.quantity;
        
        quantityInput.disabled = false;
        quantityInput.setAttribute('max', data.quantity);
        if (parseInt(quantityInput.value, 10) === 0 && data.quantity > 0) {
            quantityInput.value = 1;
        }

        validateQuantity(quantityInput);
        calculateTotals();
    }

    // Populates an entire row with existing data (used when loading an edit form or old input).
    function populateRow(wrapper, data) {
        const medicineSelect = $(wrapper).find('.medicine-name-select');
        const batchSelect = $(wrapper).find('.batch-number-select');
        const quantityInput = wrapper.querySelector('.quantity-input');
        const freeQuantityInput = wrapper.querySelector('.free-quantity-input');
        const salePriceInput = wrapper.querySelector('.sale-price-input');
        const discountInput = wrapper.querySelector('.discount-percentage-input');
        const packInput = wrapper.querySelector('.pack-input'); 

        if (data.id) {
            const idInput = wrapper.querySelector('input[name*="[id]"]');
            if (idInput) idInput.value = data.id;
        }

        quantityInput.value = data.quantity || 1;
        freeQuantityInput.value = data.free_quantity || 0;
        salePriceInput.value = parseFloat(data.sale_price || 0).toFixed(2);
        discountInput.value = data.discount_percentage || 0;

        wrapper.querySelector('.gst-rate-input').value = data.gst_rate || 0;
        wrapper.querySelector('.expiry-date-input').value = data.expiry_date ? new Date(data.expiry_date).toISOString().split('T')[0] : '';
        wrapper.querySelector('.mrp-input-hidden').value = data.ptr || '';
        packInput.value = data.pack || ''; 
        
        wrapper.querySelector('.mrp-input').value = data.ptr || 'N/A';
        wrapper.querySelector('.gst-percent-input').value = `${data.gst_rate || 0}%`;
        
        quantityInput.disabled = false;
        salePriceInput.disabled = false;
        discountInput.disabled = false;

        wrapper.dataset.availableQuantity = data.available_quantity || data.quantity || 0;
        quantityInput.setAttribute('max', wrapper.dataset.availableQuantity);


        if (data.medicine_id && data.medicine_name) {
            // For existing items, data.medicine_name from blade is already formatted.
            const medicineOption = new Option(data.medicine_name, data.medicine_id, true, true);
            // Set data-pack on the option to align with `processResults` format if re-selected later
            $(medicineOption).data('pack', data.pack); 
            medicineSelect.append(medicineOption).trigger('change');
            
            // Directly fetch batches after medicine selection
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
        let grandSubtotal = 0;
        let grandTotalGst = 0;
        let hasInvalidQuantity = false;

        document.querySelectorAll('.sale-item-wrapper').forEach(wrapper => {
            const medicineSelect = $(wrapper).find('.medicine-name-select');
            const batchSelect = $(wrapper).find('.batch-number-select');
            const quantityInput = wrapper.querySelector('.quantity-input');

            const qty = parseFloat(quantityInput.value) || 0;
            const freeQty = parseFloat(wrapper.querySelector('.free-quantity-input').value) || 0;
            const price = parseFloat(wrapper.querySelector('.sale-price-input').value) || 0;
            const disc = parseFloat(wrapper.querySelector('.discount-percentage-input').value) || 0;
            const gstRate = parseFloat(wrapper.querySelector('.gst-rate-input').value) || 0;

            const lineTotalBeforeDiscount = qty * price;
            const afterDisc = lineTotalBeforeDiscount * (1 - disc / 100);
            const gstAmount = (afterDisc * gstRate) / 100;

            wrapper.querySelector('.gst-amount-input').value = gstAmount.toFixed(2);
            
            grandSubtotal += afterDisc;
            grandTotalGst += gstAmount;

            if (quantityInput.classList.contains('is-invalid')) {
                hasInvalidQuantity = true;
            } else if (!medicineSelect.val() || !batchSelect.val() || parseInt(quantityInput.value) < 1) {
                hasInvalidQuantity = true;
            }
        });

        const grandTotal = grandSubtotal + grandTotalGst;

        document.getElementById('subtotal').textContent = grandSubtotal.toFixed(2);
        document.getElementById('total_gst').textContent = grandTotalGst.toFixed(2);
        document.getElementById('grand_total').textContent = grandTotal.toFixed(2);

        const submitButton = saleForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = hasInvalidQuantity || document.querySelectorAll('.sale-item-wrapper').length === 0;
        }
    }

    // --- Initialization ---
    addItemBtn.addEventListener('click', () => addItem());

    if (window.oldInput && (window.oldInput.new_items || window.oldInput.existing_items)) {
        const existingItems = Object.entries(window.oldInput.existing_items || {});
        const newItems = Object.entries(window.oldInput.new_items || {});

        existingItems.forEach(([id, data]) => addItem({ ...data, id }));
        newItems.forEach(([index, data]) => addItem(data));
    } else if (document.querySelectorAll('.sale-item-wrapper').length === 0) {
        addItem();
    } else {
        // Handle existing items on page load (edit form)
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
                pack: wrapper.dataset.pack
            };
            initializeRow(wrapper);
            populateRow(wrapper, data);
        });
        calculateTotals();
    }

    // Add a submit event listener to the form to perform final validation
    saleForm.addEventListener('submit', function(event) {
        let isValid = true;
        document.querySelectorAll('.sale-item-wrapper').forEach(wrapper => {
            const medicineSelect = $(wrapper).find('.medicine-name-select');
            const batchSelect = $(wrapper).find('.batch-number-select');
            const quantityInput = wrapper.querySelector('.quantity-input');
            
            validateQuantity(quantityInput);

            if (!medicineSelect.val() || !batchSelect.val() || quantityInput.classList.contains('is-invalid') || parseInt(quantityInput.value) < 1) {
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

    calculateTotals(); 
});