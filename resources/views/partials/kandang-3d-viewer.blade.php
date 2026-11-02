{{-- =========================================================================
     DIGITAL TWIN 3D VIEWER: SIMULASI INTERAKTIF KANDANG AYAM PINTAR
     Three.js WebGL Engine (60 FPS, OrbitControls 360°, Particle Effects)
     Inovator: Aditya Ramadhani (SMKN 2 Surabaya) - RansumKu
     Fitur: Kereta Hopper Katrol L298N, Sirkulasi Blower, Misting Atap, Ayam 3D
     ========================================================================= --}}

<div class="bg-white rounded-2xl border border-line p-5 md:p-6 shadow-2xs space-y-4" id="section-digital-twin-3d">
    {{-- Header Digital Twin 3D --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-line">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 grid place-items-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined text-[22px]">view_in_ar</span>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-sm font-black uppercase tracking-wider text-ink">Digital Twin 3D Kandang Pintar</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800 border border-purple-200 uppercase">
                        WebGL 3D Engine · Live Interactive
                    </span>
                </div>
                <p class="text-[11px] text-muted">Simulasi fisik interaktif 3D: Kereta pakan katrol tali sling meluncur di rel, corong pakan 2 tingkat (kanan & kiri), ayam baterai, blower, dan sprinkler atap</p>
            </div>
        </div>

        {{-- Preset Tombol Kamera 3D --}}
        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button" onclick="setKamera3D('perspektif')" class="px-3 py-1.5 rounded-lg text-[11px] font-bold bg-surface hover:bg-surface-2 text-ink border border-line transition shadow-2xs cursor-pointer flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">videocam</span>
                <span>Perspektif</span>
            </button>
            <button type="button" onclick="setKamera3D('lorong')" class="px-3 py-1.5 rounded-lg text-[11px] font-bold bg-surface hover:bg-surface-2 text-ink border border-line transition shadow-2xs cursor-pointer flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">straighten</span>
                <span>Lorong Pakan</span>
            </button>
            <button type="button" onclick="setKamera3D('atas')" class="px-3 py-1.5 rounded-lg text-[11px] font-bold bg-surface hover:bg-surface-2 text-ink border border-line transition shadow-2xs cursor-pointer flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">vertical_align_top</span>
                <span>Tampak Atas</span>
            </button>
            <button type="button" onclick="toggleAutoRotate3D()" id="btn-autorotate-3d" class="px-3 py-1.5 rounded-lg text-[11px] font-bold bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 transition shadow-2xs cursor-pointer flex items-center gap-1" title="Putar Otomatis 360 Derajat">
                <span class="material-symbols-outlined text-[15px]">360</span>
                <span>Auto-Putar</span>
            </button>
        </div>
    </div>

    {{-- Area Canvas 3D Viewport --}}
    <div class="relative w-full h-[420px] md:h-[480px] rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 shadow-inner">
        {{-- Canvas Container --}}
        <div id="canvas-container-3d" class="w-full h-full cursor-grab active:cursor-grabbing"></div>

        {{-- Overlay Loading State (jika CDN perlu waktu sejenak) --}}
        <div id="loader-3d" class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center gap-3 text-slate-300 z-10 transition-opacity duration-300">
            <span class="material-symbols-outlined text-4xl text-purple-400 animate-spin">cyclone</span>
            <span class="text-xs font-bold tracking-wider uppercase text-purple-300">Memuat Model 3D Digital Twin...</span>
        </div>

        {{-- Overlay Badge Status 3D Real-time (Pojok Kiri Atas) --}}
        <div class="absolute top-3 left-3 flex flex-col gap-2 pointer-events-none z-20">
            {{-- Status Kereta Pakan --}}
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900/90 backdrop-blur border border-slate-700/80 text-white shadow-lg text-xs font-bold">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse" id="dot-feeder-3d"></span>
                <span class="text-slate-300 font-normal">Kereta Pakan:</span>
                <span class="text-emerald-300" id="status-feeder-3d">Siaga di Pangkal (Titik A)</span>
            </div>

            {{-- Status Kipas & Sprinkler --}}
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-slate-900/80 backdrop-blur border border-slate-800 text-[11px] font-semibold text-slate-300">
                <span class="flex items-center gap-1" id="badge-kipas-3d">
                    <span class="material-symbols-outlined text-[14px] text-slate-400" id="icon-fan-3d">mode_fan</span>
                    <span id="text-fan-3d">Kipas OFF</span>
                </span>
                <span class="text-slate-600">•</span>
                <span class="flex items-center gap-1" id="badge-atap-3d">
                    <span class="material-symbols-outlined text-[14px] text-slate-400" id="icon-roof-3d">water_drop</span>
                    <span id="text-roof-3d">Sprinkler OFF</span>
                </span>
            </div>
        </div>

        {{-- Tombol Kontrol Cepat Interaktif (Pojok Kanan Atas) --}}
        <div class="absolute top-3 right-3 flex items-center gap-2 z-20">
            <button type="button" onclick="triggerSimulasiGerakPakan()" id="btn-trigger-pakan-3d" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                <span class="material-symbols-outlined text-[16px]">play_arrow</span>
                <span>Jalankan Kereta 3D</span>
            </button>
            <button type="button" onclick="toggleUjiCuacaPanas3D()" id="btn-cuaca-3d" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800/90 hover:bg-slate-700 text-amber-300 border border-slate-700 shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer" title="Simulasikan cuaca panas untuk menyalakan Kipas & Sprinkler">
                <span class="material-symbols-outlined text-[16px]">thermostat</span>
                <span>Simulasi Panas</span>
            </button>
        </div>

        {{-- Petunjuk Navigasi Kamera & Spek Mekanik (Pojok Bawah) --}}
        <div class="absolute bottom-3 inset-x-3 flex items-center justify-between pointer-events-none text-[10px] text-slate-400 px-2 z-20">
            <span class="bg-slate-900/80 px-2.5 py-1 rounded-md backdrop-blur border border-slate-800 flex items-center gap-1.5">
                <span>🖱️ <strong>Klik & Geser</strong> putar 360°</span>
                <span class="text-slate-600">•</span>
                <span><strong>Scroll</strong> zoom</span>
                <span class="text-slate-600">•</span>
                <span><strong>Klik Kanan</strong> geser posisi</span>
            </span>
            <span class="hidden sm:inline bg-slate-900/80 px-2.5 py-1 rounded-md backdrop-blur border border-slate-800 font-mono text-emerald-400">
                Katrol Tali Sling · L298N · 60 FPS
            </span>
        </div>
    </div>
</div>

{{-- Script Three.js & OrbitControls dari CDN Resmi --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

<script>
let scene3D, camera3D, renderer3D, controls3D;
let feederCart3D, roofWaterParticles3D;
let fanBladesList = [];
let feedP_L_T1, feedP_L_T2, feedP_R_T1, feedP_R_T2;
let coopLight1, coopLight2;
let cableMeshA, cableMeshB;
let chickensList = [];

// Status & Variabel Animasi 3D
let isFeederRunning3D = false;
let feederDirection3D = 1; // 1 = Maju (A -> B), -1 = Mundur (B -> A)
let feederPositionX = -7.0; // Dari x = -7.0 (Titik A) ke x = 7.0 (Titik B)
const FEEDER_LIMIT_A = -7.0;
const FEEDER_LIMIT_B = 7.0;
let isFanSpinning3D = false;
let isSprinklerActive3D = false;
let chickenBobTime = 0;

function initThreeJSKandang() {
    const container = document.getElementById('canvas-container-3d');
    const loader = document.getElementById('loader-3d');
    if (!container) return;

    if (typeof THREE === 'undefined') {
        setTimeout(initThreeJSKandang, 150);
        return;
    }

    const width = container.clientWidth;
    const height = container.clientHeight;

    // 1. Scene
    scene3D = new THREE.Scene();
    scene3D.background = new THREE.Color(0x0a0f1d);
    scene3D.fog = new THREE.FogExp2(0x0a0f1d, 0.022);

    // 2. Camera
    camera3D = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
    camera3D.position.set(13, 9, 14);

    // 3. Renderer
    renderer3D = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer3D.setSize(width, height);
    renderer3D.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer3D.shadowMap.enabled = true;
    renderer3D.shadowMap.type = THREE.PCFSoftShadowMap;
    container.innerHTML = ''; // Pastikan bersih
    container.appendChild(renderer3D.domElement);

    // 4. OrbitControls
    if (typeof THREE.OrbitControls !== 'undefined') {
        controls3D = new THREE.OrbitControls(camera3D, renderer3D.domElement);
        controls3D.enableDamping = true;
        controls3D.dampingFactor = 0.05;
        controls3D.maxPolarAngle = Math.PI / 2 + 0.05; // Tidak tembus tanah
        controls3D.minDistance = 4;
        controls3D.maxDistance = 35;
        controls3D.autoRotate = false;
        controls3D.autoRotateSpeed = 1.0;
        controls3D.target.set(0, 2, 0);
    }

    // 5. Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.65);
    scene3D.add(ambientLight);

    const dirLight = new THREE.DirectionalLight(0xfffaed, 0.9);
    dirLight.position.set(15, 20, 10);
    dirLight.castShadow = true;
    dirLight.shadow.mapSize.width = 1024;
    dirLight.shadow.mapSize.height = 1024;
    scene3D.add(dirLight);

    // Lampu Sorot Dalam Kandang (Kuning Hangat / Fotoperiode Ayam)
    coopLight1 = new THREE.PointLight(0xffe6a3, 1.3, 20);
    coopLight1.position.set(-3, 3.8, 0);
    scene3D.add(coopLight1);

    coopLight2 = new THREE.PointLight(0xffe6a3, 1.3, 20);
    coopLight2.position.set(3, 3.8, 0);
    scene3D.add(coopLight2);

    // 6. Bangun Objek Geometri Kandang Ayam 3D
    buildKandang3DScene();

    // 7. Event Resize
    window.addEventListener('resize', onWindowResize3D);

    // Hilangkan loader
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => loader.style.display = 'none', 300);
    }

    // 8. Mulai Animasi Loop
    animateThreeJS();
}

function createStylizedChicken() {
    const chicken = new THREE.Group();

    // Badan Ayam Petelur (Putih Bulu)
    const bodyGeo = new THREE.SphereGeometry(0.24, 12, 10);
    bodyGeo.scale(1, 0.85, 1.25);
    const bodyMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.85 });
    const body = new THREE.Mesh(bodyGeo, bodyMat);
    body.position.y = 0.22;
    chicken.add(body);

    // Kepala Ayam
    const headGeo = new THREE.SphereGeometry(0.12, 10, 8);
    const head = new THREE.Mesh(headGeo, bodyMat);
    head.position.set(0, 0.38, 0.16);
    chicken.add(head);

    // Jengger Merah (Comb)
    const combGeo = new THREE.BoxGeometry(0.04, 0.1, 0.14);
    const combMat = new THREE.MeshStandardMaterial({ color: 0xef4444, roughness: 0.5 });
    const comb = new THREE.Mesh(combGeo, combMat);
    comb.position.set(0, 0.49, 0.16);
    chicken.add(comb);

    // Paruh Kuning Oranye
    const beakGeo = new THREE.ConeGeometry(0.05, 0.11, 6);
    const beakMat = new THREE.MeshStandardMaterial({ color: 0xf59e0b, roughness: 0.4 });
    const beak = new THREE.Mesh(beakGeo, beakMat);
    beak.rotation.x = Math.PI / 2;
    beak.position.set(0, 0.36, 0.28);
    chicken.add(beak);

    return chicken;
}

