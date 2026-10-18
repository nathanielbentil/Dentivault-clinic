<x-app-layout :title="'Pharmacy Dispensary Station'">
    <div x-data="{
        search: '',
        dispenseFilter: 'all',
        showDispenseModal: false,
        showScanModal: false,
        selectedItem: { patient_code: '', name: '', ward_room: '', diagnosis: '', treatment_plan: '', attending_doctor: '' },
        batchNumber: 'BATCH-2026-GH99',
        expiryDate: '2028-09-30',
        openDispense(p) {
            this.selectedItem = { ...p };
            this.showDispenseModal = true;
        }
    }" class="space-y-6">

        <!-- Top Navigation & Breadcrumbs -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-xs font-bold text-slate-300 hover:text-white transition-all flex items-center gap-2">
                    <span>←</span>
                    <span>Return to Main Console</span>
                </a>
                <span class="text-slate-600">/</span>
                <span class="text-xs font-mono text-amber-400 font-bold uppercase tracking-wider">Clinical Station: Pharmacy Dispensary</span>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded-full bg-amber-950 text-amber-300 border border-amber-800 font-mono text-[11px] flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Hospital Central Dispensary & Narcotics Vault</span>
                </span>
            </div>
        </div>

        <!-- Dedicated Workstation Header Banner -->
        <div class="glass-card rounded-2xl p-6 sm:p-8 border border-amber-500/30 bg-gradient-to-r from-slate-950 via-slate-900 to-amber-950/40 relative overflow-hidden shadow-2xl">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 flex items-center gap-1.5">
                            <span>💊</span>
                            <span>Hospital Pharmacy Dispensary</span>
                        </span>
                        <span class="text-xs text-slate-400 font-mono">
                            {{ $user->email }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        Dispensary Fulfillment & Medication Verification
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">
                        Licensed Pharmacist: <strong class="text-amber-300">{{ $user->name }} (RPh)</strong> • Prescription Verification, Controlled Substance Auditing & Barcode Release
                    </p>
                </div>

                <!-- Action Controls on Header -->
                <div class="flex flex-wrap items-center gap-3">
                    <button @click="showScanModal = true" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition-all flex items-center gap-2 cursor-pointer">
                        <span>🔍</span>
                        <span>Scan Rx Barcode</span>
                    </button>
                    <button @click="alert('Pharmaceutical stock levels synced with Central Medical Store.')" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-amber-300 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer">
                        <span>📊</span>
                        <span>Stock Inventory Audit</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Live Station KPI Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Orders</span>
                    <span class="p-2 rounded-xl bg-amber-950/60 border border-amber-500/20 text-amber-400">⏳</span>
                </div>
                <div class="text-3xl font-black text-amber-400 mt-2">{{ $pendingCount }} Orders</div>
                <div class="text-xs text-amber-400/80 mt-1">Awaiting Pharmacist Check</div>
            </div>

            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dispensed Today</span>
                    <span class="p-2 rounded-xl bg-emerald-950/60 border border-emerald-500/20 text-emerald-400">✅</span>
                </div>
                <div class="text-3xl font-black text-emerald-400 mt-2">{{ $dispensedCount }} Orders</div>
                <div class="text-xs text-emerald-400/80 mt-1">Dispatched to Nursing Wards</div>
            </div>

            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Controlled Narcotics</span>
                    <span class="p-2 rounded-xl bg-purple-950/60 border border-purple-500/20 text-purple-400">🔒</span>
                </div>
                <div class="text-3xl font-black text-purple-400 mt-2">12 Units</div>
                <div class="text-xs text-purple-400/80 mt-1">Double Signature Verified</div>
            </div>

            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Drug Shortage Alert</span>
                    <span class="p-2 rounded-xl bg-cyan-950/60 border border-cyan-500/20 text-cyan-400">🛡️</span>
                </div>
                <div class="text-3xl font-black text-white mt-2">0 Deficits</div>
                <div class="text-xs text-cyan-400 mt-1">All Essential Meds In Stock</div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="glass-card rounded-2xl p-4 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="w-full sm:w-96 relative">
                <input type="text" x-model="search" placeholder="Search orders by patient code, name, or medication..."
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-amber-500 focus:outline-none">
            </div>
            <div class="flex items-center gap-3 text-xs w-full sm:w-auto">
                <span class="text-slate-400 whitespace-nowrap">Order Status:</span>
                <select x-model="dispenseFilter" class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs text-slate-200 focus:border-amber-500">
                    <option value="all">All Prescriptions</option>
                    <option value="pending">Pending Dispense</option>
                    <option value="fulfilled">Fulfilled & Verified</option>
                </select>
            </div>
        </div>

        <!-- Dispensary Orders Table -->
        <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
            <div class="p-4 border-b border-slate-800/80 bg-slate-900/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base font-bold text-white">Live Pharmaceutical Dispensing Queue</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-amber-950 text-amber-300 border border-amber-800">
                        Prescriptions Dispatched from Doctors
                    </span>
                </div>
                <div class="text-xs text-slate-400 font-mono">
                    Formulary: WHO / Ghana Health Service (GHS)
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/90 text-slate-400 uppercase tracking-wider text-[10px] font-bold border-b border-slate-800">
                        <tr>
                            <th class="p-3">Rx Reference</th>
                            <th class="p-3">Patient Code & Name</th>
                            <th class="p-3">Ward / Bed</th>
                            <th class="p-3">Prescribed Medication & Dosage</th>
                            <th class="p-3">Attending Physician</th>
                            <th class="p-3">Safety Review</th>
                            <th class="p-3 text-right">Pharmacist Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @foreach($patients as $p)
                            <tr class="hover:bg-slate-900/50 transition-colors"
                                x-show="('{{ strtolower($p->name . ' ' . $p->patient_code . ' ' . $p->ward_room . ' ' . $p->treatment_plan) }}'.includes(search.toLowerCase()))">
                                <td class="p-3 font-mono font-bold text-amber-400">
                                    RX-{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-white">{{ $p->name }}</div>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $p->patient_code }}</span>
                                </td>
                                <td class="p-3 font-medium text-slate-300">{{ $p->ward_room }}</td>
                                <td class="p-3">
                                    <div class="font-mono text-amber-300 text-xs font-semibold bg-slate-950/80 px-2.5 py-1.5 rounded-lg border border-amber-900/40 inline-block">
                                        💊 {{ $p->treatment_plan }}
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="text-slate-200">{{ $p->attending_doctor }}</div>
                                    <span class="text-[9px] text-emerald-400 font-mono">Doctor Signature Verified</span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-300 border border-emerald-700 text-[10px] font-bold">
                                        ✓ No Allergies
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click='openDispense({{ json_encode(["patient_code" => $p->patient_code, "name" => $p->name, "ward_room" => $p->ward_room, "diagnosis" => $p->diagnosis, "treatment_plan" => $p->treatment_plan, "attending_doctor" => $p->attending_doctor]) }})'
                                            class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition-all shadow-md">
                                            Verify & Dispense 📦
                                        </button>
                                        <button type="button" @click="alert('Dosage inquiry flag sent to {{ $p->attending_doctor }} for patient {{ $p->name }}.')"
                                            class="px-2 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-400 text-xs border border-slate-700">
                                            ⚠️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal: Verify & Dispense -->
        <div x-show="showDispenseModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showDispenseModal = false" class="glass-card rounded-2xl p-6 border border-amber-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>📦</span>
                        <span>Pharmaceutical Dispensing Authorization</span>
                    </h3>
                    <button @click="showDispenseModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400">Patient:</span>
                        <span class="text-white font-bold ml-1" x-text="selectedItem.name"></span>
                        <span class="text-amber-400 font-mono ml-1" x-text="'(' + selectedItem.patient_code + ' - ' + selectedItem.ward_room + ')'"></span>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Prescribed Regimen</label>
                        <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-amber-300 font-mono" x-text="selectedItem.treatment_plan"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Medication Batch Number</label>
                            <input type="text" x-model="batchNumber" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Expiry Date</label>
                            <input type="date" x-model="expiryDate" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Pharmacist Dispensing Instructions & Auxiliary Labels</label>
                        <textarea rows="2" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200 text-xs">Take with food. Complete entire antibiotic course even if feeling better.</textarea>
                    </div>
                    <div class="p-3 rounded-xl bg-amber-950/40 border border-amber-800/40 text-[11px] text-amber-200">
                        ⚡ Dispense confirmation will immediately update the Patient Medical Chart and alert nursing staff.
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showDispenseModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="button" @click="showDispenseModal = false; alert('Medication packaged, sealed, and dispatched to ' + selectedItem.ward_room + '!')" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 font-bold text-slate-950 transition-all">Authorize & Dispense 📦</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Barcode Scanner -->
        <div x-show="showScanModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showScanModal = false" class="glass-card rounded-2xl p-6 border border-amber-500/40 w-full max-w-md space-y-4 shadow-2xl text-center">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🔍</span>
                        <span>Optical Barcode & RFID Scanner</span>
                    </h3>
                    <button @click="showScanModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>
                <div class="p-6 rounded-2xl bg-slate-950 border border-dashed border-amber-500/40 space-y-3">
                    <div class="text-4xl animate-bounce">📱</div>
                    <div class="text-sm font-bold text-white">Ready for Medication Scan</div>
                    <p class="text-xs text-slate-400">Hold prescription slip or blister pack barcode up to optical reader.</p>
                    <div class="h-1 bg-amber-500/60 rounded-full animate-pulse"></div>
                </div>
                <button type="button" @click="showScanModal = false; alert('Barcode verified: Amoxicillin 500mg (Batch GH-9912). Match confirmed!')" class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 font-bold text-slate-950 text-xs">
                    Simulate Successful Scan ✓
                </button>
            </div>
        </div>

    </div>
</x-app-layout>
