/**
 * Halaman masuk RansumKu: intro sinematik 3D (ayam → telur → pecah → form login).
 *
 * - Three.js r128 dimuat on-demand dari CDN (sama seperti Digital Twin kandang), hanya saat intro dimainkan.
 * - Seluruh adegan adalah fungsi deterministik dari waktu `t` (satu timeline), tanpa model eksternal:
 *   ayam & telur dibangun secara prosedural (hierarki sendi, forward kinematics).
 * - Jika WebGL / CDN gagal, reduced-motion aktif, atau pengguna menekan "Lewati animasi", form langsung tampil.
 */

const root = document.documentElement;
const STORAGE_KEY = 'rk_login_intro';
const THREE_URL = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
const GLTF_URL = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js';
const stageEl = document.getElementById('lg-stage');
const MODEL_URL = (stageEl && stageEl.dataset && stageEl.dataset.modelUrl) || '/models/chicken.glb';
const EGG_MODEL_URL = (stageEl && stageEl.dataset && stageEl.dataset.eggUrl) || '/models/egg.glb';
const flashEl = document.getElementById('lg-flash');
const skipBtn = document.getElementById('lg-skip');
const emailInput = document.getElementById('email');

let revealed = false;
let activeScene = null;
let cachedChickenGltf = null;

/* ----------------------------------------------------------------------------
 * Utilitas
 * ------------------------------------------------------------------------- */
const clamp01 = (x) => Math.min(1, Math.max(0, x));
const lerp = (a, b, x) => a + (b - a) * x;
const smooth = (x) => {
    const v = clamp01(x);
    return v * v * (3 - 2 * v);
};
const easeOut = (x) => 1 - Math.pow(1 - clamp01(x), 3);

function mulberry32(seed) {
    let a = seed;
    return function () {
        a |= 0;
        a = (a + 0x6d2b79f5) | 0;
        let t = Math.imul(a ^ (a >>> 15), 1 | a);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** Garis waktu tunggal (detik). */
const TL = {
    walk0: 1.2,
    walk1: 2.9,
    layStart: 2.9,
    eggStart: 3.45,
    eggLanded: 4.05,
    layEnd: 4.4,
    step0: 4.4,
    step1: 5.2,
    shake0: 5.2,
    crack0: 5.5,
    crack1: 6.35,
    burst0: 6.4,
    flash0: 6.5,
    reveal: 7.15,
    end: 8.4,
};

/* ----------------------------------------------------------------------------
 * Reveal form
 * ------------------------------------------------------------------------- */
function markDone() {
    try {
        sessionStorage.setItem(STORAGE_KEY, '1');
    } catch (e) {
        /* sessionStorage dapat diblokir; abaikan */
    }
}

function revealForm(fast) {
    if (revealed) {
        return;
    }
    revealed = true;
    root.classList.toggle('lg-fast', !!fast);
    root.classList.add('lg-done');
    markDone();

    if (fast && flashEl) {
        flashEl.style.opacity = '0';
    }

    window.setTimeout(() => {
        if (emailInput && document.activeElement === document.body) {
            emailInput.focus({ preventScroll: true });
        }
    }, fast ? 60 : 900);

    window.setTimeout(disposeScene, fast ? 450 : 1200);

    /* Mulai ornamen 3D ambient & maskot interaktif di atas kartu form */
    window.setTimeout(() => {
        initInteractiveExperience(cachedChickenGltf);
    }, fast ? 80 : 550);
}

function disposeScene() {
    if (activeScene) {
        activeScene.dispose();
        activeScene = null;
    }
    if (stageEl && stageEl.parentNode) {
        stageEl.parentNode.removeChild(stageEl);
    }
    if (flashEl) {
        flashEl.style.opacity = '0';
    }
}

/* ----------------------------------------------------------------------------
 * Form: tombol mata & loading state
 * ------------------------------------------------------------------------- */
function setupForm() {
    const form = document.getElementById('form-masuk');
    const toggle = document.getElementById('toggle-password');
    const password = document.getElementById('password');
    const submit = document.getElementById('btn-masuk');

    if (toggle && password) {
        toggle.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
            toggle.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            toggle.classList.toggle('is-on', show);
            const icon = toggle.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.textContent = show ? 'visibility_off' : 'visibility';
            }
        });
    }

    if (form && submit) {
        form.addEventListener('submit', () => {
            if (!form.checkValidity()) {
                return;
            }
            try {
                sessionStorage.setItem('rk_login_submitted', '1');
            } catch (e) {}
            submit.classList.add('is-loading');
            submit.setAttribute('aria-busy', 'true');
            window.setTimeout(() => {
                submit.disabled = true;
            }, 0);
        });
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                submit.disabled = false;
                submit.classList.remove('is-loading');
                submit.removeAttribute('aria-busy');
            }
        });
    }
}

/* ----------------------------------------------------------------------------
 * Intro 3D
 * ------------------------------------------------------------------------- */
const EGG_H = 0.3;
const EGG_R = 0.235;
const EGG_SPLIT_Y = 0.06;
const EGG_X = -0.9;

