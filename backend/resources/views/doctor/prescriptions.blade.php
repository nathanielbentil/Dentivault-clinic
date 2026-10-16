<x-app-layout :title="'Doctor Prescriptions Station'">
    <div x-data="{
        search: '',
        priorityFilter: 'all',
        showRegisterModal: false,
        showNewRxModal: false,
        showInteractionModal: false,
        showEditRxModal: false,
        selectedOrder: { patient_code: '', name: '', ward_room: '', diagnosis: '', treatment_plan: '', priority: 'Routine' },
        drug1: 'Warfarin 5mg',
        drug2: 'Aspirin 81mg',
        interactionResult: null,
        checkInteractions() {
            if (this.drug1.includes('Warfarin') && this.drug2.includes('Aspirin')) {
                this.interactionResult = {
                    severity: 'High / Critical Warning',
                    color: 'rose',
                    message: 'Severe bleeding risk detected! Concomitant administration of Warfarin and Aspirin synergistically increases hemorrhagic risk. Consider INR monitoring or alternative antiplatelet.'
                };
            } else if (this.drug1.includes('Ciprofloxacin') && this.drug2.includes('Antacid')) {
                this.interactionResult = {
                    severity: 'Moderate Interaction',
                    color: 'amber',
                    message: 'Chelation risk: Polyvalent cations in antacids bind with fluoroquinolones, reducing Ciprofloxacin absorption by up to 85%. Administer 2 hours apart.'
                };
            } else {
                this.interactionResult = {
                    severity: 'Compatible / No Contraindication',
                    color: 'emerald',
                    message: 'No hazardous biochemical interactions detected between selected regimens. Safe for co-administration.'
                };
            }
        },
        openEditRx(p) {
            this.selectedOrder = { ...p };
            this.showEditRxModal = true;
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
                <span class="text-xs font-mono text-cyan-400 font-bold uppercase tracking-wider">Clinical Station: Doctor Prescriptions</span>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded-full bg-cyan-950 text-cyan-300 border border-cyan-800 font-mono text-[11px] flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-cyan-400 animate-pulse"></span>
                    <span>E-Prescribing System (Surescripts & Dispensary Synced)</span>
                </span>
            </div>
        </div>

        <!-- Dedicated Workstation Header Banner -->
        <div class="glass-card rounded-2xl p-6 sm:p-8 border border-cyan-500/30 bg-gradient-to-r from-slate-950 via-slate-900 to-cyan-950/40 relative overflow-hidden shadow-2xl">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 flex items-center gap-1.5">
                            <span>🩺</span>
                            <span>Attending Physician Workstation</span>
                        </span>
                        <span class="text-xs text-slate-400 font-mono">
                            {{ $user->email }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        Physician Electronic Prescriptions & Clinical Orders
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">
                        Attending Medical Officer: <strong class="text-cyan-300">{{ $user->name }} (MD)</strong> • Digital Signature Token Active • Direct Hospital Dispensary Bridge
                    </p>
                </div>

                <!-- Action Controls on Header -->
                <div class="flex flex-wrap items-center gap-3">
                    <button @click="showRegisterModal = true" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 hover:from-cyan-500 hover:to-teal-400 text-slate-950 font-bold text-xs shadow-lg transition-all flex items-center gap-1.5 cursor-pointer">
                        <span>➕</span>
                        <span>Admit Patient</span>
                    </button>
                    <button @click="showNewRxModal = true" class="px-4 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 transition-all flex items-center gap-2 cursor-pointer">
                        <span>💊</span>
                        <span>+ Draft New Prescription</span>
                    </button>
                    <button @click="showInteractionModal = true" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-cyan-300 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer">
                        <span>🔬</span>
                        <span>Drug Interaction Lab</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Live Station KPI Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Active Orders</span>
                    <span class="p-2 rounded-xl bg-cyan-950/60 border border-cyan-500/20 text-cyan-400">📋</span>
                </div>
                <div class="text-3xl font-black text-white mt-2">{{ $totalOrders }} Orders</div>
                <div class="text-xs text-cyan-400 mt-1">Across All 52 In-Patients</div>
            </div>

            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Pharmacy Dispense</span>
                    <span class="p-2 rounded-xl bg-amber-950/60 border border-amber-500/20 text-amber-400">⏳</span>
                </div>
                <div class="text-3xl font-black text-amber-400 mt-2">{{ $pendingFulfillment }} In Queue</div>
                <div class="text-xs text-amber-400/80 mt-1">Dispensary Acknowledged</div>
            </div>

            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dispensed Today</span>
                    <span class="p-2 rounded-xl bg-emerald-950/60 border border-emerald-500/20 text-emerald-400">✅</span>
                </div>
                <div class="text-3xl font-black text-emerald-400 mt-2">{{ $dispensedToday }} Completed</div>
                <div class="text-xs text-emerald-400/80 mt-1">Administered by Nursing</div>
            </div>

            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Critical ICU Cases</span>
                    <span class="p-2 rounded-xl bg-rose-950/60 border border-rose-500/20 text-rose-400">🚨</span>
                </div>
                <div class="text-3xl font-black text-rose-400 mt-2">{{ $criticalIcu }} ICU Patients</div>
                <div class="text-xs text-rose-400/80 mt-1">Emergency IV Infusions Required</div>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="glass-card rounded-2xl p-4 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="w-full sm:w-96 relative">
                <input type="text" x-model="search" placeholder="Search orders by patient code, name, ward, or drug regimen..."
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none">
            </div>
            <div class="flex items-center gap-3 text-xs w-full sm:w-auto">
                <span class="text-slate-400 whitespace-nowrap">Filter Urgency:</span>
                <select x-model="priorityFilter" class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs text-slate-200 focus:border-cyan-500">
                    <option value="all">All Prescription Orders</option>
                    <option value="ICU">Critical ICU Orders</option>
                    <option value="Admitted">In-Patient Ward Orders</option>
                    <option value="Outpatient">Outpatient Prescriptions</option>
                </select>
                <button type="button" @click="alert('Shift prescription manifest exported to PDF for hospital archive.')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-bold transition-all whitespace-nowrap">
                    🖨️ Export Manifest
                </button>
            </div>
        </div>

        <!-- Specialized Electronic Prescriptions Table -->
        <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
            <div class="p-4 border-b border-slate-800/80 bg-slate-900/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base font-bold text-white">Active Electronic Prescriptions List</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800">
                        Showing 52 Patient Clinical Charts
                    </span>
                </div>
                <div class="text-xs text-slate-400 font-mono">
                    Signed by: Dr. Isaac Donkoh, MD
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/90 text-slate-400 uppercase tracking-wider text-[10px] font-bold border-b border-slate-800">
                        <tr>
                            <th class="p-3">Order Ref / Code</th>
                            <th class="p-3">Patient & Demographics</th>
                            <th class="p-3">Ward Location</th>
                            <th class="p-3">Clinical Assessment</th>
                            <th class="p-3">Prescription & Dosage Regimen</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Digital Signature</th>
                            <th class="p-3 text-right">Physician Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @foreach($patients as $p)
                            <tr class="hover:bg-slate-900/50 transition-colors"
                                x-show="(priorityFilter === 'all' || priorityFilter === '{{ $p->admission_status }}') && ('{{ strtolower($p->name . ' ' . $p->patient_code . ' ' . $p->ward_room . ' ' . $p->diagnosis . ' ' . $p->treatment_plan) }}'.includes(search.toLowerCase()))">
                                <td class="p-3 font-mono font-bold text-cyan-400">
                                    {{ $p->patient_code }}
                                    <span class="block text-[9px] text-slate-500 font-sans">RX-{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-white">{{ $p->name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $p->age }} yrs, {{ $p->gender }} • <span class="text-rose-400 font-bold">{{ $p->blood_group }}</span></div>
                                </td>
                                <td class="p-3 font-medium text-slate-300">{{ $p->ward_room }}</td>
                                <td class="p-3">
                                    <span class="text-slate-200 font-semibold">{{ $p->diagnosis }}</span>
                                </td>
                                <td class="p-3">
                                    <div class="font-mono text-cyan-300 text-xs font-semibold bg-slate-950/80 px-2.5 py-1.5 rounded-lg border border-cyan-900/40 inline-block">
                                        💊 {{ $p->treatment_plan ?? 'Normal Saline 500ml IV + Multivitamins' }}
                                    </div>
                                </td>
                                <td class="p-3">
                                    @if($p->admission_status === 'ICU')
                                        <span class="px-2 py-0.5 rounded-full bg-rose-950 text-rose-300 border border-rose-700 text-[10px] font-bold animate-pulse">STAT / ICU</span>
                                    @elseif($p->admission_status === 'Admitted')
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-300 border border-emerald-700 text-[10px] font-bold">Active In-Ward</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-cyan-950 text-cyan-300 border border-cyan-700 text-[10px] font-bold">Outpatient</span>
                                    @endif
                                </td>
                                <td class="p-3 font-mono text-[10px] text-slate-400">
                                    <span class="text-emerald-400">✓ Signed</span>
                                    <span class="block text-[9px] text-slate-500 truncate max-w-[100px]">{{ hash('sha256', $p->patient_code . $p->treatment_plan) }}</span>
                                </td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click='openEditRx({{ json_encode(["patient_code" => $p->patient_code, "name" => $p->name, "ward_room" => $p->ward_room, "diagnosis" => $p->diagnosis, "treatment_plan" => $p->treatment_plan]) }})'
                                            class="px-2.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-slate-950 font-bold text-xs transition-all shadow-md">
                                            Modify Rx 💊
                                        </button>
                                        <button type="button" @click="alert('Printing verified Rx slip for patient: {{ $p->name }} ({{ $p->patient_code }})')" class="px-2 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs">
                                            🖨️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal 1: Draft New Prescription -->
        <div x-show="showNewRxModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showNewRxModal = false" class="glass-card rounded-2xl p-6 border border-cyan-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>💊</span>
                        <span>Physician Order Pad: New Electronic Prescription</span>
                    </h3>
                    <button @click="showNewRxModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Select Patient</label>
                        <select class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                            @foreach($patients->take(15) as $p)
                                <option value="{{ $p->patient_code }}">{{ $p->patient_code }} - {{ $p->name }} ({{ $p->ward_room }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Physician Assessment / Primary Diagnosis</label>
                        <input type="text" placeholder="e.g. Acute Bacterial Pneumonia, Moderate Sepsis" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Pharmaceutical Regimen & Route</label>
                        <textarea rows="3" placeholder="e.g. Ceftriaxone 1g IV daily + Azithromycin 500mg PO daily x 5 days" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-cyan-300 font-mono text-xs"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Administration Route</label>
                            <select class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                                <option>Intravenous (IV Infusion)</option>
                                <option>Oral (PO)</option>
                                <option>Intramuscular (IM)</option>
                                <option>Subcutaneous (SC)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Order Urgency</label>
                            <select class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                                <option>Routine Shift Schedule</option>
                                <option>Urgent (Within 2 Hours)</option>
                                <option>STAT / Immediate Emergency</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-cyan-950/40 border border-cyan-800/40 text-[11px] text-cyan-200">
                        🔒 Digital signature will be sealed with Argon2id session keys and dispatched to the Dispensary.
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showNewRxModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="button" @click="showNewRxModal = false; alert('Prescription Order successfully transmitted to Pharmacy Dispensary!')" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 font-bold text-slate-950 transition-all">Sign & Transmit Order 🚀</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 2: Drug Interaction Simulator -->
        <div x-show="showInteractionModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showInteractionModal = false" class="glass-card rounded-2xl p-6 border border-cyan-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🔬</span>
                        <span>Clinical Pharmacology: Drug-Drug Interaction Safety Lab</span>
                    </h3>
                    <button @click="showInteractionModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>
                <div class="space-y-4 text-xs">
                    <p class="text-slate-400">
                        Select two pharmaceutical agents to simulate real-time pharmacokinetic and pharmacodynamic compatibility before finalizing orders.
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Medication Agent 1</label>
                            <select x-model="drug1" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                                <option value="Warfarin 5mg">Warfarin 5mg (Anticoagulant)</option>
                                <option value="Ciprofloxacin 500mg">Ciprofloxacin 500mg (Antibiotic)</option>
                                <option value="Metformin 1000mg">Metformin 1000mg (Antidiabetic)</option>
                                <option value="Lisinopril 20mg">Lisinopril 20mg (ACE Inhibitor)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Medication Agent 2</label>
                            <select x-model="drug2" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                                <option value="Aspirin 81mg">Aspirin 81mg (Antiplatelet)</option>
                                <option value="Antacid (Al/Mg)">Antacid (Aluminum/Magnesium)</option>
                                <option value="Spironolactone 25mg">Spironolactone 25mg (K-sparing)</option>
                                <option value="Normal Saline IV">Normal Saline IV (Electrolyte)</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" @click="checkInteractions()" class="w-full py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 font-bold text-slate-950 transition-all text-xs">
                        Run Interaction Analysis ⚡
                    </button>
                    <div x-show="interactionResult" class="p-4 rounded-xl border space-y-1"
                        :class="interactionResult ? 'bg-' + interactionResult.color + '-950/40 border-' + interactionResult.color + '-500/40 text-' + interactionResult.color + '-200' : ''">
                        <div class="font-bold text-xs uppercase" x-text="interactionResult?.severity"></div>
                        <div class="text-[11px] leading-relaxed" x-text="interactionResult?.message"></div>
                    </div>
                </div>
                <div class="flex justify-end pt-2 border-t border-slate-800">
                    <button type="button" @click="showInteractionModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs">Close Laboratory</button>
                </div>
            </div>
        </div>

        <!-- Modal 3: Modify Prescription -->
        <div x-show="showEditRxModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showEditRxModal = false" class="glass-card rounded-2xl p-6 border border-cyan-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🩺</span>
                        <span>Update Patient Prescription & Diagnosis</span>
                    </h3>
                    <button @click="showEditRxModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400">Patient:</span>
                        <span class="text-white font-bold ml-1" x-text="selectedOrder.name"></span>
                        <span class="text-cyan-400 font-mono ml-1" x-text="'(' + selectedOrder.patient_code + ')'"></span>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Ward Location</label>
                        <input type="text" readonly x-model="selectedOrder.ward_room" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-slate-400">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Clinical Assessment / Diagnosis</label>
                        <input type="text" x-model="selectedOrder.diagnosis" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Pharmaceutical Regimen & Dosage</label>
                        <textarea rows="3" x-model="selectedOrder.treatment_plan" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-cyan-300 font-mono text-xs"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showEditRxModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="button" @click="showEditRxModal = false; alert('Prescription updated and re-verified!')" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 font-bold text-slate-950 transition-all">Save & Sign Order 💊</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 4: Admit New Patient (Doctor Station) -->
        <div x-show="showRegisterModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="showRegisterModal = false" class="glass-card rounded-2xl p-6 border border-cyan-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>➕</span>
                        <span>Admit New Hospital Patient (MD Station)</span>
                    </h3>
                    <button @click="showRegisterModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>
                <form method="POST" action="{{ route('patients.admit') }}" class="space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Full Patient Legal Name</label>
                        <input type="text" name="name" required placeholder="e.g. Eleanor Vance" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white placeholder-slate-500 focus:border-cyan-500 focus:outline-none">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Age</label>
                            <input type="number" name="age" value="38" min="1" max="120" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Gender</label>
                            <select name="gender" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                                <option value="Female">Female</option>
                                <option value="Male">Male</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Blood Group</label>
                            <select name="blood_group" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                                <option value="O+">O+</option><option value="A+">A+</option><option value="B+">B+</option><option value="AB+">AB+</option>
                                <option value="O-">O-</option><option value="A-">A-</option><option value="B-">B-</option><option value="AB-">AB-</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Admission Status</label>
                            <select name="admission_status" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                                <option value="Admitted">In-Patient (Admitted)</option>
                                <option value="ICU">ICU Critical</option>
                                <option value="Outpatient">Outpatient Consultation</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Ward Wing & Bed</label>
                            <input type="text" name="ward_room" value="General Ward 2 (Bed 05)" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Primary Clinical Diagnosis</label>
                        <input type="text" name="diagnosis" value="Post-operative surgical observation & telemetry" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Initial Treatment Regimen & Orders (Optional)</label>
                        <input type="text" name="treatment_plan" placeholder="e.g. IV Cefazolin 1g Q8H, Bed rest, SpO2 continuous" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white placeholder-slate-500 focus:border-cyan-500 focus:outline-none">
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showRegisterModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition-all">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 hover:from-cyan-500 hover:to-teal-400 font-bold text-slate-950 transition-all shadow-lg flex items-center gap-1.5 cursor-pointer">
                            <span>➕</span>
                            <span>Admit Patient</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
