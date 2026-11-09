@use('App\Support\Fmt')
@if (count($belanja))
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-ink">Rekomendasi Belanja Tambahan</h3>
                <p class="text-xs text-muted">Daftar kekurangan bahan pakan yang perlu dibeli agar target adukan terpenuhi.</p>
            </div>
            <span class="text-xs font-bold text-rose-600 shrink-0">
                {{ count($belanja) }} Bahan Kurang
            </span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-line bg-white">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-muted bg-surface/40 border-b border-line text-[11px] uppercase tracking-wider font-semibold">
                        <th class="py-2.5 px-3.5 text-left">Bahan Pakan</th>
                        <th class="py-2.5 px-2.5 text-center">Stok Gudang</th>
                        <th class="py-2.5 px-2.5 text-center">Kebutuhan</th>
                        <th class="py-2.5 px-2.5 text-center text-rose-600 font-bold">Kekurangan</th>
                        <th class="py-2.5 px-3.5 text-right">Estimasi Biaya</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/50">
                    @foreach ($belanja as $b)
                        <tr class="hover:bg-surface/20 transition-colors">
                            <td class="py-2.5 px-3.5 font-bold text-ink text-left">
                                {{ $b['nama'] }}
                                @if (!empty($b['harga_satuan']))
                                    <span class="block text-[10px] font-normal text-muted">{{ Fmt::rp($b['harga_satuan']) }} / kg</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-2.5 text-center tabular-nums text-muted">
                                {{ isset($b['stok_ada']) ? Fmt::num($b['stok_ada'], 1).' kg' : '-' }}
                            </td>
                            <td class="py-2.5 px-2.5 text-center tabular-nums text-ink font-medium">
                                {{ isset($b['kebutuhan_kg']) ? Fmt::num($b['kebutuhan_kg'], 2).' kg' : '-' }}
                            </td>
                            <td class="py-2.5 px-2.5 text-center tabular-nums font-bold text-rose-600">
                                {{ Fmt::num($b['kekurangan_kg'], 2) }} kg
                            </td>
                            <td class="py-2.5 px-3.5 text-right tabular-nums font-bold text-ink">
                                {{ Fmt::rp($b['biaya']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-surface-2/60 border-t-2 border-line font-bold text-ink">
                        <td class="py-2.5 px-3.5 text-left" colspan="3">Total Estimasi Belanja Tambahan</td>
                        <td class="py-2.5 px-2.5 text-center tabular-nums text-rose-600 font-bold">
                            {{ Fmt::num(collect($belanja)->sum('kekurangan_kg'), 2) }} kg
                        </td>
                        <td class="py-2.5 px-3.5 text-right tabular-nums text-brand font-black text-xs sm:text-sm">
                            {{ Fmt::rp(collect($belanja)->sum('biaya')) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="text-[11px] text-muted flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[14px] shrink-0">info</span>
            <span>Estimasi biaya belanja dihitung berdasarkan harga beli satuan bahan yang tersimpan di gudang pakan saat ini.</span>
        </p>
    </div>
@endif
