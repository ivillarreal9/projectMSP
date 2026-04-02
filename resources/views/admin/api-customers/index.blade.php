<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Customers MSP</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Customers MSP</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Vista provisional — exportar customers a Excel
                        @if($credential)
                            <span class="inline-flex items-center gap-1 ml-2 text-green-600 dark:text-green-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                {{ $credential->username }}
                            </span>
                        @else
                            <span class="text-red-500 ml-2">Sin credenciales</span>
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button id="btn-export" onclick="exportExcel()"
                            class="hidden inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Exportar Excel
                    </button>

                    <button onclick="fetchCustomers()"
                            id="btn-fetch"
                            class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                        <svg id="btn-icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span id="btn-text">Consultar API</span>
                    </button>
                </div>
            </div>

            {{-- Error --}}
            <div id="error-box" class="hidden mb-4 flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg text-sm">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span id="error-msg"></span>
            </div>

            {{-- Tabla --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">

                {{-- Estado vacío --}}
                <div id="state-empty" class="flex flex-col items-center gap-3 py-16 text-gray-300 dark:text-gray-600">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Presiona "Consultar API" para cargar los customers</p>
                </div>

                {{-- Loading --}}
                <div id="state-loading" class="hidden flex flex-col items-center gap-3 py-16">
                    <svg class="animate-spin w-8 h-8 text-purple-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Consultando API...</p>
                </div>

                {{-- Tabla de resultados --}}
                <div id="state-results" class="hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Customer Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Customer ID</th>
                                </tr>
                            </thead>
                            <tbody id="table-body" class="divide-y divide-gray-50 dark:divide-gray-700/60"></tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                        <p id="table-footer" class="text-xs text-gray-400 dark:text-gray-500"></p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
    let hasData = false;

    function fetchCustomers() {
        setState('loading');
        hideError();

        fetch('{{ route('admin.api-customers.fetch') }}')
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    showError(data.error);
                    setState('empty');
                    return;
                }

                renderTable(data.data);
                hasData = true;
                document.getElementById('btn-export').classList.remove('hidden');
            })
            .catch(err => {
                showError('Error de conexión: ' + err.message);
                setState('empty');
            });
    }

    function renderTable(rows) {
        const tbody = document.getElementById('table-body');
        tbody.innerHTML = '';

        rows.forEach((row, i) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition';
            tr.innerHTML = `
                <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">${i + 1}</td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap">${esc(row.CustomerName)}</td>
                <td class="px-4 py-3 font-mono text-xs text-purple-600 dark:text-purple-400 whitespace-nowrap">${esc(row.CustomerId)}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('table-footer').textContent = `Total: ${rows.length} customers`;
        setState('results');
    }

    function exportExcel() {
        window.location.href = '{{ route('admin.api-customers.export') }}';
    }

    function setState(state) {
        document.getElementById('state-empty').classList.add('hidden');
        document.getElementById('state-loading').classList.add('hidden');
        document.getElementById('state-results').classList.add('hidden');

        if (state === 'empty')   document.getElementById('state-empty').classList.remove('hidden');
        if (state === 'loading') document.getElementById('state-loading').classList.remove('hidden');
        if (state === 'results') document.getElementById('state-results').classList.remove('hidden');

        const btn  = document.getElementById('btn-fetch');
        const icon = document.getElementById('btn-icon');
        const text = document.getElementById('btn-text');

        if (state === 'loading') {
            btn.disabled = true;
            icon.innerHTML = '<path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>';
            icon.classList.add('animate-spin');
            text.textContent = 'Consultando...';
        } else {
            btn.disabled = false;
            icon.classList.remove('animate-spin');
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>';
            text.textContent = 'Consultar API';
        }
    }

    function showError(msg) {
        document.getElementById('error-box').classList.remove('hidden');
        document.getElementById('error-msg').textContent = msg;
    }

    function hideError() {
        document.getElementById('error-box').classList.add('hidden');
    }

    function esc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    </script>

</x-app-layout>
