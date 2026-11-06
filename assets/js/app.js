// ========================================================
// THEME MANAGER: DARK MODE & LIGHT MODE
// ========================================================
(function initTheme() {
    const savedTheme = localStorage.getItem('aslab_theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initialTheme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-bs-theme', initialTheme);
})();

document.addEventListener('DOMContentLoaded', function () {
    // ----------------------------------------------------
    // 1. Theme Toggle Button Handler
    // ----------------------------------------------------
    const themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');
    
    function updateThemeIcons(currentTheme) {
        document.querySelectorAll('.theme-icon').forEach(icon => {
            if (currentTheme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });
    }

    const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
    updateThemeIcons(currentTheme);

    themeToggleBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const nowTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const newTheme = nowTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('aslab_theme', newTheme);
            updateThemeIcons(newTheme);

            // Trigger theme event for Three.js background
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
        });
    });

    // ----------------------------------------------------
    // 2. Interactive Three.js 3D Constellation Background
    // ----------------------------------------------------
    const canvas = document.getElementById('bg-canvas');
    if (canvas && typeof THREE !== 'undefined') {
        initThreeCanvas(canvas);
    }

    function initThreeCanvas(canvasEl) {
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 1, 1000);
        camera.position.z = 400;

        const renderer = new THREE.WebGLRenderer({ canvas: canvasEl, alpha: true, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        let particleColor = isDark ? 0x38bdf8 : 0x2563eb;
        let lineColor = isDark ? 0x1e3a8a : 0x93c5fd;

        const particleCount = window.innerWidth < 768 ? 60 : 120;
        const particlesData = [];
        const positions = new Float32Array(particleCount * 3);
        const particleGeometry = new THREE.BufferGeometry();

        const range = 500;
        for (let i = 0; i < particleCount; i++) {
            const x = Math.random() * range - range / 2;
            const y = Math.random() * range - range / 2;
            const z = Math.random() * range - range / 2;

            positions[i * 3] = x;
            positions[i * 3 + 1] = y;
            positions[i * 3 + 2] = z;

            particlesData.push({
                velocity: new THREE.Vector3(
                    (-1 + Math.random() * 2) * 0.4,
                    (-1 + Math.random() * 2) * 0.4,
                    (-1 + Math.random() * 2) * 0.4
                )
            });
        }

        particleGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        const particleMaterial = new THREE.PointsMaterial({
            color: particleColor,
            size: 3.5,
            transparent: true,
            opacity: isDark ? 0.75 : 0.6
        });

        const pointCloud = new THREE.Points(particleGeometry, particleMaterial);
        scene.add(pointCloud);

        // Lines Geometry
        const maxLines = particleCount * particleCount;
        const linePositions = new Float32Array(maxLines * 3);
        const lineColors = new Float32Array(maxLines * 3);

        const linesGeometry = new THREE.BufferGeometry();
        linesGeometry.setAttribute('position', new THREE.BufferAttribute(linePositions, 3));
        linesGeometry.setAttribute('color', new THREE.BufferAttribute(lineColors, 3));

        const linesMaterial = new THREE.LineBasicMaterial({
            vertexColors: true,
            transparent: true,
            opacity: isDark ? 0.4 : 0.25
        });

        const linesMesh = new THREE.LineSegments(linesGeometry, linesMaterial);
        scene.add(linesMesh);

        // Mouse Parallax
        let mouseX = 0, mouseY = 0;
        window.addEventListener('mousemove', function (e) {
            mouseX = (e.clientX - window.innerWidth / 2) * 0.05;
            mouseY = (e.clientY - window.innerHeight / 2) * 0.05;
        });

        // Theme Change Handler for Three.js
        window.addEventListener('themeChanged', function (e) {
            const dark = e.detail.theme === 'dark';
            particleMaterial.color.setHex(dark ? 0x38bdf8 : 0x2563eb);
            particleMaterial.opacity = dark ? 0.75 : 0.6;
            linesMaterial.opacity = dark ? 0.4 : 0.25;
        });

        // Animation Loop
        function animate() {
            requestAnimationFrame(animate);

            let vertexpos = 0;
            let colorpos = 0;
            let numConnected = 0;

            const posArray = particleGeometry.attributes.position.array;

            for (let i = 0; i < particleCount; i++) {
                const data = particlesData[i];

                posArray[i * 3] += data.velocity.x;
                posArray[i * 3 + 1] += data.velocity.y;
                posArray[i * 3 + 2] += data.velocity.z;

                if (posArray[i * 3] < -range / 2 || posArray[i * 3] > range / 2) data.velocity.x = -data.velocity.x;
                if (posArray[i * 3 + 1] < -range / 2 || posArray[i * 3 + 1] > range / 2) data.velocity.y = -data.velocity.y;
                if (posArray[i * 3 + 2] < -range / 2 || posArray[i * 3 + 2] > range / 2) data.velocity.z = -data.velocity.z;

                // Connect nearby particles
                for (let j = i + 1; j < particleCount; j++) {
                    const dx = posArray[i * 3] - posArray[j * 3];
                    const dy = posArray[i * 3 + 1] - posArray[j * 3 + 1];
                    const dz = posArray[i * 3 + 2] - posArray[j * 3 + 2];
                    const dist = Math.sqrt(dx * dx + dy * dy + dz * dz);

                    if (dist < 110) {
                        const alpha = 1.0 - dist / 110;

                        linePositions[vertexpos++] = posArray[i * 3];
                        linePositions[vertexpos++] = posArray[i * 3 + 1];
                        linePositions[vertexpos++] = posArray[i * 3 + 2];

                        linePositions[vertexpos++] = posArray[j * 3];
                        linePositions[vertexpos++] = posArray[j * 3 + 1];
                        linePositions[vertexpos++] = posArray[j * 3 + 2];

                        lineColors[colorpos++] = 0.23;
                        lineColors[colorpos++] = 0.51;
                        lineColors[colorpos++] = 0.96;

                        lineColors[colorpos++] = 0.23;
                        lineColors[colorpos++] = 0.51;
                        lineColors[colorpos++] = 0.96;

                        numConnected++;
                    }
                }
            }

            linesGeometry.setDrawRange(0, numConnected * 2);
            linesGeometry.attributes.position.needsUpdate = true;
            linesGeometry.attributes.color.needsUpdate = true;
            particleGeometry.attributes.position.needsUpdate = true;

            camera.position.x += (mouseX - camera.position.x) * 0.05;
            camera.position.y += (-mouseY - camera.position.y) * 0.05;
            camera.lookAt(scene.position);

            pointCloud.rotation.y += 0.001;
            linesMesh.rotation.y += 0.001;

            renderer.render(scene, camera);
        }

        animate();

        window.addEventListener('resize', function () {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    }

    // ----------------------------------------------------
    // 3. Smooth Counter Animation (CountUp for Stats)
    // ----------------------------------------------------
    const counterElements = document.querySelectorAll('.counter-value');
    counterElements.forEach(el => {
        const target = parseInt(el.getAttribute('data-target'), 10);
        if (isNaN(target)) return;

        let current = 0;
        const duration = 800; // ms
        const steps = 30;
        const increment = target / steps;
        const stepTime = duration / steps;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                el.textContent = target;
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(current);
            }
        }, stepTime);
    });

    // ----------------------------------------------------
    // 4. Kalkulasi Otomatis Stok Proyektor & Kabel Roll
    // ----------------------------------------------------
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

    // ----------------------------------------------------
    // 5. Image Upload Preview
    // ----------------------------------------------------
    const fotoInput = document.getElementById('foto_dokumentasi');
    const previewImg = document.getElementById('preview_img');
    const previewContainer = document.getElementById('image_preview_box');

    if (fotoInput && previewImg && previewContainer) {
        fotoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                    alert('Hanya file foto (JPG, PNG, WEBP) yang diperbolehkan!');
                    this.value = '';
                    previewContainer.classList.add('d-none');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file maksimal 5 MB!');
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

    // ----------------------------------------------------
    // 6. Salin Format WhatsApp dengan Animasi Toast
    // ----------------------------------------------------
    window.copyToWhatsApp = function (textToCopy, buttonElement) {
        if (!navigator.clipboard) {
            const textArea = document.createElement("textarea");
            textArea.value = textToCopy;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showToast("Format laporan berhasil disalin!");
            } catch (err) {
                alert("Gagal menyalin teks.");
            }
            document.body.removeChild(textArea);
            return;
        }

        navigator.clipboard.writeText(textToCopy).then(function () {
            showToast("Format laporan berhasil disalin ke clipboard!");
            if (buttonElement) {
                const originalHtml = buttonElement.innerHTML;
                buttonElement.innerHTML = '<i class="fa-solid fa-check me-1"></i> Tersalin!';
                buttonElement.classList.replace('btn-outline-success', 'btn-success');
                setTimeout(() => {
                    buttonElement.innerHTML = originalHtml;
                    buttonElement.classList.replace('btn-success', 'btn-outline-success');
                }, 2000);
            }
        }).catch(function (err) {
            console.error('Error saat menyalin: ', err);
            alert("Gagal menyalin format WhatsApp.");
        });
    };

    function showToast(message) {
        let toastEl = document.getElementById('liveToast');
        if (!toastEl) {
            const toastHtml = `
            <div class="toast-container">
                <div id="liveToast" class="toast align-items-center text-bg-dark border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
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
            document.getElementById('toastMessage').innerHTML = `<i class="fa-solid fa-circle-check text-success me-2"></i> ${message}`;
        }

        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }
});