function buildIntro(THREE, host, hooks, gltfData, eggGltf) {
    const isMobile = window.matchMedia('(max-width: 768px)').matches || window.matchMedia('(pointer: coarse)').matches;
    let width = window.innerWidth;
    let height = window.innerHeight;

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, isMobile ? 1.5 : 2));
    renderer.setSize(width, height);
    renderer.setClearColor(0xf7f8f3, 1);
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.domElement.style.display = 'block';
    host.appendChild(renderer.domElement);

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf7f8f3);

    const camera = new THREE.PerspectiveCamera(32, width / height, 0.1, 60);

    const disposables = [];
    const track = (obj) => {
        disposables.push(obj);
        return obj;
    };

    /* ---------- Cahaya studio ---------- */
    scene.add(new THREE.HemisphereLight(0xffffff, 0xe3e7da, 0.85));
    const keyLight = new THREE.DirectionalLight(0xfff3e0, 1.05);
    keyLight.position.set(2.5, 6, 4);
    keyLight.target.position.set(-1, 0, 0);
    keyLight.castShadow = true;
    keyLight.shadow.mapSize.set(isMobile ? 1024 : 2048, isMobile ? 1024 : 2048);
    keyLight.shadow.camera.left = -6;
    keyLight.shadow.camera.right = 5;
    keyLight.shadow.camera.top = 5;
    keyLight.shadow.camera.bottom = -5;
    keyLight.shadow.camera.near = 0.5;
    keyLight.shadow.camera.far = 20;
    keyLight.shadow.bias = -0.0006;
    keyLight.shadow.normalBias = 0.02;
    scene.add(keyLight, keyLight.target);

    const fillLight = new THREE.DirectionalLight(0xdfe9ff, 0.3);
    fillLight.position.set(-4, 3, 3);
    const rimLight = new THREE.DirectionalLight(0xfff0d0, 0.45);
    rimLight.position.set(-2, 4, -5);
    scene.add(fillLight, rimLight);

    const emeraldLight = new THREE.PointLight(0x2fd48a, 0, 4.5);
    scene.add(emeraldLight);

    /* ---------- Tekstur prosedural ---------- */
    const makeCanvas = (size) => {
        const c = document.createElement('canvas');
        c.width = size;
        c.height = size;
        return c;
    };

    const featherCanvas = makeCanvas(256);
    {
        const g = featherCanvas.getContext('2d');
        g.fillStyle = '#808080';
        g.fillRect(0, 0, 256, 256);
        const rowH = 16;
        const colW = 24;
        for (let row = 0; row < 18; row++) {
            for (let col = -1; col < 12; col++) {
                const cx = col * colW + (row % 2 ? colW / 2 : 0);
                const cy = row * rowH;
                const grad = g.createRadialGradient(cx, cy - 4, 1, cx, cy, 15);
                grad.addColorStop(0, '#d8d8d8');
                grad.addColorStop(0.7, '#8a8a8a');
                grad.addColorStop(1, '#3c3c3c');
                g.fillStyle = grad;
                g.beginPath();
                g.arc(cx, cy, 14, 0, Math.PI);
                g.fill();
            }
        }
    }
    const featherTex = track(new THREE.CanvasTexture(featherCanvas));
    featherTex.wrapS = THREE.RepeatWrapping;
    featherTex.wrapT = THREE.RepeatWrapping;
    featherTex.repeat.set(3, 2);

    const speckleCanvas = makeCanvas(128);
    {
        const g = speckleCanvas.getContext('2d');
        g.fillStyle = '#a0a0a0';
        g.fillRect(0, 0, 128, 128);
        const rnd = mulberry32(7);
        for (let i = 0; i < 520; i++) {
            const v = 90 + Math.floor(rnd() * 110);
            g.fillStyle = `rgb(${v},${v},${v})`;
            g.fillRect(rnd() * 128, rnd() * 128, 1 + rnd() * 1.5, 1 + rnd() * 1.5);
        }
    }
    const speckleTex = track(new THREE.CanvasTexture(speckleCanvas));
    speckleTex.wrapS = THREE.RepeatWrapping;
    speckleTex.wrapT = THREE.RepeatWrapping;

    const radialTexture = (stops) => {
        const c = makeCanvas(128);
        const g = c.getContext('2d');
        const grad = g.createRadialGradient(64, 64, 0, 64, 64, 64);
        stops.forEach(([o, col]) => grad.addColorStop(o, col));
        g.fillStyle = grad;
        g.fillRect(0, 0, 128, 128);
        return track(new THREE.CanvasTexture(c));
    };

    /* ---------- Lantai panggung ---------- */
    const poolTex = radialTexture([
        [0, 'rgba(255,255,255,1)'],
        [0.55, 'rgba(236,241,233,0.85)'],
        [1, 'rgba(247,248,243,0)'],
    ]);
    const pool = new THREE.Mesh(
        track(new THREE.CircleGeometry(8, 64)),
        track(new THREE.MeshBasicMaterial({ map: poolTex, transparent: true, depthWrite: false })),
    );
    pool.rotation.x = -Math.PI / 2;
    pool.position.set(-0.8, 0, 0);
    scene.add(pool);

    const shadowMat = track(new THREE.ShadowMaterial({ opacity: 0.2 }));
    const shadowFloor = new THREE.Mesh(track(new THREE.PlaneGeometry(40, 40)), shadowMat);
    shadowFloor.rotation.x = -Math.PI / 2;
    shadowFloor.position.y = 0.002;
    shadowFloor.receiveShadow = true;
    scene.add(shadowFloor);

    /* Bayangan kontak lembut (ambient occlusion palsu) di bawah ayam dan telur */
    const aoTex = radialTexture([
        [0, 'rgba(20,35,25,0.6)'],
        [0.55, 'rgba(20,35,25,0.22)'],
        [1, 'rgba(20,35,25,0)'],
    ]);
    const makeAo = () => {
        const mat = track(new THREE.MeshBasicMaterial({ map: aoTex, transparent: true, depthWrite: false, opacity: 0 }));
        const m = new THREE.Mesh(track(new THREE.PlaneGeometry(1, 1)), mat);
        m.rotation.x = -Math.PI / 2;
        m.position.y = 0.004;
        m.renderOrder = 1;
        scene.add(m);
        return m;
    };
    const chickenAo = makeAo();
    const eggAo = makeAo();

    /* ---------- Material ---------- */
    const fadeMats = [];
    const fadeMat = (mat) => {
        mat.transparent = true;
        fadeMats.push(mat);
        return track(mat);
    };

    const legCanvas = makeCanvas(64);
    {
        const g = legCanvas.getContext('2d');
        g.fillStyle = '#9a9a9a';
        g.fillRect(0, 0, 64, 64);
        for (let y = 0; y < 64; y += 6) {
            const grad = g.createLinearGradient(0, y, 0, y + 6);
            grad.addColorStop(0, '#d0d0d0');
            grad.addColorStop(0.8, '#707070');
            grad.addColorStop(1, '#3a3a3a');
            g.fillStyle = grad;
            g.fillRect(0, y, 64, 5);
        }
    }
    const legTex = track(new THREE.CanvasTexture(legCanvas));
    legTex.wrapS = THREE.RepeatWrapping;
    legTex.wrapT = THREE.RepeatWrapping;
    legTex.repeat.set(1, 3);

    const featherMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xfbfaf4, roughness: 0.92, metalness: 0, bumpMap: featherTex, bumpScale: 1.1, vertexColors: true }));
    const combMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xd23a33, roughness: 0.55 }));
    const beakMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xf0b43c, roughness: 0.45 }));
    const legMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xe6a238, roughness: 0.55, bumpMap: legTex, bumpScale: 0.8 }));
    const irisMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xd9822b, roughness: 0.3 }));
    const glintMat = fadeMat(new THREE.MeshBasicMaterial({ color: 0xffffff }));
    const eyeMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0x15110e, roughness: 0.15, metalness: 0.2 }));
    const lidMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xf1efe6, roughness: 0.9 }));

    /* Gradasi warna bulu: punggung putih hangat, perut sedikit keabu-abuan (vertex color). */
    const tintFeather = (geo) => {
        geo.computeBoundingBox();
        const { min, max } = geo.boundingBox;
        const pos = geo.attributes.position;
        const colors = new Float32Array(pos.count * 3);
        for (let i = 0; i < pos.count; i++) {
            const k = clamp01((pos.getY(i) - min.y) / (max.y - min.y || 1));
            const noise = 0.015 * Math.sin(pos.getX(i) * 23.0 + pos.getZ(i) * 17.0);
            const v = lerp(0.84, 1.0, smooth(k)) + noise;
            colors[i * 3] = v;
            colors[i * 3 + 1] = v;
            colors[i * 3 + 2] = v * lerp(0.95, 0.985, k);
        }
        geo.setAttribute('color', new THREE.BufferAttribute(colors, 3));
    };

    const mk = (geo, mat) => {
        if (mat === featherMat) {
            tintFeather(geo);
        }
        const m = new THREE.Mesh(track(geo), mat);
        m.castShadow = true;
        m.receiveShadow = true;
        return m;
    };
    const sphere = (r, ws = 28, hs = 20) => new THREE.SphereGeometry(r, ws, hs);

    /* ---------- Ayam (hierarki sendi) ---------- */
    const chicken = new THREE.Group();
    const torso = new THREE.Group();
    chicken.add(torso);

    const body = mk(sphere(0.5, 40, 28), featherMat);
    body.scale.set(1.2, 0.95, 0.9);
    body.position.set(0, 0.78, 0);
    torso.add(body);

    const breast = mk(sphere(0.32, 28, 20), featherMat);
    breast.scale.set(1, 1, 0.95);
    breast.position.set(0.36, 0.74, 0);
    torso.add(breast);

    const saddle = mk(sphere(0.3, 24, 16), featherMat);
    saddle.scale.set(1.5, 0.7, 1.3);
    saddle.position.set(-0.18, 0.96, 0);
    torso.add(saddle);

    [-1, 0, 1].forEach((i) => {
        const hackle = mk(sphere(0.15, 18, 12), featherMat);
        hackle.scale.set(1, 1.15, 0.9);
        hackle.position.set(0.36, 1.02, i * 0.12);
        torso.add(hackle);
    });

    const wings = [1, -1].map((s) => {
        const wing = mk(sphere(0.4, 28, 18), featherMat);
        wing.scale.set(1, 0.6, 0.2);
        wing.position.set(-0.08, 0.8, s * 0.44);
        wing.rotation.z = 0.12;
        wing.rotation.y = -s * 0.12;
        [1, 2, 3].forEach((k) => {
            const primary = mk(sphere(0.3, 18, 12), featherMat);
            primary.scale.set(0.85, 0.42, 1.8);
            primary.position.set(-0.22 - k * 0.1, -0.05 - k * 0.07, s * 0.35);
            primary.rotation.z = 0.08 * k;
            wing.add(primary);
        });
        torso.add(wing);
        return wing;
    });

    const tail = new THREE.Group();
    tail.position.set(-0.55, 0.86, 0);
    torso.add(tail);
    [-2, -1, 0, 1, 2].forEach((i) => {
        const feather = mk(sphere(0.3, 18, 12), featherMat);
        feather.scale.set(0.5, 1, 0.12);
        feather.position.set(0, 0.22, i * 0.05);
        feather.rotation.x = i * 0.2;
        tail.add(feather);
    });

    const neck = new THREE.Group();
    neck.position.set(0.4, 0.98, 0);
    torso.add(neck);
    const neckMesh = mk(new THREE.CylinderGeometry(0.12, 0.18, 0.42, 18), featherMat);
    neckMesh.position.set(0.06, 0.2, 0);
    neckMesh.rotation.z = -0.28;
    neck.add(neckMesh);

    const head = new THREE.Group();
    head.position.set(0.12, 0.45, 0);
    neck.add(head);
    const skull = mk(sphere(0.17, 28, 20), featherMat);
    skull.scale.set(1.15, 1, 0.88);
    head.add(skull);
    const combBase = mk(sphere(0.05, 16, 10), combMat);
    combBase.scale.set(2.4, 0.5, 0.4);
    combBase.position.set(0, 0.155, 0);
    head.add(combBase);
    [0.04, 0.052, 0.058, 0.05, 0.038].forEach((size, i) => {
        const lobe = mk(sphere(size, 16, 12), combMat);
        lobe.scale.set(0.75, 1.25, 0.4);
        lobe.position.set(-0.075 + i * 0.045, 0.165 + [0, 0.02, 0.035, 0.022, 0.004][i], 0);
        head.add(lobe);
    });
    [1, -1].forEach((s) => {
        const earLobe = mk(sphere(0.03, 14, 10), lidMat);
        earLobe.scale.set(0.8, 1, 0.35);
        earLobe.position.set(-0.01, -0.04, s * 0.15);
        head.add(earLobe);
    });
    const beak = mk(new THREE.ConeGeometry(0.06, 0.17, 18), beakMat);
    beak.rotation.z = -Math.PI / 2;
    beak.position.set(0.22, -0.01, 0);
    head.add(beak);
    const lowerBeak = mk(new THREE.ConeGeometry(0.045, 0.12, 14), beakMat);
    lowerBeak.rotation.z = -Math.PI / 2;
    lowerBeak.position.set(0.19, -0.045, 0);
    head.add(lowerBeak);
    const wattle = mk(sphere(0.05, 14, 10), combMat);
    wattle.scale.set(0.6, 1.9, 0.5);
    wattle.position.set(0.13, -0.16, 0);
    head.add(wattle);
    const lids = [1, -1].map((s) => {
        const eye = mk(sphere(0.032, 16, 12), irisMat);
        eye.position.set(0.11, 0.04, s * 0.14);
        head.add(eye);
        const pupil = mk(sphere(0.02, 12, 10), eyeMat);
        pupil.position.set(0.116, 0.04, s * 0.16);
        head.add(pupil);
        const glint = mk(sphere(0.006, 8, 6), glintMat);
        glint.position.set(0.124, 0.05, s * 0.172);
        head.add(glint);
        const lid = mk(sphere(0.036, 14, 10), lidMat);
        lid.position.copy(eye.position);
        lid.scale.set(1, 0.7, 1);
        lid.visible = false;
        head.add(lid);
        return lid;
    });

    const legs = [1, -1].map((s) => {
        const thigh = mk(sphere(0.13, 18, 14), featherMat);
        thigh.scale.set(1, 1.2, 0.8);
        thigh.position.set(0.04, 0.5, s * 0.21);
        chicken.add(thigh);

        const upper = new THREE.Group();
        upper.position.set(0.04, 0.46, s * 0.2);
        chicken.add(upper);
        const upperMesh = mk(new THREE.CylinderGeometry(0.03, 0.034, 0.22, 12), legMat);
        upperMesh.position.y = -0.11;
        upper.add(upperMesh);

        const lower = new THREE.Group();
        lower.position.y = -0.22;
        upper.add(lower);
        const lowerMesh = mk(new THREE.CylinderGeometry(0.024, 0.03, 0.24, 12), legMat);
        lowerMesh.position.y = -0.12;
        lower.add(lowerMesh);

        const foot = new THREE.Group();
        foot.position.y = -0.24;
        lower.add(foot);
        [-0.35, 0, 0.35].forEach((a) => {
            const toe = mk(new THREE.BoxGeometry(0.17, 0.022, 0.035), legMat);
            toe.position.set(0.07, 0.011, 0);
            const toePivot = new THREE.Group();
            toePivot.rotation.y = a;
            toePivot.add(toe);
            foot.add(toePivot);
        });
        const heel = mk(new THREE.BoxGeometry(0.08, 0.022, 0.035), legMat);
        heel.position.set(-0.04, 0.011, 0);
        foot.add(heel);

        return { upper, lower, foot, side: s };
    });

    scene.add(chicken);

    /* ---------- Ayam GLTF Photorealistic (Jika model tersedia) ---------- */
    let mixer = null;
    let gltfChicken = null;
    let gltfWalkAction = null;
    let gltfIdleAction = null;
    let gltfLayAction = null;
    const gltfMats = [];
    const gltfBones = {};
    let rigBones = null;

    /**
     * Membangun rig skeletal 7-tulang (GPU SkinnedMesh) secara otomatis untuk mesh ayam
     * photogrammetry statis (seperti Object_5 pada chicken.glb) agar dapat berjalan,
     * mematuk, bernapas, dan merunduk bertelur secara mulus tanpa glitch keyframe.
     */
    function buildStaticRiggedChicken(sourceMesh, maxAniso) {
        const rawGeo = sourceMesh.geometry.clone();
        rawGeo.computeBoundingBox();
        const bb = rawGeo.boundingBox;
        const minY = bb.min.y;
        const maxY = bb.max.y;
        const rawH = Math.max(0.1, maxY - minY);

        /* Geser geometri agar ujung cakar menyentuh tepat Y = 0 di ruang lokal mesh. */
        const pos = rawGeo.attributes.position;
        const count = pos.count;
        const skinIndices = new Uint16Array(count * 4);
        const skinWeights = new Float32Array(count * 4);

        for (let i = 0; i < count; i++) {
            const x = pos.getX(i);
            const rawY = pos.getY(i);
            const z = pos.getZ(i);
            const y = rawY - minY;
            pos.setY(i, y);

            /* Normalisasi tinggi ke skala referensi [0 .. 3.87] */
            const yn = (y / rawH) * 3.869;

            /* Pemisahan anatomi kaki kanan (X < 0, langkah depan) vs kaki kiri (X >= 0, langkah belakang) */
            const isRightLeg = z - x > -0.05;
            const legBoneIdx = isRightLeg ? 5 : 6;
            const wy = yn <= 0.16 ? 1 : yn >= 0.90 ? 0 : 1 - smooth((yn - 0.16) / 0.74);
            const wx = yn < 0.74 ? 1 : smooth((Math.abs(x) - 0.12) / 0.18);
            const wLeg = wy * wx;

            const wUpper = 1 - wLeg;
            const fSpine = smooth((yn - 0.82) / 0.38);
            const wRoot = wUpper * (1 - fSpine);
            const wBody = wUpper * fSpine;

            const fTail = smooth((-0.75 - z) / 0.45) * smooth((yn - 1.80) / 0.35);
            const fNeckHead = smooth((yn - 2.45) / 0.35) * smooth((z - 0.55) / 0.35);
            const fHead = smooth((yn + 0.35 * (z - 1.25) - 3.05) / 0.22);

            const wTail = wBody * fTail;
            const wHead = wBody * fNeckHead * fHead;
            const wNeck = wBody * fNeckHead * (1 - fHead);
            const wSpine = wBody * Math.max(0, 1 - fTail - fNeckHead);

            /* Maksimal 3 tulang aktif per verteks (selalu muat di 4 slot vec4 WebGL) */
            const active = [];
            if (wRoot > 1e-5) {
                active.push([0, wRoot]);
            }
            if (wSpine > 1e-5) {
                active.push([1, wSpine]);
            }
            if (wNeck > 1e-5) {
                active.push([2, wNeck]);
            }
            if (wHead > 1e-5) {
                active.push([3, wHead]);
            }
            if (wTail > 1e-5) {
                active.push([4, wTail]);
            }
            if (wLeg > 1e-5) {
                active.push([legBoneIdx, wLeg]);
            }
            active.sort((a, b) => b[1] - a[1]);

            let wSum = 0;
            const slots = Math.min(4, active.length);
            for (let k = 0; k < slots; k++) {
                wSum += active[k][1];
            }
            const invSum = wSum > 0 ? 1 / wSum : 1;
            const base = i * 4;
            for (let k = 0; k < 4; k++) {
                if (k < slots) {
                    skinIndices[base + k] = active[k][0];
                    skinWeights[base + k] = active[k][1] * invSum;
                } else {
                    skinIndices[base + k] = 0;
                    skinWeights[base + k] = 0;
                }
            }
        }

        pos.needsUpdate = true;
        rawGeo.setAttribute('skinIndex', new THREE.BufferAttribute(skinIndices, 4));
        rawGeo.setAttribute('skinWeight', new THREE.BufferAttribute(skinWeights, 4));
        rawGeo.computeBoundingSphere();

        /* Ekstrak tekstur dari material asli (mendukung standar PBR maupun KHR_materials_pbrSpecularGlossiness) */
        const oldMat = Array.isArray(sourceMesh.material) ? sourceMesh.material[0] : sourceMesh.material;
        const diffuseMap =
            (oldMat &&
                (oldMat.map ||
                    oldMat.diffuseMap ||
                    (oldMat.uniforms && oldMat.uniforms.diffuseMap && oldMat.uniforms.diffuseMap.value))) ||
            null;
        const specMap =
            (oldMat &&
                (oldMat.specularMap ||
                    oldMat.glossinessMap ||
                    (oldMat.uniforms &&
                        oldMat.uniforms.specularGlossinessMap &&
                        oldMat.uniforms.specularGlossinessMap.value))) ||
            null;

        if (diffuseMap) {
            diffuseMap.encoding = THREE.LinearEncoding;
            diffuseMap.anisotropy = maxAniso;
            diffuseMap.needsUpdate = true;
        }
        if (specMap) {
            specMap.anisotropy = maxAniso;
        }

        const stdMat = track(
            new THREE.MeshStandardMaterial({
                map: diffuseMap,
                bumpMap: specMap || diffuseMap,
                bumpScale: 0.008,
                roughness: 0.84,
                metalness: 0.02,
                side: THREE.FrontSide,
                skinning: true,
                transparent: true,
                opacity: 1,
            }),
        );
        gltfMats.push(stdMat);

        const skinned = new THREE.SkinnedMesh(track(rawGeo), stdMat);
        skinned.castShadow = true;
        skinned.receiveShadow = true;
        skinned.frustumCulled = false;

        /* Hierarki 7 tulang dalam koordinat lokal mesh */
        const rootBone = new THREE.Bone();
        rootBone.position.set(0, 1.05, 0);

        const spineBone = new THREE.Bone();
        spineBone.position.set(0, 0.85, 0);
        rootBone.add(spineBone);

        const neckBone = new THREE.Bone();
        neckBone.position.set(0, 0.70, 0.95);
        spineBone.add(neckBone);

        const headBone = new THREE.Bone();
        headBone.position.set(0, 0.58, 0.32);
        neckBone.add(headBone);

        const tailBone = new THREE.Bone();
        tailBone.position.set(0, 0.35, -0.85);
        spineBone.add(tailBone);

        const rightFootBone = new THREE.Bone();
        rightFootBone.position.set(-0.38, 0.15, 0.43);

        const leftFootBone = new THREE.Bone();
        leftFootBone.position.set(0.36, 0.15, -0.43);

        skinned.add(rootBone, rightFootBone, leftFootBone);
        skinned.updateMatrixWorld(true);

        const bones = [rootBone, spineBone, neckBone, headBone, tailBone, rightFootBone, leftFootBone];
        const skeleton = new THREE.Skeleton(bones);
        skinned.bind(skeleton, new THREE.Matrix4());

        rigBones = {
            root: rootBone,
            spine: spineBone,
            neck: neckBone,
            head: headBone,
            tail: tailBone,
            rightFoot: rightFootBone,
            leftFoot: leftFootBone,
        };

        const holder = new THREE.Group();
        const targetHeight = 1.22;
        holder.scale.setScalar(targetHeight / rawH);
        holder.rotation.set(0, Math.PI / 2, 0);
        holder.add(skinned);
        return holder;
    }

    if (gltfData && gltfData.scene) {
        gltfChicken = new THREE.Group();
        const model = gltfData.scene;
        const maxAniso = renderer.capabilities ? renderer.capabilities.getMaxAnisotropy() : 4;
        const hasClips = Boolean(gltfData.animations && gltfData.animations.length > 0);

        let primaryMesh = null;
        model.traverse((child) => {
            if (child.isMesh && !primaryMesh) {
                primaryMesh = child;
            }
        });

        if (!hasClips && primaryMesh) {
            const riggedHolder = buildStaticRiggedChicken(primaryMesh, maxAniso);
            gltfChicken.add(riggedHolder);
            scene.add(gltfChicken);
            chicken.visible = false;
        } else {
            model.scale.setScalar(2.55);
            model.rotation.set(0, Math.PI / 2, 0);
            model.position.set(0, 0.01, 0);

            model.traverse((child) => {
                if (child.name === 'CHICKEN_-Spine_01') {
                    gltfBones.spine = child;
                } else if (child.name === 'CHICKEN_-Neck_02') {
                    gltfBones.neck = child;
                } else if (child.name === 'CHICKEN_-Head_05') {
                    gltfBones.head = child;
                } else if (child.name === 'CHICKEN_-Tail_015') {
                    gltfBones.tail = child;
                }
                if (child.isMesh) {
                    child.castShadow = true;
                    child.receiveShadow = true;
                    child.frustumCulled = false;
                    const mats = Array.isArray(child.material) ? child.material : [child.material];
                    mats.forEach((m) => {
                        if (!m) {
                            return;
                        }
                        if (m.map) {
                            m.map.encoding = THREE.LinearEncoding;
                            m.map.anisotropy = maxAniso;
                            m.map.needsUpdate = true;
                        }
                        if (m.normalMap) {
                            m.normalMap.anisotropy = maxAniso;
                        }
                        m.alphaTest = 0.32;
                        m.depthWrite = true;
                        m.transparent = true;
                        m.side = THREE.DoubleSide;
                        m.roughness = 0.78;
                        m.metalness = 0.02;
                        m.needsUpdate = true;
                        gltfMats.push(m);
                    });
                }
            });

            gltfChicken.add(model);
            scene.add(gltfChicken);
            chicken.visible = false;

            if (hasClips) {
                mixer = new THREE.AnimationMixer(model);
                const rawClip = gltfData.animations[0];

                const walkClip = rawClip.clone();
                walkClip.name = 'rk_walk';
                const idleClip = rawClip.clone();
                idleClip.name = 'rk_idle';
                const layClip = rawClip.clone();
                layClip.name = 'rk_lay';

                gltfWalkAction = mixer.clipAction(walkClip);
                gltfIdleAction = mixer.clipAction(idleClip);
                gltfLayAction = mixer.clipAction(layClip);

                gltfWalkAction.play();
                gltfIdleAction.play();
                gltfLayAction.play();
            }
        }
    }

    /* ---------- Telur (Mendukung egg.glb Photorealistic Coklat + Pemotongan Menetas Otomatis) ---------- */
    const maxAnisoEgg = renderer.capabilities ? renderer.capabilities.getMaxAnisotropy() : 4;
    let eggSourceMesh = null;
    if (eggGltf && eggGltf.scene) {
        eggGltf.scene.updateMatrixWorld(true);
        eggGltf.scene.traverse((child) => {
            if (child.isMesh && !eggSourceMesh) {
                eggSourceMesh = child;
            }
        });
    }

    let normEggGeo = null;
    let eggProfileBins = null;
    let eggColorMap = null;
    let eggNormalMap = null;

    if (eggSourceMesh && eggSourceMesh.geometry) {
        normEggGeo = eggSourceMesh.geometry.clone();
        normEggGeo.applyMatrix4(eggSourceMesh.matrixWorld);
        if (normEggGeo.attributes.tangent) {
            normEggGeo.deleteAttribute('tangent');
        }
        normEggGeo.computeBoundingBox();
        const ebb = normEggGeo.boundingBox;
        const ecx = (ebb.min.x + ebb.max.x) * 0.5;
        const ecy = (ebb.min.y + ebb.max.y) * 0.5;
        const ecz = (ebb.min.z + ebb.max.z) * 0.5;
        const halfH = Math.max(0.01, (ebb.max.y - ebb.min.y) * 0.5);
        const eggScale = EGG_H / halfH;

        const ePos = normEggGeo.attributes.position;
        const BINS = 32;
        const binSum = new Float32Array(BINS);
        const binCnt = new Uint16Array(BINS);

        for (let i = 0; i < ePos.count; i++) {
            const x = (ePos.getX(i) - ecx) * eggScale;
            const y = (ePos.getY(i) - ecy) * eggScale;
            const z = (ePos.getZ(i) - ecz) * eggScale;
            ePos.setXYZ(i, x, y, z);

            const uBin = clamp01((y + EGG_H) / (2 * EGG_H));
            const bIdx = Math.min(BINS - 1, Math.floor(uBin * BINS));
            binSum[bIdx] += Math.hypot(x, z);
            binCnt[bIdx]++;
        }
        ePos.needsUpdate = true;
        normEggGeo.normalizeNormals();
        normEggGeo.computeBoundingSphere();

        eggProfileBins = new Float32Array(BINS);
        for (let b = 0; b < BINS; b++) {
            const yMid = -EGG_H + ((b + 0.5) / BINS) * (2 * EGG_H);
            const th = Math.acos(Math.max(-1, Math.min(1, -yMid / EGG_H)));
            const fallbackR = EGG_R * Math.sin(th);
            eggProfileBins[b] = binCnt[b] > 0 ? binSum[b] / binCnt[b] : fallbackR;
        }

        const oldEggMat = Array.isArray(eggSourceMesh.material) ? eggSourceMesh.material[0] : eggSourceMesh.material;
        if (oldEggMat) {
            eggColorMap =
                oldEggMat.map ||
                oldEggMat.diffuseMap ||
                (oldEggMat.uniforms && oldEggMat.uniforms.diffuseMap && oldEggMat.uniforms.diffuseMap.value) ||
                null;
            eggNormalMap = oldEggMat.normalMap || null;
        }
        if (eggColorMap) {
            eggColorMap.encoding = THREE.LinearEncoding;
            eggColorMap.anisotropy = maxAnisoEgg;
            eggColorMap.needsUpdate = true;
        }
        if (eggNormalMap) {
            eggNormalMap.anisotropy = maxAnisoEgg;
            eggNormalMap.needsUpdate = true;
        }
    }

    const eggRadiusAtY = (y) => {
        if (eggProfileBins) {
            const BINS = eggProfileBins.length;
            const u = clamp01((y + EGG_H) / (2 * EGG_H)) * (BINS - 1);
            const i0 = Math.floor(u);
            const i1 = Math.min(BINS - 1, i0 + 1);
            return lerp(eggProfileBins[i0], eggProfileBins[i1], u - i0);
        }
        const theta = Math.acos(Math.max(-1, Math.min(1, -y / EGG_H)));
        return EGG_R * Math.sin(theta) * (1 + 0.14 * Math.cos(theta));
    };
    const eggProfile = (from, to, count) => {
        const pts = [];
        for (let i = 0; i < count; i++) {
            const th = lerp(from, to, i / (count - 1));
            pts.push(new THREE.Vector2(EGG_R * Math.sin(th) * (1 + 0.14 * Math.cos(th)), -EGG_H * Math.cos(th)));
        }
        return pts;
    };
    const thetaSplit = Math.acos(-EGG_SPLIT_Y / EGG_H);
    const jag = (phi) => {
        const wrapped = ((phi % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);
        const s = ((wrapped / (Math.PI * 2)) * 9) % 1;
        const tri = 1 - Math.abs(2 * s - 1);
        return (tri - 0.5) * 0.07;
    };
    const shapeEgg = (geo) => {
        const pos = geo.attributes.position;
        const nor = geo.attributes.normal;
        for (let i = 0; i < pos.count; i++) {
            let y = pos.getY(i);
            const x = pos.getX(i);
            const z = pos.getZ(i);
            if (Math.abs(y - EGG_SPLIT_Y) < 1e-5) {
                y += jag(Math.atan2(x, z));
                pos.setY(i, y);
            }
            const nx = x / (EGG_R * EGG_R);
            const ny = y / (EGG_H * EGG_H);
            const nz = z / (EGG_R * EGG_R);
            const l = Math.hypot(nx, ny, nz) || 1;
            nor.setXYZ(i, nx / l, ny / l, nz / l);
        }
        pos.needsUpdate = true;
        nor.needsUpdate = true;
    };

    const shellMat = track(
        new THREE.MeshPhysicalMaterial(
            eggColorMap
                ? {
                      clearcoat: 0.16,
                      clearcoatRoughness: 0.38,
                      color: 0xffffff,
                      map: eggColorMap,
                      normalMap: eggNormalMap,
                      normalScale: new THREE.Vector2(0.65, 0.65),
                      roughness: 0.44,
                      metalness: 0,
                      side: THREE.DoubleSide,
                      emissive: 0x000000,
                  }
                : {
                      clearcoat: 0.18,
                      clearcoatRoughness: 0.35,
                      color: 0xd89b6a,
                      roughness: 0.42,
                      metalness: 0,
                      bumpMap: speckleTex,
                      bumpScale: 0.25,
                      side: THREE.DoubleSide,
                      emissive: 0x000000,
                  },
        ),
    );

    const egg = new THREE.Group();
    scene.add(egg);

    const rSplit = eggRadiusAtY(EGG_SPLIT_Y);
    let petals = [];

    if (normEggGeo && normEggGeo.index) {
        /* Partisi segitiga egg.glb secara presisi ke cangkang bawah (eggBase) dan 4 kelopak atas (petals).
         * Saat telur masih utuh (bt < 0), kelima bagian menyatu sempurna 100% tanpa celah seam;
         * saat telur pecah (bt >= 0), kelopak terbelah mengikuti patahan zig-zag organik. */
        const srcIdx = normEggGeo.index.array;
        const ePos = normEggGeo.attributes.position;
        const baseIndices = [];
        const petalIndices = [[], [], [], []];

        for (let i = 0; i < srcIdx.length; i += 3) {
            const i0 = srcIdx[i];
            const i1 = srcIdx[i + 1];
            const i2 = srcIdx[i + 2];
            const mx = (ePos.getX(i0) + ePos.getX(i1) + ePos.getX(i2)) / 3;
            const my = (ePos.getY(i0) + ePos.getY(i1) + ePos.getY(i2)) / 3;
            const mz = (ePos.getZ(i0) + ePos.getZ(i1) + ePos.getZ(i2)) / 3;
            const phi = ((Math.atan2(mx, mz) % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);
            const ySplit = EGG_SPLIT_Y + jag(phi);

            if (my < ySplit) {
                baseIndices.push(i0, i1, i2);
            } else {
                const phiJagged = ((phi + 0.07 * Math.sin(my * 28)) % (Math.PI * 2) + Math.PI * 2) % (Math.PI * 2);
                const k = Math.min(3, Math.floor((phiJagged / (Math.PI * 2)) * 4));
                petalIndices[k].push(i0, i1, i2);
            }
        }

        const baseGeo = normEggGeo.clone();
        baseGeo.setIndex(baseIndices);
        const eggBase = mk(baseGeo, shellMat);
        egg.add(eggBase);

        petals = [0, 1, 2, 3].map((k) => {
            const phiStart = (k * Math.PI) / 2;
            const pGeo = normEggGeo.clone();
            pGeo.setIndex(petalIndices[k]);
            const mesh = mk(pGeo, shellMat);
            const phiC = phiStart + Math.PI / 4;
            const dir = new THREE.Vector3(Math.sin(phiC), 0, Math.cos(phiC));
            const pivotPos = new THREE.Vector3(dir.x * rSplit, EGG_SPLIT_Y, dir.z * rSplit);
            const axis = new THREE.Vector3(Math.cos(phiC), 0, -Math.sin(phiC));
            const pivot = new THREE.Group();
            pivot.position.copy(pivotPos);
            mesh.position.copy(pivotPos).multiplyScalar(-1);
            pivot.add(mesh);
            egg.add(pivot);
            return { pivot, dir, axis, pivotPos, power: [1, 0.85, 1.15, 0.95][k] };
        });
    } else {
        const eggBase = mk(new THREE.LatheGeometry(eggProfile(0, thetaSplit, 24), 40), shellMat);
        shapeEgg(eggBase.geometry);
        egg.add(eggBase);

        petals = [0, 1, 2, 3].map((k) => {
            const phiStart = (k * Math.PI) / 2;
            const geo = new THREE.LatheGeometry(eggProfile(thetaSplit, Math.PI, 28), 14, phiStart, Math.PI / 2);
            shapeEgg(geo);
            const mesh = mk(geo, shellMat);
            const phiC = phiStart + Math.PI / 4;
            const dir = new THREE.Vector3(Math.sin(phiC), 0, Math.cos(phiC));
            const pivotPos = new THREE.Vector3(dir.x * rSplit, EGG_SPLIT_Y, dir.z * rSplit);
            const axis = new THREE.Vector3(Math.cos(phiC), 0, -Math.sin(phiC));
            const pivot = new THREE.Group();
            pivot.position.copy(pivotPos);
            mesh.position.copy(pivotPos).multiplyScalar(-1);
            pivot.add(mesh);
            egg.add(pivot);
            return { pivot, dir, axis, pivotPos, power: [1, 0.85, 1.15, 0.95][k] };
        });
    }

    /* Retakan: tabung tipis dengan draw-range progresif */
    const crackMat = track(new THREE.MeshBasicMaterial({ color: 0x4a3b2a }));
    const cracks = [];
    const addCrack = (points, radius = 0.0045) => {
        const curve = new THREE.CatmullRomCurve3(points, false, 'catmullrom', 0.5);
        const tubular = 140;
        const radial = 5;
        const geo = track(new THREE.TubeGeometry(curve, tubular, radius, radial, false));
        const mesh = new THREE.Mesh(geo, crackMat);
        egg.add(mesh);
        cracks.push({ mesh, geo, tubular, radial });
    };
    {
        const ring = [];
        const N = 90;
        for (let i = 0; i <= N; i++) {
            const phi = (i / N) * Math.PI * 2;
            const y = EGG_SPLIT_Y + jag(phi);
            const r = eggRadiusAtY(y) * 1.012;
            ring.push(new THREE.Vector3(r * Math.sin(phi), y, r * Math.cos(phi)));
        }
        addCrack(ring, 0.0048);

        const rnd = mulberry32(21);
        const branch = (phi0, thFrom, thTo, steps) => {
            const pts = [];
            let phi = phi0;
            for (let j = 0; j <= steps; j++) {
                const th = lerp(thFrom, thTo, j / steps);
                phi += (rnd() - 0.5) * 0.14;
                const y = -EGG_H * Math.cos(th);
                const r = eggRadiusAtY(y) * 1.012;
                pts.push(new THREE.Vector3(r * Math.sin(phi), y, r * Math.cos(phi)));
            }
            addCrack(pts, 0.0042);
        };
        branch(0.7, thetaSplit, Math.PI * 0.82, 16);
        branch(2.6, thetaSplit, Math.PI * 0.78, 16);
        branch(4.6, thetaSplit, Math.PI * 0.84, 16);
        branch(1.7, thetaSplit, thetaSplit - 0.55, 8);
        branch(3.9, thetaSplit, thetaSplit - 0.5, 8);
    }

    /* Cahaya dalam telur */
    const orbMat = track(new THREE.MeshBasicMaterial({ color: 0xbdf77a, transparent: true, opacity: 0 }));
    const orb = new THREE.Mesh(track(sphere(0.17, 24, 16)), orbMat);
    orb.position.set(0, EGG_SPLIT_Y - 0.08, 0);
    egg.add(orb);

    const haloTex = radialTexture([
        [0, 'rgba(198,243,106,0.85)'],
        [0.35, 'rgba(47,212,138,0.45)'],
        [1, 'rgba(8,123,80,0)'],
    ]);
    const haloMat = track(new THREE.SpriteMaterial({ map: haloTex, transparent: true, opacity: 0, depthWrite: false, depthTest: false }));
    const halo = new THREE.Sprite(haloMat);
    halo.position.set(0, EGG_SPLIT_Y + 0.05, 0.1);
    halo.renderOrder = 10;
    egg.add(halo);

    /* Serpihan kecil (lintasan balistik analitik, bukan mesin fisika) */
    const shardRnd = mulberry32(99);
    const shardMat = shellMat;
    const shards = [];
    for (let i = 0; i < 8; i++) {
        const mesh = mk(new THREE.TetrahedronGeometry(0.03 + shardRnd() * 0.025), shardMat);
        const ang = shardRnd() * Math.PI * 2;
        mesh.visible = false;
        scene.add(mesh);
        shards.push({
            mesh,
            start: new THREE.Vector3(EGG_X + Math.sin(ang) * rSplit, EGG_H + EGG_SPLIT_Y, Math.cos(ang) * rSplit),
            vel: new THREE.Vector3(Math.sin(ang) * (0.6 + shardRnd() * 0.9), 1.1 + shardRnd() * 1.1, Math.cos(ang) * (0.6 + shardRnd() * 0.9)),
            spin: new THREE.Vector3(shardRnd() * 8, shardRnd() * 8, shardRnd() * 8),
        });
    }

    /* Partikel halus (jumlah terbatas) */
    const PCOUNT = 40;
    const pRnd = mulberry32(5);
    const pDirs = [];
    const pPos = new Float32Array(PCOUNT * 3);
    const pCol = new Float32Array(PCOUNT * 3);
    const palette = [new THREE.Color(0x087b50), new THREE.Color(0x2fd48a), new THREE.Color(0x8fe05a)];
    for (let i = 0; i < PCOUNT; i++) {
        const a = pRnd() * Math.PI * 2;
        const e = 0.25 + pRnd() * 0.95;
        const sp = 0.5 + pRnd() * 1.1;
        pDirs.push(new THREE.Vector3(Math.cos(a) * Math.cos(e) * sp, Math.sin(e) * sp, Math.sin(a) * Math.cos(e) * sp));
        const c = palette[i % 3];
        pCol.set([c.r, c.g, c.b], i * 3);
    }
    const dotTex = radialTexture([
        [0, 'rgba(255,255,255,1)'],
        [0.5, 'rgba(255,255,255,0.7)'],
        [1, 'rgba(255,255,255,0)'],
    ]);
    const pGeo = track(new THREE.BufferGeometry());
    pGeo.setAttribute('position', new THREE.BufferAttribute(pPos, 3));
    pGeo.setAttribute('color', new THREE.BufferAttribute(pCol, 3));
    const pMat = track(new THREE.PointsMaterial({ size: 0.085, map: dotTex, vertexColors: true, transparent: true, opacity: 0, depthWrite: false }));
    const particles = new THREE.Points(pGeo, pMat);
    particles.frustumCulled = false;
    particles.visible = false;
    scene.add(particles);

    /* ---------- Parameter responsif ---------- */
    let aspect = width / height;
    const startXFor = (a) => -(1.4 + 1.1 * Math.min(a, 1.8));
    let startX = startXFor(aspect);

    /* ---------- Render sebuah frame pada waktu t ---------- */
    const tmpV = new THREE.Vector3();
    const projV = new THREE.Vector3();

    function poseChicken(t) {
        const fade = smooth(t / 0.9);
        const exitFade = 1 - smooth((t - 4.6) / 0.9);
        const chickenFade = fade * exitFade;
        const walkU = clamp01((t - TL.walk0) / (TL.walk1 - TL.walk0));
        const stepU = clamp01((t - TL.step0) / (TL.step1 - TL.step0));
        let x = startX * (1 - smooth(walkU)) + 3.2 * smooth(stepU);

        const env = (t0, t1) => {
            if (t < t0 || t > t1) {
                return 0;
            }
            return smooth(Math.min((t - t0) / 0.3, (t1 - t) / 0.35));
        };
        const walkW = Math.max(env(TL.walk0, TL.walk1), env(TL.step0, TL.step1));
        const layW = smooth((t - TL.layStart) / 0.4) * (1 - smooth((t - 4.1) / 0.3));

        if (gltfChicken) {
            gltfChicken.visible = chickenFade > 0.005;
            gltfMats.forEach((m) => {
                m.opacity = chickenFade;
                m.transparent = chickenFade < 0.99;
            });
            shadowMat.opacity = 0.22 * chickenFade;

            const effort = smooth((t - 3.4) / 0.2) * (1 - smooth((t - 4.1) / 0.25));
            const idle = (1 - walkW) * (1 - layW);
            const pulse = (center, spread) => Math.exp(-Math.pow((t - center) / spread, 2));
            const peck = Math.max(pulse(0.55, 0.1), pulse(0.85, 0.1)) * idle;

            if (rigBones) {
                const phase = (x - startX) * ((Math.PI * 2) / 0.82);
                const strideAmp = 0.40;

                /* Posisi langkah kaki di ruang lokal mesh (+Z = maju, -Z = mundur).
                 * Saat berdiri/merunduk (walkW = 0), kedua kaki dirapatkan sejajar di bawah tubuh. */
                const zR = 0.08 * (1 - walkW) + Math.sin(phase) * strideAmp * walkW;
                const zL = -0.08 * (1 - walkW) - Math.sin(phase) * strideAmp * walkW;
                const liftR = Math.max(0, Math.cos(phase)) * 0.30 * walkW;
                const liftL = Math.max(0, -Math.cos(phase)) * 0.30 * walkW;

                rigBones.rightFoot.position.set(-0.38, 0.15 + liftR, zR);
                rigBones.leftFoot.position.set(0.36, 0.15 + liftL, zL);
                rigBones.rightFoot.rotation.set(liftR * 0.75, 0, 0);
                rigBones.leftFoot.rotation.set(liftL * 0.75, 0, 0);

                /* Pantulan tubuh saat berjalan & gerakan merunduk (squat) saat bertelur.
                 * Karena rightFoot & leftFoot terpisah dari root, telapak kaki tetap menapak di y = 0. */
                const walkBounce = Math.cos(phase * 2) * 0.045 * walkW;
                const breathY = Math.sin(t * 2.3) * 0.014 * (1 - walkW);
                const squatY = 0.36 * layW + 0.08 * effort;
                rigBones.root.position.set(
                    Math.sin(phase) * 0.025 * walkW,
                    1.05 + walkBounce + breathY - squatY,
                    0.06 * peck - 0.05 * layW,
                );

                /* Torso (spine): goyangan alami, napas halus, condong saat mematuk & bertelur */
                rigBones.spine.rotation.set(
                    0.18 * peck + 0.04 * walkW - 0.14 * layW - 0.06 * effort,
                    Math.sin(phase) * 0.03 * walkW,
                    Math.sin(phase) * 0.04 * walkW + Math.sin(t * 20) * 0.015 * effort,
                );
                rigBones.spine.scale.set(
                    1 + 0.035 * layW + Math.sin(t * 20) * 0.018 * effort,
                    1 + Math.sin(t * 2.4) * 0.012 + 0.02 * layW,
                    1 + 0.025 * layW,
                );

                /* Leher & kepala: anggukan kepala khas ayam saat berjalan (head-bobbing),
                 * mematuk lembut saat muncul, dan menoleh penasaran. */
                const bob = Math.sin(phase * 2 - 0.45) * 0.12 * walkW;
                rigBones.neck.position.set(0, 0.70, 0.95 + Math.sin(phase * 2 - 0.45) * 0.06 * walkW);
                rigBones.neck.rotation.set(
                    bob + 0.42 * peck - 0.08 * layW + Math.sin(t * 1.8) * 0.03 * idle,
                    Math.sin(t * 1.4) * 0.08 * idle * (1 - peck),
                    0,
                );

                rigBones.head.rotation.set(
                    -bob * 0.85 + 0.28 * peck + 0.12 * layW + Math.sin(t * 2.1) * 0.035 * idle,
                    Math.sin(t * 1.5) * 0.26 * idle * (1 - peck) + Math.sin(t * 2.2) * 0.28 * layW * (1 - effort),
                    Math.sin(t * 1.9) * 0.06 * idle * (1 - peck),
                );

                /* Ekor: terangkat saat merunduk bertelur agar telur keluar secara natural dari bawah ekor */
                rigBones.tail.rotation.set(
                    0.34 * layW + 0.12 * effort + Math.sin(phase * 2) * 0.05 * walkW + Math.sin(t * 2.8) * 0.03,
                    Math.sin(phase) * 0.04 * walkW,
                    0,
                );

                gltfChicken.position.set(x, 0, 0);
                gltfChicken.rotation.set(0, -0.22 * layW, 0);
            } else if (mixer && gltfWalkAction && gltfIdleAction && gltfLayAction) {
                const stridePhase = (((x - startX) / 0.78) % 1 + 1) % 1;
                gltfWalkAction.time = 1.3 + stridePhase * 1.0;
                gltfIdleAction.time = 0.0;
                gltfLayAction.time = 3.0;

                const wWalk = walkW;
                const wLay = (1 - walkW) * layW;
                const wIdle = Math.max(0.0001, 1 - wWalk - wLay);

                gltfWalkAction.setEffectiveWeight(wWalk);
                gltfLayAction.setEffectiveWeight(wLay);
                gltfIdleAction.setEffectiveWeight(wIdle);

                mixer.update(1e-6);

                if (gltfBones.spine) {
                    gltfBones.spine.rotation.z += Math.sin(t * 2.4) * 0.025 * (1 - walkW) - 0.08 * effort;
                }
                if (gltfBones.neck) {
                    gltfBones.neck.rotation.z += Math.sin(t * 1.8) * 0.04 * idle - 0.35 * peck;
                }
                if (gltfBones.head) {
                    gltfBones.head.rotation.y += Math.sin(t * 1.5) * 0.25 * idle * (1 - peck) + Math.sin(t * 2.2) * 0.3 * layW * (1 - effort);
                    gltfBones.head.rotation.z += -0.3 * peck + 0.12 * layW;
                }
                if (gltfBones.tail) {
                    gltfBones.tail.rotation.z += 0.28 * layW + Math.sin(t * 24) * 0.05 * effort;
                }

                gltfChicken.position.set(x, -0.04 * layW, 0);
                gltfChicken.rotation.y = -0.22 * layW;
                gltfChicken.rotation.z = -0.04 * layW;
            }

            chickenAo.position.x = x - 0.05;
            chickenAo.scale.set(1.9 + 0.25 * layW, 1.15, 1);
            chickenAo.material.opacity = 0.85 * chickenFade;
            chickenAo.visible = chickenFade > 0.005;
            return;
        }

        chicken.visible = chickenFade > 0.005;
        fadeMats.forEach((m) => {
            m.opacity = chickenFade;
        });
        shadowMat.opacity = 0.2 * chickenFade;

        const phase = (x - startX) * ((Math.PI * 2) / 0.9);

        chicken.position.set(x, 0, 0);
        chicken.rotation.y = -0.3 * layW;

        const A = 0.55;
        legs.forEach((leg) => {
            const ph = phase + (leg.side > 0 ? 0 : Math.PI);
            const swing = Math.sin(ph) * A * walkW;
            const lift = Math.max(0, Math.cos(ph)) * 0.75 * walkW;
            leg.upper.rotation.z = swing + layW * 0.95;
            leg.lower.rotation.z = -lift - layW * 1.8;
            leg.foot.rotation.z = -(leg.upper.rotation.z + leg.lower.rotation.z);
        });

        const effort = smooth((t - 3.4) / 0.2) * (1 - smooth((t - 4.1) / 0.25));
        torso.position.y = Math.cos(phase * 2) * 0.015 * walkW + Math.sin(t * 2.2) * 0.006;
        torso.rotation.z = -0.14 * layW - 0.03 * walkW;
        torso.rotation.x = Math.sin(t * 26) * 0.022 * effort + Math.sin(phase) * 0.035 * walkW;
        torso.scale.set(
            1 + 0.03 * layW,
            1 + Math.sin(t * 2.4) * 0.012 + 0.03 * layW,
            1 + 0.04 * layW + Math.sin(t * 20) * 0.02 * effort,
        );
        tail.rotation.z = 0.55 + 0.35 * layW + Math.sin(t * 3.2) * 0.05 + Math.sin(t * 24) * 0.05 * effort;
        const flapEnv = env(TL.step0 + 0.05, TL.step0 + 0.55);
        wings.forEach((w, i) => {
            const side = i === 0 ? 1 : -1;
            w.rotation.z = 0.12 + 0.05 * effort * Math.sin(t * 22);
            w.rotation.x = side * 0.55 * flapEnv * Math.abs(Math.sin(t * 24));
        });

        const pulse = (center, spread) => Math.exp(-Math.pow((t - center) / spread, 2));
        const peck = Math.max(pulse(0.55, 0.09), pulse(0.82, 0.09), pulse(1.0, 0.08)) * (1 - walkW) * (1 - layW);
        neck.position.x = 0.4 + 0.05 * Math.sin(phase + 0.6) * walkW + 0.04 * peck;
        neck.rotation.z = -0.1 * layW + 0.04 * Math.sin(t * 1.7) - 0.75 * peck;
        const idle = (1 - walkW) * (1 - layW);
        head.rotation.y = Math.sin(t * 1.4) * 0.32 * idle * (1 - peck) + Math.sin(t * 2.3) * 0.4 * layW * (1 - effort);
        head.rotation.z = Math.sin(t * 2.1) * 0.05 * idle + 0.18 * layW - 0.55 * peck;
        const blink = t % 2.6 < 0.12;
        lids.forEach((lid) => {
            lid.visible = blink;
        });

        chicken.position.y = 0;
        legs.forEach((leg) => leg.foot.updateWorldMatrix(true, false));
        let minY = Infinity;
        legs.forEach((leg) => {
            leg.foot.getWorldPosition(tmpV);
            minY = Math.min(minY, tmpV.y);
        });
        chicken.position.y = -minY;

        chickenAo.position.x = x - 0.05;
        chickenAo.scale.set(1.9 + 0.2 * layW, 1.1, 1);
        chickenAo.material.opacity = 0.8 * fade;
    }

    function poseEgg(t) {
        egg.visible = t > TL.eggStart;
        eggAo.visible = egg.visible;
        if (!egg.visible) {
            return;
        }
        const u = clamp01((t - TL.eggStart) / (TL.eggLanded - TL.eggStart));
        const fall = u * u;
        const s = 0.3 + 0.7 * smooth(u);
        const b = Math.max(0, t - TL.eggLanded);
        let y = lerp(0.52, EGG_H, fall);
        y += 0.06 * Math.abs(Math.sin(b * 9)) * Math.exp(-b * 5);
        let rz = 0.2 * Math.exp(-b * 3.2) * Math.sin(b * 11);

        const shakeAmp = 0.045 * smooth((t - TL.shake0) / 0.5) * (t < TL.burst0 ? 1 : 0) * (0.65 + 0.35 * Math.sin(t * 8));
        const rx = Math.sin(t * 47 + 1) * shakeAmp * 0.6;
        rz += Math.sin(t * 52) * shakeAmp;
        egg.position.set(lerp(-0.52, EGG_X, smooth(u)) + Math.sin(t * 61) * shakeAmp * 0.12, y, 0);
        egg.scale.setScalar(s);
        egg.rotation.set(rx, 0, rz);

        const lift = clamp01((y - EGG_H) / 0.4);
        eggAo.position.x = egg.position.x;
        eggAo.scale.set(0.75 * s, 0.6 * s, 1);
        eggAo.material.opacity = 0.9 * (1 - lift) * smooth(u * 1.5);

        /* Retakan & cahaya */
        const p = clamp01((t - TL.crack0) / (TL.crack1 - TL.crack0));
        const bt = t - TL.burst0;
        const bu = clamp01(bt / 1.0);
        const crackVisible = p > 0 && bt < 0;
        cracks.forEach((c, i) => {
            c.mesh.visible = crackVisible;
            const local = i === 0 ? p : clamp01((p - (i - 1) * 0.14) / 0.45);
            const segs = Math.floor(local * c.tubular);
            c.geo.setDrawRange(0, segs * c.radial * 6);
        });
        crackMat.color.setHex(0x4a3b2a).lerp(new THREE.Color(0xb7f56a), smooth(p) * 0.85);
        const glow = smooth((t - TL.crack0) / 0.9);
        const emissiveLevel = glow * (bt >= 0 ? Math.max(0, 1 - bt) : 1);
        shellMat.emissive.setRGB(0.1 * emissiveLevel, 0.25 * emissiveLevel, 0.125 * emissiveLevel);

        const lightPulse = (bt < 0 ? glow * 1.1 : 2.6 * (1 - smooth(bt / 1.4)) + 0.35) * (egg.visible ? 1 : 0);
        emeraldLight.intensity = lightPulse;
        emeraldLight.position.set(egg.position.x, EGG_H + 0.2, 0.55);

        /* Cangkang terbelah */
        petals.forEach((pt) => {
            if (bt < 0) {
                pt.pivot.position.copy(pt.pivotPos);
                pt.pivot.quaternion.identity();
                return;
            }
            const open = 1.35 * easeOut(bu / 0.55) + 0.6 * smooth((bu - 0.4) / 0.6);
            const out = 0.55 * easeOut(bu) * pt.power;
            const raw = 0.35 + 1.6 * bu - 2.33 * bu * bu;
            const landed = Math.max(0, bu - 0.85);
            const bounce = raw < 0.04 ? 0.05 * Math.abs(Math.sin(landed * 30)) * Math.exp(-landed * 14) : 0;
            const yAbs = Math.max(0.04, raw) + bounce;
            pt.pivot.position.set(pt.pivotPos.x + pt.dir.x * out, yAbs - EGG_H, pt.pivotPos.z + pt.dir.z * out);
            pt.pivot.quaternion.setFromAxisAngle(pt.axis, open * (0.9 + 0.1 * pt.power));
        });

        orbMat.opacity = bt < 0 ? 0 : smooth(bt / 0.25) * (1 - smooth((bt - 0.6) / 0.7));
        orb.position.y = lerp(EGG_SPLIT_Y - 0.08, EGG_SPLIT_Y + 0.22, easeOut(bt / 0.9));
        orb.scale.setScalar(bt < 0 ? 0.01 : 0.7 + 0.5 * easeOut(bt / 0.8));
        haloMat.opacity = bt < 0 ? 0.12 * glow : 0.9 * smooth(bt / 0.3) * (1 - smooth((bt - 0.5) / 0.9));
        halo.scale.setScalar(bt < 0 ? 0.5 : 0.9 + 2.3 * easeOut(bt / 1.0));

        /* Serpihan */
        shards.forEach((sh) => {
            if (bt < 0) {
                sh.mesh.visible = false;
                return;
            }
            sh.mesh.visible = true;
            const g = 6;
            sh.mesh.position.set(
                sh.start.x + sh.vel.x * bt,
                Math.max(0.03, sh.start.y + sh.vel.y * bt - 0.5 * g * bt * bt),
                sh.start.z + sh.vel.z * bt,
            );
            const spinT = Math.min(bt, 0.6);
            sh.mesh.rotation.set(sh.spin.x * spinT, sh.spin.y * spinT, sh.spin.z * spinT);
        });

        /* Partikel */
        if (bt < 0) {
            particles.visible = false;
        } else {
            particles.visible = true;
            const m = easeOut(bt / 1.1);
            for (let i = 0; i < PCOUNT; i++) {
                pPos[i * 3] = egg.position.x + pDirs[i].x * m * 0.9;
                pPos[i * 3 + 1] = EGG_H + EGG_SPLIT_Y + pDirs[i].y * m * 0.9 + 0.18 * bt;
                pPos[i * 3 + 2] = pDirs[i].z * m * 0.9;
            }
            pGeo.attributes.position.needsUpdate = true;
            pMat.opacity = smooth(bt / 0.12) * (1 - smooth((bt - 0.55) / 0.9)) * 0.9;
        }
    }

    function poseCamera(t) {
        const fit = aspect < 1 ? 1 + (1 - aspect) : 1;
        const walkU = clamp01((t - TL.walk0) / (TL.walk1 - TL.walk0));
        let tx;
        if (t < TL.walk1) {
            tx = lerp(startX * 0.45, 0, smooth(walkU));
        } else {
            tx = lerp(0, -0.52, smooth((t - 3.4) / 1.3));
        }
        const k = smooth((t - 4.4) / 1.3);
        const nextFov = lerp(32, 27, k);
        if (Math.abs(camera.fov - nextFov) > 0.01) {
            camera.fov = nextFov;
            camera.updateProjectionMatrix();
        }
        const dolly = lerp(1, 0.6, k) * lerp(1, 0.9, smooth((t - 5.8) / 0.6));
        const dist = 7.2 * fit * dolly;
        const camY = lerp(1.5, 0.85, k);
        const targetY = lerp(0.7, 0.36, k);
        camera.position.set(tx + 0.9 + Math.sin(t * 0.4) * 0.25, camY, dist);
        camera.lookAt(tx, targetY, 0);
    }

    function updateFlash(t) {
        if (!flashEl) {
            return;
        }
        if (t < TL.flash0 - 0.05 || t > TL.end) {
            flashEl.style.opacity = '0';
            return;
        }
        const a = smooth((t - TL.flash0) / 0.7) * 0.95 * (1 - smooth((t - 7.3) / 0.95));
        const maxDim = Math.max(width, height);
        const r = easeOut((t - TL.flash0) / 1.0) * maxDim * 1.25 + 40;
        projV.set(egg.position.x, EGG_H + 0.1, 0).project(camera);
        flashEl.style.setProperty('--fx', `${((projV.x * 0.5 + 0.5) * width).toFixed(1)}px`);
        flashEl.style.setProperty('--fy', `${((-projV.y * 0.5 + 0.5) * height).toFixed(1)}px`);
        flashEl.style.setProperty('--r', `${r.toFixed(1)}px`);
        flashEl.style.opacity = a.toFixed(3);
    }

    function renderAt(t) {
        poseChicken(t);
        poseEgg(t);
        poseCamera(t);
        camera.updateMatrixWorld();
        updateFlash(t);
        renderer.render(scene, camera);
    }

    /* ---------- Loop ---------- */
    let t = 0;
    let last = performance.now();
    let rafId = 0;
    let running = true;
    let revealCalled = false;

    const animWatchdog = window.setTimeout(() => {
        if (!revealCalled) {
            revealCalled = true;
            hooks.onReveal();
        }
        if (running) {
            running = false;
            hooks.onEnd();
        }
    }, (TL.end + 3.0) * 1000);

    function frame(now) {
        if (!running) {
            return;
        }
        const dt = Math.min(0.05, (now - last) / 1000);
        last = now;
        t += dt;
        renderAt(t);
        if (!revealCalled && t >= TL.reveal) {
            revealCalled = true;
            hooks.onReveal();
        }
        if (t >= TL.end) {
            running = false;
            window.clearTimeout(animWatchdog);
            hooks.onEnd();
            return;
        }
        rafId = requestAnimationFrame(frame);
    }

    function onResize() {
        width = window.innerWidth;
        height = window.innerHeight;
        aspect = width / height;
        startX = startXFor(aspect);
        renderer.setSize(width, height);
        camera.aspect = aspect;
        camera.updateProjectionMatrix();
        if (!running) {
            return;
        }
    }
    window.addEventListener('resize', onResize);

    const onContextLost = (event) => {
        event.preventDefault();
        hooks.onFail();
    };
    renderer.domElement.addEventListener('webglcontextlost', onContextLost);

    function dispose() {
        running = false;
        window.clearTimeout(animWatchdog);
        cancelAnimationFrame(rafId);
        window.removeEventListener('resize', onResize);
        renderer.domElement.removeEventListener('webglcontextlost', onContextLost);
        if (mixer) {
            mixer.stopAllAction();
            if (gltfData && gltfData.scene) {
                mixer.uncacheRoot(gltfData.scene);
            }
        }
        scene.traverse((obj) => {
            if (obj.geometry) {
                obj.geometry.dispose();
            }
        });
        disposables.forEach((d) => d.dispose && d.dispose());
        renderer.dispose();
        if (renderer.forceContextLoss) {
            renderer.forceContextLoss();
        }
        if (renderer.domElement.parentNode) {
            renderer.domElement.parentNode.removeChild(renderer.domElement);
        }
    }

    /* Pre-warm shader & tekstur (termasuk tekstur 4K egg.glb) di VRAM sebelum loop dimulai
     * agar tidak terjadi jeda kompilasi pada t = 3.45s saat telur muncul. */
    poseCamera(0);
    camera.updateMatrixWorld();
    egg.visible = true;
    egg.scale.setScalar(0.0001);
    if (renderer.compile) {
        renderer.compile(scene, camera);
    }
    renderer.render(scene, camera);

    renderAt(0);
    rafId = requestAnimationFrame((now) => {
        last = now;
        frame(now);
    });

    return { dispose };
}

/* ----------------------------------------------------------------------------
 * Bootstrap
 * ------------------------------------------------------------------------- */
function loadScript(url) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = url;
        script.async = true;
        const timer = window.setTimeout(() => reject(new Error('timeout ' + url)), 8000);
        script.onload = () => {
            window.clearTimeout(timer);
            resolve();
        };
        script.onerror = () => {
            window.clearTimeout(timer);
            reject(new Error('error ' + url));
        };
        document.head.appendChild(script);
    });
}

