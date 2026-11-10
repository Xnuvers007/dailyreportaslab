/**
 * ASLAB REPORT - PROGRESSIVE WEB APP (PWA) CLIENT CONTROLLER
 * - Pendaftaran Service Worker (sw.js)
 * - In-App Custom Install Prompt (Banner pasang aplikasi responsif)
 * - Deteksi Status Online / Offline secara real-time
 * - Panduan Instalasi iOS Safari (Add to Home Screen)
 */

(function () {
    'use strict';

    // 1. Registrasi Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            // Hindari Service Worker pada iframe atau preview terbatas
            if (window.self !== window.top) return;

            // Daftarkan via sw.php gateway untuk kebal dari firewall/proxy hosting
            navigator.serviceWorker.register('sw.php', { scope: './' })
                .catch(function () {
                    return navigator.serviceWorker.register('sw.js', { scope: './' });
                })
                .then(function (registration) {
                    // Update check otomatis
                    registration.onupdatefound = function () {
                        const installingWorker = registration.installing;
                        if (installingWorker) {
                            installingWorker.onstatechange = function () {
                                if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    showPwaToast('Versi baru tersedia! Muat ulang halaman untuk memperbarui.', 'info');
                                }
                            };
                        }
                    };
                })
                .catch(function (error) {
                    console.warn('[PWA] Service Worker registration failed:', error);
                });
        });
    }

    // 2. Deteksi Mode Standalone (Sudah Terpasang)
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                         window.navigator.standalone === true;
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    // 3. Custom Install Prompt (Android, Chrome, Edge, iOS)
    let deferredPrompt = null;

    // Inisialisasi tombol pasang di navbar sedini mungkin
    if (!isStandalone) {
        window.addEventListener('DOMContentLoaded', function () {
            const navBtn = document.getElementById('nav-install-btn');
            if (navBtn) {
                navBtn.classList.remove('d-none');
                navBtn.classList.add('d-inline-flex');
                navBtn.addEventListener('click', function () {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.then(function (choice) {
                            if (choice && choice.outcome === 'accepted') {
                                navBtn.classList.add('d-none');
                                removePwaInstallBanner();
                            }
                            deferredPrompt = null;
                        });
                    } else if (isIOS) {
                        showPwaToast('📲 Di iPhone/iPad: Ketuk tombol Bagikan (Share 📤) di Safari, lalu pilih "Tambahkan ke Layar Utama" ➕', 'info');
                    } else {
                        showPwaToast('💡 Untuk memasang aplikasi: Buka menu browser (titik tiga ⋮ di kanan atas) lalu klik "Pasang AslabReport" / "Install App" / "Tambahkan ke Layar Utama"', 'info');
                    }
                });
            }
        });
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        // Cegah mini-infobar default browser agar bisa pakai UI custom yang estetik
        e.preventDefault();
        deferredPrompt = e;

        // Pastikan tombol di navbar tampil aktif
        const navBtn = document.getElementById('nav-install-btn');
        if (navBtn && !isStandalone) {
            navBtn.classList.remove('d-none');
            navBtn.classList.add('d-inline-flex');
        }

        // Jangan tampilkan banner jika sudah di-dismiss dalam 7 hari terakhir
        const dismissedAt = localStorage.getItem('aslab_pwa_dismissed_at');
        if (dismissedAt) {
            const daysPassed = (Date.now() - parseInt(dismissedAt, 10)) / (1000 * 60 * 60 * 24);
            if (daysPassed < 7) {
                return;
            }
        }

        if (!isStandalone) {
            createPwaInstallBanner();
        }
    });

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        removePwaInstallBanner();
        showPwaToast('🎉 AslabReport berhasil dipasang di layar utama Anda!', 'success');
    });

    function createPwaInstallBanner() {
        if (document.getElementById('pwa-install-banner')) return;

        const banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.className = 'pwa-install-banner';
        banner.innerHTML = `
            <div class="pwa-banner-card">
                <div class="pwa-banner-content">
                    <img src="assets/img/icons/icon-96.png" alt="Logo AslabReport" class="pwa-banner-logo">
                    <div class="pwa-banner-text">
                        <div class="pwa-banner-title">Pasang Aplikasi AslabReport</div>
                        <div class="pwa-banner-desc">Akses cepat & stabil langsung dari layar utama HP Anda tanpa perlu buka browser</div>
                    </div>
                </div>
                <div class="pwa-banner-actions">
                    <button id="pwa-install-confirm-btn" class="btn-pwa-install">
                        <i class="fa-solid fa-download me-1"></i> Pasang
                    </button>
                    <button id="pwa-install-dismiss-btn" class="btn-pwa-dismiss" title="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(banner);

        // Pasang Event Listeners
        const confirmBtn = document.getElementById('pwa-install-confirm-btn');
        const dismissBtn = document.getElementById('pwa-install-dismiss-btn');

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(function (choiceResult) {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('[PWA] Pengguna menyetujui instalasi.');
                        }
                        deferredPrompt = null;
                        removePwaInstallBanner();
                    });
                }
            });
        }

        if (dismissBtn) {
            dismissBtn.addEventListener('click', function () {
                localStorage.setItem('aslab_pwa_dismissed_at', Date.now().toString());
                removePwaInstallBanner();
            });
        }
    }

    function removePwaInstallBanner() {
        const banner = document.getElementById('pwa-install-banner');
        if (banner) {
            banner.style.animation = 'pwaSlideDown 0.3s ease-in forwards';
            setTimeout(function () {
                banner.remove();
            }, 300);
        }
    }

    // 4. Deteksi Koneksi Real-Time (Online / Offline Indicator)
    function setupNetworkListeners() {
        window.addEventListener('offline', function () {
            showNetworkToast(false);
        });

        window.addEventListener('online', function () {
            showNetworkToast(true);
        });
    }

    function showNetworkToast(isOnline) {
        let toast = document.getElementById('pwa-network-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'pwa-network-toast';
            document.body.appendChild(toast);
        }

        if (!isOnline) {
            toast.className = 'pwa-network-toast offline';
            toast.innerHTML = `
                <i class="fa-solid fa-wifi-slash me-2"></i>
                <span><strong>Mode Offline:</strong> Koneksi terputus. Menampilkan data lokal.</span>
            `;
            toast.style.display = 'flex';
        } else {
            toast.className = 'pwa-network-toast online';
            toast.innerHTML = `
                <i class="fa-solid fa-circle-check me-2"></i>
                <span><strong>Kembali Online:</strong> Koneksi internet tersambung kembali.</span>
            `;
            toast.style.display = 'flex';
            setTimeout(function () {
                if (toast) toast.style.display = 'none';
            }, 3500);
        }
    }

    // 5. Toast Notifikasi PWA Ringan
    function showPwaToast(message, type) {
        let toast = document.getElementById('pwa-simple-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'pwa-simple-toast';
            document.body.appendChild(toast);
        }

        toast.className = 'pwa-simple-toast ' + (type || 'info');
        toast.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-info'}"></i>
                <span>${message}</span>
            </div>
        `;
        toast.style.display = 'block';

        setTimeout(function () {
            if (toast) toast.style.display = 'none';
        }, 4000);
    }

    // Inisialisasi Deteksi Jaringan
    setupNetworkListeners();

})();