function buildKandang3DScene() {
    // A. Lantai Kandang Semen
    const floorGeo = new THREE.PlaneGeometry(24, 14);
    const floorMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.8, metalness: 0.1 });
    const floor = new THREE.Mesh(floorGeo, floorMat);
    floor.rotation.x = -Math.PI / 2;
    floor.receiveShadow = true;
    scene3D.add(floor);

    // Grid Garis Lantai
    const grid = new THREE.GridHelper(24, 24, 0x334155, 0x1e293b);
    grid.position.y = 0.01;
    scene3D.add(grid);

    // B. Tiang Rangka Baja Ringan Kandang (A-Frame Portal)
    const steelMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.6, roughness: 0.3 });
    for (let x = -9; x <= 9; x += 4.5) {
        // Kolom Kiri
        const colL = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.08, 4), steelMat);
        colL.position.set(x, 2, -3.5);
        scene3D.add(colL);

        // Kolom Kanan
        const colR = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.08, 4), steelMat);
        colR.position.set(x, 2, 3.5);
        scene3D.add(colR);

        // Kuda-Kuda Atap Kiri
        const rafterL = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.06, 4.2), steelMat);
        rafterL.position.set(x, 4.6, -1.8);
        rafterL.rotation.x = Math.PI / 5.5;
        scene3D.add(rafterL);

        // Kuda-Kuda Atap Kanan
        const rafterR = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.06, 4.2), steelMat);
        rafterR.position.set(x, 4.6, 1.8);
        rafterR.rotation.x = -Math.PI / 5.5;
        scene3D.add(rafterR);
    }

    // C. Atap Seng Kandang Rapat (Solid Seng Galvalum Penahan Panas)
    const roofMat = new THREE.MeshStandardMaterial({ 
        color: 0x475569, 
        metalness: 0.75, 
        roughness: 0.3, 
        side: THREE.DoubleSide 
    });

    const roofL = new THREE.Mesh(new THREE.PlaneGeometry(19, 4.4), roofMat);
    roofL.position.set(0, 4.6, -1.8);
    roofL.rotation.x = Math.PI / 5.5;
    scene3D.add(roofL);

    const roofR = new THREE.Mesh(new THREE.PlaneGeometry(19, 4.4), roofMat);
    roofR.position.set(0, 4.6, 1.8);
    roofR.rotation.x = -Math.PI / 5.5;
    scene3D.add(roofR);

    // Nok Penutup Bubungan Puncak Atap (Ridge Cap) - Rapat menutup celah tengah
    const ridgeCap = new THREE.Mesh(
        new THREE.CylinderGeometry(0.25, 0.25, 19, 8),
        new THREE.MeshStandardMaterial({ color: 0x334155, metalness: 0.8, roughness: 0.25 })
    );
    ridgeCap.rotation.z = Math.PI / 2;
    ridgeCap.position.set(0, 5.35, 0);
    scene3D.add(ridgeCap);

    // Pipa Sprinkler MURNI DI LUAR ATAP (Di Atas Nok Bubungan Luar)
    const ridgePipe = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 19), steelMat);
    ridgePipe.rotation.z = Math.PI / 2;
    ridgePipe.position.set(0, 5.55, 0); // Di luar atap atas!
    scene3D.add(ridgePipe);

    // Nozzle-nozzle Sprinkler di Luar Atap Mengarah ke Lereng Kiri dan Kanan
    for (let nx = -8; nx <= 8; nx += 2) {
        const nozL = new THREE.Mesh(new THREE.ConeGeometry(0.035, 0.08, 6), steelMat);
        nozL.position.set(nx, 5.55, -0.07);
        nozL.rotation.x = Math.PI / 3;
        scene3D.add(nozL);

        const nozR = new THREE.Mesh(new THREE.ConeGeometry(0.035, 0.08, 6), steelMat);
        nozR.position.set(nx, 5.55, 0.07);
        nozR.rotation.x = -Math.PI / 3;
        scene3D.add(nozR);
    }

    // D. Rak Kandang Baterai Ayam Petelur 2 TINGKAT (Sisi Kiri & Sisi Kanan Lorong)
    const cageMat = new THREE.MeshStandardMaterial({ color: 0x475569, metalness: 0.5, roughness: 0.5 });
    const troughMat = new THREE.MeshStandardMaterial({ color: 0xfacc15, roughness: 0.35 }); // Talang pakan PVC KUNING persis sesuai foto
    const frameMat = new THREE.MeshStandardMaterial({ color: 0x78350f, metalness: 0.6 });  // Rangka besi cokelat seperti di foto

    chickensList = [];

    for (let x = -8; x <= 8; x += 2.0) {
        // --- 1. SISI KIRI LORONG (2 TINGKAT) ---
        // Tiang Rangka Baterai Kiri (Cokelat)
        const legL1 = new THREE.Mesh(new THREE.BoxGeometry(0.05, 1.8, 0.05), frameMat);
        legL1.position.set(x - 0.8, 0.9, -1.4);
        scene3D.add(legL1);
        const legL2 = new THREE.Mesh(new THREE.BoxGeometry(0.05, 1.8, 0.05), frameMat);
        legL2.position.set(x + 0.8, 0.9, -1.4);
        scene3D.add(legL2);

        // A. Tingkat 1 (Bawah) - Kiri
        const cageL_T1 = new THREE.Mesh(new THREE.BoxGeometry(1.7, 0.65, 1.2), cageMat);
        cageL_T1.position.set(x, 0.45, -2.1);
        cageL_T1.castShadow = true;
        scene3D.add(cageL_T1);

        const troughL_T1 = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.12, 0.28), troughMat);
        troughL_T1.position.set(x, 0.38, -1.35);
        scene3D.add(troughL_T1);

        const chickL_T1 = createStylizedChicken();
        chickL_T1.position.set(x, 0.38, -1.65);
        chickL_T1.rotation.y = 0; // Menghadap lorong
        scene3D.add(chickL_T1);
        chickensList.push(chickL_T1);

        // B. Tingkat 2 (Atas) - Kiri
        const cageL_T2 = new THREE.Mesh(new THREE.BoxGeometry(1.7, 0.65, 1.2), cageMat);
        cageL_T2.position.set(x, 1.35, -2.1);
        cageL_T2.castShadow = true;
        scene3D.add(cageL_T2);

        const troughL_T2 = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.12, 0.28), troughMat);
        troughL_T2.position.set(x, 1.28, -1.35);
        scene3D.add(troughL_T2);

        const chickL_T2 = createStylizedChicken();
        chickL_T2.position.set(x, 1.28, -1.65);
        chickL_T2.rotation.y = 0; // Menghadap lorong
        scene3D.add(chickL_T2);
        chickensList.push(chickL_T2);

        // --- 2. SISI KANAN LORONG (2 TINGKAT) ---
        // Tiang Rangka Baterai Kanan (Cokelat)
        const legR1 = new THREE.Mesh(new THREE.BoxGeometry(0.05, 1.8, 0.05), frameMat);
        legR1.position.set(x - 0.8, 0.9, 1.4);
        scene3D.add(legR1);
        const legR2 = new THREE.Mesh(new THREE.BoxGeometry(0.05, 1.8, 0.05), frameMat);
        legR2.position.set(x + 0.8, 0.9, 1.4);
        scene3D.add(legR2);

        // A. Tingkat 1 (Bawah) - Kanan
        const cageR_T1 = new THREE.Mesh(new THREE.BoxGeometry(1.7, 0.65, 1.2), cageMat);
        cageR_T1.position.set(x, 0.45, 2.1);
        cageR_T1.castShadow = true;
        scene3D.add(cageR_T1);

        const troughR_T1 = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.12, 0.28), troughMat);
        troughR_T1.position.set(x, 0.38, 1.35);
        scene3D.add(troughR_T1);

        const chickR_T1 = createStylizedChicken();
        chickR_T1.position.set(x, 0.38, 1.65);
        chickR_T1.rotation.y = Math.PI; // Menghadap lorong
        scene3D.add(chickR_T1);
        chickensList.push(chickR_T1);

        // B. Tingkat 2 (Atas) - Kanan
        const cageR_T2 = new THREE.Mesh(new THREE.BoxGeometry(1.7, 0.65, 1.2), cageMat);
        cageR_T2.position.set(x, 1.35, 2.1);
        cageR_T2.castShadow = true;
        scene3D.add(cageR_T2);

        const troughR_T2 = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.12, 0.28), troughMat);
        troughR_T2.position.set(x, 1.28, 1.35);
        scene3D.add(troughR_T2);

        const chickR_T2 = createStylizedChicken();
        chickR_T2.position.set(x, 1.28, 1.65);
        chickR_T2.rotation.y = Math.PI; // Menghadap lorong
        scene3D.add(chickR_T2);
        chickensList.push(chickR_T2);
    }

    // E. Rel Baja Suspensi Kereta Pakan
    const railMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, metalness: 0.9, roughness: 0.2 });
    const rail1 = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 16), railMat);
    rail1.rotation.z = Math.PI / 2;
    rail1.position.set(0, 2.4, -0.4);
    scene3D.add(rail1);

    const rail2 = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 16), railMat);
    rail2.rotation.z = Math.PI / 2;
    rail2.position.set(0, 2.4, 0.4);
    scene3D.add(rail2);

    // Stasiun Batas A (Pangkal Rel + Motor Katrol L298N)
    const stationMatA = new THREE.MeshStandardMaterial({ color: 0x10b981 });
    const stationA = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.18, 2.5), stationMatA);
    stationA.position.set(-7.5, 1.25, 0);
    scene3D.add(stationA);

    // Motor Gearbox Kuning di Titik A (Motor DC dari kit mobil robot)
    const yellowMotor = new THREE.Mesh(
        new THREE.BoxGeometry(0.3, 0.25, 0.4),
        new THREE.MeshStandardMaterial({ color: 0xfacc15, metalness: 0.3, roughness: 0.4 })
    );
    yellowMotor.position.set(-7.5, 2.4, 0);
    scene3D.add(yellowMotor);

    // Stasiun Batas B (Ujung Rel + Pulley Katrol Pembalik)
    const stationMatB = new THREE.MeshStandardMaterial({ color: 0xef4444 });
    const stationB = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.18, 2.5), stationMatB);
    stationB.position.set(7.5, 1.25, 0);
    scene3D.add(stationB);

    // Pulley Katrol Putar di Titik B
    const returnPulley = new THREE.Mesh(
        new THREE.CylinderGeometry(0.15, 0.15, 0.1, 16),
        steelMat
    );
    returnPulley.position.set(7.5, 2.4, 0);
    scene3D.add(returnPulley);

    // F. Kereta Pakan 3D: TROLLEY TANGGA VERTIKAL DENGAN CORONG BIRU BERTINGKAT (Sesuai Foto RansumKu)
    feederCart3D = new THREE.Group();

    const ladderMat = new THREE.MeshStandardMaterial({ color: 0x64748b, metalness: 0.7, roughness: 0.3 }); // Rangka tangga baja abu-abu
    const blueHopperMat = new THREE.MeshStandardMaterial({ color: 0x0284c7, metalness: 0.2, roughness: 0.4 }); // Corong biru persis di foto
    const feedInsideMat = new THREE.MeshStandardMaterial({ color: 0xfbbf24, roughness: 0.9 }); // Pakan kuning di dalam corong

    // Fungsi Pembantu: Membuat 1 Unit Corong Pakan Biru Kotak Piramida Terbalik
    function createBlueHopperUnit() {
        const hGroup = new THREE.Group();
        // 1. Kotak bibir atas corong
        const boxTop = new THREE.Mesh(new THREE.BoxGeometry(0.48, 0.2, 0.38), blueHopperMat);
        boxTop.position.y = 0.1;
        hGroup.add(boxTop);

        // 2. Permukaan pakan kuning di dalam
        const feedSurface = new THREE.Mesh(new THREE.PlaneGeometry(0.42, 0.32), feedInsideMat);
        feedSurface.rotation.x = -Math.PI / 2;
        feedSurface.position.y = 0.18;
        hGroup.add(feedSurface);

        // 3. Badan corong mengerucut ke bawah (Inverted Truncated Pyramid)
        const pyramidGeo = new THREE.CylinderGeometry(0.32, 0.12, 0.32, 4);
        const pyramid = new THREE.Mesh(pyramidGeo, blueHopperMat);
        pyramid.position.y = -0.15;
        pyramid.rotation.y = Math.PI / 4;
        hGroup.add(pyramid);

        // 4. Cerat bibir curahan bawah ke talang kuning
        const snout = new THREE.Mesh(new THREE.BoxGeometry(0.16, 0.1, 0.16), blueHopperMat);
        snout.position.y = -0.34;
        hGroup.add(snout);

        return hGroup;
    }

    // --- RANGKA TANGGA VERTIKAL SISI KIRI (Menempel di Depan Talang Pakan Kiri) ---
    // Dua Tiang Vertikal Tangga Kiri
    const poleL1 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 2.2, 0.05), ladderMat);
    poleL1.position.set(-0.25, 1.1, -1.35);
    feederCart3D.add(poleL1);

    const poleL2 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 2.2, 0.05), ladderMat);
    poleL2.position.set(0.25, 1.1, -1.35);
    feederCart3D.add(poleL2);

    // Palang-palang anak tangga kiri
    for (let py = 0.2; py <= 2.1; py += 0.45) {
        const rung = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.5), ladderMat);
        rung.rotation.z = Math.PI / 2;
        rung.position.set(0, py, -1.35);
        feederCart3D.add(rung);
    }

    // Corong Biru Tingkat 2 (Atas) - Sisi Kiri (Tepat di atas talang kuning atas)
    const hopperL_T2 = createBlueHopperUnit();
    hopperL_T2.position.set(0, 1.6, -1.35);
    feederCart3D.add(hopperL_T2);

    // Corong Biru Tingkat 1 (Bawah) - Sisi Kiri (Tepat di atas talang kuning bawah)
    const hopperL_T1 = createBlueHopperUnit();
    hopperL_T1.position.set(0, 0.72, -1.35);
    feederCart3D.add(hopperL_T1);

    // --- RANGKA TANGGA VERTIKAL SISI KANAN (Menempel di Depan Talang Pakan Kanan) ---
    // Dua Tiang Vertikal Tangga Kanan
    const poleR1 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 2.2, 0.05), ladderMat);
    poleR1.position.set(-0.25, 1.1, 1.35);
    feederCart3D.add(poleR1);

    const poleR2 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 2.2, 0.05), ladderMat);
    poleR2.position.set(0.25, 1.1, 1.35);
    feederCart3D.add(poleR2);

    // Palang-palang anak tangga kanan
    for (let py = 0.2; py <= 2.1; py += 0.45) {
        const rung = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.5), ladderMat);
        rung.rotation.z = Math.PI / 2;
        rung.position.set(0, py, 1.35);
        feederCart3D.add(rung);
    }

    // Corong Biru Tingkat 2 (Atas) - Sisi Kanan (Tepat di atas talang kuning atas)
    const hopperR_T2 = createBlueHopperUnit();
    hopperR_T2.position.set(0, 1.6, 1.35);
    feederCart3D.add(hopperR_T2);

    // Corong Biru Tingkat 1 (Bawah) - Sisi Kanan (Tepat di atas talang kuning bawah)
    const hopperR_T1 = createBlueHopperUnit();
    hopperR_T1.position.set(0, 0.72, 1.35);
    feederCart3D.add(hopperR_T1);

    // --- JEMBATAN GANTRY ATAS PENGHUBUNG TANGGA KIRI & KANAN ---
    const bridgeBeam = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.12, 2.75), ladderMat);
    bridgeBeam.position.set(0, 2.25, 0);
    feederCart3D.add(bridgeBeam);

    // Rangka Suspensi Roda Rel Tengah
    const hanger = new THREE.Mesh(new THREE.BoxGeometry(0.2, 0.35, 0.8), ladderMat);
    hanger.position.set(0, 2.4, 0);
    feederCart3D.add(hanger);

    // Roda Kereta di Rel Suspensi
    const wheelMat = new THREE.MeshStandardMaterial({ color: 0xf59e0b, metalness: 0.7 });
    const wL = new THREE.Mesh(new THREE.CylinderGeometry(0.07, 0.07, 0.12), wheelMat);
    wL.position.set(0, 2.4, -0.4);
    wL.rotation.x = Math.PI / 2;
    feederCart3D.add(wL);

    const wR = new THREE.Mesh(new THREE.CylinderGeometry(0.07, 0.07, 0.12), wheelMat);
    wR.position.set(0, 2.4, 0.4);
    wR.rotation.x = Math.PI / 2;
    feederCart3D.add(wR);

    // Posisi awal di Titik A (Pangkal)
    feederCart3D.position.x = feederPositionX;
    scene3D.add(feederCart3D);

    // Tali Kawat Sling Penarik (Cable Winch Line)
    const cableMat = new THREE.MeshBasicMaterial({ color: 0x94a3b8 });
    const cableGeoA = new THREE.CylinderGeometry(0.015, 0.015, 1);
    cableMeshA = new THREE.Mesh(cableGeoA, cableMat);
    cableMeshA.rotation.z = Math.PI / 2;
    scene3D.add(cableMeshA);

    const cableGeoB = new THREE.CylinderGeometry(0.015, 0.015, 1);
    cableMeshB = new THREE.Mesh(cableGeoB, cableMat);
    cableMeshB.rotation.z = Math.PI / 2;
    scene3D.add(cableMeshB);

    updateCableLines();

    // G. Kipas Blower Sirkulasi 3D DI ATAS (Dipasang Berseri Berjarak per 4.5 Meter di Plafon/Puncak Atap)
    fanBladesList = [];

    function createCeilingFanUnit(xPos, yPos, zPos, isExhaustWall = false) {
        const fanGroup = new THREE.Group();
        fanGroup.position.set(xPos, yPos, zPos);
        fanGroup.rotation.y = -Math.PI / 2;

        const radius = isExhaustWall ? 0.85 : 0.65;
        const fanFrame = new THREE.Mesh(
            new THREE.TorusGeometry(radius, 0.08, 16, 32),
            new THREE.MeshStandardMaterial({ color: isExhaustWall ? 0x1e293b : 0x334155, metalness: 0.7 })
        );
        fanGroup.add(fanFrame);

        // Rangka gantungan ke plafon atap
        if (!isExhaustWall) {
            const strut = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.7), new THREE.MeshStandardMaterial({ color: 0x64748b }));
            strut.position.set(0, 0.7, 0);
            fanGroup.add(strut);
        }

        const blades = new THREE.Group();
        const bladeGeo = new THREE.BoxGeometry(0.16, radius * 0.88, 0.02);
        const bladeMat = new THREE.MeshStandardMaterial({ color: 0x38bdf8 });
        for (let i = 0; i < 4; i++) {
            const blade = new THREE.Mesh(bladeGeo, bladeMat);
            blade.rotation.z = (Math.PI / 2) * i;
            blade.position.y = (radius * 0.38) * Math.sin((Math.PI / 2) * i);
            blade.position.x = (radius * 0.38) * Math.cos((Math.PI / 2) * i);
            blades.add(blade);
        }
        fanGroup.add(blades);
        scene3D.add(fanGroup);
        fanBladesList.push(blades);
        return fanGroup;
    }

    // Pasang kipas berseri di plafon atas berjarak beberapa meter (Sesuai Arahan Aditya)
    createCeilingFanUnit(-4.5, 4.3, 0); // Kipas Sirkulasi Atas Zona 1
    createCeilingFanUnit(0.0, 4.3, 0);  // Kipas Sirkulasi Atas Zona 2 (Tengah)
    createCeilingFanUnit(4.5, 4.3, 0);  // Kipas Sirkulasi Atas Zona 3
    createCeilingFanUnit(9.4, 4.3, 0, true); // Kipas Exhaust Dinding Belakang Atas (Gable Apex)

    // H. Partikel Air Sprinkler MURNI DI LUAR ATAP (Outer Roof Slope Flow)
    const particleCount = 260;
    const waterGeo = new THREE.BufferGeometry();
    const waterPositions = new Float32Array(particleCount * 3);
    for (let k = 0; k < particleCount; k++) {
        const i = k * 3;
        const isLeft = k % 2 === 0;
        const x = (Math.random() - 0.5) * 18;
        // z mengalir di lereng atap luar: dari z = ±0.25 ke z = ±3.7
        const progress = Math.random();
        const z = isLeft ? (-0.25 - progress * 3.45) : (0.25 + progress * 3.45);
        const y = 5.48 - 0.39 * Math.abs(z); // Tepat di atas permukaan luar seng

        waterPositions[i] = x;
        waterPositions[i + 1] = y;
        waterPositions[i + 2] = z;
    }
    waterGeo.setAttribute('position', new THREE.BufferAttribute(waterPositions, 3));
    const waterMat = new THREE.PointsMaterial({ color: 0x38bdf8, size: 0.16, transparent: true, opacity: 0.9 });
    roofWaterParticles3D = new THREE.Points(waterGeo, waterMat);
    roofWaterParticles3D.visible = false;
    scene3D.add(roofWaterParticles3D);

    // I. Partikel Butiran Ransum Pakan (4 Titik Curahan Langsung dari Cerat Bawah Corong Biru)
    function createFeedEmitter(yBase, zBase) {
        const pCount = 25;
        const geo = new THREE.BufferGeometry();
        const pos = new Float32Array(pCount * 3);
        for (let i = 0; i < pCount * 3; i += 3) {
            pos[i] = (Math.random() - 0.5) * 0.12;
            pos[i + 1] = yBase - Math.random() * 0.16;
            pos[i + 2] = zBase + (Math.random() - 0.5) * 0.1;
        }
        geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
        const mat = new THREE.PointsMaterial({ color: 0xfbbf24, size: 0.07 });
        const p = new THREE.Points(geo, mat);
        p.visible = false;
        return p;
    }

    feedP_L_T2 = createFeedEmitter(1.3, -1.35); // Curah ke Talang Kiri Tingkat 2 (Atas)
    feedP_L_T1 = createFeedEmitter(0.42, -1.35); // Curah ke Talang Kiri Tingkat 1 (Bawah)
    feedP_R_T2 = createFeedEmitter(1.3, 1.35);  // Curah ke Talang Kanan Tingkat 2 (Atas)
    feedP_R_T1 = createFeedEmitter(0.42, 1.35);  // Curah ke Talang Kanan Tingkat 1 (Bawah)

    feederCart3D.add(feedP_L_T2);
    feederCart3D.add(feedP_L_T1);
    feederCart3D.add(feedP_R_T2);
    feederCart3D.add(feedP_R_T1);
}

