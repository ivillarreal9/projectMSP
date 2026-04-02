{{-- resources/views/admin/reports/msp/correos.blade.php --}}
@extends('admin.reports.msp.layouts.app')
@section('title', 'Envío de Correos MSP')

@section('content')
<div class="space-y-6 fade-in">

    {{-- Selector de período --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
        <form method="GET" class="flex flex-wrap gap-4 items-center justify-between">
            <div class="flex gap-4 items-end">
                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Período</label>
                    <select name="periodo" onchange="this.form.submit()"
                            class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach($periodos as $p)
                            <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-sm text-gray-500 pb-2">
                    <i class="fa-solid fa-users mr-1"></i>
                    {{ $clientes->count() }} clientes en este período
                </div>
            </div>
        </form>
    </div>

    {{-- Botones principales --}}
    <div style="display:flex; gap:16px;">
        <button onclick="showPanel('individual')"
                id="btnIndividual"
                style="flex:1"
                class="group flex items-center gap-4 p-6 rounded-2xl border-2 border-orange-400 bg-white dark:bg-gray-800
                       hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all shadow-sm active:scale-95">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0"
                 style="background:#d4520a">
                <i class="fa-solid fa-paper-plane text-white text-xl"></i>
            </div>
            <div class="text-left">
                <div class="font-bold text-gray-800 dark:text-white text-base">Envío Individual</div>
                <div class="text-sm text-gray-500 mt-0.5">Envía el reporte PDF a un cliente específico</div>
            </div>
            <i class="fa-solid fa-chevron-right ml-auto text-orange-400 group-hover:translate-x-1 transition-transform"></i>
        </button>

        <button onclick="showPanel('masivo')"
                id="btnMasivo"
                style="flex:1"
                class="group flex items-center gap-4 p-6 rounded-2xl border-2 border-blue-400 bg-white dark:bg-gray-800
                       hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all shadow-sm active:scale-95">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 bg-blue-600">
                <i class="fa-solid fa-envelope-bulk text-white text-xl"></i>
            </div>
            <div class="text-left">
                <div class="font-bold text-gray-800 dark:text-white text-base">Envío Masivo</div>
                <div class="text-sm text-gray-500 mt-0.5">Envía reportes a múltiples clientes a la vez</div>
            </div>
            <i class="fa-solid fa-chevron-right ml-auto text-blue-400 group-hover:translate-x-1 transition-transform"></i>
        </button>
    </div>

    {{-- ══ PANEL ENVÍO INDIVIDUAL ══ --}}
    <div id="panelIndividual" class="hidden">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b dark:border-gray-700 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:#d4520a">
                    <i class="fa-solid fa-paper-plane text-white text-xs"></i>
                </div>
                <h3 class="font-semibold text-gray-700 dark:text-gray-200">Envío Individual</h3>
                <button onclick="hidePanel('individual')"
                        class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-0">
                {{-- Lista clientes --}}
                <div class="border-r dark:border-gray-700">
                    <div class="p-4 border-b dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-750">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Clientes</span>
                    </div>
                    <div class="overflow-y-auto" style="max-height:400px">
                        @foreach($clientes as $cliente)
                        <label class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700
                                      cursor-pointer border-b dark:border-gray-700 last:border-0">
                            <input type="radio" name="single_client_radio" class="single-radio"
                                   value="{{ $cliente->customer_name }}"
                                   data-email="{{ $cliente->email_cliente }}"
                                   {{ request('cliente') == $cliente->customer_name ? 'checked' : '' }}>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                                    {{ $cliente->customer_name }}
                                </div>
                                <div class="text-xs text-gray-400 truncate">
                                    {{ $cliente->email_cliente ?? 'Sin email' }}
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Formulario individual --}}
                <div class="lg:col-span-2 p-6">
                    <form action="{{ route('admin.msp.correos.enviar') }}" method="POST" id="singleForm">
                        @csrf
                        <input type="hidden" name="periodo" value="{{ $periodo }}">
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Cliente seleccionado</label>
                                <select name="customer_name" id="singleCustomer"
                                        class="w-full border dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm
                                               focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white" required>
                                    <option value="">— Selecciona un cliente —</option>
                                    @foreach($clientes as $c)
                                        <option value="{{ $c->customer_name }}"
                                                data-email="{{ $c->email_cliente }}"
                                                {{ request('cliente') == $c->customer_name ? 'selected' : '' }}>
                                            {{ $c->customer_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Email destino</label>
                                <div class="relative">
                                    <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    <input type="email" name="email" id="singleEmail"
                                           placeholder="cliente@empresa.com"
                                           class="w-full border dark:border-gray-600 rounded-xl pl-9 pr-4 py-2.5 text-sm
                                                  focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white" required>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Asunto</label>
                                <input type="text" name="subject" value="Informe MSP — {{ $periodo }}"
                                       class="w-full border dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm
                                              focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white" required>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Mensaje adicional (opcional)</label>
                                <textarea name="mensaje" rows="3" placeholder="Estimado cliente..."
                                          class="w-full border dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm
                                                 focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit"
                                        class="flex items-center gap-2 text-white px-6 py-2.5 rounded-xl text-sm font-semibold
                                               hover:opacity-90 active:scale-95 transition"
                                        style="background:#d4520a">
                                    <i class="fa-solid fa-paper-plane"></i> Enviar con PDF
                                </button>
                                <a id="previewPdfLink" href="#" target="_blank"
                                   class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium
                                          border dark:border-gray-600 text-gray-600 dark:text-gray-300
                                          hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <i class="fa-solid fa-eye"></i> Ver PDF
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ PANEL ENVÍO MASIVO ══ --}}
    <div id="panelMasivo" class="hidden">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b dark:border-gray-700 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                    <i class="fa-solid fa-envelope-bulk text-white text-xs"></i>
                </div>
                <h3 class="font-semibold text-gray-700 dark:text-gray-200">Envío Masivo</h3>
                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-medium ml-2" id="masivoBadge">
                    0 clientes
                </span>
                <button onclick="hidePanel('masivo')"
                        class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-0">
                {{-- Lista clientes con checkboxes --}}
                <div class="border-r dark:border-gray-700">
                    <div class="p-4 border-b dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-750">
                        <span class="text-xs font-semibold text-gray-500 uppercase">Seleccionar clientes</span>
                        <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                            <input type="checkbox" id="selectAll" class="rounded"> Todos
                        </label>
                    </div>
                    <div class="overflow-y-auto" style="max-height:400px">
                        @foreach($clientes as $cliente)
                        <label class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700
                                      cursor-pointer border-b dark:border-gray-700 last:border-0">
                            <input type="checkbox" class="client-checkbox rounded"
                                   value="{{ $cliente->customer_name }}"
                                   data-email="{{ $cliente->email_cliente }}">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                                    {{ $cliente->customer_name }}
                                </div>
                                <div class="text-xs text-gray-400 truncate">
                                    {{ $cliente->email_cliente ?? 'Sin email' }}
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-750 border-t dark:border-gray-700 text-xs text-gray-500">
                        <span id="selectedCount">0</span> clientes seleccionados
                    </div>
                </div>

                {{-- Formulario masivo --}}
                <div class="lg:col-span-2 p-6">
                    <form action="{{ route('admin.msp.correos.masivo') }}" method="POST" id="masivoForm">
                        @csrf
                        <input type="hidden" name="periodo" value="{{ $periodo }}">
                        <div id="clientesHidden"></div>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Asunto</label>
                                <input type="text" name="subject" value="Informe MSP — {{ $periodo }}"
                                       class="w-full border dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm
                                              focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" required>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Mensaje</label>
                                <textarea name="mensaje" rows="4"
                                          placeholder="Estimado cliente, adjunto su informe MSP..."
                                          class="w-full border dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm
                                                 focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-3 text-xs text-blue-700 dark:text-blue-300">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                Se generará un PDF individual por cada cliente seleccionado y se enviará a su correo registrado.
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <button type="submit" id="masivoBtn"
                                        class="flex items-center gap-2 text-white px-6 py-2.5 rounded-xl text-sm font-semibold
                                               hover:opacity-90 active:scale-95 transition disabled:opacity-40"
                                        style="background:#1d4ed8" disabled>
                                    <i class="fa-solid fa-envelope-bulk"></i> Enviar a seleccionados
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// ── Mostrar/ocultar paneles ───────────────────────────────────────────────────
function showPanel(type) {
    document.getElementById('panelIndividual').classList.add('hidden');
    document.getElementById('panelMasivo').classList.add('hidden');

    document.getElementById('btnIndividual').classList.remove('border-orange-400');
    document.getElementById('btnMasivo').classList.remove('border-blue-400');
    document.getElementById('btnIndividual').classList.add('border-gray-200');
    document.getElementById('btnMasivo').classList.add('border-gray-200');

    if (type === 'individual') {
        document.getElementById('panelIndividual').classList.remove('hidden');
        document.getElementById('btnIndividual').classList.remove('border-gray-200');
        document.getElementById('btnIndividual').classList.add('border-orange-400');
    } else {
        document.getElementById('panelMasivo').classList.remove('hidden');
        document.getElementById('btnMasivo').classList.remove('border-gray-200');
        document.getElementById('btnMasivo').classList.add('border-blue-400');
    }

    // Scroll suave al panel
    setTimeout(() => {
        const panel = type === 'individual'
            ? document.getElementById('panelIndividual')
            : document.getElementById('panelMasivo');
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}

function hidePanel(type) {
    const panel = type === 'individual' ? 'panelIndividual' : 'panelMasivo';
    document.getElementById(panel).classList.add('hidden');
}

// Abrir automáticamente si viene con ?cliente=
@if(request('cliente'))
showPanel('individual');
@endif

// ── Checkboxes masivo ─────────────────────────────────────────────────────────
const checkboxes = document.querySelectorAll('.client-checkbox');
const selectAll  = document.getElementById('selectAll');
const countEl    = document.getElementById('selectedCount');
const badgeEl    = document.getElementById('masivoBadge');
const masivoBtn  = document.getElementById('masivoBtn');
const hiddenDiv  = document.getElementById('clientesHidden');

function updateSelection() {
    const checked = [...checkboxes].filter(c => c.checked);
    countEl.textContent = checked.length;
    badgeEl.textContent = checked.length + ' clientes';
    masivoBtn.disabled  = checked.length === 0;
    hiddenDiv.innerHTML = '';
    checked.forEach((c, i) => {
        hiddenDiv.innerHTML += `<input type="hidden" name="clientes[${i}][customer_name]" value="${c.value}">`;
        hiddenDiv.innerHTML += `<input type="hidden" name="clientes[${i}][email]" value="${c.dataset.email || ''}">`;
    });
}

checkboxes.forEach(c => c.addEventListener('change', updateSelection));
selectAll.addEventListener('change', e => {
    checkboxes.forEach(c => c.checked = e.target.checked);
    updateSelection();
});

// ── Radio individual → auto-fill ──────────────────────────────────────────────
document.querySelectorAll('.single-radio').forEach(r => {
    r.addEventListener('change', function() {
        document.getElementById('singleCustomer').value = this.value;
        document.getElementById('singleEmail').value    = this.dataset.email || '';
        const periodo = '{{ $periodo }}';
        document.getElementById('previewPdfLink').href =
            `/admin/reports/msp/pdf/${encodeURIComponent(this.value)}/preview?periodo=${periodo}`;
    });
});

// ── Select individual → auto-fill ─────────────────────────────────────────────
const singleSel = document.getElementById('singleCustomer');
singleSel.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('singleEmail').value = opt.dataset.email || '';
    const periodo = '{{ $periodo }}';
    document.getElementById('previewPdfLink').href =
        `/admin/reports/msp/pdf/${encodeURIComponent(this.value)}/preview?periodo=${periodo}`;
});
</script>
@endpush