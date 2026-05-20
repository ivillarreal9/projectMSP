<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Sincronizar</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Clientes que coinciden entre Odoo y MSP</p>
            </div>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                {{ count($filas) }} coincidencias
            </span>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

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

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden">

                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-3">
                    <input type="text" placeholder="Buscar por nombre o número..."
                        class="w-full sm:w-80 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500"
                        oninput="filtrar(this.value)">
                    <select onchange="filtrarTipo(this.value)"
                        class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                        <option value="">Todos</option>
                        <option value="exacto">Exacto (account_no)</option>
                        <option value="fuzzy">Por nombre (≥75%)</option>
                    </select>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 w-10">#</th>
                                <th class="px-4 py-3">Nombre Odoo</th>
                                <th class="px-4 py-3">Número de cuenta</th>
                                <th class="px-4 py-3">Nombre MSP</th>
                                <th class="px-4 py-3">Reference ID</th>
                                <th class="px-4 py-3 text-center">Similitud</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($filas as $i => $fila)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition fila
                                    {{ $fila['tipo'] === 'fuzzy' ? 'bg-indigo-50/40 dark:bg-indigo-900/10' : '' }}"
                                    data-buscar="{{ strtolower($fila['odoo_nombre'] . ' ' . $fila['msp_nombre'] . ' ' . $fila['numero_cuenta'] . ' ' . $fila['reference_id']) }}"
                                    data-tipo="{{ $fila['tipo'] }}">

                                    <td class="px-4 py-2.5 text-gray-400 dark:text-gray-500 tabular-nums text-xs">{{ $i + 1 }}</td>
                                    <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-gray-100">{{ $fila['odoo_nombre'] }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $fila['numero_cuenta'] }}</td>
                                    <td class="px-4 py-2.5 text-gray-800 dark:text-gray-100">{{ $fila['msp_nombre'] }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $fila['reference_id'] }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        @php
                                            $sim   = $fila['similitud'];
                                            $color = $sim >= 90
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400';
                                        @endphp
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $color }}">
                                            {{ $sim }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                                        No hay coincidencias.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="sin-resultados" class="hidden px-6 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">
                    No hay resultados para tu búsqueda.
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600"></span>
                    Exacto (account_no = ReferenceId)
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm bg-indigo-100 dark:bg-indigo-900/30 border border-indigo-300 dark:border-indigo-700"></span>
                    Por nombre ≥ 55%
                </span>
            </div>
        </div>
    </div>

    <script>
        let filtroTexto = '', filtroTipo = '';
        function aplicar() {
            let visibles = 0;
            document.querySelectorAll('.fila').forEach(f => {
                const ok = (!filtroTexto || f.dataset.buscar.includes(filtroTexto))
                        && (!filtroTipo  || f.dataset.tipo === filtroTipo);
                f.classList.toggle('hidden', !ok);
                if (ok) visibles++;
            });
            document.getElementById('sin-resultados').classList.toggle('hidden', visibles > 0);
        }
        function filtrar(q)     { filtroTexto = q.toLowerCase().trim(); aplicar(); }
        function filtrarTipo(t) { filtroTipo  = t; aplicar(); }
    </script>
</x-app-layout>