function updateCableLines() {
    if (!cableMeshA || !cableMeshB) return;
    
    // Kabel dari Titik A (-7.5) ke Kereta Pakan
    const distA = feederPositionX - (-7.5);
    if (distA > 0.1) {
        cableMeshA.scale.y = distA;
        cableMeshA.position.set(-7.5 + distA / 2, 2.45, 0);
        cableMeshA.visible = true;
    } else {
        cableMeshA.visible = false;
    }

    // Kabel dari Kereta Pakan ke Titik B (7.5)
    const distB = 7.5 - feederPositionX;
    if (distB > 0.1) {
        cableMeshB.scale.y = distB;
        cableMeshB.position.set(feederPositionX + distB / 2, 2.45, 0);
        cableMeshB.visible = true;
    } else {
        cableMeshB.visible = false;
    }
}

function setFeedParticlesVisible(vis) {
    if (feedP_L_T1) feedP_L_T1.visible = vis;
    if (feedP_L_T2) feedP_L_T2.visible = vis;
    if (feedP_R_T1) feedP_R_T1.visible = vis;
    if (feedP_R_T2) feedP_R_T2.visible = vis;
}

// Loop Animasi 3D (60 FPS)
function animateThreeJS() {
    requestAnimationFrame(animateThreeJS);

    // 1. Animasi Gerak Kereta Pakan (Travelling Hopper Katrol 2 Tingkat)
    if (isFeederRunning3D && feederCart3D) {
        feederPositionX += 0.05 * feederDirection3D;
        feederCart3D.position.x = feederPositionX;
        updateCableLines();

        setFeedParticlesVisible(true);

        // Hitung persentase lintasan lorong
        const progress = Math.round(((feederPositionX - FEEDER_LIMIT_A) / (FEEDER_LIMIT_B - FEEDER_LIMIT_A)) * 100);

        // Sampai di Titik B (Ujung Lorong)
        if (feederDirection3D === 1 && feederPositionX >= FEEDER_LIMIT_B) {
            feederPositionX = FEEDER_LIMIT_B;
            feederCart3D.position.x = FEEDER_LIMIT_B;
            isFeederRunning3D = false;
            feederDirection3D = -1; // Sesi berikutnya siap mundur ke A
            setFeedParticlesVisible(false);
            updateStatusFeederText('Parkir di Ujung (Titik B 100%)');
            resetFeederButtonUI();
        }
        // Sampai di Titik A (Pangkal Lorong)
        else if (feederDirection3D === -1 && feederPositionX <= FEEDER_LIMIT_A) {
            feederPositionX = FEEDER_LIMIT_A;
            feederCart3D.position.x = FEEDER_LIMIT_A;
            isFeederRunning3D = false;
            feederDirection3D = 1; // Sesi berikutnya siap maju ke B
            setFeedParticlesVisible(false);
            updateStatusFeederText('Siaga di Pangkal (Titik A 0%)');
            resetFeederButtonUI();
        } else {
            updateStatusFeederText(`Meluncur ${feederDirection3D === 1 ? 'MAJU (A➔B)' : 'MUNDUR (B➔A)'} ${progress}%`);
        }
    } else {
        setFeedParticlesVisible(false);
    }

    // 2. Animasi Ayam Mematuk Pakan (Bobbing Animation)
    chickenBobTime += 0.05;
    for (let i = 0; i < chickensList.length; i++) {
        const chicken = chickensList[i];
        // Gerakan paruh mematuk talang pakan
        chicken.rotation.x = Math.sin(chickenBobTime + i) * 0.12;
    }

    // 3. Animasi Seluruh Kipas Blower Atas Berputar Bersama
    if (isFanSpinning3D && fanBladesList.length > 0) {
        for (let i = 0; i < fanBladesList.length; i++) {
            fanBladesList[i].rotation.z += 0.35; // Putaran kencang serempak
        }
    }

    // 4. Animasi Percikan Air Sprinkler 100% DI LUAR ATAP SENG
    if (isSprinklerActive3D && roofWaterParticles3D) {
        roofWaterParticles3D.visible = true;
        const pos = roofWaterParticles3D.geometry.attributes.position.array;
        for (let k = 0; k < pos.length / 3; k++) {
            const i = k * 3;
            const isLeft = pos[i + 2] < 0;
            if (isLeft) {
                pos[i + 2] -= 0.055; // Mengalir turun di lereng kiri LUAR
                if (pos[i + 2] < -3.75) {
                    pos[i + 2] = -0.25; // Reset kembali ke nok bubungan atas luar
                    pos[i] = (Math.random() - 0.5) * 18;
                }
            } else {
                pos[i + 2] += 0.055; // Mengalir turun di lereng kanan LUAR
                if (pos[i + 2] > 3.75) {
                    pos[i + 2] = 0.25; // Reset kembali ke nok bubungan atas luar
                    pos[i] = (Math.random() - 0.5) * 18;
                }
            }
            // Ketinggian Y selalu berada tepat di atas seng luar (tidak pernah di dalam ruangan)
            pos[i + 1] = 5.48 - 0.39 * Math.abs(pos[i + 2]);
        }
        roofWaterParticles3D.geometry.attributes.position.needsUpdate = true;
    } else if (roofWaterParticles3D) {
        roofWaterParticles3D.visible = false;
    }

    // 5. Update OrbitControls
    if (controls3D) controls3D.update();

    // 6. Render Scene WebGL
    if (renderer3D && scene3D && camera3D) {
        renderer3D.render(scene3D, camera3D);
    }
}

