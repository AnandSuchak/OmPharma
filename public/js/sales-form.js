// public/js/sales-form.js

document.addEventListener('DOMContentLoaded', function () {
    const saleItemsContainer = document.getElementById('sale_items_container');
    const addItemBtn = document.getElementById('add_new_item');
    const extraDiscountInput = document.getElementById('extra_discount');
    const subtotalElem = document.getElementById('subtotal');
    const gstTotalElem = document.getElementById('gst_total');
    const grandTotalElem = document.getElementById('grand_total');

    function updateTotals() {
        let subtotal = 0;
        let gstTotal = 0;

        saleItemsContainer.querySelectorAll('.sale-item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.quantity').value) || 0;
            const price = parseFloat(row.querySelector('.sale_price').value) || 0;
            const discount = parseFloat(row.querySelector('.discount_percentage').value) || 0;
            const gstRate = parseFloat(row.querySelector('.gst_rate').value) || 0;

            const lineTotal = qty * price;
            const afterDiscount = lineTotal * (1 - discount / 100);
            const gstAmount = afterDiscount * (gstRate / 100);
            const total = afterDiscount + gstAmount;

            row.querySelector('.gst_rupees').value = gstAmount.toFixed(2);
            row.querySelector('.row_total').value = total.toFixed(2);

            subtotal += afterDiscount;
            gstTotal += gstAmount;
        });

        const extraDiscount = parseFloat(extraDiscountInput.value) || 0;
        const grandTotal = subtotal + gstTotal - extraDiscount;

        subtotalElem.value = subtotal.toFixed(2);
        gstTotalElem.value = gstTotal.toFixed(2);
        grandTotalElem.value = grandTotal.toFixed(2);
    }

    function bindRowEvents(row) {
        row.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', updateTotals);
        });

        row.querySelector('.remove_row').addEventListener('click', () => {
            row.remove();
            updateTotals();
        });

        // Select2 init and batch logic
        $(row.querySelector('.medicine_id')).select2({
            placeholder: 'Select medicine',
            ajax: {
                url: '/api/medicines/search-names',
                dataType: 'json',
                delay: 250,
                data: params => ({ search: params.term }),
                processResults: data => ({ results: data.map(m => ({
                    id: m.id,
                    text: `${m.name} - ${m.pack}`,
                    pack: m.pack
                })) })
            }
        }).on('select2:select', function (e) {
            const medicineId = e.params.data.id;
            const pack = e.params.data.pack;
            row.querySelector('.pack').value = pack;

            fetch(`/api/medicines/${medicineId}/batches`)
                .then(res => res.json())
                .then(batches => {
                    const batchSelect = row.querySelector('.batch_number');
                    batchSelect.innerHTML = '';

                    if (batches.length > 0) {
                        batches.forEach(batch => {
                            const opt = document.createElement('option');
                            opt.value = batch.batch_number;
                            opt.text = `${batch.batch_number} (Exp: ${batch.expiry_date})`;
                            opt.dataset.salePrice = batch.sale_price;
                            opt.dataset.gstRate = batch.gst_rate;
                            batchSelect.appendChild(opt);
                        });
                        batchSelect.selectedIndex = 0;
                        batchSelect.dispatchEvent(new Event('change'));
                    } else {
                        // fallback
                        fetch(`/api/medicines/${medicineId}/details`)
                            .then(res => res.json())
                            .then(detail => {
                                row.querySelector('.expiry_date').value = '';
                                row.querySelector('.sale_price').value = detail.sale_price || 0;
                                row.querySelector('.gst_rate').value = detail.gst_rate || 0;
                                row.querySelector('.gst_rupees').value = 0;
                                row.querySelector('.row_total').value = 0;
                                updateTotals();
                            });
                    }
                });
        });

        row.querySelector('.batch_number').addEventListener('change', function () {
            const selected = this.selectedOptions[0];
            const price = parseFloat(selected.dataset.salePrice || 0);
            const gst = parseFloat(selected.dataset.gstRate || 0);

            row.querySelector('.sale_price').value = price;
            row.querySelector('.gst_rate').value = gst;

            updateTotals();
        });
    }

    addItemBtn.addEventListener('click', function () {
        const index = saleItemsContainer.querySelectorAll('.sale-item-row').length;
        fetch(`/sale-item-row-template?index=${index}`)
            .then(res => res.text())
            .then(html => {
                const div = document.createElement('div');
                div.innerHTML = html.trim();
                const row = div.firstChild;
                saleItemsContainer.appendChild(row);
                bindRowEvents(row);
                updateTotals();
            });
    });

    extraDiscountInput.addEventListener('input', updateTotals);

    saleItemsContainer.querySelectorAll('.sale-item-row').forEach(bindRowEvents);
    updateTotals();
});
