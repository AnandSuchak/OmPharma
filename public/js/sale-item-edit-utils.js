// public/js/sale-item-edit-utils.js

// Helper to fetch original sold quantity stored in data attribute
function getOriginalSoldQty(wrapper) {
    return parseFloat(wrapper.dataset.originalSoldQty) || 0;
}