function onWindowResize3D() {
    const container = document.getElementById('canvas-container-3d');
    if (!container || !camera3D || !renderer3D) return;
    const width = container.clientWidth;
    const height = container.clientHeight;
    camera3D.aspect = width / height;
    camera3D.updateProjectionMatrix();
    renderer3D.setSize(width, height);
}

// Preset Sudut Pandang Kamera 3D
function setKamera3D(tipe) {
    if (!camera3D || !controls3D) return;
    if (tipe === 'perspektif') {
        camera3D.position.set(13, 9, 14);
        controls3D.target.set(0, 2, 0);
    } else if (tipe === 'lorong') {
        camera3D.position.set(feederPositionX - 3.2, 2.5, 0);
        controls3D.target.set(feederPositionX + 4, 1.8, 0);
    } else if (tipe === 'atas') {
        camera3D.position.set(0, 22, 0.01);
        controls3D.target.set(0, 0, 0);
    }
    controls3D.update();
}

function toggleAutoRotate3D() {
    if (!controls3D) return;
    controls3D.autoRotate = !controls3D.autoRotate;
    const btn = document.getElementById('btn-autorotate-3d');
    if (btn) {
        btn.className = controls3D.autoRotate
            ? 'px-3 py-1.5 rounded-lg text-[11px] font-bold bg-purple-600 text-white shadow-2xs cursor-pointer flex items-center gap-1'
            : 'px-3 py-1.5 rounded-lg text-[11px] font-bold bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 transition shadow-2xs cursor-pointer flex items-center gap-1';
    }
}

