<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Sincronizar</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @include('admin.sincronizar.partials.nav')

            {{-- PASO 1: Botón inicial --}}
            <div id="paso-1" class="bg-white dark:bg-gray-800 rounded-2xl shadow p-12 flex flex-col items-center gap-6">
                <svg class="w-16 h-16 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Sincronización Odoo → MSP</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Actualiza el <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">referenceId</span>
                        en MSP con el número de cuenta de Odoo para los clientes que coinciden por nombre.
                    </p>
                </div>
                <button onclick="cargarPreview()"
                    class="inline-flex items-center gap-2 px-8 py-3 bg-cyan-500 hover:bg-cyan-600
                           text-white font-semibold rounded-xl shadow transition text-sm">
                    <svg id="icon-preview" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sincronizar
                </button>
            </div>

            {{-- PASO 2: Preview + confirmación --}}
            <div id="paso-2" class="hidden space-y-4">

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden">

                    {{-- Cabecera --}}
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Vista previa</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Se actualizarán <span id="preview-total" class="font-semibold text-cyan-600"></span> clientes en MSP.
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <div class="flex items-center gap-2">
                                <button onclick="cancelar()"
                                    class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700
                                           hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                    Cancelar
                                </button>
                                <button id="btn-confirmar" onclick="confirmar()"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-green-500 hover:bg-green-600
                                           text-white text-sm font-semibold rounded-lg shadow transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Confirmar y ejecutar
                                </button>
                            </div>
                            <div id="progreso-wrap" class="hidden w-56 space-y-1">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span id="prog-texto">Procesando...</span>
                                    <span id="prog-pct">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div id="prog-bar" class="bg-green-500 h-1.5 rounded-full transition-all duration-300" style="width:0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla preview --}}
                    <div class="overflow-x-auto max-h-[55vh] overflow-y-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700/90 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                <tr>
                                    <th class="px-4 py-3 w-10">#</th>
                                    <th class="px-4 py-3">Nombre Odoo</th>
                                    <th class="px-4 py-3">Nombre MSP</th>
                                    <th class="px-4 py-3">Nro. cuenta → referenceId</th>
                                    <th class="px-4 py-3 text-center">Similitud</th>
                                </tr>
                            </thead>
                            <tbody id="preview-tbody" class="divide-y divide-gray-100 dark:divide-gray-700"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- PASO 3: Resultado --}}
            <div id="paso-3" class="hidden bg-white dark:bg-gray-800 rounded-2xl shadow p-10 flex flex-col items-center gap-5">

                <div id="res-ok" class="hidden flex flex-col items-center gap-3 text-center">
                    <svg class="w-12 h-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-base font-semibold text-gray-800 dark:text-gray-100">
                        <span id="res-count"></span> clientes sincronizados correctamente.
                    </p>
                </div>

                <div id="res-errores" class="hidden w-full max-w-lg rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-5 py-4">
                    <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">Errores:</p>
                    <ul id="res-errores-lista" class="text-xs text-red-600 dark:text-red-400 space-y-1 list-disc list-inside"></ul>
                </div>

                <button onclick="reiniciar()"
                    class="px-5 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700
                           hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                    Volver
                </button>
            </div>

        </div>
    </div>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinning { animation: spin 1s linear infinite; }
    </style>

    <script>
        const CSRF        = '{{ csrf_token() }}';
        const URL_PREVIEW = '{{ route('admin.sincronizar.preview') }}';
        const URL_EXEC    = '{{ route('admin.sincronizar.ejecutar.post') }}';

        let clientesParaSync = [];

        // Escapa texto interpolado en innerHTML (nombres de clientes vienen de Odoo/MSP)
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        async function cargarPreview() {
            const icon = document.getElementById('icon-preview');
            icon.classList.add('spinning');

            try {
                const res  = await fetch(URL_PREVIEW, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();

                clientesParaSync = data.clientes;

                document.getElementById('preview-total').textContent = data.total;

                const tbody = document.getElementById('preview-tbody');
                tbody.innerHTML = data.clientes.map((c, i) => `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-2.5 text-gray-400 text-xs">${i + 1}</td>
                        <td class="px-4 py-2.5 text-gray-800 dark:text-gray-100">${esc(c.odoo_nombre)}</td>
                        <td class="px-4 py-2.5 text-gray-800 dark:text-gray-100">${esc(c.msp_nombre)}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-cyan-600 dark:text-cyan-400">${esc(c.numero_cuenta)}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold
                                ${c.similitud >= 90 ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700'}">
                                ${c.similitud}%
                            </span>
                        </td>
                    </tr>
                `).join('');

                document.getElementById('paso-1').classList.add('hidden');
                document.getElementById('paso-2').classList.remove('hidden');

            } catch (e) {
                alert('Error al cargar preview: ' + e.message);
            } finally {
                icon.classList.remove('spinning');
            }
        }

        async function confirmar() {
            const btn      = document.getElementById('btn-confirmar');
            const progWrap = document.getElementById('progreso-wrap');
            const progBar  = document.getElementById('prog-bar');
            const progPct  = document.getElementById('prog-pct');
            const progTxt  = document.getElementById('prog-texto');

            btn.disabled = true;
            btn.classList.add('opacity-60', 'cursor-not-allowed');
            progWrap.classList.remove('hidden');

            const LOTE_SIZE = 20;
            const total     = clientesParaSync.length;
            let procesados  = 0;
            let okTotal     = 0;
            let erroresTotal = [];

            const lotes = [];
            for (let i = 0; i < total; i += LOTE_SIZE) {
                lotes.push(clientesParaSync.slice(i, i + LOTE_SIZE));
            }

            try {
                for (const lote of lotes) {
                    const res  = await fetch(URL_EXEC, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept':       'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ lote }),
                    });

                    if (!res.ok) {
                        const text = await res.text();
                        throw new Error(`HTTP ${res.status}: ${text.substring(0, 100)}`);
                    }

                    const data = await res.json();
                    okTotal       += data.actualizados ?? 0;
                    erroresTotal   = erroresTotal.concat(data.errores ?? []);
                    procesados    += lote.length;

                    const pct = Math.round((procesados / total) * 100);
                    progBar.style.width = pct + '%';
                    progPct.textContent = pct + '%';
                    progTxt.textContent = `${procesados} / ${total}`;
                }

                document.getElementById('paso-2').classList.add('hidden');
                document.getElementById('paso-3').classList.remove('hidden');
                document.getElementById('res-ok').classList.remove('hidden');
                document.getElementById('res-count').textContent = okTotal + ' / ' + total;

                if (erroresTotal.length > 0) {
                    document.getElementById('res-errores').classList.remove('hidden');
                    document.getElementById('res-errores-lista').innerHTML =
                        erroresTotal.map(e => `<li>${esc(e)}</li>`).join('');
                }

            } catch (e) {
                alert('Error al ejecutar: ' + e.message);
                btn.disabled = false;
                btn.classList.remove('opacity-60', 'cursor-not-allowed');
                progWrap.classList.add('hidden');
            }
        }

        function cancelar() {
            document.getElementById('paso-2').classList.add('hidden');
            document.getElementById('paso-1').classList.remove('hidden');
        }

        function reiniciar() {
            document.getElementById('paso-3').classList.add('hidden');
            document.getElementById('paso-1').classList.remove('hidden');
            document.getElementById('res-ok').classList.add('hidden');
            document.getElementById('res-errores').classList.add('hidden');
        }
    </script>
</x-app-layout>
