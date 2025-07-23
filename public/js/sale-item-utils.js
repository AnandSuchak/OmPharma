// public/js/sale-item-utils.js

// --- Constants ---
const EXTRA_DISCOUNT_PERCENTAGE = 3;

// --- Reset item details for a specific row ---
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
    const packInput = wrapper.querySelector('.pack-input');
    const availableQuantityDisplay = wrapper.querySelector('.available-quantity');
    const extraDiscountCheckbox = wrapper.querySelector('.extra-discount-checkbox');
    const appliedExtraDiscountInput = wrapper.querySelector('.applied-extra-discount-percentage');

    if (salePriceInput) salePriceInput.value = parseFloat(0).toFixed(2);
    if (mrpInputDisplay) mrpInputDisplay.value = 'N/A';
    if (gstPercentDisplay) gstPercentDisplay.value = '0%';
    if (gstAmountDisplay) gstAmountDisplay.value = parseFloat(0).toFixed(2);
    if (discountInput) discountInput.value = 0;

    if (gstRateInputHidden) gstRateInputHidden.value = 0;
    if (expiryDateInput) expiryDateInput.value = '';
    if (ptrInputHidden) ptrInputHidden.value = '';
    if (packInput) packInput.value = '';

    if (extraDiscountCheckbox) extraDiscountCheckbox.checked = false;
    if (appliedExtraDiscountInput) appliedExtraDiscountInput.value = parseFloat(0).toFixed(2);

    if (resetQuantity) {
        if (quantityInput) {
            quantityInput.value = 0;
            quantityInput.disabled = true;
            quantityInput.setAttribute('max', '0');
            quantityInput.classList.remove('is-invalid');
        }
        if (freeQuantityInput) freeQuantityInput.value = 0;
        wrapper.dataset.availableQuantity = 0;
        if (availableQuantityDisplay) availableQuantityDisplay.textContent = '';
        const existingWarning = wrapper.querySelector('.qty-warning');
        if (existingWarning) existingWarning.remove();
    } else {
        if (quantityInput) quantityInput.disabled = true;
    }

    if (salePriceInput) salePriceInput.disabled = true;
    if (discountInput) discountInput.disabled = true;
}

// --- Validates entered quantity (supports edit mode) ---
function validateQuantity(quantityInput, originalSold = 0) {
    if (!quantityInput) return;

    const wrapper = quantityInput.closest('.sale-item-wrapper');
    const available = parseFloat(wrapper.dataset.availableQuantity);
    const requested = parseFloat(quantityInput.value);
    const existingWarning = wrapper.querySelector('.qty-warning');

    if (existingWarning) existingWarning.remove();

    if (isNaN(requested) || requested < 0) return;

    // In edit mode: allowed = available + originally sold
    const maxAllowed = available + (parseFloat(originalSold) || 0);

    if (!isNaN(maxAllowed) && requested > maxAllowed) {
        quantityInput.classList.add('is-invalid');
        const warning = document.createElement('div');
        warning.className = 'qty-warning text-danger small mt-1';
        warning.textContent = `Stock limit for this batch is ${maxAllowed}.`;
        quantityInput.parentNode.appendChild(warning);
    } else {
        quantityInput.classList.remove('is-invalid');
    }

    calculateTotals();
}

// --- Calculates and updates subtotal, GST, and grand total ---
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

        const gstAmount = lineTotal * (gstRate / 100);

        subtotal += lineTotal;
        totalGst += gstAmount;

        $row.find('.gst-amount-input').val(gstAmount.toFixed(2));
    });

    const grandTotal = subtotal + totalGst;

    $('#subtotal').text(subtotal.toFixed(2));
    $('#total_gst').text(totalGst.toFixed(2));
    $('#grand_total').text(grandTotal.toFixed(2));
}
