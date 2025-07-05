document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        const gst = document.getElementById('gst_number')?.value.trim();
        const pan = document.getElementById('pan_number')?.value.trim();
        const contact = document.getElementById('contact_number')?.value.trim();

        if (!gst && !pan) {
            alert('Either GST Number or PAN Number is required.');
            e.preventDefault();
            return false;
        }

        if (contact && !/^[6-9]\d{9}$/.test(contact)) {
            alert('Please enter a valid 10-digit Indian mobile number.');
            e.preventDefault();
            return false;
        }
    });
});
