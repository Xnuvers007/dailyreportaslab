/**
 * ASLAB REPORT - 3D CONSTELLATION BACKGROUND (THREE.JS)
 * Efek partikel & jaringan konstelasi interaktif untuk halaman Login & Brand Canvas
 */

document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('bg-canvas');
    if (!canvas || typeof THREE === 'undefined') return;

    initThreeConstellation(canvas);

    function initThreeConstellation(canvasEl) {
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 1, 1000);
        camera.position.z = 400;

        const renderer = new THREE.WebGLRenderer({ canvas: canvasEl, alpha: true, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        let isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
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
                    (-1 + Math.random() * 2) * 0.35,
                    (-1 + Math.random() * 2) * 0.35,
                    (-1 + Math.random() * 2) * 0.35
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
            opacity: isDark ? 0.45 : 0.25
        });

        const linesMesh = new THREE.LineSegments(linesGeometry, linesMaterial);
        scene.add(linesMesh);

        // Mouse Parallax Effect
        let mouseX = 0;
        let mouseY = 0;
        window.addEventListener('mousemove', function (e) {
            mouseX = (e.clientX - window.innerWidth / 2) * 0.08;
            mouseY = (e.clientY - window.innerHeight / 2) * 0.08;
        });

        // Event listener saat tema gelap / terang berganti
        window.addEventListener('themeChanged', function (e) {
            isDark = e.detail.theme === 'dark';
            particleColor = isDark ? 0x38bdf8 : 0x2563eb;
            lineColor = isDark ? 0x1e3a8a : 0x93c5fd;

            particleMaterial.color.setHex(particleColor);
            particleMaterial.opacity = isDark ? 0.75 : 0.6;
            linesMaterial.opacity = isDark ? 0.45 : 0.25;
        });

        const minDistance = 110;

        function animate() {
            requestAnimationFrame(animate);

            // Pergerakan kamera lembut mengikuti mouse
            camera.position.x += (mouseX - camera.position.x) * 0.04;
            camera.position.y += (-mouseY - camera.position.y) * 0.04;
            camera.lookAt(scene.position);

            const posArray = particleGeometry.attributes.position.array;
            let lineIndex = 0;
            let colorIndex = 0;

            const baseColor = new THREE.Color(lineColor);

            for (let i = 0; i < particleCount; i++) {
                const data = particlesData[i];

                posArray[i * 3] += data.velocity.x;
                posArray[i * 3 + 1] += data.velocity.y;
                posArray[i * 3 + 2] += data.velocity.z;

                if (posArray[i * 3] < -range / 2 || posArray[i * 3] > range / 2) data.velocity.x = -data.velocity.x;
                if (posArray[i * 3 + 1] < -range / 2 || posArray[i * 3 + 1] > range / 2) data.velocity.y = -data.velocity.y;
                if (posArray[i * 3 + 2] < -range / 2 || posArray[i * 3 + 2] > range / 2) data.velocity.z = -data.velocity.z;

                // Hubungkan partikel yang saling berdekatan dengan garis
                for (let j = i + 1; j < particleCount; j++) {
                    const dx = posArray[i * 3] - posArray[j * 3];
                    const dy = posArray[i * 3 + 1] - posArray[j * 3 + 1];
                    const dz = posArray[i * 3 + 2] - posArray[j * 3 + 2];
                    const dist = Math.sqrt(dx * dx + dy * dy + dz * dz);

                    if (dist < minDistance) {
                        const alpha = 1.0 - dist / minDistance;

                        linePositions[lineIndex++] = posArray[i * 3];
                        linePositions[lineIndex++] = posArray[i * 3 + 1];
                        linePositions[lineIndex++] = posArray[i * 3 + 2];

                        linePositions[lineIndex++] = posArray[j * 3];
                        linePositions[lineIndex++] = posArray[j * 3 + 1];
                        linePositions[lineIndex++] = posArray[j * 3 + 2];

                        lineColors[colorIndex++] = baseColor.r * alpha;
                        lineColors[colorIndex++] = baseColor.g * alpha;
                        lineColors[colorIndex++] = baseColor.b * alpha;

                        lineColors[colorIndex++] = baseColor.r * alpha;
                        lineColors[colorIndex++] = baseColor.g * alpha;
                        lineColors[colorIndex++] = baseColor.b * alpha;
                    }
                }
            }

            particleGeometry.attributes.position.needsUpdate = true;

            linesGeometry.setDrawRange(0, lineIndex / 3);
            linesGeometry.attributes.position.needsUpdate = true;
            linesGeometry.attributes.color.needsUpdate = true;

            renderer.render(scene, camera);
        }

        animate();

        window.addEventListener('resize', function () {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    }
});
