{{-- =========================================================================
     DIGITAL TWIN 3D VIEWER: SIMULASI INTERAKTIF KANDANG AYAM PINTAR
     Three.js WebGL Engine (60 FPS, OrbitControls 360°, Particle Effects)
     Inovator: Aditya Ramadhani (SMKN 2 Surabaya) - RansumKu
     Fitur: Kereta Hopper Katrol L298N, Sirkulasi Blower, Misting Atap, Ayam 3D
     ========================================================================= --}}

<div class="bg-white rounded-xl border border-line p-4 sm:p-5 md:p-6 shadow-2xs space-y-3.5 sm:space-y-4" id="section-digital-twin-3d">
    {{-- Header Digital Twin 3D (Rapi & Bebas Tombol Menumpuk) --}}
    <div class="flex items-center justify-between gap-3 pb-3 border-b border-line">
        <div class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[24px] text-purple-600 shrink-0">view_in_ar</span>
            <div>
                <h3 class="text-sm font-black uppercase tracking-wider text-ink">Digital Twin 3D Kandang Pintar</h3>
                <p class="text-[11px] text-muted hidden sm:block">Simulasi fisik interaktif 3D: Kereta pakan katrol tali sling meluncur di rel, corong pakan 2 tingkat, ayam baterai, blower, dan sprinkler atap</p>
            </div>
        </div>

        {{-- Indikator Kinerja Hardware --}}
        <div class="flex items-center gap-2 text-xs font-semibold text-muted shrink-0">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="hidden sm:inline">60 FPS Hardware-Accelerated</span>
            <span class="sm:hidden font-mono text-[11px] text-emerald-600 font-bold">60 FPS</span>
        </div>
    </div>

    {{-- Area Canvas 3D Viewport (Leluasa 360° Bebas Hambatan Sentuh) --}}
    <div class="relative w-full h-[380px] sm:h-[440px] md:h-[490px] rounded-2xl overflow-hidden bg-sky-100 border border-slate-300 shadow-inner group">
        {{-- Canvas Container --}}
        <div id="canvas-container-3d" class="w-full h-full cursor-grab active:cursor-grabbing"></div>

        {{-- Overlay Loading State --}}
        <div id="loader-3d" class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center gap-3 text-slate-300 z-10 transition-opacity duration-300">
            <span class="material-symbols-outlined text-4xl text-purple-400 animate-spin">cyclone</span>
            <span class="text-xs font-bold tracking-wider uppercase text-purple-300">Memuat Model 3D Digital Twin...</span>
        </div>

        {{-- Top HUD Bar: Status Realtime & Utility Pintar --}}
        <div class="absolute top-2.5 sm:top-3 inset-x-2.5 sm:inset-x-3 flex items-start justify-between gap-2 z-20 pointer-events-none">
            {{-- Status HUD Ringkas (Kiri Atas) --}}
            <div class="flex flex-col gap-1.5 pointer-events-auto max-w-[65%] sm:max-w-none">
                <div class="inline-flex items-center gap-2 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-xl bg-slate-900/90 backdrop-blur-md border border-slate-700/80 text-white shadow-lg text-[10px] sm:text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0" id="dot-feeder-3d"></span>
                    <span class="text-slate-300 font-normal hidden sm:inline">Kereta Pakan:</span>
                    <span class="text-emerald-300 truncate" id="status-feeder-3d">Siaga di Pangkal (Titik A)</span>
                </div>

                <div id="hud-hen-model" style="display:none" class="items-center gap-2 px-2.5 py-1 rounded-lg bg-slate-900/80 backdrop-blur-md border border-slate-800 text-[10px] font-semibold text-slate-300" role="status" aria-live="polite">
                    <span id="hud-hen-model-icon" class="material-symbols-outlined text-[13px] text-amber-300 animate-spin">progress_activity</span>
                    <span id="hud-hen-model-text">Memuat model ayam 3D...</span>
                </div>

                <div class="inline-flex flex-wrap items-center gap-2 px-2.5 py-1 rounded-lg bg-slate-900/80 backdrop-blur-md border border-slate-800 text-[10px] font-semibold text-slate-300">
                    <span class="flex items-center gap-1" id="badge-kipas-3d">
                        <span class="material-symbols-outlined text-[13px] text-slate-400" id="icon-fan-3d">mode_fan</span>
                        <span id="text-fan-3d">Kipas OFF</span>
                    </span>
                    <span class="text-slate-600">•</span>
                    <span class="flex items-center gap-1" id="badge-atap-3d">
                        <span class="material-symbols-outlined text-[13px] text-slate-400" id="icon-roof-3d">water_drop</span>
                        <span id="text-roof-3d">Sprinkler OFF</span>
                    </span>
                    <span class="text-slate-600">&bull;</span>
                    <span class="flex items-center gap-1" id="badge-telur-3d" title="Telur terkumpul di Bak Pengumpul ujung sabuk">
                        <span>Telur</span>
                        <span id="text-egg-3d">0</span>
                    </span>
                </div>
            </div>

            {{-- Quick Utility Toggles (Kanan Atas: Pin IoT & 360 Auto-Putar) --}}
            <div class="flex items-center gap-1.5 pointer-events-auto shrink-0">
                <button type="button" onclick="toggleLabelsIoT3D()" id="btn-labels-iot" class="px-2.5 sm:px-3 py-1.5 rounded-xl text-[11px] sm:text-xs font-bold bg-cyan-600 hover:bg-cyan-500 text-white shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer border border-cyan-400/40" title="Tampilkan / Sembunyikan Label Pin Komponen IoT">
                    <span class="material-symbols-outlined text-[15px] sm:text-[16px]">pin_drop</span>
                    <span class="hidden sm:inline">Pin IoT</span>
                </button>
                <button type="button" onclick="toggleAutoRotate3D()" id="btn-autorotate-3d" class="px-2.5 sm:px-3 py-1.5 rounded-xl text-[11px] sm:text-xs font-bold bg-slate-900/85 hover:bg-slate-800 text-purple-300 border border-slate-700/80 shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer" title="Putar Otomatis 360 Derajat">
                    <span class="material-symbols-outlined text-[15px] sm:text-[16px]">360</span>
                    <span class="hidden sm:inline">Auto-Putar</span>
                </button>
            </div>
        </div>

        {{-- Hint Navigasi Halus (Bawah Viewport) --}}
        <div class="absolute bottom-2.5 inset-x-3 flex items-center justify-between pointer-events-none text-[10px] text-slate-400 z-20">
            <span class="bg-slate-900/85 px-2 py-0.5 rounded-md backdrop-blur border border-slate-800 text-[10px] flex items-center gap-1">
                <span class="hidden sm:inline"><strong>Klik & Geser</strong> putar · <strong>Scroll</strong> zoom · <strong>Klik Kanan</strong> pan</span>
                <span class="sm:hidden"><strong>Sentuh & Geser</strong> untuk memutar 360°</span>
            </span>
            <span class="hidden sm:inline bg-slate-900/85 px-2 py-0.5 rounded-md backdrop-blur border border-slate-800 font-mono text-emerald-400 text-[10px]">
                Katrol Tali Sling · L298N
            </span>
        </div>
    </div>

    {{-- ================================================================= --}}
    {{-- BARIS PUSAT KONTROL UTAMA SIMULASI 3D (ERGONOMIC CONTROL CENTER)  --}}
    {{-- Tertata rapi di bawah canvas: Ramah sentuhan mobile, tidak numpuk --}}
    {{-- ================================================================= --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pt-1">
        
        {{-- Sektor 1: Tombol Aksi Simulasi Fisik (Primary Action Buttons — Sinkron Aktuator Fisik) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 w-full md:w-auto md:flex md:flex-wrap md:items-center">
            {{-- Tombol 1: Kereta Pakan Motor Kuning L298N (GPIO 18, 19) --}}
            <button type="button" onclick="toggleSimulasiGerakPakan()" id="btn-trigger-pakan-3d" class="px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-700/20 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer border border-emerald-400/30" title="Jalankan / Hentikan Motor Kereta Pakan L298N (D18/D19)">
                <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                <div class="text-left">
                    <span class="block leading-tight font-extrabold" id="text-feeder-btn">Jalankan Kereta 3D</span>
                    <span class="block text-[9px] text-emerald-100 font-normal leading-tight hidden sm:block" id="subtext-feeder-btn">Motor L298N · D18/D19</span>
                </div>
            </button>

            {{-- Tombol 2: Uji Cuaca Panas (Kipas 12V D26 & Pompa 5V D14) --}}
            <button type="button" onclick="toggleUjiCuacaPanas3D()" id="btn-cuaca-3d" class="px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700 shadow-md transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer" title="Simulasikan Kipas DC 12V (D26) & Pompa Celup 5V (D14)">
                <span class="material-symbols-outlined text-[18px]" id="icon-cuaca-btn">thermostat</span>
                <div class="text-left">
                    <span class="block leading-tight font-extrabold" id="text-cuaca-btn">Uji Cuaca Panas</span>
                    <span class="block text-[9px] text-amber-200/80 font-normal leading-tight hidden sm:block" id="subtext-cuaca-btn">Kipas D26 &amp; Pompa D14</span>
                </div>
            </button>
        </div>

        {{-- Sektor 2: Presets Sudut Pandang Kamera (Camera Perspective Deck - Abu-abu Slider) --}}
        <div class="flex items-center gap-1.5 justify-between sm:justify-end border-t md:border-t-0 pt-2.5 md:pt-0 border-line/60">
            <span class="text-[11px] font-bold text-muted mr-1 hidden lg:inline">Sudut Pandang:</span>
            
            <div class="inline-flex p-1 rounded-lg bg-slate-100 border border-slate-200/80 gap-1 w-full sm:w-auto justify-center">
                <button type="button" onclick="setKamera3D('perspektif')" id="btn-cam-perspektif" class="flex-1 sm:flex-initial px-2.5 sm:px-3 py-1.5 rounded-md text-[11px] font-bold bg-white text-slate-800 shadow-xs border border-slate-200/80 flex items-center justify-center gap-1 cursor-pointer transition" title="Tampak Isometrik Luar">
                    <span class="material-symbols-outlined text-[15px] text-slate-500">videocam</span>
                    <span>Perspektif</span>
                </button>
                <button type="button" onclick="setKamera3D('lorong')" id="btn-cam-lorong" class="flex-1 sm:flex-initial px-2.5 sm:px-3 py-1.5 rounded-md text-[11px] font-medium text-slate-500 hover:text-slate-800 hover:bg-white/60 flex items-center justify-center gap-1 cursor-pointer transition" title="Tampak Lorong Tengah Antar Baterai">
                    <span class="material-symbols-outlined text-[15px] text-slate-500">straighten</span>
                    <span>Lorong Pakan</span>
                </button>
                <button type="button" onclick="setKamera3D('atas')" id="btn-cam-atas" class="flex-1 sm:flex-initial px-2.5 sm:px-3 py-1.5 rounded-md text-[11px] font-medium text-slate-500 hover:text-slate-800 hover:bg-white/60 flex items-center justify-center gap-1 cursor-pointer transition" title="Tampak Atas Denah Kandang">
                    <span class="material-symbols-outlined text-[15px] text-slate-500">vertical_align_top</span>
                    <span>Tampak Atas</span>
                </button>
            </div>
        </div>

    </div>
</div>

{{-- Script Three.js & OrbitControls dari CDN Resmi --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>

<script>
// =========================================================================
// KONFIGURASI TUNING UTAMA (MAKAN, LUBANG SANGKAR, JALUR & BAK TELUR)
// =========================================================================
const FEED = {
    cageFrontZ: 1.40,         // |Z| bidang kawat depan sangkar (m); diverifikasi otomatis dari CAGE_CONFIG
    bodyFrontClear: 0.015,    // celah aman dada ayam di belakang kawat depan (m) agar badan tidak menembus kawat
    beakFwd: 0.222,           // jarak maju ujung paruh dari poros pusat ayam saat berdiri tegak (m)
    peckGap: 0.04,            // jarak aman target patokan di luar kawat depan masuk ke talang kuning (m)
    maxNeck: 0.20,            // julur leher maksimum aReach.x (m) agar leher tetap proporsional
    peckPitchMax: 0.95,       // amplitudo sudut anggukan kepala maksimum saat mematok (rad)
    peckHz: 2.4,              // kecepatan gerak matok sinusoidal (siklus per detik)
    bodyTilt: 0.45,           // porsi anggukan leher yang ikut menundukkan badan di atas kaki
    headUpFrac: 0.78,         // fraksi anggukan minimum saat kepala di dalam lubang makan (0..1) agar bebas kawat
    blendSec: 0.25,           // durasi transisi halus feedBlend (0..1) maju/mundur sangkar (dtk)
    lingerSec: 1.2,           // durasi sisa pakan di talang setelah kereta pakan berlalu (dtk)
    touchLift: 0.002,         // celah mikro di atas permukaan atas talang (m) agar paruh tepat menyentuh
    tolerance: 0.004          // toleransi galat kontak paruh terhadap permukaan atas talang (m)
};

const FEED_HOLE = {
    width: 0.074,             // lebar celah leher bagian bawah (m) — rentang 0.07-0.08 m
    archWidth: 0.080,         // lebar lengkung atas "keyhole" tempat kepala masuk (m) — rentang 0.07-0.08 m
    height: 0.200,            // tinggi total bukaan makan lonjong/keyhole (m) — rentang 0.16-0.20 m
    bottomOffset: 0.095,      // tinggi tepi bawah lubang dari dasar modul floorY (m), tepat di atas bibir talang
    slotXs: [-0.58, 0.0, 0.58], // posisi X lokal ke-3 lubang makan per modul sangkar (1 per ayam)
    wireRadius: 0.0028,       // jari-jari batang kawat sangkar galvanis (m)
    frameThick: 0.006,        // tebal profil bingkai logam di sekeliling tiap lubang makan (m)
    frameDepth: 0.014,        // kedalaman profil bingkai logam lubang makan (m)
    vBars: 25,                // jumlah batang kawat vertikal pada panel depan per modul
    hBarOffsets: [0.055, 0.095, 0.195, 0.295, 0.44, 0.58] // posisi Y relatif batang kawat horizontal depan (m)
};

const EGG_PATH = {
    floorSlopeDeg: 8.5,       // kemiringan lantai kawat sangkar menuju sabuk telur (derajat, ±8-10°)
    beltZ: 1.52,              // |Z| tengah sabuk telur di tepi depan dalam sangkar (m)
    beltStartX: -8.90,        // koordinat X pangkal sabuk telur (m)
    beltEndX: 8.85,           // koordinat X ujung sabuk telur sebelum masuk bak pengumpul (m)
    beltOffsetY: 0.004,       // tinggi permukaan atas sabuk telur dari floorY modul (m), konstan sepanjang X
    layScaleSec: 0.20,        // fase 1 (LAY): durasi skala telur membesar halus 0 -> 1 di bawah ekor (dtk)
    gravity: 9.8,             // fase 2 (DROP): percepatan gravitasi jatuh lurus ke lantai miring (m/s^2)
    slopeAccel: 0.85,         // fase 3 (ROLL_SLOPE): percepatan konstan menuruni lantai miring (m/s^2)
    slopeMaxSpeed: 0.55,      // kecepatan maksimum saat menggelinding di lantai miring (m/s)
    beltSpeed: 0.68,          // fase 4 (ON_BELT): kecepatan konstan di tengah sabuk telur menuju bak (m/s)
    minSpacing: 0.12,         // jarak minimal antar dua telur berurutan di sabuk yang sama (m)
    collectSec: 0.30,         // fase 5 (COLLECT): durasi animasi masuk ke bak pengumpul sebelum di-pool (dtk)
    maxActiveEggs: 60,        // batas maksimum telur aktif di scene (recycle tertua jika terlampaui)
    eggScale: 1.55,           // faktor skala visual telur cokelat ringan (tanpa memuat egg.glb)
    layIntervalSec: 2.6,      // rata-rata interval antar kejadian ayam BERTELUR di seluruh kandang (dtk)
    layDurationSec: 1.05,     // durasi kontraksi & getar halus ayam saat BERTELUR (~1 dtk)
    layPeakFrac: 0.80         // fraksi durasi BERTELUR saat puncak kontraksi mengeluarkan telur (0..1)
};

const EGG_COLLECTOR = {
    x: 9.12,                  // koordinat X pusat bak pengumpul di ujung +X sabuk (beltEndX + 0.27 m)
    dropDepth: 0.068,         // kedalaman bantalan dasar bak di bawah permukaan ujung sabuk telur (m)
    zBySide: { '-1': -1.52, '1': 1.52 }, // koordinat Z dunia bak mengikuti sabuk telur kiri & kanan
    basketSize: { w: 0.42, h: 0.13, d: 0.34 }, // dimensi bak pengumpul (m)
    labelText: 'BAK PENGUMPUL TELUR',
    stations: []              // diisi otomatis dari koordinat scene saat buildKandang3DScene()
};

const HEN_IDLE = {
    breathHz: 0.95,           // frekuensi napas mikro asinkron per ayam (siklus/dtk)
    breathSquatM: 0.0035,     // amplitudo naik-turun badan halus saat bernapas (m)
    breathPitchRad: 0.024,    // ayunan mikro leher mengikuti napas (rad)
    breathTailRad: 0.032,     // goyang mikro ekor penyeimbang napas (rad)
    headSnapSpeed: 18.0,      // kecepatan menoleh khas unggas / saccadic head-snap (1/dtk)
    headBobKickM: 0.015,      // dorongan maju leher sesaat ketika kepala menoleh cepat (m)
    roamXMax: 0.075,          // batas aman geser kiri-kanan di dalam sekat kompartemen (m)
    roamAbsZMin: 1.78,        // batas maju saat IDLE agar paruh aman di belakang kawat depan (m)
    roamAbsZMax: 2.30,        // batas mundur saat IDLE dekat pipa nipple drinker belakang (m)
    walkSpeed: 0.16,          // kecepatan melangkah pendek di dalam sangkar (m/dtk)
    walkBobHz: 3.6,           // irama anggukan leher & langkah kaki saat berjalan (Hz)
    foragePitch: 0.72,        // sudut menunduk saat mematuk eksplorasi lantai kawat (rad)
    forageHz: 2.8,            // kecepatan patokan kecil saat eksplorasi lantai (Hz)
    drinkPitch: -0.26,        // sudut mendongak saat minum di pipa nipple belakang (rad)
    drinkHz: 2.5,             // irama mematuk tetesan air di nipple drinker (Hz)
    preenYaw: 0.82,           // sudut menoleh ke sayap/punggung saat merapikan bulu (rad)
    tailWagHz: 5.5,           // frekuensi kibas ekor cepat saat merapikan bulu (Hz)
    feederAlertDist: 3.6,     // jarak X kereta pakan yang memicu ayam antusias bersiap di depan (m)
    strideLengthM: 0.072,     // panjang satu siklus langkah kiri-kanan di dalam sangkar (m)
    minStepHz: 2.8,           // irama angkat cakar minimum saat berputar / menggeser langkah (Hz)
    scratchHz: 4.3,           // irama cakar mengais lantai kawat pada fase awal FORAGE (Hz)
    fidgetStepHz: 2.4,        // irama geser tumpuan kaki di lantai miring saat berdiri (Hz)
    fidgetMinSec: 1.6,        // jeda minimum antar geser tumpuan cakar saat berdiri (dtk)
    fidgetMaxSec: 3.8,        // jeda maksimum antar geser tumpuan cakar saat berdiri (dtk)
    weightRollRad: 0.042      // ayunan miring badan mikro mengikuti perpindahan tumpuan kaki kiri-kanan (rad)
};

let scene3D, camera3D, renderer3D, controls3D;
let feederCart3D, roofWaterParticles3D;
let fanBladesList = [];
let feedP_L_T1, feedP_L_T2, feedP_R_T1, feedP_R_T2;
let coopLight1, coopLight2;
let ceilingLights = [];
let cableMeshA, cableMeshB;
let chickensList = [];
let iotLabelsGroup;
let isLabelsVisible = true;

// Status & Variabel Animasi 3D
let isFeederRunning3D = false;
let feederDirection3D = 1; // 1 = Maju (A -> B), -1 = Mundur (B -> A)
let feederPositionX = -7.0; // Dari x = -7.0 (Titik A) ke x = 7.0 (Titik B)
const FEEDER_LIMIT_A = -7.0;
const FEEDER_LIMIT_B = 7.0;
let isFanSpinning3D = false;
let isSprinklerActive3D = false;
let isLampOn3D = false;
let chickenBobTime = 0;

// Flag simulasi mandiri (agar tidak ditimpa oleh background poller setiap 3 detik)
let isManualSimulationHot = false;
let isManualFeederActive = false;
let isManualLampActive3D = false;
let feederPauseUntil = 0;

// Siklus hidup scene (disposal), lingkungan peternakan, & registri talang pakan
let rafHandle3D = 0;
let isDisposed3D = true;
let feedTroughs = [];
let farmSkyUniforms = null;
let farmEnvRT = null;
let coopBulbMat = null;
let neighborHenSlots = [];
let neighborFanBlades = [];
let neighborCarts = [];
let neighborAnimCursor = 0;

function initThreeJSKandang() {
    const container = document.getElementById('canvas-container-3d');
    const loader = document.getElementById('loader-3d');
    if (!container) return;

    if (typeof THREE === 'undefined') {
        setTimeout(initThreeJSKandang, 150);
        return;
    }

    isDisposed3D = false;
    henModel = PROC_HEN_MODEL;
    henInst = null;
    neighborHenSlots = [];
    neighborFanBlades = [];
    neighborCarts = [];
    neighborAnimCursor = 0;
    nextLayAt = 0;
    decisionCursor = 0;
    lastFrameTimeMs = 0;
    eggStats = { total: 0, perLane: {} };
    selectedPin = null;
    const eggHud = document.getElementById('text-egg-3d');
    if (eggHud) eggHud.textContent = '0';

    const width = container.clientWidth;
    const height = container.clientHeight;

    // 1. Scene (Atmosfer Pagi Tropis Realistis Kawasan Peternakan Indonesia)
    scene3D = new THREE.Scene();
    scene3D.background = new THREE.Color(0xc4dcf2);
    scene3D.fog = new THREE.FogExp2(0xcde2f3, 0.0034);

    // 2. Camera (jarak pandang luas hingga lembah & pegunungan horizon)
    camera3D = new THREE.PerspectiveCamera(50, width / height, 0.1, 320);
    camera3D.position.set(13, 6.5, 10);

    // 3. Renderer (ACES Filmic Tone Mapping untuk rentang dinamis sinematik alami)
    renderer3D = new THREE.WebGLRenderer({ antialias: true, alpha: false });
    renderer3D.setSize(width, height);
    renderer3D.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer3D.shadowMap.enabled = true;
    renderer3D.shadowMap.type = THREE.PCFSoftShadowMap;
    if (typeof THREE.ACESFilmicToneMapping !== 'undefined') {
        renderer3D.toneMapping = THREE.ACESFilmicToneMapping;
        renderer3D.toneMappingExposure = 0.88;
    }
    container.innerHTML = ''; // Pastikan bersih
    container.appendChild(renderer3D.domElement);

    // Environment refleksi IBL multi-zona (langit zenit, pita horizon hangat, kubah matahari, & pantulan lembah rumput)
    if (farmEnvRT) {
        farmEnvRT.dispose();
        farmEnvRT = null;
    }
    if (typeof THREE.PMREMGenerator === 'function') {
        try {
            const pmrem = new THREE.PMREMGenerator(renderer3D);
            const envScene = new THREE.Scene();
            envScene.background = new THREE.Color(0x4a6b8c);
            const envHorizon = new THREE.Mesh(
                new THREE.CylinderGeometry(9.6, 9.6, 3.2, 16, 1, true),
                new THREE.MeshBasicMaterial({ color: 0x8ea9be, side: THREE.BackSide })
            );
            envHorizon.position.y = 0.8;
            envScene.add(envHorizon);
            const envGround = new THREE.Mesh(
                new THREE.SphereGeometry(10, 12, 8, 0, Math.PI * 2, Math.PI * 0.5, Math.PI * 0.5),
                new THREE.MeshBasicMaterial({ color: 0x26381b, side: THREE.BackSide })
            );
            envScene.add(envGround);
            const envSun = new THREE.Mesh(
                new THREE.SphereGeometry(1.15, 8, 8),
                new THREE.MeshBasicMaterial({ color: 0xfff0cc })
            );
            envSun.position.set(5.5, 6.8, 4.2);
            envScene.add(envSun);
            farmEnvRT = pmrem.fromScene(envScene, 0.035);
            scene3D.environment = farmEnvRT.texture;
            envHorizon.geometry.dispose();
            envHorizon.material.dispose();
            envGround.geometry.dispose();
            envGround.material.dispose();
            envSun.geometry.dispose();
            envSun.material.dispose();
            pmrem.dispose();
        } catch (_) {
            farmEnvRT = null;
        }
    }

    // 4. OrbitControls
    if (typeof THREE.OrbitControls !== 'undefined') {
        controls3D = new THREE.OrbitControls(camera3D, renderer3D.domElement);
        controls3D.enableDamping = true;
        controls3D.dampingFactor = 0.05;
        controls3D.maxPolarAngle = Math.PI / 2 - 0.025; // Menjaga kamera tetap di atas hamparan rumput
        controls3D.minDistance = 4;
        controls3D.maxDistance = 58;
        controls3D.autoRotate = false;
        controls3D.autoRotateSpeed = 1.0;
        controls3D.target.set(0, 1.8, 0);
    }

    // 5. Pencahayaan Matahari Pagi Tropis + Bounce Light Rumput (bayangan dedaunan & rangka tajam alami)
    scene3D.add(new THREE.AmbientLight(0xdcebff, 0.19));
    scene3D.add(new THREE.HemisphereLight(0xd9efff, 0x395021, 0.34));

    const dirLight = new THREE.DirectionalLight(0xfff2cf, 1.06);
    dirLight.position.set(16, 18.5, 11.5);
    dirLight.castShadow = true;
    dirLight.shadow.mapSize.width = 2048;
    dirLight.shadow.mapSize.height = 2048;
    dirLight.shadow.bias = -0.00035;
    dirLight.shadow.camera.near = 1;
    dirLight.shadow.camera.far = 72;
    dirLight.shadow.camera.left = -26;
    dirLight.shadow.camera.right = 22;
    dirLight.shadow.camera.top = 20;
    dirLight.shadow.camera.bottom = -22;
    scene3D.add(dirLight);

    // Lampu plafon hangat (fotoperiode ayam); coopLight1/2 tetap dipakai syncKandang3DState
    ceilingLights = [];
    [-6, -2, 2, 6].forEach((lx) => {
        const pl = new THREE.PointLight(0xffe8b0, 0.58, 9.5, 2);
        pl.position.set(lx, 3.2, 0);
        scene3D.add(pl);
        ceilingLights.push(pl);
    });
    coopLight1 = ceilingLights[1];
    coopLight2 = ceilingLights[2];

    // 6. Bangun Objek Geometri Kandang Ayam 3D
    buildKandang3DScene();
    calibrateAllChickens();
    loadHenModel();

    // Sinkronkan kondisi awal Digital Twin 3D dengan status aktual di Database
    syncKandang3DState({
        kipas: {{ !empty($farm?->relay_kipas) ? 'true' : 'false' }},
        misting: {{ !empty($farm?->relay_misting) ? 'true' : 'false' }},
        lampu: {{ !empty($farm?->relay_lampu) ? 'true' : 'false' }},
        pakan: {{ !empty($farm?->relay_pakan) ? 'true' : 'false' }}
    });

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

// =========================================================================
// AYAM PETELUR: MODEL PROSEDURAL COKELAT (CADANGAN) & MODEL GLB (INSTANCED)
// =========================================================================

/** Pose bertelur. */
const HEN_LAY = { squatM: 0.05, procSquatM: 0.03, tailLift: 0.45, shakeAmp: 0.004 };

/** Model GLB. Konstanta rig memakai satuan mentah model (sama dengan buildStaticRiggedChicken di login.js). */
const HEN_GLB = {
    url: '{{ asset('models/chicken.glb') }}',
    heightM: 0.44,                 // tinggi ayam di dalam sangkar (0.42 - 0.45 m)
    expectedRawHeight: 3.869,      // tinggi mentah chicken.glb yang dipakai untuk kalibrasi rig
    targetTriangles: 15000,        // decimation ringan yang menjaga sambungan UV bulu tetap utuh
    uvCell: 0.06,                  // ambang sel UV agar sambungan seam tekstur tidak pernah menyatu
    timeoutMs: 60000,
    anisotropyMax: 8,
    ventLocal: [0, 0.145, -0.145], // titik telur keluar (lokal ayam, meter): tepat di bawah pangkal ekor
    rig: { pnY: 2.6, pnZ: 0.95, pbY: 1.05, pbZ: 0, ptY: 2.25, ptZ: -0.85, phY: 3.18, phZ: 1.27 }
};

/** Model kinematika ayam prosedural cokelat Lohmann/ISA Brown (cadangan). Koordinat [y, z] lokal ayam, meter. */
const PROC_HEN_MODEL = {
    kind: 'prosedural',
    get pitchMax() { return FEED.peckPitchMax; },
    baseBeakFwd: 0.222,
    rig: { pn: [0.26, 0.17], pb: [0.12, 0] },
    beakRel: [0.17, 0.18],
    chestPts: [[0.2, 0.26], [0.3, 0.17], [0.12, 0.2]],
    vent: [0, 0.06, -0.28],
    squat: HEN_LAY.procSquatM
};

let henModel = PROC_HEN_MODEL;
let henInst = null;

// <FEED_SOLVER_BEGIN>
function rotateYZ(y, z, angle) {
    const c = Math.cos(angle);
    const s = Math.sin(angle);
    return [y * c - z * s, y * s + z * c];
}

/** Ujung paruh (lokal ayam, meter, sebelum skala instance) untuk anggukan `pitch` dan julur leher `reach`. */
function henBeakTip(model, pitch, reach) {
    const fwdDelta = FEED.beakFwd - (model.baseBeakFwd !== undefined ? model.baseBeakFwd : 0.222);
    const neck = rotateYZ(model.beakRel[0], model.beakRel[1] + reach + fwdDelta, pitch);
    const lean = rotateYZ(
        model.rig.pn[0] + neck[0] - model.rig.pb[0],
        model.rig.pn[1] + neck[1] - model.rig.pb[1],
        pitch * FEED.bodyTilt
    );
    return [model.rig.pb[0] + lean[0], model.rig.pb[1] + lean[1]];
}

/** Titik dada paling depan (sumbu Z lokal) setelah badan miring mengikuti anggukan. */
function henChestForward(model, pitch) {
    let max = -Infinity;
    const lean = pitch * FEED.bodyTilt;
    for (let i = 0; i < model.chestPts.length; i += 1) {
        const pt = model.chestPts[i];
        const r = rotateYZ(pt[0] - model.rig.pb[0], pt[1] - model.rig.pb[1], lean);
        max = Math.max(max, model.rig.pb[1] + r[1]);
    }
    return max;
}

/**
 * Cari pose menyentuh permukaan atas talang kuning (topY diambil dari scene):
 * - Dada ayam berhenti di belakang kawat depan (`cageFrontZ + bodyFrontClear`) agar tidak menembus kawat.
 * - Sisa jarak ke permukaan talang ditutup dinamis oleh julur leher `reach` (<= `FEED.maxNeck`).
 * - Target |Z| berada di bidang terbuka talang kuning (`[farAbsZ + 0.025, min(nearAbsZ, cageFrontZ) - peckGap]`).
 * - Jika tidak terjangkau, `peckGap` lalu `cageFrontZ` disesuaikan otomatis dan dicatat ke `console.warn`.
 */
function solveFeedPose(model, scale, homeY, trough) {
    const targetY = trough.topY + FEED.touchLift;
    const yTarget = (targetY - homeY) / scale;
    const maxReach = FEED.maxNeck / scale;
    const troughCenterAbsZ = 0.5 * (trough.farAbsZ + trough.nearAbsZ);
    const minAbsZ = trough.farAbsZ + 0.025;

    const searchWith = (frontWireAbsZ, gap) => {
        const rawMaxAbsZ = Math.min(trough.nearAbsZ, frontWireAbsZ) - gap;
        const maxAbsZ = Math.max(minAbsZ + 0.005, rawMaxAbsZ);
        const idealAbsZ = Math.min(maxAbsZ, Math.max(minAbsZ, troughCenterAbsZ));

        const evaluate = (pitch) => {
            const t0 = henBeakTip(model, pitch, 0);
            const t1 = henBeakTip(model, pitch, 1);
            const slope = t1[0] - t0[0];
            let reach = Math.abs(slope) > 1e-6 ? (yTarget - t0[0]) / slope : 0;
            reach = Math.min(maxReach, Math.max(0, reach));
            const tip = henBeakTip(model, pitch, reach);
            const chest = henChestForward(model, pitch);
            const frontAbsZ = frontWireAbsZ + FEED.bodyFrontClear + chest * scale;
            const beakAbsZ = frontAbsZ - tip[1] * scale;
            const beakY = homeY + tip[0] * scale;
            const errBand = beakAbsZ < minAbsZ ? (minAbsZ - beakAbsZ) : (beakAbsZ > maxAbsZ ? (beakAbsZ - maxAbsZ) : 0);
            const errY = Math.abs(beakY - targetY);
            const err = Math.hypot(errBand, errY);
            const score = err * 1000 + Math.abs(beakAbsZ - idealAbsZ) + (reach * scale) * 0.15;
            return { pitch, reach, chest, frontAbsZ, beakAbsZ, beakY, errBand, errY, err, score };
        };

        const samples = 120;
        let best = evaluate(0);
        for (let i = 1; i <= samples; i += 1) {
            const cand = evaluate((model.pitchMax * i) / samples);
            if (cand.score < best.score) {
                best = cand;
            }
        }
        let step = model.pitchMax / samples;
        for (let k = 0; k < 16; k += 1) {
            step *= 0.5;
            for (let d = -1; d <= 1; d += 2) {
                const p = best.pitch + d * step;
                if (p >= 0 && p <= model.pitchMax) {
                    const cand = evaluate(p);
                    if (cand.score < best.score) {
                        best = cand;
                    }
                }
            }
        }
        best.gap = gap;
        best.usedFrontZ = frontWireAbsZ;
        best.adjusted = false;
        return best;
    };

    let result = searchWith(FEED.cageFrontZ, FEED.peckGap);
    if (result.err > FEED.tolerance) {
        for (let gap = FEED.peckGap - 0.005; gap >= 0.005 - 1e-9; gap -= 0.005) {
            const cand = searchWith(FEED.cageFrontZ, Math.max(0.005, gap));
            cand.adjusted = true;
            if (cand.err < result.err) {
                result = cand;
            }
            if (cand.err <= FEED.tolerance) {
                break;
            }
        }
    }
    if (result.err > FEED.tolerance) {
        for (let shift = 0.01; shift <= 0.08 + 1e-9; shift += 0.01) {
            const cand = searchWith(FEED.cageFrontZ - shift, 0.01);
            cand.adjusted = true;
            if (cand.err < result.err) {
                result = cand;
            }
            if (cand.err <= FEED.tolerance) {
                break;
            }
        }
    }
    result.ok = result.err <= FEED.tolerance;
    return result;
}
// <FEED_SOLVER_END>

// ---------------------------------------------------------------------
// AYAM PROSEDURAL (cadangan / sementara sebelum GLB siap). Bulu cokelat Lohmann/ISA Brown.
// ---------------------------------------------------------------------
let procHenMats = null;
let procHenAssets = null;

function getProcHenMats() {
    if (procHenMats) {
        return procHenMats;
    }
    procHenMats = {
        comb: new THREE.MeshStandardMaterial({ color: 0xdc2626, roughness: 0.5 }),
        beak: new THREE.MeshStandardMaterial({ color: 0xf5b301, roughness: 0.4 }),
        leg: new THREE.MeshStandardMaterial({ color: 0xe0a030, roughness: 0.6 }),
        eye: new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.2 }),
        tail: new THREE.MeshStandardMaterial({ color: new THREE.Color().setHSL(0.06, 0.5, 0.2), roughness: 0.9 }),
        feather: [[0.065, 0.62, 0.36], [0.07, 0.58, 0.42], [0.06, 0.55, 0.32], [0.075, 0.6, 0.46]].map((hsl) => (
            new THREE.MeshStandardMaterial({ color: new THREE.Color().setHSL(hsl[0], hsl[1], hsl[2]), roughness: 0.9, metalness: 0 })
        ))
    };
    return procHenMats;
}

function getProcHenAssets() {
    if (procHenAssets) {
        return procHenAssets;
    }
    const body = new THREE.SphereGeometry(0.2, 14, 10);
    body.scale(1, 0.78, 1.3);
    const skull = new THREE.SphereGeometry(0.072, 12, 10);
    skull.scale(0.45, 0.86, 0.96);
    procHenAssets = {
        body,
        neck: new THREE.CylinderGeometry(0.03, 0.042, 0.14, 8),
        skull,
        spike: [0, 1, 2].map((c) => new THREE.BoxGeometry(0.03, 0.05 + (c === 1 ? 0.025 : 0), 0.035)),
        wattle: new THREE.CylinderGeometry(0.014, 0.02, 0.05, 6),
        beak: new THREE.ConeGeometry(0.03, 0.08, 6),
        eye: new THREE.SphereGeometry(0.01, 6, 6),
        wing: new THREE.BoxGeometry(0.03, 0.12, 0.22),
        leg: new THREE.CylinderGeometry(0.012, 0.012, 0.12, 6),
        foot: new THREE.BoxGeometry(0.032, 0.012, 0.065),
        tail: new THREE.BoxGeometry(0.1, 0.14, 0.03)
    };
    return procHenAssets;
}

function disposeProcHenAssets() {
    if (procHenAssets) {
        const geo = procHenAssets;
        Object.keys(geo).forEach((key) => {
            const value = geo[key];
            (Array.isArray(value) ? value : [value]).forEach((g) => g.dispose());
        });
        procHenAssets = null;
    }
    if (procHenMats) {
        Object.keys(procHenMats).forEach((key) => {
            const value = procHenMats[key];
            (Array.isArray(value) ? value : [value]).forEach((m) => m.dispose());
        });
        procHenMats = null;
    }
}

/**
 * Ayam petelur prosedural. Hierarki: rig > pivot (pinggul, miring) > inner > [badan, upper(leher+kepala), sayap, ekor];
 * kaki ada di rig (tetap menapak). poseIndex: 0 = berdiri, 1 = kepala sedikit menunduk, 2 = menoleh.
 * Kepala menghadap +Z lokal; tinggi sekitar 0.45 m.
 */
function createStylizedChicken(poseIndex = 0) {
    const A = getProcHenAssets();
    const M = getProcHenMats();
    const chicken = new THREE.Group();
    const pose = poseIndex % 3;
    const featherMat = M.feather[Math.floor(Math.random() * M.feather.length)];
    const pivotY = PROC_HEN_MODEL.rig.pb[0];

    const rig = new THREE.Group();
    chicken.add(rig);
    const pivot = new THREE.Group();
    pivot.position.y = pivotY;
    rig.add(pivot);
    const inner = new THREE.Group();
    inner.position.y = -pivotY;
    pivot.add(inner);

    const body = new THREE.Mesh(A.body, featherMat);
    body.position.y = 0.2;
    inner.add(body);

    const upper = new THREE.Group();
    upper.position.set(0, 0.26, 0.17);
    inner.add(upper);

    const neck = new THREE.Mesh(A.neck, featherMat);
    upper.add(neck);

    const head = new THREE.Group();
    head.position.set(0, 0.18, 0.04);
    upper.add(head);
    head.add(new THREE.Mesh(A.skull, featherMat));

    for (let c = 0; c < 3; c += 1) {
        const spike = new THREE.Mesh(A.spike[c], M.comb);
        spike.position.set(0, 0.085, -0.03 + c * 0.04);
        head.add(spike);
    }
    const wattle = new THREE.Mesh(A.wattle, M.comb);
    wattle.position.set(0, -0.07, 0.06);
    head.add(wattle);
    const beak = new THREE.Mesh(A.beak, M.beak);
    beak.rotation.x = Math.PI / 2;
    beak.position.set(0, -0.01, 0.1);
    head.add(beak);

    const legs = [];
    [-1, 1].forEach((side) => {
        const eye = new THREE.Mesh(A.eye, M.eye);
        eye.position.set(side * 0.06, 0.01, 0.04);
        head.add(eye);

        const wing = new THREE.Mesh(A.wing, featherMat);
        wing.position.set(side * 0.19, 0.22, -0.02);
        wing.rotation.z = side * -0.25;
        inner.add(wing);

        const legPivot = new THREE.Group();
        legPivot.position.set(side * 0.07, 0.12, 0.02);
        const legMesh = new THREE.Mesh(A.leg, M.leg);
        legMesh.position.y = -0.06;
        legPivot.add(legMesh);
        const footMesh = new THREE.Mesh(A.foot, M.leg);
        footMesh.position.set(0, -0.114, 0.018);
        legPivot.add(footMesh);
        legPivot.userData = { isLeft: side < 0, foot: footMesh };
        rig.add(legPivot);
        legs.push(legPivot);
    });

    const tails = [];
    [0, 1].forEach((t) => {
        const tail = new THREE.Mesh(A.tail, M.tail);
        tail.position.set(0, 0.3 + t * 0.03, -0.27 - t * 0.02);
        tail.rotation.x = -0.6 - t * 0.2;
        tail.userData.baseRotX = tail.rotation.x;
        inner.add(tail);
        tails.push(tail);
    });

    chicken.scale.setScalar(0.98 + Math.random() * 0.04);
    chicken.userData.bobOffset = Math.random() * Math.PI * 2;
    chicken.userData.idlePitch = pose === 1 ? 0.18 : 0;
    chicken.userData.idleYaw = pose === 2 ? 0.5 : 0;
    chicken.userData.rigRefs = { rig, pivot, upper, neck, head, tails, legs };
    return chicken;
}

/** Terapkan pose ke ayam prosedural (setara dengan shader ayam GLB, termasuk ayunan kaki & cakar). */
function applyProcHenPose(chicken, pitch, yaw, squat, tailLift, reach, legPhase = 0, legAmp = 0) {
    const r = chicken.userData.rigRefs;
    if (!r) {
        return;
    }
    r.pivot.rotation.x = pitch * FEED.bodyTilt;
    r.pivot.position.y = PROC_HEN_MODEL.rig.pb[0] - squat;
    r.upper.rotation.x = pitch;
    const hy = 0.18;
    const hz = 0.04 + reach;
    r.head.position.set(0, hy, hz);
    r.head.rotation.y = yaw;
    r.neck.position.set(0, hy / 2, hz / 2);
    r.neck.rotation.x = Math.atan2(hz, hy);
    r.neck.scale.y = Math.hypot(hy, hz) / 0.14;
    for (let t = 0; t < r.tails.length; t += 1) {
        r.tails[t].rotation.x = r.tails[t].userData.baseRotX + tailLift;
    }
    if (r.legs) {
        for (let i = 0; i < r.legs.length; i += 1) {
            const lp = r.legs[i];
            const ph = legPhase + (lp.userData.isLeft ? Math.PI : 0);
            const sw = Math.max(0, Math.cos(ph));
            const lift = sw * sw * (3 - 2 * sw) * legAmp;
            lp.rotation.x = -Math.sin(ph) * 0.52 * legAmp;
            lp.position.y = 0.12 + lift * 0.038;
            if (lp.userData.foot) {
                lp.userData.foot.rotation.x = lift * 0.55;
            }
        }
    }
}

// ---------------------------------------------------------------------
// AYAM GLB: analisis, bake, decimation, material, shader instanced
// ---------------------------------------------------------------------
function henSmooth(x) {
    const v = Math.min(1, Math.max(0, x));
    return v * v * (3 - 2 * v);
}

/** Ukur ujung paruh dan titik dada dari geometri asli (satuan mentah, kaki di y=0), memakai pembobotan yang sama dengan shader. */
function analyzeHenGeometry(geometry, minY) {
    const pos = geometry.attributes.position;
    const edges = [1.0, 1.4, 1.8, 2.2, 2.6, 2.95];
    const chest = edges.slice(1).map(() => null);
    let beak = null;
    for (let i = 0; i < pos.count; i += 1) {
        const x = pos.getX(i);
        const y = pos.getY(i) - minY;
        const z = pos.getZ(i);
        const wy = y <= 0.16 ? 1 : (y >= 0.9 ? 0 : 1 - henSmooth((y - 0.16) / 0.74));
        const wx = y < 0.74 ? 1 : henSmooth((Math.abs(x) - 0.12) / 0.18);
        const wLeg = wy * wx;
        const neckHead = henSmooth((y - 2.45) / 0.35) * henSmooth((z - 0.55) / 0.35);
        const head = henSmooth((y + 0.35 * (z - 1.25) - 3.05) / 0.22);
        if (neckHead > 0.5 && head > 0.5) {
            if (!beak || z > beak[1]) {
                beak = [y, z];
            }
        } else if (neckHead < 0.35 && wLeg < 0.5) {
            for (let b = 0; b < chest.length; b += 1) {
                if (y >= edges[b] && y < edges[b + 1]) {
                    if (!chest[b] || z > chest[b][1]) {
                        chest[b] = [y, z];
                    }
                    break;
                }
            }
        }
    }
    const chestPts = chest.filter(Boolean);
    if (!beak || chestPts.length < 3) {
        throw new Error('geometri ayam tidak cocok dengan rig (paruh/dada tidak ditemukan)');
    }
    return { beak, chestPts };
}

/** Geser kaki ke y=0, skalakan ke meter, dan buang atribut yang tidak dipakai. */
function bakeHenGeometry(source, minY, scale) {
    const src = source.geometry;
    if (!src.index || !src.attributes.position) {
        throw new Error('geometri ayam harus ber-index');
    }
    const pos = src.attributes.position;
    const nor = src.attributes.normal;
    const uv = src.attributes.uv;
    const count = pos.count;
    const p = new Float32Array(count * 3);
    const n = new Float32Array(count * 3);
    const t = new Float32Array(count * 2);
    for (let i = 0; i < count; i += 1) {
        p[i * 3] = pos.getX(i) * scale;
        p[i * 3 + 1] = (pos.getY(i) - minY) * scale;
        p[i * 3 + 2] = pos.getZ(i) * scale;
        if (nor) {
            n[i * 3] = nor.getX(i);
            n[i * 3 + 1] = nor.getY(i);
            n[i * 3 + 2] = nor.getZ(i);
        }
        if (uv) {
            t[i * 2] = uv.getX(i);
            t[i * 2 + 1] = uv.getY(i);
        }
    }
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.BufferAttribute(p, 3));
    geometry.setAttribute('normal', new THREE.BufferAttribute(n, 3));
    if (uv) {
        geometry.setAttribute('uv', new THREE.BufferAttribute(t, 2));
    }
    const idx = src.index.array;
    geometry.setIndex(new THREE.BufferAttribute(count > 65535 ? new Uint32Array(idx) : new Uint16Array(idx), 1));
    if (!nor) {
        geometry.computeVertexNormals();
    }
    return geometry;
}

/** Satu percobaan decimation kedap-celah: posisi 3D disatukan per sel ruang, sedangkan UV & normal dipisah per pulau UV. */
function clusterHenGeometry(geometry, cell) {
    const pos = geometry.attributes.position.array;
    const nor = geometry.attributes.normal.array;
    const uv = geometry.attributes.uv.array;
    const index = geometry.index.array;
    const count = pos.length / 3;
    const uvCell = HEN_GLB.uvCell;
    const spatialMap = new Map();
    const sumSpatialP = [];
    const cntSpatial = [];
    const ids = new Map();
    const remap = new Uint32Array(count);
    const vertSpatial = [];
    const sumN = [];
    const sumT = [];
    const members = [];
    for (let i = 0; i < count; i += 1) {
        const px = pos[i * 3], py = pos[i * 3 + 1], pz = pos[i * 3 + 2];
        // Jaga detail kepala, jengger, paruh, dan kaki 2x lebih rapat daripada badan
        const fine = (pz > 0.11 || py < 0.09) ? 0.5 : 1.0;
        const cs = cell * fine;
        const sKey = fine + ':' + Math.floor(px / cs) + ',' + Math.floor(py / cs) + ',' + Math.floor(pz / cs);
        let sId = spatialMap.get(sKey);
        if (sId === undefined) {
            sId = cntSpatial.length;
            spatialMap.set(sKey, sId);
            sumSpatialP.push(0, 0, 0);
            cntSpatial.push(0);
        }
        sumSpatialP[sId * 3] += px;
        sumSpatialP[sId * 3 + 1] += py;
        sumSpatialP[sId * 3 + 2] += pz;
        cntSpatial[sId] += 1;

        const key = sId + '|' + Math.floor(uv[i * 2] / uvCell) + ',' + Math.floor(uv[i * 2 + 1] / uvCell);
        let id = ids.get(key);
        if (id === undefined) {
            id = members.length;
            ids.set(key, id);
            vertSpatial.push(sId);
            sumN.push(0, 0, 0);
            sumT.push(0, 0);
            members.push(0);
        }
        remap[i] = id;
        members[id] += 1;
        for (let a = 0; a < 3; a += 1) {
            sumN[id * 3 + a] += nor[i * 3 + a];
        }
        sumT[id * 2] += uv[i * 2];
        sumT[id * 2 + 1] += uv[i * 2 + 1];
    }
    const m = members.length;
    const p = new Float32Array(m * 3);
    const n = new Float32Array(m * 3);
    const t = new Float32Array(m * 2);
    for (let id = 0; id < m; id += 1) {
        const k = members[id];
        const sId = vertSpatial[id];
        const sk = cntSpatial[sId];
        p[id * 3] = sumSpatialP[sId * 3] / sk;
        p[id * 3 + 1] = sumSpatialP[sId * 3 + 1] / sk;
        p[id * 3 + 2] = sumSpatialP[sId * 3 + 2] / sk;
        const len = Math.hypot(sumN[id * 3], sumN[id * 3 + 1], sumN[id * 3 + 2]) || 1;
        n[id * 3] = sumN[id * 3] / len;
        n[id * 3 + 1] = sumN[id * 3 + 1] / len;
        n[id * 3 + 2] = sumN[id * 3 + 2] / len;
        t[id * 2] = sumT[id * 2] / k;
        t[id * 2 + 1] = sumT[id * 2 + 1] / k;
    }
    const tris = [];
    const seen = new Set();
    for (let k = 0; k < index.length; k += 3) {
        const a = remap[index[k]];
        const b = remap[index[k + 1]];
        const c = remap[index[k + 2]];
        if (a === b || b === c || a === c) {
            continue;
        }
        const key = [a, b, c].sort((x, y) => x - y).join(',');
        if (seen.has(key)) {
            continue;
        }
        seen.add(key);
        tris.push(a, b, c);
    }
    const out = new THREE.BufferGeometry();
    out.setAttribute('position', new THREE.BufferAttribute(p, 3));
    out.setAttribute('normal', new THREE.BufferAttribute(n, 3));
    out.setAttribute('uv', new THREE.BufferAttribute(t, 2));
    out.setIndex(new THREE.BufferAttribute(m > 65535 ? new Uint32Array(tris) : new Uint16Array(tris), 1));
    return { geometry: out, triangles: tris.length / 3 };
}

/** Decimation ringan: cari ukuran sel terkecil yang masih memenuhi target segitiga. */
function simplifyHenGeometry(geometry, targetTriangles) {
    if (!targetTriangles || geometry.index.count / 3 <= targetTriangles || !geometry.attributes.uv) {
        return geometry;
    }
    geometry.computeBoundingBox();
    const diag = geometry.boundingBox.getSize(new THREE.Vector3()).length();
    let lo = diag / 800;
    let hi = diag / 10;
    let best = null;
    for (let it = 0; it < 10; it += 1) {
        const cell = Math.sqrt(lo * hi);
        const attempt = clusterHenGeometry(geometry, cell);
        if (attempt.triangles > targetTriangles) {
            lo = cell;
        } else {
            if (best) {
                best.geometry.dispose();
            }
            best = attempt;
            hi = cell;
        }
    }
    if (!best) {
        return geometry;
    }
    geometry.dispose();
    return best.geometry;
}

function disposeMaterialAndTextures(material, keepSet) {
    const visit = (value) => {
        if (value && value.isTexture && (!keepSet || !keepSet.has(value))) {
            value.dispose();
        }
    };
    Object.keys(material).forEach((key) => visit(material[key]));
    if (material.uniforms) {
        Object.keys(material.uniforms).forEach((key) => visit(material.uniforms[key].value));
    }
    material.dispose();
}

function disposeGltfScene(gltf, keepSet) {
    if (!gltf || !gltf.scene) {
        return;
    }
    gltf.scene.traverse((o) => {
        if (o.geometry) {
            o.geometry.dispose();
        }
        const mats = Array.isArray(o.material) ? o.material : (o.material ? [o.material] : []);
        mats.forEach((m) => disposeMaterialAndTextures(m, keepSet));
    });
}

/** Satu MeshStandardMaterial bersama; ekstrak diffuseMap & specularGlossinessMap seperti di login.js. */
function createHenMaterial(source, maxAnisotropy) {
    const old = Array.isArray(source.material) ? source.material[0] : source.material;
    const diffuseMap = (old && (old.map || old.diffuseMap || (old.uniforms && old.uniforms.diffuseMap && old.uniforms.diffuseMap.value))) || null;
    const specMap = (old && (old.specularMap || old.glossinessMap || (old.uniforms && old.uniforms.specularGlossinessMap && old.uniforms.specularGlossinessMap.value))) || null;
    if (diffuseMap) {
        diffuseMap.encoding = THREE.LinearEncoding;
        diffuseMap.anisotropy = maxAnisotropy;
        diffuseMap.needsUpdate = true;
    }
    if (specMap) {
        specMap.anisotropy = maxAnisotropy;
    }
    const material = new THREE.MeshStandardMaterial({
        map: diffuseMap,
        bumpMap: specMap || diffuseMap,
        bumpScale: 0.008,
        color: diffuseMap ? 0xffffff : 0x8a5a3a,
        roughness: 0.84,
        metalness: 0.02,
        side: THREE.FrontSide
    });
    const textures = new Set([diffuseMap, specMap].filter(Boolean));
    return { material, textures };
}

/** GLSL deformasi per-instance: leher + julur, yaw kepala, jongkok, ekor, badan miring, & artikulasi langkah kaki kiri-kanan (aReach.y). */
function buildHenShaderDecl(scale) {
    const R = HEN_GLB.rig;
    const f = (v) => v.toFixed(6);
    return `
attribute vec4 aPose;
attribute vec2 aReach;
uniform float uTilt;
#define RK_INV_S ${f(1 / scale)}
#define RK_PN_Y ${f(R.pnY)}
#define RK_PN_Z ${f(R.pnZ)}
#define RK_PB_Y ${f(R.pbY)}
#define RK_PB_Z ${f(R.pbZ)}
#define RK_PT_Y ${f(R.ptY)}
#define RK_PT_Z ${f(R.ptZ)}
#define RK_PH_Y ${f(R.phY)}
#define RK_PH_Z ${f(R.phZ)}
float rkSmooth(float x) { x = clamp(x, 0.0, 1.0); return x * x * (3.0 - 2.0 * x); }
vec3 rkRotX(vec3 v, float a) { float c = cos(a); float s = sin(a); return vec3(v.x, v.y * c - v.z * s, v.y * s + v.z * c); }
vec3 rkRotY(vec3 v, float a) { float c = cos(a); float s = sin(a); return vec3(v.x * c + v.z * s, v.y, -v.x * s + v.z * c); }
vec3 rkDeform(vec3 v, float asPoint) {
    vec3 pr = position * RK_INV_S;
    float wy = pr.y <= 0.16 ? 1.0 : (pr.y >= 0.90 ? 0.0 : 1.0 - rkSmooth((pr.y - 0.16) / 0.74));
    float wx = pr.y < 0.74 ? 1.0 : rkSmooth((abs(pr.x) - 0.12) / 0.18);
    float wLeg = wy * wx;
    float wUp = 1.0 - wLeg;
    float nh = rkSmooth((pr.y - 2.45) / 0.35) * rkSmooth((pr.z - 0.55) / 0.35);
    float hd = rkSmooth((pr.y + 0.35 * (pr.z - 1.25) - 3.05) / 0.22);
    float tl = rkSmooth((-0.75 - pr.z) / 0.45) * rkSmooth((pr.y - 1.80) / 0.35);
    float wNH = wUp * nh;
    float wHead = wNH * hd;
    float wTail = wUp * tl;
    float pitch = aPose.x;
    float yaw = aPose.y;
    float squat = aPose.z;
    float tailUp = aPose.w;
    float k = mix(1.0, RK_INV_S, asPoint);
    vec3 q = v * k;
    vec3 pH = vec3(0.0, RK_PH_Y, RK_PH_Z) * asPoint;
    q = mix(q, rkRotY(q - pH, yaw) + pH, wHead);
    vec3 pN = vec3(0.0, RK_PN_Y, RK_PN_Z) * asPoint;
    vec3 qn = rkRotX(q - pN, pitch) + pN + vec3(0.0, -sin(pitch), cos(pitch)) * (aReach.x * RK_INV_S * asPoint);
    q = mix(q, qn, wNH);
    vec3 pT = vec3(0.0, RK_PT_Y, RK_PT_Z) * asPoint;
    q = mix(q, rkRotX(q - pT, tailUp) + pT, wTail);
    vec3 pB = vec3(0.0, RK_PB_Y, RK_PB_Z) * asPoint;
    q = mix(q, rkRotX(q - pB, pitch * uTilt) + pB, wUp);
    q.y -= squat * RK_INV_S * asPoint * wUp;
    float legAmp = floor(aReach.y) * 0.001;
    if (legAmp > 0.0005) {
        float legPhi = (fract(aReach.y) / 0.998) * 6.2831853;
        float wyS = pr.y <= 0.46 ? 1.0 : (pr.y >= 0.88 ? 0.0 : 1.0 - rkSmooth((pr.y - 0.46) / 0.42));
        float wxS = pr.y < 0.70 ? 1.0 : rkSmooth((abs(pr.x) - 0.12) / 0.18);
        float wStride = wyS * wxS;
        float isLeft = pr.x < -0.035 ? 1.0 : 0.0;
        float ph = legPhi + isLeft * 3.14159265;
        float sw = max(0.0, cos(ph));
        float lift = sw * sw * (3.0 - 2.0 * sw) * legAmp;
        float hFrac = clamp((0.86 - pr.y) / 0.74, 0.0, 1.0);
        float dzBase = (mix(0.36, -0.36, isLeft) + 0.48 * sin(ph)) * legAmp;
        float hock = -0.12 * lift * sin(hFrac * 3.14159265);
        float wToe = 1.0 - rkSmooth((pr.y - 0.04) / 0.20);
        float ankX = mix(0.37, -0.39, isLeft);
        float ankZ = mix(-0.49, 0.45, isLeft);
        float dxLeg = -(pr.x - ankX) * (0.22 * lift * wToe);
        float dyLeg = lift * 0.54 * hFrac - min(0.24, abs(pr.z - ankZ) * 0.35) * lift * wToe;
        float dzLeg = dzBase * hFrac + hock - (pr.z - ankZ) * (0.34 * lift * wToe);
        q += vec3(dxLeg, dyLeg, dzLeg) * (wStride * asPoint);
        q = mix(q, rkRotX(q, (dzBase / 0.74) * 0.65), wStride * (1.0 - asPoint));
    }
    return q / k;
}
`;
}

function attachHenShader(material, scale) {
    const decl = buildHenShaderDecl(scale);
    material.onBeforeCompile = (shader) => {
        shader.uniforms.uTilt = { value: FEED.bodyTilt };
        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', '#include <common>\n' + decl)
            .replace('#include <beginnormal_vertex>', '#include <beginnormal_vertex>\n    objectNormal = normalize(rkDeform(objectNormal, 0.0));')
            .replace('#include <begin_vertex>', '#include <begin_vertex>\n    transformed = rkDeform(transformed, 1.0);');
        material.userData.shader = shader;
    };
    material.customProgramCacheKey = () => 'rk-hen-instanced-v2';
}

// ---------------------------------------------------------------------
// Pemuatan GLB progresif + HUD + fallback
// ---------------------------------------------------------------------
let henLoadToken = 0;
let henHudTimer = 0;

function setHenHud(text, busy) {
    const box = document.getElementById('hud-hen-model');
    const label = document.getElementById('hud-hen-model-text');
    const icon = document.getElementById('hud-hen-model-icon');
    window.clearTimeout(henHudTimer);
    if (!box) {
        return;
    }
    if (!text) {
        box.style.display = 'none';
        return;
    }
    box.style.display = 'inline-flex';
    if (label) {
        label.textContent = text;
    }
    if (icon) {
        icon.textContent = busy ? 'progress_activity' : 'info';
        icon.className = busy ? 'material-symbols-outlined text-[13px] text-amber-300 animate-spin' : 'material-symbols-outlined text-[13px] text-amber-300';
    }
}

function failHenModel(reason) {
    console.warn('[Digital Twin] Model ayam GLB tidak dipakai, memakai ayam prosedural cokelat:', reason);
    setHenHud('Model ayam 3D gagal dimuat, memakai ayam sederhana', false);
    henHudTimer = window.setTimeout(() => setHenHud(''), 6000);
}

function loadHenModel() {
    const token = (henLoadToken += 1);
    if (typeof THREE.GLTFLoader === 'undefined') {
        failHenModel('GLTFLoader tidak tersedia');
        return;
    }
    setHenHud('Memuat model ayam 3D...', true);
    let settled = false;
    const timer = window.setTimeout(() => {
        if (!settled) {
            settled = true;
            failHenModel('timeout ' + HEN_GLB.timeoutMs + ' ms');
        }
    }, HEN_GLB.timeoutMs);

    new THREE.GLTFLoader().load(
        HEN_GLB.url,
        (gltf) => {
            if (settled || isDisposed3D || token !== henLoadToken) {
                disposeGltfScene(gltf, null);
                return;
            }
            settled = true;
            window.clearTimeout(timer);
            try {
                applyHenGlb(gltf);
                setHenHud('');
            } catch (err) {
                disposeGltfScene(gltf, null);
                failHenModel(err && err.message ? err.message : err);
            }
        },
        (ev) => {
            if (!settled && ev && ev.lengthComputable && ev.total > 0) {
                setHenHud('Memuat model ayam 3D... ' + Math.round((ev.loaded / ev.total) * 100) + '%', true);
            }
        },
        (err) => {
            if (settled) {
                return;
            }
            settled = true;
            window.clearTimeout(timer);
            failHenModel(err && err.message ? err.message : 'gagal mengunduh');
        }
    );
}

/** Ganti ayam prosedural dengan satu InstancedMesh berisi ayam GLB. */
function applyHenGlb(gltf) {
    let source = null;
    gltf.scene.traverse((o) => {
        if (o.isMesh && !source) {
            source = o;
        }
    });
    if (!source) {
        throw new Error('mesh ayam tidak ditemukan di chicken.glb');
    }
    const raw = source.geometry;
    raw.computeBoundingBox();
    const minY = raw.boundingBox.min.y;
    const rawHeight = raw.boundingBox.max.y - minY;
    if (Math.abs(rawHeight - HEN_GLB.expectedRawHeight) > 0.1) {
        throw new Error('tinggi model ' + rawHeight.toFixed(3) + ' berbeda dari yang dikalibrasi (' + HEN_GLB.expectedRawHeight + '), rig perlu disetel ulang');
    }
    const scale = HEN_GLB.heightM / rawHeight;
    const analysis = analyzeHenGeometry(raw, minY);

    let geometry = bakeHenGeometry(source, minY, scale);
    const trisBefore = geometry.index.count / 3;
    geometry = simplifyHenGeometry(geometry, HEN_GLB.targetTriangles);
    const trisAfter = geometry.index.count / 3;

    const count = chickensList.length;
    const bgCount = neighborHenSlots.length;
    const totalCount = count + bgCount;
    const pose = new Float32Array(totalCount * 4);
    const reach = new Float32Array(totalCount * 2);
    const poseAttr = new THREE.InstancedBufferAttribute(pose, 4);
    const reachAttr = new THREE.InstancedBufferAttribute(reach, 2);
    poseAttr.setUsage(THREE.DynamicDrawUsage);
    reachAttr.setUsage(THREE.DynamicDrawUsage);
    geometry.setAttribute('aPose', poseAttr);
    geometry.setAttribute('aReach', reachAttr);

    const maxAniso = Math.min(HEN_GLB.anisotropyMax, renderer3D && renderer3D.capabilities ? renderer3D.capabilities.getMaxAnisotropy() : 4);
    const built = createHenMaterial(source, maxAniso);
    attachHenShader(built.material, scale);

    const mesh = new THREE.InstancedMesh(geometry, built.material, totalCount);
    mesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
    mesh.frustumCulled = false;
    mesh.castShadow = false;
    mesh.receiveShadow = false;

    const R = HEN_GLB.rig;
    henModel = {
        kind: 'glb',
        get pitchMax() { return FEED.peckPitchMax; },
        baseBeakFwd: analysis.beak[1] * scale,
        rig: { pn: [R.pnY * scale, R.pnZ * scale], pb: [R.pbY * scale, R.pbZ * scale] },
        beakRel: [(analysis.beak[0] - R.pnY) * scale, (analysis.beak[1] - R.pnZ) * scale],
        chestPts: analysis.chestPts.map((p) => [p[0] * scale, p[1] * scale]),
        vent: HEN_GLB.ventLocal,
        squat: HEN_LAY.squatM
    };

    chickensList.forEach((chicken, i) => {
        const u = chicken.userData;
        if (u.rigRefs) {
            chicken.remove(u.rigRefs.rig);
            u.rigRefs = null;
        }
        u.instIndex = i;
    });
    disposeProcHenAssets();

    henInst = { mesh, pose, reach, poseAttr, reachAttr, material: built.material, textures: built.textures };
    scene3D.add(mesh);
    disposeGltfScene(gltf, built.textures);
    calibrateAllChickens();
    chickensList.forEach((chicken) => {
        chicken.updateMatrixWorld(true);
        mesh.setMatrixAt(chicken.userData.instIndex, chicken.matrixWorld);
    });
    neighborHenSlots.forEach((slot, j) => {
        const idx = count + j;
        mesh.setMatrixAt(idx, slot.matrix);
        const o = idx * 4;
        pose[o] = slot.pitchBase;
        pose[o + 1] = Math.sin(slot.phase) * 0.34;
        pose[o + 2] = 0;
        pose[o + 3] = 0.04 * Math.cos(slot.phase * 0.7);
        reach[idx * 2] = slot.reachBase;
        reach[idx * 2 + 1] = 0;
    });
    mesh.instanceMatrix.needsUpdate = true;
    poseAttr.needsUpdate = true;
    reachAttr.needsUpdate = true;
    console.info('[Digital Twin] Ayam GLB aktif: ' + count + ' utama + ' + bgCount + ' kandang tetangga (' + totalCount + ' instance, 1 draw call), ' + Math.round(trisBefore) + ' -> ' + Math.round(trisAfter) + ' segitiga/ayam.');
}

function disposeHenInstances() {
    if (!henInst) {
        return;
    }
    if (henInst.mesh.parent) {
        henInst.mesh.parent.remove(henInst.mesh);
    }
    henInst.mesh.geometry.dispose();
    henInst.material.dispose();
    if (henInst.textures) {
        henInst.textures.forEach((tex) => tex.dispose());
    }
    henInst.mesh.dispose();
    henInst = null;
}

// ---------------------------------------------------------------------
// Fungsi tinggi lantai miring sangkar (±8.5°) & permukaan sabuk telur
// ---------------------------------------------------------------------
function cageBeltTopY(floorY) {
    return floorY + EGG_PATH.beltOffsetY;
}

function cageFloorHeightAtAbsZ(floorY, absZ) {
    const slopeTan = Math.tan((EGG_PATH.floorSlopeDeg * Math.PI) / 180);
    const dZ = Math.max(0, absZ - EGG_PATH.beltZ);
    return cageBeltTopY(floorY) + dZ * slopeTan;
}

// ---------------------------------------------------------------------
// Kalibrasi pose makan tiap ayam terhadap talang kuning di scene
// ---------------------------------------------------------------------
function findFeedTrough(u) {
    for (let i = 0; i < feedTroughs.length; i += 1) {
        const t = feedTroughs[i];
        if (t.side === u.side && t.tierIdx === u.tierIdx && Math.abs(u.holeX - t.x) <= t.halfLenX - 0.02) {
            return t;
        }
    }
    return null;
}

function calibrateAllChickens() {
    const stats = { total: 0, adjusted: 0, failed: 0, noTrough: 0, worst: 0, minGap: FEED.peckGap, minFrontZ: FEED.cageFrontZ };
    chickensList.forEach((chicken) => {
        const u = chicken.userData;
        u.trough = findFeedTrough(u);
        u.feedPose = null;
        if (!u.trough) {
            stats.noTrough += 1;
            return;
        }
        // Hitung tinggi pijakan kaki di depan sangkar secara konsisten terhadap lantai miring 8.5°
        let frontY = cageFloorHeightAtAbsZ(u.floorY, FEED.cageFrontZ + 0.23);
        let res = solveFeedPose(henModel, u.sc, frontY, u.trough);
        frontY = cageFloorHeightAtAbsZ(u.floorY, res.frontAbsZ);
        res = solveFeedPose(henModel, u.sc, frontY, u.trough);
        res.frontY = frontY;

        stats.total += 1;
        stats.worst = Math.max(stats.worst, res.err);
        if (res.adjusted) {
            stats.adjusted += 1;
            stats.minGap = Math.min(stats.minGap, res.gap);
            stats.minFrontZ = Math.min(stats.minFrontZ, res.usedFrontZ);
        }
        if (res.ok) {
            u.feedPose = res;
        } else {
            stats.failed += 1;
        }
    });
    if (stats.noTrough) {
        console.warn('[Digital Twin] ' + stats.noTrough + ' ayam tidak punya talang di depannya; ayam itu tidak akan makan.');
    }
    if (stats.adjusted) {
        console.warn('[Digital Twin] ' + stats.adjusted + ' ayam membutuhkan penyesuaian otomatis agar paruh menyentuh talang kuning (peckGap -> ' + stats.minGap.toFixed(3) + ' m, cageFrontZ -> ' + stats.minFrontZ.toFixed(3) + ' m).');
    }
    if (stats.failed) {
        console.warn('[Digital Twin] ' + stats.failed + ' ayam tidak dapat menjangkau talang kuning (galat terburuk ' + (stats.worst * 1000).toFixed(1) + ' mm > maxNeck); patokan ditahan agar tidak mematok di udara.');
    }
    return stats;
}

window.kandangFeed = {
    config: FEED,
    holeConfig: FEED_HOLE,
    eggPathConfig: EGG_PATH,
    eggCollector: EGG_COLLECTOR,
    idleConfig: HEN_IDLE,
    recalibrate: () => calibrateAllChickens()
};

/** Diagnostik cepat di konsol browser untuk memeriksa Masalah 1, 2, 3, dan variasi gerak alami ayam. */
window.kandangVerify3D = function() {
    let maxBeakErrMm = 0, maxReachM = 0, maxHoleDxMm = 0, maxFeedYawRad = 0, feedingCount = 0;
    let steppingLegCount = 0, maxLegAmp = 0;
    const idleSubCounts = {};
    chickensList.forEach((c) => {
        const u = c.userData;
        if (u.state === 'IDLE' && u.idleSub) {
            idleSubCounts[u.idleSub] = (idleSubCounts[u.idleSub] || 0) + 1;
        }
        const curLegAmp = u.legAmpEff || 0;
        if (curLegAmp > 0.05) {
            steppingLegCount += 1;
        }
        maxLegAmp = Math.max(maxLegAmp, curLegAmp);
        if (u.feedPose) {
            maxBeakErrMm = Math.max(maxBeakErrMm, u.feedPose.err * 1000);
            maxReachM = Math.max(maxReachM, u.feedPose.reach * u.sc);
        }
        if (u.state === 'MAKAN' && u.feedBlend > 0.98) {
            feedingCount += 1;
            maxHoleDxMm = Math.max(maxHoleDxMm, Math.abs(c.position.x - u.holeX) * 1000);
            const yawEff = (henInst && u.instIndex !== undefined)
                ? Math.abs(henInst.pose[u.instIndex * 4 + 1])
                : Math.abs(u.yawNow * (1 - smooth01(Math.min(1, u.feedBlend * 1.65))));
            maxFeedYawRad = Math.max(maxFeedYawRad, yawEff);
        }
    });
    let minBeltGapM = Infinity;
    eggLanes.forEach((l) => {
        const sorted = l.eggs.slice().sort((a, b) => b.x - a.x);
        for (let i = 1; i < sorted.length; i += 1) {
            minBeltGapM = Math.min(minBeltGapM, sorted[i - 1].x - sorted[i].x);
        }
    });
    return {
        henModel: henModel.kind,
        totalHens: chickensList.length,
        feedingCount,
        steppingLegCount,
        maxLegAmp: +maxLegAmp.toFixed(3),
        idleSubCounts,
        maxBeakErrMm: +maxBeakErrMm.toFixed(3),
        maxReachM: +maxReachM.toFixed(4),
        maxHoleDxMm: +maxHoleDxMm.toFixed(3),
        maxFeedYawRad: +maxFeedYawRad.toFixed(4),
        holeSpec: {
            widthM: FEED_HOLE.width,
            archWidthM: FEED_HOLE.archWidth,
            heightM: FEED_HOLE.height,
            bottomOffsetM: FEED_HOLE.bottomOffset,
            totalHoles: chickensList.length
        },
        eggCollectorStations: EGG_COLLECTOR.stations.map((st) => ({
            side: st.side, tier: st.tierIdx, x: st.x, y: +st.padY.toFixed(3), z: st.z
        })),
        activeEggs: activeEggs.length,
        poolSize: eggPool.length,
        minBeltGapM: minBeltGapM === Infinity ? null : +minBeltGapM.toFixed(4),
        collectedTotal: eggStats.total,
        hudEggText: (document.getElementById('text-egg-3d') || {}).textContent
    };
};

// =========================================================================
// KONFIGURASI ANIMASI & PIN (terhubung ke FEED, FEED_HOLE, EGG_PATH, EGG_COLLECTOR)
// =========================================================================
const KANDANG_ANIM_CONFIG = {
    makan: {
        pEatFeeder: 0.96,        // peluang IDLE -> MAKAN saat kereta pakan dalam jangkauan X
        feederReach: 1.85,       // jarak X (m) kereta pakan yang memicu ayam makan
        eatMinSec: 6, eatMaxSec: 14,
        idleMinSec: 2.5, idleMaxSec: 7,
        peckMin: 4, peckMax: 8,  // jumlah matokan beruntun per siklus (frekuensi FEED.peckHz)
        lookPauseMin: 0.25, lookPauseMax: 0.55 // jeda singkat kepala tetap lurus (headYaw=0) di lubang makan
    },
    performa: {
        stateUpdateGroups: 6     // keputusan transisi state dibagi ke 6 kelompok frame
    },
    pin: {
        labelOffsetPx: 46,
        gapPx: 6,
        detailRefreshMs: 500
    }
};

const EGG_LANE_X_START = EGG_PATH.beltStartX;
const EGG_LANE_X_END = EGG_PATH.beltEndX;
let eggLanes = [];
let eggPool = [];
let activeEggs = [];
let eggStats = { total: 0, perLane: {} };
let nextLayAt = 0;
let decisionCursor = 0;
let lastFrameTimeMs = 0;
let fanGroups = [];
let iotPinDefs = [];
let iotPins = [];
let iotPinLayer = null;
let iotPinSvg = null;
let iotPinPanel = null;
let selectedPin = null;
let lastPinDetailRefresh = 0;

const EGG_BASE_SIZE = { x: 0.05, y: 0.04, z: 0.04 };
const _eggTmpVec = new THREE.Vector3();
const _eggTmpQuadA = new THREE.Vector3();
const _eggTmpQuadB = new THREE.Vector3();
let eggAssets = null;

function getEggAssets() {
    if (eggAssets) {
        return eggAssets;
    }
    eggAssets = {
        geo: new THREE.SphereGeometry(0.5, 14, 10),
        mat: new THREE.MeshStandardMaterial({ color: 0xc28254, roughness: 0.55, metalness: 0.02 }),
        beltMat: new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.62, metalness: 0.08 }),
        beltEdgeMat: new THREE.MeshStandardMaterial({ color: 0xcbd5e1, metalness: 0.25, roughness: 0.38 }),
        trayMat: new THREE.MeshStandardMaterial({ color: 0x334155, metalness: 0.20, roughness: 0.48 }),
        rimMat: new THREE.MeshStandardMaterial({ color: 0xfbbf24, metalness: 0.15, roughness: 0.35 }),
        padMat: new THREE.MeshStandardMaterial({ color: 0x10b981, roughness: 0.75, metalness: 0.04 })
    };
    return eggAssets;
}

function disposeEggAssets() {
    if (!eggAssets) {
        return;
    }
    Object.keys(eggAssets).forEach((k) => {
        if (eggAssets[k] && typeof eggAssets[k].dispose === 'function') {
            eggAssets[k].dispose();
        }
    });
    eggAssets = null;
}

function randomBetween(min, max) {
    return min + Math.random() * (max - min);
}

function eggRestRadiusY() {
    return 0.5 * EGG_BASE_SIZE.y * EGG_PATH.eggScale;
}

function eggRollRadius() {
    return 0.25 * (EGG_BASE_SIZE.x + EGG_BASE_SIZE.y) * EGG_PATH.eggScale;
}

// ---------------------------------------------------------------------
// SABUK TELUR KONSTAN & BAK PENGUMPUL (EGG_COLLECTOR) DI UJUNG +X BARIS
// ---------------------------------------------------------------------
function eggLaneHeightAt(lane) {
    return lane.y;
}

function createCollectorBadgeSprite(text) {
    const canvas = document.createElement('canvas');
    canvas.width = 320;
    canvas.height = 72;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = 'rgba(15, 23, 42, 0.92)';
    ctx.strokeStyle = '#f59e0b';
    ctx.lineWidth = 4;
    const r = 16;
    ctx.beginPath();
    ctx.moveTo(r, 4);
    ctx.lineTo(316 - r, 4);
    ctx.quadraticCurveTo(316, 4, 316, 4 + r);
    ctx.lineTo(316, 68 - r);
    ctx.quadraticCurveTo(316, 68, 316 - r, 68);
    ctx.lineTo(r, 68);
    ctx.quadraticCurveTo(4, 68, 4, 68 - r);
    ctx.lineTo(4, 4 + r);
    ctx.quadraticCurveTo(4, 4, r, 4);
    ctx.closePath();
    ctx.fill();
    ctx.stroke();

    ctx.fillStyle = '#10b981';
    ctx.beginPath();
    ctx.arc(28, 36, 9, 0, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = '#fef3c7';
    ctx.font = 'bold 21px system-ui, -apple-system, sans-serif';
    ctx.textBaseline = 'middle';
    ctx.fillText(text, 48, 37);

    const tex = new THREE.CanvasTexture(canvas);
    tex.needsUpdate = true;
    const mat = new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: true, depthWrite: false });
    const sprite = new THREE.Sprite(mat);
    sprite.scale.set(0.95, 0.215, 1);
    return sprite;
}

/** Bangun sabuk telur konstan & bak pengumpul terlihat di koordinat ujung sabuk (EGG_COLLECTOR). */
function createEggLaneAndCollector(side, tierIdx, floorY) {
    const EA = getEggAssets();
    const beltY = cageBeltTopY(floorY);
    const beltZ = side * EGG_PATH.beltZ;
    const length = EGG_PATH.beltEndX - EGG_PATH.beltStartX;
    const centerX = 0.5 * (EGG_PATH.beltStartX + EGG_PATH.beltEndX);

    const belt = new THREE.Mesh(new THREE.BoxGeometry(length, 0.014, 0.12), EA.beltMat);
    belt.position.set(centerX, beltY - 0.007, beltZ);
    belt.receiveShadow = true;
    scene3D.add(belt);

    // Rel pemandu tipis di kedua tepi sabuk telur agar jalur tampak tegas
    [-0.062, 0.062].forEach((dz) => {
        const rail = new THREE.Mesh(new THREE.BoxGeometry(length, 0.018, 0.006), EA.beltEdgeMat);
        rail.position.set(centerX, beltY + 0.004, beltZ + dz);
        scene3D.add(rail);
    });

    // Bak pengumpul telur (EGG_COLLECTOR) di ujung +X sabuk telur
    const { w, h, d } = EGG_COLLECTOR.basketSize;
    const colX = EGG_COLLECTOR.x;
    const colZ = beltZ;
    const padY = beltY - EGG_COLLECTOR.dropDepth;
    const group = new THREE.Group();
    group.position.set(colX, padY, colZ);

    // Dasar bak & bantalan hijau peredam benturan telur
    const base = new THREE.Mesh(new THREE.BoxGeometry(w, 0.016, d), EA.trayMat);
    base.position.y = -0.008;
    group.add(base);

    const pad = new THREE.Mesh(new THREE.BoxGeometry(w - 0.03, 0.010, d - 0.03), EA.padMat);
    pad.position.y = 0.003;
    group.add(pad);

    // Dinding keliling bak (sisi -X menghadap sabuk dibuat lebih rendah sebagai bibir masuk)
    const wallThick = 0.014;
    const backWall = new THREE.Mesh(new THREE.BoxGeometry(wallThick, h, d), EA.trayMat);
    backWall.position.set(w / 2 - wallThick / 2, h / 2 - 0.008, 0);
    group.add(backWall);

    const frontLipH = Math.max(0.032, EGG_COLLECTOR.dropDepth - 0.018);
    const frontWall = new THREE.Mesh(new THREE.BoxGeometry(wallThick, frontLipH, d), EA.trayMat);
    frontWall.position.set(-w / 2 + wallThick / 2, frontLipH / 2 - 0.008, 0);
    group.add(frontWall);

    [-1, 1].forEach((sz) => {
        const sideWall = new THREE.Mesh(new THREE.BoxGeometry(w, h, wallThick), EA.trayMat);
        sideWall.position.set(0, h / 2 - 0.008, sz * (d / 2 - wallThick / 2));
        group.add(sideWall);

        const sideRim = new THREE.Mesh(new THREE.BoxGeometry(w + 0.01, 0.014, 0.018), EA.rimMat);
        sideRim.position.set(0, h - 0.004, sz * (d / 2 - wallThick / 2));
        group.add(sideRim);
    });

    const endRim = new THREE.Mesh(new THREE.BoxGeometry(0.018, 0.016, d + 0.01), EA.rimMat);
    endRim.position.set(w / 2 - wallThick / 2, h - 0.004, 0);
    group.add(endRim);

    // Peluncur miring (chute) penghubung ujung sabuk X=8.85 m ke bibir dalam bak
    const chuteDx = (colX - w / 2 + 0.03) - EGG_PATH.beltEndX;
    const chuteDy = EGG_COLLECTOR.dropDepth - 0.018;
    const chuteLen = Math.hypot(chuteDx, chuteDy);
    const chute = new THREE.Mesh(new THREE.BoxGeometry(chuteLen, 0.010, 0.13), EA.beltEdgeMat);
    chute.position.set(-w / 2 - chuteDx / 2 + 0.02, EGG_COLLECTOR.dropDepth - chuteDy / 2 - 0.004, 0);
    chute.rotation.z = -Math.atan2(chuteDy, chuteDx);
    group.add(chute);

    // Indikator lampu LED hijau yang menyala terang sesaat tiap telur masuk bak
    const beaconMat = new THREE.MeshStandardMaterial({
        color: 0x10b981,
        emissive: 0x059669,
        emissiveIntensity: 0.75,
        roughness: 0.25
    });
    const beacon = new THREE.Mesh(new THREE.SphereGeometry(0.022, 10, 8), beaconMat);
    beacon.position.set(w / 2 - 0.01, h + 0.022, 0);
    group.add(beacon);

    scene3D.add(group);

    // Tiang penyangga stasiun bak pengumpul & label penanda di atas tingkat 2 (1x per baris)
    if (tierIdx === 1) {
        const postH = padY + h + 0.08;
        [-1, 1].forEach((sz) => {
            const post = new THREE.Mesh(new THREE.CylinderGeometry(0.016, 0.016, postH, 8), EA.beltEdgeMat);
            post.position.set(colX + w / 2 - 0.02, postH / 2, colZ + sz * (d / 2 - 0.02));
            scene3D.add(post);
        });
        const badge = createCollectorBadgeSprite(EGG_COLLECTOR.labelText);
        badge.position.set(colX, padY + h + 0.42, colZ);
        scene3D.add(badge);
    }

    const collector = {
        side,
        tierIdx,
        x: colX,
        y: padY,
        z: colZ,
        padY: padY + 0.008,
        beltEndY: beltY,
        flash: 0,
        beaconMat,
        group
    };
    EGG_COLLECTOR.stations.push(collector);

    const lane = { side, tierIdx, z: beltZ, y: beltY, eggs: [], collector };
    eggLanes.push(lane);
    eggStats.perLane[`${side}_${tierIdx}`] = 0;
    return lane;
}

/** Hitung sekali daftar waypoint polyline jalur telur untuk satu slot ayam pada modul sangkar. */
function buildSlotEggWaypoints(side, floorY, slotX, lane) {
    const rY = eggRestRadiusY();
    const rearAbsZ = 2.68;
    const lipAbsZ = EGG_PATH.beltZ + 0.045;
    const col = lane.collector;
    const W0 = new THREE.Vector3(slotX, cageFloorHeightAtAbsZ(floorY, rearAbsZ) + rY, side * rearAbsZ);
    const W1 = new THREE.Vector3(slotX, cageFloorHeightAtAbsZ(floorY, lipAbsZ) + rY, side * lipAbsZ);
    const W2 = new THREE.Vector3(slotX, lane.y + rY, lane.z);
    const W3 = new THREE.Vector3(EGG_PATH.beltEndX, lane.y + rY, lane.z);
    const W4 = new THREE.Vector3(0.5 * (EGG_PATH.beltEndX + col.x), lane.y + rY - 0.014, col.z);
    const W5 = new THREE.Vector3(col.x, col.padY + rY, col.z);
    const slopeLen1 = W0.distanceTo(W1);
    const slopeLen2 = W1.distanceTo(W2);
    return {
        points: [W0, W1, W2, W3, W4, W5],
        rearAbsZ,
        lipAbsZ,
        slopeLen1,
        slopeLen2,
        slopeTotal: slopeLen1 + slopeLen2,
        beltLen: Math.max(0.001, W3.x - W2.x),
        collector: col
    };
}

function findEggLane(chicken) {
    const u = chicken.userData;
    if (!u.lane) {
        u.lane = eggLanes.find((l) => l.side === u.side && l.tierIdx === u.tierIdx) || null;
    }
    return u.lane;
}

function releaseEggFromLane(egg) {
    if (egg && egg.lane) {
        const idx = egg.lane.eggs.indexOf(egg);
        if (idx >= 0) {
            egg.lane.eggs.splice(idx, 1);
        }
    }
}

function recycleEggToPool(egg) {
    if (!egg) {
        return;
    }
    releaseEggFromLane(egg);
    egg.active = false;
    if (egg.mesh) {
        egg.mesh.visible = false;
    }
    const idx = activeEggs.indexOf(egg);
    if (idx >= 0) {
        activeEggs.splice(idx, 1);
    }
}

function acquireEgg() {
    let egg = eggPool.find((e) => !e.active);
    if (!egg && eggPool.length < EGG_PATH.maxActiveEggs) {
        const EA = getEggAssets();
        const mesh = new THREE.Mesh(EA.geo, EA.mat);
        mesh.castShadow = false;
        mesh.visible = false;
        scene3D.add(mesh);
        egg = { mesh, active: false, lane: null, counted: false };
        eggPool.push(egg);
    }
    if (!egg && activeEggs.length > 0) {
        // Pool penuh (> maxActiveEggs): recycle telur tertua secara bersih
        egg = activeEggs[0];
        recycleEggToPool(egg);
    }
    return egg;
}

/** Catat telur yang selesai masuk ke EGG_COLLECTOR (sekali per telur) lalu kembalikan ke pool. */
function completeEggCollection(egg) {
    if (!egg.counted && egg.lane) {
        egg.counted = true;
        const laneKey = `${egg.lane.side}_${egg.lane.tierIdx}`;
        eggStats.total += 1;
        eggStats.perLane[laneKey] = (eggStats.perLane[laneKey] || 0) + 1;
        window.kandangEggStats = eggStats;
        const hud = document.getElementById('text-egg-3d');
        if (hud) {
            hud.textContent = String(eggStats.total);
        }
        if (egg.lane.collector) {
            egg.lane.collector.flash = 1.0;
        }
        window.dispatchEvent(new CustomEvent('kandang3d:telur', {
            detail: {
                total: eggStats.total,
                perLane: eggStats.perLane,
                collector: egg.lane.collector ? {
                    x: egg.lane.collector.x,
                    y: egg.lane.collector.padY,
                    z: egg.lane.collector.z
                } : null
            }
        }));
    }
    recycleEggToPool(egg);
}

/** Fase 1 (LAY): munculkan telur tepat dari bawah ekor ayam mengikuti posisi (X, Y, Z) badan ayam sebenarnya. */
function spawnEgg(chicken) {
    const u = chicken.userData;
    const lane = findEggLane(chicken);
    if (!lane || !u.eggPath) {
        return null;
    }
    const egg = acquireEgg();
    if (!egg) {
        return null;
    }

    chicken.updateWorldMatrix(true, false);
    const vent = henModel.vent;
    const spawnPos = chicken.localToWorld(new THREE.Vector3(vent[0], vent[1], vent[2]));
    const path = u.eggPath;
    const rY = eggRestRadiusY();
    const clampedAbsZ = Math.min(path.rearAbsZ, Math.max(path.lipAbsZ + 0.04, Math.abs(spawnPos.z)));
    const landY = cageFloorHeightAtAbsZ(u.floorY, clampedAbsZ) + rY;
    const uSlope0 = (path.rearAbsZ - clampedAbsZ) / Math.max(0.001, path.rearAbsZ - path.lipAbsZ);

    egg.active = true;
    egg.counted = false;
    egg.lane = lane;
    egg.path = path;
    egg.side = u.side;
    egg.state = 'LAY';
    egg.t = 0;
    egg.vy = 0;
    egg.speed = 0;
    egg.spawnX = spawnPos.x;
    egg.spawnY = Math.max(spawnPos.y, landY + 0.025);
    egg.spawnZ = u.side * clampedAbsZ;
    egg.landY = landY;
    egg.dxOffset = spawnPos.x - path.points[0].x;
    egg.slopeDist = Math.min(path.slopeLen1 * 0.96, Math.max(0, uSlope0 * path.slopeLen1));
    egg.x = path.points[2].x;
    egg.rollX = 0;
    egg.rollZ = 0;

    egg.mesh.visible = true;
    egg.mesh.position.set(egg.spawnX, egg.spawnY, egg.spawnZ);
    egg.mesh.rotation.set(0, 0, 0);
    egg.mesh.scale.set(0.0001, 0.0001, 0.0001);
    activeEggs.push(egg);
    return egg;
}

/**
 * State machine deterministik per telur (tanpa fisika/mantul acak):
 * 1. LAY -> 2. DROP -> 3. ROLL_SLOPE -> 4. ON_BELT (minSpacing 0.12 m) -> 5. COLLECT (masuk bak & kembali ke pool).
 */
function updateEggRoll(dt) {
    const es = EGG_PATH.eggScale;
    const fullX = EGG_BASE_SIZE.x * es;
    const fullY = EGG_BASE_SIZE.y * es;
    const fullZ = EGG_BASE_SIZE.z * es;
    const rollR = eggRollRadius();

    // Redupkan halus lampu indikator bak pengumpul setelah menerima telur
    for (let s = 0; s < EGG_COLLECTOR.stations.length; s += 1) {
        const st = EGG_COLLECTOR.stations[s];
        if (st.flash > 0) {
            st.flash = Math.max(0, st.flash - dt * 2.6);
            st.beaconMat.emissiveIntensity = 0.75 + st.flash * 2.2;
        }
    }

    // Tahap A: proses fase LAY, DROP, ROLL_SLOPE, dan COLLECT
    for (let i = activeEggs.length - 1; i >= 0; i -= 1) {
        const egg = activeEggs[i];
        const mesh = egg.mesh;
        const pts = egg.path.points;

        if (egg.state === 'LAY') {
            egg.t += dt;
            const k = smooth01(egg.t / EGG_PATH.layScaleSec);
            const s = Math.max(0.002, k);
            mesh.scale.set(fullX * s, fullY * s, fullZ * s);
            mesh.position.set(egg.spawnX, egg.spawnY, egg.spawnZ);
            if (egg.t >= EGG_PATH.layScaleSec) {
                mesh.scale.set(fullX, fullY, fullZ);
                egg.state = 'DROP';
                egg.vy = 0;
            }
        } else if (egg.state === 'DROP') {
            egg.vy += EGG_PATH.gravity * dt;
            mesh.position.x = egg.spawnX;
            mesh.position.z = egg.spawnZ;
            mesh.position.y -= egg.vy * dt;
            if (mesh.position.y <= egg.landY) {
                mesh.position.y = egg.landY;
                egg.state = 'ROLL_SLOPE';
                egg.speed = 0;
            }
        } else if (egg.state === 'ROLL_SLOPE') {
            const s1 = egg.path.slopeLen1;
            const s2 = egg.path.slopeLen2;
            const sTotal = egg.path.slopeTotal;
            const entryX = pts[2].x;

            // Cek apakah ada telur di sabuk yang sedang melintas tepat di depan titik masuk slot ini
            let beltBlocked = false;
            if (egg.slopeDist >= s1 - 0.015) {
                const laneEggs = egg.lane.eggs;
                for (let j = 0; j < laneEggs.length; j += 1) {
                    if (Math.abs(laneEggs[j].x - entryX) < EGG_PATH.minSpacing) {
                        beltBlocked = true;
                        break;
                    }
                }
            }

            if (beltBlocked && egg.slopeDist >= s1) {
                egg.speed = 0;
                egg.slopeDist = s1;
            } else {
                egg.speed = Math.min(egg.speed + EGG_PATH.slopeAccel * dt, EGG_PATH.slopeMaxSpeed);
                const limitDist = beltBlocked ? s1 : sTotal;
                const ds = Math.max(0, Math.min(egg.speed * dt, limitDist - egg.slopeDist));
                egg.slopeDist += ds;
                egg.rollX += (-egg.side) * (ds / rollR);
            }

            if (egg.slopeDist <= s1) {
                const u = s1 > 1e-5 ? egg.slopeDist / s1 : 1;
                mesh.position.lerpVectors(pts[0], pts[1], u);
                mesh.position.x += egg.dxOffset * (1 - u);
            } else {
                const u = s2 > 1e-5 ? smooth01((egg.slopeDist - s1) / s2) : 1;
                mesh.position.lerpVectors(pts[1], pts[2], u);
            }
            mesh.rotation.set(egg.rollX, 0, 0);

            if (!beltBlocked && egg.slopeDist >= sTotal - 1e-4) {
                egg.state = 'ON_BELT';
                egg.x = pts[2].x;
                egg.speed = EGG_PATH.beltSpeed;
                mesh.position.copy(pts[2]);
                egg.lane.eggs.push(egg);
            }
        } else if (egg.state === 'COLLECT') {
            egg.t += dt;
            const rawU = Math.min(1, egg.t / EGG_PATH.collectSec);
            const u = smooth01(rawU);
            // Interpolasi kurva Bezier kuadratik melalui waypoint [W3, W4, W5] masuk ke bak pengumpul
            const om = 1 - u;
            _eggTmpQuadA.copy(pts[3]).multiplyScalar(om * om);
            _eggTmpQuadB.copy(pts[4]).multiplyScalar(2 * om * u);
            _eggTmpVec.copy(pts[5]).multiplyScalar(u * u).add(_eggTmpQuadA).add(_eggTmpQuadB);

            const ds = mesh.position.distanceTo(_eggTmpVec);
            mesh.position.copy(_eggTmpVec);
            egg.rollZ -= ds / rollR;
            mesh.rotation.set(0, 0, egg.rollZ);

            const shrink = rawU > 0.72 ? (1 - smooth01((rawU - 0.72) / 0.28) * 0.65) : 1;
            mesh.scale.set(fullX * shrink, fullY * shrink, fullZ * shrink);

            if (egg.t >= EGG_PATH.collectSec) {
                completeEggCollection(egg);
            }
        }
    }

    // Tahap B: proses fase ON_BELT per jalur dari depan (+X) ke belakang (-X) untuk menjaga jarak >= minSpacing (0.12 m)
    for (let l = 0; l < eggLanes.length; l += 1) {
        const lane = eggLanes[l];
        if (!lane.eggs.length) {
            continue;
        }
        lane.eggs.sort((a, b) => b.x - a.x);
        for (let idx = 0; idx < lane.eggs.length; idx += 1) {
            const egg = lane.eggs[idx];
            const pts = egg.path.points;
            let desiredSpeed = EGG_PATH.beltSpeed;
            let maxAllowedX = pts[3].x;

            if (idx > 0) {
                const ahead = lane.eggs[idx - 1];
                const gap = ahead.x - egg.x;
                const slowBand = EGG_PATH.minSpacing * 1.45;
                if (gap < slowBand) {
                    const ratio = smooth01((gap - EGG_PATH.minSpacing) / Math.max(0.01, slowBand - EGG_PATH.minSpacing));
                    desiredSpeed = EGG_PATH.beltSpeed * ratio;
                }
                maxAllowedX = Math.min(maxAllowedX, Math.max(egg.x, ahead.x - EGG_PATH.minSpacing));
            }

            egg.speed = desiredSpeed;
            const nextX = Math.min(maxAllowedX, egg.x + egg.speed * dt);
            const dx = Math.max(0, nextX - egg.x);
            egg.x += dx;

            const uBelt = Math.min(1, Math.max(0, (egg.x - pts[2].x) / egg.path.beltLen));
            meshPositionOnBelt(egg, pts[2], pts[3], uBelt, dx, rollR);

            if (egg.x >= pts[3].x - 1e-4) {
                lane.eggs.splice(idx, 1);
                idx -= 1;
                egg.state = 'COLLECT';
                egg.t = 0;
            }
        }
    }
}

function meshPositionOnBelt(egg, wStart, wEnd, uBelt, dx, rollR) {
    const mesh = egg.mesh;
    mesh.position.lerpVectors(wStart, wEnd, uBelt);
    egg.rollZ -= dx / rollR;
    const dampRollX = egg.rollX * Math.max(0, 1 - uBelt * 5);
    mesh.rotation.set(dampRollX, 0, egg.rollZ);
}

// ---------------------------------------------------------------------
// STATE MACHINE AYAM: IDLE <-> MAKAN (di lubang makan & talang kuning) <-> BERTELUR
// ---------------------------------------------------------------------
function smooth01(x) {
    const v = Math.min(1, Math.max(0, x));
    return v * v * (3 - 2 * v);
}

function initChickenState(chicken, now) {
    const u = chicken.userData;
    u.state = 'IDLE';
    u.stateEnd = now + randomBetween(0, KANDANG_ANIM_CONFIG.makan.idleMaxSec) * 1000;
    u.lookNext = now + randomBetween(200, 1400);
    u.feedUntil = 0;
    u.feedBlend = 0;
    u.targetBlend = 0;
    u.wave = 0;
    u.peckT = 0;
    u.peckTarget = 0;
    u.peckPhase = 'look';
    u.yawNow = u.idleYaw;
    u.targetYaw = u.idleYaw;
    u.squatNow = 0;
    u.targetSquat = 0;
    u.tailNow = 0;
    u.targetTail = 0;
    u.shake = 0;
    u.pendingLay = false;
    u.layStart = 0;
    u.layDurationMs = EGG_PATH.layDurationSec * 1000;
    u.laySpawned = false;

    // Variabel gerak hidup alami (micro-life & sub-aktivitas IDLE di dalam sekat sangkar)
    u.breathMul = 0.86 + Math.random() * 0.28;
    u.idleX = u.homeX;
    u.idleAbsZ = Math.min(HEN_IDLE.roamAbsZMax, Math.max(HEN_IDLE.roamAbsZMin, Math.abs(u.homeZ)));
    u.idleTargetX = u.idleX;
    u.idleTargetAbsZ = u.idleAbsZ;
    u.bodyYawOffset = u.yawJitter;
    u.targetBodyYawOffset = u.yawJitter;
    u.idlePitchNow = u.idlePitch;
    u.idleTargetPitch = u.idlePitch;
    u.idleReachNow = 0;
    u.idleTargetReach = 0;
    u.saccadeKick = 0;
    u.walkPhase = Math.random() * Math.PI * 2;
    u.walkStepAmp = 0;
    u.legPhase = Math.random() * Math.PI * 2;
    u.legAmp = 0;
    u.legAmpEff = 0;
    u.scratchActive = false;
    u.fidgetActive = false;
    u.fidgetNext = now + randomBetween(200, HEN_IDLE.fidgetMaxSec * 1000);
    u.fidgetUntil = 0;
    u.prevWorldX = u.idleX;
    u.prevAbsZ = u.idleAbsZ;
    u.prevRotY = u.sideYaw + u.bodyYawOffset;
    u.idleSub = 'LOOK';
    u.subStart = now;
    u.subEnd = now + randomBetween(300, 2600);
    u.preenKind = 'wag';
    u.preenSide = Math.random() < 0.5 ? -1 : 1;
}

/** Kereta pakan sedang jalan dan berada dalam jangkauan ayam pada sumbu X. */
function isFeederNear(u) {
    return isFeederRunning3D && Boolean(feederCart3D)
        && Math.abs(u.holeX - feederPositionX) < KANDANG_ANIM_CONFIG.makan.feederReach;
}

/** Kereta pakan sedang mendekat sehingga ayam mulai antusias menghadap talang. */
function isFeederApproaching(u) {
    return isFeederRunning3D && Boolean(feederCart3D)
        && Math.abs(u.holeX - feederPositionX) < HEN_IDLE.feederAlertDist;
}

/** Perbarui masa tersedianya pakan di talang depan ayam; true bila pakan masih ada. */
function updateFeedAvailability(u, now) {
    if (u.feedPose && isFeederNear(u)) {
        if (now >= u.feedUntil && u.state === 'IDLE' && !u.pendingLay) {
            u.stateEnd = Math.min(u.stateEnd, now + randomBetween(80, 360));
        }
        u.feedUntil = now + FEED.lingerSec * 1000;
    }
    return now < u.feedUntil;
}

function startMakan(chicken, now) {
    const cfg = KANDANG_ANIM_CONFIG.makan;
    const u = chicken.userData;
    const phaseDelayMs = ((u.peckPhaseOffset || 0) / (Math.PI * 2)) * (1000 / Math.max(0.5, FEED.peckHz));
    u.state = 'MAKAN';
    u.stateEnd = now + randomBetween(cfg.eatMinSec, cfg.eatMaxSec) * 1000;
    u.peckPhase = 'look';
    u.peckT = 0;
    u.peckTarget = Math.round(randomBetween(cfg.peckMin, cfg.peckMax));
    u.lookStart = now;
    u.lookEnd = now + phaseDelayMs;
    u.targetYaw = 0;
    u.targetBodyYawOffset = 0;
}

function startBertelur(chicken, now) {
    const u = chicken.userData;
    u.pendingLay = false;
    u.state = 'BERTELUR';
    u.layStart = now;
    u.layDurationMs = EGG_PATH.layDurationSec * 1000 * randomBetween(0.92, 1.08);
    u.stateEnd = now + u.layDurationMs;
    u.laySpawned = false;
    u.targetBodyYawOffset = u.yawJitter * 0.4;
}

/** Pilih ayam untuk bertelur (menghadap depan, tidak sedang minum ke belakang atau makan). */
function pickLayingChicken(now) {
    const n = chickensList.length;
    for (let tries = 0; tries < 24; tries += 1) {
        const c = chickensList[Math.floor(Math.random() * n)];
        const u = c.userData;
        if (!findEggLane(c) || !u.eggPath) {
            continue;
        }
        if (u.state === 'IDLE' && u.feedBlend < 0.05 && now >= u.feedUntil && Math.abs(u.bodyYawOffset) < 0.55) {
            startBertelur(c, now);
            return;
        }
    }
    const fallback = chickensList[Math.floor(Math.random() * n)];
    if (fallback && fallback.userData && fallback.userData.state !== 'BERTELUR') {
        fallback.userData.pendingLay = true;
    }
}

function decideChickenState(chicken, now) {
    const cfg = KANDANG_ANIM_CONFIG.makan;
    const u = chicken.userData;
    if (u.state === 'IDLE' && u.pendingLay && u.feedBlend < 0.05 && Math.abs(u.bodyYawOffset) < 0.55) {
        startBertelur(chicken, now);
        return;
    }
    if (u.state === 'IDLE' && now >= u.stateEnd) {
        if (u.feedPose && now < u.feedUntil && !u.pendingLay && Math.random() < cfg.pEatFeeder) {
            startMakan(chicken, now);
        } else {
            u.stateEnd = now + randomBetween(cfg.idleMinSec, cfg.idleMaxSec) * 1000;
        }
    }
}

/** Pilih sub-perilaku alami berikutnya saat ayam berada pada state IDLE. */
function chooseNextIdleSub(u, now) {
    u.subStart = now;

    // Jika kereta pakan mendekat, ayam antusias maju ke dekat bagian depan sekatnya
    if (isFeederApproaching(u) && !u.pendingLay) {
        u.idleSub = 'ALERT';
        u.subEnd = now + randomBetween(700, 1400);
        u.idleTargetX = u.holeX + randomBetween(-0.025, 0.025);
        u.idleTargetAbsZ = HEN_IDLE.roamAbsZMin;
        u.targetBodyYawOffset = randomBetween(-0.10, 0.10);
        u.idleTargetPitch = -0.08;
        u.idleTargetReach = 0.01;
        u.lookNext = now + randomBetween(180, 450);
        return;
    }

    // Jika baru selesai DRINK (menghadap belakang), kembalikan arah hadap ke depan terlebih dahulu
    if (u.idleSub === 'DRINK') {
        u.idleSub = 'LOOK';
        u.subEnd = now + randomBetween(1400, 2600);
        u.targetBodyYawOffset = randomBetween(-0.30, 0.30);
        u.idleTargetAbsZ = randomBetween(HEN_IDLE.roamAbsZMin + 0.04, 2.12);
        u.idleTargetPitch = randomBetween(-0.04, 0.16);
        u.idleTargetReach = 0;
        u.lookNext = now + 350;
        return;
    }

    const r = Math.random();
    if (r < 0.26) {
        u.idleSub = 'LOOK';
        u.subEnd = now + randomBetween(1600, 3400);
        u.idleTargetPitch = randomBetween(-0.10, 0.22);
        u.idleTargetReach = randomBetween(0, 0.015);
        u.lookNext = now + randomBetween(120, 600);
    } else if (r < 0.54) {
        u.idleSub = 'STEP';
        u.subEnd = now + randomBetween(1400, 2700);
        u.idleTargetX = u.holeX + randomBetween(-HEN_IDLE.roamXMax, HEN_IDLE.roamXMax);
        u.idleTargetAbsZ = randomBetween(HEN_IDLE.roamAbsZMin, HEN_IDLE.roamAbsZMax - 0.04);
        const moveAngle = Math.atan2(u.idleTargetX - u.idleX, Math.max(0.04, Math.abs(u.idleAbsZ - u.idleTargetAbsZ)));
        u.targetBodyYawOffset = Math.max(-0.42, Math.min(0.42, moveAngle * 0.45 + randomBetween(-0.28, 0.28)));
        u.idleTargetPitch = randomBetween(0.04, 0.20);
        u.targetYaw = randomBetween(-0.28, 0.28);
        u.saccadeKick = 0.8;
    } else if (r < 0.76) {
        u.idleSub = 'FORAGE';
        u.subEnd = now + randomBetween(1800, 3400);
        u.targetBodyYawOffset = randomBetween(-0.35, 0.35);
        u.targetYaw = randomBetween(-0.32, 0.32);
        u.idleTargetPitch = HEN_IDLE.foragePitch * randomBetween(0.85, 1.06);
        u.saccadeKick = 1.0;
    } else if (r < 0.88 && !u.pendingLay) {
        u.idleSub = 'DRINK';
        u.subEnd = now + randomBetween(2200, 3500);
        u.idleTargetX = u.holeX + randomBetween(-0.04, 0.04);
        u.idleTargetAbsZ = HEN_IDLE.roamAbsZMax;
        const turnSign = u.slotIdx === 0 ? 1 : (u.slotIdx === 2 ? -1 : (Math.random() < 0.5 ? -1 : 1));
        u.targetBodyYawOffset = turnSign * randomBetween(2.45, 2.78);
        u.targetYaw = 0;
        u.idleTargetPitch = HEN_IDLE.drinkPitch;
    } else {
        u.idleSub = 'PREEN';
        u.preenKind = Math.random() < 0.48 ? 'wag' : 'wing';
        u.preenSide = Math.random() < 0.5 ? -1 : 1;
        u.subEnd = now + (u.preenKind === 'wag' ? randomBetween(1100, 1700) : randomBetween(2000, 3200));
        u.targetYaw = u.preenKind === 'wing' ? u.preenSide * HEN_IDLE.preenYaw : randomBetween(-0.2, 0.2);
        u.idleTargetPitch = u.preenKind === 'wing' ? 0.34 : 0.08;
        u.saccadeKick = 0.7;
    }
}

/** Mematok di permukaan talang kuning melalui tengah lubang makan (headYaw dijaga ~0 agar tidak menabrak kawat). */
function playPeckAnimation(chicken, dt, now) {
    const cfg = KANDANG_ANIM_CONFIG.makan;
    const u = chicken.userData;
    u.targetBlend = 1;
    u.targetSquat = 0;
    u.targetTail = 0;
    u.shake = 0;
    u.targetYaw = 0;
    u.targetBodyYawOffset = 0;

    if (u.peckPhase === 'peck') {
        u.peckT += dt * FEED.peckHz * u.peckHzMul;
        if (u.peckT >= u.peckTarget) {
            u.peckT = u.peckTarget;
            u.wave = 0;
            u.peckPhase = 'look';
            u.lookStart = now;
            u.lookEnd = now + randomBetween(cfg.lookPauseMin, cfg.lookPauseMax) * 1000;
        } else {
            u.wave = 0.5 + 0.5 * Math.sin(Math.PI * 2 * u.peckT - Math.PI / 2);
        }
    } else {
        u.wave = 0;
        if (now >= u.lookEnd) {
            u.peckPhase = 'peck';
            u.peckT = 0;
            u.peckTarget = Math.round(randomBetween(cfg.peckMin, cfg.peckMax));
        }
    }
}

/** Bertelur (~1 dtk): ayam merunduk, ekor terangkat, bergetar halus, telur keluar tepat di puncak kontraksi. */
function playLayEggAnimation(chicken, dt, now) {
    const u = chicken.userData;
    const progress = Math.min(1, Math.max(0, (now - u.layStart) / Math.max(1, u.layDurationMs)));
    const contractEnv = progress < EGG_PATH.layPeakFrac
        ? smooth01(progress / EGG_PATH.layPeakFrac)
        : 1 - 0.35 * smooth01((progress - EGG_PATH.layPeakFrac) / Math.max(0.05, 1 - EGG_PATH.layPeakFrac));

    u.targetBlend = 0;
    u.wave = 0;
    u.targetYaw = 0;
    u.targetBodyYawOffset = 0;
    u.idleTargetPitch = 0.06;
    u.idleTargetReach = 0;
    u.targetSquat = henModel.squat * contractEnv;
    u.targetTail = HEN_LAY.tailLift * contractEnv;
    u.shake = HEN_LAY.shakeAmp * contractEnv;

    if (!u.laySpawned && progress >= EGG_PATH.layPeakFrac) {
        u.laySpawned = true;
        spawnEgg(chicken);
    }
    if (now >= u.stateEnd) {
        u.state = 'IDLE';
        u.shake = 0;
        u.stateEnd = now + randomBetween(KANDANG_ANIM_CONFIG.makan.idleMinSec, KANDANG_ANIM_CONFIG.makan.idleMaxSec) * 1000;
        chooseNextIdleSub(u, now);
    }
}

/**
 * Perilaku alami saat IDLE: napas berkelanjutan, saccadic head-snap khas unggas,
 * langkah pendek di dalam sekat, eksplorasi lantai, minum di pipa nipple belakang, & kibas ekor/merapikan bulu.
 */
function playIdleAnimation(chicken, dt, now) {
    const u = chicken.userData;
    u.targetBlend = 0;
    u.wave = 0;

    if (now >= u.subEnd || (isFeederApproaching(u) && u.idleSub !== 'ALERT' && u.idleSub !== 'DRINK')) {
        chooseNextIdleSub(u, now);
    }

    const tSec = (now - u.subStart) * 0.001;
    u.shake = 0;
    u.targetSquat = 0;
    u.targetTail = 0;
    u.idleTargetReach = 0;
    u.scratchActive = false;

    // Geser tumpuan cakar berkala di atas kawat miring 8.5° agar kaki tidak kaku saat ayam berdiri di tempat
    if (now >= u.fidgetNext) {
        u.fidgetUntil = now + randomBetween(460, 820);
        u.fidgetNext = now + randomBetween(HEN_IDLE.fidgetMinSec, HEN_IDLE.fidgetMaxSec) * 1000;
        if (u.idleSub === 'LOOK' || u.idleSub === 'PREEN') {
            u.idleTargetX = Math.max(
                u.holeX - HEN_IDLE.roamXMax,
                Math.min(u.holeX + HEN_IDLE.roamXMax, u.idleX + randomBetween(-0.024, 0.024))
            );
            u.idleTargetAbsZ = Math.max(
                HEN_IDLE.roamAbsZMin,
                Math.min(HEN_IDLE.roamAbsZMax - 0.04, u.idleAbsZ + randomBetween(-0.025, 0.025))
            );
        }
    }
    u.fidgetActive = now < u.fidgetUntil;

    if (u.idleSub === 'LOOK' || u.idleSub === 'ALERT') {
        const fast = u.idleSub === 'ALERT';
        if (now >= u.lookNext) {
            u.targetYaw = randomBetween(fast ? -0.38 : -0.68, fast ? 0.38 : 0.68);
            u.idleTargetPitch = fast ? randomBetween(-0.12, 0.08) : randomBetween(-0.12, 0.26);
            u.saccadeKick = 1.0;
            u.lookNext = now + (fast ? randomBetween(280, 680) : randomBetween(550, 1550));
        }
        u.targetSquat = fast ? -0.004 : 0;
        u.targetTail = fast ? 0.10 : 0.02;
    } else if (u.idleSub === 'STEP') {
        if (now >= u.lookNext) {
            u.targetYaw = randomBetween(-0.42, 0.42);
            u.saccadeKick = 0.75;
            u.lookNext = now + randomBetween(500, 1200);
        }
        u.targetTail = 0.06;
    } else if (u.idleSub === 'FORAGE') {
        // Pola alami ayam: mengais lantai kawat dengan cakar bergantian (45% awal siklus), lalu mematuk (55% sisa siklus)
        const forageCycle = ((tSec * 0.85) + (u.bobOffset / (Math.PI * 2))) % 1;
        if (forageCycle < 0.45) {
            u.scratchActive = true;
            u.idleTargetPitch = 0.34 + 0.06 * Math.sin(tSec * 12.0);
            u.idleTargetReach = 0.012;
            u.targetSquat = 0.010;
            u.targetTail = 0.18;
        } else {
            const peckWave = Math.max(0, Math.sin(tSec * Math.PI * 2 * HEN_IDLE.forageHz + u.bobOffset));
            const pulse = peckWave * peckWave;
            u.idleTargetPitch = HEN_IDLE.foragePitch + 0.16 * pulse;
            u.idleTargetReach = 0.028 * pulse;
            u.targetSquat = 0.016 + 0.006 * pulse;
            u.targetTail = 0.12 + 0.06 * pulse;
        }
    } else if (u.idleSub === 'DRINK') {
        const turnedEnough = Math.abs(u.bodyYawOffset) > 1.75;
        const sip = turnedEnough ? Math.max(0, Math.sin(tSec * Math.PI * 2 * HEN_IDLE.drinkHz + u.bobOffset)) : 0;
        u.idleTargetPitch = turnedEnough ? (HEN_IDLE.drinkPitch - 0.10 * sip) : 0.06;
        u.idleTargetReach = turnedEnough ? (0.020 * sip) : 0;
        u.targetSquat = -0.004 * sip;
        u.targetTail = -0.05;
    } else if (u.idleSub === 'PREEN') {
        if (u.preenKind === 'wag') {
            u.fidgetActive = true;
            u.targetSquat = 0.018;
            u.targetTail = 0.24 + 0.20 * Math.sin(tSec * Math.PI * 2 * HEN_IDLE.tailWagHz);
            u.shake = 0.0022;
        } else {
            const nibble = 0.08 * Math.sin(tSec * 14.0);
            u.targetSquat = 0.026;
            u.targetYaw = u.preenSide * HEN_IDLE.preenYaw;
            u.idleTargetPitch = 0.34 + nibble;
            u.targetTail = 0.08;
        }
    }
}

/**
 * Hitung pose akhir ayam:
 * - Saat IDLE: gabungkan napas mikro asinkron, head-snap khas unggas, langkah & kaisan cakar kaki kiri-kanan, & sub-aktivitas.
 * - Saat MAKAN (feedBlend -> 1): kaki melangkah maju saat menuju lubang lalu menapak mantap di lantai miring 8.5°,
 *   X terkunci ke u.holeX, dan headYaw -> 0 agar kepala lolos bersih tanpa menyentuh kawat.
 */
function applyChickenPose(chicken, dt, now) {
    const u = chicken.userData;
    const fp = u.feedPose;

    const step = dt / FEED.blendSec;
    const target = (u.targetBlend && fp) ? 1 : 0;
    u.feedBlend += Math.max(-step, Math.min(step, target - u.feedBlend));
    const eBody = smooth01(u.feedBlend);
    const eHead = smooth01(Math.min(1, u.feedBlend * 1.65));
    const idleFade = u.state === 'BERTELUR' ? 0 : (1 - eBody);
    const idleHeadFade = u.state === 'BERTELUR' ? 0 : (1 - eHead);

    // Pergeseran langkah kaki halus di dalam sekat kompartemen saat IDLE
    if (u.state === 'IDLE' && eBody < 0.05) {
        const dx = u.idleTargetX - u.idleX;
        const dz = u.idleTargetAbsZ - u.idleAbsZ;
        const dist = Math.hypot(dx, dz);
        if (dist > 0.002) {
            const move = Math.min(dist, HEN_IDLE.walkSpeed * dt);
            u.idleX += (dx / dist) * move;
            u.idleAbsZ += (dz / dist) * move;
            u.walkPhase += dt * Math.PI * 2 * HEN_IDLE.walkBobHz;
            u.walkStepAmp = Math.min(1, u.walkStepAmp + dt * 9);
        } else {
            u.walkStepAmp = Math.max(0, u.walkStepAmp - dt * 8);
        }
    } else {
        u.walkStepAmp = Math.max(0, u.walkStepAmp - dt * 10);
    }

    // Saccadic head-snap (cepat untuk kepala/leher khas ayam, mulus untuk badan)
    const kSnap = Math.min(1, dt * HEN_IDLE.headSnapSpeed);
    const kBody = Math.min(1, dt * 6.5);
    u.yawNow += (u.targetYaw - u.yawNow) * kSnap;
    u.idlePitchNow += (u.idleTargetPitch - u.idlePitchNow) * Math.min(1, dt * 11);
    u.idleReachNow += (u.idleTargetReach - u.idleReachNow) * Math.min(1, dt * 12);
    u.bodyYawOffset += (u.targetBodyYawOffset - u.bodyYawOffset) * Math.min(1, dt * 5.2);
    u.squatNow += (u.targetSquat - u.squatNow) * kBody;
    u.tailNow += (u.targetTail - u.tailNow) * Math.min(1, dt * 10);
    u.saccadeKick = Math.max(0, u.saccadeKick - dt * 6.5);

    const frontAbs = fp ? fp.frontAbsZ : u.idleAbsZ;
    const curAbsZ = u.idleAbsZ + (frontAbs - u.idleAbsZ) * eBody;
    const shakeX = (u.state === 'BERTELUR' || u.shake > 0)
        ? Math.sin(now * 0.055 + u.bobOffset) * u.shake * (1 - eBody)
        : 0;
    const curX = u.idleX + (u.holeX - u.idleX) * eBody + shakeX;
    const curY = cageFloorHeightAtAbsZ(u.floorY, curAbsZ);
    const rotY = u.sideYaw + u.bodyYawOffset * (1 - eBody);

    // Kinematika langkah kaki & cakar unggas (sinkron dengan perpindahan nyata, putaran badan, kaisan lantai, & geser tumpuan)
    const prevX = u.prevWorldX !== undefined ? u.prevWorldX : curX;
    const prevZ = u.prevAbsZ !== undefined ? u.prevAbsZ : curAbsZ;
    const prevRot = u.prevRotY !== undefined ? u.prevRotY : rotY;
    const dPos = Math.hypot(curX - prevX, curAbsZ - prevZ);
    const dTurn = Math.abs(rotY - prevRot) * 0.11;
    u.prevWorldX = curX;
    u.prevAbsZ = curAbsZ;
    u.prevRotY = rotY;

    const footTravel = dPos + dTurn;
    const footSpeed = footTravel / Math.max(dt, 0.001);
    let targetLegAmp = 0;
    if (u.state !== 'BERTELUR' && eBody <= 0.99) {
        if (u.state === 'IDLE' && u.scratchActive && eBody < 0.05) {
            u.legPhase = (u.legPhase + dt * Math.PI * 2 * HEN_IDLE.scratchHz) % (Math.PI * 2);
            targetLegAmp = 0.94;
        } else if (footSpeed > 0.006) {
            const dPhi = Math.max(
                (footTravel / HEN_IDLE.strideLengthM) * Math.PI * 2,
                dt * Math.PI * 2 * HEN_IDLE.minStepHz
            );
            u.legPhase = (u.legPhase + dPhi) % (Math.PI * 2);
            targetLegAmp = Math.min(1.0, 0.64 + footSpeed * 2.6);
        } else if (u.state === 'IDLE' && u.fidgetActive && eBody < 0.05) {
            u.legPhase = (u.legPhase + dt * Math.PI * 2 * HEN_IDLE.fidgetStepHz) % (Math.PI * 2);
            targetLegAmp = 0.58;
        }
    }
    const ampRate = targetLegAmp > u.legAmp ? 13.0 : 8.5;
    u.legAmp += (targetLegAmp - u.legAmp) * Math.min(1, dt * ampRate);
    if (u.legAmp < 0.002) {
        u.legAmp = 0;
    }
    const plantFade = 1 - smooth01((eBody - 0.92) / 0.08);
    const legAmpEff = u.legAmp * plantFade;
    u.legAmpEff = legAmpEff;

    // Napas berkelanjutan & ayunan langkah mikro mengikuti irama kaki kiri-kanan (padam 0 saat masuk lubang makan)
    const breathWave = Math.sin(now * 0.001 * Math.PI * 2 * HEN_IDLE.breathHz * u.breathMul + u.bobOffset);
    const stepDoubleBob = Math.abs(Math.cos(u.legPhase)) * legAmpEff;
    const microPitch = (breathWave * HEN_IDLE.breathPitchRad + stepDoubleBob * 0.085) * idleHeadFade;
    const microReach = (u.saccadeKick * (1 - u.saccadeKick) * 4 * HEN_IDLE.headBobKickM
        + stepDoubleBob * 0.018) * idleHeadFade;
    const microSquat = (breathWave * HEN_IDLE.breathSquatM + stepDoubleBob * 0.006) * idleFade;
    const microTail = (breathWave * HEN_IDLE.breathTailRad + Math.sin(u.legPhase) * 0.045 * legAmpEff) * idleFade;

    let pitch = u.idlePitchNow * (1 - eHead) + microPitch;
    let reach = u.idleReachNow * idleHeadFade + microReach;
    if (fp && u.feedBlend > 0) {
        const wavePart = FEED.headUpFrac + (1 - FEED.headUpFrac) * u.wave;
        pitch += fp.pitch * (eHead * wavePart);
        reach += fp.reach * (eBody * wavePart);
    }

    const yawEffective = u.yawNow * (1 - eHead);
    const squatEffective = Math.max(-0.01, u.squatNow + microSquat);
    const tailEffective = u.tailNow + microTail;

    chicken.position.set(curX, curY, u.side * curAbsZ);
    chicken.rotation.set(
        0,
        rotY,
        Math.sin(u.legPhase) * HEN_IDLE.weightRollRad * legAmpEff * (1 - eBody)
    );

    if (henInst && u.instIndex !== undefined) {
        chicken.updateMatrixWorld(true);
        henInst.mesh.setMatrixAt(u.instIndex, chicken.matrixWorld);
        const o = u.instIndex * 4;
        henInst.pose[o] = pitch;
        henInst.pose[o + 1] = yawEffective;
        henInst.pose[o + 2] = squatEffective;
        henInst.pose[o + 3] = tailEffective;
        const ampInt = Math.round(Math.min(1, Math.max(0, legAmpEff)) * 1000);
        const phi01 = (((u.legPhase / (Math.PI * 2)) % 1) + 1) % 1;
        henInst.reach[u.instIndex * 2] = reach;
        henInst.reach[u.instIndex * 2 + 1] = ampInt > 0 ? (ampInt + phi01 * 0.998) : 0;
    } else {
        applyProcHenPose(chicken, pitch, yawEffective, squatEffective, tailEffective, reach, u.legPhase, legAmpEff);
    }
}

function updateChickenStates(dt, now) {
    const n = chickensList.length;
    if (!n) return;

    if (nextLayAt === 0) {
        nextLayAt = now + 1200;
        for (let i = 0; i < n; i += 1) {
            initChickenState(chickensList[i], now);
            chooseNextIdleSub(chickensList[i].userData, now);
        }
    }
    if (now >= nextLayAt) {
        nextLayAt = now + EGG_PATH.layIntervalSec * 1000 * randomBetween(0.75, 1.25);
        pickLayingChicken(now);
    }

    for (let i = 0; i < n; i += 1) {
        updateFeedAvailability(chickensList[i].userData, now);
    }

    const groups = Math.max(1, KANDANG_ANIM_CONFIG.performa.stateUpdateGroups);
    const slice = decisionCursor++ % groups;
    for (let i = slice; i < n; i += groups) {
        decideChickenState(chickensList[i], now);
    }

    for (let i = 0; i < n; i += 1) {
        const c = chickensList[i];
        const u = c.userData;
        if (u.state === 'MAKAN' && (now >= u.feedUntil || now >= u.stateEnd)) {
            u.state = 'IDLE';
            u.stateEnd = now + randomBetween(KANDANG_ANIM_CONFIG.makan.idleMinSec, KANDANG_ANIM_CONFIG.makan.idleMaxSec) * 1000;
        }
        if (u.state === 'IDLE' && u.pendingLay && u.feedBlend < 0.05) {
            startBertelur(c, now);
        }
        if (u.state === 'MAKAN') {
            playPeckAnimation(c, dt, now);
        } else if (u.state === 'BERTELUR') {
            playLayEggAnimation(c, dt, now);
        } else {
            playIdleAnimation(c, dt, now);
        }
        applyChickenPose(c, dt, now);
    }

    if (henInst) {
        if (neighborHenSlots.length > 0) {
            const baseIdx = chickensList.length;
            const stepCount = Math.min(24, neighborHenSlots.length);
            for (let s = 0; s < stepCount; s += 1) {
                neighborAnimCursor = (neighborAnimCursor + 1) % neighborHenSlots.length;
                const slot = neighborHenSlots[neighborAnimCursor];
                const idx = baseIdx + neighborAnimCursor;
                const o = idx * 4;
                const t = now * 0.85 + slot.phase;
                const peckWave = slot.isFeederNear ? Math.max(0, Math.sin(now * 6.5 + slot.phase)) : 0;
                henInst.pose[o] = slot.pitchBase + peckWave * 0.65;
                henInst.pose[o + 1] = slot.isFeederNear ? 0 : Math.sin(t) * 0.38;
                henInst.reach[idx * 2] = slot.isFeederNear ? 0.06 : slot.reachBase;
            }
        }
        henInst.mesh.instanceMatrix.needsUpdate = true;
        henInst.poseAttr.needsUpdate = true;
        henInst.reachAttr.needsUpdate = true;
        if (henInst.material.userData.shader) {
            henInst.material.userData.shader.uniforms.uTilt.value = FEED.bodyTilt;
        }
    }
}

// ---------------------------------------------------------------------
// PERBAIKAN RENDER KIPAS (Bug 1)
// Penyebab: kipas x=0 & x=9 berimpit dengan penggantung rel / tiang portal,
// bola LED plafon (x=0) menembus strut, cincin terlalu rendah (menyentuh
// troli saat lewat), dan atap opak menutupi kipas dari kamera perspektif.
// ---------------------------------------------------------------------
function fixFanRendering() {
    scene3D.updateMatrixWorld(true);
    const fanSet = new Set();
    fanGroups.forEach((g) => g.traverse((o) => fanSet.add(o)));

    const envRoot = scene3D.getObjectByName('FarmEnvironment3D');
    const envSet = new Set();
    if (envRoot) envRoot.traverse((o) => envSet.add(o));
    const obstacles = [];
    scene3D.traverse((o) => {
        if (!o.isMesh || fanSet.has(o) || envSet.has(o) || o === feederCart3D) return;
        if (feederCart3D && feederCart3D.children.includes(o)) return;
        const box = new THREE.Box3().setFromObject(o);
        const size = box.getSize(new THREE.Vector3());
        if (size.x > 6 || box.max.y < 2.3 || box.min.y > 3.7) return;
        obstacles.push(box);
    });

    fanGroups.forEach((group) => {
        for (let attempt = 0; attempt < 12; attempt++) {
            group.updateMatrixWorld(true);
            const fanBox = new THREE.Box3().setFromObject(group).expandByScalar(0.02);
            const hit = obstacles.some((b) => {
                const shrunk = b.clone().expandByScalar(-0.01);
                return shrunk.intersectsBox(fanBox);
            });
            if (!hit) break;
            group.position.x += 0.4;
        }
        group.traverse((o) => {
            if (!o.isMesh) return;
            o.renderOrder = 2;
            o.castShadow = true;
            const mats = Array.isArray(o.material) ? o.material : [o.material];
            mats.forEach((m) => {
                m.transparent = false;
                m.depthWrite = true;
                m.depthTest = true;
                m.polygonOffset = true;
                m.polygonOffsetFactor = -1;
                m.polygonOffsetUnits = -1;
            });
        });
    });
}

// ---------------------------------------------------------------------
// PIN PENJELASAN IOT (Bug 2): overlay HTML, leader line, anti-tumpang-tindih
// ---------------------------------------------------------------------
const IOT_PIN_INFO = {
    'Panel ESP32 & Relay': {
        short: 'ESP32 & Relay',
        name: 'ESP32 30-Pin + Step-Down LM2596 (5.0V) & Relay CW-021',
        fungsi: 'Otak sistem: membaca sensor DHT22 (D15), OLED 0.96" (D21/D22), mengendalikan Relay K1 Kipas 12V (D26), K2 Pompa 5V (D14), dan driver L298N.',
        nilai: () => 'Online · menerima data'
    },
    'Motor & Driver L298N': {
        short: 'L298N (Motor & LED)',
        name: 'Motor Gearbox Kuning (OUT1/OUT2) & LED Strip 5V (OUT3) · L298N',
        fungsi: 'Menggerakkan katrol kereta pakan via D18/D19 (IN1/IN2) dan menyalakan LED Strip DC 5V via D27 (IN3 → OUT3).',
        nilai: () => (document.getElementById('status-feeder-3d') || {}).textContent || '-'
    },
    'Limit Switch A': {
        short: 'Limit A (D33)',
        name: 'Micro Limit Switch Merah KW12 (Pin D33 · Pangkal A)',
        fungsi: 'Menghentikan motor kereta pakan saat menyentuh batas pangkal lorong kandang (titik A).',
        nilai: () => (feederPositionX <= FEEDER_LIMIT_A + 0.1 ? 'TERTEKAN (kereta di A)' : 'Terbuka')
    },
    'Limit Switch B': {
        short: 'Limit B (D32)',
        name: 'Micro Limit Switch Hitam V-156 (Pin D32 · Ujung B)',
        fungsi: 'Membalik arah motor kereta pakan saat menyentuh batas ujung lorong kandang (titik B).',
        nilai: () => (feederPositionX >= FEEDER_LIMIT_B - 0.1 ? 'TERTEKAN (kereta di B)' : 'Terbuka')
    },
    'Tandon & Pompa Sprinkler': {
        short: 'Pompa 5V (D14)',
        name: 'Tandon Air & Pompa Celup Putih 5V (Pin D14 · Relay K2)',
        fungsi: 'Menyuplai air 5V ke selang sprinkler atap luar untuk mendinginkan seng/mika tanpa membasahi ayam.',
        nilai: () => (isSprinklerActive3D ? 'Pompa ON (menyemprot atap)' : 'Pompa OFF')
    },
    'Sensor DHT22 Suhu': {
        short: 'DHT22 (D15)',
        name: 'Sensor DHT22 PCB Hitam 3-Pin (Pin D15)',
        fungsi: 'Mengukur suhu & kelembaban udara setinggi ayam untuk evaluasi THI & proteksi anti-bau (Lock RH ≥ 75%).',
        nilai: () => {
            const t = window.kandangTelemetry;
            return t ? `${t.suhu}°C · RH ${t.kelembaban}%` : 'Menunggu data sensor...';
        }
    },
    'Kipas Sirkulasi Plafon 4x4': {
        short: 'Kipas 12V (D26)',
        name: 'Kipas Sirkulasi DC 12V Stiker Pink (Pin D26 · Relay K1)',
        fungsi: 'Membuang kubah hawa panas di bawah atap dengan suplai 12V langsung melalui NO1 Relay agar sirkulasi udara tetap sejuk.',
        nilai: () => (isFanSpinning3D ? 'ON (berputar)' : 'OFF')
    }
};

/** Membuat penanda 3D (Object3D kosong) yang posisinya jadi jangkar pin HTML. */
function registerIoTPinAnchor(text, colorHex, icon) {
    const anchor = new THREE.Object3D();
    iotPinDefs.push({ text, colorHex, icon, anchor });
    return anchor;
}

function createIoTPins() {
    const canvasHost = document.getElementById('canvas-container-3d');
    if (!canvasHost || !canvasHost.parentElement) return;
    const host = canvasHost.parentElement;

    if (iotPinLayer) iotPinLayer.remove();
    iotPins = [];

    iotPinLayer = document.createElement('div');
    iotPinLayer.style.cssText = 'position:absolute;inset:0;overflow:hidden;pointer-events:none;z-index:15;';

    iotPinSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    iotPinSvg.setAttribute('style', 'position:absolute;inset:0;width:100%;height:100%;pointer-events:none;overflow:visible;');
    iotPinLayer.appendChild(iotPinSvg);

    iotPinDefs.forEach((def) => {
        const info = IOT_PIN_INFO[def.text] || { name: def.text, fungsi: '', nilai: () => '-' };
        const world = new THREE.Vector3();
        def.anchor.getWorldPosition(world);
        world.y -= 0.28;

        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('stroke', def.colorHex);
        line.setAttribute('stroke-width', '1.6');
        line.setAttribute('stroke-opacity', '0.9');
        const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        dot.setAttribute('r', '4');
        dot.setAttribute('fill', def.colorHex);
        dot.setAttribute('stroke', '#0f172a');
        dot.setAttribute('stroke-width', '1.5');
        iotPinSvg.appendChild(line);
        iotPinSvg.appendChild(dot);

        const el = document.createElement('div');
        el.style.cssText = `position:absolute;left:0;top:0;pointer-events:auto;cursor:pointer;display:flex;align-items:center;gap:6px;padding:5px 10px;border-radius:10px;background:rgba(15,23,42,0.94);border:1.5px solid ${def.colorHex};color:#fff;font:700 11px/1.15 system-ui,-apple-system,sans-serif;white-space:nowrap;box-shadow:0 2px 10px rgba(0,0,0,0.5);will-change:transform;user-select:none;`;
        el.innerHTML = `<span style="width:9px;height:9px;border-radius:50%;background:${def.colorHex};border:1.5px solid rgba(255,255,255,0.85);flex:none"></span><span>${def.text}</span>`;
        iotPinLayer.appendChild(el);

        const pin = { def, info, world, el, line, dot, labelEl: el.children[1], textNow: def.text, cx: undefined, cy: undefined, hover: false, width: 0, lastSlot: 0, visible: true };
        el.addEventListener('mouseenter', () => { pin.hover = true; });
        el.addEventListener('mouseleave', () => { pin.hover = false; });
        el.addEventListener('click', (ev) => {
            ev.stopPropagation();
            showIoTPinDetail(selectedPin === pin ? null : pin);
        });
        iotPins.push(pin);
    });

    iotPinPanel = document.createElement('div');
    iotPinPanel.style.cssText = 'position:absolute;left:10px;bottom:38px;max-width:min(280px,calc(100% - 20px));padding:10px 12px;border-radius:12px;background:rgba(15,23,42,0.96);border:1.5px solid #38bdf8;color:#fff;font:500 11px/1.4 system-ui,sans-serif;pointer-events:auto;display:none;z-index:25;box-shadow:0 6px 20px rgba(0,0,0,0.55);';
    iotPinLayer.appendChild(iotPinPanel);

    host.appendChild(iotPinLayer);
    setIoTPinsVisible(isLabelsVisible);
}

function setIoTPinsVisible(visible) {
    if (iotPinLayer) iotPinLayer.style.display = visible ? 'block' : 'none';
}

function showIoTPinDetail(pin) {
    selectedPin = pin;
    if (!iotPinPanel) return;
    if (!pin) {
        iotPinPanel.style.display = 'none';
        return;
    }
    iotPinPanel.style.borderColor = pin.def.colorHex;
    iotPinPanel.style.display = 'block';
    refreshIoTPinDetail();
}

function refreshIoTPinDetail() {
    if (!selectedPin || !iotPinPanel) return;
    const { def, info } = selectedPin;
    iotPinPanel.innerHTML = `
        <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start">
            <strong style="font-size:12px">${info.name}</strong>
            <span data-close style="cursor:pointer;font-size:14px;line-height:1;padding:0 2px">✕</span>
        </div>
        <div style="margin-top:4px;color:#cbd5e1">${info.fungsi}</div>
        <div style="margin-top:6px;padding:4px 8px;border-radius:8px;background:rgba(56,189,248,0.15);color:#7dd3fc;font-weight:700">Nilai langsung: ${info.nilai()}</div>`;
    const close = iotPinPanel.querySelector('[data-close]');
    if (close) close.addEventListener('click', () => showIoTPinDetail(null));
}

/** Proyeksi pin ke layar: urutan tetap, slot stabil (hysteresis), posisi dihaluskan agar tidak bergetar. */
function updatePinLayout() {
    if (!iotPinLayer || !camera3D || !isLabelsVisible || !iotPins.length) return;
    const container = document.getElementById('canvas-container-3d');
    if (!container) return;
    const W = container.clientWidth;
    const H = container.clientHeight;
    const cfg = KANDANG_ANIM_CONFIG.pin;
    const narrow = W < 520;
    const labelH = narrow ? 24 : 28;
    const tmp = new THREE.Vector3();
    const base = narrow ? 34 : cfg.labelOffsetPx;
    const step = labelH + cfg.gapPx;
    const slots = [-base, -base - step, -base - step * 2, base - 6, base - 6 + step];

    const projected = [];
    iotPins.forEach((pin) => {
        tmp.copy(pin.world).applyMatrix4(camera3D.matrixWorldInverse);
        const behind = tmp.z >= 0;
        tmp.copy(pin.world).project(camera3D);
        const sx = (tmp.x * 0.5 + 0.5) * W;
        const sy = (-tmp.y * 0.5 + 0.5) * H;
        const visible = !behind && sx > -10 && sx < W + 10 && sy > -10 && sy < H + 10;
        pin.visible = visible;
        const display = visible ? 'flex' : 'none';
        if (pin.el.style.display !== display) {
            pin.el.style.display = display;
            pin.line.style.display = visible ? 'block' : 'none';
            pin.dot.style.display = visible ? 'block' : 'none';
            pin.cx = undefined;
        }
        if (!visible) return;

        const text = narrow && pin.info.short ? pin.info.short : pin.def.text;
        if (pin.textNow !== text) {
            pin.labelEl.textContent = text;
            pin.textNow = text;
            pin.width = 0;
        }
        if (!pin.width) pin.width = pin.el.offsetWidth || 110;
        pin.el.style.fontSize = narrow ? '10px' : '11px';
        projected.push({ pin, sx, sy });
    });

    const placed = [];
    projected.forEach(({ pin, sx, sy }) => {
        const w = pin.width;
        const tx = Math.max(w / 2 + 4, Math.min(W - w / 2 - 4, sx));
        const clamp = (y) => Math.max(labelH / 2 + 4, Math.min(H - labelH / 2 - 4, y));
        const clashes = (y) => placed.some((p) => Math.abs(tx - p.cx) < (w + p.w) / 2 + cfg.gapPx && Math.abs(y - p.cy) < labelH + cfg.gapPx);

        let slot = pin.lastSlot;
        let ty = clamp(sy + slots[slot]);
        if (clashes(ty)) {
            slot = -1;
            for (let si = 0; si < slots.length; si++) {
                const candidate = clamp(sy + slots[si]);
                if (!clashes(candidate)) {
                    slot = si;
                    ty = candidate;
                    break;
                }
            }
            if (slot === -1) {
                slot = 0;
                ty = clamp(sy + slots[0]);
            }
            pin.lastSlot = slot;
        }
        placed.push({ cx: tx, cy: ty, w });

        if (pin.cx === undefined) {
            pin.cx = tx;
            pin.cy = ty;
        } else {
            pin.cx += (tx - pin.cx) * 0.22;
            pin.cy += (ty - pin.cy) * 0.22;
            if (Math.abs(tx - pin.cx) < 0.3) pin.cx = tx;
            if (Math.abs(ty - pin.cy) < 0.3) pin.cy = ty;
        }

        const isActive = pin.hover || selectedPin === pin;
        const scale = isActive ? 1.1 : 1;
        pin.el.style.transform = `translate(${Math.round(pin.cx - w / 2)}px, ${Math.round(pin.cy - labelH / 2)}px) scale(${scale})`;
        pin.el.style.zIndex = isActive ? '5' : '1';
        pin.el.style.background = isActive ? pin.def.colorHex : 'rgba(15,23,42,0.94)';
        pin.el.style.color = isActive ? '#0f172a' : '#fff';

        const edgeY = pin.cy < sy ? pin.cy + labelH / 2 : pin.cy - labelH / 2;
        pin.line.setAttribute('x1', sx.toFixed(1));
        pin.line.setAttribute('y1', sy.toFixed(1));
        pin.line.setAttribute('x2', pin.cx.toFixed(1));
        pin.line.setAttribute('y2', edgeY.toFixed(1));
        pin.dot.setAttribute('cx', sx.toFixed(1));
        pin.dot.setAttribute('cy', sy.toFixed(1));
    });

    const nowMs = performance.now();
    if (selectedPin && nowMs - lastPinDetailRefresh > cfg.detailRefreshMs) {
        lastPinDetailRefresh = nowMs;
        refreshIoTPinDetail();
    }
}
/** Buat tekstur prosedural ringan berbasis Canvas 2D (0 MB unduhan eksternal). */
function createFarmCanvasTexture(size, drawFn, repeatX = 1, repeatY = 1, clampEdge = false) {
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    let seed = 1337 + size;
    const rnd = () => {
        seed = (seed * 1664525 + 1013904223) >>> 0;
        return seed / 4294967296;
    };
    drawFn(ctx, size, rnd);
    const tex = new THREE.CanvasTexture(canvas);
    tex.wrapS = clampEdge ? THREE.ClampToEdgeWrapping : THREE.RepeatWrapping;
    tex.wrapT = clampEdge ? THREE.ClampToEdgeWrapping : THREE.RepeatWrapping;
    tex.repeat.set(repeatX, repeatY);
    if (renderer3D && renderer3D.capabilities) {
        tex.anisotropy = Math.min(8, renderer3D.capabilities.getMaxAnisotropy());
    }
    return tex;
}

/** Fungsi topografi kontinu lembah peternakan: rata di dalam kompleks kandang, bergelombang mulus ke perbukitan & gunung. */
function farmTerrainHeightAt(x, z) {
    const dx = Math.max(0, Math.max(-48.0 - x, x - 26.5));
    const dz = Math.max(0, Math.max(-42.5 - z, z - 22.5));
    const distOut = Math.hypot(dx, dz);
    if (distOut <= 0.001) {
        return -0.09;
    }
    const tBlend = Math.min(1, distOut / 38.0);
    const smooth = tBlend * tBlend * (3 - 2 * tBlend);
    const ang = Math.atan2(z, x);
    const r = Math.hypot(x, z);

    // Lembah pertanian landai alami (tidak curam menutup langit)
    const roll =
        1.8 * Math.sin(x * 0.035 + 0.6) * Math.cos(z * 0.032 - 0.4) +
        1.4 * Math.sin((x - z) * 0.022 + 1.1) +
        2.1 * Math.max(0, Math.sin(ang * 2.0 + 2.15)) * Math.min(1, distOut / 65.0);

    // Siluet perbukitan & Gunung tropis jauh di cakrawala (R = 105..175m)
    const farT = Math.max(0, Math.min(1, (r - 96.0) / 78.0));
    const farSmooth = farT * farT * (3 - 2 * farT);
    const mountainBell = Math.exp(-Math.pow(((ang + 1.92) / 0.42), 2)) * 16.5
        + Math.exp(-Math.pow(((ang + 0.95) / 0.55), 2)) * 8.2;
    const ridge = (5.8 + 3.1 * Math.sin(ang * 3.0 - 0.5) + 1.9 * Math.cos(ang * 5.0 + 0.9) + mountainBell) * farSmooth;

    return -0.09 + smooth * (Math.max(0.08, roll + 1.5 * Math.min(1, distOut / 50.0)) + ridge);
}

/** Buat menara Silo Pakan Galvanis realistis (pelat bergelombang, tangga inspeksi bersangkar, corong kerucut, & pipa ulir auger). */
function createRealisticFeedSilo(sx, sz, mats, withAuger = true) {
    const g = new THREE.Group();
    g.position.set(sx, -0.02, sz);

    // Skirt bayangan tanah (AO) + pondasi beton bertulang
    if (mats.aoSkirtMat) {
        const ao = new THREE.Mesh(new THREE.PlaneGeometry(4.1, 4.1), mats.aoSkirtMat);
        ao.rotation.x = -Math.PI / 2;
        ao.position.y = -0.058;
        ao.renderOrder = -1;
        g.add(ao);
    }
    const pad = new THREE.Mesh(new THREE.BoxGeometry(2.85, 0.16, 2.85), mats.kerbMat);
    pad.position.y = 0.04;
    pad.receiveShadow = true;
    g.add(pad);

    const r = 1.08;
    const body = new THREE.Mesh(new THREE.CylinderGeometry(r, r, 2.95, 24), mats.siloSheetMat);
    body.position.y = 3.28;
    body.castShadow = true;
    body.receiveShadow = true;
    g.add(body);

    [1.85, 2.55, 3.25, 3.95, 4.65].forEach((ry, idx) => {
        const ring = new THREE.Mesh(
            new THREE.TorusGeometry(r + 0.016, 0.020, 6, 28),
            idx === 2 ? mats.siloAccentMat : mats.siloDarkMat
        );
        ring.rotation.x = Math.PI / 2;
        ring.position.y = ry;
        g.add(ring);
    });

    const roofCone = new THREE.Mesh(new THREE.ConeGeometry(r + 0.07, 0.96, 24), mats.siloGalvMat);
    roofCone.position.y = 5.23;
    roofCone.castShadow = true;
    g.add(roofCone);

    const cap = new THREE.Mesh(new THREE.CylinderGeometry(0.19, 0.23, 0.24, 14), mats.siloAccentMat);
    cap.position.y = 5.78;
    g.add(cap);

    // Pipa pengisian tiup (fill pipe) & tangga inspeksi samping
    const fillPipe = new THREE.Mesh(new THREE.CylinderGeometry(0.045, 0.045, 5.1, 8), mats.siloGalvMat);
    fillPipe.position.set(-r - 0.09, 2.95, 0);
    g.add(fillPipe);

    [-0.14, 0.14].forEach((lz) => {
        const rail = new THREE.Mesh(new THREE.BoxGeometry(0.025, 4.25, 0.025), mats.siloDarkMat);
        rail.position.set(0, 2.65, -r - 0.11 + lz * 0.02);
        rail.position.x = lz;
        g.add(rail);
    });
    for (let ry = 0.85; ry <= 4.65; ry += 0.34) {
        const rung = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.02, 0.02), mats.siloDarkMat);
        rung.position.set(0, ry, -r - 0.11);
        g.add(rung);
    }
    for (let cy = 2.2; cy <= 4.6; cy += 0.8) {
        const hoop = new THREE.Mesh(new THREE.TorusGeometry(0.32, 0.015, 5, 14, Math.PI * 1.15), mats.siloDarkMat);
        hoop.rotation.x = Math.PI / 2;
        hoop.rotation.z = -Math.PI * 0.075;
        hoop.position.set(0, cy, -r - 0.12);
        g.add(hoop);
    }

    const hopperCone = new THREE.Mesh(new THREE.CylinderGeometry(r, 0.22, 1.18, 24), mats.siloGalvMat);
    hopperCone.position.y = 1.22;
    hopperCone.castShadow = true;
    g.add(hopperCone);

    const boot = new THREE.Mesh(new THREE.BoxGeometry(0.44, 0.36, 0.44), mats.siloDarkMat);
    boot.position.y = 0.52;
    g.add(boot);

    for (let i = 0; i < 4; i += 1) {
        const ang = (Math.PI / 4) + (i * Math.PI) / 2;
        const lx = Math.cos(ang) * (r * 0.88);
        const lz = Math.sin(ang) * (r * 0.88);
        const leg = new THREE.Mesh(new THREE.BoxGeometry(0.11, 2.08, 0.11), mats.siloDarkMat);
        leg.position.set(lx, 1.08, lz);
        leg.castShadow = true;
        g.add(leg);
        const brace = new THREE.Mesh(new THREE.BoxGeometry(0.04, 0.04, r * 1.22), mats.siloDarkMat);
        brace.position.set(lx * 0.5, 0.72, lz * 0.5);
        brace.rotation.y = -ang;
        g.add(brace);
    }

    if (withAuger) {
        // Pipa ulir konveyor (auger) dari sepatu silo ke ujung pangkal rel pakan (-9.75, 2.68, -0.42 relatif kandang)
        const p1 = new THREE.Vector3(0, 0.56, 0);
        const p2 = new THREE.Vector3(2.60, 2.68, 1.63);
        const dir = p2.clone().sub(p1);
        const auger = new THREE.Mesh(new THREE.CylinderGeometry(0.068, 0.068, dir.length(), 10), mats.siloGalvMat);
        auger.position.copy(p1).addScaledVector(dir, 0.5);
        auger.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), dir.clone().normalize());
        auger.castShadow = true;
        g.add(auger);
    }
    return g;
}

