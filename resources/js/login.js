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

const stageEl = document.getElementById('lg-stage');
const flashEl = document.getElementById('lg-flash');
const skipBtn = document.getElementById('lg-skip');
const emailInput = document.getElementById('email');

let revealed = false;
let activeScene = null;

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

    if (fast) {
        window.setTimeout(disposeScene, 450);
    }
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

function buildIntro(THREE, host, hooks) {
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

    /* ---------- Material ---------- */
    const fadeMats = [];
    const fadeMat = (mat) => {
        mat.transparent = true;
        fadeMats.push(mat);
        return track(mat);
    };

    const featherMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xfbfaf4, roughness: 0.92, metalness: 0, bumpMap: featherTex, bumpScale: 1.1 }));
    const combMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xd23a33, roughness: 0.55 }));
    const beakMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xf0b43c, roughness: 0.45 }));
    const legMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xe6a238, roughness: 0.6 }));
    const eyeMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0x15110e, roughness: 0.15, metalness: 0.2 }));
    const lidMat = fadeMat(new THREE.MeshStandardMaterial({ color: 0xf1efe6, roughness: 0.9 }));

    const mk = (geo, mat) => {
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

    const wings = [1, -1].map((s) => {
        const wing = mk(sphere(0.4, 28, 18), featherMat);
        wing.scale.set(1, 0.6, 0.2);
        wing.position.set(-0.08, 0.8, s * 0.44);
        wing.rotation.z = 0.12;
        wing.rotation.y = -s * 0.12;
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
    [0, 1, 2].forEach((i) => {
        const lobe = mk(sphere(i === 1 ? 0.06 : 0.048, 16, 12), combMat);
        lobe.scale.set(0.8, 1.1, 0.45);
        lobe.position.set(-0.05 + i * 0.06, 0.17 + (i === 1 ? 0.03 : 0), 0);
        head.add(lobe);
    });
    const beak = mk(new THREE.ConeGeometry(0.06, 0.17, 18), beakMat);
    beak.rotation.z = -Math.PI / 2;
    beak.position.set(0.22, -0.015, 0);
    head.add(beak);
    const wattle = mk(sphere(0.045, 14, 10), combMat);
    wattle.scale.set(0.6, 1.6, 0.5);
    wattle.position.set(0.13, -0.14, 0);
    head.add(wattle);
    const lids = [1, -1].map((s) => {
        const eye = mk(sphere(0.028, 14, 10), eyeMat);
        eye.position.set(0.11, 0.04, s * 0.14);
        head.add(eye);
        const lid = mk(sphere(0.033, 14, 10), lidMat);
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

    /* ---------- Telur ---------- */
    const eggRadiusAtY = (y) => {
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

    const shellMat = track(new THREE.MeshStandardMaterial({
        color: 0xf6eddc,
        roughness: 0.42,
        metalness: 0,
        bumpMap: speckleTex,
        bumpScale: 0.25,
        side: THREE.DoubleSide,
        emissive: 0x000000,
    }));

    const egg = new THREE.Group();
    scene.add(egg);

    const eggBase = mk(new THREE.LatheGeometry(eggProfile(0, thetaSplit, 24), 40), shellMat);
    shapeEgg(eggBase.geometry);
    egg.add(eggBase);

    const rSplit = eggRadiusAtY(EGG_SPLIT_Y);
    const petals = [0, 1, 2, 3].map((k) => {
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
        fadeMats.forEach((m) => {
            m.opacity = fade;
        });
        shadowMat.opacity = 0.2 * fade;

        const walkU = clamp01((t - TL.walk0) / (TL.walk1 - TL.walk0));
        const stepU = clamp01((t - TL.step0) / (TL.step1 - TL.step0));
        let x = startX * (1 - smooth(walkU)) + 1.6 * smooth(stepU);

        const env = (t0, t1) => {
            if (t < t0 || t > t1) {
                return 0;
            }
            return smooth(Math.min((t - t0) / 0.3, (t1 - t) / 0.35));
        };
        const walkW = Math.max(env(TL.walk0, TL.walk1), env(TL.step0, TL.step1));
        const layW = smooth((t - TL.layStart) / 0.4) * (1 - smooth((t - 4.1) / 0.3));
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
        torso.rotation.x = Math.sin(t * 26) * 0.022 * effort;
        torso.scale.set(1, 1 + Math.sin(t * 2.4) * 0.012, 1);
        tail.rotation.z = 0.55 + 0.35 * layW + Math.sin(t * 3.2) * 0.05 + Math.sin(t * 24) * 0.05 * effort;
        wings.forEach((w) => {
            w.rotation.z = 0.12 + 0.05 * effort * Math.sin(t * 22);
        });

        neck.position.x = 0.4 + 0.05 * Math.sin(phase + 0.6) * walkW;
        neck.rotation.z = -0.1 * layW + 0.04 * Math.sin(t * 1.7);
        const idle = (1 - walkW) * (1 - layW);
        head.rotation.y = Math.sin(t * 1.4) * 0.32 * idle;
        head.rotation.z = Math.sin(t * 2.1) * 0.05 * idle + 0.18 * layW;
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
    }

    function poseEgg(t) {
        egg.visible = t > TL.eggStart;
        if (!egg.visible) {
            return;
        }
        const u = clamp01((t - TL.eggStart) / (TL.eggLanded - TL.eggStart));
        const fall = u * u;
        const s = 0.3 + 0.7 * smooth(u);
        const b = Math.max(0, t - TL.eggLanded);
        let y = lerp(0.66, EGG_H, fall);
        y += 0.06 * Math.abs(Math.sin(b * 9)) * Math.exp(-b * 5);
        let rz = 0.2 * Math.exp(-b * 3.2) * Math.sin(b * 11);

        const shakeAmp = 0.045 * smooth((t - TL.shake0) / 0.5) * (t < TL.burst0 ? 1 : 0) * (0.65 + 0.35 * Math.sin(t * 8));
        const rx = Math.sin(t * 47 + 1) * shakeAmp * 0.6;
        rz += Math.sin(t * 52) * shakeAmp;
        egg.position.set(lerp(-0.62, EGG_X, smooth(u)) + Math.sin(t * 61) * shakeAmp * 0.12, y, 0);
        egg.scale.setScalar(s);
        egg.rotation.set(rx, 0, rz);

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
            const yAbs = Math.max(0.04, 0.35 + 1.6 * bu - 2.33 * bu * bu);
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
        cancelAnimationFrame(rafId);
        window.removeEventListener('resize', onResize);
        renderer.domElement.removeEventListener('webglcontextlost', onContextLost);
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
function loadThree() {
    return new Promise((resolve, reject) => {
        if (window.THREE) {
            resolve(window.THREE);
            return;
        }
        const script = document.createElement('script');
        script.src = THREE_URL;
        script.async = true;
        const timer = window.setTimeout(() => reject(new Error('timeout')), 6000);
        script.onload = () => {
            window.clearTimeout(timer);
            window.THREE ? resolve(window.THREE) : reject(new Error('no THREE'));
        };
        script.onerror = () => {
            window.clearTimeout(timer);
            reject(new Error('load error'));
        };
        document.head.appendChild(script);
    });
}

function startIntro() {
    if (skipBtn) {
        skipBtn.addEventListener('click', () => revealForm(true));
    }
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !revealed) {
            revealForm(true);
        }
    });

    loadThree()
        .then((THREE) => {
            if (revealed) {
                return;
            }
            activeScene = buildIntro(THREE, stageEl, {
                onReveal: () => revealForm(false),
                onEnd: () => disposeScene(),
                onFail: () => {
                    revealForm(true);
                },
            });
        })
        .catch(() => {
            revealForm(true);
        });
}

setupForm();

if (root.classList.contains('lg-intro') && stageEl) {
    try {
        startIntro();
    } catch (e) {
        revealForm(true);
    }
}