function loadThreeAndLoader() {
    return new Promise((resolve, reject) => {
        if (window.THREE && window.THREE.GLTFLoader) {
            resolve(window.THREE);
            return;
        }
        const promiseThree = window.THREE ? Promise.resolve() : loadScript(THREE_URL);
        promiseThree
            .then(() => {
                if (window.THREE && window.THREE.GLTFLoader) {
                    return Promise.resolve();
                }
                return loadScript(GLTF_URL);
            })
            .then(() => {
                window.THREE ? resolve(window.THREE) : reject(new Error('no THREE'));
            })
            .catch(reject);
    });
}

function loadGLTFModel(THREE, url, timeoutMs = 10000) {
    return new Promise((resolve) => {
        if (!THREE.GLTFLoader || !url) {
            resolve(null);
            return;
        }
        const loader = new THREE.GLTFLoader();
        const timer = window.setTimeout(() => {
            resolve(null);
        }, timeoutMs);
        loader.load(
            url,
            (gltf) => {
                window.clearTimeout(timer);
                resolve(gltf);
            },
            undefined,
            () => {
                window.clearTimeout(timer);
                resolve(null);
            }
        );
    });
}

function startIntro() {
    let loadWatchdog = null;
    if (skipBtn) {
        skipBtn.addEventListener('click', () => {
            if (loadWatchdog) {
                window.clearTimeout(loadWatchdog);
            }
            revealForm(true);
        });
    }
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !revealed) {
            if (loadWatchdog) {
                window.clearTimeout(loadWatchdog);
            }
            revealForm(true);
        }
    });

    /* Fallback jika CDN Three.js atau unduhan model tertahan lebih dari 5.5s */
    loadWatchdog = window.setTimeout(() => {
        if (!activeScene && !revealed) {
            revealForm(true);
        }
    }, 5500);

    loadThreeAndLoader()
        .then((THREE) => {
            if (revealed) {
                return;
            }
            return Promise.all([
                loadGLTFModel(THREE, MODEL_URL, 3500),
                loadGLTFModel(THREE, EGG_MODEL_URL, 3500),
            ]).then(([chickenGltf, eggGltf]) => {
                cachedChickenGltf = chickenGltf;
                if (loadWatchdog) {
                    window.clearTimeout(loadWatchdog);
                }
                if (revealed) {
                    return;
                }
                activeScene = buildIntro(
                    THREE,
                    stageEl,
                    {
                        onReveal: () => revealForm(false),
                        onEnd: () => disposeScene(),
                        onFail: () => revealForm(true),
                    },
                    chickenGltf,
                    eggGltf,
                );
            });
        })
        .catch(() => {
            if (loadWatchdog) {
                window.clearTimeout(loadWatchdog);
            }
            revealForm(true);
        });
}