/**
 * Bangun lingkungan fotorealistis kawasan peternakan ayam petelur asli Indonesia (0 MB aset eksternal):
 * - Kubah langit pagi tropis dengan awan cumulus berlapis volumetrik (GLSL)
 * - Topografi lembah 3D kontinu menyatu tanpa sambungan (rumput detail + petak sawah/ladang + perbukitan hutan & gunung)
 * - Transisi bayangan tanah (Contact AO decals), jalan makadam beraspal, selokan drainase, & tiang listrik PLN berkabel
 * - Pohon peneduh botani bercabang asli (alpha-cutout daun majemuk + bayangan daun tembus cahaya), rumpun pohon pisang, & rumput liar 3D.
 */
function buildFarmEnvironment3D() {
    const envGroup = new THREE.Group();
    envGroup.name = 'FarmEnvironment3D';

    // 1. Kubah Langit Pagi Tropis + Awan Cumulus Volumetrik Berlapis
    farmSkyUniforms = {
        uTime: { value: 0 },
        uSunDir: { value: new THREE.Vector3(16, 18.5, 11.5).normalize() }
    };
    const skyMat = new THREE.ShaderMaterial({
        uniforms: farmSkyUniforms,
        side: THREE.BackSide,
        depthWrite: false,
        fog: false,
        vertexShader: `
            varying vec3 vWorldDir;
            void main() {
                vec4 wp = modelMatrix * vec4(position, 1.0);
                vWorldDir = wp.xyz;
                gl_Position = projectionMatrix * viewMatrix * wp;
            }
        `,
        fragmentShader: `
            uniform float uTime;
            uniform vec3 uSunDir;
            varying vec3 vWorldDir;
            float rkHash(vec2 p) {
                return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453123);
            }
            float rkNoise(vec2 p) {
                vec2 i = floor(p);
                vec2 f = fract(p);
                vec2 u = f * f * (3.0 - 2.0 * f);
                return mix(
                    mix(rkHash(i), rkHash(i + vec2(1.0, 0.0)), u.x),
                    mix(rkHash(i + vec2(0.0, 1.0)), rkHash(i + vec2(1.0, 1.0)), u.x),
                    u.y
                );
            }
            float rkFbm(vec2 p) {
                float v = 0.0;
                v += 0.50 * rkNoise(p);
                v += 0.25 * rkNoise(p * 2.03 + vec2(4.2, 1.9));
                v += 0.13 * rkNoise(p * 4.11 - vec2(2.7, 5.1));
                v += 0.07 * rkNoise(p * 8.23 + vec2(1.3, 3.4));
                v += 0.05 * rkNoise(p * 16.1);
                return v;
            }
            void main() {
                vec3 d = normalize(vWorldDir);
                float h = clamp(d.y, 0.0, 1.0);
                vec3 zenith = vec3(0.11, 0.35, 0.72);
                vec3 midSky = vec3(0.35, 0.62, 0.88);
                vec3 horizon = vec3(0.80, 0.89, 0.95);
                vec3 col = mix(horizon, midSky, smoothstep(0.0, 0.26, h));
                col = mix(col, zenith, smoothstep(0.22, 0.86, h));

                vec3 sunN = normalize(uSunDir);
                float sunDot = max(dot(d, sunN), 0.0);
                col += vec3(1.0, 0.91, 0.68) * pow(sunDot, 7.0) * 0.20;
                col += vec3(1.0, 0.97, 0.88) * smoothstep(0.9960, 0.9995, sunDot) * 0.75;

                if (d.y > 0.015) {
                    vec2 uv = d.xz / (d.y + 0.15) * 1.18 + vec2(uTime * 0.0055, uTime * 0.0022);
                    float base = rkFbm(uv);
                    float detail = rkFbm(uv * 2.7 + vec2(1.7, -2.3));
                    float density = base * 0.78 + detail * 0.22;
                    float cloudMask = smoothstep(0.47, 0.70, density)
                        * smoothstep(0.015, 0.14, d.y)
                        * (1.0 - smoothstep(0.76, 0.98, d.y));
                    float sunSample = rkFbm(uv + normalize(sunN.xz) * 0.11);
                    float selfShadow = clamp((density - sunSample) * 3.1 + 0.36, 0.0, 1.0);
                    vec3 cloudShadow = vec3(0.57, 0.67, 0.79);
                    vec3 cloudMid = vec3(0.86, 0.90, 0.95);
                    vec3 cloudSun = vec3(1.03, 1.01, 0.97);
                    vec3 cCol = mix(cloudSun, cloudMid, selfShadow);
                    cCol = mix(cCol, cloudShadow, smoothstep(0.56, 0.82, density) * 0.55);
                    cCol += vec3(1.0, 0.92, 0.74) * pow(sunDot, 5.0) * (1.0 - selfShadow) * 0.28;
                    col = mix(col, cCol, cloudMask * 0.88);
                }
                // Kabut atmosferik tipis di kaki cakrawala
                float haze = exp(-abs(d.y) * 14.0);
                col = mix(col, vec3(0.82, 0.90, 0.94), haze * 0.38);
                gl_FragColor = vec4(col, 1.0);
            }
        `
    });
    const skyDome = new THREE.Mesh(new THREE.SphereGeometry(255, 36, 22), skyMat);
    skyDome.renderOrder = -10;
    envGroup.add(skyDome);

    // 2. Medan Lembah & Padang Rumput 3D Kontinu (350m x 350m, 144x144 grid, vertex color AO + petak ladang + hutan bukit)
    const grassTex = createFarmCanvasTexture(1024, (ctx, S, rnd) => {
        ctx.fillStyle = '#4a6b2c';
        ctx.fillRect(0, 0, S, S);
        for (let i = 0; i < 680; i += 1) {
            const kind = rnd();
            ctx.fillStyle = kind < 0.42
                ? 'rgba(54, 84, 30, 0.34)'
                : (kind < 0.78 ? 'rgba(92, 118, 46, 0.28)' : 'rgba(98, 80, 48, 0.22)');
            ctx.beginPath();
            ctx.arc(rnd() * S, rnd() * S, 10 + rnd() * 38, 0, Math.PI * 2);
            ctx.fill();
        }
        const bladeCols = ['#2f4d1b', '#3a5d22', '#466c2a', '#537a32', '#35541e', '#628439', '#4d672b'];
        for (let i = 0; i < 26000; i += 1) {
            ctx.fillStyle = bladeCols[Math.floor(rnd() * bladeCols.length)];
            const bx = rnd() * S;
            const by = rnd() * S;
            ctx.fillRect(bx, by, 1.2 + rnd() * 2.2, 2.5 + rnd() * 5.5);
        }
        // Bintik cengkeh/bunga rumput kecil alami
        for (let i = 0; i < 480; i += 1) {
            ctx.fillStyle = rnd() < 0.65 ? 'rgba(214, 220, 175, 0.35)' : 'rgba(232, 204, 120, 0.32)';
            ctx.fillRect(rnd() * S, rnd() * S, 2, 2);
        }
    }, 34, 34);

    const grassBumpTex = createFarmCanvasTexture(512, (ctx, S, rnd) => {
        ctx.fillStyle = '#808080';
        ctx.fillRect(0, 0, S, S);
        for (let i = 0; i < 14000; i += 1) {
            const v = 90 + Math.floor(rnd() * 85);
            ctx.fillStyle = 'rgb(' + v + ',' + v + ',' + v + ')';
            ctx.fillRect(rnd() * S, rnd() * S, 2, 3 + rnd() * 4);
        }
    }, 34, 34);

    // Daftar posisi pusat keempat unit kandang kembar: Unit 1 (Utama), Unit 2, Unit 3, dan Unit 4 (Seberang Jalan)
    const coopCenters = [
        [0, 0],
        [0, -13.6],
        [0, -27.2],
        [-32.8, -13.6]
    ];

    const terrainGeo = new THREE.PlaneGeometry(350, 350, 144, 144);
    terrainGeo.rotateX(-Math.PI / 2);
    const tPos = terrainGeo.attributes.position;
    const tCols = new Float32Array(tPos.count * 3);
    const tmpCol = new THREE.Color();

    for (let i = 0; i < tPos.count; i += 1) {
        const vx = tPos.getX(i);
        const vz = tPos.getZ(i);
        const vy = farmTerrainHeightAt(vx, vz);
        tPos.setY(i, vy);

        // Variasi warna makro alami: rumput lembap, pematang sawah/ladang di lembah, hutan bukit, & kabut gunung
        const macro = 0.5 + 0.5 * Math.sin(vx * 0.075 + 0.4) * Math.cos(vz * 0.068 - 0.8);
        let rC = 0.72 + 0.14 * macro;
        let gC = 0.78 + 0.12 * macro;
        let bC = 0.66 + 0.08 * macro;

        const rDist = Math.hypot(vx, vz);
        if (vy > 0.25) {
            // Efek petak sawah / kebun bertingkat di lereng tengah
            const paddyBand = Math.sin(vx * 0.16 + Math.sin(vz * 0.05) * 1.5) * Math.cos(vz * 0.14);
            if (vy < 9.5) {
                const fMix = Math.min(1, (vy - 0.25) / 4.0) * 0.45;
                if (paddyBand > 0.15) {
                    rC = rC * (1 - fMix) + 0.82 * fMix;
                    gC = gC * (1 - fMix) + 0.90 * fMix;
                    bC = bC * (1 - fMix) + 0.58 * fMix;
                } else if (Math.abs(paddyBand) < 0.06) {
                    rC *= 0.72; gC *= 0.70; bC *= 0.64; // Pematang tanah/semak
                }
            }
            // Hutan lereng bukit lebih teduh & gunung jauh kebiruan alami
            const forestMix = Math.max(0, Math.min(1, (vy - 5.5) / 11.0));
            rC = rC * (1 - forestMix) + 0.46 * forestMix;
            gC = gC * (1 - forestMix) + 0.58 * forestMix;
            bC = bC * (1 - forestMix) + 0.44 * forestMix;

            const mistMix = Math.max(0, Math.min(1, (rDist - 88.0) / 68.0));
            rC = rC * (1 - mistMix) + 0.58 * mistMix;
            gC = gC * (1 - mistMix) + 0.69 * mistMix;
            bC = bC * (1 - mistMix) + 0.74 * mistMix;
        } else {
            // Contact AO tanah lembap di sekeliling fondasi keempat kandang & tepi jalan
            for (let c = 0; c < coopCenters.length; c += 1) {
                const cdx = Math.max(0, Math.abs(vx - coopCenters[c][0]) - 11.3);
                const cdz = Math.max(0, Math.abs(vz - coopCenters[c][1]) - 5.0);
                const cDist = Math.hypot(cdx, cdz);
                if (cDist < 2.2) {
                    const ao = 0.66 + 0.34 * (cDist / 2.2);
                    rC *= ao; gC *= ao * 0.97; bC *= ao * 0.90;
                }
            }
        }
        tmpCol.setRGB(rC, gC, bC);
        tCols[i * 3] = tmpCol.r;
        tCols[i * 3 + 1] = tmpCol.g;
        tCols[i * 3 + 2] = tmpCol.b;
    }
    terrainGeo.setAttribute('color', new THREE.BufferAttribute(tCols, 3));
    terrainGeo.computeVertexNormals();

    const terrainMesh = new THREE.Mesh(
        terrainGeo,
        new THREE.MeshStandardMaterial({
            map: grassTex,
            bumpMap: grassBumpTex,
            bumpScale: 0.028,
            vertexColors: true,
            roughness: 0.96,
            metalness: 0.0
        })
    );
    terrainMesh.receiveShadow = true;
    envGroup.add(terrainMesh);

    // Tekstur Skirt Gradasi Tanah/Kerikil & Contact AO (menghilangkan batas kaku beton-ke-rumput)
    const aoSkirtFrameTex = createFarmCanvasTexture(256, (ctx, S, rnd) => {
        ctx.clearRect(0, 0, S, S);
        const pad = 28;
        for (let p = 0; p < pad; p += 1) {
            const t = p / pad;
            const alpha = (t * t) * 0.62;
            ctx.strokeStyle = 'rgba(42, 36, 25, ' + alpha.toFixed(3) + ')';
            ctx.lineWidth = 2;
            ctx.strokeRect(p, p, S - p * 2, S - p * 2);
        }
        ctx.fillStyle = 'rgba(40, 35, 25, 0.62)';
        ctx.fillRect(pad, pad, S - pad * 2, S - pad * 2);
        for (let i = 0; i < 900; i += 1) {
            const m = 6 + rnd() * (S - 12);
            const n = 6 + rnd() * (S - 12);
            const edgeD = Math.min(m, n, S - m, S - n);
            if (edgeD < pad + 4) {
                ctx.fillStyle = 'rgba(72, 62, 44, ' + (0.35 * (edgeD / (pad + 4))).toFixed(2) + ')';
                ctx.fillRect(m, n, 2, 2);
            }
        }
    }, 1, 1, true);
    const aoSkirtMat = new THREE.MeshBasicMaterial({
        map: aoSkirtFrameTex,
        transparent: true,
        depthWrite: false,
        polygonOffset: true,
        polygonOffsetFactor: -1,
        polygonOffsetUnits: -1
    });

    // 3. Jalan Operasional Aspal/Makadam Aus + Bahu Kerikil + Jalur Penghubung Antar Kandang
    const roadTex = createFarmCanvasTexture(512, (ctx, S, rnd) => {
        ctx.fillStyle = '#535652';
        ctx.fillRect(0, 0, S, S);
        for (let i = 0; i < 12000; i += 1) {
            const v = 62 + Math.floor(rnd() * 48);
            ctx.fillStyle = 'rgb(' + v + ',' + (v - 1) + ',' + (v - 4) + ')';
            ctx.fillRect(rnd() * S, rnd() * S, 1.5 + rnd() * 2.8, 1.5 + rnd() * 2.8);
        }
        // Jejak ban truk pakan & gradasi debu bahu jalan
        const gradL = ctx.createLinearGradient(0, 0, S, 0);
        gradL.addColorStop(0.0, 'rgba(78, 66, 46, 0.55)');
        gradL.addColorStop(0.10, 'rgba(52, 54, 50, 0.0)');
        gradL.addColorStop(0.26, 'rgba(30, 32, 30, 0.24)');
        gradL.addColorStop(0.42, 'rgba(60, 62, 58, 0.05)');
        gradL.addColorStop(0.58, 'rgba(60, 62, 58, 0.05)');
        gradL.addColorStop(0.74, 'rgba(30, 32, 30, 0.24)');
        gradL.addColorStop(0.90, 'rgba(52, 54, 50, 0.0)');
        gradL.addColorStop(1.0, 'rgba(78, 66, 46, 0.55)');
        ctx.fillStyle = gradL;
        ctx.fillRect(0, 0, S, S);
    }, 1, 24);
    const roadMat = new THREE.MeshStandardMaterial({
        map: roadTex,
        bumpMap: roadTex,
        bumpScale: 0.022,
        roughness: 0.92,
        metalness: 0.02
    });

    const concreteWeatheredTex = createFarmCanvasTexture(512, (ctx, S, rnd) => {
        ctx.fillStyle = '#9b9486';
        ctx.fillRect(0, 0, S, S);
        for (let i = 0; i < 9000; i += 1) {
            const d = 128 + Math.floor(rnd() * 42);
            ctx.fillStyle = 'rgb(' + d + ',' + (d - 4) + ',' + (d - 12) + ')';
            ctx.fillRect(rnd() * S, rnd() * S, 2 + rnd() * 2, 2 + rnd() * 2);
        }
        for (let i = 0; i < 85; i += 1) {
            ctx.fillStyle = rnd() < 0.5 ? 'rgba(68, 64, 54, 0.10)' : 'rgba(175, 170, 156, 0.08)';
            ctx.beginPath();
            ctx.arc(rnd() * S, rnd() * S, 10 + rnd() * 28, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.strokeStyle = 'rgba(58, 53, 45, 0.36)';
        ctx.lineWidth = 2.5;
        ctx.strokeRect(1.5, 1.5, S - 3, S - 3);
    }, 6, 3);

    const apronMat = new THREE.MeshStandardMaterial({
        map: concreteWeatheredTex,
        bumpMap: concreteWeatheredTex,
        bumpScale: 0.015,
        roughness: 0.91,
        metalness: 0.02
    });
    const kerbMat = new THREE.MeshStandardMaterial({ color: 0x918b7e, roughness: 0.89, metalness: 0.02 });

    // Jalan utama truk pakan di sisi barat (X = -16.2)
    const roadSkirt = new THREE.Mesh(new THREE.PlaneGeometry(6.4, 128), aoSkirtMat);
    roadSkirt.rotation.x = -Math.PI / 2;
    roadSkirt.position.set(-16.2, -0.084, -8);
    envGroup.add(roadSkirt);

    const mainRoad = new THREE.Mesh(new THREE.PlaneGeometry(4.8, 126), roadMat);
    mainRoad.rotation.x = -Math.PI / 2;
    mainRoad.position.set(-16.2, -0.072, -8);
    mainRoad.receiveShadow = true;
    envGroup.add(mainRoad);

    [-18.68, -13.72].forEach((kx) => {
        const kerb = new THREE.Mesh(new THREE.BoxGeometry(0.16, 0.08, 126), kerbMat);
        kerb.position.set(kx, -0.056, -8);
        kerb.receiveShadow = true;
        envGroup.add(kerb);
    });

    // Jalur servis beton penghubung antar pintu barat silo (X = -11.9) & koridor panen telur timur (X = +12.5)
    const westServiceWalk = new THREE.Mesh(new THREE.BoxGeometry(3.4, 0.08, 36.8), apronMat);
    westServiceWalk.position.set(-11.9, -0.070, -13.6);
    westServiceWalk.receiveShadow = true;
    envGroup.add(westServiceWalk);

    const eastHarvestWalk = new THREE.Mesh(new THREE.BoxGeometry(3.8, 0.08, 35.8), apronMat);
    eastHarvestWalk.position.set(12.6, -0.070, -13.2);
    eastHarvestWalk.receiveShadow = true;
    envGroup.add(eastHarvestWalk);

    // Akses beton penghubung jalan utama ke Kandang Unit 4 di seberang barat (Z = -13.6)
    const westCoopApron = new THREE.Mesh(new THREE.BoxGeometry(4.2, 0.08, 8.6), apronMat);
    westCoopApron.position.set(-20.4, -0.070, -13.6);
    westCoopApron.receiveShadow = true;
    envGroup.add(westCoopApron);

    // Skirt AO + Fondasi plint beton + Parit Drainase Kandang 1 (Utama)
    const mainAoSkirt = new THREE.Mesh(new THREE.PlaneGeometry(24.4, 11.6), aoSkirtMat);
    mainAoSkirt.rotation.x = -Math.PI / 2;
    mainAoSkirt.position.set(0, -0.083, 0);
    envGroup.add(mainAoSkirt);

    const plinth = new THREE.Mesh(new THREE.BoxGeometry(22.6, 0.11, 9.9), kerbMat);
    plinth.position.set(0, -0.058, 0);
    plinth.receiveShadow = true;
    envGroup.add(plinth);

    const ditchWallMat = new THREE.MeshStandardMaterial({ color: 0x8d9298, roughness: 0.88 });
    const ditchWaterMat = new THREE.MeshStandardMaterial({ color: 0x465e5d, roughness: 0.26, metalness: 0.22 });
    [-4.52, 4.52].forEach((dz) => {
        const ditchBottom = new THREE.Mesh(new THREE.BoxGeometry(22.4, 0.04, 0.42), ditchWaterMat);
        ditchBottom.position.set(0, -0.082, dz);
        envGroup.add(ditchBottom);
        [-0.22, 0.22].forEach((off) => {
            const lip = new THREE.Mesh(new THREE.BoxGeometry(22.4, 0.09, 0.07), ditchWallMat);
            lip.position.set(0, -0.055, dz + off);
            envGroup.add(lip);
        });
    });

    // 4. Material & Silo Pakan Galvanis Kandang Utama + Palet Karung Pakan + Troli Panen Telur
    const siloSheetTex = createFarmCanvasTexture(256, (ctx, S) => {
        ctx.fillStyle = '#cfd6de';
        ctx.fillRect(0, 0, S, S);
        for (let y = 0; y < S; y += 8) {
            ctx.fillStyle = (y % 16 === 0) ? 'rgba(255,255,255,0.16)' : 'rgba(30,41,59,0.14)';
            ctx.fillRect(0, y, S, 4);
        }
        ctx.strokeStyle = 'rgba(71,85,105,0.22)';
        ctx.lineWidth = 1.5;
        for (let x = 0; x < S; x += 64) {
            ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, S); ctx.stroke();
        }
    }, 3, 4);
    const siloSharedMats = {
        siloSheetMat: new THREE.MeshStandardMaterial({
            map: siloSheetTex,
            bumpMap: siloSheetTex,
            bumpScale: 0.015,
            metalness: 0.66,
            roughness: 0.30
        }),
        siloGalvMat: new THREE.MeshStandardMaterial({ color: 0xd4dbe3, metalness: 0.68, roughness: 0.28 }),
        siloDarkMat: new THREE.MeshStandardMaterial({ color: 0x3f4c5c, metalness: 0.56, roughness: 0.38 }),
        siloAccentMat: new THREE.MeshStandardMaterial({ color: 0xe5940a, roughness: 0.42, metalness: 0.14 }),
        kerbMat,
        aoSkirtMat
    };
    envGroup.userData.sharedMats = {
        apronMat,
        kerbMat,
        aoSkirtMat,
        ditchWallMat,
        ditchWaterMat,
        siloSharedMats
    };

    envGroup.add(createRealisticFeedSilo(-12.35, -2.05, siloSharedMats, true));

    // Palet Kayu & Tumpukan Karung Pakan Konsentrat di Pelataran Barat Kandang 1
    const palletGroup = new THREE.Group();
    palletGroup.position.set(-12.25, -0.02, 2.35);
    const woodMat = new THREE.MeshStandardMaterial({ color: 0x7c5836, roughness: 0.88 });
    const sackMatA = new THREE.MeshStandardMaterial({ color: 0xd8cdb8, roughness: 0.85 });
    const sackMatB = new THREE.MeshStandardMaterial({ color: 0xcbbba0, roughness: 0.87 });
    const palletBase = new THREE.Mesh(new THREE.BoxGeometry(1.18, 0.10, 1.02), woodMat);
    palletBase.position.y = 0.05;
    palletBase.castShadow = true;
    palletGroup.add(palletBase);
    for (let layer = 0; layer < 4; layer += 1) {
        for (let s = 0; s < 2; s += 1) {
            const sack = new THREE.Mesh(
                new THREE.BoxGeometry(layer % 2 === 0 ? 0.50 : 0.42, 0.15, layer % 2 === 0 ? 0.42 : 0.50),
                (layer + s) % 2 === 0 ? sackMatA : sackMatB
            );
            sack.scale.set(1, 0.88, 1);
            const off = (s === 0 ? -0.24 : 0.24);
            sack.position.set(
                layer % 2 === 0 ? off : 0,
                0.16 + layer * 0.135,
                layer % 2 === 0 ? 0 : off
            );
            sack.rotation.y = (layer * 0.06) - 0.05;
            sack.castShadow = true;
            palletGroup.add(sack);
        }
    }
    envGroup.add(palletGroup);

    // Troli Panen & Rak Krat Telur di Pelataran Timur (+X = +12.45)
    const harvestCart = new THREE.Group();
    harvestCart.position.set(12.45, -0.02, 1.35);
    const cartDeck = new THREE.Mesh(
        new THREE.BoxGeometry(1.15, 0.08, 0.78),
        new THREE.MeshStandardMaterial({ color: 0x115e59, roughness: 0.48, metalness: 0.32 })
    );
    cartDeck.position.y = 0.28;
    cartDeck.castShadow = true;
    harvestCart.add(cartDeck);
    const handleBar = new THREE.Mesh(new THREE.BoxGeometry(0.05, 0.68, 0.72), siloSharedMats.siloDarkMat);
    handleBar.position.set(0.55, 0.62, 0);
    harvestCart.add(handleBar);
    [[-0.42, -0.32], [-0.42, 0.32], [0.42, -0.32], [0.42, 0.32]].forEach(([wx, wz]) => {
        const wh = new THREE.Mesh(new THREE.CylinderGeometry(0.11, 0.11, 0.07, 12), siloSharedMats.siloDarkMat);
        wh.rotation.x = Math.PI / 2;
        wh.position.set(wx, 0.11, wz);
        harvestCart.add(wh);
    });
    const trayStackMat = new THREE.MeshStandardMaterial({ color: 0xc26a1b, roughness: 0.78 });
    const eggCrateBrown = new THREE.MeshStandardMaterial({ color: 0xbc7c4e, roughness: 0.58 });
    [[-0.24, -0.16], [-0.24, 0.18], [0.18, -0.16], [0.18, 0.18]].forEach(([tx, tz], idx) => {
        const stackH = 0.26 + (idx % 2) * 0.12;
        const stack = new THREE.Mesh(new THREE.BoxGeometry(0.34, stackH, 0.30), trayStackMat);
        stack.position.set(tx, 0.32 + stackH / 2, tz);
        stack.castShadow = true;
        harvestCart.add(stack);
        const topEggs = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.04, 0.24), eggCrateBrown);
        topEggs.position.set(tx, 0.33 + stackH, tz);
        harvestCart.add(topEggs);
    });
    envGroup.add(harvestCart);

    // 5. Tiang Listrik Beton PLN + Kabel Udara Melengkung (Catenary) Sepanjang Jalan Barat
    const poleMat = new THREE.MeshStandardMaterial({ color: 0x8d8d88, roughness: 0.90 });
    const wireLineMat = new THREE.LineBasicMaterial({ color: 0x262f38, transparent: true, opacity: 0.72 });
    const poleZs = [16.5, 0.5, -15.5, -31.5];
    const poleX = -19.25;
    poleZs.forEach((pz) => {
        const pg = new THREE.Group();
        pg.position.set(poleX, -0.09, pz);
        const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.10, 0.16, 7.6, 10), poleMat);
        pole.position.y = 3.8;
        pole.castShadow = true;
        pg.add(pole);
        const arm = new THREE.Mesh(new THREE.BoxGeometry(1.35, 0.08, 0.08), siloSharedMats.siloDarkMat);
        arm.position.y = 7.25;
        pg.add(arm);
        [-0.55, 0, 0.55].forEach((ix) => {
            const ins = new THREE.Mesh(new THREE.CylinderGeometry(0.035, 0.045, 0.14, 8), siloSharedMats.siloGalvMat);
            ins.position.set(ix, 7.35, 0);
            pg.add(ins);
        });
        envGroup.add(pg);
    });
    [-0.55, 0, 0.55].forEach((ix) => {
        const pts = [];
        for (let seg = 0; seg < poleZs.length - 1; seg += 1) {
            const zA = poleZs[seg];
            const zB = poleZs[seg + 1];
            for (let s = 0; s <= 12; s += 1) {
                const u = s / 12;
                const sag = Math.sin(u * Math.PI) * 0.42;
                pts.push(new THREE.Vector3(poleX + ix, 7.32 - sag, zA + (zB - zA) * u));
            }
        }
        const lineGeo = new THREE.BufferGeometry().setFromPoints(pts);
        envGroup.add(new THREE.Line(lineGeo, wireLineMat));
    });
    // Kabel tarikan listrik dari tiang PLN ke pangkal Kandang 1
    const dropPts = [];
    for (let s = 0; s <= 12; s += 1) {
        const u = s / 12;
        const sag = Math.sin(u * Math.PI) * 0.28;
        dropPts.push(new THREE.Vector3(
            poleX + 0.55 + (-9.0 - (poleX + 0.55)) * u,
            7.22 + (3.62 - 7.22) * u - sag,
            0.5 + (-3.5 - 0.5) * u
        ));
    }
    envGroup.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints(dropPts), wireLineMat));

    // 6. Pagar Keliling Kawasan Biosekuriti (mencakup seluruh 4 unit kandang kembar)
    const fenceCoords = [];
    for (let x = -45; x <= 21; x += 3) {
        if (x >= -19.0 && x <= -13.2) {
            continue; // Bukaan gerbang jalan masuk utama
        }
        fenceCoords.push([x, 18.5, 0]);
    }
    for (let x = -45; x <= 21; x += 3) {
        if (x >= -19.0 && x <= -13.2) {
            continue;
        }
        fenceCoords.push([x, -38.5, 0]);
    }
    for (let z = -38.5; z <= 18.5; z += 3) {
        fenceCoords.push([21.5, z, Math.PI / 2]);
        fenceCoords.push([-45.5, z, Math.PI / 2]);
    }
    const postInst = new THREE.InstancedMesh(
        new THREE.BoxGeometry(0.11, 1.48, 0.11),
        new THREE.MeshStandardMaterial({ color: 0x6b7280, metalness: 0.35, roughness: 0.62 }),
        fenceCoords.length
    );
    const railInst = new THREE.InstancedMesh(
        new THREE.BoxGeometry(3.02, 0.055, 0.04),
        new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.48, roughness: 0.42 }),
        fenceCoords.length * 2
    );
    const fDummy = new THREE.Object3D();
    fenceCoords.forEach(([fx, fz, rot], idx) => {
        fDummy.position.set(fx, 0.65, fz);
        fDummy.rotation.set(0, rot, 0);
        fDummy.scale.set(1, 1, 1);
        fDummy.updateMatrix();
        postInst.setMatrixAt(idx, fDummy.matrix);

        const dx = Math.cos(rot) * 1.5;
        const dz = -Math.sin(rot) * 1.5;
        fDummy.position.set(fx + dx, 1.15, fz + dz);
        fDummy.updateMatrix();
        railInst.setMatrixAt(idx * 2, fDummy.matrix);
        fDummy.position.set(fx + dx, 0.58, fz + dz);
        fDummy.updateMatrix();
        railInst.setMatrixAt(idx * 2 + 1, fDummy.matrix);
    });
    postInst.instanceMatrix.needsUpdate = true;
    railInst.instanceMatrix.needsUpdate = true;
    envGroup.add(postInst);
    envGroup.add(railInst);

    // 7. Pohon Peneduh Botani Realistis (Batang Bercabang + Daun Majemuk Alpha-Cutout + Bayangan Dedaunan Asli)
    const barkTex = createFarmCanvasTexture(256, (ctx, S, rnd) => {
        ctx.fillStyle = '#4c3b2b';
        ctx.fillRect(0, 0, S, S);
        for (let i = 0; i < 2400; i += 1) {
            const v = 42 + Math.floor(rnd() * 48);
            ctx.fillStyle = 'rgb(' + (v + 16) + ',' + (v + 4) + ',' + (v - 6) + ')';
            ctx.fillRect(rnd() * S, rnd() * S, 2 + rnd() * 3, 10 + rnd() * 24);
        }
        for (let i = 0; i < 120; i += 1) {
            ctx.fillStyle = 'rgba(78, 92, 58, 0.22)'; // Lumut tipis alami pada kulit kayu tropis
            ctx.beginPath();
            ctx.arc(rnd() * S, rnd() * S, 4 + rnd() * 10, 0, Math.PI * 2);
            ctx.fill();
        }
    }, 2, 4);

    /** Bangun geometri batang pohon bercabang organik (akar melebar + batang utama + 6 dahan penyangga tajuk). */
    function buildRealisticTreeTrunkGeo() {
        const pos = [], nor = [], uv = [], idx = [];
        function addLimbTube(pts, rStart, rEnd, radSegs) {
            const rings = pts.length;
            const baseV = pos.length / 3;
            for (let i = 0; i < rings; i += 1) {
                const t = i / (rings - 1);
                const p = pts[i];
                const tangent = (i < rings - 1 ? pts[i + 1].clone().sub(p) : p.clone().sub(pts[i - 1])).normalize();
                const upRef = Math.abs(tangent.y) > 0.92 ? new THREE.Vector3(1, 0, 0) : new THREE.Vector3(0, 1, 0);
                const sideA = new THREE.Vector3().crossVectors(tangent, upRef).normalize();
                const sideB = new THREE.Vector3().crossVectors(tangent, sideA).normalize();
                const flare = i === 0 ? 1.28 : 1.0;
                const rad = (rStart + (rEnd - rStart) * t) * flare;
                for (let j = 0; j <= radSegs; j += 1) {
                    const a = (j / radSegs) * Math.PI * 2;
                    const ca = Math.cos(a), sa = Math.sin(a);
                    const nx = sideA.x * ca + sideB.x * sa;
                    const ny = sideA.y * ca + sideB.y * sa;
                    const nz = sideA.z * ca + sideB.z * sa;
                    pos.push(p.x + nx * rad, p.y + ny * rad, p.z + nz * rad);
                    nor.push(nx, ny, nz);
                    uv.push(j / radSegs, t * 2.5);
                }
            }
            for (let i = 0; i < rings - 1; i += 1) {
                for (let j = 0; j < radSegs; j += 1) {
                    const a = baseV + i * (radSegs + 1) + j;
                    const b = a + radSegs + 1;
                    idx.push(a, b, a + 1, a + 1, b, b + 1);
                }
            }
        }

        // Batang utama sedikit meliuk alami
        addLimbTube([
            new THREE.Vector3(0, 0.0, 0),
            new THREE.Vector3(0.03, 0.65, -0.02),
            new THREE.Vector3(-0.02, 1.35, 0.03),
            new THREE.Vector3(0.02, 1.98, 0.0),
            new THREE.Vector3(0.0, 2.55, -0.02)
        ], 0.25, 0.11, 8);

        // 6 dahan utama menyebar menopang sub-tajuk daun
        const branches = [
            [[0.01, 1.62, 0.01], [0.42, 2.12, 0.28], [0.88, 2.58, 0.52]],
            [[-0.02, 1.58, 0.02], [-0.46, 2.08, 0.34], [-0.92, 2.52, 0.62]],
            [[0.02, 1.72, -0.02], [0.36, 2.22, -0.44], [0.76, 2.66, -0.82]],
            [[-0.01, 1.68, -0.02], [-0.40, 2.18, -0.40], [-0.82, 2.62, -0.76]],
            [[0.02, 1.95, 0.02], [0.22, 2.58, 0.26], [0.38, 3.12, 0.42]],
            [[-0.01, 2.02, -0.01], [-0.20, 2.65, -0.22], [-0.34, 3.18, -0.36]]
        ];
        branches.forEach((b) => {
            addLimbTube(b.map((p) => new THREE.Vector3(p[0], p[1], p[2])), 0.105, 0.038, 6);
        });

        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
        geo.setAttribute('normal', new THREE.Float32BufferAttribute(nor, 3));
        geo.setAttribute('uv', new THREE.Float32BufferAttribute(uv, 2));
        geo.setIndex(idx);
        return geo;
    }

    // Atlas Ranting & Gugus Daun Tropis 512x512 RGBA (Alpha-Cutout Tajam, Bukan Bola Polos)
    const foliageClusterTex = createFarmCanvasTexture(512, (ctx, S, rnd) => {
        ctx.clearRect(0, 0, S, S);
        // Ranting kayu halus di tengah gugus daun
        ctx.strokeStyle = '#3d2e1e';
        ctx.lineWidth = 5;
        ctx.lineCap = 'round';
        ctx.beginPath();
        ctx.moveTo(S * 0.5, S * 0.88);
        ctx.quadraticCurveTo(S * 0.48, S * 0.55, S * 0.50, S * 0.20);
        ctx.stroke();

        const drawLeaf = (cx, cy, len, wid, ang, colMain, colVein) => {
            ctx.save();
            ctx.translate(cx, cy);
            ctx.rotate(ang);
            ctx.fillStyle = colMain;
            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.quadraticCurveTo(wid, -len * 0.42, 0, -len);
            ctx.quadraticCurveTo(-wid, -len * 0.42, 0, 0);
            ctx.closePath();
            ctx.fill();
            ctx.strokeStyle = colVein;
            ctx.lineWidth = 1.3;
            ctx.beginPath();
            ctx.moveTo(0, -2);
            ctx.lineTo(0, -len * 0.86);
            ctx.stroke();
            ctx.restore();
        };

        // Lapis 1: Daun dalam teduh (hijau zaitun tua untuk kedalaman tajuk)
        const deepCols = ['#1e3614', '#243f18', '#2a481c'];
        for (let i = 0; i < 62; i += 1) {
            const a = rnd() * Math.PI * 2;
            const rad = 18 + rnd() * (S * 0.30);
            const lx = S * 0.5 + Math.cos(a) * rad;
            const ly = S * 0.52 + Math.sin(a) * rad * 0.88;
            drawLeaf(lx, ly, 46 + rnd() * 34, 18 + rnd() * 10, a + Math.PI * 0.5 + (rnd() - 0.5) * 0.8,
                deepCols[Math.floor(rnd() * deepCols.length)], 'rgba(68,102,44,0.35)');
        }
        // Lapis 2: Daun tengah rimbun
        const midCols = ['#2f541f', '#386225', '#426e2b', '#345b22'];
        for (let i = 0; i < 74; i += 1) {
            const a = rnd() * Math.PI * 2;
            const rad = 12 + rnd() * (S * 0.35);
            const lx = S * 0.5 + Math.cos(a) * rad;
            const ly = S * 0.50 + Math.sin(a) * rad * 0.88;
            drawLeaf(lx, ly, 42 + rnd() * 32, 16 + rnd() * 9, a + Math.PI * 0.5 + (rnd() - 0.5) * 0.9,
                midCols[Math.floor(rnd() * midCols.length)], 'rgba(116,158,72,0.42)');
        }
        // Lapis 3: Pucuk daun muda terkena sinar matahari pagi
        const sunCols = ['#528232', '#60913a', '#6ea043', '#4b792d'];
        for (let i = 0; i < 48; i += 1) {
            const a = rnd() * Math.PI * 2;
            const rad = 24 + rnd() * (S * 0.36);
            const lx = S * 0.5 + Math.cos(a) * rad;
            const ly = S * 0.48 + Math.sin(a) * rad * 0.86;
            drawLeaf(lx, ly, 36 + rnd() * 28, 14 + rnd() * 8, a + Math.PI * 0.5 + (rnd() - 0.5) * 0.7,
                sunCols[Math.floor(rnd() * sunCols.length)], 'rgba(168,206,104,0.52)');
        }
    }, 1, 1, true);

    /**
     * Geometri tajuk pohon botani 3D: 126 kartu ranting daun melengkung pada 7 kubah dahan
     * dengan Bent Spherical Normals + Vertex Color Ambient Occlusion agar pencahayaan selembut pohon asli.
     */
    function buildBotanicalCrownGeo() {
        const pos = [], nor = [], uv = [], col = [], idx = [];
        let seed = 9241;
        const rnd = () => {
            seed = (seed * 1664525 + 1013904223) >>> 0;
            return seed / 4294967296;
        };

        const domes = [
            { cx: 0.0,   cy: 3.35, cz: 0.0,   rx: 1.18, ry: 0.92, rz: 1.18, count: 26 },
            { cx: 0.92,  cy: 2.78, cz: 0.56,  rx: 1.02, ry: 0.80, rz: 0.98, count: 18 },
            { cx: -0.96, cy: 2.72, cz: 0.62,  rx: 1.04, ry: 0.80, rz: 0.98, count: 18 },
            { cx: 0.82,  cy: 2.86, cz: -0.84, rx: 1.00, ry: 0.82, rz: 1.00, count: 18 },
            { cx: -0.86, cy: 2.80, cz: -0.78, rx: 1.02, ry: 0.80, rz: 1.00, count: 18 },
            { cx: 0.18,  cy: 2.55, cz: 1.02,  rx: 0.92, ry: 0.72, rz: 0.88, count: 14 },
            { cx: -0.14, cy: 2.60, cz: -1.04, rx: 0.92, ry: 0.72, rz: 0.88, count: 14 }
        ];
        const treeCenter = new THREE.Vector3(0, 2.82, 0);
        const qRot = new THREE.Quaternion();
        const euler = new THREE.Euler();
        const vLocal = new THREE.Vector3();
        const vWorld = new THREE.Vector3();
        const nOut = new THREE.Vector3();
        const nFace = new THREE.Vector3();

        domes.forEach((dm) => {
            const dCenter = new THREE.Vector3(dm.cx, dm.cy, dm.cz);
            for (let k = 0; k < dm.count; k += 1) {
                const isCore = k < 5;
                const u = rnd();
                const v = rnd();
                const theta = u * Math.PI * 2;
                const phi = Math.acos(1 - 2 * v) * 0.82;
                const shell = isCore ? (0.28 + rnd() * 0.35) : (0.68 + rnd() * 0.34);

                const px = dm.cx + Math.sin(phi) * Math.cos(theta) * dm.rx * shell;
                const py = dm.cy + Math.cos(phi) * dm.ry * shell;
                const pz = dm.cz + Math.sin(phi) * Math.sin(theta) * dm.rz * shell;

                const cardSize = (isCore ? 1.18 : 0.92) + rnd() * 0.32;
                const yaw = Math.atan2(px - dm.cx, pz - dm.cz) + (rnd() - 0.5) * 1.25;
                const pitch = -0.35 + (py - dm.cy) * 0.38 + (rnd() - 0.5) * 0.65;
                const roll = (rnd() - 0.5) * 0.95;
                euler.set(pitch, yaw, roll, 'YXZ');
                qRot.setFromEuler(euler);

                nFace.set(0, 0, 1).applyQuaternion(qRot).normalize();
                const baseV = pos.length / 3;

                // Grid 2x2 melengkung ringan (9 verteks per gugus daun agar tampak bervolume dari segala sudut)
                for (let gy = 0; gy <= 2; gy += 1) {
                    for (let gx = 0; gx <= 2; gx += 1) {
                        const su = (gx / 2) - 0.5;
                        const sv = (gy / 2) - 0.5;
                        const archZ = (0.16 - 0.34 * (su * su + sv * sv)) * cardSize;
                        vLocal.set(su * cardSize, sv * cardSize, archZ).applyQuaternion(qRot);
                        vWorld.set(px + vLocal.x, py + vLocal.y, pz + vLocal.z);

                        nOut.copy(vWorld).sub(dCenter).normalize();
                        nOut.lerp(nFace, 0.20).normalize();

                        // Ambient occlusion alami: bagian bawah/dalam tajuk lebih teduh, pucuk atas cerah
                        const hNorm = Math.max(0, Math.min(1, (vWorld.y - 1.85) / 2.25));
                        const radialNorm = Math.min(1, vWorld.distanceTo(treeCenter) / 2.35);
                        const ao = (isCore ? 0.52 : 0.66) + 0.24 * hNorm + 0.16 * radialNorm;

                        pos.push(vWorld.x, vWorld.y, vWorld.z);
                        nor.push(nOut.x, nOut.y, nOut.z);
                        uv.push(gx / 2, gy / 2);
                        col.push(ao * 0.96, ao * 1.01, ao * 0.90);
                    }
                }
                for (let gy = 0; gy < 2; gy += 1) {
                    for (let gx = 0; gx < 2; gx += 1) {
                        const a = baseV + gy * 3 + gx;
                        const b = a + 3;
                        idx.push(a, a + 1, b, a + 1, b + 1, b);
                    }
                }
            }
        });

        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
        geo.setAttribute('normal', new THREE.Float32BufferAttribute(nor, 3));
        geo.setAttribute('uv', new THREE.Float32BufferAttribute(uv, 2));
        geo.setAttribute('color', new THREE.Float32BufferAttribute(col, 3));
        geo.setIndex(idx);
        return geo;
    }

    // Penempatan pohon: sekitar jalan, sela kandang, luar pagar, dan lereng bukit (mengikuti farmTerrainHeightAt!)
    // Zona pandang depan Kandang 1 (Z: 0..17, X: -13..+16) dijaga 100% bebas pohon tinggi!
    const treePlacements = [
        // Tepi barat jalan & sekitar Kandang 4
        [-20.4, 14.2, 1.18], [-20.2, 6.8, 1.04], [-20.5, -4.5, 1.12], [-20.4, -23.5, 1.15], [-20.6, -33.8, 1.24],
        [-46.8, -6.0, 1.25], [-47.2, -16.5, 1.16], [-46.5, -26.5, 1.30], [-36.0, -2.5, 1.08], [-28.5, -26.0, 1.14],
        // Peneduh timur & belakang deretan kandang
        [16.8, -6.8, 1.06], [17.4, -19.8, 1.15], [17.0, -32.2, 1.22],
        [-8.2, -35.8, 1.18], [1.5, -36.2, 1.10], [10.8, -35.6, 1.24], [-24.0, -36.0, 1.20], [-35.5, -36.2, 1.15],
        // Sisi timur & selatan luar pagar
        [23.2, 12.5, 1.26], [23.8, 2.5, 1.14], [24.2, -8.5, 1.28], [23.6, -21.5, 1.18], [24.0, -32.0, 1.22],
        [-26.5, 14.5, 1.22], [-35.0, 11.0, 1.30], [-42.5, 5.5, 1.18],
        // Sabuk hutan lereng perbukitan lembah (duduk presisi di kontur bukit 3D!)
        [-58, -48, 1.75], [-42, -55, 1.92], [-24, -58, 1.80], [-6, -60, 1.95], [12, -57, 1.85], [30, -52, 1.78],
        [46, -40, 1.88], [54, -20, 1.82], [52, 2, 1.72], [48, 22, 1.85], [34, 36, 1.76], [14, 44, 1.82],
        [-10, 44, 1.74], [-30, 38, 1.86], [-52, 26, 1.90], [-62, 6, 1.80], [-64, -18, 1.88], [-60, -36, 1.78],
        [-16, -78, 2.25], [6, -82, 2.35], [26, -76, 2.15], [-38, -74, 2.20], [44, -66, 2.10], [-54, -64, 2.18]
    ];

    const trunkGeo = buildRealisticTreeTrunkGeo();
    const crownGeo = buildBotanicalCrownGeo();
    const trunkMat = new THREE.MeshStandardMaterial({
        map: barkTex,
        bumpMap: barkTex,
        bumpScale: 0.035,
        roughness: 0.94,
        metalness: 0.0
    });
    const foliageMat = new THREE.MeshStandardMaterial({
        map: foliageClusterTex,
        alphaTest: 0.42,
        side: THREE.DoubleSide,
        vertexColors: true,
        roughness: 0.86,
        metalness: 0.0
    });
    const foliageDepthMat = new THREE.MeshDepthMaterial({
        depthPacking: THREE.RGBADepthPacking,
        map: foliageClusterTex,
        alphaTest: 0.42
    });

    const trunkInst = new THREE.InstancedMesh(trunkGeo, trunkMat, treePlacements.length);
    const canopyInst = new THREE.InstancedMesh(crownGeo, foliageMat, treePlacements.length);
    canopyInst.customDepthMaterial = foliageDepthMat;
    trunkInst.castShadow = true;
    trunkInst.receiveShadow = true;
    canopyInst.castShadow = true;
    canopyInst.receiveShadow = true;

    const treeTints = [0xf2f7ea, 0xe4f0d8, 0xf8f6e3, 0xdcead2, 0xebf3dc];
    const tintColor = new THREE.Color();
    treePlacements.forEach(([tx, tz, sc], idx) => {
        const groundY = farmTerrainHeightAt(tx, tz) - 0.04;
        fDummy.position.set(tx, groundY, tz);
        fDummy.rotation.set((idx % 3 - 1) * 0.025, idx * 1.37, ((idx + 1) % 3 - 1) * 0.025);
        fDummy.scale.set(sc, sc * (0.94 + (idx % 4) * 0.04), sc);
        fDummy.updateMatrix();
        trunkInst.setMatrixAt(idx, fDummy.matrix);
        canopyInst.setMatrixAt(idx, fDummy.matrix);
        tintColor.setHex(treeTints[idx % treeTints.length]);
        canopyInst.setColorAt(idx, tintColor);
    });
    trunkInst.instanceMatrix.needsUpdate = true;
    canopyInst.instanceMatrix.needsUpdate = true;
    if (canopyInst.instanceColor) {
        canopyInst.instanceColor.needsUpdate = true;
    }
    envGroup.add(trunkInst);
    envGroup.add(canopyInst);

    // 8. Rumpun Pohon Pisang Tropis Khas Pedesaan Indonesia (Pelepah Melengkung Sobek Alami)
    const bananaLeafTex = createFarmCanvasTexture(256, (ctx, S) => {
        ctx.clearRect(0, 0, S, S);
        const cx = S * 0.5;
        // Pelepah daun pisang lebar dengan sobekan angin alami di tepi
        for (let y = 14; y < S - 10; y += 1) {
            const v = y / S;
            const halfW = Math.sin(Math.pow(v, 0.72) * Math.PI) * (S * 0.40);
            const tearGap = ((y > 72 && y < 80) || (y > 138 && y < 145) || (y > 196 && y < 202));
            if (tearGap) {
                ctx.fillStyle = '#4e7f29';
                ctx.fillRect(cx - 5, y, 10, 1.5);
                continue;
            }
            const grad = ctx.createLinearGradient(cx - halfW, y, cx + halfW, y);
            grad.addColorStop(0.0, '#3e6b20');
            grad.addColorStop(0.45, '#598d31');
            grad.addColorStop(0.50, '#89b449');
            grad.addColorStop(0.55, '#598d31');
            grad.addColorStop(1.0, '#3a651d');
            ctx.fillStyle = grad;
            ctx.fillRect(cx - halfW, y, halfW * 2, 1.5);
        }
        ctx.fillStyle = '#7da640';
        ctx.fillRect(cx - 2.5, 16, 5, S - 20);
    }, 1, 1, true);

    function buildBananaPlantGeo() {
        const pos = [], nor = [], uv = [], idx = [];
        // Batang semu pohon pisang (pseudostem)
        const radSeg = 7;
        for (let i = 0; i <= 3; i += 1) {
            const t = i / 3;
            const r = 0.11 * (1 - t * 0.38);
            const y = t * 1.52;
            for (let j = 0; j <= radSeg; j += 1) {
                const a = (j / radSeg) * Math.PI * 2;
                pos.push(Math.cos(a) * r, y, Math.sin(a) * r);
                nor.push(Math.cos(a), 0.15, Math.sin(a));
                uv.push(0.5, 0.5);
            }
        }
        for (let i = 0; i < 3; i += 1) {
            for (let j = 0; j < radSeg; j += 1) {
                const a = i * (radSeg + 1) + j;
                const b = a + radSeg + 1;
                idx.push(a, b, a + 1, a + 1, b, b + 1);
            }
        }
        // 8 pelepah daun pisang melengkung parabolik dengan lipatan V di tulang daun
        const frondCount = 8;
        for (let f = 0; f < frondCount; f += 1) {
            const az = (f / frondCount) * Math.PI * 2 + (f % 2) * 0.22;
            const len = 1.42 + (f % 3) * 0.18;
            const rise = 0.72 + (f % 2) * 0.26;
            const droop = 0.62 + ((f + 1) % 3) * 0.16;
            const wid = 0.24;
            const baseV = pos.length / 3;
            const segs = 5;
            const ca = Math.cos(az), sa = Math.sin(az);
            const px = -sa, pz = ca;
            for (let s = 0; s <= segs; s += 1) {
                const u = s / segs;
                const rDist = 0.06 + u * len;
                const fy = 1.44 + u * rise - (u * u) * droop;
                const cx = ca * rDist;
                const cz = sa * rDist;
                for (let w = 0; w <= 2; w += 1) {
                    const side = w - 1; // -1, 0, +1
                    const vFold = Math.abs(side) * 0.055;
                    pos.push(cx + px * side * wid, fy - vFold, cz + pz * side * wid);
                    nor.push(px * side * 0.25, 0.94, pz * side * 0.25);
                    uv.push(w / 2, 1 - u);
                }
            }
            for (let s = 0; s < segs; s += 1) {
                for (let w = 0; w < 2; w += 1) {
                    const a = baseV + s * 3 + w;
                    const b = a + 3;
                    idx.push(a, a + 1, b, a + 1, b + 1, b);
                }
            }
        }
        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
        geo.setAttribute('normal', new THREE.Float32BufferAttribute(nor, 3));
        geo.setAttribute('uv', new THREE.Float32BufferAttribute(uv, 2));
        geo.setIndex(idx);
        return geo;
    }

    const bananaCoords = [
        // Rumpun pisang tepi parit & sudut pagar (2-3 batang berdekatan seperti tunas anakan asli)
        [-14.3, 6.2, 1.08], [-13.6, 6.9, 0.78], [-14.9, 7.1, 0.64],
        [15.2, 5.6, 1.12], [15.9, 6.3, 0.82], [14.6, 6.5, 0.68],
        [15.6, -6.6, 1.05], [16.3, -7.2, 0.76],
        [15.8, -20.2, 1.10], [16.5, -20.9, 0.80],
        [-21.2, 10.6, 1.15], [-21.9, 11.3, 0.84], [-20.6, 11.5, 0.65],
        [-21.4, -18.5, 1.08], [-22.1, -19.2, 0.78],
        [-12.2, 17.6, 1.04], [-11.5, 17.9, 0.74],
        [6.5, 17.6, 1.10], [7.3, 17.9, 0.80],
        [-4.2, -37.2, 1.14], [-3.4, -37.6, 0.82],
        [-21.5, -36.8, 1.08], [-20.8, -37.3, 0.76]
    ];
    const bananaMat = new THREE.MeshStandardMaterial({
        map: bananaLeafTex,
        alphaTest: 0.38,
        side: THREE.DoubleSide,
        roughness: 0.74,
        metalness: 0.02
    });
    const bananaInst = new THREE.InstancedMesh(buildBananaPlantGeo(), bananaMat, bananaCoords.length);
    bananaInst.customDepthMaterial = new THREE.MeshDepthMaterial({
        depthPacking: THREE.RGBADepthPacking,
        map: bananaLeafTex,
        alphaTest: 0.38
    });
    bananaInst.castShadow = true;
    bananaInst.receiveShadow = true;
    bananaCoords.forEach(([bx, bz, sc], idx) => {
        fDummy.position.set(bx, farmTerrainHeightAt(bx, bz) - 0.02, bz);
        fDummy.rotation.set(0, idx * 1.9, 0);
        fDummy.scale.set(sc, sc, sc);
        fDummy.updateMatrix();
        bananaInst.setMatrixAt(idx, fDummy.matrix);
    });
    bananaInst.instanceMatrix.needsUpdate = true;
    envGroup.add(bananaInst);

    // 9. Rumpun Rumput Liar 3D (320 rumpun di sepanjang bahu jalan, parit, tiang pagar, & bawah pohon)
    const grassBladeTex = createFarmCanvasTexture(128, (ctx, S, rnd) => {
        ctx.clearRect(0, 0, S, S);
        const cols = ['#2f521c', '#3d6624', '#4d782d', '#5c8936', '#69943e'];
        for (let i = 0; i < 34; i += 1) {
            const x0 = 10 + (i / 34) * (S - 20) + (rnd() - 0.5) * 4;
            const lean = (rnd() - 0.5) * 26;
            const h = S * (0.48 + rnd() * 0.46);
            ctx.strokeStyle = cols[Math.floor(rnd() * cols.length)];
            ctx.lineWidth = 2.6 + rnd() * 1.8;
            ctx.beginPath();
            ctx.moveTo(x0, S);
            ctx.quadraticCurveTo(x0 + lean * 0.45, S - h * 0.55, x0 + lean, S - h);
            ctx.stroke();
        }
    }, 1, 1, true);

    const tuftGeo = new THREE.BufferGeometry();
    {
        const p = [], n = [], u = [], ix = [];
        for (let plane = 0; plane < 3; plane += 1) {
            const ang = (plane * Math.PI) / 3;
            const dx = Math.cos(ang) * 0.26;
            const dz = Math.sin(ang) * 0.26;
            const b = p.length / 3;
            p.push(-dx * 0.85, 0, -dz * 0.85, dx * 0.85, 0, dz * 0.85, dx * 0.95, 0.22, dz * 0.95 + 0.03, -dx * 0.95, 0.22, -dz * 0.95 + 0.03);
            // Normal menghadap atas (0,1,0) agar iluminasi rumpun rumput menyatu 100% mulus dengan tanah
            for (let k = 0; k < 4; k += 1) n.push(0, 1, 0);
            u.push(0, 0, 1, 0, 1, 1, 0, 1);
            ix.push(b, b + 1, b + 2, b, b + 2, b + 3);
        }
        tuftGeo.setAttribute('position', new THREE.Float32BufferAttribute(p, 3));
        tuftGeo.setAttribute('normal', new THREE.Float32BufferAttribute(n, 3));
        tuftGeo.setAttribute('uv', new THREE.Float32BufferAttribute(u, 2));
        tuftGeo.setIndex(ix);
    }
    const tuftCoords = [];
    let tSeed = 7719;
    const tRnd = () => { tSeed = (tSeed * 1664525 + 1013904223) >>> 0; return tSeed / 4294967296; };
    // Sepanjang bahu jalan utama
    for (let z = -37; z <= 18; z += 1.6) {
        tuftCoords.push([-19.0 - tRnd() * 0.8, z + tRnd() * 0.6, 0.8 + tRnd() * 0.5]);
        if (Math.abs(z + 13.6) > 5.0) {
            tuftCoords.push([-13.4 + tRnd() * 0.5, z + tRnd() * 0.6, 0.75 + tRnd() * 0.45]);
        }
    }
    // Tepi luar parit keempat kandang & sekitar pagar
    coopCenters.forEach(([ccx, ccz]) => {
        for (let x = ccx - 10.5; x <= ccx + 10.5; x += 2.2) {
            tuftCoords.push([x + (tRnd() - 0.5) * 0.7, ccz + 5.05 + tRnd() * 0.45, 0.7 + tRnd() * 0.45]);
            tuftCoords.push([x + (tRnd() - 0.5) * 0.7, ccz - 5.05 - tRnd() * 0.45, 0.7 + tRnd() * 0.45]);
        }
    });
    for (let i = 0; i < 26; i += 1) {
        const tp = treePlacements[i];
        tuftCoords.push([tp[0] + 0.45, tp[1] - 0.35, 0.95]);
        tuftCoords.push([tp[0] - 0.42, tp[1] + 0.40, 0.88]);
    }

    const tuftInst = new THREE.InstancedMesh(
        tuftGeo,
        new THREE.MeshStandardMaterial({
            map: grassBladeTex,
            color: 0x93a87b,
            alphaTest: 0.40,
            side: THREE.DoubleSide,
            roughness: 0.95,
            metalness: 0.0
        }),
        tuftCoords.length
    );
    tuftCoords.forEach(([gx, gz, sc], idx) => {
        fDummy.position.set(gx, farmTerrainHeightAt(gx, gz) - 0.02, gz);
        fDummy.rotation.set(0, idx * 2.1, 0);
        fDummy.scale.set(sc, sc * (0.85 + (idx % 3) * 0.14), sc);
        fDummy.updateMatrix();
        tuftInst.setMatrixAt(idx, fDummy.matrix);
    });
    tuftInst.instanceMatrix.needsUpdate = true;
    envGroup.add(tuftInst);

    scene3D.add(envGroup);
}

