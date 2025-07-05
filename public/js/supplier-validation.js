document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action*="suppliers"]');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        const gst = document.getElementById('gst')?.value.trim();
        const dln = document.getElementById('dln')?.value.trim();
        const phone = document.getElementById('phone_number')?.value.trim();

        if (!gst) {
            alert('GST Number is required.');
            e.preventDefault();
            return false;
        }

        if (!dln) {
            alert('Drug License Number (DLN) is required.');
            e.preventDefault();
            return false;
        }

        if (phone && !/^[6-9]\d{9}$/.test(phone)) {
            alert('Please enter a valid 10-digit Indian phone number.');
            e.preventDefault();
            return false;
        }
    });
});
