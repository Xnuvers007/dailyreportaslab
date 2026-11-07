/**
 * ASLAB REPORT - 3D INTERACTIVE LOGIN AVATAR (THREE.JS)
 * Karakter 3D Chibi Asisten Lab UNPAM (Baju PDH Oranye) dengan 2-Bone IK Rig:
 * - IDLE: Bernafas halus, kedip sesekali, kepala & bola mata mengikuti kursor mouse, tangan bersandar santai
 * - USERNAME FOCUS: Mencondongkan kepala ke depan, mata melirik ke input username, tangan kanan di dagu/pipi berpikir
 * - PASSWORD FOCUS: KEDUA TANGAN MENUTUP KEDUA MATA SECARA SEMPURNA (Hands cover eyes + Eyelids close tight!)
 * - SHOW PASSWORD (EYE TOGGLE): Tangan kanan tetap menutup mata kanan, tangan kiri membuka celah, mata kiri terbuka lebar mengintip!
 * - LOGIN ERROR: Menggelengkan kepala dengan ekspresi kaget (Oops!) mulut "O"
 * - LOGIN SUCCESS: Tersenyum lebar, mata merem gembira (⌒ ⌒), dan tangan kanan mengacungkan jempol (Thumbs up!)
 */

document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('login-avatar-canvas');
    if (!canvas || typeof THREE === 'undefined') return;

    initLoginAvatar(canvas);

    function initLoginAvatar(canvasEl) {
        // 1. Scene, Camera, Renderer Setup
        const scene = new THREE.Scene();
        const width = canvasEl.clientWidth || 260;
        const height = canvasEl.clientHeight || 200;

        const camera = new THREE.PerspectiveCamera(35, width / height, 0.1, 50);
        camera.position.set(0, 0.85, 3.25);
        camera.lookAt(0, 0.85, 0);

        const renderer = new THREE.WebGLRenderer({ canvas: canvasEl, alpha: true, antialias: true });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;

        // 2. Studio Lighting Setup
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.95);
        scene.add(ambientLight);

        const dirLight = new THREE.DirectionalLight(0xffffff, 1.05);
        dirLight.position.set(2.0, 4.0, 3.0);
        scene.add(dirLight);

        // Warm orange rim light (khas almamater UNPAM)
        const orangeRimLight = new THREE.PointLight(0xf97316, 1.4, 8);
        orangeRimLight.position.set(-2.2, 1.8, 1.6);
        scene.add(orangeRimLight);

        const blueFillLight = new THREE.PointLight(0x38bdf8, 0.7, 8);
        blueFillLight.position.set(2.2, -0.4, 1.8);
        scene.add(blueFillLight);

        // 3. Shared Materials
        const skinMat = new THREE.MeshStandardMaterial({ color: 0xfed7aa, roughness: 0.65 });
        const sleeveMat = new THREE.MeshStandardMaterial({ color: 0xea580c, roughness: 0.6 });
        const collarMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.4 });
        const hairMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.85 });
        const glassesMat = new THREE.MeshStandardMaterial({ color: 0x0284c7, metalness: 0.8, roughness: 0.2 });
        const glassLensMat = new THREE.MeshStandardMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.25 });
        const eyeWhiteMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
        const pupilMat = new THREE.MeshBasicMaterial({ color: 0x0f172a });
        const shineMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
        const closedEyeMat = new THREE.MeshBasicMaterial({ color: 0x1e293b });
        const blushMat = new THREE.MeshBasicMaterial({ color: 0xf43f5e, transparent: true, opacity: 0.45 });
        const smileMat = new THREE.MeshBasicMaterial({ color: 0xc2410c });
        const mouthOMat = new THREE.MeshBasicMaterial({ color: 0x991b1b });

        // Root Character Group
        const characterGroup = new THREE.Group();
        characterGroup.position.set(0, 0, 0);
        scene.add(characterGroup);

        // Procedural Texture Baju PDH Oranye UNPAM
        function createPdhTexture() {
            const cvs = document.createElement('canvas');
            cvs.width = 512;
            cvs.height = 512;
            const ctx = cvs.getContext('2d');

            ctx.fillStyle = '#ea580c';
            ctx.fillRect(0, 0, 512, 512);

            // Garis belahan kancing tengah
            ctx.fillStyle = '#c2410c';
            ctx.fillRect(242, 40, 28, 472);

            // Kancing Putih
            ctx.fillStyle = '#ffffff';
            for (let y = 100; y < 480; y += 75) {
                ctx.beginPath();
                ctx.arc(256, y, 6, 0, Math.PI * 2);
                ctx.fill();
            }

            // Kantong Dada Kiri: "ASLAB"
            ctx.fillStyle = '#c2410c';
            ctx.fillRect(100, 150, 95, 80);
            ctx.fillStyle = '#0f172a';
            ctx.fillRect(105, 132, 85, 24);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 15px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('ASLAB', 147, 150);

            // Kantong Dada Kanan: "UNPAM"
            ctx.fillStyle = '#c2410c';
            ctx.fillRect(317, 150, 95, 80);
            ctx.fillStyle = '#1e3a8a';
            ctx.fillRect(322, 132, 85, 24);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 14px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('UNPAM', 364, 150);

            const texture = new THREE.CanvasTexture(cvs);
            texture.anisotropy = 4;
            return texture;
        }

        // Texture ID Card Lanyard
        function createIdCardTexture() {
            const cvs = document.createElement('canvas');
            cvs.width = 256;
            cvs.height = 360;
            const ctx = cvs.getContext('2d');

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, 256, 360);

            ctx.fillStyle = '#1e40af';
            ctx.fillRect(0, 0, 256, 85);

            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 17px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('ASISTEN LAB', 128, 42);
            ctx.font = '11px sans-serif';
            ctx.fillText('UNIVERSITAS PAMULANG', 128, 66);

            ctx.fillStyle = '#f97316';
            ctx.fillRect(80, 110, 96, 110);
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.arc(128, 150, 22, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = '#0f172a';
            ctx.font = 'bold 16px sans-serif';
            ctx.fillText('ASLAB TI', 128, 255);
            ctx.font = '12px sans-serif';
            ctx.fillStyle = '#ea580c';
            ctx.fillText('LAB KOMPUTER', 128, 280);

            const texture = new THREE.CanvasTexture(cvs);
            texture.anisotropy = 4;
            return texture;
        }

        // ----------------------------------------------------
        // 4. TORSO & DESK PEEK GEOMETRY
        // ----------------------------------------------------
        const torsoGroup = new THREE.Group();
        characterGroup.add(torsoGroup);

        // Badan Melengkung Manis (Rounded Torso)
        const torsoMat = new THREE.MeshStandardMaterial({ map: createPdhTexture(), roughness: 0.6 });
        const torso = new THREE.Mesh(new THREE.CylinderGeometry(0.48, 0.42, 0.72, 24), torsoMat);
        torso.position.y = 0.32;
        torsoGroup.add(torso);

        // Kerah Baju Putih (Crisp White Collar)
        const collar = new THREE.Mesh(new THREE.CylinderGeometry(0.24, 0.28, 0.16, 16), collarMat);
        collar.position.set(0, 0.70, 0);
        torsoGroup.add(collar);

        // Pangkat Pundak (Shoulder Epaulets)
        const epauletMat = new THREE.MeshStandardMaterial({ color: 0x9a3412, roughness: 0.5 });
        const leftEpaulet = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.06, 0.18), epauletMat);
        leftEpaulet.position.set(-0.38, 0.72, 0);
        leftEpaulet.rotation.z = 0.18;
        torsoGroup.add(leftEpaulet);

        const rightEpaulet = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.06, 0.18), epauletMat);
        rightEpaulet.position.set(0.38, 0.72, 0);
        rightEpaulet.rotation.z = -0.18;
        torsoGroup.add(rightEpaulet);

        // Lanyard Biru & Kartu Identitas Aslab
        const lanyardMat = new THREE.MeshStandardMaterial({ color: 0x1e40af, roughness: 0.4 });
        const lanyard = new THREE.Mesh(new THREE.TorusGeometry(0.32, 0.022, 8, 24, Math.PI), lanyardMat);
        lanyard.position.set(0, 0.70, 0.22);
        lanyard.rotation.x = Math.PI / 2.2;
        torsoGroup.add(lanyard);

        const idCardMat = new THREE.MeshStandardMaterial({ map: createIdCardTexture(), roughness: 0.3 });
        const idCard = new THREE.Mesh(new THREE.BoxGeometry(0.26, 0.36, 0.015), idCardMat);
        idCard.position.set(0, 0.35, 0.49);
        idCard.rotation.x = 0.08;
        torsoGroup.add(idCard);

        // ----------------------------------------------------
        // 5. HEAD GROUP & DETAILED CHIBI FACE
        // ----------------------------------------------------
        const headGroup = new THREE.Group();
        headGroup.position.set(0, 0.72, 0);
        characterGroup.add(headGroup);

        // Leher
        const neck = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.20, 0.22, 16), skinMat);
        neck.position.y = 0.08;
        headGroup.add(neck);

        // Kepala Chibi Menggemaskan
        const head = new THREE.Mesh(new THREE.SphereGeometry(0.46, 32, 32), skinMat);
        head.position.y = 0.48;
        head.scale.set(1.02, 0.98, 1.0);
        headGroup.add(head);

        // Pipi Merona Pink (Blushing Cheeks)
        const leftBlush = new THREE.Mesh(new THREE.CircleGeometry(0.075, 16), blushMat);
        leftBlush.position.set(-0.28, 0.40, 0.41);
        leftBlush.rotation.y = -0.35;
        headGroup.add(leftBlush);

        const rightBlush = new THREE.Mesh(new THREE.CircleGeometry(0.075, 16), blushMat);
        rightBlush.position.set(0.28, 0.40, 0.41);
        rightBlush.rotation.y = 0.35;
        headGroup.add(rightBlush);

        // Telinga Lucu
        const earGeo = new THREE.SphereGeometry(0.09, 16, 16);
        const leftEar = new THREE.Mesh(earGeo, skinMat);
        leftEar.position.set(-0.46, 0.48, 0);
        leftEar.scale.set(0.6, 1, 0.8);
        headGroup.add(leftEar);

        const rightEar = new THREE.Mesh(earGeo, skinMat);
        rightEar.position.set(0.46, 0.48, 0);
        rightEar.scale.set(0.6, 1, 0.8);
        headGroup.add(rightEar);

        // Rambut Stylized Chibi
        const hairTop = new THREE.Mesh(new THREE.SphereGeometry(0.49, 24, 24, 0, Math.PI * 2, 0, Math.PI / 1.7), hairMat);
        hairTop.position.set(0, 0.52, -0.02);
        headGroup.add(hairTop);

        // Poni & Jambul Depan
        const bangCenter = new THREE.Mesh(new THREE.BoxGeometry(0.36, 0.16, 0.20), hairMat);
        bangCenter.position.set(0, 0.86, 0.36);
        bangCenter.rotation.x = 0.28;
        headGroup.add(bangCenter);

        const bangLeft = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.22, 0.15), hairMat);
        bangLeft.position.set(-0.25, 0.78, 0.38);
        bangLeft.rotation.z = 0.22;
        headGroup.add(bangLeft);

        const bangRight = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.22, 0.15), hairMat);
        bangRight.position.set(0.25, 0.78, 0.38);
        bangRight.rotation.z = -0.22;
        headGroup.add(bangRight);

        // ----------------------------------------------------
        // 6. DUAL EYE SYSTEM (OPEN WITH SHINE & CLOSED ARCS)
        // ----------------------------------------------------
        const EYE_LX = -0.19, EYE_RX = 0.19;
        const EYE_Y = 0.50;
        const EYE_Z = 0.42;

        // MATA KIRI (OPEN GROUP)
        const leftEyeOpenGroup = new THREE.Group();
        leftEyeOpenGroup.position.set(EYE_LX, EYE_Y, EYE_Z);
        headGroup.add(leftEyeOpenGroup);

        const leftEyeWhite = new THREE.Mesh(new THREE.SphereGeometry(0.082, 16, 16), eyeWhiteMat);
        leftEyeWhite.scale.set(1, 1.1, 0.5);
        leftEyeOpenGroup.add(leftEyeWhite);

        const leftPupil = new THREE.Mesh(new THREE.SphereGeometry(0.052, 16, 16), pupilMat);
        leftPupil.position.set(0, 0, 0.042);
        leftEyeOpenGroup.add(leftPupil);

        const leftShine = new THREE.Mesh(new THREE.SphereGeometry(0.018, 8, 8), shineMat);
        leftShine.position.set(0.016, 0.020, 0.075);
        leftEyeOpenGroup.add(leftShine);

        // MATA KIRI (CLOSED ARC ⌒)
        const leftEyeClosedGroup = new THREE.Group();
        leftEyeClosedGroup.position.set(EYE_LX, EYE_Y, EYE_Z + 0.04);
        leftEyeClosedGroup.visible = false;
        headGroup.add(leftEyeClosedGroup);

        const leftClosedArc = new THREE.Mesh(new THREE.TorusGeometry(0.065, 0.016, 8, 16, Math.PI), closedEyeMat);
        leftClosedArc.rotation.x = Math.PI; // Happy closed eye curve (⌒)
        leftEyeClosedGroup.add(leftClosedArc);

        // MATA KANAN (OPEN GROUP)
        const rightEyeOpenGroup = new THREE.Group();
        rightEyeOpenGroup.position.set(EYE_RX, EYE_Y, EYE_Z);
        headGroup.add(rightEyeOpenGroup);

        const rightEyeWhite = new THREE.Mesh(new THREE.SphereGeometry(0.082, 16, 16), eyeWhiteMat);
        rightEyeWhite.scale.set(1, 1.1, 0.5);
        rightEyeOpenGroup.add(rightEyeWhite);

        const rightPupil = new THREE.Mesh(new THREE.SphereGeometry(0.052, 16, 16), pupilMat);
        rightPupil.position.set(0, 0, 0.042);
        rightEyeOpenGroup.add(rightPupil);

        const rightShine = new THREE.Mesh(new THREE.SphereGeometry(0.018, 8, 8), shineMat);
        rightShine.position.set(0.016, 0.020, 0.075);
        rightEyeOpenGroup.add(rightShine);

        // MATA KANAN (CLOSED ARC ⌒)
        const rightEyeClosedGroup = new THREE.Group();
        rightEyeClosedGroup.position.set(EYE_RX, EYE_Y, EYE_Z + 0.04);
        rightEyeClosedGroup.visible = false;
        headGroup.add(rightEyeClosedGroup);

        const rightClosedArc = new THREE.Mesh(new THREE.TorusGeometry(0.065, 0.016, 8, 16, Math.PI), closedEyeMat);
        rightClosedArc.rotation.x = Math.PI;
        rightEyeClosedGroup.add(rightClosedArc);

        // KACAMATA MODERN (Tech Blue Frames)
        const frameWidth = 0.22, frameHeight = 0.16;
        const leftGlass = new THREE.Mesh(new THREE.BoxGeometry(frameWidth, frameHeight, 0.025), glassLensMat);
        leftGlass.position.set(EYE_LX, EYE_Y, EYE_Z + 0.07);
        headGroup.add(leftGlass);

        const rightGlass = new THREE.Mesh(new THREE.BoxGeometry(frameWidth, frameHeight, 0.025), glassLensMat);
        rightGlass.position.set(EYE_RX, EYE_Y, EYE_Z + 0.07);
        headGroup.add(rightGlass);

        const bridge = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.022, 0.022), glassesMat);
        bridge.position.set(0, EYE_Y, EYE_Z + 0.07);
        headGroup.add(bridge);

        // SENYUM RAMAH
        const smile = new THREE.Mesh(new THREE.TorusGeometry(0.09, 0.016, 8, 16, Math.PI), smileMat);
        smile.position.set(0, 0.33, 0.43);
        smile.rotation.x = Math.PI;
        headGroup.add(smile);

        // MULUT "O" (Ekspresi Kaget / Error)
        const mouthO = new THREE.Mesh(new THREE.TorusGeometry(0.065, 0.018, 8, 16), mouthOMat);
        mouthO.position.set(0, 0.31, 0.44);
        mouthO.visible = false;
        headGroup.add(mouthO);

        // ----------------------------------------------------
        // 7. INVERSE KINEMATICS (IK) 2-BONE ARMS & HANDS RIG
        // ----------------------------------------------------
        const L1 = 0.45; // Upper Arm length
        const L2 = 0.45; // Forearm length

        // Posisi Bahu (Shoulder Positions)
        const SHOULDER_L = new THREE.Vector3(-0.38, 0.72, 0.05);
        const SHOULDER_R = new THREE.Vector3(0.38, 0.72, 0.05);

        // Joint Spheres di Bahu
        const leftShoulderMesh = new THREE.Mesh(new THREE.SphereGeometry(0.11, 16, 16), sleeveMat);
        leftShoulderMesh.position.copy(SHOULDER_L);
        torsoGroup.add(leftShoulderMesh);

        const rightShoulderMesh = new THREE.Mesh(new THREE.SphereGeometry(0.11, 16, 16), sleeveMat);
        rightShoulderMesh.position.copy(SHOULDER_R);
        torsoGroup.add(rightShoulderMesh);

        // Mesh Lengan Kiri (Upper Arm + Forearm + Elbow + Hand)
        const leftUpperArmMesh = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.08, 1, 16), sleeveMat);
        characterGroup.add(leftUpperArmMesh);

        const leftElbowMesh = new THREE.Mesh(new THREE.SphereGeometry(0.085, 12, 12), sleeveMat);
        characterGroup.add(leftElbowMesh);

        const leftForearmMesh = new THREE.Mesh(new THREE.CylinderGeometry(0.078, 0.07, 1, 16), skinMat);
        characterGroup.add(leftForearmMesh);

        // Telapak Tangan Kiri (Palm + Fingers)
        const leftHandGroup = new THREE.Group();
        const leftPalm = new THREE.Mesh(new THREE.BoxGeometry(0.22, 0.24, 0.08), skinMat);
        leftHandGroup.add(leftPalm);
        for (let i = -1.5; i <= 1.5; i += 1.0) {
            const finger = new THREE.Mesh(new THREE.CylinderGeometry(0.024, 0.02, 0.10, 8), skinMat);
            finger.position.set(i * 0.05, 0.14, 0);
            leftHandGroup.add(finger);
        }
        characterGroup.add(leftHandGroup);

        // Mesh Lengan Kanan (Upper Arm + Forearm + Elbow + Hand)
        const rightUpperArmMesh = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.08, 1, 16), sleeveMat);
        characterGroup.add(rightUpperArmMesh);

        const rightElbowMesh = new THREE.Mesh(new THREE.SphereGeometry(0.085, 12, 12), sleeveMat);
        characterGroup.add(rightElbowMesh);

        const rightForearmMesh = new THREE.Mesh(new THREE.CylinderGeometry(0.078, 0.07, 1, 16), skinMat);
        characterGroup.add(rightForearmMesh);

        // Telapak Tangan Kanan (Palm + Fingers + Thumb untuk Thumbs Up)
        const rightHandGroup = new THREE.Group();
        const rightPalm = new THREE.Mesh(new THREE.BoxGeometry(0.22, 0.24, 0.08), skinMat);
        rightHandGroup.add(rightPalm);
        for (let i = -1.5; i <= 1.5; i += 1.0) {
            const finger = new THREE.Mesh(new THREE.CylinderGeometry(0.024, 0.02, 0.10, 8), skinMat);
            finger.position.set(i * 0.05, 0.14, 0);
            rightHandGroup.add(finger);
        }
        // Jempol Kanan (Thumbs Up)
        const thumbMesh = new THREE.Mesh(new THREE.CylinderGeometry(0.035, 0.028, 0.18, 12), skinMat);
        thumbMesh.position.set(-0.13, 0.12, 0.02);
        thumbMesh.rotation.z = Math.PI / 4;
        thumbMesh.visible = false;
        rightHandGroup.add(thumbMesh);
        characterGroup.add(rightHandGroup);

        // Helper Fungsi untuk mengorientasikan Cylinder antara 2 titik 3D
        const CYL_UP = new THREE.Vector3(0, 1, 0);
        function updateLimbSegment(mesh, pointA, pointB) {
            const dir = new THREE.Vector3().subVectors(pointB, pointA);
            const len = dir.length();
            mesh.scale.set(1, Math.max(0.01, len), 1);
            mesh.position.set((pointA.x + pointB.x) / 2, (pointA.y + pointB.y) / 2, (pointA.z + pointB.z) / 2);
            if (len > 0.0001) {
                mesh.quaternion.setFromUnitVectors(CYL_UP, dir.clone().normalize());
            }
        }

        // 2-Bone Analytical Inverse Kinematics Solver
        function solve2BoneIK(S, H, len1, len2, bendRef) {
            const V = new THREE.Vector3().subVectors(H, S);
            let d = V.length();
            const maxReach = len1 + len2 - 0.002;
            if (d > maxReach) d = maxReach;
            if (d < 0.01) d = 0.01;
            const u = V.clone().normalize();

            const cosA = Math.max(-1, Math.min(1, (len1 * len1 + d * d - len2 * len2) / (2 * len1 * d)));
            const sinA = Math.sqrt(Math.max(0, 1 - cosA * cosA));

            // Proyeksikan bendRef tegak lurus terhadap u
            const dot = bendRef.dot(u);
            const perp = new THREE.Vector3().subVectors(bendRef, u.clone().multiplyScalar(dot));
            let pLen = perp.length();
            if (pLen < 0.0001) perp.set(0, 1, 0), pLen = 1;
            const n = perp.divideScalar(pLen);

            const E = new THREE.Vector3()
                .copy(S)
                .addScaledVector(u, len1 * cosA)
                .addScaledVector(n, len1 * sinA);

            return E;
        }

        // ----------------------------------------------------
        // 8. ANIMATION TARGETS & INTERPOLATION (LERP)
        // ----------------------------------------------------
        const currentLHand = new THREE.Vector3(-0.36, 0.22, 0.40);
        const currentRHand = new THREE.Vector3(0.36, 0.22, 0.40);
        const targetLHand = new THREE.Vector3(-0.36, 0.22, 0.40);
        const targetRHand = new THREE.Vector3(0.36, 0.22, 0.40);

        const targetLHandRot = new THREE.Euler(0, 0, 0);
        const targetRHandRot = new THREE.Euler(0, 0, 0);

        const bendRefL = new THREE.Vector3(-1.0, -0.2, 0.3);
        const bendRefR = new THREE.Vector3(1.0, -0.2, 0.3);

        const headTargetRot = new THREE.Euler(0, 0, 0);
        const headTargetPos = new THREE.Vector3(0, 0.72, 0);
        const pupilTargetOffset = new THREE.Vector2(0, 0);

        let leftEyeMustClose = false;
        let rightEyeMustClose = false;

        // States: 'IDLE', 'USERNAME', 'PASSWORD', 'SNEAK_PEEK', 'ERROR', 'SUCCESS'
        let currentState = 'IDLE';
        let isPasswordVisible = false;

        // DOM Listeners
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('togglePasswordBtn');
        const loginForm = document.querySelector('form');
        const errorAlert = document.querySelector('.alert-danger');

        // 1. EVENT: FOCUS USERNAME (Curious / Peeking)
        if (usernameInput) {
            usernameInput.addEventListener('focus', function () {
                if (currentState !== 'ERROR' && currentState !== 'SUCCESS') {
                    currentState = 'USERNAME';
                }
            });

            usernameInput.addEventListener('blur', function () {
                setTimeout(function () {
                    if (document.activeElement !== passwordInput && currentState === 'USERNAME') {
                        currentState = 'IDLE';
                    }
                }, 100);
            });

            usernameInput.addEventListener('input', function () {
                if (currentState === 'USERNAME') {
                    pupilTargetOffset.x = (Math.random() * 0.02 - 0.01);
                }
            });
        }

        // 2. EVENT: FOCUS PASSWORD (Cover Eyes State - 🙈)
        if (passwordInput) {
            passwordInput.addEventListener('focus', function () {
                if (currentState !== 'ERROR' && currentState !== 'SUCCESS') {
                    currentState = isPasswordVisible ? 'SNEAK_PEEK' : 'PASSWORD';
                }
            });

            passwordInput.addEventListener('blur', function () {
                setTimeout(function () {
                    if (document.activeElement !== usernameInput && (currentState === 'PASSWORD' || currentState === 'SNEAK_PEEK')) {
                        currentState = 'IDLE';
                    }
                }, 100);
            });
        }

        // 3. EVENT: TOGGLE SHOW PASSWORD (Sneak Peek State)
        if (togglePasswordBtn && passwordInput) {
            togglePasswordBtn.addEventListener('click', function () {
                isPasswordVisible = !isPasswordVisible;
                passwordInput.type = isPasswordVisible ? 'text' : 'password';

                const icon = togglePasswordBtn.querySelector('i');
                if (icon) {
                    icon.className = isPasswordVisible ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
                }

                if (document.activeElement === passwordInput || currentState === 'PASSWORD' || currentState === 'SNEAK_PEEK') {
                    currentState = isPasswordVisible ? 'SNEAK_PEEK' : 'PASSWORD';
                }
            });
        }

        // 4. EVENT: SUBMIT (Success State)
        if (loginForm) {
            loginForm.addEventListener('submit', function () {
                currentState = 'SUCCESS';
            });
        }

        // 5. EVENT: ERROR DETECTED ON PAGE LOAD
        let errorTimer = 0;
        if (errorAlert) {
            currentState = 'ERROR';
            errorTimer = 2.8;
        }

        // Mouse tracking saat IDLE
        let mouseX = 0, mouseY = 0;
        window.addEventListener('mousemove', function (e) {
            const rect = canvasEl.getBoundingClientRect();
            mouseX = (e.clientX - (rect.left + rect.width / 2)) / (window.innerWidth / 2);
            mouseY = (e.clientY - (rect.top + rect.height / 2)) / (window.innerHeight / 2);
        });

        // ----------------------------------------------------
        // 9. ANIMATION LOOP & STATE UPDATE
        // ----------------------------------------------------
        const clock = new THREE.Clock();
        let blinkTimer = 0;
        let isBlinking = false;

        function updateState(elapsedTime, delta) {
            switch (currentState) {
                case 'IDLE':
                    headTargetRot.set(mouseY * 0.28, mouseX * 0.38, 0);
                    headTargetPos.set(0, 0.72, 0);
                    pupilTargetOffset.set(mouseX * 0.02, -mouseY * 0.02);

                    // Tangan Bersandar Santai di Bawah
                    targetLHand.set(-0.36, 0.22, 0.40);
                    targetRHand.set(0.36, 0.22, 0.40);
                    targetLHandRot.set(0.2, 0.1, 0);
                    targetRHandRot.set(0.2, -0.1, 0);

                    bendRefL.set(-1.0, -0.2, 0.3);
                    bendRefR.set(1.0, -0.2, 0.3);

                    leftEyeMustClose = false;
                    rightEyeMustClose = false;
                    smile.visible = true;
                    mouthO.visible = false;
                    thumbMesh.visible = false;
                    break;

                case 'USERNAME':
                    headTargetPos.set(0, 0.70, 0.12);
                    headTargetRot.set(0.24, -0.12, 0.06);
                    pupilTargetOffset.set(-0.01, -0.035);

                    // Tangan Kanan Naik ke Dagu/Pipi (Pose Berpikir)
                    targetRHand.set(0.24, 0.95, 0.46);
                    targetRHandRot.set(0.4, -0.3, 0.25);
                    bendRefR.set(0.8, -0.4, 0.4);

                    // Tangan Kiri Santai di Bawah
                    targetLHand.set(-0.36, 0.22, 0.40);
                    targetLHandRot.set(0.2, 0.1, 0);
                    bendRefL.set(-1.0, -0.2, 0.3);

                    leftEyeMustClose = false;
                    rightEyeMustClose = false;
                    smile.visible = true;
                    mouthO.visible = false;
                    thumbMesh.visible = false;
                    break;

                case 'PASSWORD':
                    // KEDUA TANGAN MENUTUP KEDUA MATA SECARA SEMPURNA (100% Guaranteed Covers Eyes!)
                    // Koordinat mata riil = (±0.19, 0.72 + 0.50, 0.42) = (±0.19, 1.22, 0.42)
                    targetLHand.set(-0.19, 1.22, 0.54);
                    targetRHand.set(0.19, 1.22, 0.54);

                    // Rotasi telapak tangan menempel rapi menutupi mata
                    targetLHandRot.set(0.15, 0.2, -0.12);
                    targetRHandRot.set(0.15, -0.2, 0.12);

                    // Siku menekuk keluar ke samping secara natural
                    bendRefL.set(-1.0, -0.15, 0.35);
                    bendRefR.set(1.0, -0.15, 0.35);

                    // Kepala agak menunduk dan berpaling sedikit malu
                    headTargetPos.set(0, 0.72, 0);
                    headTargetRot.set(0.12, 0.16, -0.05);

                    // KEDUA MATA WAJIB MEREM / TUTUP RAPAT!
                    leftEyeMustClose = true;
                    rightEyeMustClose = true;

                    smile.visible = true;
                    mouthO.visible = false;
                    thumbMesh.visible = false;
                    break;

                case 'SNEAK_PEEK':
                    // SNEAK PEEK: Tangan Kanan Tetap Tutup Mata Kanan, Tangan Kiri Buka Celah!
                    targetRHand.set(0.19, 1.22, 0.54);
                    targetRHandRot.set(0.15, -0.2, 0.12);
                    bendRefR.set(1.0, -0.15, 0.35);
                    rightEyeMustClose = true; // Mata kanan tetap merem rapat!

                    // Tangan Kiri Geser ke Samping Bawah (Membuka Celah Mengintip!)
                    targetLHand.set(-0.38, 0.92, 0.48);
                    targetLHandRot.set(0.3, 0.35, -0.2);
                    bendRefL.set(-1.0, -0.3, 0.25);

                    // MATA KIRI TERBUKA LEBAR & MELIRIK KE PASSWORD!
                    leftEyeMustClose = false;
                    pupilTargetOffset.set(0.015, -0.038); // Melirik tajam ke bawah ke kolom password

                    headTargetPos.set(0, 0.70, 0.10);
                    headTargetRot.set(0.20, -0.12, 0.10);

                    smile.visible = true;
                    mouthO.visible = false;
                    thumbMesh.visible = false;
                    break;

                case 'ERROR':
                    // Kaget & Menggelengkan Kepala Cepat (Oops!)
                    errorTimer -= delta;
                    headTargetRot.set(-0.08, Math.sin(elapsedTime * 22) * 0.28, 0);
                    headTargetPos.set(0, 0.72, 0);

                    targetLHand.set(-0.42, 0.15, 0.25);
                    targetRHand.set(0.42, 0.15, 0.25);
                    targetLHandRot.set(0, 0, 0);
                    targetRHandRot.set(0, 0, 0);

                    leftEyeMustClose = false;
                    rightEyeMustClose = false;
                    pupilTargetOffset.set(0, 0);

                    smile.visible = false;
                    mouthO.visible = true; // Mulut "O" terkejut
                    thumbMesh.visible = false;

                    if (errorTimer <= 0) {
                        currentState = 'IDLE';
                    }
                    break;

                case 'SUCCESS':
                    // Mengangguk Gembira, Mata Merem Senyum (⌒ ⌒), Thumbs Up!
                    headTargetRot.set(-0.05 + Math.sin(elapsedTime * 6) * 0.10, 0, 0);
                    headTargetPos.set(0, 0.72, 0);

                    // Lengan Kanan Acungkan Jempol ke Depan (Thumbs Up!)
                    targetRHand.set(0.22, 0.85, 0.58);
                    targetRHandRot.set(0.3, -0.2, 0.1);
                    bendRefR.set(0.8, -0.3, 0.4);

                    // Tangan Kiri Santai
                    targetLHand.set(-0.36, 0.22, 0.40);
                    targetLHandRot.set(0.2, 0.1, 0);
                    bendRefL.set(-1.0, -0.2, 0.3);

                    // Kedua Mata Merem Senyum Bahagia
                    leftEyeMustClose = true;
                    rightEyeMustClose = true;

                    smile.visible = true;
                    mouthO.visible = false;
                    thumbMesh.visible = true;
                    break;
            }

            // Animasi Kedip Otomatis (Saat IDLE / USERNAME)
            if (currentState === 'IDLE' || currentState === 'USERNAME') {
                blinkTimer += delta;
                if (blinkTimer > 3.4 && !isBlinking) {
                    isBlinking = true;
                }
                if (isBlinking) {
                    leftEyeMustClose = true;
                    rightEyeMustClose = true;
                    if (blinkTimer > 3.58) {
                        isBlinking = false;
                        blinkTimer = 0;
                        leftEyeMustClose = false;
                        rightEyeMustClose = false;
                    }
                }
            }
        }

        // ----------------------------------------------------
        // 10. MAIN RENDER LOOP (SMOOTH LERP & IK UPDATE)
        // ----------------------------------------------------
        function renderLoop() {
            requestAnimationFrame(renderLoop);

            const delta = clock.getDelta();
            const elapsedTime = clock.getElapsedTime();

            updateState(elapsedTime, delta);

            // 1. Lerp Head Position & Rotation
            headGroup.rotation.x += (headTargetRot.x - headGroup.rotation.x) * 0.14;
            headGroup.rotation.y += (headTargetRot.y - headGroup.rotation.y) * 0.14;
            headGroup.rotation.z += (headTargetRot.z - headGroup.rotation.z) * 0.14;

            headGroup.position.x += (headTargetPos.x - headGroup.position.x) * 0.14;
            headGroup.position.y += (headTargetPos.y - headGroup.position.y) * 0.14;
            headGroup.position.z += (headTargetPos.z - headGroup.position.z) * 0.14;

            // 2. Toggling Mata Buka vs Merem (100% Reliable!)
            leftEyeOpenGroup.visible = !leftEyeMustClose;
            leftEyeClosedGroup.visible = leftEyeMustClose;

            rightEyeOpenGroup.visible = !rightEyeMustClose;
            rightEyeClosedGroup.visible = rightEyeMustClose;

            // 3. Lerp Bola Mata / Pupil Tracking
            leftPupil.position.x += (pupilTargetOffset.x - leftPupil.position.x) * 0.2;
            leftPupil.position.y += (pupilTargetOffset.y - leftPupil.position.y) * 0.2;
            rightPupil.position.x += (pupilTargetOffset.x - rightPupil.position.x) * 0.2;
            rightPupil.position.y += (pupilTargetOffset.y - rightPupil.position.y) * 0.2;

            // 4. Lerp Tangan (Left & Right Hands)
            currentLHand.lerp(targetLHand, 0.15);
            currentRHand.lerp(targetRHand, 0.15);

            leftHandGroup.position.copy(currentLHand);
            rightHandGroup.position.copy(currentRHand);

            leftHandGroup.rotation.x += (targetLHandRot.x - leftHandGroup.rotation.x) * 0.15;
            leftHandGroup.rotation.y += (targetLHandRot.y - leftHandGroup.rotation.y) * 0.15;
            leftHandGroup.rotation.z += (targetLHandRot.z - leftHandGroup.rotation.z) * 0.15;

            rightHandGroup.rotation.x += (targetRHandRot.x - rightHandGroup.rotation.x) * 0.15;
            rightHandGroup.rotation.y += (targetRHandRot.y - rightHandGroup.rotation.y) * 0.15;
            rightHandGroup.rotation.z += (targetRHandRot.z - rightHandGroup.rotation.z) * 0.15;

            // 5. Solve 2-Bone IK untuk Siku & Lengan
            const leftElbowPos = solve2BoneIK(SHOULDER_L, currentLHand, L1, L2, bendRefL);
            const rightElbowPos = solve2BoneIK(SHOULDER_R, currentRHand, L1, L2, bendRefR);

            leftElbowMesh.position.copy(leftElbowPos);
            rightElbowMesh.position.copy(rightElbowPos);

            updateLimbSegment(leftUpperArmMesh, SHOULDER_L, leftElbowPos);
            updateLimbSegment(leftForearmMesh, leftElbowPos, currentLHand);

            updateLimbSegment(rightUpperArmMesh, SHOULDER_R, rightElbowPos);
            updateLimbSegment(rightForearmMesh, rightElbowPos, currentRHand);

            // 6. Pernafasan Halus (Breathing Motion)
            const breathing = Math.sin(elapsedTime * 2.2) * 0.012;
            torso.position.y = 0.32 + breathing;
            idCard.position.y = 0.35 + breathing;

            renderer.render(scene, camera);
        }

        renderLoop();

        // Responsive Resize
        window.addEventListener('resize', function () {
            const newW = canvasEl.clientWidth || 260;
            const newH = canvasEl.clientHeight || 200;
            camera.aspect = newW / newH;
            camera.updateProjectionMatrix();
            renderer.setSize(newW, newH);
        });

        // Theme Switch Support (Dark / Light Mode)
        window.addEventListener('themeChanged', function (e) {
            const isDark = e.detail.theme === 'dark';
            orangeRimLight.intensity = isDark ? 1.5 : 1.0;
            blueFillLight.intensity = isDark ? 0.9 : 0.5;
            ambientLight.intensity = isDark ? 0.75 : 0.95;
        });
    }
});