function buildKandang3DScene() {
    // Bangun lanskap peternakan asli terlebih dahulu di sekeliling kandang utama
    buildFarmEnvironment3D();

    // =====================================================================
    // KOORDINAT ARSITEKTUR KANDANG:
    // Y=0: Lantai, Y=3.60: Tepi Atap/Kolom, Y=4.85: Nok Bubungan Puncak Atap
    // Z=±3.5: Kolom samping, Z=±2.1: Kandang baterai, Z=±1.32: Talang
    // Sisi Depan (Z > 0): TERBUKA TANPA TEMBOK (Cutaway view ke kandang & troli)
    // Sisi Belakang (Z < 0): Curb wall rendah 0.45m + tiang baja
    // Dinding Ujung (X = ±9.9): Portal rangka baja terbuka (lorong bebas pandang)
    // Atap: Pelana galvalum simetris rapi (kemiringan ~18.6°)
    // Sprinkler: Pipa nok luar Y=5.02, partikel 100% meluncur di permukaan seng luar
    // Kipas Blower: Gantung dari plafon di Y=3.2, berputar di atas lorong
    // =====================================================================

    // A. Lantai beton abu-krem bertekstur agregat & bump halus + sambungan dilatasi tiap 3m + garis drainase tengah
    const slabTex = createFarmCanvasTexture(512, (ctx, S, rnd) => {
        ctx.fillStyle = '#a29a8a';
        ctx.fillRect(0, 0, S, S);
        for (let i = 0; i < 8500; i += 1) {
            const d = 136 + Math.floor(rnd() * 38);
            ctx.fillStyle = 'rgb(' + d + ',' + (d - 5) + ',' + (d - 13) + ')';
            ctx.fillRect(rnd() * S, rnd() * S, 2, 2);
        }
        for (let i = 0; i < 55; i += 1) {
            ctx.fillStyle = 'rgba(78, 72, 62, 0.08)';
            ctx.beginPath();
            ctx.arc(rnd() * S, rnd() * S, 12 + rnd() * 26, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.strokeStyle = 'rgba(62, 57, 48, 0.36)';
        ctx.lineWidth = 2.5;
        ctx.strokeRect(1.5, 1.5, S - 3, S - 3);
    }, 7, 3);
    const floorMat = new THREE.MeshStandardMaterial({
        map: slabTex,
        bumpMap: slabTex,
        bumpScale: 0.012,
        roughness: 0.92,
        metalness: 0.02
    });
    const floor = new THREE.Mesh(new THREE.PlaneGeometry(22.2, 9.6), floorMat);
    floor.rotation.x = -Math.PI / 2;
    floor.receiveShadow = true;
    scene3D.add(floor);

    const drainMat = new THREE.MeshStandardMaterial({ color: 0x646c76, roughness: 0.9 });
    const drainLine = new THREE.Mesh(new THREE.BoxGeometry(22.2, 0.02, 0.12), drainMat);
    drainLine.position.y = 0.011;
    scene3D.add(drainLine);

    // B. Dinding Kandang:
    // Sisi Belakang (Z = -3.6): Tembok batako plester abu-tua rendah (0.45m)
    // Sisi Depan (Z = +3.6): 100% TERBUKA TANPA TEMBOK (agar dalam kandang terlihat jelas)
    const wallMat = new THREE.MeshStandardMaterial({
        map: slabTex,
        color: 0x68737e,
        bumpMap: slabTex,
        bumpScale: 0.018,
        roughness: 0.88
    });
    const wallBack = new THREE.Mesh(new THREE.BoxGeometry(20.4, 0.45, 0.15), wallMat);
    wallBack.position.set(0, 0.225, -3.6);
    wallBack.castShadow = true;
    wallBack.receiveShadow = true;
    scene3D.add(wallBack);

    // C. Kolom Tiang Baja Galvanis + Plat Tapak Angkur (Baseplate) per interval 3m sepanjang lorong
    const steelMat = new THREE.MeshStandardMaterial({ color: 0x9aa6b5, metalness: 0.74, roughness: 0.27 });
    const basePlateGeo = new THREE.BoxGeometry(0.22, 0.024, 0.22);
    const colPositions = [-9, -6, -3, 0, 3, 6, 9];
    for (const cx of colPositions) {
        const cL = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.065, 3.6, 12), steelMat);
        cL.position.set(cx, 1.8, -3.5);
        cL.castShadow = true;
        scene3D.add(cL);
        const bpL = new THREE.Mesh(basePlateGeo, steelMat);
        bpL.position.set(cx, 0.012, -3.5);
        scene3D.add(bpL);

        const cR = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.065, 3.6, 12), steelMat);
        cR.position.set(cx, 1.8, 3.5);
        cR.castShadow = true;
        scene3D.add(cR);
        const bpR = new THREE.Mesh(basePlateGeo, steelMat);
        bpR.position.set(cx, 0.012, 3.5);
        scene3D.add(bpR);
    }

    // D. ATAP PELANA SENG GALVALUM BERGELOMBANG RAPI & SIMETRIS (Kemiringan ~18.6°)
    const galvalumTex = createFarmCanvasTexture(256, (ctx, S, rnd) => {
        ctx.fillStyle = '#aeb8c2';
        ctx.fillRect(0, 0, S, S);
        for (let x = 0; x < S; x += 8) {
            const g = ctx.createLinearGradient(x, 0, x + 8, 0);
            g.addColorStop(0.0, 'rgba(30, 41, 59, 0.22)');
            g.addColorStop(0.45, 'rgba(255, 255, 255, 0.20)');
            g.addColorStop(1.0, 'rgba(30, 41, 59, 0.22)');
            ctx.fillStyle = g;
            ctx.fillRect(x, 0, 8, S);
        }
        // Garis sambungan lembaran seng & sedikit patina cuaca alami
        ctx.strokeStyle = 'rgba(51, 65, 85, 0.26)';
        ctx.lineWidth = 2;
        ctx.beginPath(); ctx.moveTo(0, S * 0.5); ctx.lineTo(S, S * 0.5); ctx.stroke();
        for (let i = 0; i < 400; i += 1) {
            ctx.fillStyle = 'rgba(90, 96, 102, 0.07)';
            ctx.fillRect(rnd() * S, rnd() * S, 4, 6);
        }
    }, 22, 3);

    const roofMat = new THREE.MeshStandardMaterial({
        map: galvalumTex,
        bumpMap: galvalumTex,
        bumpScale: 0.018,
        color: 0xb6c2cf,
        metalness: 0.56,
        roughness: 0.33,
        side: THREE.DoubleSide,
        transparent: true,
        opacity: 0.44,
        depthWrite: false
    });

    // Helper untuk membuat bidang atap miring presisi matematis lengkap dengan UV tekstur gelombang seng
    function createSlopedRoofMesh(xMin, xMax, yRidge, zRidge, yEave, zEave, mat) {
        const geo = new THREE.BufferGeometry();
        const vertices = new Float32Array([
            xMin, yRidge, zRidge,
            xMax, yEave,  zEave,
            xMax, yRidge, zRidge,
            xMin, yRidge, zRidge,
            xMin, yEave,  zEave,
            xMax, yEave,  zEave
        ]);
        const uvs = new Float32Array([
            0, 1,
            1, 0,
            1, 1,
            0, 1,
            0, 0,
            1, 0
        ]);
        geo.setAttribute('position', new THREE.BufferAttribute(vertices, 3));
        geo.setAttribute('uv', new THREE.BufferAttribute(uvs, 2));
        geo.computeVertexNormals();
        return new THREE.Mesh(geo, mat);
    }

    const roofL = createSlopedRoofMesh(-10.2, 10.2, 4.85, 0, 3.60, -3.7, roofMat);
    scene3D.add(roofL);

    const roofR = createSlopedRoofMesh(-10.2, 10.2, 4.85, 0, 3.60, 3.7, roofMat);
    scene3D.add(roofR);

    // Gording Baja Memanjang (C-Purlins) di Bawah Seng Atap
    [-2.75, -1.55, 1.55, 2.75].forEach((pz) => {
        const py = 4.85 - (1.25 / 3.7) * Math.abs(pz) - 0.035;
        const purlin = new THREE.Mesh(new THREE.BoxGeometry(20.3, 0.04, 0.04), steelMat);
        purlin.position.set(0, py, pz);
        scene3D.add(purlin);
    });

    const ridgeCapMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, metalness: 0.82, roughness: 0.26 });
    const ridgeCap = new THREE.Mesh(new THREE.CylinderGeometry(0.12, 0.12, 20.4, 10), ridgeCapMat);
    ridgeCap.rotation.z = Math.PI / 2;
    ridgeCap.position.set(0, 4.88, 0);
    scene3D.add(ridgeCap);

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

    // F. Rak Kandang Baterai 2 TINGKAT — parameterized grid (kiri & kanan lorong)
    const CAGE_CONFIG = {
        sides: [-1, 1],            // -1 = Z negatif (kiri), +1 = Z positif (kanan)
        columnXs: [-8, -6, -4, -2, 0, 2, 4, 6, 8],
        tierCenterYs: [0.45, 1.35],
        cageSize: { w: 1.75, h: 0.7, d: 1.4 },
        cageCenterZ: 2.1,
        troughZ: 1.32,
        chickensPerCage: 3
    };

    const cageWireSolidMat = new THREE.MeshStandardMaterial({ color: 0xcbd5e1, metalness: 0.24, roughness: 0.42 });
    const keyholeFrameMat = new THREE.MeshStandardMaterial({
        color: 0xf8fafc,
        emissive: 0x38bdf8,
        emissiveIntensity: 0.10,
        metalness: 0.20,
        roughness: 0.28
    });
    const troughMat = new THREE.MeshStandardMaterial({ color: 0xfcd34d, roughness: 0.45, metalness: 0.05 }); // Talang KUNING cerah
    const aFrameMat = new THREE.MeshStandardMaterial({ color: 0xb0bec5, metalness: 0.82, roughness: 0.22 });
    const drinkerMat = new THREE.MeshStandardMaterial({ color: 0xfacc15, roughness: 0.4 });
    const manureMat = new THREE.MeshStandardMaterial({ color: 0x3f2d20, roughness: 0.95 });

    chickensList = [];
    feedTroughs = [];
    eggLanes = [];
    EGG_COLLECTOR.stations = [];
    activeEggs = [];
    eggPool = [];
    iotPinDefs = [];

    /** Tambahkan satu batang kotak tipis berorientasi bebas ke array BufferGeometry gabungan (hemat draw call). */
    function pushWireBar(posArr, norArr, idxArr, x1, y1, z1, x2, y2, z2, rZ, rPerp) {
        const dx = x2 - x1;
        const dy = y2 - y1;
        const dz = z2 - z1;
        const len = Math.hypot(dx, dy, dz);
        if (len < 0.002) {
            return;
        }
        const tx = dx / len, ty = dy / len, tz = dz / len;
        let ux = 0, uy = 1, uz = 0;
        if (Math.abs(ty) > 0.92) {
            ux = 1; uy = 0; uz = 0;
        }
        // s1 = normalize(t x u), s2 = t x s1
        let s1x = ty * uz - tz * uy;
        let s1y = tz * ux - tx * uz;
        let s1z = tx * uy - ty * ux;
        const s1L = Math.hypot(s1x, s1y, s1z) || 1;
        s1x /= s1L; s1y /= s1L; s1z /= s1L;
        const s2x = ty * s1z - tz * s1y;
        const s2y = tz * s1x - tx * s1z;
        const s2z = tx * s1y - ty * s1x;

        const r1 = rPerp !== undefined ? rPerp : rZ;
        const r2 = rZ;
        const faces = [
            { nx: s1x, ny: s1y, nz: s1z, ax: s1x * r1, ay: s1y * r1, az: s1z * r1, bx: s2x * r2, by: s2y * r2, bz: s2z * r2 },
            { nx: -s1x, ny: -s1y, nz: -s1z, ax: -s1x * r1, ay: -s1y * r1, az: -s1z * r1, bx: -s2x * r2, by: -s2y * r2, bz: -s2z * r2 },
            { nx: s2x, ny: s2y, nz: s2z, ax: s2x * r2, ay: s2y * r2, az: s2z * r2, bx: -s1x * r1, by: -s1y * r1, bz: -s1z * r1 },
            { nx: -s2x, ny: -s2y, nz: -s2z, ax: -s2x * r2, ay: -s2y * r2, az: -s2z * r2, bx: s1x * r1, by: s1y * r1, bz: s1z * r1 }
        ];
        for (let f = 0; f < 4; f += 1) {
            const fc = faces[f];
            const base = posArr.length / 3;
            posArr.push(
                x1 + fc.ax - fc.bx, y1 + fc.ay - fc.by, z1 + fc.az - fc.bz,
                x1 + fc.ax + fc.bx, y1 + fc.ay + fc.by, z1 + fc.az + fc.bz,
                x2 + fc.ax + fc.bx, y2 + fc.ay + fc.by, z2 + fc.az + fc.bz,
                x2 + fc.ax - fc.bx, y2 + fc.ay - fc.by, z2 + fc.az - fc.bz
            );
            for (let v = 0; v < 4; v += 1) {
                norArr.push(fc.nx, fc.ny, fc.nz);
            }
            idxArr.push(base, base + 1, base + 2, base, base + 2, base + 3);
        }
    }

    /** Potong interval [segMin, segMax] dengan daftar bukaan [[cutA, cutB], ...]. */
    function subtractWireIntervals(segMin, segMax, cuts) {
        let parts = [[segMin, segMax]];
        for (let i = 0; i < cuts.length; i += 1) {
            const cA = cuts[i][0];
            const cB = cuts[i][1];
            const next = [];
            for (let j = 0; j < parts.length; j += 1) {
                const a = parts[j][0];
                const b = parts[j][1];
                if (cB <= a || cA >= b) {
                    next.push([a, b]);
                } else {
                    if (cA - a > 0.004) next.push([a, cA]);
                    if (b - cB > 0.004) next.push([cB, b]);
                }
            }
            parts = next;
        }
        return parts;
    }

    /**
     * Geometri kawat 1 modul sangkar (+Z lokal menghadap talang kuning):
     * - Panel depan memiliki 3 bukaan makan lonjong/"keyhole" (batang yang memotong lubang dihilangkan).
     * - Lantai kawat miring ±8.5° menurun ke sabuk telur depan (+Z lokal) + sekat kompartemen 3 ayam.
     */
    function buildCageWireModuleGeometry() {
        const { w, h, d } = CAGE_CONFIG.cageSize;
        const r = FEED_HOLE.wireRadius;
        const rFrame = r * 1.45;
        const xMin = -w / 2, xMax = w / 2;
        const yMin = -h / 2, yMax = h / 2;
        const zBack = -d / 2, zFront = d / 2;
        const pos = [], nor = [], idx = [];

        // 1. Rangka tepi luar modul sangkar
        pushWireBar(pos, nor, idx, xMin, yMin, zFront, xMax, yMin, zFront, rFrame);
        pushWireBar(pos, nor, idx, xMin, yMax, zFront, xMax, yMax, zFront, rFrame);
        pushWireBar(pos, nor, idx, xMin, yMin, zBack, xMax, yMin, zBack, rFrame);
        pushWireBar(pos, nor, idx, xMin, yMax, zBack, xMax, yMax, zBack, rFrame);
        [xMin, xMax].forEach((xv) => {
            pushWireBar(pos, nor, idx, xv, yMin, zFront, xv, yMax, zFront, rFrame);
            pushWireBar(pos, nor, idx, xv, yMin, zBack, xv, yMax, zBack, rFrame);
            pushWireBar(pos, nor, idx, xv, yMin, zBack, xv, yMin, zFront, rFrame);
            pushWireBar(pos, nor, idx, xv, yMax, zBack, xv, yMax, zFront, rFrame);
        });

        // 2. Panel kawat depan (+Z lokal = |Z| dunia 1.40 m) dengan 3 bukaan makan keyhole bebas kawat
        const yHoleMin = yMin + FEED_HOLE.bottomOffset;
        const yHoleMax = yHoleMin + FEED_HOLE.height;
        const halfArch = FEED_HOLE.archWidth / 2;
        const halfSlot = FEED_HOLE.width / 2;

        for (let i = 1; i < FEED_HOLE.vBars - 1; i += 1) {
            const vx = xMin + (w * i) / (FEED_HOLE.vBars - 1);
            const vCuts = [];
            for (let k = 0; k < FEED_HOLE.slotXs.length; k += 1) {
                if (Math.abs(vx - FEED_HOLE.slotXs[k]) <= halfArch + r * 1.2) {
                    vCuts.push([yHoleMin, yHoleMax]);
                }
            }
            const segs = subtractWireIntervals(yMin, yMax, vCuts);
            for (let s = 0; s < segs.length; s += 1) {
                pushWireBar(pos, nor, idx, vx, segs[s][0], zFront, vx, segs[s][1], zFront, r);
            }
        }

        for (let i = 0; i < FEED_HOLE.hBarOffsets.length; i += 1) {
            const hy = yMin + FEED_HOLE.hBarOffsets[i];
            if (hy <= yMin + 0.01 || hy >= yMax - 0.01) {
                continue;
            }
            const hCuts = [];
            if (hy > yHoleMin + 0.002 && hy < yHoleMax - 0.002) {
                const uY = (hy - yHoleMin) / FEED_HOLE.height;
                const hw = uY >= 0.50 ? halfArch : halfSlot;
                for (let k = 0; k < FEED_HOLE.slotXs.length; k += 1) {
                    hCuts.push([FEED_HOLE.slotXs[k] - hw, FEED_HOLE.slotXs[k] + hw]);
                }
            }
            const segs = subtractWireIntervals(xMin, xMax, hCuts);
            for (let s = 0; s < segs.length; s += 1) {
                pushWireBar(pos, nor, idx, segs[s][0], hy, zFront, segs[s][1], hy, zFront, r);
            }
        }

        // 3. Kawat atap, belakang, samping, dan 2 sekat dalam pembagi 3 kompartemen ayam
        for (let i = 1; i <= 5; i += 1) {
            const zv = zBack + (d * i) / 6;
            pushWireBar(pos, nor, idx, xMin, yMax, zv, xMax, yMax, zv, r * 0.85);
        }
        for (let i = 1; i <= 8; i += 1) {
            const xv = xMin + (w * i) / 9;
            pushWireBar(pos, nor, idx, xv, yMin, zBack, xv, yMax, zBack, r * 0.85);
            pushWireBar(pos, nor, idx, xv, yMax, zBack, xv, yMax, zFront, r * 0.8);
        }
        [0.22, 0.46].forEach((off) => {
            pushWireBar(pos, nor, idx, xMin, yMin + off, zBack, xMax, yMin + off, zBack, r * 0.85);
            pushWireBar(pos, nor, idx, xMin, yMin + off, zBack, xMin, yMin + off, zFront, r * 0.85);
            pushWireBar(pos, nor, idx, xMax, yMin + off, zBack, xMax, yMin + off, zFront, r * 0.85);
        });
        [-w / 6, w / 6].forEach((px) => {
            pushWireBar(pos, nor, idx, px, yMin + 0.08, zBack, px, yMax, zFront - 0.04, r * 0.8);
        });

        // 4. Lantai kawat miring ±8.5° (dari belakang sangkar menurun ke bibir sabuk telur di depan)
        const rearAbsZ = CAGE_CONFIG.cageCenterZ + d / 2 - 0.02;
        const lipAbsZ = EGG_PATH.beltZ + 0.02;
        const zSlopeBack = CAGE_CONFIG.cageCenterZ - rearAbsZ;
        const zSlopeFront = CAGE_CONFIG.cageCenterZ - lipAbsZ;
        const slopeTan = Math.tan((EGG_PATH.floorSlopeDeg * Math.PI) / 180);
        const ySlopeBack = yMin + EGG_PATH.beltOffsetY + (rearAbsZ - EGG_PATH.beltZ) * slopeTan;
        const ySlopeFront = yMin + EGG_PATH.beltOffsetY + (lipAbsZ - EGG_PATH.beltZ) * slopeTan;

        for (let i = 0; i <= 12; i += 1) {
            const fx = xMin + 0.04 + ((w - 0.08) * i) / 12;
            pushWireBar(pos, nor, idx, fx, ySlopeBack, zSlopeBack, fx, ySlopeFront, zSlopeFront, r * 0.85);
        }
        [0.15, 0.5, 0.88].forEach((t) => {
            const zy = ySlopeBack + (ySlopeFront - ySlopeBack) * t;
            const zz = zSlopeBack + (zSlopeFront - zSlopeBack) * t;
            pushWireBar(pos, nor, idx, xMin, zy, zz, xMax, zy, zz, r * 0.85);
        });

        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
        geo.setAttribute('normal', new THREE.Float32BufferAttribute(nor, 3));
        geo.setIndex(idx);
        return geo;
    }

    /** Geometri bingkai tipis logam berbentuk lonjong/"keyhole" (1 geometri di-instance 108x). */
    function buildKeyholeFrameGeometry() {
        const ft = FEED_HOLE.frameThick;
        const fd = FEED_HOLE.frameDepth;
        const H = FEED_HOLE.height;
        const hs = FEED_HOLE.width / 2;
        const ha = FEED_HOLE.archWidth / 2;
        const shoulderY = H * 0.50;
        const archCenterY = H - ha;
        const pos = [], nor = [], idx = [];

        // Ambang bawah & tiang leher bawah
        pushWireBar(pos, nor, idx, -hs - ft, 0, 0, hs + ft, 0, 0, fd * 0.5, ft * 0.5);
        pushWireBar(pos, nor, idx, -hs, 0, 0, -hs, shoulderY, 0, fd * 0.5, ft * 0.5);
        pushWireBar(pos, nor, idx, hs, 0, 0, hs, shoulderY, 0, fd * 0.5, ft * 0.5);

        // Bahu pelebaran keyhole & pipi atas
        pushWireBar(pos, nor, idx, -hs, shoulderY, 0, -ha, shoulderY + 0.012, 0, fd * 0.5, ft * 0.5);
        pushWireBar(pos, nor, idx, hs, shoulderY, 0, ha, shoulderY + 0.012, 0, fd * 0.5, ft * 0.5);
        pushWireBar(pos, nor, idx, -ha, shoulderY + 0.012, 0, -ha, archCenterY, 0, fd * 0.5, ft * 0.5);
        pushWireBar(pos, nor, idx, ha, shoulderY + 0.012, 0, ha, archCenterY, 0, fd * 0.5, ft * 0.5);

        // Busur atas melengkung (8 segmen halus)
        const arcSegs = 8;
        for (let a = 0; a < arcSegs; a += 1) {
            const a0 = (Math.PI * a) / arcSegs;
            const a1 = (Math.PI * (a + 1)) / arcSegs;
            const x0 = Math.cos(a0) * ha;
            const y0 = archCenterY + Math.sin(a0) * ha;
            const x1 = Math.cos(a1) * ha;
            const y1 = archCenterY + Math.sin(a1) * ha;
            pushWireBar(pos, nor, idx, x0, y0, 0, x1, y1, 0, fd * 0.5, ft * 0.5);
        }

        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3));
        geo.setAttribute('normal', new THREE.Float32BufferAttribute(nor, 3));
        geo.setIndex(idx);
        return geo;
    }

    const totalModules = CAGE_CONFIG.sides.length * CAGE_CONFIG.columnXs.length * CAGE_CONFIG.tierCenterYs.length;
    const totalHoles = totalModules * CAGE_CONFIG.chickensPerCage;
    const cageWireInst = new THREE.InstancedMesh(buildCageWireModuleGeometry(), cageWireSolidMat, totalModules);
    const feedHoleFrameInst = new THREE.InstancedMesh(buildKeyholeFrameGeometry(), keyholeFrameMat, totalHoles);
    cageWireInst.receiveShadow = true;
    scene3D.add(cageWireInst);
    scene3D.add(feedHoleFrameInst);
    let moduleInstCursor = 0;
    let holeInstCursor = 0;
    const _instDummy = new THREE.Object3D();

    // Buat terlebih dahulu sabuk telur, bak pengumpul (EGG_COLLECTOR), drinker, & sabuk kotoran per sisi dan tingkat
    CAGE_CONFIG.sides.forEach((side) => {
        for (let fx = -9; fx <= 9; fx += 2) {
            scene3D.add(createAFrame(side, fx));
        }
        CAGE_CONFIG.tierCenterYs.forEach((cy, tierIdx) => {
            const floorY = cy - CAGE_CONFIG.cageSize.h / 2;
            const drinker = new THREE.Mesh(new THREE.CylinderGeometry(0.012, 0.012, 17.6), drinkerMat);
            drinker.rotation.z = Math.PI / 2;
            drinker.position.set(0, floorY + 0.52, side * 2.58);
            scene3D.add(drinker);

            const manureBelt = new THREE.Mesh(new THREE.BoxGeometry(17.7, 0.012, 1.18), manureMat);
            manureBelt.position.set(0, floorY - 0.035, side * 2.18);
            scene3D.add(manureBelt);

            createEggLaneAndCollector(side, tierIdx, floorY);
        });
    });

    /** Satu modul sangkar (kawat berlubang makan, bingkai keyhole, talang kuning, waypoint telur, & 3 ayam). */
    function createCageModule(side, tierIndex, x) {
        const { h, d } = CAGE_CONFIG.cageSize;
        const cageFront = CAGE_CONFIG.cageCenterZ - d / 2;
        if (Math.abs(FEED.cageFrontZ - cageFront) > 0.001) {
            console.warn('[Digital Twin] FEED.cageFrontZ (' + FEED.cageFrontZ + ') berbeda dari kawat depan di scene (' + cageFront + '); memakai nilai scene.');
            FEED.cageFrontZ = cageFront;
        }
        const cy = CAGE_CONFIG.tierCenterYs[tierIndex];
        const floorY = cy - h / 2;
        const rotY = side === -1 ? 0 : Math.PI;
        const module = new THREE.Group();

        // Instance kawat sangkar berlubang makan (1 draw call untuk 36 modul)
        _instDummy.position.set(x, cy, side * CAGE_CONFIG.cageCenterZ);
        _instDummy.rotation.set(0, rotY, 0);
        _instDummy.scale.set(1, 1, 1);
        _instDummy.updateMatrix();
        cageWireInst.setMatrixAt(moduleInstCursor++, _instDummy.matrix);

        // Instance 3 bingkai logam lubang makan keyhole pada bidang kawat depan |Z| = cageFront
        for (let k = 0; k < FEED_HOLE.slotXs.length; k += 1) {
            _instDummy.position.set(x + FEED_HOLE.slotXs[k], floorY + FEED_HOLE.bottomOffset, side * cageFront);
            _instDummy.rotation.set(0, rotY, 0);
            _instDummy.updateMatrix();
            feedHoleFrameInst.setMatrixAt(holeInstCursor++, _instDummy.matrix);
        }

        // Talang pakan kuning (posisi & ukuran asli dipertahankan)
        const trough = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.1, 0.25), troughMat);
        trough.position.set(x, floorY, side * CAGE_CONFIG.troughZ);
        trough.receiveShadow = true;
        module.add(trough);
        trough.updateMatrixWorld(true);
        const troughBox = new THREE.Box3().setFromObject(trough);
        feedTroughs.push({
            side,
            tierIdx: tierIndex,
            x,
            mesh: trough,
            topY: troughBox.max.y,
            nearAbsZ: Math.max(Math.abs(troughBox.min.z), Math.abs(troughBox.max.z)),
            farAbsZ: Math.min(Math.abs(troughBox.min.z), Math.abs(troughBox.max.z)),
            halfLenX: (troughBox.max.x - troughBox.min.x) / 2
        });

        const lane = eggLanes.find((l) => l.side === side && l.tierIdx === tierIndex) || null;

        // 3 ekor ayam per modul: disejajarkan tepat dengan posisi X lubang makannya masing-masing (FEED_HOLE.slotXs)
        for (let c = 0; c < CAGE_CONFIG.chickensPerCage; c += 1) {
            const chicken = createStylizedChicken(c);
            const sideYaw = side === -1 ? 0 : Math.PI;
            const yawJitter = (Math.random() - 0.5) * 0.35;
            const holeX = x + FEED_HOLE.slotXs[c];
            const homeX = holeX + (Math.random() - 0.5) * 0.022;
            const homeAbsZ = 1.88 + (c % 2) * 0.30;
            const homeY = cageFloorHeightAtAbsZ(floorY, homeAbsZ);

            chicken.position.set(homeX, homeY, side * homeAbsZ);
            chicken.rotation.y = sideYaw + yawJitter;
            module.add(chicken);
            chickensList.push(chicken);
            Object.assign(chicken.userData, {
                side,
                tierIdx: tierIndex,
                slotIdx: c,
                floorY,
                holeX,
                homeX,
                homeY,
                homeZ: chicken.position.z,
                sideYaw,
                yawJitter,
                sc: chicken.scale.x,
                peckHzMul: 0.92 + Math.random() * 0.16,
                peckPhaseOffset: Math.random() * Math.PI * 2,
                lane,
                eggPath: lane ? buildSlotEggWaypoints(side, floorY, holeX, lane) : null,
                instIndex: chickensList.length - 1
            });
        }
        return module;
    }

    /** Rangka A galvanis: 2 tiang, palang atas, dan penguat diagonal per portal. */
    function createAFrame(side, x) {
        const frame = new THREE.Group();
        const zNear = side * 1.3;
        const zFar = side * 2.85;
        [zNear, zFar].forEach((tz) => {
            const leg = new THREE.Mesh(new THREE.BoxGeometry(0.05, 2.1, 0.05), aFrameMat);
            leg.position.set(x, 1.05, tz);
            leg.castShadow = true;
            frame.add(leg);
        });
        const topBar = new THREE.Mesh(new THREE.BoxGeometry(0.04, 0.04, Math.abs(zFar - zNear)), aFrameMat);
        topBar.position.set(x, 2.05, (zNear + zFar) / 2);
        frame.add(topBar);

        const dz = zFar - zNear;
        const dy = 1.9;
        const brace = new THREE.Mesh(new THREE.BoxGeometry(0.03, 0.03, Math.hypot(dz, dy)), aFrameMat);
        brace.position.set(x, 1.05, (zNear + zFar) / 2);
        brace.rotation.x = -Math.atan2(dy, dz);
        frame.add(brace);
        return frame;
    }

    CAGE_CONFIG.sides.forEach((side) => {
        CAGE_CONFIG.columnXs.forEach((x) => {
            CAGE_CONFIG.tierCenterYs.forEach((_, tierIndex) => {
                scene3D.add(createCageModule(side, tierIndex, x));
            });
        });
    });
    cageWireInst.instanceMatrix.needsUpdate = true;
    feedHoleFrameInst.instanceMatrix.needsUpdate = true;

    // Lampu LED plafon (visual) tiap 3m
    const bulbGeo = new THREE.SphereGeometry(0.055, 8, 6);
    const bulbMat = new THREE.MeshStandardMaterial({ color: 0xfff9e0, emissive: 0xffe066, emissiveIntensity: 1.2 });
    coopBulbMat = bulbMat;
    for (let bx = -9; bx <= 9; bx += 3) {
        const bulb = new THREE.Mesh(bulbGeo, bulbMat);
        bulb.position.set(bx, 3.45, 0);
        scene3D.add(bulb);
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

    // Motor gearbox kuning (Motor DC 12V High-Torque)
    const motorBox = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.22, 0.38),
        new THREE.MeshStandardMaterial({ color: 0xfbbf24, metalness: 0.3 }));
    motorBox.position.set(-8.5, 2.6, 0);
    scene3D.add(motorBox);

    // Spool Katrol Gulungan Kawat Sling
    const spoolWinch = new THREE.Mesh(
        new THREE.CylinderGeometry(0.09, 0.09, 0.18, 16),
        new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.85 })
    );
    spoolWinch.rotation.x = Math.PI / 2;
    spoolWinch.position.set(-8.5, 2.6, 0);
    scene3D.add(spoolWinch);

    // Stasiun Pulley B (Titik ujung, warna merah)
    const stB = new THREE.Mesh(new THREE.CylinderGeometry(0.16, 0.16, 2.6),
        new THREE.MeshStandardMaterial({ color: 0xef4444 }));
    stB.position.set(8.5, 1.3, 0);
    scene3D.add(stB);
    const pulley = new THREE.Mesh(new THREE.CylinderGeometry(0.14, 0.14, 0.1, 16), steelMat);
    pulley.position.set(8.5, 2.6, 0);
    scene3D.add(pulley);

    // =====================================================================
    // KOMPONEN IOT PENGGERAK & SENSOR DIGITAL TWIN (HARDWARE ASLI)
    // =====================================================================
    iotLabelsGroup = new THREE.Group();

    // Helper membuat 3D Badge Sprite Label Melayang
    function createBadgeSprite(text, colorHex = '#38bdf8', icon = '') {
        return registerIoTPinAnchor(text, colorHex, icon);
    }

    // 1. PANEL KONTROL MASTER IOT (ESP32 + 4-CHANNEL RELAY IP65 BOX)
    // Dipasang di tiang pangkal lorong dekat stasiun A
    const boxGroup = new THREE.Group();
    boxGroup.position.set(-8.5, 1.55, -1.05);

    // Kotak Panel Putih Industri
    const boxEnclosure = new THREE.Mesh(
        new THREE.BoxGeometry(0.36, 0.50, 0.22),
        new THREE.MeshStandardMaterial({ color: 0x78909c, roughness: 0.4, metalness: 0.2 })
    );
    boxGroup.add(boxEnclosure);

    // Pintu / Bezel Hitam
    const boxDoor = new THREE.Mesh(
        new THREE.BoxGeometry(0.34, 0.48, 0.02),
        new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.4 })
    );
    boxDoor.position.z = 0.11;
    boxGroup.add(boxDoor);

    // Layar OLED 0.96" Menyala Cyan (Menampilkan Telemetri Suhu & Status)
    const oledScreen = new THREE.Mesh(
        new THREE.BoxGeometry(0.14, 0.09, 0.02),
        new THREE.MeshStandardMaterial({ color: 0x06b6d4, emissive: 0x0891b2, roughness: 0.2 })
    );
    oledScreen.position.set(0, 0.08, 0.12);
    boxGroup.add(oledScreen);

    // 3 LED Indikator IoT (Power Hijau, WiFi Biru, Relay Aktif Oranye)
    const ledGreen = new THREE.Mesh(new THREE.SphereGeometry(0.016, 8, 8),
        new THREE.MeshStandardMaterial({ color: 0x22c55e, emissive: 0x16a34a }));
    ledGreen.position.set(-0.08, -0.08, 0.12);
    boxGroup.add(ledGreen);

    const ledBlue = new THREE.Mesh(new THREE.SphereGeometry(0.016, 8, 8),
        new THREE.MeshStandardMaterial({ color: 0x3b82f6, emissive: 0x2563eb }));
    ledBlue.position.set(0, -0.08, 0.12);
    boxGroup.add(ledBlue);

    const ledAmber = new THREE.Mesh(new THREE.SphereGeometry(0.016, 8, 8),
        new THREE.MeshStandardMaterial({ color: 0xf59e0b, emissive: 0xd97706 }));
    ledAmber.position.set(0.08, -0.08, 0.12);
    boxGroup.add(ledAmber);

    // Pipa konduit fleksibel dari panel ke motor
    const conduit = new THREE.Mesh(new THREE.CylinderGeometry(0.025, 0.025, 1.1),
        new THREE.MeshStandardMaterial({ color: 0x334155 }));
    conduit.position.set(0, 0.55, 0);
    boxGroup.add(conduit);

    scene3D.add(boxGroup);

    const lblPanel = createBadgeSprite('Panel ESP32 & Relay', '#06b6d4', '');
    lblPanel.position.set(-8.5, 2.05, -1.05);
    iotLabelsGroup.add(lblPanel);

    // 2. MODUL DRIVER MOTOR L298N (PENGGERAK KATROL PAKAN)
    const l298Group = new THREE.Group();
    l298Group.position.set(-8.5, 2.45, -0.32);

    // PCB Merah L298N
    const pcb = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.02, 0.18),
        new THREE.MeshStandardMaterial({ color: 0xb91c1c }));
    l298Group.add(pcb);

    // Heatsink Aluminium Merah khas L298N
    const heatsink = new THREE.Mesh(new THREE.BoxGeometry(0.07, 0.10, 0.14),
        new THREE.MeshStandardMaterial({ color: 0x111827, metalness: 0.7, roughness: 0.4 }));
    heatsink.position.y = 0.06;
    l298Group.add(heatsink);

    scene3D.add(l298Group);

    const lblMotor = createBadgeSprite('Motor & Driver L298N', '#f59e0b', '');
    lblMotor.position.set(-8.5, 2.95, 0);
    iotLabelsGroup.add(lblMotor);

    // 3. LIMIT SWITCH TITIK A & TITIK B (SENSOR PEMBATAS KERETA PAKAN)
    // Limit Switch A di Titik Pangkal (x = -7.2)
    const limA = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.05, 0.06),
        new THREE.MeshStandardMaterial({ color: 0xef4444 }));
    limA.position.set(-7.2, 2.65, 0.44);
    scene3D.add(limA);

    const lblLimA = createBadgeSprite('Limit Switch A', '#ef4444', '');
    lblLimA.position.set(-7.2, 2.98, 0.44);
    iotLabelsGroup.add(lblLimA);

    // Limit Switch B di Titik Ujung (x = 7.2)
    const limB = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.05, 0.06),
        new THREE.MeshStandardMaterial({ color: 0xef4444 }));
    limB.position.set(7.2, 2.65, 0.44);
    scene3D.add(limB);

    const lblLimB = createBadgeSprite('Limit Switch B', '#ef4444', '');
    lblLimB.position.set(7.2, 2.98, 0.44);
    iotLabelsGroup.add(lblLimB);

    // 4. SISTEM PENGGERAK SPRINKLER (TANDON AIR + POMPA DIAFRAGMA 12V + RISER PIPE)
    // Tandon Air Biru (Toren 100 Liter) di Sisi Luar Belakang
    const torenGroup = new THREE.Group();
    torenGroup.position.set(-8.8, 0.48, -4.0);

    const torenBody = new THREE.Mesh(
        new THREE.CylinderGeometry(0.38, 0.38, 0.96, 16),
        new THREE.MeshStandardMaterial({ color: 0x0284c7, roughness: 0.3 })
    );
    torenGroup.add(torenBody);

    const torenCap = new THREE.Mesh(
        new THREE.CylinderGeometry(0.20, 0.20, 0.08, 16),
        new THREE.MeshStandardMaterial({ color: 0x1e293b })
    );
    torenCap.position.y = 0.52;
    torenGroup.add(torenCap);

    scene3D.add(torenGroup);

    // Pompa Booster Diafragma 12V (Penggerak Semprot Air Atap)
    const pumpMesh = new THREE.Mesh(
        new THREE.BoxGeometry(0.18, 0.12, 0.12),
        new THREE.MeshStandardMaterial({ color: 0x1e293b, metalness: 0.7 })
    );
    pumpMesh.position.set(-8.8, 0.15, -3.4);
    scene3D.add(pumpMesh);

    // Pipa Riser Saluran Air Naik Menyusuri Tiang Kolom ke Puncak Nok Atap
    const riserPipe = new THREE.Mesh(
        new THREE.CylinderGeometry(0.025, 0.025, 4.88),
        new THREE.MeshStandardMaterial({ color: 0xe2e8f0, metalness: 0.85 })
    );
    riserPipe.position.set(-8.8, 2.58, -3.48);
    scene3D.add(riserPipe);

    const lblPump = createBadgeSprite('Tandon & Pompa Sprinkler', '#38bdf8', '');
    lblPump.position.set(-8.8, 1.35, -4.0);
    iotLabelsGroup.add(lblPump);

    // 5. NODE SENSOR LINGKUNGAN IOT (ZONA TENGAH KANDANG)
    // Sensor DHT22 (Suhu & Kelembaban Udara di Zona Ayam)
    const dht22 = new THREE.Mesh(
        new THREE.BoxGeometry(0.08, 0.11, 0.05),
        new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.4 })
    );
    dht22.position.set(0, 1.35, -1.33);
    scene3D.add(dht22);

    const lblDHT = createBadgeSprite('Sensor DHT22 Suhu', '#10b981', '');
    lblDHT.position.set(0, 1.72, -1.33);
    iotLabelsGroup.add(lblDHT);

    // 6. Label Kipas Sirkulasi Plafon (DC 4x4)
    const lblFan = createBadgeSprite('Kipas Sirkulasi Plafon 4x4', '#38bdf8', '');
    lblFan.position.set(0, 3.65, 0);
    iotLabelsGroup.add(lblFan);

    scene3D.add(iotLabelsGroup);
    createIoTPins();

    // H. Kereta Pakan (Travelling Hopper) — Tangga Vertikal + Corong Biru
    feederCart3D = new THREE.Group();

    const ladderMat = new THREE.MeshStandardMaterial({ color: 0x6b7280, metalness: 0.7, roughness: 0.3 });
    const blueHopperMat = new THREE.MeshStandardMaterial({ color: 0x1565c0, metalness: 0.1, roughness: 0.38 });
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
    fanGroups = [];

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
        const strutLen = 3.60 - (yPos + R + 0.045);
        if (strutLen > 0.03) {
            const strut = new THREE.Mesh(
                new THREE.CylinderGeometry(0.018, 0.018, strutLen),
                steelMat
            );
            strut.position.set(0, R + 0.045 + strutLen / 2, 0);
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
        fanGroups.push(fg);
        return fg;
    }

    // 3 unit kipas sirkulasi plafon berjarak 4.5m sepanjang lorong (ketinggian Y = 3.0m)
    // Kipas berjarak seragam 4.5 m, di antara penggantung rel (kelipatan 3 m), tinggi 3.2 m
    createCeilingFanUnit(-6.75, 3.2, 0);
    createCeilingFanUnit(-2.25, 3.2, 0);
    createCeilingFanUnit( 2.25, 3.2, 0);
    createCeilingFanUnit( 6.75, 3.2, 0);
    createCeilingFanUnit( 9.6, 3.2, 0, true); // Exhaust ujung portal
    fixFanRendering();

    // I2. Bangun Deretan Kandang Tetangga Kembar Identik (Unit 2, Unit 3, & Unit 4)
    // Seluruh arsitektur, rangka kuda-kuda, sangkar baterai 2 tingkat, talang kuning, rel kereta pakan,
    // kipas sirkulasi, tandon sprinkler, silo auger, dan ayam GLB di dalamnya SAMA PERSIS dengan Kandang 1!
    (function buildIdenticalNeighborCoops() {
        const envRoot = scene3D.getObjectByName('FarmEnvironment3D');
        if (!envRoot) return;
        const sm = envRoot.userData.sharedMats;
        const twinUnits = [
            { x: 0,     z: -13.6, cartX:  2.6, cartDir:  1, henStride: 2 }, // Kandang Unit 2 (sebelah persis Kandang 1)
            { x: 0,     z: -27.2, cartX: -3.8, cartDir: -1, henStride: 2 }, // Kandang Unit 3 (baris ketiga paralel)
            { x: -32.8, z: -13.6, cartX:  0.4, cartDir:  1, henStride: 3 }  // Kandang Unit 4 (seberang jalan barat)
        ];

        // Atap galvalum bergelombang kembar (sedikit lebih solid dari cutaway aktif namun tetap memperlihatkan rangka & sangkar)
        const twinRoofMat = new THREE.MeshStandardMaterial({
            map: galvalumTex,
            bumpMap: galvalumTex,
            bumpScale: 0.022,
            color: 0xb2becb,
            metalness: 0.58,
            roughness: 0.34,
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0.72,
            depthWrite: false
        });

        // InstancedMesh gabungan untuk 3 kandang kembar (3 x 36 = 108 modul sangkar kawat + 324 bingkai keyhole + 108 talang kuning)
        const modulesPerCoop = CAGE_CONFIG.sides.length * CAGE_CONFIG.columnXs.length * CAGE_CONFIG.tierCenterYs.length;
        const totalTwinModules = twinUnits.length * modulesPerCoop;
        const totalTwinHoles = totalTwinModules * CAGE_CONFIG.chickensPerCage;

        const twinCageWireInst = new THREE.InstancedMesh(cageWireInst.geometry.clone(), cageWireSolidMat, totalTwinModules);
        const twinHoleFrameInst = new THREE.InstancedMesh(feedHoleFrameInst.geometry.clone(), keyholeFrameMat, totalTwinHoles);
        const twinTroughInst = new THREE.InstancedMesh(new THREE.BoxGeometry(1.9, 0.1, 0.25), troughMat, totalTwinModules);
        twinCageWireInst.receiveShadow = true;
        twinTroughInst.receiveShadow = true;
        envRoot.add(twinCageWireInst);
        envRoot.add(twinHoleFrameInst);
        envRoot.add(twinTroughInst);

        const EA = getEggAssets();
        const fanRingMat = new THREE.MeshStandardMaterial({ color: 0x334155, metalness: 0.8, roughness: 0.25 });
        const fanBladeMat = new THREE.MeshStandardMaterial({ color: 0x38bdf8, metalness: 0.2, roughness: 0.4 });
        const stAMat = new THREE.MeshStandardMaterial({ color: 0x10b981 });
        const stBMat = new THREE.MeshStandardMaterial({ color: 0xef4444 });
        const motorMat = new THREE.MeshStandardMaterial({ color: 0xfbbf24, metalness: 0.3 });
        const torenBlueMat = new THREE.MeshStandardMaterial({ color: 0x0284c7, roughness: 0.3 });

        let mCur = 0;
        let hCur = 0;
        let tCur = 0;
        const dm = new THREE.Object3D();
        const henDummy = new THREE.Object3D();

        // InstancedMesh baja struktural gabungan (kolom, kuda-kuda, gording, rangka A) agar draw call super hemat
        const steelBoxMatrices = [];
        const steelCylMatrices = [];
        const addBoxM = (px, py, pz, sx, sy, sz, rx = 0) => {
            dm.position.set(px, py, pz);
            dm.rotation.set(rx, 0, 0);
            dm.scale.set(sx, sy, sz);
            dm.updateMatrix();
            steelBoxMatrices.push(dm.matrix.clone());
        };
        const addCylM = (px, py, pz, r, len, rx = 0, rz = 0) => {
            dm.position.set(px, py, pz);
            dm.rotation.set(rx, 0, rz);
            dm.scale.set(r, len, r);
            dm.updateMatrix();
            steelCylMatrices.push(dm.matrix.clone());
        };

        twinUnits.forEach((unit, uIdx) => {
            const ug = new THREE.Group();
            ug.position.set(unit.x, 0, unit.z);

            // 1. Skirt AO tanah, plint beton, parit drainase cucuran atap, lantai beton, & garis drainase tengah
            const aoSkirt = new THREE.Mesh(new THREE.PlaneGeometry(24.4, 11.6), sm.aoSkirtMat);
            aoSkirt.rotation.x = -Math.PI / 2;
            aoSkirt.position.y = -0.083;
            ug.add(aoSkirt);

            const uPlinth = new THREE.Mesh(new THREE.BoxGeometry(22.6, 0.11, 9.9), sm.kerbMat);
            uPlinth.position.y = -0.058;
            uPlinth.receiveShadow = true;
            ug.add(uPlinth);

            [-4.52, 4.52].forEach((dz) => {
                const db = new THREE.Mesh(new THREE.BoxGeometry(22.4, 0.04, 0.42), sm.ditchWaterMat);
                db.position.set(0, -0.082, dz);
                ug.add(db);
                [-0.22, 0.22].forEach((off) => {
                    const lip = new THREE.Mesh(new THREE.BoxGeometry(22.4, 0.09, 0.07), sm.ditchWallMat);
                    lip.position.set(0, -0.055, dz + off);
                    ug.add(lip);
                });
            });

            const uFloor = new THREE.Mesh(new THREE.PlaneGeometry(22.2, 9.6), floorMat);
            uFloor.rotation.x = -Math.PI / 2;
            uFloor.receiveShadow = true;
            ug.add(uFloor);

            const uDrain = new THREE.Mesh(new THREE.BoxGeometry(22.2, 0.02, 0.12), drainMat);
            uDrain.position.y = 0.011;
            ug.add(uDrain);

            // 2. Curb wall belakang rendah (0.45m), kolom baja + baseplate, kuda-kuda, gording, atap galvalum, nok, & pipa sprinkler
            const uWallBack = new THREE.Mesh(new THREE.BoxGeometry(20.4, 0.45, 0.15), wallMat);
            uWallBack.position.set(0, 0.225, -3.6);
            uWallBack.castShadow = true;
            uWallBack.receiveShadow = true;
            ug.add(uWallBack);

            const rafLen = Math.hypot(1.25, 3.5);
            const rafAng = Math.atan2(3.5, -1.25);
            for (const cx of colPositions) {
                const wx = unit.x + cx;
                [-3.5, 3.5].forEach((cz) => {
                    addCylM(wx, 1.8, unit.z + cz, 0.065, 3.6, 0, 0);
                    addBoxM(wx, 0.012, unit.z + cz, 0.22, 0.024, 0.22);
                });
                addBoxM(wx, 3.60, unit.z, 0.05, 0.05, 7.0);
                addBoxM(wx, 4.225, unit.z, 0.04, 1.25, 0.04);
                addCylM(wx, 4.225, unit.z - 1.75, 0.035, rafLen, -rafAng, 0);
                addCylM(wx, 4.225, unit.z + 1.75, 0.035, rafLen,  rafAng, 0);
                addBoxM(wx, 2.9, unit.z, 0.03, 0.6, 0.84);
            }

            [-2.75, -1.55, 1.55, 2.75].forEach((pz) => {
                const py = 4.85 - (1.25 / 3.7) * Math.abs(pz) - 0.035;
                addBoxM(unit.x, py, unit.z + pz, 20.3, 0.04, 0.04);
            });

            ug.add(createSlopedRoofMesh(-10.2, 10.2, 4.85, 0, 3.60, -3.7, twinRoofMat));
            ug.add(createSlopedRoofMesh(-10.2, 10.2, 4.85, 0, 3.60,  3.7, twinRoofMat));

            const uRidge = new THREE.Mesh(new THREE.CylinderGeometry(0.12, 0.12, 20.4, 8), ridgeCapMat);
            uRidge.rotation.z = Math.PI / 2;
            uRidge.position.set(0, 4.88, 0);
            ug.add(uRidge);

            [-3.7, 3.7].forEach((fz) => {
                addBoxM(unit.x, 3.60, unit.z + fz, 20.4, 0.08, 0.04);
            });
            addCylM(unit.x, 5.02, unit.z, 0.03, 20.4, 0, Math.PI / 2);

            // Tandon air biru & pipa riser sprinkler identik di belakang pangkal (-8.8, -4.0)
            const uToren = new THREE.Mesh(new THREE.CylinderGeometry(0.38, 0.38, 0.96, 14), torenBlueMat);
            uToren.position.set(-8.8, 0.48, -4.0);
            uToren.castShadow = true;
            ug.add(uToren);
            addCylM(unit.x - 8.8, 2.58, unit.z - 3.48, 0.025, 4.88, 0, 0);

            // 3. Rangka A, sangkar baterai 2 tingkat, talang pakan kuning, sabuk kotoran, sabuk telur, & bak pengumpul telur
            const cageFront = CAGE_CONFIG.cageCenterZ - CAGE_CONFIG.cageSize.d / 2;
            const bs = EGG_COLLECTOR.basketSize;
            CAGE_CONFIG.sides.forEach((side) => {
                const zNear = side * 1.3;
                const zFar = side * 2.85;
                const braceLen = Math.hypot(zFar - zNear, 1.9);
                const braceAng = -Math.atan2(1.9, zFar - zNear);
                for (let fx = -9; fx <= 9; fx += 2) {
                    const wfx = unit.x + fx;
                    addBoxM(wfx, 1.05, unit.z + zNear, 0.05, 2.1, 0.05);
                    addBoxM(wfx, 1.05, unit.z + zFar,  0.05, 2.1, 0.05);
                    addBoxM(wfx, 2.05, unit.z + (zNear + zFar) / 2, 0.04, 0.04, Math.abs(zFar - zNear));
                    addBoxM(wfx, 1.05, unit.z + (zNear + zFar) / 2, 0.03, 0.03, braceLen, braceAng);
                }
                CAGE_CONFIG.tierCenterYs.forEach((cy) => {
                    const floorY = cy - CAGE_CONFIG.cageSize.h / 2;
                    const drk = new THREE.Mesh(new THREE.CylinderGeometry(0.012, 0.012, 17.6, 6), drinkerMat);
                    drk.rotation.z = Math.PI / 2;
                    drk.position.set(0, floorY + 0.52, side * 2.58);
                    ug.add(drk);

                    const mBelt = new THREE.Mesh(new THREE.BoxGeometry(17.7, 0.012, 1.18), manureMat);
                    mBelt.position.set(0, floorY - 0.035, side * 2.18);
                    ug.add(mBelt);

                    const beltZ = side * EGG_PATH.beltZ;
                    const beltY = cageBeltTopY(floorY);
                    const beltLen = EGG_PATH.beltEndX - EGG_PATH.beltStartX;
                    const eBelt = new THREE.Mesh(new THREE.BoxGeometry(beltLen, 0.014, 0.12), EA.beltMat);
                    eBelt.position.set((EGG_PATH.beltStartX + EGG_PATH.beltEndX) / 2, beltY - 0.007, beltZ);
                    ug.add(eBelt);

                    const colTray = new THREE.Mesh(new THREE.BoxGeometry(bs.w, bs.h, bs.d), EA.trayMat);
                    colTray.position.set(EGG_COLLECTOR.x, beltY - EGG_COLLECTOR.dropDepth + bs.h / 2 - 0.008, beltZ);
                    ug.add(colTray);
                });

                CAGE_CONFIG.columnXs.forEach((cx, colIdx) => {
                    CAGE_CONFIG.tierCenterYs.forEach((cy) => {
                        const floorY = cy - CAGE_CONFIG.cageSize.h / 2;
                        const rotY = side === -1 ? 0 : Math.PI;

                        dm.position.set(unit.x + cx, cy, unit.z + side * CAGE_CONFIG.cageCenterZ);
                        dm.rotation.set(0, rotY, 0);
                        dm.scale.set(1, 1, 1);
                        dm.updateMatrix();
                        twinCageWireInst.setMatrixAt(mCur++, dm.matrix);

                        for (let k = 0; k < FEED_HOLE.slotXs.length; k += 1) {
                            dm.position.set(unit.x + cx + FEED_HOLE.slotXs[k], floorY + FEED_HOLE.bottomOffset, unit.z + side * cageFront);
                            dm.rotation.set(0, rotY, 0);
                            dm.updateMatrix();
                            twinHoleFrameInst.setMatrixAt(hCur++, dm.matrix);

                            // Isi sangkar kandang tetangga dengan ayam GLB asli (hemat poligon via stride merata)
                            if (((colIdx + k + uIdx) % unit.henStride) === 0) {
                                const isFeedingPose = Math.abs(cx - unit.cartX) < 2.2;
                                const absZ = isFeedingPose ? (cageFront + 0.14) : (1.88 + (k % 2) * 0.28);
                                const hy = cageFloorHeightAtAbsZ(floorY, absZ);
                                henDummy.position.set(unit.x + cx + FEED_HOLE.slotXs[k], hy, unit.z + side * absZ);
                                henDummy.rotation.set(0, rotY + (isFeedingPose ? 0 : ((colIdx - 4) * 0.06)), 0);
                                henDummy.scale.set(1, 1, 1);
                                henDummy.updateMatrix();
                                neighborHenSlots.push({
                                    matrix: henDummy.matrix.clone(),
                                    phase: (uIdx * 2.1) + colIdx * 0.85 + k * 1.4 + (side > 0 ? 0.9 : 0),
                                    pitchBase: isFeedingPose ? 0.22 : (0.04 * (k - 1)),
                                    reachBase: isFeedingPose ? 0.05 : 0.0,
                                    isFeederNear: isFeedingPose
                                });
                            }
                        }

                        dm.position.set(unit.x + cx, floorY, unit.z + side * CAGE_CONFIG.troughZ);
                        dm.rotation.set(0, 0, 0);
                        dm.updateMatrix();
                        twinTroughInst.setMatrixAt(tCur++, dm.matrix);
                    });
                });
            });

            // 4. Rel Kereta Pakan, Stasiun Motor Hijau/Pulley Merah, Kereta Hopper Biru, & 5 Kipas Blower Sirkulasi
            [-0.42, 0.42].forEach((rz) => {
                const rMesh = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 19, 8), railMat);
                rMesh.rotation.z = Math.PI / 2;
                rMesh.position.set(0, 2.6, rz);
                ug.add(rMesh);
            });
            const uStA = new THREE.Mesh(new THREE.CylinderGeometry(0.16, 0.16, 2.6, 10), stAMat);
            uStA.position.set(-8.5, 1.3, 0);
            ug.add(uStA);
            const uMtr = new THREE.Mesh(new THREE.BoxGeometry(0.28, 0.22, 0.38), motorMat);
            uMtr.position.set(-8.5, 2.6, 0);
            ug.add(uMtr);
            const uStB = new THREE.Mesh(new THREE.CylinderGeometry(0.16, 0.16, 2.6, 10), stBMat);
            uStB.position.set(8.5, 1.3, 0);
            ug.add(uStB);

            const twinCart = new THREE.Group();
            [-1.32, 1.32].forEach((cz) => {
                [-0.22, 0.22].forEach((lx) => {
                    const post = new THREE.Mesh(new THREE.BoxGeometry(0.04, 2.2, 0.04), ladderMat);
                    post.position.set(lx, 1.1, cz);
                    twinCart.add(post);
                });
                const hTop = createBlueHopperUnit();
                hTop.position.set(0, 1.58, cz);
                twinCart.add(hTop);
                const hBot = createBlueHopperUnit();
                hBot.position.set(0, 0.70, cz);
                twinCart.add(hBot);
            });
            const cBridge = new THREE.Mesh(new THREE.BoxGeometry(0.1, 0.1, 2.64), ladderMat);
            cBridge.position.set(0, 2.25, 0);
            twinCart.add(cBridge);
            const cHanger = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.32, 0.84), ladderMat);
            cHanger.position.set(0, 2.55, 0);
            twinCart.add(cHanger);
            twinCart.position.x = unit.cartX;
            ug.add(twinCart);
            neighborCarts.push({ mesh: twinCart, dir: unit.cartDir });

            [-6.75, -2.25, 2.25, 6.75, 9.6].forEach((fx, fIdx) => {
                const R = fIdx === 4 ? 0.42 : 0.38;
                const fg = new THREE.Group();
                fg.position.set(fx, 3.2, 0);
                fg.rotation.y = Math.PI / 2;
                fg.add(new THREE.Mesh(new THREE.TorusGeometry(R, 0.045, 8, 20), fanRingMat));
                const blades = new THREE.Group();
                for (let bi = 0; bi < 4; bi += 1) {
                    const bl = new THREE.Mesh(new THREE.BoxGeometry(0.10, R * 0.85, 0.016), fanBladeMat);
                    const ang = (Math.PI / 2) * bi;
                    bl.rotation.z = ang;
                    bl.position.set(Math.cos(ang + Math.PI / 4) * R * 0.4, Math.sin(ang + Math.PI / 4) * R * 0.4, 0);
                    blades.add(bl);
                }
                fg.add(blades);
                ug.add(fg);
                neighborFanBlades.push(blades);
            });

            // 5. Menara Silo Pakan Galvanis + Pipa Ulir Auger tersendiri untuk masing-masing unit kandang kembar
            envRoot.add(createRealisticFeedSilo(unit.x - 12.35, unit.z - 2.05, sm.siloSharedMats, true));
            envRoot.add(ug);
        });

        const twinSteelBoxInst = new THREE.InstancedMesh(new THREE.BoxGeometry(1, 1, 1), steelMat, steelBoxMatrices.length);
        const twinSteelCylInst = new THREE.InstancedMesh(new THREE.CylinderGeometry(1, 1, 1, 10), steelMat, steelCylMatrices.length);
        twinSteelBoxInst.castShadow = true;
        twinSteelCylInst.castShadow = true;
        steelBoxMatrices.forEach((m, i) => twinSteelBoxInst.setMatrixAt(i, m));
        steelCylMatrices.forEach((m, i) => twinSteelCylInst.setMatrixAt(i, m));
        twinSteelBoxInst.instanceMatrix.needsUpdate = true;
        twinSteelCylInst.instanceMatrix.needsUpdate = true;
        envRoot.add(twinSteelBoxInst);
        envRoot.add(twinSteelCylInst);

        twinCageWireInst.instanceMatrix.needsUpdate = true;
        twinHoleFrameInst.instanceMatrix.needsUpdate = true;
        twinTroughInst.instanceMatrix.needsUpdate = true;
    })();

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
    const waterMat = new THREE.PointsMaterial({ color: 0x0284c7, size: 0.16, transparent: true, opacity: 0.94 });
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
    if (isDisposed3D) return;
    rafHandle3D = requestAnimationFrame(animateThreeJS);

    // 1. Animasi Gerak Kereta Pakan (Travelling Hopper Katrol 2 Tingkat - Auto Shuttle Loop)
    if (isFeederRunning3D && feederCart3D) {
        const nowMs = Date.now();
        if (nowMs < feederPauseUntil) {
            // Sedang jeda sebentar di stasiun (Pangkal A atau Ujung B)
            setFeedParticlesVisible(false);
        } else {
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
                feederDirection3D = -1; // Balik arah siap mundur ke A
                feederPauseUntil = nowMs + 1500; // Jeda 1.5 detik di ujung B
                setFeedParticlesVisible(false);
                updateStatusFeederText('Sampai di Ujung B · Jeda 1.5 detik lalu balik arah...');
            }
            // Sampai di Titik A (Pangkal Lorong)
            else if (feederDirection3D === -1 && feederPositionX <= FEEDER_LIMIT_A) {
                feederPositionX = FEEDER_LIMIT_A;
                feederCart3D.position.x = FEEDER_LIMIT_A;
                feederDirection3D = 1; // Balik arah siap maju ke B
                feederPauseUntil = nowMs + 1500; // Jeda 1.5 detik di pangkal A
                setFeedParticlesVisible(false);
                updateStatusFeederText('Sampai di Pangkal A · Jeda 1.5 detik lalu jalan lagi...');
            } else {
                updateStatusFeederText(`Meluncur ${feederDirection3D === 1 ? 'MAJU (A➔B)' : 'MUNDUR (B➔A)'} ${progress}%`);
            }
        }
    } else {
        setFeedParticlesVisible(false);
    }

    // 2. State machine ayam (makan / bertelur) & fisika telur
    const frameNowMs = performance.now();
    const frameDt = lastFrameTimeMs ? Math.min((frameNowMs - lastFrameTimeMs) / 1000, 0.05) : 0.016;
    lastFrameTimeMs = frameNowMs;
    if (farmSkyUniforms) {
        farmSkyUniforms.uTime.value += frameDt;
    }
    updateChickenStates(frameDt, frameNowMs);
    updateEggRoll(frameDt);

    // 3. Animasi Seluruh Kipas Blower Atas & Kandang Tetangga
    if (isFanSpinning3D && fanBladesList.length > 0) {
        for (let i = 0; i < fanBladesList.length; i++) {
            fanBladesList[i].rotation.z += 0.35; // Putaran kencang serempak
        }
    }
    if (neighborFanBlades.length > 0) {
        const nbSpin = isFanSpinning3D ? 0.32 : 0.09;
        for (let i = 0; i < neighborFanBlades.length; i++) {
            neighborFanBlades[i].rotation.z += nbSpin;
        }
    }
    if (isFeederRunning3D && neighborCarts.length > 0) {
        for (let i = 0; i < neighborCarts.length; i++) {
            const nc = neighborCarts[i];
            nc.mesh.position.x += 0.032 * nc.dir;
            if (nc.mesh.position.x >= FEEDER_LIMIT_B) { nc.mesh.position.x = FEEDER_LIMIT_B; nc.dir = -1; }
            if (nc.mesh.position.x <= FEEDER_LIMIT_A) { nc.mesh.position.x = FEEDER_LIMIT_A; nc.dir = 1; }
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
    updatePinLayout();
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
    setCameraPresetUI(tipe);

    const nama = {
        'perspektif': 'Perspektif Isometrik 3D',
        'lorong': 'Lorong Rel Pakan (Tampak Depan)',
        'atas': 'Denah Kandang (Tampak Atas)'
    }[tipe] || tipe;

    if (typeof window.showToast === 'function') {
        window.showToast(`Kamera beralih ke mode ${nama}.`, 'info', 'Sudut Kamera 3D');
    }
}

function setCameraPresetUI(tipe) {
    const presets = ['perspektif', 'lorong', 'atas'];
    presets.forEach(p => {
        const b = document.getElementById(`btn-cam-${p}`);
        if (!b) return;
        if (p === tipe) {
            b.className = 'flex-1 sm:flex-initial px-2.5 sm:px-3 py-1.5 rounded-md text-[11px] font-bold bg-white text-slate-800 shadow-xs border border-slate-200/80 flex items-center justify-center gap-1 cursor-pointer transition';
        } else {
            b.className = 'flex-1 sm:flex-initial px-2.5 sm:px-3 py-1.5 rounded-md text-[11px] font-medium text-slate-500 hover:text-slate-800 hover:bg-white/60 flex items-center justify-center gap-1 cursor-pointer transition';
        }
    });
}

function toggleAutoRotate3D() {
    if (!controls3D) return;
    controls3D.autoRotate = !controls3D.autoRotate;
    const btn = document.getElementById('btn-autorotate-3d');
    if (btn) {
        btn.className = controls3D.autoRotate
            ? 'px-2.5 sm:px-3 py-1.5 rounded-xl text-[11px] sm:text-xs font-bold bg-purple-600 hover:bg-purple-500 text-white shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer border border-purple-400/40'
            : 'px-2.5 sm:px-3 py-1.5 rounded-xl text-[11px] sm:text-xs font-bold bg-slate-900/85 hover:bg-slate-800 text-purple-300 border border-slate-700/80 shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer';
    }

    if (typeof window.showToast === 'function') {
        window.showToast(
            controls3D.autoRotate ? 'Kamera 3D berputar otomatis 360° mengelilingi kandang.' : 'Putaran otomatis kamera dinonaktifkan.',
            'info',
            'Auto-Putar 360°'
        );
    }
}

function toggleLabelsIoT3D() {
    if (!iotLabelsGroup) return;
    isLabelsVisible = !isLabelsVisible;
    iotLabelsGroup.visible = isLabelsVisible;
    setIoTPinsVisible(isLabelsVisible);
    const btn = document.getElementById('btn-labels-iot');
    if (btn) {
        btn.className = isLabelsVisible
            ? 'px-2.5 sm:px-3 py-1.5 rounded-xl text-[11px] sm:text-xs font-bold bg-cyan-600 hover:bg-cyan-500 text-white shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer border border-cyan-400/40'
            : 'px-2.5 sm:px-3 py-1.5 rounded-xl text-[11px] sm:text-xs font-bold bg-slate-900/85 hover:bg-slate-800 text-cyan-300 border border-slate-700/80 shadow-lg transition active:scale-95 flex items-center gap-1.5 cursor-pointer';
    }

    if (typeof window.showToast === 'function') {
        window.showToast(
            isLabelsVisible ? 'Pin label komponen IoT (ESP32, L298N, sensor) aktif ditampilkan.' : 'Pin label komponen IoT disembunyikan.',
            'info',
            'Pin Label IoT'
        );
    }
}

// Pemicu Gerak Kereta 3D & Motor Fisik L298N (Sinkron 2 Arah dengan Kartu Kereta Pakan Rel)
function toggleSimulasiGerakPakan() {
    if (typeof window.ubahSakelar === 'function') {
        isManualFeederActive = false;
        window.ubahSakelar('pakan', !isFeederRunning3D);
        return;
    }

    isManualFeederActive = !isManualFeederActive;
    isFeederRunning3D = isManualFeederActive;

    updateFeederButtonUI(isFeederRunning3D);

    if (isFeederRunning3D) {
        updateStatusFeederText(feederDirection3D === 1 ? 'Meluncur MAJU (A ➔ B)' : 'Meluncur MUNDUR (B ➔ A)');
        if (typeof window.showToast === 'function') {
            window.showToast('Kereta pakan travelling hopper mulai meluncur di rel (Auto-Shuttle A ⇄ B).', 'success', 'Kereta Pakan 3D Aktif');
        }
    } else {
        setFeedParticlesVisible(false);
        updateStatusFeederText('Kereta Dihentikan (Pause Manual)');
        if (typeof window.showToast === 'function') {
            window.showToast('Kereta pakan dihentikan di posisinya saat ini.', 'info', 'Kereta Pakan Dijeda');
        }
    }
}

// Kompatibilitas global untuk kontroler eksternal & konsol
window.setKamera3D = setKamera3D;
window.toggleAutoRotate3D = toggleAutoRotate3D;
window.toggleLabelsIoT3D = toggleLabelsIoT3D;
window.toggleSimulasiGerakPakan = toggleSimulasiGerakPakan;
window.triggerSimulasiGerakPakan = toggleSimulasiGerakPakan;
window.toggleSimulasiPakan3D = toggleSimulasiGerakPakan;

function updateFeederButtonUI(isRunning) {
    const btn = document.getElementById('btn-trigger-pakan-3d');
    const textEl = document.getElementById('text-feeder-btn');
    const subTextEl = document.getElementById('subtext-feeder-btn');
    const iconEl = btn ? btn.querySelector('.material-symbols-outlined') : null;
    if (!btn) return;

    if (isRunning) {
        btn.className = 'px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-md shadow-rose-900/30 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer border border-rose-400/40';
        if (iconEl) {
            iconEl.textContent = 'pause_circle';
            iconEl.className = 'material-symbols-outlined text-[18px] animate-pulse';
        }
        if (textEl) textEl.textContent = 'Hentikan Kereta';
        if (subTextEl) subTextEl.textContent = 'Jeda L298N (Pause)';
    } else {
        btn.className = 'px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-700/20 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer border border-emerald-400/30';
        if (iconEl) {
            iconEl.textContent = 'play_arrow';
            iconEl.className = 'material-symbols-outlined text-[18px]';
        }
        if (textEl) textEl.textContent = 'Jalankan Kereta 3D';
        if (subTextEl) subTextEl.textContent = 'Motor L298N · D18/D19';
    }
}

function resetFeederButtonUI() {
    updateFeederButtonUI(false);
}

function updateStatusFeederText(teks) {
    const el = document.getElementById('status-feeder-3d');
    if (el) el.textContent = teks;
}

// Simulasi Uji Cuaca Panas (Trigger Kipas 12V D26 & Pompa 5V D14 - Mandiri dari poller sensor)
function toggleUjiCuacaPanas3D() {
    isManualSimulationHot = !isManualSimulationHot;
    isFanSpinning3D = isManualSimulationHot;
    isSprinklerActive3D = isManualSimulationHot;

    updateBadgeKipas3D(isFanSpinning3D);
    updateBadgeSprinkler3D(isSprinklerActive3D);
    updateCuacaButtonUI(isManualSimulationHot);

    if (typeof window.showToast === 'function') {
        if (isManualSimulationHot) {
            window.showToast('Simulasi cuaca panas aktif: Kipas DC 12V (D26) berputar & Pompa Celup 5V (D14) menyemprot atap.', 'warning', 'Simulasi Cuaca Panas ON');
        } else {
            window.showToast('Simulasi cuaca panas dimatikan. Kipas & pompa kembali ke status normal.', 'info', 'Simulasi Cuaca Panas OFF');
        }
    }
}

window.toggleUjiCuacaPanas3D = toggleUjiCuacaPanas3D;
window.toggleSimulasiPanas3D = toggleUjiCuacaPanas3D;

function updateCuacaButtonUI(isHot) {
    const btn = document.getElementById('btn-cuaca-3d');
    const textEl = document.getElementById('text-cuaca-btn');
    const subTextEl = document.getElementById('subtext-cuaca-btn');
    const iconEl = document.getElementById('icon-cuaca-btn');
    if (!btn) return;

    if (isHot) {
        btn.className = 'px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-md shadow-amber-500/20 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer border border-amber-300';
        if (iconEl) {
            iconEl.textContent = 'mode_fan';
            iconEl.className = 'material-symbols-outlined text-[18px] animate-spin';
        }
        if (textEl) textEl.textContent = 'Cuaca Panas Aktif';
        if (subTextEl) subTextEl.textContent = 'Kipas & Pompa 3D ON';
    } else {
        btn.className = 'px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700 shadow-md transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer';
        if (iconEl) {
            iconEl.textContent = 'thermostat';
            iconEl.className = 'material-symbols-outlined text-[18px]';
        }
        if (textEl) textEl.textContent = 'Uji Cuaca Panas';
        if (subTextEl) subTextEl.textContent = 'Kipas D26 & Pompa D14';
    }
}

// Kendali & Simulasi Lampu LED Strip 5V (Sinkron 2 Arah dengan Tab Lampu Fotoperiode & Fisik ESP32 D27)
function toggleLampu3D() {
    if (typeof window.ubahSakelar === 'function') {
        isManualLampActive3D = false;
        window.ubahSakelar('lampu', !isLampOn3D);
        return;
    }

    isManualLampActive3D = !isManualLampActive3D;
    isLampOn3D = isManualLampActive3D;

    applyLampu3DVisual(isLampOn3D);
    updateBadgeLampu3D(isLampOn3D);
    updateLampButtonUI(isLampOn3D);

    if (typeof window.showToast === 'function') {
        if (isLampOn3D) {
            window.showToast('Simulasi LED Strip DC 5V aktif: pencahayaan fotoperiode kandang 3D menyala terang.', 'success', 'Uji LED Strip 5V ON');
        } else {
            window.showToast('Simulasi LED Strip DC 5V dimatikan. Lampu kembali mengikuti status Web.', 'info', 'Uji LED Strip 5V OFF');
        }
    }
}

window.toggleLampu3D = toggleLampu3D;

function applyLampu3DVisual(isAktif) {
    ceilingLights.forEach((pl) => { pl.intensity = isAktif ? 0.62 : 0.14; });
    if (coopBulbMat) {
        coopBulbMat.emissiveIntensity = isAktif ? 1.2 : 0.12;
    }
}

function updateLampButtonUI(isAktif) {
    const btn = document.getElementById('btn-lampu-3d');
    const textEl = document.getElementById('text-lampu-btn');
    const subTextEl = document.getElementById('subtext-lampu-btn');
    const iconEl = document.getElementById('icon-lampu-btn');
    if (!btn) return;

    if (isAktif) {
        btn.className = 'px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold bg-yellow-400 hover:bg-yellow-300 text-slate-950 shadow-md shadow-yellow-500/20 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer border border-yellow-200';
        if (iconEl) {
            iconEl.textContent = 'light_mode';
            iconEl.className = 'material-symbols-outlined text-[18px] animate-pulse';
        }
        if (textEl) textEl.textContent = 'LED Strip ON';
        if (subTextEl) subTextEl.textContent = 'Fotoperiode Terang';
    } else {
        btn.className = 'px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-yellow-200 border border-slate-700 shadow-md transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer';
        if (iconEl) {
            iconEl.textContent = 'lightbulb';
            iconEl.className = 'material-symbols-outlined text-[18px]';
        }
        if (textEl) textEl.textContent = 'Uji LED Strip 5V';
        if (subTextEl) subTextEl.textContent = 'L298N IN3/OUT3 · D27';
    }
}

function updateBadgeKipas3D(isAktif) {
    const fanText = document.getElementById('text-fan-3d');
    const fanIcon = document.getElementById('icon-fan-3d');
    if (fanText) fanText.textContent = isAktif ? 'Kipas ON (Berputar)' : 'Kipas OFF';
    if (fanIcon) fanIcon.className = isAktif ? 'material-symbols-outlined text-[13px] text-cyan-400 animate-spin' : 'material-symbols-outlined text-[13px] text-slate-400';
}

function updateBadgeSprinkler3D(isAktif) {
    const roofText = document.getElementById('text-roof-3d');
    const roofIcon = document.getElementById('icon-roof-3d');
    if (roofText) roofText.textContent = isAktif ? 'Sprinkler ON (Menyemprot)' : 'Sprinkler OFF';
    if (roofIcon) roofIcon.className = isAktif ? 'material-symbols-outlined text-[13px] text-cyan-300 animate-bounce' : 'material-symbols-outlined text-[13px] text-slate-400';
}

function updateBadgeLampu3D(isAktif) {
    const lampText = document.getElementById('text-lamp-3d');
    const lampIcon = document.getElementById('icon-lamp-3d');
    if (lampText) lampText.textContent = isAktif ? 'Lampu ON (Terang)' : 'Lampu OFF';
    if (lampIcon) lampIcon.className = isAktif ? 'material-symbols-outlined text-[13px] text-yellow-300 animate-pulse' : 'material-symbols-outlined text-[13px] text-slate-400';
}

// Sinkronisasi Status dari Luar (Misal dari Widget Kontroler Sakelar atau Telemetri IoT)
window.syncKandang3DState = function(state) {
    if (!state) return;

    const kipasVal = state.kipas !== undefined ? state.kipas : state.relay_kipas;
    const mistingVal = state.misting !== undefined ? state.misting : state.relay_misting;
    const lampuVal = state.lampu !== undefined ? state.lampu : state.relay_lampu;
    const pakanVal = state.pakan !== undefined ? state.pakan : state.relay_pakan;

    // Hanya override kipas & sprinkler jika pengguna TIDAK sedang mengaktifkan mode simulasi cuaca panas manual
    if (!isManualSimulationHot) {
        if (kipasVal !== undefined) {
            isFanSpinning3D = Boolean(kipasVal);
            updateBadgeKipas3D(isFanSpinning3D);
        }
        if (mistingVal !== undefined) {
            isSprinklerActive3D = Boolean(mistingVal);
            updateBadgeSprinkler3D(isSprinklerActive3D);
        }
    }

    // Sinkronkan lampu 3D & tombol Uji LED Strip 5V dengan Tab Lampu Fotoperiode
    if (!isManualLampActive3D && lampuVal !== undefined) {
        isLampOn3D = Boolean(lampuVal);
        applyLampu3DVisual(isLampOn3D);
        updateBadgeLampu3D(isLampOn3D);
        updateLampButtonUI(isLampOn3D);
    }

    // Hanya override kereta pakan jika pengguna TIDAK sedang mengaktifkan gerak simulasi manual
    if (!isManualFeederActive) {
        if (pakanVal !== undefined) {
            if (Boolean(pakanVal) && !isFeederRunning3D) {
                isFeederRunning3D = true;
                updateStatusFeederText(feederDirection3D === 1 ? 'Meluncur MAJU (A ➔ B)' : 'Meluncur MUNDUR (B ➔ A)');
                updateFeederButtonUI(true);
            } else if (!Boolean(pakanVal) && isFeederRunning3D) {
                isFeederRunning3D = false;
                setFeedParticlesVisible(false);
                updateFeederButtonUI(false);
            }
        }
    }
};

/** Bebaskan semua sumber daya WebGL, listener, dan loop animasi saat halaman ditinggalkan. */
function disposeKandang3D() {
    if (isDisposed3D) return;
    isDisposed3D = true;
    henLoadToken += 1;
    window.clearTimeout(henHudTimer);
    cancelAnimationFrame(rafHandle3D);
    window.removeEventListener('resize', onWindowResize3D);
    if (controls3D) {
        controls3D.dispose();
        controls3D = null;
    }
    disposeHenInstances();
    if (scene3D) {
        scene3D.traverse((o) => {
            if (o.geometry) o.geometry.dispose();
            const mats = Array.isArray(o.material) ? o.material : (o.material ? [o.material] : []);
            mats.forEach((m) => {
                Object.keys(m).forEach((k) => { if (m[k] && m[k].isTexture) m[k].dispose(); });
                m.dispose();
            });
            if (o.customDepthMaterial) {
                o.customDepthMaterial.dispose();
            }
            if (o.isInstancedMesh && typeof o.dispose === 'function') {
                o.dispose();
            }
        });
    }
    if (farmEnvRT) {
        farmEnvRT.dispose();
        farmEnvRT = null;
    }
    farmSkyUniforms = null;
    coopBulbMat = null;
    EGG_COLLECTOR.stations = [];
    disposeProcHenAssets();
    disposeEggAssets();
    if (iotPinLayer) {
        iotPinLayer.remove();
        iotPinLayer = null;
    }
    if (renderer3D) {
        const canvas = renderer3D.domElement;
        renderer3D.dispose();
        renderer3D.forceContextLoss();
        if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
        renderer3D = null;
    }
    scene3D = null;
    camera3D = null;
    chickensList = [];
    neighborHenSlots = [];
    neighborFanBlades = [];
    neighborCarts = [];
    feedTroughs = [];
    eggPool = [];
    activeEggs = [];
}

window.disposeKandang3D = disposeKandang3D;
window.addEventListener('pagehide', disposeKandang3D);
window.addEventListener('beforeunload', disposeKandang3D);
window.addEventListener('pageshow', (ev) => {
    if (ev.persisted && isDisposed3D) setTimeout(initThreeJSKandang, 50);
});

// Inisialisasi saat DOM siap
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(initThreeJSKandang, 100);
});
</script>
