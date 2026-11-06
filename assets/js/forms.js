/**
 * ASLAB REPORT - FORMS & EXPORT CONTROLLER
 * Menangani Number Steppers, Drag-and-Drop Image Upload, Shift Pill Selector, & Export Range
 */

document.addEventListener('DOMContentLoaded', function () {

    // 1. Interactive Number Steppers (+ / - Buttons)
    document.querySelectorAll('.number-stepper').forEach(stepper => {
        const input = stepper.querySelector('input[type="number"]');
        const btnMinus = stepper.querySelector('.btn-minus');
        const btnPlus = stepper.querySelector('.btn-plus');

        if (!input || !btnMinus || !btnPlus) return;

        btnMinus.addEventListener('click', () => {
            let val = parseInt(input.value, 10) || 0;
            const min = input.hasAttribute('min') ? parseInt(input.getAttribute('min'), 10) : 0;
            if (val > min) {
                input.value = val - 1;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });

        btnPlus.addEventListener('click', () => {
            let val = parseInt(input.value, 10) || 0;
            const max = input.hasAttribute('max') ? parseInt(input.getAttribute('max'), 10) : 9999;
            if (val < max) {
                input.value = val + 1;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });

    // 2. Drag & Drop File Upload Zone
    const dropzone = document.getElementById('upload_dropzone');
    const fileInput = document.getElementById('foto_dokumentasi');
    const previewContainer = document.getElementById('image_preview_box');
    const previewImg = document.getElementById('preview_img');
    const removeBtn = document.getElementById('btn_remove_preview');

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            }, false);
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelection(files[0]);
            }
        });

        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                handleFileSelection(this.files[0]);
            }
        });

        function handleFileSelection(file) {
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                alert('Hanya format foto JPG, PNG, atau WEBP yang diperbolehkan!');
                fileInput.value = '';
                if (previewContainer) previewContainer.classList.add('d-none');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Ukuran file maksimal 5 MB!');
                fileInput.value = '';
                if (previewContainer) previewContainer.classList.add('d-none');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                if (previewImg && previewContainer) {
                    previewImg.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                }
            };
            reader.readAsDataURL(file);
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.value = '';
                if (previewContainer) previewContainer.classList.add('d-none');
            });
        }
    }

    // 3. Modal Stacking & Backdrop Fix (Pindahkan semua modal ke <body>)
    document.querySelectorAll('.modal').forEach(modalEl => {
        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }
    });

    // 4. Export Modal Range Selector & Target Controller
    const rangeTypeSelect = document.getElementById('export_range_type');
    const customRangeSection = document.getElementById('export_custom_range_section');
    const exportForm = document.querySelector('#exportModal form');

    if (rangeTypeSelect && customRangeSection) {
        function toggleCustomRange() {
            if (rangeTypeSelect.value === 'custom') {
                customRangeSection.classList.remove('d-none');
            } else {
                customRangeSection.classList.add('d-none');
            }
        }
        rangeTypeSelect.addEventListener('change', toggleCustomRange);
        toggleCustomRange();
    }

    if (exportForm) {
        exportForm.addEventListener('submit', function () {
            const formatRadio = exportForm.querySelector('input[name="format"]:checked');
            // Jika Excel: langsung unduh di tab ini (_self). Jika PDF: buka tab baru (_blank)
            if (formatRadio && formatRadio.value === 'excel') {
                exportForm.target = '_self';
            } else {
                exportForm.target = '_blank';
            }

            // Tutup modal secara otomatis agar layar tidak tetap gelap
            const modalEl = document.getElementById('exportModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    setTimeout(() => modalInstance.hide(), 400);
                }
            }
        });
    }

    // 4. Live Sticky Summary Bar Updating (buat-laporan.php)
    const liveProyektorSiap = document.getElementById('live_summary_proyektor');
    const liveKabelSiap = document.getElementById('live_summary_kabel');
    const liveShift = document.getElementById('live_summary_shift');

    function updateLiveSummary() {
        const inpProyektor = document.getElementById('proyektor_tersedia');
        const inpKabel = document.getElementById('kabel_roll_tersedia');
        const checkedShift = document.querySelector('input[name="shift"]:checked');

        if (liveProyektorSiap && inpProyektor) {
            liveProyektorSiap.textContent = (parseInt(inpProyektor.value, 10) || 0) + ' Unit Siap';
        }
        if (liveKabelSiap && inpKabel) {
            liveKabelSiap.textContent = (parseInt(inpKabel.value, 10) || 0) + ' Unit Siap';
        }
        if (liveShift && checkedShift) {
            liveShift.textContent = 'Shift ' + checkedShift.value;
        }
    }

    document.querySelectorAll('#proyektor_tersedia, #kabel_roll_tersedia, input[name="shift"]').forEach(el => {
        el.addEventListener('input', updateLiveSummary);
        el.addEventListener('change', updateLiveSummary);
    });
    updateLiveSummary();

});