/* ----------------------------------------------------------------------------
 * Pengalaman 3D Pasca-Intro: Ambient Partikel & Maskot Form Interaktif
 * ------------------------------------------------------------------------- */
let interactiveInitialized = false;

function initInteractiveExperience(chickenGltf) {
    if (interactiveInitialized) {
        return;
    }
    interactiveInitialized = true;

    initAmbientParticles();
    initCardTilt();
    initInteractiveMascot(chickenGltf);
}

function initAmbientParticles() {
    const canvas = document.getElementById('lg-ambient');
    if (!canvas) {
        return;
    }
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }

    let w = (canvas.width = window.innerWidth);
    let h = (canvas.height = window.innerHeight);

    window.addEventListener('resize', () => {
        w = canvas.width = window.innerWidth;
        h = canvas.height = window.innerHeight;
    });

    const isMobile = window.matchMedia('(max-width: 768px)').matches || window.matchMedia('(pointer: coarse)').matches;
    const P_COUNT = isMobile ? 26 : 52;
    const particles = [];
    for (let i = 0; i < P_COUNT; i++) {
        const isGold = Math.random() > 0.45;
        particles.push({
            x: Math.random() * w,
            y: Math.random() * h,
            z: 0.25 + Math.random() * 0.75,
            r: isGold ? 1.8 + Math.random() * 3.2 : 1.4 + Math.random() * 2.6,
            vy: -(0.16 + Math.random() * 0.32),
            vx: (Math.random() - 0.5) * 0.2,
            swayAmp: 10 + Math.random() * 20,
            swayFreq: 0.001 + Math.random() * 0.0018,
            phase: Math.random() * Math.PI * 2,
            color: isGold
                ? ['rgba(245, 158, 11, ', 'rgba(251, 191, 36, ', 'rgba(217, 119, 6, '][Math.floor(Math.random() * 3)]
                : ['rgba(16, 185, 129, ', 'rgba(52, 211, 153, ', 'rgba(5, 150, 105, '][Math.floor(Math.random() * 3)],
            baseAlpha: 0.25 + Math.random() * 0.45,
            pulseSpeed: 0.002 + Math.random() * 0.0025,
        });
    }

    let targetMouseX = w / 2;
    let targetMouseY = h / 2;
    let curMouseX = targetMouseX;
    let curMouseY = targetMouseY;

    window.addEventListener('pointermove', (e) => {
        targetMouseX = e.clientX;
        targetMouseY = e.clientY;
    });

    let running = true;
    let last = performance.now();

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            running = false;
        } else {
            running = true;
            last = performance.now();
            requestAnimationFrame(loop);
        }
    });

    function loop(now) {
        if (!running) {
            return;
        }
        const dt = Math.min(0.06, (now - last) / 1000);
        last = now;

        curMouseX += (targetMouseX - curMouseX) * 0.05;
        curMouseY += (targetMouseY - curMouseY) * 0.05;

        const pX = (curMouseX / (w || 1) - 0.5) * 45;
        const pY = (curMouseY / (h || 1) - 0.5) * 45;

        ctx.clearRect(0, 0, w, h);

        for (let i = 0; i < P_COUNT; i++) {
            const p = particles[i];
            p.y += p.vy;
            p.x += p.vx;
            p.phase += dt * 1.5;

            if (p.y < -30) {
                p.y = h + 20;
                p.x = Math.random() * w;
            }
            if (p.x < -40) {
                p.x = w + 30;
            }
            if (p.x > w + 40) {
                p.x = -30;
            }

            const sway = Math.sin(now * p.swayFreq + p.phase) * p.swayAmp;
            const drawX = p.x + sway + pX * p.z;
            const drawY = p.y + pY * p.z;
            const alpha = p.baseAlpha * (0.7 + 0.3 * Math.sin(now * p.pulseSpeed + p.phase));

            ctx.beginPath();
            ctx.arc(drawX, drawY, p.r * (0.8 + 0.4 * p.z), 0, Math.PI * 2);
            ctx.fillStyle = p.color + alpha.toFixed(3) + ')';
            ctx.shadowColor = p.color + (alpha * 0.8).toFixed(3) + ')';
            ctx.shadowBlur = 8 * p.z;
            ctx.fill();
        }

        ctx.shadowBlur = 0;
        requestAnimationFrame(loop);
    }

    requestAnimationFrame(loop);
}

