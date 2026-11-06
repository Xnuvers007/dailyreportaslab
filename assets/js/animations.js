/**
 * ASLAB REPORT - ANIMATION CONTROLLER
 * Menangani CountUp Stats, Toast, & Micro-Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Smooth CountUp Animation untuk Angka Inventaris
    const counters = document.querySelectorAll('.counter-value');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'), 10);
        if (isNaN(target)) return;

        let current = 0;
        const duration = 900;
        const steps = 35;
        const increment = target / steps;
        const stepTime = duration / steps;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target;
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current);
            }
        }, stepTime);
    });

    // 2. Toast Notification Global
    window.showToast = function (message, type = 'success') {
        let toastEl = document.getElementById('liveToast');
        if (!toastEl) {
            const toastHtml = `
            <div class="toast-container">
                <div id="liveToast" class="toast align-items-center text-bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body" id="toastMessage">
                            <i class="fa-solid fa-circle-check text-success me-2"></i> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', toastHtml);
            toastEl = document.getElementById('liveToast');
        } else {
            const iconClass = type === 'success' ? 'fa-circle-check text-success' : 'fa-circle-info text-info';
            document.getElementById('toastMessage').innerHTML = `<i class="fa-solid ${iconClass} me-2"></i> ${message}`;
        }

        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    };
});
