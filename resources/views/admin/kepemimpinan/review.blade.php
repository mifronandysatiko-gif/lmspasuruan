@extends('layouts.dashboard')

@section('title', 'Review Logbook Kepemimpinan - Coach & Mentor')
@section('header_title', 'Panel Telaah Aksi Perubahan (Coach & Mentor)')

@section('content')
<div class="space-y-8" x-data="{ reviewModal: false, selectedItem: null }">
    
    <!-- Top Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-emerald-600 uppercase tracking-wider mb-1">
                <i class="fa-solid fa-signature"></i> Panel Pembimbing & Penguji
            </div>
            <h2 class="text-xl font-extrabold text-slate-900">Bimbingan & Persetujuan Milestone Aksi Perubahan</h2>
            <p class="text-xs text-slate-500">Telaah laporan mingguan peserta Pelatihan Kepemimpinan, berikan catatan revisi, atau konfirmasi persetujuan resmi.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 bg-sky-50 text-sky-700 text-xs font-bold rounded-xl border border-sky-200">
                Mode: {{ strtoupper(str_replace('_', ' ', $user->role)) }}
            </span>
        </div>
    </div>

    <!-- Table of Logbook Submissions -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-sm text-slate-900">Daftar Pengajuan Logbook Peserta Bimbingan</h3>
            <span class="text-xs text-slate-400">Total: {{ $logbooks->count() }} Pengajuan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Peserta ASN & Identitas</th>
                        <th class="py-3 px-4">Jabatan & Unit Kerja</th>
                        <th class="py-3 px-4">Milestone & Minggu</th>
                        <th class="py-3 px-4">Tanggal & Bukti</th>
                        <th class="py-3 px-4">Status Persetujuan</th>
                        <th class="py-3 px-4 text-center">Tindakan Pembimbing</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logbooks as $log)
                        @php
                            $logUser = $log->user;
                            $logProf = $logUser?->profile;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $logProf?->formatted_full_name ?? $logUser->name }}</div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                    <span class="text-[11px] text-slate-600 font-mono font-semibold">{{ $logUser->nip ? 'NIP: ' . $logUser->nip : 'Non-ASN' }}</span>
                                    @if($logProf?->nik)
                                        <span class="text-[10px] text-slate-500 font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200" title="Nomor Induk Kependudukan">
                                            NIK: {{ $logProf->nik }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4 max-w-xs">
                                <p class="font-semibold text-slate-800 text-xs truncate">{{ $logProf?->jabatan ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ $logProf?->unit_kerja ?? 'Pemerintah Kabupaten Pasuruan' }}</p>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-slate-100 font-bold text-[10px] text-slate-700">
                                    Minggu ke-{{ $log->week_number }}
                                </span>
                                <div class="font-bold text-slate-800 text-xs mt-1">{{ $log->milestone_title }}</div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="text-slate-600">{{ \Carbon\Carbon::parse($log->activity_date)->isoFormat('D MMM Y') }}</div>
                                @if($log->evidence_file_name)
                                    <a href="{{ asset('storage/evidence/' . $log->evidence_file_name) }}" target="_blank" class="text-[11px] text-pasuruan-700 hover:text-pasuruan-900 font-bold hover:underline flex items-center gap-1 mt-0.5" title="Klik untuk mengunduh/melihat berkas bukti">
                                        <i class="fa-solid fa-file-pdf text-rose-500"></i>
                                        <span class="truncate max-w-[180px]">{{ $log->evidence_file_name }}</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400"></i>
                                    </a>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                @if($log->status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        <i class="fa-solid fa-circle-check text-emerald-600"></i> Disetujui (Approved)
                                    </span>
                                @elseif($log->status === 'revision')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                        <i class="fa-solid fa-circle-exclamation text-rose-600"></i> Perlu Revisi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                        <i class="fa-solid fa-clock text-amber-600"></i> Menunggu Telaah
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <button type="button" @click="selectedItem = {{ json_encode($log) }}; reviewModal = true" class="px-3.5 py-1.5 bg-pasuruan-700 hover:bg-pasuruan-800 text-white font-bold text-xs rounded-lg shadow-2xs transition-colors">
                                    <i class="fa-solid fa-pen-to-square mr-1 text-amber-300"></i> Telaah & Nilai
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                Tidak ada logbook yang membutuhkan telaah saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Telaah & Approval Logbook -->
    <div x-show="reviewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6">
        <div class="flex min-h-full items-center justify-center">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-slate-200 my-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                    <i class="fa-solid fa-signature text-emerald-600"></i>
                    <span>Form Telaah Bimbingan Logbook</span>
                </div>
                <button @click="reviewModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div class="p-3.5 bg-slate-50 rounded-xl space-y-2 border border-slate-200">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <span class="font-bold text-slate-900 text-sm" x-text="selectedItem?.user?.profile?.full_name || selectedItem?.user?.name"></span>
                        <div class="flex items-center gap-2 font-mono text-[11px] text-slate-600">
                            <span>NIP: <strong class="text-slate-800" x-text="selectedItem?.user?.nip || '-'"></strong></span>
                            <span>•</span>
                            <span>NIK: <strong class="text-slate-800" x-text="selectedItem?.user?.profile?.nik || '-'"></strong></span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                        <span><strong class="text-slate-600">Jabatan:</strong> <span x-text="selectedItem?.user?.profile?.jabatan || '-'"></span></span>
                        <span>•</span>
                        <span><strong class="text-slate-600">Unit Kerja:</strong> <span x-text="selectedItem?.user?.profile?.unit_kerja || 'Pemkab Pasuruan'"></span></span>
                    </div>
                    <div class="pt-1.5 border-t border-slate-200/80 font-bold text-pasuruan-800 text-xs" x-text="selectedItem?.milestone_title"></div>
                    <template x-if="selectedItem?.evidence_file_name">
                        <div class="pt-1.5 flex items-center justify-between border-t border-slate-200/80">
                            <span class="text-rose-600 font-bold flex items-center gap-1 text-[11px]">
                                <i class="fa-solid fa-file-pdf"></i>
                                <span x-text="selectedItem?.evidence_file_name"></span>
                            </span>
                            <a :href="'{{ asset('storage/evidence') }}/' + selectedItem?.evidence_file_name" target="_blank" class="px-2.5 py-1 bg-white hover:bg-slate-100 text-pasuruan-800 font-bold rounded-lg border border-slate-300 text-[10px] inline-flex items-center gap-1 shadow-2xs">
                                <i class="fa-solid fa-download text-slate-500"></i> Unduh Berkas
                            </a>
                        </div>
                    </template>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700 uppercase tracking-wider text-[10px]">Deskripsi Capaian Peserta:</label>
                    <p class="text-slate-600 p-2.5 bg-slate-50 rounded-lg text-xs leading-relaxed" x-text="selectedItem?.activity_description"></p>
                </div>
            </div>

            <!-- Form Action Status & Feedback -->
            <form :action="'/admin/kepemimpinan/' + selectedItem?.id + '/status'" method="POST" class="space-y-4 pt-1">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">
                        Catatan Evaluasi / Rekomendasi Revisi
                    </label>
                    <textarea name="feedback" rows="3" required placeholder="Tuliskan catatan bimbingan untuk peserta..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none"></textarea>
                </div>

                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-2.5 text-emerald-900 text-[11px]">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <span>Persetujuan seluruh milestone aksi perubahan akan mengesahkan kelulusan peserta dan menerbitkan E-Sertifikat Kepemimpinan resmi (72 JP).</span>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <button type="submit" name="status" value="revision" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 transition-colors">
                        <i class="fa-solid fa-rotate-left mr-1"></i> Minta Revisi
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="reviewModal = false" class="px-3 py-2 text-slate-500 hover:text-slate-700 text-xs font-bold">
                            Tutup
                        </button>
                        <button type="submit" name="status" value="approved" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-check-double"></i> Konfirmasi Persetujuan (Approve)
                        </button>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>

</div>
@endsection
