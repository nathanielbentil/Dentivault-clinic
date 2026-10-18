<x-app-layout :title="'Nurse Vitals & Ward Observation Station'">
    <div x-data="{
        search: '',
        wardFilter: 'all',
        showLogModal: false,
        showHandoverModal: false,
        selectedPatient: { patient_code: '', name: '', ward_room: '', diagnosis: '', vitals_summary: '' },
        bp: '120/80',
        hr: 75,
        temp: 36.8,
        spo2: 98,
        resp: 16,
        pain: 2,
        openLogVitals(p) {
            this.selectedPatient = { ...p };
            this.showLogModal = true;
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
                <span class="text-xs font-mono text-pink-400 font-bold uppercase tracking-wider">Clinical Station: Nursing Vitals & Bedside Observations</span>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded-full bg-pink-950 text-pink-300 border border-pink-800 font-mono text-[11px] flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-pink-400 animate-pulse"></span>
                    <span>Active Shift Round: In-Patient Ward Floor</span>
                </span>
            </div>
        </div>

        <!-- Dedicated Workstation Header Banner -->
        <div class="glass-card rounded-2xl p-6 sm:p-8 border border-pink-500/30 bg-gradient-to-r from-slate-950 via-slate-900 to-pink-950/40 relative overflow-hidden shadow-2xl">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full bg-pink-500/20 text-pink-300 border border-pink-500/40 flex items-center gap-1.5">
                            <span>💉</span>
                            <span>Shift Nursing Station</span>
                        </span>
                        <span class="text-xs text-slate-400 font-mono">
                            {{ $user->email }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        In-Patient Bed Roster & Shift Vitals Charting
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">
                        Attending Registered Nurse: <strong class="text-pink-300">{{ $user->name }} (RN)</strong> • Bedside Vital Signs & Medication Administration Record (MAR)
                    </p>
                </div>

                <!-- Action Controls on Header -->
                <div class="flex flex-wrap items-center gap-3">
                    <button @click="showHandoverModal = true" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-pink-300 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer">
                        <span>📝</span>
                        <span>Shift Handover Log</span>
                    </button>
                    <button @click="alert('Vitals synchronization broadcast triggered across all in-patient bed monitors.')" class="px-4 py-2.5 rounded-xl bg-pink-500 hover:bg-pink-400 text-slate-950 font-bold text-xs shadow-lg shadow-pink-500/20 transition-all flex items-center gap-2 cursor-pointer">
                        <span>⏱️</span>
                        <span>Sync Bed Monitors</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Live Station KPI Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active In-Patient Beds</span>
                    <span class="p-2 rounded-xl bg-pink-950/60 border border-pink-500/20 text-pink-400">🛏️</span>
                </div>
                <div class="text-3xl font-black text-pink-400 mt-2">{{ $admittedCount }} Beds</div>
                <div class="text-xs text-slate-400 mt-1">General & Surgical Wards</div>
            </div>

            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">ICU Monitored Cases</span>
                    <span class="p-2 rounded-xl bg-rose-950/60 border border-rose-500/20 text-rose-400">🚨</span>
                </div>
                <div class="text-3xl font-black text-rose-400 mt-2">{{ $icuPatients->count() }} Patients</div>
                <div class="text-xs text-rose-400/80 mt-1">Continuous Pulse Oximetry</div>
            </div>

            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Vitals Checks Due</span>
                    <span class="p-2 rounded-xl bg-amber-950/60 border border-amber-500/20 text-amber-400">⏱️</span>
                </div>
                <div class="text-3xl font-black text-amber-400 mt-2">7 Due Now</div>
                <div class="text-xs text-amber-400/80 mt-1">Current Hourly Round</div>
            </div>

            <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Meds Administered</span>
                    <span class="p-2 rounded-xl bg-emerald-950/60 border border-emerald-500/20 text-emerald-400">💉</span>
                </div>
                <div class="text-3xl font-black text-emerald-400 mt-2">34 Logged</div>
                <div class="text-xs text-emerald-400/80 mt-1">100% On Schedule Today</div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="glass-card rounded-2xl p-4 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="w-full sm:w-96 relative">
                <input type="text" x-model="search" placeholder="Search patients by name, code, or ward room..."
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-pink-500 focus:outline-none">
            </div>
            <div class="flex items-center gap-3 text-xs w-full sm:w-auto">
                <span class="text-slate-400 whitespace-nowrap">Filter Ward:</span>
                <select x-model="wardFilter" class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs text-slate-200 focus:border-pink-500">
                    <option value="all">All Hospital Wards</option>
                    <option value="ICU">Intensive Care (ICU)</option>
                    <option value="Admitted">General In-Patient Ward</option>
                </select>
            </div>
        </div>

        <!-- In-Patient Bed Roster & Live Vitals Matrix -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($patients as $p)
                <div class="glass-card rounded-2xl p-5 border {{ $p->admission_status === 'ICU' ? 'border-rose-500/50 bg-rose-950/10 ring-1 ring-rose-500/20' : 'border-slate-800 bg-slate-900/60' }} space-y-4"
                    x-show="(wardFilter === 'all' || wardFilter === '{{ $p->admission_status }}') && ('{{ strtolower($p->name . ' ' . $p->patient_code . ' ' . $p->ward_room . ' ' . $p->diagnosis) }}'.includes(search.toLowerCase()))">
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-mono text-xs font-bold text-pink-400">{{ $p->patient_code }}</span>
                            <h3 class="text-base font-bold text-white mt-0.5">{{ $p->name }}</h3>
                            <span class="text-[11px] text-slate-400">{{ $p->age }} yrs • {{ $p->gender }} • <strong class="text-rose-400">{{ $p->blood_group }}</strong></span>
                        </div>
                        <div class="text-right">
                            <span class="px-2.5 py-1 rounded-xl text-xs font-bold font-mono {{ $p->admission_status === 'ICU' ? 'bg-rose-950 text-rose-300 border border-rose-700 animate-pulse' : 'bg-pink-950 text-pink-300 border border-pink-800' }}">
                                {{ $p->ward_room }}
                            </span>
                            <div class="text-[10px] text-slate-500 mt-1">Room Bed Unit</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-950/80 border border-slate-800/80 space-y-1">
                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Primary Clinical Diagnosis:</div>
                        <div class="text-xs text-slate-200 font-medium">{{ $p->diagnosis }}</div>
                    </div>

                    <!-- Telemetry Metrics Display -->
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="p-2 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-[9px] text-slate-500 block uppercase">Blood Pressure</span>
                            <span class="text-xs font-mono font-bold text-cyan-300">{{ explode('|', $p->vitals_summary ?? 'BP: 120/80 | HR: 72')[0] }}</span>
                        </div>
                        <div class="p-2 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-[9px] text-slate-500 block uppercase">Heart Rate</span>
                            <span class="text-xs font-mono font-bold text-emerald-400">74 bpm</span>
                        </div>
                        <div class="p-2 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-[9px] text-slate-500 block uppercase">SpO2 Oxygen</span>
                            <span class="text-xs font-mono font-bold text-pink-300">99%</span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/60 flex items-center justify-between text-xs">
                        <span class="text-slate-400 text-[11px]">Attending Doctor: <strong class="text-white">{{ $p->attending_doctor }}</strong></span>
                        <span class="text-pink-400 text-[11px] font-mono">Assigned RN: Beatrice</span>
                    </div>

                    <div class="flex items-center gap-2 pt-1 border-t border-slate-800">
                        <button type="button" @click='openLogVitals({{ json_encode(["patient_code" => $p->patient_code, "name" => $p->name, "ward_room" => $p->ward_room, "diagnosis" => $p->diagnosis, "vitals_summary" => $p->vitals_summary]) }})'
                            class="flex-1 py-2 rounded-xl bg-pink-600 hover:bg-pink-500 text-slate-950 font-bold text-xs transition-all shadow-md text-center">
                            Log Vitals ⏱️
                        </button>
                        <button type="button" @click="alert('Scheduled dose for {{ $p->name }} verified and administered.')"
                            class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-pink-300 font-bold text-xs border border-slate-700 transition-all">
                            Administer Med 💉
                        </button>
                    </div>

                </div>
            @endforeach
        </div>

        <!-- Modal: Log Vitals -->
        <div x-show="showLogModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showLogModal = false" class="glass-card rounded-2xl p-6 border border-pink-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>⏱️</span>
                        <span>Record Shift Vital Signs</span>
                    </h3>
                    <button @click="showLogModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400">Patient:</span>
                        <span class="text-white font-bold ml-1" x-text="selectedPatient.name"></span>
                        <span class="text-pink-400 font-mono ml-1" x-text="'(' + selectedPatient.patient_code + ' - ' + selectedPatient.ward_room + ')'"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Blood Pressure (mmHg)</label>
                            <input type="text" x-model="bp" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Heart Rate (bpm)</label>
                            <input type="number" x-model="hr" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Body Temperature (°C)</label>
                            <input type="text" x-model="temp" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Oxygen Saturation SpO2 (%)</label>
                            <input type="number" x-model="spo2" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Respiratory Rate (breaths/min)</label>
                            <input type="number" x-model="resp" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Patient Pain Scale (0-10)</label>
                            <input type="number" min="0" max="10" x-model="pain" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Nursing Shift Observations</label>
                        <textarea rows="2" placeholder="Patient alert, resting comfortably, IV fluids running well." class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200 text-xs"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showLogModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="button" @click="showLogModal = false; alert('Vital signs record saved and transmitted to attending physician!')" class="px-5 py-2 rounded-xl bg-pink-600 hover:bg-pink-500 font-bold text-slate-950 transition-all">Submit Vitals Record 💾</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Shift Handover Notes -->
        <div x-show="showHandoverModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showHandoverModal = false" class="glass-card rounded-2xl p-6 border border-pink-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>📝</span>
                        <span>Nursing Shift Handover Briefing</span>
                    </h3>
                    <button @click="showHandoverModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>
                <div class="space-y-3 text-xs text-slate-300">
                    <p class="text-slate-400">
                        Official SBAR (Situation, Background, Assessment, Recommendation) nursing transition briefing for incoming shift staff.
                    </p>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Outgoing Charge Nurse</label>
                        <input type="text" readonly value="{{ $user->name }} (RN)" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-pink-300 font-bold">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Ward Wing Status & Priority Patients</label>
                        <textarea rows="3" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200">ICU Bed 01 requires SpO2 check every 30 mins. Patient PAT-000003 scheduled for IV Ceftriaxone at 06:00. All narcotic counts reconciled.</textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showHandoverModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300">Close</button>
                        <button type="button" @click="showHandoverModal = false; alert('Shift handover signed and committed to nursing archive!')" class="px-5 py-2 rounded-xl bg-pink-600 hover:bg-pink-500 font-bold text-slate-950 transition-all">Sign & Archive Briefing 📝</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
