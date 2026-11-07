/**
 * ASLAB REPORT - 3D INTERACTIVE LOGIN AVATAR (THREE.JS)
 * Karakter 3D Asisten Lab UNPAM (Baju PDH Oranye) dengan respon interaktif:
 * - IDLE: Bernafas, kedip sesekali, kepala mengikuti kursor mouse
 * - USERNAME FOCUS: Mencondongkan kepala, melirik ke bawah, tangan mengintip penasaran
 * - PASSWORD FOCUS: Kedua tangan menutup kedua mata ("Eits, rahasia gak boleh diintip!")
 * - SHOW PASSWORD (EYE TOGGLE): Mengintip lewat celah jari dengan 1 mata terbuka lebar
 * - LOGIN ERROR: Menggelengkan kepala dengan ekspresi kaget (Oops!)
 * - LOGIN SUCCESS: Tersenyum lebar & mengacungkan jempol
 */

document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('login-avatar-canvas');
    if (!canvas || typeof THREE === 'undefined') return;

    initLoginAvatar(canvas);

    function initLoginAvatar(canvasEl) {
        // 1. Scene, Camera, Renderer
        const scene = new THREE.Scene();
        const width = canvasEl.clientWidth || 240;
        const height = canvasEl.clientHeight || 190;

        const camera = new THREE.PerspectiveCamera(40, width / height, 0.1, 50);
        camera.position.set(0, 0.5, 4.6);

        const renderer = new THREE.WebGLRenderer({ canvas: canvasEl, alpha: true, antialias: true });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;

        // 2. Lighting Setup
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.85);
        scene.add(ambientLight);

        const dirLight = new THREE.DirectionalLight(0xffffff, 0.95);
        dirLight.position.set(2.5, 4.5, 3.5);
        scene.add(dirLight);

        // Warm orange rim light (khas almamater UNPAM)
        const orangeRimLight = new THREE.PointLight(0xf97316, 1.3, 8);
        orangeRimLight.position.set(-2.2, 1.8, 1.8);
        scene.add(orangeRimLight);

        const blueFillLight = new THREE.PointLight(0x38bdf8, 0.7, 8);
        blueFillLight.position.set(2.2, -0.8, 1.8);
        scene.add(blueFillLight);

        // 3. Root Group
        const characterGroup = new THREE.Group();
        characterGroup.position.set(0, -0.75, 0);
        scene.add(characterGroup);

        // Texture Baju PDH Oranye UNPAM
        function createPdhTexture() {
            const cvs = document.createElement('canvas');
            cvs.width = 512;
            cvs.height = 512;
            const ctx = cvs.getContext('2d');

            ctx.fillStyle = '#ea580c';
            ctx.fillRect(0, 0, 512, 512);

            // Placket Kancing Tengah
            ctx.fillStyle = '#c2410c';
            ctx.fillRect(240, 60, 32, 452);

            // Kancing Putih
            ctx.fillStyle = '#ffffff';
            for (let y = 120; y < 480; y += 75) {
                ctx.beginPath();
                ctx.arc(256, y, 6, 0, Math.PI * 2);
                ctx.fill();
            }

            // Kantong Dada Kiri: "ASLAB"
            ctx.fillStyle = '#c2410c';
            ctx.fillRect(100, 160, 100, 90);
            ctx.fillStyle = '#0f172a';
            ctx.fillRect(110, 140, 80, 24);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 15px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('ASLAB', 150, 157);

            // Kantong Dada Kanan: "UNPAM"
            ctx.fillStyle = '#c2410c';
            ctx.fillRect(312, 160, 100, 90);
            ctx.fillStyle = '#1e3a8a';
            ctx.fillRect(322, 140, 80, 24);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 14px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('UNPAM', 362, 157);

            // Garis Kerah
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
            ctx.font = 'bold 18px sans-serif';
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
        // BUILD BODY, HEAD & ARTICULATED ARMS
        // ----------------------------------------------------

        // Torso / Badan
        const pdhTexture = createPdhTexture();
        const torsoMat = new THREE.MeshStandardMaterial({ map: pdhTexture, roughness: 0.6, metalness: 0.05 });
        const torso = new THREE.Mesh(new THREE.CylinderGeometry(0.68, 0.62, 1.5, 24), torsoMat);
        torso.position.y = 0.75;
        characterGroup.add(torso);

        // Shoulder Epaulets (Pangkat Pundak)
        const epauletMat = new THREE.MeshStandardMaterial({ color: 0x9a3412, roughness: 0.5 });
        const leftEpaulet = new THREE.Mesh(new THREE.BoxGeometry(0.4, 0.08, 0.2), epauletMat);
        leftEpaulet.position.set(-0.62, 1.45, 0);
        leftEpaulet.rotation.z = 0.15;
        characterGroup.add(leftEpaulet);

        const rightEpaulet = new THREE.Mesh(new THREE.BoxGeometry(0.4, 0.08, 0.2), epauletMat);
        rightEpaulet.position.set(0.62, 1.45, 0);
        rightEpaulet.rotation.z = -0.15;
        characterGroup.add(rightEpaulet);

        // Lanyard & ID Card
        const lanyardMat = new THREE.MeshStandardMaterial({ color: 0x2563eb, roughness: 0.4 });
        const lanyard = new THREE.Mesh(new THREE.TorusGeometry(0.5, 0.025, 8, 24, Math.PI), lanyardMat);
        lanyard.position.set(0, 1.45, 0.25);
        lanyard.rotation.x = Math.PI / 2.3;
        characterGroup.add(lanyard);

        const idCardMat = new THREE.MeshStandardMaterial({ map: createIdCardTexture(), roughness: 0.3 });
        const idCard = new THREE.Mesh(new THREE.BoxGeometry(0.38, 0.52, 0.02), idCardMat);
        idCard.position.set(0, 0.82, 0.68);
        idCard.rotation.x = 0.08;
        characterGroup.add(idCard);

        // Head Group (Pivot Leher)
        const headGroup = new THREE.Group();
        headGroup.position.set(0, 1.55, 0);
        characterGroup.add(headGroup);

        const skinMat = new THREE.MeshStandardMaterial({ color: 0xfbd0b1, roughness: 0.65 });
        const neck = new THREE.Mesh(new THREE.CylinderGeometry(0.22, 0.26, 0.32, 16), skinMat);
        neck.position.y = 0.14;
        headGroup.add(neck);

        // Kepala
        const head = new THREE.Mesh(new THREE.SphereGeometry(0.55, 32, 32), skinMat);
        head.position.y = 0.62;
        headGroup.add(head);

        // Rambut Modern
        const hairMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.85 });
        const hairTop = new THREE.Mesh(new THREE.SphereGeometry(0.58, 24, 24, 0, Math.PI * 2, 0, Math.PI / 1.7), hairMat);
        hairTop.position.set(0, 0.68, -0.04);
        headGroup.add(hairTop);

        // Jambul Rambut Depan
        const bangs = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.18, 0.22), hairMat);
        bangs.position.set(0, 1.08, 0.42);
        bangs.rotation.x = 0.3;
        headGroup.add(bangs);

        // Kacamata Tech Modern (UNPAM Blue Frames)
        const frameMat = new THREE.MeshStandardMaterial({ color: 0x0284c7, metalness: 0.8, roughness: 0.2 });
        const glassMat = new THREE.MeshStandardMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.4 });

        const leftGlass = new THREE.Mesh(new THREE.BoxGeometry(0.24, 0.17, 0.03), glassMat);
        leftGlass.position.set(-0.22, 0.65, 0.52);
        headGroup.add(leftGlass);

        const rightGlass = new THREE.Mesh(new THREE.BoxGeometry(0.24, 0.17, 0.03), glassMat);
        rightGlass.position.set(0.22, 0.65, 0.52);
        headGroup.add(rightGlass);

        const bridge = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.025, 0.025), frameMat);
        bridge.position.set(0, 0.65, 0.53);
        headGroup.add(bridge);

        // Mata & Pupil
        const eyeWhiteMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
        const pupilMat = new THREE.MeshBasicMaterial({ color: 0x0f172a });

        const leftEye = new THREE.Mesh(new THREE.SphereGeometry(0.075, 16, 16), eyeWhiteMat);
        leftEye.position.set(-0.21, 0.65, 0.46);
        headGroup.add(leftEye);

        const leftPupil = new THREE.Mesh(new THREE.SphereGeometry(0.042, 12, 12), pupilMat);
        leftPupil.position.set(-0.21, 0.65, 0.52);
        headGroup.add(leftPupil);

        const rightEye = new THREE.Mesh(new THREE.SphereGeometry(0.075, 16, 16), eyeWhiteMat);
        rightEye.position.set(0.21, 0.65, 0.46);
        headGroup.add(rightEye);

        const rightPupil = new THREE.Mesh(new THREE.SphereGeometry(0.042, 12, 12), pupilMat);
        rightPupil.position.set(0.21, 0.65, 0.52);
        headGroup.add(rightPupil);

        // Kelopak Mata (Eyelids untuk Animasi Kedip & Tutup Mata)
        const eyelidMat = new THREE.MeshStandardMaterial({ color: 0xf4b994, roughness: 0.6 });
        const leftEyelid = new THREE.Mesh(new THREE.SphereGeometry(0.082, 16, 16, 0, Math.PI * 2, 0, Math.PI / 2), eyelidMat);
        leftEyelid.position.set(-0.21, 0.65, 0.46);
        leftEyelid.rotation.x = -Math.PI / 2; // Mulai terbuka
        leftEyelid.scale.set(1, 0.05, 1);
        headGroup.add(leftEyelid);

        const rightEyelid = new THREE.Mesh(new THREE.SphereGeometry(0.082, 16, 16, 0, Math.PI * 2, 0, Math.PI / 2), eyelidMat);
        rightEyelid.position.set(0.21, 0.65, 0.46);
        rightEyelid.rotation.x = -Math.PI / 2;
        rightEyelid.scale.set(1, 0.05, 1);
        headGroup.add(rightEyelid);

        // Senyum Ramah
        const smileMat = new THREE.MeshBasicMaterial({ color: 0xd97706 });
        const smile = new THREE.Mesh(new THREE.TorusGeometry(0.11, 0.018, 8, 16, Math.PI), smileMat);
        smile.position.set(0, 0.46, 0.5);
        smile.rotation.x = Math.PI;
        headGroup.add(smile);

        // Mulut "O" (Ekspresi Kaget / Error)
        const mouthOMat = new THREE.MeshBasicMaterial({ color: 0x991b1b });
        const mouthO = new THREE.Mesh(new THREE.TorusGeometry(0.07, 0.02, 8, 16), mouthOMat);
        mouthO.position.set(0, 0.44, 0.5);
        mouthO.visible = false;
        headGroup.add(mouthO);

        // ----------------------------------------------------
        // ARTICULATED ARMS (Lengan dengan Sendi Bahu & Siku)
        // ----------------------------------------------------
        const sleeveMat = new THREE.MeshStandardMaterial({ color: 0xea580c, roughness: 0.6 });

        // LENGAN KIRI (Left Arm Pivot di Bahu)
        const leftShoulderPivot = new THREE.Group();
        leftShoulderPivot.position.set(-0.65, 1.45, 0.05);
        characterGroup.add(leftShoulderPivot);

        const leftUpperArm = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.13, 0.55, 16), sleeveMat);
        leftUpperArm.position.y = -0.28;
        leftShoulderPivot.add(leftUpperArm);

        const leftElbowPivot = new THREE.Group();
        leftElbowPivot.position.set(0, -0.55, 0);
        leftShoulderPivot.add(leftElbowPivot);

        const leftForearm = new THREE.Mesh(new THREE.CylinderGeometry(0.13, 0.11, 0.5, 16), skinMat);
        leftForearm.position.y = -0.25;
        leftElbowPivot.add(leftForearm);

        // Telapak Tangan Kiri
        const leftHand = new THREE.Mesh(new THREE.BoxGeometry(0.24, 0.28, 0.08), skinMat);
        leftHand.position.set(0, -0.55, 0);
        leftElbowPivot.add(leftHand);

        // LENGAN KANAN (Right Arm Pivot di Bahu)
        const rightShoulderPivot = new THREE.Group();
        rightShoulderPivot.position.set(0.65, 1.45, 0.05);
        characterGroup.add(rightShoulderPivot);

        const rightUpperArm = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.13, 0.55, 16), sleeveMat);
        rightUpperArm.position.y = -0.28;
        rightShoulderPivot.add(rightUpperArm);

        const rightElbowPivot = new THREE.Group();
        rightElbowPivot.position.set(0, -0.55, 0);
        rightShoulderPivot.add(rightElbowPivot);

        const rightForearm = new THREE.Mesh(new THREE.CylinderGeometry(0.13, 0.11, 0.5, 16), skinMat);
        rightForearm.position.y = -0.25;
        rightElbowPivot.add(rightForearm);

        // Telapak Tangan Kanan
        const rightHand = new THREE.Mesh(new THREE.BoxGeometry(0.24, 0.28, 0.08), skinMat);
        rightHand.position.set(0, -0.55, 0);
        rightElbowPivot.add(rightHand);

        // Jempol Kanan (Thumbs Up Pose)
        const thumbGeo = new THREE.CylinderGeometry(0.04, 0.035, 0.16, 12);
        const thumbMesh = new THREE.Mesh(thumbGeo, skinMat);
        thumbMesh.position.set(-0.12, 0.08, 0.02);
        thumbMesh.rotation.z = Math.PI / 4;
        thumbMesh.visible = false;
        rightHand.add(thumbMesh);

        // ----------------------------------------------------
        // STATE MANAGEMENT & INTERACTION TARGETS
        // ----------------------------------------------------
        // States: 'IDLE', 'USERNAME', 'PASSWORD', 'SNEAK_PEEK', 'ERROR', 'SUCCESS'
        let currentState = 'IDLE';
        let isPasswordVisible = false;

        // Target rotasi & posisi untuk LERP smooth transition
        const targets = {
            headRotX: 0,
            headRotY: 0,
            headRotZ: 0,
            headPosY: 1.55,
            headPosZ: 0,
            pupilY: 0.65,
            pupilZ: 0.52,
            pupilScale: 1.0,
            leftEyelidScaleY: 0.05,
            rightEyelidScaleY: 0.05,
            // Left Arm
            lShoulderRotX: 0.35,
            lShoulderRotY: 0.1,
            lShoulderRotZ: 0.3,
            lElbowRotX: 0.4,
            lElbowRotY: 0,
            lElbowRotZ: 0,
            // Right Arm
            rShoulderRotX: 0.35,
            rShoulderRotY: -0.1,
            rShoulderRotZ: -0.3,
            rElbowRotX: 0.4,
            rElbowRotY: 0,
            rElbowRotZ: 0
        };

        // DOM Inputs & Listeners
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('togglePasswordBtn');
        const loginForm = document.querySelector('form');
        const errorAlert = document.querySelector('.alert-danger');

        // 1. EVENT: FOCUS USERNAME (Curious / Peeking State)
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

            // Efek membaca karakter saat mengetik username
            usernameInput.addEventListener('input', function () {
                if (currentState === 'USERNAME') {
                    targets.pupilY = 0.63 + (Math.random() * 0.02 - 0.01);
                }
            });
        }

        // 2. EVENT: FOCUS PASSWORD (Cover Eyes State)
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

        // 3. EVENT: TOGGLE PASSWORD VISIBILITY (Sneak Peek State)
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

        // 4. EVENT: SUBMIT (Success Thumbs Up)
        if (loginForm) {
            loginForm.addEventListener('submit', function () {
                currentState = 'SUCCESS';
            });
        }

        // 5. EVENT: ERROR DETECTED ON PAGE LOAD
        let errorTimer = 0;
        if (errorAlert) {
            currentState = 'ERROR';
            errorTimer = 2.8; // Goyang kepala selama 2.8 detik
        }

        // Mouse tracking saat IDLE
        let mouseX = 0;
        let mouseY = 0;
        window.addEventListener('mousemove', function (e) {
            const rect = canvasEl.getBoundingClientRect();
            mouseX = e.clientX - (rect.left + rect.width / 2);
            mouseY = e.clientY - (rect.top + rect.height / 2);
        });

        // ----------------------------------------------------
        // ANIMATION LOOP & SMOOTH INTERPOLATION (LERP)
        // ----------------------------------------------------
        let clock = new THREE.Clock();
        let blinkTimer = 0;
        let isBlinking = false;

        function updateStateParameters(elapsedTime, delta) {
            switch (currentState) {
                case 'IDLE':
                    // Kepala mengikuti arah kursor mouse
                    targets.headRotY = (mouseX / (window.innerWidth / 2)) * 0.45;
                    targets.headRotX = (mouseY / (window.innerHeight / 2)) * 0.35;
                    targets.headRotZ = 0;
                    targets.headPosY = 1.55;
                    targets.headPosZ = 0;

                    targets.pupilY = 0.65;
                    targets.pupilZ = 0.52;
                    targets.pupilScale = 1.0;

                    // Posisi Tangan Rileks di Samping Dada
                    targets.lShoulderRotX = 0.35;
                    targets.lShoulderRotY = 0.1;
                    targets.lShoulderRotZ = 0.3;
                    targets.lElbowRotX = 0.4;

                    targets.rShoulderRotX = 0.35;
                    targets.rShoulderRotY = -0.1;
                    targets.rShoulderRotZ = -0.3;
                    targets.rElbowRotX = 0.4;

                    smile.visible = true;
                    mouthO.visible = false;
                    thumbMesh.visible = false;
                    break;

                case 'USERNAME':
                    // Karakter mencondongkan badan ke depan & melirik ke bawah (Curious / Peeking)
                    targets.headPosY = 1.52;
                    targets.headPosZ = 0.18;
                    targets.headRotX = 0.28;
                    targets.headRotY = -0.12;
                    targets.headRotZ = 0.08;

                    // Melirik ke arah kolom username
                    targets.pupilY = 0.62;
                    targets.pupilZ = 0.53;
                    targets.pupilScale = 1.05;

                    // Tangan kanan naik sedikit ke pipi/dagu dengan gaya berpikir/ngintip
                    targets.rShoulderRotX = 1.5;
                    targets.rShoulderRotY = -0.35;
                    targets.rShoulderRotZ = -0.45;
                    targets.rElbowRotX = 1.25;

                    // Tangan kiri rileks
                    targets.lShoulderRotX = 0.3;
                    targets.lShoulderRotY = 0.1;
                    targets.lShoulderRotZ = 0.25;
                    targets.lElbowRotX = 0.3;

                    smile.visible = true;
                    mouthO.visible = false;
                    thumbMesh.visible = false;
                    break;

                case 'PASSWORD':
                    // KEDUA TANGAN MENUTUPI KEDUA MATA ("Eits, rahasia gak boleh diintip!")
                    targets.headRotX = 0.12;
                    targets.headRotY = 0.16; // Menoleh sedikit karena malu
                    targets.headRotZ = -0.05;
                    targets.headPosY = 1.55;
                    targets.headPosZ = 0;

                    // Lengan Kiri Naik Menutupi Mata Kiri
                    targets.lShoulderRotX = 1.85;
                    targets.lShoulderRotY = 0.6;
                    targets.lShoulderRotZ = 0.35;
                    targets.lElbowRotX = 1.45;

                    // Lengan Kanan Naik Menutupi Mata Kanan
                    targets.rShoulderRotX = 1.85;
                    targets.rShoulderRotY = -0.6;
                    targets.rShoulderRotZ = -0.35;
                    targets.rElbowRotX = 1.45;

                    // Tutup kedua mata sepenuhnya
                    targets.leftEyelidScaleY = 1.0;
                    targets.rightEyelidScaleY = 1.0;

                    smile.visible = true;
                    mouthO.visible = false;
                    thumbMesh.visible = false;
                    break;

                case 'SNEAK_PEEK':
                    // SNEAK PEEK: Buka celah mengintip dengan satu mata terbuka lebar!
                    targets.headRotX = 0.25;
                    targets.headRotY = -0.15;
                    targets.headRotZ = 0.1;
                    targets.headPosY = 1.52;
                    targets.headPosZ = 0.12;

                    // Tangan kanan tetap menutup mata kanan
                    targets.rShoulderRotX = 1.85;
                    targets.rShoulderRotY = -0.6;
                    targets.rShoulderRotZ = -0.35;
                    targets.rElbowRotX = 1.45;
                    targets.rightEyelidScaleY = 1.0; // Mata kanan tetap tertutup

                    // Tangan kiri turun sedikit membuka celah mengintip!
                    targets.lShoulderRotX = 1.4;
                    targets.lShoulderRotY = 0.35;
                    targets.lShoulderRotZ = 0.55;
                    targets.lElbowRotX = 0.85;

                    // Mata kiri terbuka lebar melirik ke password!
                    targets.leftEyelidScaleY = 0.05;
                    targets.pupilY = 0.62;
                    targets.pupilZ = 0.54;
                    targets.pupilScale = 1.25;

                    smile.visible = true;
                    mouthO.visible = false;
                    thumbMesh.visible = false;
                    break;

                case 'ERROR':
                    // Kaget & Menggelengkan Kepala (Oops!)
                    errorTimer -= delta;
                    targets.headRotY = Math.sin(elapsedTime * 18) * 0.32; // Goyang kepala cepat
                    targets.headRotX = -0.1;
                    targets.headRotZ = 0;
                    targets.headPosY = 1.55;
                    targets.headPosZ = 0;

                    // Tangan turun lemas di samping
                    targets.lShoulderRotX = 0.15;
                    targets.lShoulderRotZ = 0.45;
                    targets.rShoulderRotX = 0.15;
                    targets.rShoulderRotZ = -0.45;

                    targets.leftEyelidScaleY = 0.05;
                    targets.rightEyelidScaleY = 0.05;
                    targets.pupilScale = 1.35; // Mata melotot kaget

                    smile.visible = false;
                    mouthO.visible = true; // Mulut "O" terkejut
                    thumbMesh.visible = false;

                    if (errorTimer <= 0) {
                        currentState = 'IDLE';
                    }
                    break;

                case 'SUCCESS':
                    // TERSENYUM LEBAR & MENGACUNGKAN JEMPOL (Thumbs up!)
                    targets.headRotX = -0.05 + Math.sin(elapsedTime * 6) * 0.1; // Mengangguk gembira
                    targets.headRotY = 0;
                    targets.headRotZ = 0;
                    targets.headPosY = 1.55;

                    // Lengan Kanan Acungkan Jempol ke Depan
                    targets.rShoulderRotX = 1.25;
                    targets.rShoulderRotY = -0.2;
                    targets.rShoulderRotZ = -0.15;
                    targets.rElbowRotX = 0.95;

                    // Lengan Kiri Santai
                    targets.lShoulderRotX = 0.4;
                    targets.lShoulderRotZ = 0.3;

                    targets.leftEyelidScaleY = 0.05;
                    targets.rightEyelidScaleY = 0.05;
                    smile.visible = true;
                    mouthO.visible = false;
                    thumbMesh.visible = true;
                    break;
            }

            // Animasi Kedip Otomatis (Hanya saat mata tidak sedang tertutup di state PASSWORD)
            if (currentState !== 'PASSWORD') {
                blinkTimer += delta;
                if (blinkTimer > 3.5 && !isBlinking) {
                    isBlinking = true;
                }
                if (isBlinking) {
                    if (currentState !== 'SNEAK_PEEK') {
                        targets.rightEyelidScaleY = 1.0;
                    }
                    targets.leftEyelidScaleY = 1.0;

                    if (blinkTimer > 3.7) {
                        isBlinking = false;
                        blinkTimer = 0;
                        if (currentState !== 'SNEAK_PEEK') {
                            targets.rightEyelidScaleY = 0.05;
                        }
                        targets.leftEyelidScaleY = 0.05;
                    }
                }
            }
        }

        function renderLoop() {
            requestAnimationFrame(renderLoop);

            const delta = clock.getDelta();
            const elapsedTime = clock.getElapsedTime();

            updateStateParameters(elapsedTime, delta);

            // 1. Lerp Rotasi & Posisi Kepala
            headGroup.rotation.y += (targets.headRotY - headGroup.rotation.y) * 0.1;
            headGroup.rotation.x += (targets.headRotX - headGroup.rotation.x) * 0.1;
            headGroup.rotation.z += (targets.headRotZ - headGroup.rotation.z) * 0.1;
            headGroup.position.y += (targets.headPosY - headGroup.position.y) * 0.1;
            headGroup.position.z += (targets.headPosZ - headGroup.position.z) * 0.1;

            // 2. Lerp Pupils & Eyelids
            leftPupil.position.y += (targets.pupilY - leftPupil.position.y) * 0.15;
            leftPupil.position.z += (targets.pupilZ - leftPupil.position.z) * 0.15;
            rightPupil.position.y += (targets.pupilY - rightPupil.position.y) * 0.15;
            rightPupil.position.z += (targets.pupilZ - rightPupil.position.z) * 0.15;

            leftPupil.scale.set(targets.pupilScale, targets.pupilScale, targets.pupilScale);
            rightPupil.scale.set(targets.pupilScale, targets.pupilScale, targets.pupilScale);

            leftEyelid.scale.y += (targets.leftEyelidScaleY - leftEyelid.scale.y) * 0.2;
            rightEyelid.scale.y += (targets.rightEyelidScaleY - rightEyelid.scale.y) * 0.2;

            // 3. Lerp Lengan Kiri
            leftShoulderPivot.rotation.x += (targets.lShoulderRotX - leftShoulderPivot.rotation.x) * 0.1;
            leftShoulderPivot.rotation.y += (targets.lShoulderRotY - leftShoulderPivot.rotation.y) * 0.1;
            leftShoulderPivot.rotation.z += (targets.lShoulderRotZ - leftShoulderPivot.rotation.z) * 0.1;
            leftElbowPivot.rotation.x += (targets.lElbowRotX - leftElbowPivot.rotation.x) * 0.1;

            // 4. Lerp Lengan Kanan
            rightShoulderPivot.rotation.x += (targets.rShoulderRotX - rightShoulderPivot.rotation.x) * 0.1;
            rightShoulderPivot.rotation.y += (targets.rShoulderRotY - rightShoulderPivot.rotation.y) * 0.1;
            rightShoulderPivot.rotation.z += (targets.rShoulderRotZ - rightShoulderPivot.rotation.z) * 0.1;
            rightElbowPivot.rotation.x += (targets.rElbowRotX - rightElbowPivot.rotation.x) * 0.1;

            // 5. Breathing Motion Halus
            const breathing = Math.sin(elapsedTime * 2.2) * 0.025;
            torso.position.y = 0.75 + breathing;
            idCard.position.y = 0.82 + breathing;

            renderer.render(scene, camera);
        }

        renderLoop();

        // Responsive Resize
        window.addEventListener('resize', function () {
            const newW = canvasEl.clientWidth || 240;
            const newH = canvasEl.clientHeight || 190;
            camera.aspect = newW / newH;
            camera.updateProjectionMatrix();
            renderer.setSize(newW, newH);
        });

        // Theme switch listener
        window.addEventListener('themeChanged', function (e) {
            const isDark = e.detail.theme === 'dark';
            orangeRimLight.intensity = isDark ? 1.5 : 1.0;
            blueFillLight.intensity = isDark ? 0.9 : 0.5;
            ambientLight.intensity = isDark ? 0.7 : 0.95;
        });
    }
});
