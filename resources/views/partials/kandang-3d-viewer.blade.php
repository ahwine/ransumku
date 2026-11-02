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
    camera3D = new THREE.PerspectiveCamera(50, width / height, 0.1, 80);
    camera3D.position.set(13, 6.5, 10);

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
        controls3D.target.set(0, 1.8, 0);
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
    // =====================================================================
    // KOORDINAT ARSITEKTUR KANDANG:
    // Y=0: Lantai, Y=3.60: Tepi Atap/Kolom, Y=4.85: Nok Bubungan Puncak Atap
    // Z=±3.5: Kolom samping, Z=±2.1: Kandang baterai, Z=±1.32: Talang
    // Sisi Depan (Z > 0): TERBUKA TANPA TEMBOK (Cutaway view ke kandang & troli)
    // Sisi Belakang (Z < 0): Curb wall rendah 0.45m + tiang baja
    // Dinding Ujung (X = ±9.9): Portal rangka baja terbuka (lorong bebas pandang)
    // Atap: Pelana galvalum simetris rapi (kemiringan ~18.6°)
    // Sprinkler: Pipa nok luar Y=5.02, partikel 100% meluncur di permukaan seng luar
    // Kipas Blower: Gantung dari plafon di Y=3.0, berputar di atas lorong
    // =====================================================================

    // A. Lantai Kandang Semen
    const floorMat = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.9, metalness: 0.05 });
    const floor = new THREE.Mesh(new THREE.PlaneGeometry(22, 9.5), floorMat);
    floor.rotation.x = -Math.PI / 2;
    floor.receiveShadow = true;
    scene3D.add(floor);

    // Grid Lorong Lantai
    const grid = new THREE.GridHelper(22, 22, 0x475569, 0x334155);
    grid.position.y = 0.01;
    scene3D.add(grid);

    // B. Dinding Kandang:
    // Sisi Belakang (Z = -3.6): Tembok batako rendah (0.45m)
    // Sisi Depan (Z = +3.6): 100% TERBUKA TANPA TEMBOK (sesuai instruksi agar dalam kandang terlihat jelas)
    const wallMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.85 });
    const wallBack = new THREE.Mesh(new THREE.BoxGeometry(20.4, 0.45, 0.15), wallMat);
    wallBack.position.set(0, 0.225, -3.6);
    scene3D.add(wallBack);

    // C. Kolom Tiang Baja Ringan (Tinggi 3.6m per interval 3m sepanjang lorong)
    const steelMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.75, roughness: 0.25 });
    const colPositions = [-9, -6, -3, 0, 3, 6, 9];
    for (const cx of colPositions) {
        // Kolom Kiri (Belakang)
        const cL = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.065, 3.6), steelMat);
        cL.position.set(cx, 1.8, -3.5);
        cL.castShadow = true;
        scene3D.add(cL);

        // Kolom Kanan (Depan - tetap ada tiang rangka penopang atap, tapi tanpa tembok)
        const cR = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.065, 3.6), steelMat);
        cR.position.set(cx, 1.8, 3.5);
        cR.castShadow = true;
        scene3D.add(cR);
    }

    // D. ATAP PELANA GALVALUM RAPI & SIMETRIS (Kemiringan ~18.6°)
    // Puncak Nok (Ridge): Y = 4.85, Z = 0
    // Tepi Luar (Eave):   Y = 3.60, Z = ±3.7 (overhang 20cm dari kolom)
    // Panjang Atap:      X = -10.2 s/d +10.2
    const roofMat = new THREE.MeshStandardMaterial({
        color: 0x475569, // Abu metalik seng galvalum
        metalness: 0.82,
        roughness: 0.35,
        side: THREE.DoubleSide
    });

    // Helper untuk membuat bidang atap miring presisi matematis via BufferGeometry
    function createSlopedRoofMesh(xMin, xMax, yRidge, zRidge, yEave, zEave, mat) {
        const geo = new THREE.BufferGeometry();
        const vertices = new Float32Array([
            // Segitiga 1
            xMin, yRidge, zRidge,
            xMax, yEave,  zEave,
            xMax, yRidge, zRidge,
            // Segitiga 2
            xMin, yRidge, zRidge,
            xMin, yEave,  zEave,
            xMax, yEave,  zEave
        ]);
        geo.setAttribute('position', new THREE.BufferAttribute(vertices, 3));
        geo.computeVertexNormals();
        return new THREE.Mesh(geo, mat);
    }

    // Bidang Atap Kiri (Belakang)
    const roofL = createSlopedRoofMesh(-10.2, 10.2, 4.85, 0, 3.60, -3.7, roofMat);
    scene3D.add(roofL);

    // Bidang Atap Kanan (Depan)
    const roofR = createSlopedRoofMesh(-10.2, 10.2, 4.85, 0, 3.60, 3.7, roofMat);
    scene3D.add(roofR);

    // Nok Penutup Bubungan (Ridge Cap) di puncak Y = 4.88
    const ridgeCap = new THREE.Mesh(
        new THREE.CylinderGeometry(0.12, 0.12, 20.4, 8),
        new THREE.MeshStandardMaterial({ color: 0x1e293b, metalness: 0.85, roughness: 0.25 })
    );
    ridgeCap.rotation.z = Math.PI / 2;
    ridgeCap.position.set(0, 4.88, 0);
    scene3D.add(ridgeCap);

    // Lisplang / Fascia Trim di tepi seng bawah
    const fasciaL = new THREE.Mesh(new THREE.BoxGeometry(20.4, 0.08, 0.04), steelMat);
    fasciaL.position.set(0, 3.60, -3.7);
    scene3D.add(fasciaL);
    const fasciaR = new THREE.Mesh(new THREE.BoxGeometry(20.4, 0.08, 0.04), steelMat);
    fasciaR.position.set(0, 3.60, 3.7);
    scene3D.add(fasciaR);

    // Helper membuat balok kuda-kuda miring presisi
    function createRafterBar(x, y1, z1, y2, z2) {
        const dy = y2 - y1;
        const dz = z2 - z1;
        const len = Math.hypot(dy, dz);
        const cyl = new THREE.Mesh(new THREE.CylinderGeometry(0.035, 0.035, len), steelMat);
        cyl.position.set(x, (y1 + y2) / 2, (z1 + z2) / 2);
        cyl.rotation.x = Math.atan2(dz, dy);
        return cyl;
    }

    // Kuda-Kuda Baja Rapi di Setiap Portal Kolom
    for (const cx of colPositions) {
        // Balok Tarik Horizontal (Tie Beam) di Y = 3.60 menghubungkan kolom kiri & kanan
        const tie = new THREE.Mesh(new THREE.BoxGeometry(0.05, 0.05, 7.0), steelMat);
        tie.position.set(cx, 3.60, 0);
        scene3D.add(tie);

        // Tiang Gantung Tengah (King Post) dari Y=3.60 ke puncak Y=4.85
        const kingPost = new THREE.Mesh(new THREE.BoxGeometry(0.04, 1.25, 0.04), steelMat);
        kingPost.position.set(cx, 4.225, 0);
        scene3D.add(kingPost);

        // Kaki Kuda-Kuda Kiri & Kanan
        scene3D.add(createRafterBar(cx, 4.85, 0, 3.60, -3.5));
        scene3D.add(createRafterBar(cx, 4.85, 0, 3.60, 3.5));
    }

    // E. Pipa Sprinkler MURNI DI LUAR ATAP (Tepat di atas nok bubungan puncak Y = 5.02)
    const sprayPipe = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 20.4), steelMat);
    sprayPipe.rotation.z = Math.PI / 2;
    sprayPipe.position.set(0, 5.02, 0);
    scene3D.add(sprayPipe);

    // Nozzle Sprinkler Mengarah ke Lereng Atap Kiri dan Kanan
    const nozzleMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, metalness: 0.85, roughness: 0.2 });
    for (let nx = -8.5; nx <= 8.5; nx += 2.5) {
        const nL = new THREE.Mesh(new THREE.ConeGeometry(0.03, 0.08, 6), nozzleMat);
        nL.position.set(nx, 5.00, -0.07);
        nL.rotation.x = Math.PI / 3;
        scene3D.add(nL);

        const nR = new THREE.Mesh(new THREE.ConeGeometry(0.03, 0.08, 6), nozzleMat);
        nR.position.set(nx, 5.00, 0.07);
        nR.rotation.x = -Math.PI / 3;
        scene3D.add(nR);
    }

    // F. Rak Kandang Baterai 2 TINGKAT — Sisi Kiri & Kanan Lorong
    const cageMat = new THREE.MeshStandardMaterial({ color: 0x6b7280, metalness: 0.45, roughness: 0.55 });
    const troughMat = new THREE.MeshStandardMaterial({ color: 0xfcd34d, roughness: 0.3, metalness: 0.1 }); // Talang KUNING cerah
    const frameMat = new THREE.MeshStandardMaterial({ color: 0x92400e, metalness: 0.55 }); // Rangka cokelat

    chickensList = [];

    // Kawat jaring tampilan kandang (tipis melintang per sel)
    const wireMat = new THREE.MeshStandardMaterial({ color: 0x9ca3af, metalness: 0.6, roughness: 0.4, wireframe: false });

    for (let x = -8; x <= 8; x += 2.0) {
        // ---- SISI KIRI (Z negatif) ----
        // Tiang vertikal rangka kiri
        for (const tz of [-1.3, -2.85]) {
            const leg = new THREE.Mesh(new THREE.BoxGeometry(0.05, 2.1, 0.05), frameMat);
            leg.position.set(x, 1.05, tz);
            scene3D.add(leg);
        }
        // Kandang Tingkat 1 kiri (bawah)
        const cL1 = new THREE.Mesh(new THREE.BoxGeometry(1.75, 0.7, 1.4), cageMat);
        cL1.position.set(x, 0.45, -2.1);
        cL1.castShadow = true;
        scene3D.add(cL1);
        // Talang kuning T1 kiri
        const tL1 = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.1, 0.25), troughMat);
        tL1.position.set(x, 0.35, -1.32);
        scene3D.add(tL1);
        // Ayam T1 kiri
        const ckL1 = createStylizedChicken();
        ckL1.position.set(x, 0.35, -1.65);
        ckL1.rotation.y = 0; // Menghadap lorong
        scene3D.add(ckL1);
        chickensList.push(ckL1);

        // Kandang Tingkat 2 kiri (atas)
        const cL2 = new THREE.Mesh(new THREE.BoxGeometry(1.75, 0.7, 1.4), cageMat);
        cL2.position.set(x, 1.35, -2.1);
        cL2.castShadow = true;
        scene3D.add(cL2);
        // Talang kuning T2 kiri
        const tL2 = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.1, 0.25), troughMat);
        tL2.position.set(x, 1.25, -1.32);
        scene3D.add(tL2);
        // Ayam T2 kiri
        const ckL2 = createStylizedChicken();
        ckL2.position.set(x, 1.25, -1.65);
        ckL2.rotation.y = 0;
        scene3D.add(ckL2);
        chickensList.push(ckL2);

        // ---- SISI KANAN (Z positif) ----
        for (const tz of [1.3, 2.85]) {
            const leg = new THREE.Mesh(new THREE.BoxGeometry(0.05, 2.1, 0.05), frameMat);
            leg.position.set(x, 1.05, tz);
            scene3D.add(leg);
        }
        // Kandang Tingkat 1 kanan
        const cR1 = new THREE.Mesh(new THREE.BoxGeometry(1.75, 0.7, 1.4), cageMat);
        cR1.position.set(x, 0.45, 2.1);
        cR1.castShadow = true;
        scene3D.add(cR1);
        // Talang kuning T1 kanan
        const tR1 = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.1, 0.25), troughMat);
        tR1.position.set(x, 0.35, 1.32);
        scene3D.add(tR1);
        // Ayam T1 kanan
        const ckR1 = createStylizedChicken();
        ckR1.position.set(x, 0.35, 1.65);
        ckR1.rotation.y = Math.PI;
        scene3D.add(ckR1);
        chickensList.push(ckR1);

        // Kandang Tingkat 2 kanan
        const cR2 = new THREE.Mesh(new THREE.BoxGeometry(1.75, 0.7, 1.4), cageMat);
        cR2.position.set(x, 1.35, 2.1);
        cR2.castShadow = true;
        scene3D.add(cR2);
        // Talang kuning T2 kanan
        const tR2 = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.1, 0.25), troughMat);
        tR2.position.set(x, 1.25, 1.32);
        scene3D.add(tR2);
        // Ayam T2 kanan
        const ckR2 = createStylizedChicken();
        ckR2.position.set(x, 1.25, 1.65);
        ckR2.rotation.y = Math.PI;
        scene3D.add(ckR2);
        chickensList.push(ckR2);
    }

    // G. Rel Suspensi Kereta Pakan (2 rel paralel di Y=2.6, Z=±0.4)
    const railMat = new THREE.MeshStandardMaterial({ color: 0xe5e7eb, metalness: 0.92, roughness: 0.15 });
    const rail1 = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 19), railMat);
    rail1.rotation.z = Math.PI / 2;
    rail1.position.set(0, 2.6, -0.42);
    scene3D.add(rail1);
    const rail2 = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 19), railMat);
    rail2.rotation.z = Math.PI / 2;
    rail2.position.set(0, 2.6, 0.42);
    scene3D.add(rail2);

    // Penggantung rel ke kuda-kuda per 3m
    for (const cx of colPositions) {
        const hanger = new THREE.Mesh(new THREE.BoxGeometry(0.03, 0.6, 0.84), steelMat);
        hanger.position.set(cx, 2.9, 0);
        scene3D.add(hanger);
    }

    // Stasiun Motor A (Titik awal, warna hijau)
    const stA = new THREE.Mesh(new THREE.CylinderGeometry(0.16, 0.16, 2.6),
        new THREE.MeshStandardMaterial({ color: 0x10b981 }));
    stA.position.set(-8.5, 1.3, 0);
    scene3D.add(stA);
    // Motor gearbox kuning
    const motorBox = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.22, 0.38),
        new THREE.MeshStandardMaterial({ color: 0xfbbf24, metalness: 0.3 }));
    motorBox.position.set(-8.5, 2.6, 0);
    scene3D.add(motorBox);

    // Stasiun Pulley B (Titik ujung, warna merah)
    const stB = new THREE.Mesh(new THREE.CylinderGeometry(0.16, 0.16, 2.6),
        new THREE.MeshStandardMaterial({ color: 0xef4444 }));
    stB.position.set(8.5, 1.3, 0);
    scene3D.add(stB);
    const pulley = new THREE.Mesh(new THREE.CylinderGeometry(0.14, 0.14, 0.1, 16), steelMat);
    pulley.position.set(8.5, 2.6, 0);
    scene3D.add(pulley);

    // H. Kereta Pakan (Travelling Hopper) — Tangga Vertikal + Corong Biru
    feederCart3D = new THREE.Group();

    const ladderMat = new THREE.MeshStandardMaterial({ color: 0x6b7280, metalness: 0.7, roughness: 0.3 });
    const blueHopperMat = new THREE.MeshStandardMaterial({ color: 0x0369a1, metalness: 0.15, roughness: 0.35 });
    const feedInsideMat = new THREE.MeshStandardMaterial({ color: 0xfbbf24, roughness: 0.95 });

    function createBlueHopperUnit() {
        const hg = new THREE.Group();
        // Bibir atas kotak
        const topBox = new THREE.Mesh(new THREE.BoxGeometry(0.46, 0.18, 0.36), blueHopperMat);
        topBox.position.y = 0.09;
        hg.add(topBox);
        // Permukaan pakan dalam
        const feed = new THREE.Mesh(new THREE.PlaneGeometry(0.4, 0.30), feedInsideMat);
        feed.rotation.x = -Math.PI / 2;
        feed.position.y = 0.17;
        hg.add(feed);
        // Badan mengkerucut (piramid terpancung terbalik)
        const body = new THREE.Mesh(new THREE.CylinderGeometry(0.28, 0.10, 0.30, 4), blueHopperMat);
        body.position.y = -0.14;
        body.rotation.y = Math.PI / 4;
        hg.add(body);
        // Cerat bawah
        const spout = new THREE.Mesh(new THREE.BoxGeometry(0.14, 0.09, 0.14), blueHopperMat);
        spout.position.y = -0.32;
        hg.add(spout);
        return hg;
    }

    // Tangga Kiri (Z = -1.32)
    const pL1 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 2.2, 0.04), ladderMat);
    pL1.position.set(-0.22, 1.1, -1.32);
    feederCart3D.add(pL1);
    const pL2 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 2.2, 0.04), ladderMat);
    pL2.position.set(0.22, 1.1, -1.32);
    feederCart3D.add(pL2);
    for (let ry = 0.25; ry <= 2.05; ry += 0.44) {
        const rung = new THREE.Mesh(new THREE.CylinderGeometry(0.018, 0.018, 0.44), ladderMat);
        rung.rotation.z = Math.PI / 2;
        rung.position.set(0, ry, -1.32);
        feederCart3D.add(rung);
    }
    // Corong Kiri T2 (atas)
    const hL2 = createBlueHopperUnit();
    hL2.position.set(0, 1.58, -1.32);
    feederCart3D.add(hL2);
    // Corong Kiri T1 (bawah)
    const hL1 = createBlueHopperUnit();
    hL1.position.set(0, 0.70, -1.32);
    feederCart3D.add(hL1);

    // Tangga Kanan (Z = +1.32)
    const pR1 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 2.2, 0.04), ladderMat);
    pR1.position.set(-0.22, 1.1, 1.32);
    feederCart3D.add(pR1);
    const pR2 = new THREE.Mesh(new THREE.BoxGeometry(0.04, 2.2, 0.04), ladderMat);
    pR2.position.set(0.22, 1.1, 1.32);
    feederCart3D.add(pR2);
    for (let ry = 0.25; ry <= 2.05; ry += 0.44) {
        const rung = new THREE.Mesh(new THREE.CylinderGeometry(0.018, 0.018, 0.44), ladderMat);
        rung.rotation.z = Math.PI / 2;
        rung.position.set(0, ry, 1.32);
        feederCart3D.add(rung);
    }
    // Corong Kanan T2 (atas)
    const hR2 = createBlueHopperUnit();
    hR2.position.set(0, 1.58, 1.32);
    feederCart3D.add(hR2);
    // Corong Kanan T1 (bawah)
    const hR1 = createBlueHopperUnit();
    hR1.position.set(0, 0.70, 1.32);
    feederCart3D.add(hR1);

    // Gantry balok atas penghubung (jembatan rel)
    const bridge = new THREE.Mesh(new THREE.BoxGeometry(0.1, 0.1, 2.64), ladderMat);
    bridge.position.set(0, 2.25, 0);
    feederCart3D.add(bridge);

    // Hanger gantung ke rel
    const cartHanger = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.32, 0.84), ladderMat);
    cartHanger.position.set(0, 2.55, 0);
    feederCart3D.add(cartHanger);

    // Roda di rel
    const wheelMat = new THREE.MeshStandardMaterial({ color: 0xf59e0b, metalness: 0.75 });
    const wL = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.065, 0.11), wheelMat);
    wL.position.set(0, 2.58, -0.42);
    wL.rotation.x = Math.PI / 2;
    feederCart3D.add(wL);
    const wR = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.065, 0.11), wheelMat);
    wR.position.set(0, 2.58, 0.42);
    wR.rotation.x = Math.PI / 2;
    feederCart3D.add(wR);

    feederCart3D.position.x = feederPositionX;
    scene3D.add(feederCart3D);

    // Kabel Sling Penarik
    const cableMat = new THREE.MeshBasicMaterial({ color: 0x9ca3af });
    cableMeshA = new THREE.Mesh(new THREE.CylinderGeometry(0.013, 0.013, 1), cableMat);
    cableMeshA.rotation.z = Math.PI / 2;
    scene3D.add(cableMeshA);
    cableMeshB = new THREE.Mesh(new THREE.CylinderGeometry(0.013, 0.013, 1), cableMat);
    cableMeshB.rotation.z = Math.PI / 2;
    scene3D.add(cableMeshB);
    updateCableLines();

    // I. Kipas Blower — Gantung di Plafon Berseri per ~4.5m di Sepanjang Lorong
    fanBladesList = [];

    function createCeilingFanUnit(xPos, yPos, zPos, isExhaust = false) {
        const fg = new THREE.Group();
        fg.position.set(xPos, yPos, zPos);
        // Hadapkan kipas menatap sepanjang lorong (sumbu X)
        fg.rotation.y = Math.PI / 2;

        const R = isExhaust ? 0.42 : 0.38;

        // Rangka lingkar luar
        const frame = new THREE.Mesh(
            new THREE.TorusGeometry(R, 0.045, 12, 28),
            new THREE.MeshStandardMaterial({ color: 0x334155, metalness: 0.8, roughness: 0.25 })
        );
        fg.add(frame);

        // Batang strut gantung ke balok tarik kuda-kuda (Y = 3.60)
        const strutLen = 3.60 - (yPos + R);
        if (strutLen > 0.05) {
            const strut = new THREE.Mesh(
                new THREE.CylinderGeometry(0.018, 0.018, strutLen),
                steelMat
            );
            strut.position.set(0, R + strutLen / 2, 0);
            fg.add(strut);
        }

        // Baling-baling (4 bilah, aerodinamis biru muda)
        const blades = new THREE.Group();
        const bladeMat = new THREE.MeshStandardMaterial({ color: 0x38bdf8, metalness: 0.2, roughness: 0.4 });
        for (let bi = 0; bi < 4; bi++) {
            const blade = new THREE.Mesh(new THREE.BoxGeometry(0.10, R * 0.85, 0.016), bladeMat);
            const angle = (Math.PI / 2) * bi;
            blade.rotation.z = angle;
            blade.position.set(
                Math.cos(angle + Math.PI / 4) * R * 0.4,
                Math.sin(angle + Math.PI / 4) * R * 0.4,
                0
            );
            blades.add(blade);
        }
        fg.add(blades);

        scene3D.add(fg);
        fanBladesList.push(blades);
        return fg;
    }

    // 3 unit kipas sirkulasi plafon berjarak 4.5m sepanjang lorong (ketinggian Y = 3.0m)
    createCeilingFanUnit(-4.5, 3.0, 0);       // Zona 1 (Pangkal)
    createCeilingFanUnit( 0.0, 3.0, 0);       // Zona 2 (Tengah)
    createCeilingFanUnit( 4.5, 3.0, 0);       // Zona 3 (Ujung)
    createCeilingFanUnit( 9.0, 3.1, 0, true); // Kipas Blower Ujung Portal (Exhaust)

    // J. Partikel Air Sprinkler MURNI DI LUAR ATAP (Puncak Y=4.88 meluncur ke Eaves Y=3.60)
    const particleCount = 300;
    const waterGeo = new THREE.BufferGeometry();
    const waterPos = new Float32Array(particleCount * 3);
    for (let k = 0; k < particleCount; k++) {
        const i = k * 3;
        const isLeft = (k % 2 === 0);
        const progress = Math.random();
        const absZ = 0.15 + progress * 3.5; // z dari 0.15 s/d 3.65 (di atas seng)
        const z = isLeft ? -absZ : absZ;
        const y = 4.88 - (1.25 / 3.7) * absZ + 0.05; // Selalu 5cm di atas permukaan seng luar!

        waterPos[i]     = (Math.random() - 0.5) * 19.5; // sepanjang atap kandang
        waterPos[i + 1] = y;
        waterPos[i + 2] = z;
    }
    waterGeo.setAttribute('position', new THREE.BufferAttribute(waterPos, 3));
    const waterMat = new THREE.PointsMaterial({ color: 0x38bdf8, size: 0.14, transparent: true, opacity: 0.9 });
    roofWaterParticles3D = new THREE.Points(waterGeo, waterMat);
    roofWaterParticles3D.visible = false;
    scene3D.add(roofWaterParticles3D);

    // K. Partikel Curahan Pakan dari Corong Biru ke Talang Kuning
    function createFeedEmitter(yBase, zBase) {
        const pCount = 22;
        const geo = new THREE.BufferGeometry();
        const pos = new Float32Array(pCount * 3);
        for (let i = 0; i < pCount * 3; i += 3) {
            pos[i]     = (Math.random() - 0.5) * 0.10;
            pos[i + 1] = yBase - Math.random() * 0.14;
            pos[i + 2] = zBase + (Math.random() - 0.5) * 0.08;
        }
        geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
        const mat = new THREE.PointsMaterial({ color: 0xfbbf24, size: 0.065 });
        const p = new THREE.Points(geo, mat);
        p.visible = false;
        return p;
    }

    feedP_L_T2 = createFeedEmitter(1.28, -1.32);
    feedP_L_T1 = createFeedEmitter(0.38, -1.32);
    feedP_R_T2 = createFeedEmitter(1.28,  1.32);
    feedP_R_T1 = createFeedEmitter(0.38,  1.32);

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

    // 4. Animasi Percikan Air Sprinkler 100% DI LUAR ATAP (Puncak Y=4.88, Lereng turun ke Eaves Y=3.60 di Z=±3.7)
    if (isSprinklerActive3D && roofWaterParticles3D) {
        roofWaterParticles3D.visible = true;
        const pos = roofWaterParticles3D.geometry.attributes.position.array;
        for (let k = 0; k < pos.length / 3; k++) {
            const i = k * 3;
            const isLeft = pos[i + 2] < 0;
            if (isLeft) {
                pos[i + 2] -= 0.045; // Mengalir turun di lereng kiri seng luar
                if (pos[i + 2] < -3.65) {
                    pos[i + 2] = -0.15; // Reset ke dekat puncak nok luar
                    pos[i] = (Math.random() - 0.5) * 19.5;
                }
            } else {
                pos[i + 2] += 0.045; // Mengalir turun di lereng kanan seng luar
                if (pos[i + 2] > 3.65) {
                    pos[i + 2] = 0.15; // Reset ke dekat puncak nok luar
                    pos[i] = (Math.random() - 0.5) * 19.5;
                }
            }
            // Y presisi 5cm di atas permukaan seng galvalum luar
            pos[i + 1] = 4.88 - (1.25 / 3.7) * Math.abs(pos[i + 2]) + 0.05;
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
        camera3D.position.set(13, 6.5, 10);
        controls3D.target.set(0, 1.8, 0);
    } else if (tipe === 'lorong') {
        camera3D.position.set(feederPositionX - 2.8, 1.6, 0);
        controls3D.target.set(feederPositionX + 4.5, 1.4, 0);
    } else if (tipe === 'atas') {
        camera3D.position.set(0, 18, 0.01);
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