// Pemicu Gerak Kereta 3D Langsung dari Tombol
function triggerSimulasiGerakPakan() {
    isFeederRunning3D = true;
    updateStatusFeederText(feederDirection3D === 1 ? 'Meluncur MAJU (A ➔ B)' : 'Meluncur MUNDUR (B ➔ A)');
    
    const btn = document.getElementById('btn-trigger-pakan-3d');
    if (btn) {
        btn.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold bg-amber-600 text-white shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer';
        btn.innerHTML = '<span class="material-symbols-outlined text-[16px] animate-spin">refresh</span><span>Kereta Bergerak...</span>';
    }
}

function resetFeederButtonUI() {
    const btn = document.getElementById('btn-trigger-pakan-3d');
    if (btn) {
        btn.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer';
        btn.innerHTML = '<span class="material-symbols-outlined text-[16px]">play_arrow</span><span>Jalankan Kereta 3D</span>';
    }
}

function updateStatusFeederText(teks) {
    const el = document.getElementById('status-feeder-3d');
    if (el) el.textContent = teks;
}

// Simulasi Uji Cuaca Panas (Trigger Kipas & Sprinkler 3D)
function toggleUjiCuacaPanas3D() {
    isFanSpinning3D = !isFanSpinning3D;
    isSprinklerActive3D = !isSprinklerActive3D;

    updateBadgeKipas3D(isFanSpinning3D);
    updateBadgeSprinkler3D(isSprinklerActive3D);

    const btn = document.getElementById('btn-cuaca-3d');
    if (btn) {
        btn.className = isFanSpinning3D
            ? 'px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500 text-slate-950 shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer'
            : 'px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800/90 hover:bg-slate-700 text-amber-300 border border-slate-700 shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer';
    }
}

