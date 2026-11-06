/**
 * ASLAB REPORT - CORE APP CONTROLLER
 * Menangani Kalkulasi Real-time Form, Image Preview, & Copy WhatsApp
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Kalkulasi Otomatis Stok Proyektor
    const proyektorTersedia = document.getElementById('proyektor_tersedia');
    const proyektorRusak = document.getElementById('proyektor_rusak');
    const totalProyektorDisplay = document.getElementById('total_proyektor_display');

    function hitungProyektor() {
        if (proyektorTersedia && proyektorRusak && totalProyektorDisplay) {
            const tersedia = parseInt(proyektorTersedia.value, 10) || 0;
            const rusak = parseInt(proyektorRusak.value, 10) || 0;
            totalProyektorDisplay.textContent = (tersedia + rusak) + ' Unit';
        }
    }

    if (proyektorTersedia && proyektorRusak) {
        proyektorTersedia.addEventListener('input', hitungProyektor);
        proyektorRusak.addEventListener('input', hitungProyektor);
        hitungProyektor();
    }

    // 2. Kalkulasi Otomatis Stok Kabel Roll
    const kabelTersedia = document.getElementById('kabel_roll_tersedia');
    const kabelRusak = document.getElementById('kabel_roll_rusak');
    const totalKabelDisplay = document.getElementById('total_kabel_display');

    function hitungKabel() {
        if (kabelTersedia && kabelRusak && totalKabelDisplay) {
            const tersedia = parseInt(kabelTersedia.value, 10) || 0;
            const rusak = parseInt(kabelRusak.value, 10) || 0;
            totalKabelDisplay.textContent = (tersedia + rusak) + ' Unit';
        }
    }

    if (kabelTersedia && kabelRusak) {
        kabelTersedia.addEventListener('input', hitungKabel);
        kabelRusak.addEventListener('input', hitungKabel);
        hitungKabel();
    }

    // 3. Image Upload Preview
    const fotoInput = document.getElementById('foto_dokumentasi');
    const previewImg = document.getElementById('preview_img');
    const previewContainer = document.getElementById('image_preview_box');

    if (fotoInput && previewImg && previewContainer) {
        fotoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                    alert('Hanya format foto (JPG, PNG, WEBP) yang diperbolehkan!');
                    this.value = '';
                    previewContainer.classList.add('d-none');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran foto maksimal 5 MB!');
                    this.value = '';
                    previewContainer.classList.add('d-none');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('d-none');
            }
        });
    }

    // 4. Salin Format WhatsApp
    window.copyToWhatsApp = function (textToCopy, buttonElement) {
        function triggerSuccess() {
            if (window.showToast) {
                window.showToast("Format laporan WhatsApp berhasil disalin ke clipboard!");
            } else {
                alert("Format laporan WhatsApp berhasil disalin!");
            }

            if (buttonElement) {
                const originalHtml = buttonElement.innerHTML;
                buttonElement.innerHTML = '<i class="fa-solid fa-check me-1"></i> Tersalin!';
                buttonElement.classList.add('btn-success');
                setTimeout(() => {
                    buttonElement.innerHTML = originalHtml;
                    buttonElement.classList.remove('btn-success');
                }, 2000);
            }
        }

        if (!navigator.clipboard) {
            const textArea = document.createElement("textarea");
            textArea.value = textToCopy;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                triggerSuccess();
            } catch (err) {
                alert("Gagal menyalin teks.");
            }
            document.body.removeChild(textArea);
            return;
        }

        navigator.clipboard.writeText(textToCopy).then(triggerSuccess).catch(function (err) {
            console.error('Clipboard error: ', err);
            alert("Gagal menyalin format WhatsApp.");
        });
    };
});