function initCardTilt() {
    const isMobile = window.matchMedia('(max-width: 768px)').matches || window.matchMedia('(pointer: coarse)').matches;
    if (isMobile) {
        return;
    }
    const cardWrap = document.querySelector('.lg-card-wrap');
    const card = document.querySelector('.au-card');
    if (!cardWrap || !card) {
        return;
    }

    let tiltX = 0;
    let tiltY = 0;
    let targetX = 0;
    let targetY = 0;
    let ticking = false;

    cardWrap.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const px = (e.clientX - rect.left) / rect.width - 0.5;
        const py = (e.clientY - rect.top) / rect.height - 0.5;
        targetX = Math.max(-0.5, Math.min(0.5, px)) * 7.5;
        targetY = -Math.max(-0.5, Math.min(0.5, py)) * 7.5;
        if (!ticking) {
            ticking = true;
            card.classList.add('is-tilting');
            requestAnimationFrame(updateTilt);
        }
    });

    cardWrap.addEventListener('mouseleave', () => {
        targetX = 0;
        targetY = 0;
        card.classList.remove('is-tilting');
        card.style.transform = 'perspective(1200px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
    });

    function updateTilt() {
        tiltX += (targetX - tiltX) * 0.15;
        tiltY += (targetY - tiltY) * 0.15;
        card.style.transform = `perspective(1200px) rotateX(${tiltY.toFixed(2)}deg) rotateY(${tiltX.toFixed(2)}deg) translateZ(6px)`;
        if (Math.abs(targetX - tiltX) > 0.05 || Math.abs(targetY - tiltY) > 0.05) {
            requestAnimationFrame(updateTilt);
        } else {
            ticking = false;
        }
    }
}

