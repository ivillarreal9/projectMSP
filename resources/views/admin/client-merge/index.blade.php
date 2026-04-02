<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Merge Clientes</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Alertas --}}
            @if(session('error'))
                <div class="mb-4 flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header --}}
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Merge Clientes MSP + ODOO</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Sube los dos archivos Excel y descarga el resultado combinado por similitud de nombre.
                </p>
            </div>

            {{-- Formulario --}}
            <form method="POST" action="{{ route('admin.client-merge.process') }}"
                  enctype="multipart/form-data"
                  onsubmit="showLoading()">
                @csrf

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-5">

                    {{-- Archivo MSP --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Archivo MSP
                            <span class="text-xs text-gray-400 font-normal ml-1">(columnas: Customer Name, Customer ID)</span>
                        </label>
                        <div class="relative">
                            <input type="file" name="msp_file" accept=".xlsx,.xls" required
                                   onchange="updateLabel(this, 'msp-label')"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="flex items-center gap-3 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg px-4 py-3 hover:border-purple-400 transition">
                                <svg class="w-8 h-8 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div>
                                    <p id="msp-label" class="text-sm text-gray-500 dark:text-gray-400">Haz clic o arrastra el archivo MSP (.xlsx)</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">MSP_CLIENTS.xlsx</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Archivo ODOO --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Archivo ODOO
                            <span class="text-xs text-gray-400 font-normal ml-1">(columnas: Nombre, Número de cuenta, RUC)</span>
                        </label>
                        <div class="relative">
                            <input type="file" name="odoo_file" accept=".xlsx,.xls" required
                                   onchange="updateLabel(this, 'odoo-label')"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="flex items-center gap-3 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg px-4 py-3 hover:border-purple-400 transition">
                                <svg class="w-8 h-8 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div>
                                    <p id="odoo-label" class="text-sm text-gray-500 dark:text-gray-400">Haz clic o arrastra el archivo ODOO (.xlsx)</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">ODOO_CLIENTS.xlsx</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Umbral --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Umbral de similitud
                            <span id="threshold-val" class="text-purple-600 dark:text-purple-400 font-bold ml-1">80%</span>
                        </label>
                        <input type="range" name="threshold" min="50" max="100" value="80" step="5"
                               oninput="document.getElementById('threshold-val').textContent = this.value + '%'"
                               class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-purple-600">
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>50% (más permisivo)</span>
                            <span>100% (exacto)</span>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800 rounded-lg p-3">
                        <p class="text-xs text-purple-700 dark:text-purple-300">
                            <strong>Resultado:</strong> Un Excel con CustomerID, CustomerName (MSP), Número de Cuenta y RUC (ODOO).
                            Si un cliente MSP tiene varios matches en ODOO, las cuentas y RUCs se separan con <code class="bg-purple-100 dark:bg-purple-800 px-1 rounded">|</code>.
                            Los clientes sin match se incluyen con campos vacíos.
                        </p>
                    </div>

                    {{-- Botón --}}
                    <button type="submit" id="btn-submit"
                            class="w-full inline-flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white font-medium py-3 rounded-lg transition text-sm">
                        <svg id="submit-icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span id="submit-text">Procesar y Descargar Excel</span>
                    </button>
                </div>
            </form>

            {{-- Nota provisional --}}
            <p class="text-center text-xs text-gray-300 dark:text-gray-600 mt-4">Vista provisional · El proceso puede tomar 20-30 segundos</p>

        </div>
    </div>

    <script>
    function updateLabel(input, labelId) {
        const label = document.getElementById(labelId);
        if (input.files && input.files[0]) {
            label.textContent = input.files[0].name;
            label.classList.add('text-gray-800', 'dark:text-gray-100', 'font-medium');
        }
    }

    function showLoading() {
        const btn  = document.getElementById('btn-submit');
        const icon = document.getElementById('submit-icon');
        const text = document.getElementById('submit-text');

        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
        icon.classList.add('animate-spin');
        icon.innerHTML = '<path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>';
        text.textContent = 'Procesando... puede tomar 30 segundos';
    }
    </script>

</x-app-layout>
