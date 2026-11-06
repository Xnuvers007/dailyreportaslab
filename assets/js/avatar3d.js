/**
 * ASLAB REPORT - 3D ASLAB CHARACTER (THREE.JS)
 * 3D Model: Asisten Lab Universitas Pamulang (UNPAM) dengan Baju PDH Oranye
 */

document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('aslab-avatar-canvas');
    if (!canvas || typeof THREE === 'undefined') return;

    initAslabAvatar(canvas);

    function initAslabAvatar(canvasEl) {
        // 1. Scene, Camera, Renderer
        const scene = new THREE.Scene();
        const width = canvasEl.clientWidth || 300;
        const height = canvasEl.clientHeight || 240;

        const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
        camera.position.set(0, 0.2, 5.2);

        const renderer = new THREE.WebGLRenderer({ canvas: canvasEl, alpha: true, antialias: true });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;

        // 2. Lighting Setup
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.85);
        scene.add(ambientLight);

        const dirLight = new THREE.DirectionalLight(0xffffff, 0.9);
        dirLight.position.set(3, 5, 4);
        scene.add(dirLight);

        // Warm orange rim light (khas UNPAM)
        const orangeRimLight = new THREE.PointLight(0xf97316, 1.2, 8);
        orangeRimLight.position.set(-2, 1.5, 2);
        scene.add(orangeRimLight);

        const blueFillLight = new THREE.PointLight(0x38bdf8, 0.8, 8);
        blueFillLight.position.set(2, -1, 2);
        scene.add(blueFillLight);

        // 3. Root Group
        const characterGroup = new THREE.Group();
        characterGroup.position.y = -0.55;
        scene.add(characterGroup);

        // Helper Canvas Texture untuk Baju PDH Oranye Universitas Pamulang
        function createPdhTexture() {
            const cvs = document.createElement('canvas');
            cvs.width = 512;
            cvs.height = 512;
            const ctx = cvs.getContext('2d');

            // Warna Dasar Baju PDH UNPAM (Orange Khas)
            ctx.fillStyle = '#ea580c';
            ctx.fillRect(0, 0, 512, 512);

            // Garis Kancing & Placket Tengah
            ctx.fillStyle = '#c2410c';
            ctx.fillRect(240, 60, 32, 452);

            // Kancing Putih
            ctx.fillStyle = '#ffffff';
            for (let y = 120; y < 480; y += 75) {
                ctx.beginPath();
                ctx.arc(256, y, 6, 0, Math.PI * 2);
                ctx.fill();
            }

            // Kantong Kiri (Dada Kiri: Tulisan "ASLAB")
            ctx.fillStyle = '#c2410c';
            ctx.fillRect(100, 160, 100, 90);
            ctx.fillStyle = '#0f172a'; // Badge background
            ctx.fillRect(110, 140, 80, 24);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 15px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('ASLAB', 150, 157);

            // Kantong Kanan (Dada Kanan: Tulisan "UNPAM")
            ctx.fillStyle = '#c2410c';
            ctx.fillRect(312, 160, 100, 90);
            ctx.fillStyle = '#1e3a8a'; // Badge background blue
            ctx.fillRect(322, 140, 80, 24);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 14px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('UNPAM', 362, 157);

            // Bordir Kerah
            ctx.strokeStyle = '#9a3412';
            ctx.lineWidth = 6;
            ctx.beginPath();
            ctx.moveTo(180, 0);
            ctx.lineTo(256, 70);
            ctx.lineTo(332, 0);
            ctx.stroke();

            const texture = new THREE.CanvasTexture(cvs);
            texture.anisotropy = 4;
            return texture;
        }

        // Helper Canvas Texture untuk ID Card Lanyard Universitas Pamulang
        function createIdCardTexture() {
            const cvs = document.createElement('canvas');
            cvs.width = 256;
            cvs.height = 360;
            const ctx = cvs.getContext('2d');

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, 256, 360);

            // Header Biru
            ctx.fillStyle = '#1e40af';
            ctx.fillRect(0, 0, 256, 85);

            // Logo & Header Text
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 19px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('ASISTEN LAB', 128, 42);
            ctx.font = '12px sans-serif';
            ctx.fillText('UNIVERSITAS PAMULANG', 128, 68);

            // Photo Placeholder
            ctx.fillStyle = '#f97316';
            ctx.fillRect(80, 110, 96, 110);
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.arc(128, 150, 24, 0, Math.PI * 2);
            ctx.fill();

            // Name
            ctx.fillStyle = '#0f172a';
            ctx.font = 'bold 17px sans-serif';
            ctx.fillText('ASLAB TI S1', 128, 255);
            ctx.font = '12px sans-serif';
            ctx.fillStyle = '#ea580c';
            ctx.fillText('LAB INFORMATIKA', 128, 280);

            // Barcode
            ctx.fillStyle = '#334155';
            for (let x = 40; x < 216; x += 10) {
                ctx.fillRect(x, 310, 5, 25);
            }

            const texture = new THREE.CanvasTexture(cvs);
            texture.anisotropy = 4;
            return texture;
        }

        // ----------------------------------------------------
        // BUILD 3D CHARACTER BODY PARTS
        // ----------------------------------------------------

        // 1. Torso (Baju PDH Oranye UNPAM)
        const pdhTexture = createPdhTexture();
        const torsoMat = new THREE.MeshStandardMaterial({
            map: pdhTexture,
            roughness: 0.6,
            metalness: 0.05
        });
        const torsoGeo = new THREE.CylinderGeometry(0.72, 0.65, 1.6, 24);
        const torso = new THREE.Mesh(torsoGeo, torsoMat);
        torso.position.y = 0.8;
        characterGroup.add(torso);

        // Pangkat Pundak (Shoulder Epaulets)
        const epauletMat = new THREE.MeshStandardMaterial({ color: 0x9a3412, roughness: 0.5 });
        const leftEpaulet = new THREE.Mesh(new THREE.BoxGeometry(0.45, 0.08, 0.22), epauletMat);
        leftEpaulet.position.set(-0.65, 1.55, 0);
        leftEpaulet.rotation.z = 0.15;
        characterGroup.add(leftEpaulet);

        const rightEpaulet = new THREE.Mesh(new THREE.BoxGeometry(0.45, 0.08, 0.22), epauletMat);
        rightEpaulet.position.set(0.65, 1.55, 0);
        rightEpaulet.rotation.z = -0.15;
        characterGroup.add(rightEpaulet);

        // 2. ID Card & Lanyard Tali Gantungan
        const lanyardMat = new THREE.MeshStandardMaterial({ color: 0x2563eb, roughness: 0.4 });
        const lanyardGeo = new THREE.TorusGeometry(0.55, 0.03, 8, 24, Math.PI);
        const lanyard = new THREE.Mesh(lanyardGeo, lanyardMat);
        lanyard.position.set(0, 1.55, 0.25);
        lanyard.rotation.x = Math.PI / 2.3;
        characterGroup.add(lanyard);

        // Kartu Identitas Fisik
        const idCardTexture = createIdCardTexture();
        const idCardMat = new THREE.MeshStandardMaterial({
            map: idCardTexture,
            roughness: 0.3,
            metalness: 0.1
        });
        const idCard = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.58, 0.02), idCardMat);
        idCard.position.set(0, 0.88, 0.72);
        idCard.rotation.x = 0.08;
        characterGroup.add(idCard);

        // 3. Leher & Kepala (Head Group untuk Tracking Mouse)
        const headGroup = new THREE.Group();
        headGroup.position.set(0, 1.65, 0);
        characterGroup.add(headGroup);

        const skinMat = new THREE.MeshStandardMaterial({ color: 0xfbd0b1, roughness: 0.7 });
        const neck = new THREE.Mesh(new THREE.CylinderGeometry(0.24, 0.28, 0.35, 16), skinMat);
        neck.position.y = 0.15;
        headGroup.add(neck);

        // Kepala
        const head = new THREE.Mesh(new THREE.SphereGeometry(0.58, 32, 32), skinMat);
        head.position.y = 0.65;
        headGroup.add(head);

        // Rambut Modern (Stylish Dark Haircut)
        const hairMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.85 });
        const hairTop = new THREE.Mesh(new THREE.SphereGeometry(0.61, 24, 24, 0, Math.PI * 2, 0, Math.PI / 1.7), hairMat);
        hairTop.position.set(0, 0.72, -0.05);
        headGroup.add(hairTop);

        // Jambul / Poni Rambut
        const bangs = new THREE.Mesh(new THREE.BoxGeometry(0.45, 0.2, 0.25), hairMat);
        bangs.position.set(0, 1.15, 0.45);
        bangs.rotation.x = 0.3;
        headGroup.add(bangs);

        // Kacamata Tech Modern (Modern Frame Glasses)
        const frameMat = new THREE.MeshStandardMaterial({ color: 0x0284c7, metalness: 0.8, roughness: 0.2 });
        const glassMat = new THREE.MeshStandardMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.45 });

        const leftGlass = new THREE.Mesh(new THREE.BoxGeometry(0.26, 0.18, 0.04), glassMat);
        leftGlass.position.set(-0.24, 0.68, 0.54);
        headGroup.add(leftGlass);

        const rightGlass = new THREE.Mesh(new THREE.BoxGeometry(0.26, 0.18, 0.04), glassMat);
        rightGlass.position.set(0.24, 0.68, 0.54);
        headGroup.add(rightGlass);

        const bridge = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.03, 0.03), frameMat);
        bridge.position.set(0, 0.68, 0.55);
        headGroup.add(bridge);

        // Mata & Pupil
        const eyeWhiteMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
        const pupilMat = new THREE.MeshBasicMaterial({ color: 0x0f172a });

        const leftEye = new THREE.Mesh(new THREE.SphereGeometry(0.08, 16, 16), eyeWhiteMat);
        leftEye.position.set(-0.22, 0.68, 0.48);
        headGroup.add(leftEye);

        const leftPupil = new THREE.Mesh(new THREE.SphereGeometry(0.045, 12, 12), pupilMat);
        leftPupil.position.set(-0.22, 0.68, 0.54);
        headGroup.add(leftPupil);

        const rightEye = new THREE.Mesh(new THREE.SphereGeometry(0.08, 16, 16), eyeWhiteMat);
        rightEye.position.set(0.22, 0.68, 0.48);
        headGroup.add(rightEye);

        const rightPupil = new THREE.Mesh(new THREE.SphereGeometry(0.045, 12, 12), pupilMat);
        rightPupil.position.set(0.22, 0.68, 0.54);
        headGroup.add(rightPupil);

        // Senyum Ramah
        const smileMat = new THREE.MeshBasicMaterial({ color: 0xd97706 });
        const smile = new THREE.Mesh(new THREE.TorusGeometry(0.12, 0.02, 8, 16, Math.PI), smileMat);
        smile.position.set(0, 0.48, 0.52);
        smile.rotation.x = Math.PI;
        headGroup.add(smile);

        // 4. Lengan & Tangan Memegang Tablet Digital
        const sleeveMat = new THREE.MeshStandardMaterial({ color: 0xea580c, roughness: 0.6 });

        // Lengan Kiri (Menopang Tablet)
        const leftArm = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.16, 0.9, 16), sleeveMat);
        leftArm.position.set(-0.85, 0.95, 0.2);
        leftArm.rotation.set(0.4, 0, 0.35);
        characterGroup.add(leftArm);

        // Lengan Kanan (Mengetik / Pose Siap)
        const rightArm = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.16, 0.9, 16), sleeveMat);
        rightArm.position.set(0.85, 0.95, 0.2);
        rightArm.rotation.set(0.4, 0, -0.35);
        characterGroup.add(rightArm);

        // Tablet Digital dengan Layar Menyala
        const tabletBody = new THREE.Mesh(
            new THREE.BoxGeometry(0.7, 0.05, 0.95),
            new THREE.MeshStandardMaterial({ color: 0x1e293b, metalness: 0.7, roughness: 0.3 })
        );
        tabletBody.position.set(0, 0.52, 0.65);
        tabletBody.rotation.x = 0.45;
        characterGroup.add(tabletBody);

        const tabletScreen = new THREE.Mesh(
            new THREE.PlaneGeometry(0.6, 0.85),
            new THREE.MeshBasicMaterial({ color: 0x10b981 }) // Glowing screen
        );
        tabletScreen.position.set(0, 0.55, 0.65);
        tabletScreen.rotation.x = -Math.PI / 2 + 0.45;
        characterGroup.add(tabletScreen);

        // 5. Pedestal Hologram / Platform Bawah
        const pedestalMat = new THREE.MeshStandardMaterial({
            color: 0x0f172a,
            roughness: 0.4,
            metalness: 0.6
        });
        const pedestal = new THREE.Mesh(new THREE.CylinderGeometry(1.65, 1.8, 0.2, 32), pedestalMat);
        pedestal.position.y = -0.1;
        characterGroup.add(pedestal);

        // Cincin Hologram Menyala Berputar
        const holoRingMat = new THREE.MeshBasicMaterial({
            color: 0xf97316,
            transparent: true,
            opacity: 0.85,
            wireframe: true
        });
        const holoRing = new THREE.Mesh(new THREE.TorusGeometry(1.75, 0.04, 16, 48), holoRingMat);
        holoRing.position.y = 0.05;
        holoRing.rotation.x = Math.PI / 2;
        characterGroup.add(holoRing);

        const holoRingInner = new THREE.Mesh(
            new THREE.TorusGeometry(1.4, 0.02, 16, 48),
            new THREE.MeshBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.75 })
        );
        holoRingInner.position.y = 0.08;
        holoRingInner.rotation.x = Math.PI / 2;
        characterGroup.add(holoRingInner);

        // ----------------------------------------------------
        // MOUSE TRACKING & INTERACTIVE ANIMATION LOOP
        // ----------------------------------------------------
        let targetRotX = 0;
        let targetRotY = 0;
        let currentRotX = 0;
        let currentRotY = 0;
        let isNodding = false;
        let nodTimer = 0;

        window.addEventListener('mousemove', function (e) {
            const rect = canvasEl.getBoundingClientRect();
            const mouseX = e.clientX - (rect.left + rect.width / 2);
            const mouseY = e.clientY - (rect.top + rect.height / 2);

            targetRotY = (mouseX / (window.innerWidth / 2)) * 0.45;
            targetRotX = (mouseY / (window.innerHeight / 2)) * 0.35;
        });

        // Interaksi Klik: Karakter mengangguk ramah
        canvasEl.addEventListener('click', function () {
            isNodding = true;
            nodTimer = 0;
        });

        // Theme switch listener
        window.addEventListener('themeChanged', function (e) {
            const isDark = e.detail.theme === 'dark';
            orangeRimLight.intensity = isDark ? 1.4 : 0.9;
            blueFillLight.intensity = isDark ? 1.0 : 0.6;
            ambientLight.intensity = isDark ? 0.7 : 0.95;
        });

        let clock = new THREE.Clock();

        function renderLoop() {
            requestAnimationFrame(renderLoop);

            const elapsedTime = clock.getElapsedTime();

            // Smooth Interpolation (Lerp) untuk Head & Eye Tracking
            currentRotX += (targetRotX - currentRotX) * 0.08;
            currentRotY += (targetRotY - currentRotY) * 0.08;

            headGroup.rotation.y = currentRotY;
            headGroup.rotation.x = currentRotX;

            // Gerakan Nafas Halus (Idle Breathing)
            const breathing = Math.sin(elapsedTime * 2.5) * 0.035;
            torso.position.y = 0.8 + breathing;
            headGroup.position.y = 1.65 + breathing;
            idCard.position.y = 0.88 + breathing;
            tabletBody.position.y = 0.52 + breathing;
            tabletScreen.position.y = 0.55 + breathing;

            // Rotasi Cincin Hologram
            holoRing.rotation.z += 0.015;
            holoRingInner.rotation.z -= 0.02;

            // Animasi Mengangguk jika diklik
            if (isNodding) {
                nodTimer += 0.12;
                headGroup.rotation.x = currentRotX + Math.sin(nodTimer * Math.PI) * 0.25;
                if (nodTimer >= 2) {
                    isNodding = false;
                }
            }

            renderer.render(scene, camera);
        }

        renderLoop();

        // Responsive Resize
        window.addEventListener('resize', function () {
            const newW = canvasEl.clientWidth || 300;
            const newH = canvasEl.clientHeight || 240;
            camera.aspect = newW / newH;
            camera.updateProjectionMatrix();
            renderer.setSize(newW, newH);
        });
    }
});
