<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">API MSP</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Alertas --}}
            @if(session('success'))
                <div class="mb-4 flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">API MSP</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Consulta tickets desde MSP Manager por rango de fecha.
                    @if($credencialesOk)
                        <span class="inline-flex items-center gap-1 ml-2 text-green-600 dark:text-green-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            Credenciales configuradas
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 ml-2 text-red-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            Sin credenciales configuradas
                        </span>
                    @endif
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button id="btn-export" onclick="exportExcel()"
                            class="hidden inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Exportar Excel
                    </button>

                    <button onclick="openCredModal()"
                            class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Credenciales
                    </button>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-5">
                <div class="px-6 py-4 flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Fecha desde</label>
                        <input type="date" id="fecha_inicio" value="{{ $fechaInicio }}"
                               class="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Fecha hasta</label>
                        <input type="date" id="fecha_fin" value="{{ $fechaFin }}"
                               class="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400">
                    </div>
                    <button onclick="startQuery()" id="btn-filtrar"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Filtrar
                    </button>
                    <span id="result-count" class="text-sm text-gray-400 dark:text-gray-500 self-center hidden"></span>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div id="table-empty" class="flex flex-col items-center gap-3 py-16 text-gray-300 dark:text-gray-600">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500">
                        @if(!$credencialesOk)
                            Configura las credenciales para comenzar
                        @else
                            Selecciona un rango de fechas y presiona Filtrar
                        @endif
                    </p>
                </div>

                <div id="table-container" class="hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead id="table-head">
                                <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50"></tr>
                            </thead>
                            <tbody id="table-body" class="divide-y divide-gray-50 dark:divide-gray-700/60"></tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                        <p id="table-footer" class="text-xs text-gray-400 dark:text-gray-500"></p>
                    </div>
                </div>

                <div id="table-error" class="hidden flex-col items-center gap-3 py-16">
                    <svg class="w-10 h-10 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p id="table-error-msg" class="text-sm text-red-500 text-center"></p>
                    <button onclick="startQuery()" class="text-xs text-purple-600 hover:underline mt-1">Reintentar</button>
                </div>
            </div>

        </div>
    </div>

    {{-- Botón flotante AI Chat --}}
    <button id="btn-ai-chat" onclick="openAiChat()"
            style="display:none;"
            class="fixed bottom-6 right-6 z-40 w-14 h-14 bg-purple-600 hover:bg-purple-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 items-center justify-center group">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
        <span class="absolute right-16 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
            Preguntar al AI
        </span>
    </button>

    {{-- Modal: AI Chat --}}
    <div id="ai-chat-modal"
         class="fixed inset-0 z-50 hidden items-end justify-end p-6 pointer-events-none">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md flex flex-col pointer-events-auto"
             style="height: 520px;">

            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700 shrink-0 bg-purple-600 rounded-t-2xl">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Asistente MSP</h3>
                        <p id="chat-subtitle" class="text-[10px] text-purple-200">Pregunta sobre los tickets cargados</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="clearChat()" title="Limpiar chat"
                            class="text-purple-200 hover:text-white transition p-1 rounded">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    <button onclick="closeAiChat()" class="text-purple-200 hover:text-white transition p-1 rounded">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div id="chat-messages" class="flex-1 overflow-y-auto px-4 py-3 space-y-3">
                <div class="flex gap-2">
                    <div class="w-7 h-7 bg-purple-100 dark:bg-purple-900/40 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-tl-none px-3 py-2 max-w-[85%]">
                        <p class="text-xs text-gray-700 dark:text-gray-200">
                            ¡Hola! Tengo acceso a los tickets que consultaste. Puedo ayudarte con resúmenes, buscar tickets específicos, comparativas por cliente o tipo, y más. ¿Qué quieres saber?
                        </p>
                    </div>
                </div>

                <div id="chat-suggestions" class="flex flex-wrap gap-1.5 pl-9">
                    <button onclick="sendSuggestion('¿Cuántos tickets hay por cliente?')"
                            class="text-[10px] px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-full border border-purple-200 dark:border-purple-700 hover:bg-purple-100 transition">
                        Por cliente
                    </button>
                    <button onclick="sendSuggestion('¿Cuál es el WorkType más frecuente?')"
                            class="text-[10px] px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-full border border-purple-200 dark:border-purple-700 hover:bg-purple-100 transition">
                        WorkType frecuente
                    </button>
                    <button onclick="sendSuggestion('Dame un resumen general de los tickets')"
                            class="text-[10px] px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-full border border-purple-200 dark:border-purple-700 hover:bg-purple-100 transition">
                        Resumen general
                    </button>
                    <button onclick="sendSuggestion('¿Qué tipo de issues son los más comunes?')"
                            class="text-[10px] px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-full border border-purple-200 dark:border-purple-700 hover:bg-purple-100 transition">
                        Issues comunes
                    </button>
                </div>
            </div>

            <div class="px-3 py-3 border-t border-gray-100 dark:border-gray-700 shrink-0">
                <div class="flex items-end gap-2">
                    <textarea id="chat-input"
                              placeholder="Escribe tu pregunta..."
                              rows="1"
                              onkeydown="handleChatKey(event)"
                              oninput="autoResize(this)"
                              class="flex-1 text-sm border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400 resize-none max-h-24 leading-relaxed"></textarea>
                    <button id="chat-send-btn" onclick="sendChatMessage()"
                            class="w-9 h-9 bg-purple-600 hover:bg-purple-700 text-white rounded-xl flex items-center justify-center transition shrink-0 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
                <p class="text-[10px] text-gray-300 dark:text-gray-600 mt-1.5 text-center">Enter para enviar · Shift+Enter nueva línea</p>
            </div>
        </div>
    </div>

    {{-- Modal: Progreso SSE --}}
    <div id="loading-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8">
            <div class="flex justify-center mb-6">
                <div class="relative w-16 h-16">
                    <svg class="animate-spin w-16 h-16 text-purple-200 dark:text-purple-900/50" fill="none" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    </svg>
                    <svg class="animate-spin w-16 h-16 text-purple-600 absolute inset-0" style="animation-duration:0.8s" fill="none" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <h3 class="text-center text-base font-semibold text-gray-800 dark:text-gray-100 mb-1">Consultando tickets MSP</h3>
            <p id="loading-message" class="text-center text-xs text-gray-400 dark:text-gray-500 mb-6 min-h-[1rem]">Iniciando...</p>
            <div class="mb-5">
                <div class="flex items-center justify-between mb-1.5">
                    <span id="loading-step-label" class="text-xs font-medium text-gray-500 dark:text-gray-400">Paso 1 de 3</span>
                    <span id="loading-percent-label" class="text-xs font-bold text-purple-600 dark:text-purple-400">0%</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                    <div id="loading-bar" class="h-2.5 rounded-full bg-gradient-to-r from-purple-500 to-purple-700 transition-all duration-500 ease-out" style="width: 0%"></div>
                </div>
            </div>
            <div class="space-y-2">
                <div id="step-row-1" class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 transition-all duration-300">
                    <div id="step-icon-1" class="w-7 h-7 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center shrink-0 text-xs text-gray-400 font-semibold">1</div>
                    <div>
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Tickets</p>
                        <p class="text-[10px] text-gray-400">ticketsview filtrado por fecha</p>
                    </div>
                    <span id="step-badge-1" class="ml-auto text-[10px] text-gray-400 hidden"></span>
                </div>
                <div id="step-row-2" class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 opacity-40 transition-all duration-300">
                    <div id="step-icon-2" class="w-7 h-7 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center shrink-0 text-xs text-gray-400 font-semibold">2</div>
                    <div>
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Time entries & Custom fields</p>
                        <p id="step-2-sub" class="text-[10px] text-gray-400">En paralelo por lotes de 100</p>
                    </div>
                    <span id="step-badge-2" class="ml-auto text-[10px] text-purple-500 font-medium hidden"></span>
                </div>
                <div id="step-row-3" class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 opacity-40 transition-all duration-300">
                    <div id="step-icon-3" class="w-7 h-7 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center shrink-0 text-xs text-gray-400 font-semibold">3</div>
                    <div>
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Preparando resultados</p>
                        <p class="text-[10px] text-gray-400">Combinando y formateando datos</p>
                    </div>
                </div>
            </div>
            <p class="text-center text-[10px] text-gray-300 dark:text-gray-600 mt-5">El tiempo varía según el rango de fechas</p>
        </div>
    </div>

    {{-- Modal: Credenciales --}}
    <div id="cred-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-full flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Credenciales MSP</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Autenticación Basic para MSP Manager</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.api-msp.credentials') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Usuario (email)</label>
                    <input type="text" name="username" value="{{ config('services.msp.username') ?? '' }}" placeholder="usuario@empresa.com"
                           class="w-full text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••"
                           class="w-full text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Base URL de la API</label>
                    <input type="text" name="base_url" value="{{ config('services.msp.base_url') ?? 'https://api.mspmanager.com/odata' }}"
                           class="w-full text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeCredModal()"
                            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">Cancelar</button>
                    <button type="submit"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition">Guardar credenciales</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let ticketsData     = [];
    let currentDates    = { inicio: '', fin: '' };
    let activeSource    = null;
    let currentCacheKey = '';
    let queryFinished   = false;
    let chatHistory     = [];
    let isChatSending   = false;

    const FIXED_COLS = {
        TicketNumber:           'Ticket #',
        TicketTitle:            'Título',
        CustomerName:           'Cliente',
        LocationName:           'Ubicación',
        TicketIssueTypeName:    'Tipo',
        TicketSubIssueTypeName: 'Sub-tipo',
        WorkType:               'Work Type',
        CustomWorkType:         'Custom Work Type',
        CreatedDate:            'Creado',
        CompletedDate:          'Completado',
    };
    const FIXED_KEYS = [...Object.keys(FIXED_COLS), 'TicketId', 'DueDate'];

    function startQuery() {
        const inicio = document.getElementById('fecha_inicio').value;
        const fin    = document.getElementById('fecha_fin').value;
        if (!inicio || !fin) { alert('Selecciona ambas fechas.'); return; }
        currentDates  = { inicio, fin };
        queryFinished = false;
        if (activeSource) { activeSource.close(); activeSource = null; }
        resetTable();
        showLoadingModal();
        setProgress(5, 'Iniciando consulta...', 'Paso 1 de 3');
        const url = `{{ route('admin.api-msp.stream') }}?fecha_inicio=${inicio}&fecha_fin=${fin}`;
        activeSource = new EventSource(url);

        activeSource.addEventListener('status', e => {
            const d = JSON.parse(e.data);
            setProgress(d.percent, d.message, stepLabel(d.step));
            activateStep(d.step);
            if (d.tickets_found !== undefined) {
                document.getElementById('step-badge-1').textContent = `${d.tickets_found} tickets`;
                document.getElementById('step-badge-1').classList.remove('hidden');
            }
        });

        activeSource.addEventListener('progress', e => {
            const d = JSON.parse(e.data);
            setProgress(d.percent, d.message, stepLabel(d.step));
            document.getElementById('step-2-sub').textContent = `${d.done} / ${d.total} tickets procesados`;
            document.getElementById('step-badge-2').textContent = `${d.done}/${d.total}`;
            document.getElementById('step-badge-2').classList.remove('hidden');
        });

        activeSource.addEventListener('done', e => {
            queryFinished = true;
            activeSource.close();
            activeSource = null;
            const d = JSON.parse(e.data);
            currentCacheKey = d.cache_key || '';
            setProgress(100, d.message || 'Consulta completada', 'Completado');
            markAllStepsDone();
            if (d.total === 0) {
                setTimeout(() => { hideLoadingModal(); document.getElementById('table-empty').classList.remove('hidden'); }, 500);
                return;
            }
            fetch(`{{ route('admin.api-msp.results') }}?cache_key=${d.cache_key}`)
                .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
                .then(data => {
                    setTimeout(() => {
                        hideLoadingModal();
                        renderTable(data.tickets);
                        if (data.tickets && data.tickets.length > 0) {
                            const btnAi = document.getElementById('btn-ai-chat');
                            if (btnAi) { btnAi.classList.remove('hidden'); btnAi.classList.add('flex'); }
                            const subtitle = document.getElementById('chat-subtitle');
                            if (subtitle) subtitle.textContent = `${data.tickets.length} tickets cargados`;
                        }
                    }, 500);
                });
        });

        activeSource.addEventListener('error', e => {
            if (queryFinished) return;
            try {
                const d = JSON.parse(e.data);
                activeSource.close(); activeSource = null;
                hideLoadingModal();
                showTableError(d.message || 'Error en el servidor.');
            } catch (_) {}
        });

        activeSource.onerror = () => {
            if (queryFinished) { if (activeSource) activeSource.close(); return; }
            setTimeout(() => {
                if (!queryFinished) {
                    if (activeSource) activeSource.close();
                    activeSource = null;
                    hideLoadingModal();
                    showTableError('Se perdió la conexión con el servidor.');
                }
            }, 3000);
        };
    }

    function renderTable(tickets) {
        if (!tickets || tickets.length === 0) {
            document.getElementById('table-empty').classList.remove('hidden');
            document.getElementById('table-container').classList.add('hidden');
            return;
        }
        ticketsData = tickets;
        const allKeys     = [...new Set(tickets.flatMap(t => Object.keys(t)))];
        const dynamicCols = allKeys.filter(k => !FIXED_KEYS.includes(k));
        const headRow = document.querySelector('#table-head tr');
        headRow.innerHTML = '';
        Object.entries(FIXED_COLS).forEach(([key, label]) => {
            const th = document.createElement('th');
            th.className = 'px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap';
            th.textContent = label;
            headRow.appendChild(th);
        });
        dynamicCols.forEach(col => {
            const th = document.createElement('th');
            th.className = 'px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap';
            th.innerHTML = `${escHtml(col)} <span class="text-[9px] bg-orange-100 text-orange-500 px-1 rounded normal-case">CF</span>`;
            headRow.appendChild(th);
        });
        const tbody = document.getElementById('table-body');
        tbody.innerHTML = '';
        const cellMap = {
            TicketNumber:           v => `<td class="px-4 py-3 font-mono text-xs text-purple-600 dark:text-purple-400 whitespace-nowrap">${escHtml(v)||'—'}</td>`,
            TicketTitle:            v => `<td class="px-4 py-3 text-gray-800 dark:text-gray-200 max-w-xs truncate" title="${escHtml(v)||''}">${escHtml(v)||'—'}</td>`,
            CustomerName:           v => `<td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">${escHtml(v)||'—'}</td>`,
            LocationName:           v => `<td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">${escHtml(v)||'—'}</td>`,
            TicketIssueTypeName:    v => `<td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">${escHtml(v)||'—'}</td>`,
            TicketSubIssueTypeName: v => `<td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">${escHtml(v)||'—'}</td>`,
            WorkType:               v => `<td class="px-4 py-3 whitespace-nowrap">${v?`<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">${escHtml(v)}</span>`:'<span class="text-gray-300">—</span>'}</td>`,
            CustomWorkType:         v => `<td class="px-4 py-3 whitespace-nowrap">${v?`<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">${escHtml(v)}</span>`:'<span class="text-gray-300">—</span>'}</td>`,
            CreatedDate:            v => `<td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">${escHtml(v)||'—'}</td>`,
            CompletedDate:          v => `<td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">${escHtml(v)||'—'}</td>`,
        };
        tickets.forEach(ticket => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition';
            let html = '';
            Object.keys(FIXED_COLS).forEach(key => { html += cellMap[key](ticket[key]); });
            dynamicCols.forEach(col => {
                const val = ticket[col];
                html += `<td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap text-xs">${val !== undefined && val !== '' ? escHtml(String(val)) : '<span class="text-gray-300">—</span>'}</td>`;
            });
            tr.innerHTML = html;
            tbody.appendChild(tr);
        });
        document.getElementById('table-footer').textContent = `Total: ${tickets.length} registros — Período: ${currentDates.inicio} al ${currentDates.fin}`;
        document.getElementById('table-empty').classList.add('hidden');
        document.getElementById('table-container').classList.remove('hidden');
        document.getElementById('result-count').textContent = `${tickets.length} registros encontrados`;
        document.getElementById('result-count').classList.remove('hidden');
        document.getElementById('btn-export').classList.remove('hidden');
        document.getElementById('btn-ai-chat').style.display = 'flex';
        document.getElementById('chat-subtitle').textContent = `${tickets.length} tickets cargados`;
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function openAiChat() {
        const m = document.getElementById('ai-chat-modal');
        m.classList.remove('hidden'); m.classList.add('flex');
        document.getElementById('chat-input').focus();
    }

    function closeAiChat() {
        const m = document.getElementById('ai-chat-modal');
        m.classList.add('hidden'); m.classList.remove('flex');
    }

    function clearChat() {
        chatHistory = [];
        const container = document.getElementById('chat-messages');
        const children = Array.from(container.children);
        children.slice(2).forEach(el => el.remove());
        document.getElementById('chat-suggestions').classList.remove('hidden');
    }

    function sendSuggestion(text) {
        document.getElementById('chat-input').value = text;
        document.getElementById('chat-suggestions').classList.add('hidden');
        sendChatMessage();
    }

    function handleChatKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChatMessage(); }
    }

    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 96) + 'px';
    }

    async function sendChatMessage() {
        if (isChatSending) return;
        const input   = document.getElementById('chat-input');
        const message = input.value.trim();
        if (!message || !currentCacheKey) return;
        isChatSending = true;
        input.value   = '';
        input.style.height = 'auto';
        document.getElementById('chat-suggestions').classList.add('hidden');
        appendMessage('user', message);
        const typingId = appendTyping();
        try {
            const res = await fetch('{{ route('admin.api-msp.chat') }}', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ message, cache_key: currentCacheKey }),
            });
            const data = await res.json();
            removeTyping(typingId);
            appendMessage('ai', data.reply || 'Sin respuesta.');
        } catch (err) {
            removeTyping(typingId);
            appendMessage('ai', 'Error al conectar con el asistente.');
        } finally {
            isChatSending = false;
        }
    }

    function appendMessage(role, text) {
        const container = document.getElementById('chat-messages');
        const div = document.createElement('div');
        if (role === 'user') {
            div.className = 'flex justify-end';
            div.innerHTML = `<div class="bg-purple-600 text-white rounded-2xl rounded-tr-none px-3 py-2 max-w-[85%]"><p class="text-xs whitespace-pre-wrap">${escHtml(text)}</p></div>`;
        } else {
            div.className = 'flex gap-2';
            div.innerHTML = `
                <div class="w-7 h-7 bg-purple-100 dark:bg-purple-900/40 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-tl-none px-3 py-2 max-w-[85%]">
                    <p class="text-xs text-gray-700 dark:text-gray-200 whitespace-pre-wrap">${escHtml(text)}</p>
                </div>`;
        }
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function appendTyping() {
        const container = document.getElementById('chat-messages');
        const id  = 'typing-' + Date.now();
        const div = document.createElement('div');
        div.id = id; div.className = 'flex gap-2';
        div.innerHTML = `
            <div class="w-7 h-7 bg-purple-100 dark:bg-purple-900/40 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            </div>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-tl-none px-3 py-2">
                <div class="flex gap-1 items-center h-4">
                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                </div>
            </div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
        return id;
    }

    function removeTyping(id) { const el = document.getElementById(id); if (el) el.remove(); }

    function setProgress(percent, message, label) {
        document.getElementById('loading-bar').style.width           = percent + '%';
        document.getElementById('loading-percent-label').textContent = percent + '%';
        document.getElementById('loading-message').textContent       = message;
        document.getElementById('loading-step-label').textContent    = label;
    }

    function stepLabel(step) { return step ? `Paso ${step} de 3` : 'Iniciando'; }

    function activateStep(step) {
        for (let i = 1; i <= 3; i++) {
            const row  = document.getElementById(`step-row-${i}`);
            const icon = document.getElementById(`step-icon-${i}`);
            if (i < step) {
                row.classList.remove('opacity-40');
                icon.className = 'w-7 h-7 rounded-full flex items-center justify-center shrink-0 bg-green-500 border-2 border-green-500';
                icon.innerHTML = '<svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
            } else if (i === step) {
                row.classList.remove('opacity-40');
                icon.className = 'w-7 h-7 rounded-full flex items-center justify-center shrink-0 bg-purple-50 border-2 border-purple-500';
                icon.innerHTML = '<svg class="animate-spin w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24"><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>';
            } else {
                row.classList.add('opacity-40');
            }
        }
    }

    function markAllStepsDone() {
        for (let i = 1; i <= 3; i++) {
            const row  = document.getElementById(`step-row-${i}`);
            const icon = document.getElementById(`step-icon-${i}`);
            row.classList.remove('opacity-40');
            icon.className = 'w-7 h-7 rounded-full flex items-center justify-center shrink-0 bg-green-500 border-2 border-green-500';
            icon.innerHTML = '<svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
        }
    }

    function showLoadingModal() {
        const m = document.getElementById('loading-modal');
        m.classList.remove('hidden'); m.classList.add('flex');
        for (let i = 1; i <= 3; i++) {
            document.getElementById(`step-row-${i}`).classList.add('opacity-40');
            const icon = document.getElementById(`step-icon-${i}`);
            icon.className = 'w-7 h-7 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center shrink-0 text-xs text-gray-400 font-semibold';
            icon.textContent = i;
        }
        document.getElementById('step-badge-1').classList.add('hidden');
        document.getElementById('step-badge-2').classList.add('hidden');
        document.getElementById('step-2-sub').textContent = 'En paralelo por lotes de 100';
    }

    function hideLoadingModal() {
        const m = document.getElementById('loading-modal');
        m.classList.add('hidden'); m.classList.remove('flex');
    }

    function resetTable() {
        document.getElementById('table-empty').classList.remove('hidden');
        document.getElementById('table-container').classList.add('hidden');
        document.getElementById('table-error').classList.add('hidden');
        document.getElementById('result-count').classList.add('hidden');
        document.getElementById('btn-export').classList.add('hidden');
        document.getElementById('btn-ai-chat').style.display = 'none';
        closeAiChat();
    }

    function showTableError(msg) {
        document.getElementById('table-empty').classList.add('hidden');
        document.getElementById('table-container').classList.add('hidden');
        const errDiv = document.getElementById('table-error');
        errDiv.classList.remove('hidden'); errDiv.classList.add('flex');
        document.getElementById('table-error-msg').textContent = msg;
    }

    function exportExcel() {
        const url = `{{ route('admin.api-msp.export') }}?cache_key=${currentCacheKey}&fecha_inicio=${currentDates.inicio}&fecha_fin=${currentDates.fin}`;
        window.location.href = url;
    }

    function openCredModal() {
        const m = document.getElementById('cred-modal');
        m.classList.remove('hidden'); m.classList.add('flex');
    }

    function closeCredModal() {
        const m = document.getElementById('cred-modal');
        m.classList.add('hidden'); m.classList.remove('flex');
    }

    document.getElementById('cred-modal').addEventListener('click', function(e) {
        if (e.target === this) closeCredModal();
    });
    </script>

</x-app-layout>