function initInteractiveMascot(chickenGltf) {
    const canvas = document.getElementById('lg-mascot-canvas');
    if (!canvas) {
        return;
    }

    const THREE = window.THREE;
    if (!THREE) {
        return;
    }

    const speechBubble = document.getElementById('lg-mascot-bubble');
    const speechText = document.getElementById('lg-mascot-text');
    let speechTimer = null;

    function showSpeech(text, duration = 3000) {
        if (!speechBubble || !speechText) {
            return;
        }
        speechText.textContent = text;
        speechBubble.classList.add('is-visible');
        if (speechTimer) {
            window.clearTimeout(speechTimer);
        }
        speechTimer = window.setTimeout(() => {
            speechBubble.classList.remove('is-visible');
        }, duration);
    }

    const scene = new THREE.Scene();
    const w = canvas.width || 280;
    const h = canvas.height || 190;
    const camera = new THREE.PerspectiveCamera(30, w / h, 0.1, 20);
    camera.position.set(0, 0.82, 2.75);
    camera.lookAt(0, 0.65, 0);

    const renderer = new THREE.WebGLRenderer({
        canvas: canvas,
        alpha: true,
        antialias: true,
        powerPreference: 'low-power',
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(w / 2, h / 2, false);

    /* Cahaya lembut & rim light */
    scene.add(new THREE.HemisphereLight(0xffffff, 0xd0ded4, 1.25));
    const sun = new THREE.DirectionalLight(0xfff6ea, 1.35);
    sun.position.set(1.5, 3.2, 2.8);
    scene.add(sun);
    const rim = new THREE.DirectionalLight(0x6ee7b7, 0.45);
    rim.position.set(-2, 1.5, -1.8);
    scene.add(rim);

    const mascotState = {
        yaw: 0,
        pitch: 0,
        roll: 0,
        duckY: 0,
        peckY: 0,
    };
    const mascotTarget = { ...mascotState };

    let chickenRoot = null;
    let headBone = null;
    let neckBone = null;

    if (chickenGltf && chickenGltf.scene) {
        chickenRoot = chickenGltf.scene.clone(true);
        chickenRoot.scale.setScalar(2.15);
        chickenRoot.position.set(0, -1.02, 0);
        chickenRoot.rotation.set(0, 0, 0);

        chickenRoot.traverse((child) => {
            if (child.name === 'CHICKEN_-Neck_02') {
                neckBone = child;
            } else if (child.name === 'CHICKEN_-Head_05') {
                headBone = child;
            }
            if (child.isMesh) {
                child.castShadow = false;
                child.receiveShadow = false;
                if (child.material) {
                    const mats = Array.isArray(child.material) ? child.material : [child.material];
                    mats.forEach((m) => {
                        m.transparent = false;
                        m.opacity = 1;
                        m.depthWrite = true;
                    });
                }
            }
        });
        scene.add(chickenRoot);
    } else {
        /* Model maskot prosedural 3D */
        chickenRoot = new THREE.Group();
        chickenRoot.position.set(0, 0.25, 0);

        const featherMat = new THREE.MeshStandardMaterial({ color: 0xfbf8f1, roughness: 0.88 });
        const combMat = new THREE.MeshStandardMaterial({ color: 0xef4444, roughness: 0.45 });
        const beakMat = new THREE.MeshStandardMaterial({ color: 0xf59e0b, roughness: 0.4 });
        const eyeMat = new THREE.MeshStandardMaterial({ color: 0x111827, roughness: 0.1 });

        const bodyGeo = new THREE.SphereGeometry(0.48, 26, 18);
        bodyGeo.scale(1.0, 1.15, 0.95);
        const body = new THREE.Mesh(bodyGeo, featherMat);
        body.position.set(0, 0.2, 0);
        chickenRoot.add(body);

        const headGroup = new THREE.Group();
        headGroup.position.set(0, 0.58, 0.12);

        const headGeo = new THREE.SphereGeometry(0.26, 22, 16);
        headGeo.scale(0.95, 1.05, 0.98);
        const headMesh = new THREE.Mesh(headGeo, featherMat);
        headGroup.add(headMesh);

        for (let ci = -1; ci <= 1; ci++) {
            const combSp = new THREE.Mesh(new THREE.SphereGeometry(0.065, 10, 8), combMat);
            combSp.position.set(0, 0.25 + (ci === 0 ? 0.05 : 0), -ci * 0.08);
            headGroup.add(combSp);
        }

        const beakGeo = new THREE.ConeGeometry(0.075, 0.18, 12);
        beakGeo.rotateX(Math.PI / 2);
        const beak = new THREE.Mesh(beakGeo, beakMat);
        beak.position.set(0, -0.02, 0.32);
        headGroup.add(beak);

        const wattleL = new THREE.Mesh(new THREE.SphereGeometry(0.045, 10, 8), combMat);
        wattleL.scale.set(0.7, 1.4, 0.7);
        wattleL.position.set(0.035, -0.12, 0.22);
        const wattleR = wattleL.clone();
        wattleR.position.x = -0.035;
        headGroup.add(wattleL, wattleR);

        const eyeL = new THREE.Mesh(new THREE.SphereGeometry(0.045, 12, 10), eyeMat);
        eyeL.position.set(0.16, 0.06, 0.16);
        const eyeR = eyeL.clone();
        eyeR.position.x = -0.16;
        headGroup.add(eyeL, eyeR);

        const wingGeo = new THREE.SphereGeometry(0.22, 16, 12);
        wingGeo.scale(0.45, 0.8, 1.2);
        const wingL = new THREE.Mesh(wingGeo, featherMat);
        wingL.position.set(0.42, 0.12, 0.15);
        wingL.rotation.set(0.2, 0.25, -0.3);
        const wingR = wingL.clone();
        wingR.position.x = -0.42;
        wingR.rotation.set(0.2, -0.25, 0.3);
        chickenRoot.add(wingL, wingR);

        chickenRoot.add(headGroup);
        headBone = headGroup;
        scene.add(chickenRoot);
    }

    /* Interaksi input form */
    const emailEl = document.getElementById('email');
    const passEl = document.getElementById('password');
    const toggleEl = document.getElementById('toggle-password');
    const formEl = document.getElementById('form-masuk');

    if (emailEl) {
        emailEl.addEventListener('focus', () => {
            mascotTarget.yaw = 0.22;
            mascotTarget.pitch = 0.20;
            mascotTarget.roll = -0.06;
            mascotTarget.duckY = 0.05;
            showSpeech('Siapa nama atau emailmu? 🌾', 3500);
        });
        emailEl.addEventListener('input', () => {
            mascotState.peckY = 0.07;
            mascotState.pitch += 0.04;
        });
        emailEl.addEventListener('blur', () => {
            mascotTarget.yaw = 0;
            mascotTarget.pitch = 0;
            mascotTarget.roll = 0;
            mascotTarget.duckY = 0;
        });
    }

    if (passEl) {
        passEl.addEventListener('focus', () => {
            mascotTarget.yaw = -0.68;
            mascotTarget.pitch = 0.34;
            mascotTarget.roll = 0.12;
            mascotTarget.duckY = -0.24;
            showSpeech('Ssst, aku tutup mata ya! 🙈', 3500);
        });
        passEl.addEventListener('blur', () => {
            if (!toggleEl || toggleEl.getAttribute('aria-pressed') !== 'true') {
                mascotTarget.yaw = 0;
                mascotTarget.pitch = 0;
                mascotTarget.roll = 0;
                mascotTarget.duckY = 0;
            }
        });
    }

    if (toggleEl) {
        toggleEl.addEventListener('click', () => {
            window.setTimeout(() => {
                const isShowing = passEl && passEl.type === 'text';
                if (isShowing) {
                    mascotTarget.yaw = 0.16;
                    mascotTarget.pitch = -0.08;
                    mascotTarget.roll = 0.06;
                    mascotTarget.duckY = 0.08;
                    showSpeech('Eits, jangan diintip ya! 👀', 2800);
                } else if (document.activeElement === passEl) {
                    mascotTarget.yaw = -0.68;
                    mascotTarget.pitch = 0.34;
                    mascotTarget.duckY = -0.24;
                    showSpeech('Aman, tertutup lagi! 🔒', 2200);
                }
            }, 20);
        });
    }

    if (formEl) {
        formEl.addEventListener('submit', () => {
            mascotTarget.yaw = 0;
            mascotTarget.pitch = -0.15;
            mascotTarget.duckY = 0.12;
            showSpeech('Bismillah, semoga berkah! 🚀', 4000);
        });
    }

    /* Sambutan awal */
    const errorBanner = document.querySelector('.au-banner.is-error');
    const hasError = errorBanner && !errorBanner.hidden;
    window.setTimeout(() => {
        if (hasError) {
            mascotTarget.roll = 0.22;
            mascotTarget.pitch = -0.08;
            showSpeech('Wah, cek lagi email atau sandinya ya! ⚠️', 4500);
        } else {
            showSpeech('Hai Peternak! Selamat datang 🌾', 3500);
        }
    }, 900);

    let running = true;
    let t = 0;
    let last = performance.now();

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            running = false;
        } else {
            running = true;
            last = performance.now();
            requestAnimationFrame(renderMascot);
        }
    });

    function renderMascot(now) {
        if (!running) {
            return;
        }
        const dt = Math.min(0.06, (now - last) / 1000);
        last = now;
        t += dt;

        mascotState.yaw += (mascotTarget.yaw - mascotState.yaw) * 0.12;
        mascotState.pitch += (mascotTarget.pitch - mascotState.pitch) * 0.12;
        mascotState.roll += (mascotTarget.roll - mascotState.roll) * 0.12;
        mascotState.duckY += (mascotTarget.duckY - mascotState.duckY) * 0.12;
        mascotState.peckY += (0 - mascotState.peckY) * 0.25;

        const breath = Math.sin(t * 2.4) * 0.015;
        const idleHeadBob = Math.sin(t * 1.8) * 0.02;

        if (chickenRoot) {
            chickenRoot.position.y = (chickenGltf ? -1.02 : 0.25) + mascotState.duckY + mascotState.peckY + breath;
            chickenRoot.rotation.y = mascotState.yaw * 0.45;
            chickenRoot.rotation.x = mascotState.pitch * 0.35;
            chickenRoot.rotation.z = mascotState.roll * 0.5;
        }

        if (headBone) {
            headBone.rotation.y = mascotState.yaw * 0.65;
            headBone.rotation.x = mascotState.pitch * 0.65 + idleHeadBob;
            headBone.rotation.z = mascotState.roll * 0.65;
        }
        if (neckBone) {
            neckBone.rotation.y = mascotState.yaw * 0.35;
            neckBone.rotation.x = mascotState.pitch * 0.35;
        }

        renderer.render(scene, camera);
        requestAnimationFrame(renderMascot);
    }

    requestAnimationFrame(renderMascot);
}

setupForm();

if (root.classList.contains('lg-intro') && stageEl) {
    try {
        startIntro();
    } catch (e) {
        revealForm(true);
    }
} else {
    loadThreeAndLoader()
        .then((THREE) => {
            loadGLTFModel(THREE, MODEL_URL, 3000)
                .then((gltf) => {
                    initInteractiveExperience(gltf);
                })
                .catch(() => {
                    initInteractiveExperience(null);
                });
        })
        .catch(() => {
            initInteractiveExperience(null);
        });
}