function updateBadgeKipas3D(isAktif) {
    const fanText = document.getElementById('text-fan-3d');
    const fanIcon = document.getElementById('icon-fan-3d');
    if (fanText) fanText.textContent = isAktif ? 'Kipas ON (Berputar)' : 'Kipas OFF';
    if (fanIcon) fanIcon.className = isAktif ? 'material-symbols-outlined text-[14px] text-cyan-400 animate-spin' : 'material-symbols-outlined text-[14px] text-slate-400';
}

function updateBadgeSprinkler3D(isAktif) {
    const roofText = document.getElementById('text-roof-3d');
    const roofIcon = document.getElementById('icon-roof-3d');
    if (roofText) roofText.textContent = isAktif ? 'Sprinkler ON (Menyemprot)' : 'Sprinkler OFF';
    if (roofIcon) roofIcon.className = isAktif ? 'material-symbols-outlined text-[14px] text-cyan-300 animate-bounce' : 'material-symbols-outlined text-[14px] text-slate-400';
}

// Sinkronisasi Status dari Luar (Misal dari Widget Kontroler Sakelar atau Telemetri IoT)
window.syncKandang3DState = function(state) {
    if (!state) return;
    if (state.kipas !== undefined) {
        isFanSpinning3D = Boolean(state.kipas);
        updateBadgeKipas3D(isFanSpinning3D);
    }
    if (state.misting !== undefined) {
        isSprinklerActive3D = Boolean(state.misting);
        updateBadgeSprinkler3D(isSprinklerActive3D);
    }
    if (state.lampu !== undefined) {
        const intensity = state.lampu ? 1.6 : 0.35;
        if (coopLight1) coopLight1.intensity = intensity;
        if (coopLight2) coopLight2.intensity = intensity;
    }
    if (state.pakan !== undefined) {
        if (Boolean(state.pakan) && !isFeederRunning3D) {
            triggerSimulasiGerakPakan();
        } else if (!Boolean(state.pakan)) {
            isFeederRunning3D = false;
            setFeedParticlesVisible(false);
            resetFeederButtonUI();
        }
    }
};

// Inisialisasi saat DOM siap
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(initThreeJSKandang, 100);
});
</script>
