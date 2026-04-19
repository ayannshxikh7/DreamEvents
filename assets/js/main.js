document.querySelectorAll('.alert').forEach((alert) => {
    setTimeout(() => {
        alert.classList.add('fade');
        setTimeout(() => alert.remove(), 250);
    }, 3500);
});

document.querySelectorAll('.js-processing-form').forEach((form) => {
    form.addEventListener('submit', () => {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (!submitBtn) return;
        submitBtn.innerHTML = submitBtn.dataset.processingText || 'Processing...';
        submitBtn.disabled = true;
    });
});

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.page-enter').forEach((el) => el.classList.add('loaded'));
});
