{{-- Modal Popup Uji Sensor IoT & Simulasi Kandang --}}
<div id="modal-iot-kandang" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modal-iot-kandang')"></div>

    <div data-modal-card class="relative z-10 w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[90vh] overflow-y-auto">
        {{-- Header Modal --}}
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">sensors</span>
                </div>
                <div>
                    <h3 class="text-h-sm text-ink font-bold leading-tight">Integrasi Sensor IoT Kandang</h3>
                    <p class="text-tiny text-muted">Micro-Controller ESP32 / DHT22 Real-time Interface</p>
                </div>
            </div>
            <button type="button" onclick="tutupModal('modal-iot-kandang')" class="w-8 h-8 rounded-full bg-surface hover:bg-surface-2 text-muted hover:text-ink grid place-items-center transition" aria-label="Tutup">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        {{-- Status Sensor Saat Ini --}}
        <div class="p-3.5 rounded-2xl bg-surface border border-line space-y-2 text-xs">
            <div class="flex items-center justify-between">
                <span class="font-bold text-ink">Status Koneksi:</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full {{ ($weather['sumber'] ?? '') === 'sensor' ? 'bg-emerald-100 text-emerald-800 font-bold' : 'bg-blue-100 text-blue-800 font-semibold' }}">
                    <span class="w-2 h-2 rounded-full {{ ($weather['sumber'] ?? '') === 'sensor' ? 'bg-emerald-500 animate-pulse' : 'bg-blue-500' }}"></span>
                    <span>{{ ($weather['sumber'] ?? '') === 'sensor' ? 'Sensor Fisik Terhubung' : 'Prakiraan Cuaca Mikro (BMKG/Open-Meteo)' }}</span>
                </span>
            </div>
            <p class="text-muted leading-relaxed">
                RansumKu mendukung penerimaan data otomatis dari mikrokontroler kandang (ESP32 / Arduino / Raspberry Pi) via HTTP POST Webhook JSON.
            </p>
        </div>

        {{-- Form Simulasi Uji Sensor (Berguna untuk Demo Lomba UGM) --}}
        <form method="POST" action="{{ route('api.kandang.sensor', $farm ?? auth()->user()->farm) }}" onsubmit="simulasiSensorIot(event, this)" class="space-y-3 p-4 rounded-2xl bg-brand/5 border border-brand/20">
            @csrf
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-black uppercase tracking-wider text-brand">Uji Simulasi Sensor IoT:</h4>
                <span class="text-tiny text-muted font-bold">Simulasi Live</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="lbl !text-tiny" for="iot_suhu">Suhu Kandang (°C)</label>
                    <input id="iot_suhu" name="suhu" type="number" step="0.1" min="15" max="50" class="field !min-h-10 text-sm font-bold text-ink" value="{{ $weather['suhu'] ?? 32.5 }}" required>
                </div>
                <div>
                    <label class="lbl !text-tiny" for="iot_kelembaban">Kelembaban Relatif (% RH)</label>
                    <input id="iot_kelembaban" name="kelembaban" type="number" step="0.5" min="20" max="100" class="field !min-h-10 text-sm font-bold text-ink" value="{{ $weather['kelembaban'] ?? 72 }}" required>
                </div>
            </div>

            <button type="submit" id="btn-kirim-sensor" class="btn-primary w-full !min-h-10 text-xs font-bold shadow-xs">
                <span class="material-symbols-outlined text-[16px]">send</span>
                <span>Kirim Sinyal Sensor ke Sistem</span>
            </button>
            <p id="iot-feedback" class="text-tiny font-bold text-center hidden"></p>
        </form>

        {{-- Dokumentasi Webhook Endpoint untuk Juri Lomba --}}
        <div class="space-y-1.5 text-xs text-muted">
            <span class="font-bold text-ink block">Endpoint Webhook API:</span>
            <div class="p-2.5 rounded-xl bg-gray-900 text-gray-200 font-mono text-[11px] overflow-x-auto select-all">
                POST {{ url('/api/kandang/' . ($farm->id ?? auth()->user()->farm->id ?? 1) . '/sensor') }}
            </div>
            <span class="text-tiny text-muted block">Payload JSON: <code>{"suhu": 32.5, "kelembaban": 70.0}</code></span>
        </div>

        <div class="pt-2 text-right">
            <button type="button" onclick="tutupModal('modal-iot-kandang')" class="btn-outline btn-sm !min-h-9 !px-4 text-xs">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
function simulasiSensorIot(e, form) {
    e.preventDefault();
    const btn = document.getElementById('btn-kirim-sensor');
    const fb = document.getElementById('iot-feedback');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span><span>Mengirim...</span>';

    fetch(form.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            suhu: parseFloat(document.getElementById('iot_suhu').value),
            kelembaban: parseFloat(document.getElementById('iot_kelembaban').value)
        })
    })
    .then(r => r.json())
    .then(data => {
        fb.classList.remove('hidden', 'text-danger');
        fb.classList.add('text-brand');
        fb.textContent = `Berhasil! THI terhitung: ${data.thi} (${data.status})`;
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    })
    .catch(err => {
        fb.classList.remove('hidden', 'text-brand');
        fb.classList.add('text-danger');
        fb.textContent = 'Gagal mengirim data simulasi sensor.';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>

