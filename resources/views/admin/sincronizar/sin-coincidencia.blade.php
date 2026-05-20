<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Sincronizar</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Pareo manual de clientes</p>
            </div>
            <div class="flex items-center gap-2">
                <span id="badge-odoo" class="text-xs font-medium px-2.5 py-1 rounded-full bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400">
                    {{ count($odooSinMatch) }} sin match en Odoo
                </span>
                <span id="badge-msp" class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                    {{ count($mspSinMatch) }} clientes MSP sin match
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 pb-44">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3">

            @include('admin.sincronizar.partials.nav')

            @if (session('success'))
                <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Instrucciones --}}
            <div class="flex items-start gap-3 px-4 py-3 rounded-xl bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 text-sm text-orange-700 dark:text-orange-300">
                <i class="fa-solid fa-circle-info text-base flex-shrink-0 mt-0.5"></i>
                <span>
                    <strong>1.</strong> Haz clic en un cliente <strong>MSP</strong> — queda activo (parpadea).
                    <strong>2.</strong> Haz clic en uno o varios clientes <strong>Odoo</strong> — todos se enlazan a ese MSP.
                    <strong>3.</strong> Haz clic en otro cliente MSP para iniciar un nuevo grupo.
                    <strong>4.</strong> Pulsa <strong>Enlazar todos</strong> (se guardará como: Cuenta 1: 100, Cuenta 2: 101...).
                </span>
            </div>

            {{-- Buscadores --}}
            <div class="grid grid-cols-2 gap-4">
                <input type="text" placeholder="Buscar en Odoo…"
                    class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                           text-gray-800 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                    oninput="filtrarOdoo(this.value)">
                <input type="text" placeholder="Buscar en MSP…"
                    class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                           text-gray-800 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400"
                    oninput="filtrarMsp(this.value)">
            </div>

            {{-- Tablas con scroll independiente --}}
            <div class="grid grid-cols-2 gap-4" style="height:calc(100vh - 430px); min-height:300px">

                {{-- ODOO --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden flex flex-col">
                    <div class="flex-shrink-0 px-5 py-3 border-b border-gray-200 dark:border-gray-700
                                flex items-center justify-between bg-yellow-50/60 dark:bg-yellow-900/10">
                        <span class="text-sm font-semibold text-yellow-700 dark:text-yellow-400">
                            <i class="fa-solid fa-database mr-1.5"></i>Odoo — sin coincidencia
                        </span>
                        <span id="count-odoo" class="text-xs text-yellow-600 dark:text-yellow-500">{{ count($odooSinMatch) }} registros</span>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700/90 text-xs font-semibold
                                          text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                <tr>
                                    <th class="px-4 py-2.5 w-8">#</th>
                                    <th class="px-4 py-2.5">Nombre</th>
                                    <th class="px-4 py-2.5">N° Cuenta</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-odoo" class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($odooSinMatch as $i => $fila)
                                    <tr class="fila-odoo cursor-pointer transition-colors hover:bg-yellow-50/60 dark:hover:bg-yellow-900/10"
                                        data-nombre="{{ strtolower($fila['odoo_nombre']) }}"
                                        data-cuenta="{{ $fila['numero_cuenta'] }}"
                                        data-odoo-nombre="{{ $fila['odoo_nombre'] }}"
                                        onclick="clickOdoo(this)">
                                        <td class="px-4 py-2.5 text-gray-400 text-xs tabular-nums">{{ $i + 1 }}</td>
                                        <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-gray-100">
                                            <span class="color-dot hidden w-2.5 h-2.5 rounded-full inline-block mr-1.5 align-middle flex-shrink-0"></span>{{ $fila['odoo_nombre'] }}
                                        </td>
                                        <td class="px-4 py-2.5 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $fila['numero_cuenta'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400 text-sm">Sin registros</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div id="sin-odoo" class="hidden flex-shrink-0 px-4 py-6 text-center text-gray-400 text-sm">Sin resultados.</div>
                </div>

                {{-- MSP --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden flex flex-col">
                    <div class="flex-shrink-0 px-5 py-3 border-b border-gray-200 dark:border-gray-700
                                flex items-center justify-between bg-orange-50/60 dark:bg-orange-900/10">
                        <span class="text-sm font-semibold text-orange-700 dark:text-orange-400">
                            <i class="fa-solid fa-cloud mr-1.5"></i>MSP — sin match
                        </span>
                        <span id="count-msp" class="text-xs text-orange-600 dark:text-orange-500">{{ count($mspSinMatch) }} registros</span>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700/90 text-xs font-semibold
                                          text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                <tr>
                                    <th class="px-4 py-2.5 w-8">#</th>
                                    <th class="px-4 py-2.5">Nombre MSP</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-msp" class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($mspSinMatch as $i => $cliente)
                                    <tr class="fila-msp cursor-pointer transition-colors hover:bg-orange-50/60 dark:hover:bg-orange-900/10"
                                        data-nombre="{{ strtolower($cliente['msp_nombre'] ?? '') }}"
                                        data-customer-id="{{ $cliente['customer_id'] ?? '' }}"
                                        data-msp-nombre="{{ $cliente['msp_nombre'] ?? '' }}"
                                        onclick="clickMsp(this)">
                                        <td class="px-4 py-2.5 text-gray-400 text-xs tabular-nums">{{ $i + 1 }}</td>
                                        <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-gray-100">
                                            <span class="color-dot hidden w-2.5 h-2.5 rounded-full inline-block mr-1.5 align-middle flex-shrink-0"></span>{{ $cliente['msp_nombre'] ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="px-4 py-8 text-center text-gray-400 text-sm">Sin registros</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div id="sin-msp" class="hidden flex-shrink-0 px-4 py-6 text-center text-gray-400 text-sm">Sin resultados.</div>
                </div>

            </div>
        </div>
    </div>

    {{-- ══ BARRA FLOTANTE ══ --}}
    <div id="pairing-bar"
         class="hidden fixed bottom-0 left-0 right-0 z-40
                bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-2xl">

        {{-- Header --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5
                    flex items-center justify-between gap-4
                    border-b border-gray-100 dark:border-gray-700/50">
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200 min-w-0">
                <i class="fa-solid fa-link-simple text-orange-500 flex-shrink-0"></i>
                <span id="bar-title">0 grupos</span>
                <span id="bar-hint" class="hidden text-xs font-normal text-orange-500 dark:text-orange-400 ml-1 animate-pulse">
                    ← selecciona clientes MSP para agregar al grupo activo
                </span>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button onclick="limpiarTodo()" id="btn-limpiar"
                        class="hidden text-xs font-medium text-gray-400 hover:text-gray-600 dark:hover:text-gray-300
                               transition px-2 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fa-solid fa-broom mr-1"></i>Limpiar
                </button>
                <button id="btn-enlazar" onclick="confirmarEnlace()" disabled
                        class="flex items-center gap-2 px-5 py-2 rounded-xl text-white text-sm font-semibold
                               transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed
                               enabled:hover:opacity-90 enabled:active:scale-95"
                        style="background:linear-gradient(135deg,#e8610a,#f97316)">
                    <i id="btn-icon" class="fa-solid fa-link"></i>
                    <span id="btn-text">Enlazar todos</span>
                </button>
            </div>
        </div>

        {{-- Lista de grupos --}}
        <div id="grupos-list"
             class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 max-h-52 overflow-y-auto space-y-1">
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"
         class="hidden fixed top-5 right-5 z-50 flex items-center gap-3 px-4 py-3
                rounded-xl shadow-xl text-sm font-medium text-white min-w-72 max-w-sm">
        <i id="toast-icon" class="fa-solid fa-check-circle text-base flex-shrink-0"></i>
        <span id="toast-msg"></span>
    </div>

    {{-- ══ ESTILOS DE COLOR ══ --}}
    <style>
    .par-0  { background:#dbeafe !important; outline:2px solid #93c5fd;  outline-offset:-1px; }
    .par-1  { background:#fee2e2 !important; outline:2px solid #fca5a5;  outline-offset:-1px; }
    .par-2  { background:#dcfce7 !important; outline:2px solid #86efac;  outline-offset:-1px; }
    .par-3  { background:#f3e8ff !important; outline:2px solid #d8b4fe;  outline-offset:-1px; }
    .par-4  { background:#ccfbf1 !important; outline:2px solid #5eead4;  outline-offset:-1px; }
    .par-5  { background:#fce7f3 !important; outline:2px solid #f9a8d4;  outline-offset:-1px; }
    .par-6  { background:#fff7ed !important; outline:2px solid #fdba74;  outline-offset:-1px; }
    .par-7  { background:#fef9c3 !important; outline:2px solid #fde047;  outline-offset:-1px; }

    .dark .par-0 { background:rgba(59,130,246,.18)  !important; outline-color:rgba(96,165,250,.55);  }
    .dark .par-1 { background:rgba(239,68,68,.18)   !important; outline-color:rgba(252,165,165,.55); }
    .dark .par-2 { background:rgba(34,197,94,.18)   !important; outline-color:rgba(134,239,172,.55); }
    .dark .par-3 { background:rgba(168,85,247,.18)  !important; outline-color:rgba(216,180,254,.55); }
    .dark .par-4 { background:rgba(20,184,166,.18)  !important; outline-color:rgba(94,234,212,.55);  }
    .dark .par-5 { background:rgba(236,72,153,.18)  !important; outline-color:rgba(249,168,212,.55); }
    .dark .par-6 { background:rgba(234,88,12,.18)   !important; outline-color:rgba(253,186,116,.55); }
    .dark .par-7 { background:rgba(202,138,4,.18)   !important; outline-color:rgba(253,224,71,.55);  }

    .par-pending { animation:parpadeo 1s ease-in-out infinite; }
    @keyframes parpadeo { 0%,100%{opacity:1} 50%{opacity:.4} }

    /* Input editable de cuenta por MSP */
    .cuenta-input {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 11px;
        padding: 2px 7px;
        border: 1px solid #d1d5db;
        border-radius: 5px;
        width: 120px;
        outline: none;
        background: #ffffff;
        color: #111827;
        transition: border-color .15s, box-shadow .15s;
    }
    .cuenta-input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 2px rgba(249,115,22,.15);
    }
    .dark .cuenta-input {
        background: #1f2937;
        border-color: #374151;
        color: #f3f4f6;
    }
    .dark .cuenta-input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 2px rgba(249,115,22,.2);
    }
    </style>

    {{-- ══ JAVASCRIPT ══ --}}
    <script>
    const CSRF        = '{{ csrf_token() }}';
    const URL_ENLAZAR = '{{ route("admin.sincronizar.enlazar") }}';

    const PALETTE = [
        { cls:'par-0', dot:'#3b82f6' },
        { cls:'par-1', dot:'#ef4444' },
        { cls:'par-2', dot:'#22c55e' },
        { cls:'par-3', dot:'#a855f7' },
        { cls:'par-4', dot:'#14b8a6' },
        { cls:'par-5', dot:'#ec4899' },
        { cls:'par-6', dot:'#ea580c' },
        { cls:'par-7', dot:'#ca8a04' },
    ];

    /* ── Estado ────────────────────────────────────────────────────────────── */
    // grupos: [{ id, colorIdx, mspTr, mspNombre, customerId, odooItems:[{tr,nombre,cuenta}] }]
    let grupos        = [];
    let activeGrupoId = null;
    let nextColorIdx  = 0;
    let nextId        = 0;

    /* ── Helpers de búsqueda ───────────────────────────────────────────────── */
    const getGrupo       = id  => grupos.find(g => g.id === id);
    const grupoDeMsp     = tr  => grupos.find(g => g.mspTr === tr);
    const grupoDeOdoo    = tr  => grupos.find(g => g.odooItems.some(o => o.tr === tr));

    /* ── Marcar / desmarcar filas ──────────────────────────────────────────── */
    function marcarFila(tr, colorIdx, pending = false) {
        const color = PALETTE[colorIdx % PALETTE.length];
        tr.classList.add(color.cls);
        if (pending) tr.classList.add('par-pending');
        const dot = tr.querySelector('.color-dot');
        if (dot) { dot.style.background = color.dot; dot.classList.remove('hidden'); }
    }

    function desmarcarFila(tr) {
        PALETTE.forEach(c => tr.classList.remove(c.cls));
        tr.classList.remove('par-pending');
        const dot = tr.querySelector('.color-dot');
        if (dot) dot.classList.add('hidden');
    }

    /* ── Activar / desactivar grupo ────────────────────────────────────────── */
    function activarGrupo(id) {
        // Desactivar el actual (si tiene Odoo se queda, si no se elimina)
        if (activeGrupoId !== null) {
            const g = getGrupo(activeGrupoId);
            if (g) {
                g.mspTr.classList.remove('par-pending');
                if (g.odooItems.length === 0) {
                    desmarcarFila(g.mspTr);
                    grupos = grupos.filter(x => x.id !== activeGrupoId);
                }
            }
            activeGrupoId = null;
        }

        if (id !== null) {
            const g = getGrupo(id);
            if (g) { g.mspTr.classList.add('par-pending'); activeGrupoId = id; }
        }

        sincronizarBarra();
    }

    /* ── Click MSP ─────────────────────────────────────────────────────────── */
    function clickMsp(tr) {
        const existing = grupoDeMsp(tr);

        if (existing) {
            if (activeGrupoId === existing.id) {
                // Clic sobre el activo: si tiene Odoo lo desactiva, si no lo elimina
                if (existing.odooItems.length === 0) {
                    eliminarGrupo(existing.id);
                } else {
                    existing.mspTr.classList.remove('par-pending');
                    activeGrupoId = null;
                    sincronizarBarra();
                }
            } else {
                // Reactivar un grupo existente
                activarGrupo(existing.id);
            }
            return;
        }

        // Nuevo grupo
        const id       = nextId++;
        const colorIdx = nextColorIdx++ % PALETTE.length;
        marcarFila(tr, colorIdx, true);
        grupos.push({
            id,
            colorIdx,
            mspTr: tr,
            mspNombre: tr.dataset.mspNombre,
            customerId: tr.dataset.customerId,
            odooItems: []
        });
        activarGrupo(id);
    }

    /* ── Click Odoo ────────────────────────────────────────────────────────── */
    function clickOdoo(tr) {
        // ¿Ya está en algún grupo? → quitarlo
        const existing = grupoDeOdoo(tr);
        if (existing) {
            desmarcarFila(tr);
            existing.odooItems = existing.odooItems.filter(o => o.tr !== tr);
            sincronizarBarra();
            return;
        }

        // Sin grupo activo: flash de aviso
        if (activeGrupoId === null) {
            tr.style.transition = 'outline .1s';
            tr.style.outline    = '2px solid #f97316';
            setTimeout(() => { tr.style.outline = ''; }, 500);
            return;
        }

        const g = getGrupo(activeGrupoId);
        marcarFila(tr, g.colorIdx, false);
        g.odooItems.push({
            tr,
            nombre: tr.dataset.odooNombre,
            cuenta: tr.dataset.cuenta,
        });
        sincronizarBarra();
    }

    /* ── Eliminar un grupo completo ────────────────────────────────────────── */
    function eliminarGrupo(id) {
        const g = getGrupo(id);
        if (!g) return;
        desmarcarFila(g.mspTr);
        g.odooItems.forEach(o => desmarcarFila(o.tr));
        grupos = grupos.filter(x => x.id !== id);
        if (activeGrupoId === id) activeGrupoId = null;
        sincronizarBarra();
    }

    /* ── Limpiar todo ──────────────────────────────────────────────────────── */
    function limpiarTodo() {
        grupos.forEach(g => { desmarcarFila(g.mspTr); g.odooItems.forEach(o => desmarcarFila(o.tr)); });
        grupos        = [];
        activeGrupoId = null;
        nextColorIdx  = 0;
        sincronizarBarra();
    }

    /* ── Sincronizar barra ─────────────────────────────────────────────────── */
    function sincronizarBarra() {
        const bar        = document.getElementById('pairing-bar');
        const title      = document.getElementById('bar-title');
        const hint       = document.getElementById('bar-hint');
        const lista      = document.getElementById('grupos-list');
        const btnEnlazar = document.getElementById('btn-enlazar');
        const btnLimpiar = document.getElementById('btn-limpiar');

        const hayAlgo       = grupos.length > 0;
        const totalEnlaces  = grupos.reduce((s, g) => s + g.odooItems.length, 0);
        const gruposListos  = grupos.filter(g => g.odooItems.length > 0).length;

        bar.classList.toggle('hidden', !hayAlgo && activeGrupoId === null);

        // Título
        const partes = [];
        if (gruposListos > 0)  partes.push(`${gruposListos} grupo${gruposListos > 1 ? 's' : ''}`);
        if (totalEnlaces > 0)  partes.push(`${totalEnlaces} cuenta${totalEnlaces > 1 ? 's' : ''} Odoo`);
        title.textContent = partes.length ? partes.join(' · ') : 'Sin grupos';

        // Hint activo
        hint.classList.toggle('hidden', activeGrupoId === null);

        // Botones
        btnEnlazar.disabled = totalEnlaces === 0;
        btnLimpiar.classList.toggle('hidden', !hayAlgo);

        // Renderizar grupos — MSP arriba, Odoos abajo
        lista.innerHTML = grupos.map(g => {
            const color    = PALETTE[g.colorIdx % PALETTE.length];
            const isActive = g.id === activeGrupoId;

            const odooFilas = g.odooItems.map((o, idx) => `
                <div class="flex items-center gap-2 pl-6 py-1">
                    <span class="text-gray-300 dark:text-gray-600 text-[10px] flex-shrink-0 w-3">
                        ${idx === g.odooItems.length - 1 ? '└' : '├'}
                    </span>
                    <span class="flex-1 min-w-0 truncate text-xs text-gray-700 dark:text-gray-300"
                          title="${escHtml(o.nombre)}">${escHtml(o.nombre)}</span>
                    <span class="text-[10px] text-gray-400 flex-shrink-0">cuenta:</span>
                    <span class="font-mono text-[11px] text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">
                        ${escHtml(o.cuenta)}
                    </span>
                    <button onclick="event.stopPropagation(); quitarOdoo(${g.id},'${escJs(o.cuenta)}')"
                            class="flex-shrink-0 w-4 h-4 flex items-center justify-center rounded
                                   text-gray-300 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                        <i class="fa-solid fa-xmark text-[9px]"></i>
                    </button>
                </div>`).join('');

            const emptyHint = g.odooItems.length === 0 && isActive
                ? `<div class="pl-9 py-1 text-[11px] text-orange-400 italic">
                       selecciona clientes Odoo para asignar…
                   </div>`
                : '';

            return `
            <div class="rounded-lg overflow-hidden border ${isActive
                ? 'border-orange-200 dark:border-orange-800'
                : 'border-gray-100 dark:border-gray-700/50'}">
                <div class="flex items-center gap-2 px-2 py-1.5 text-xs
                            ${isActive
                                ? 'bg-orange-50 dark:bg-orange-900/15'
                                : 'bg-gray-50/80 dark:bg-gray-700/30'}">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 ${isActive ? 'animate-pulse' : ''}"
                          style="background:${color.dot}"></span>
                    <span class="font-semibold text-gray-800 dark:text-white truncate flex-1"
                          title="${escHtml(g.mspNombre)}">${escHtml(g.mspNombre)}</span>
                    <span class="text-gray-400 flex-shrink-0 text-[10px]">MSP</span>
                    ${isActive
                        ? '<span class="text-[9px] font-bold text-orange-500 uppercase tracking-wide flex-shrink-0">activo</span>'
                        : ''}
                    <span class="text-gray-400 flex-shrink-0 text-[10px]">${g.odooItems.length} Odoo</span>
                    <button onclick="eliminarGrupo(${g.id})"
                            class="flex-shrink-0 w-4 h-4 flex items-center justify-center rounded
                                   text-gray-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                        <i class="fa-solid fa-xmark text-[9px]"></i>
                    </button>
                </div>
                ${odooFilas}
                ${emptyHint}
            </div>`;
        }).join('');
    }

    /* ── Quitar un Odoo específico de un grupo (desde la barra) ─────────────── */
    function quitarOdoo(grupoId, cuenta) {
        const g = getGrupo(grupoId);
        if (!g) return;
        const item = g.odooItems.find(o => o.cuenta === cuenta);
        if (item) { desmarcarFila(item.tr); g.odooItems = g.odooItems.filter(o => o.cuenta !== cuenta); }
        sincronizarBarra();
    }

    /* ── Confirmar y enviar ────────────────────────────────────────────────── */
    async function confirmarEnlace() {
        const totalEnlaces = grupos.reduce((s, g) => s + g.odooItems.length, 0);
        if (totalEnlaces === 0) return;

        const btnEnlazar = document.getElementById('btn-enlazar');
        const btnIcon    = document.getElementById('btn-icon');
        const btnText    = document.getElementById('btn-text');

        btnEnlazar.disabled = true;
        btnIcon.className   = 'fa-solid fa-spinner fa-spin';
        btnText.textContent = `Enlazando ${grupos.length} MSP…`;

        // Construir pares: 1 MSP -> N Odoo (formateado en ReferenceId)
        const pares = grupos.filter(g => g.odooItems.length > 0).map(g => {
            // Formato: "Cuenta 1: 100, Cuenta 2: 101"
            const refId = g.odooItems.map((o, i) => `Cuenta ${i+1}: ${o.cuenta}`).join(', ');

            return {
                customer_id:   g.customerId,
                customer_name: g.mspNombre,
                numero_cuenta: refId,
            };
        });

        try {
            const res  = await fetch(URL_ENLAZAR, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
                body: JSON.stringify({ pares }),
            });
            const data = await res.json();

            if (!res.ok && (data.enlazados ?? 0) === 0) {
                throw new Error(data.errores?.join(' | ') ?? `Error ${res.status}`);
            }

            // Animar y eliminar filas enlazadas
            grupos.forEach(g => {
                [g.mspTr, ...g.odooItems.map(o => o.tr)].forEach(tr => {
                    tr.style.transition = 'opacity .25s';
                    tr.style.opacity    = '0';
                    setTimeout(() => { tr.remove(); actualizarContadores(); }, 280);
                });
            });

            const n = data.enlazados ?? 0;
            toast(
                `${n} cliente${n !== 1 ? 's' : ''} MSP enlazado${n !== 1 ? 's' : ''}` +
                (data.errores?.length ? ` · Errores: ${data.errores.join(', ')}` : ''),
                n > 0 ? 'ok' : 'err'
            );

            limpiarTodo();
            nextColorIdx = 0;

        } catch (err) {
            toast('Error: ' + err.message, 'err');
        } finally {
            btnEnlazar.disabled = grupos.reduce((s, g) => s + g.odooItems.length, 0) === 0;
            btnIcon.className   = 'fa-solid fa-link';
            btnText.textContent = 'Enlazar todos';
        }
    }

    /* ── Contadores de tabla ───────────────────────────────────────────────── */
    function actualizarContadores() {
        const nO = document.querySelectorAll('#tbody-odoo tr.fila-odoo').length;
        const nM = document.querySelectorAll('#tbody-msp  tr.fila-msp').length;
        document.getElementById('count-odoo').textContent = nO + ' registros';
        document.getElementById('count-msp').textContent  = nM + ' registros';
        document.getElementById('badge-odoo').textContent = nO + ' sin match en Odoo';
        document.getElementById('badge-msp').textContent  = nM + ' clientes MSP sin ReferenceId';
    }

    /* ── Toast ─────────────────────────────────────────────────────────────── */
    function toast(msg, type = 'ok') {
        const el = document.getElementById('toast');
        el.style.background = type === 'ok' ? '#16a34a' : '#dc2626';
        document.getElementById('toast-icon').className =
            `fa-solid ${type === 'ok' ? 'fa-check-circle' : 'fa-circle-xmark'} text-base flex-shrink-0`;
        document.getElementById('toast-msg').textContent = msg;
        el.classList.remove('hidden');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.add('hidden'), 5000);
    }

    /* ── Escape ────────────────────────────────────────────────────────────── */
    const escHtml = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const escJs   = s => String(s).replace(/'/g,"\\'");

    /* ── Filtros ───────────────────────────────────────────────────────────── */
    function filtrarOdoo(q) {
        q = q.toLowerCase().trim();
        let vis = 0;
        document.querySelectorAll('.fila-odoo').forEach(f => {
            const ok = !q || f.dataset.nombre.includes(q) || f.dataset.cuenta.toLowerCase().includes(q);
            f.classList.toggle('hidden', !ok);
            if (ok) vis++;
        });
        document.getElementById('sin-odoo').classList.toggle('hidden', vis > 0);
        document.getElementById('count-odoo').textContent = vis + ' registros';
    }

    function filtrarMsp(q) {
        q = q.toLowerCase().trim();
        let vis = 0;
        document.querySelectorAll('.fila-msp').forEach(f => {
            const ok = !q || f.dataset.nombre.includes(q);
            f.classList.toggle('hidden', !ok);
            if (ok) vis++;
        });
        document.getElementById('sin-msp').classList.toggle('hidden', vis > 0);
        document.getElementById('count-msp').textContent = vis + ' registros';
    }
    </script>
</x-app-layout>
