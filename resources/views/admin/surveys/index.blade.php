<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 dark:hover:text-gray-300 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">Encuestas de Satisfacción</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Encuestas de Satisfacción</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gestiona y crea tipos de encuesta para WhatsApp.</p>
                </div>
                {{-- Botón Generar Token (agrégalo junto al botón Nueva Encuesta) --}}
                <button onclick="openTokenModal()"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Token API
                </button>
                <button onclick="openSurveyModal()"
                        class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva Encuesta
                </button>
            </div>

            {{-- Grid de tipos de encuesta --}}
            @if($types->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="flex flex-col items-center gap-3 py-20 text-gray-300 dark:text-gray-600">
                        <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm text-gray-400 dark:text-gray-500">No hay encuestas creadas aún</p>
                        <button onclick="openSurveyModal()"
                                class="mt-1 text-sm text-violet-600 dark:text-violet-400 hover:underline">
                            Crear primera encuesta
                        </button>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($types as $type)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition flex flex-col">

                        {{-- Card header --}}
                        <div class="px-5 pt-5 pb-4 flex-1">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-violet-100 dark:bg-violet-900/40 rounded-lg flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-tight">
                                            {{ $type->nombre }}
                                        </h3>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                            {{ $type->surveys_count }} {{ $type->surveys_count === 1 ? 'respuesta' : 'respuestas' }}
                                        </p>
                                    </div>
                                </div>
                                <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $type->activo ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                                    {{ $type->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>

                            {{-- Campos --}}
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($type->campos as $campo)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        {{ $campo }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Card footer --}}
                        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                {{-- Ver respuestas --}}
                                <a href="{{ route('admin.surveys.show', $type->slug) }}"
                                   class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200 font-medium transition">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Ver respuestas
                                </a>

                                {{-- Ver snippet --}}
                                <button onclick="showSnippet({{ json_encode($type->snippet()) }})"
                                        class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium transition">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                    </svg>
                                    Ver snippet
                                </button>
                            </div>

                            {{-- Eliminar --}}
                            <form method="POST" action="{{ route('admin.survey-types.destroy', $type) }}"
                                  onsubmit="return confirm('¿Eliminar esta encuesta y todas sus respuestas?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 transition"
                                        title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>

    {{-- ── Modal: Nueva Encuesta ── --}}
    <div id="survey-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6">

            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/40 rounded-full flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Nueva encuesta</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Define el nombre y los campos que recibirá</p>
                </div>
            </div>

            {{-- Paso 1: Formulario --}}
            <div id="survey-form-step">
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        Nombre de la encuesta
                    </label>
                    <input id="survey-name" type="text"
                           placeholder="Ej: Encuesta post-venta"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-400">
                    <p id="survey-name-error" class="text-xs text-red-500 mt-1 hidden">El nombre es obligatorio.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                        Campos
                        <span class="text-gray-400 font-normal">(sin espacios, usa guion_bajo)</span>
                    </label>
                    <div id="campos-list" class="space-y-2"></div>
                    <button type="button" onclick="addCampo()"
                            class="mt-2 inline-flex items-center gap-1.5 text-xs text-violet-600 dark:text-violet-400 hover:text-violet-800 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar campo
                    </button>
                    <p id="campos-error" class="text-xs text-red-500 mt-1 hidden">Agrega al menos un campo.</p>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeSurveyModal()"
                            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="button" onclick="submitSurveyType()" id="survey-submit-btn"
                            class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-lg transition">
                        Generar encuesta
                    </button>
                </div>
            </div>

            {{-- Paso 2: Snippet --}}
            <div id="survey-snippet-step" class="hidden">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Encuesta creada correctamente</p>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Copia esto y pégalo en tu bot de WhatsApp:
                </p>
                <div class="relative bg-gray-900 rounded-lg px-4 py-3 mb-2">
                    <pre id="survey-snippet-value"
                         class="text-xs text-green-400 font-mono whitespace-pre overflow-x-auto pr-8"></pre>
                    <button type="button" onclick="copySurveySnippet()" id="copy-snippet-btn"
                            class="absolute top-2 right-2 p-1.5 bg-gray-700 hover:bg-gray-600 rounded text-gray-300 hover:text-white transition"
                            title="Copiar">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-5">
                    Reemplaza <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{TOKEN_AQUI}</code> con tu token de API.
                </p>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="openSurveyModal()"
                            class="px-4 py-2 text-sm text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-lg transition">
                        Crear otra
                    </button>
                    <button type="button" onclick="closeSurveyModal(); location.reload()"
                            class="px-4 py-2 bg-gray-800 dark:bg-gray-600 hover:bg-gray-900 text-white text-sm font-medium rounded-lg transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modal: Ver Snippet existente ── --}}
    <div id="snippet-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Snippet de integración</h3>
                <button onclick="closeSnippetModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="relative bg-gray-900 rounded-lg px-4 py-3 mb-3">
                <pre id="existing-snippet-value"
                     class="text-xs text-green-400 font-mono whitespace-pre overflow-x-auto pr-8"></pre>
                <button type="button" onclick="copyExistingSnippet()"
                        id="copy-existing-btn"
                        class="absolute top-2 right-2 p-1.5 bg-gray-700 hover:bg-gray-600 rounded text-gray-300 hover:text-white transition"
                        title="Copiar">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500">
                Reemplaza <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{TOKEN_AQUI}</code> con tu token de API.
            </p>
        </div>
    </div>

    <script>
        // ── Nueva encuesta ───────────────────────────────────────
        function openSurveyModal() {
            const modal = document.getElementById('survey-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('survey-form-step').classList.remove('hidden');
            document.getElementById('survey-snippet-step').classList.add('hidden');
            document.getElementById('survey-name').value = '';
            document.getElementById('survey-name-error').classList.add('hidden');
            document.getElementById('campos-error').classList.add('hidden');
            document.getElementById('survey-submit-btn').disabled = false;
            document.getElementById('survey-submit-btn').textContent = 'Generar encuesta';
            document.getElementById('campos-list').innerHTML = '';
            addCampo('satisfaccion');
            addCampo('recomendacion');
        }

        function closeSurveyModal() {
            const modal = document.getElementById('survey-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function addCampo(value = '') {
            const list = document.getElementById('campos-list');
            const div  = document.createElement('div');
            div.className = 'flex items-center gap-2';
            div.innerHTML = `
                <input type="text" value="${value}" placeholder="Ej: satisfaccion"
                       class="flex-1 px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg
                              bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200
                              placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-400 font-mono">
                <button type="button" onclick="this.closest('div').remove()"
                        class="p-1.5 text-gray-400 hover:text-red-500 transition" title="Quitar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>`;
            list.appendChild(div);
        }

        function submitSurveyType() {
            const name   = document.getElementById('survey-name').value.trim();
            const inputs = document.querySelectorAll('#campos-list input');
            const campos = Array.from(inputs)
                .map(i => i.value.trim().replace(/\s+/g, '_').toLowerCase())
                .filter(Boolean);

            let valid = true;
            if (!name) {
                document.getElementById('survey-name-error').classList.remove('hidden');
                valid = false;
            } else {
                document.getElementById('survey-name-error').classList.add('hidden');
            }
            if (!campos.length) {
                document.getElementById('campos-error').classList.remove('hidden');
                valid = false;
            } else {
                document.getElementById('campos-error').classList.add('hidden');
            }
            if (!valid) return;

            const btn = document.getElementById('survey-submit-btn');
            btn.disabled = true;
            btn.textContent = 'Generando...';

            fetch('{{ route("admin.survey-types.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ nombre: name, campos })
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('survey-snippet-value').textContent = data.snippet;
                document.getElementById('survey-form-step').classList.add('hidden');
                document.getElementById('survey-snippet-step').classList.remove('hidden');
            })
            .catch(() => {
                btn.disabled = false;
                btn.textContent = 'Generar encuesta';
                alert('Error al crear la encuesta. Inténtalo de nuevo.');
            });
        }

        function copySurveySnippet() {
            const text = document.getElementById('survey-snippet-value').textContent;
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('copy-snippet-btn');
                btn.classList.add('bg-green-700');
                setTimeout(() => btn.classList.remove('bg-green-700'), 1500);
            });
        }

        document.getElementById('survey-modal').addEventListener('click', function(e) {
            if (e.target === this) closeSurveyModal();
        });

        // ── Snippet existente ────────────────────────────────────
        function showSnippet(snippet) {
            document.getElementById('existing-snippet-value').textContent = snippet;
            const modal = document.getElementById('snippet-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeSnippetModal() {
            const modal = document.getElementById('snippet-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function copyExistingSnippet() {
            const text = document.getElementById('existing-snippet-value').textContent;
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('copy-existing-btn');
                btn.classList.add('bg-green-700');
                setTimeout(() => btn.classList.remove('bg-green-700'), 1500);
            });
        }

        document.getElementById('snippet-modal').addEventListener('click', function(e) {
            if (e.target === this) closeSnippetModal();
        });

        function generarToken() {
            if (!confirm('Esto invalidará el token anterior. ¿Continuar?')) return;

            fetch('{{ route("admin.surveys.token") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                prompt('Tu nuevo token de API (cópialo ahora):', data.token);
            })
            .catch(() => alert('Error al generar el token.'));
        }
    </script>

    {{-- ── Modal: Token API ── --}}
<div id="token-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Token de API</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Usado en el header Authorization del bot</p>
            </div>
        </div>

        {{-- Paso 1: Confirmar --}}
        <div id="token-confirm-step">
            <div id="token-warning" class="hidden mb-4 flex items-center gap-2 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg px-3 py-2">
                <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span id="token-warning-text" class="text-xs text-red-600 dark:text-red-300"></span>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                Se generará un nuevo token. El token anterior quedará
                <span class="font-semibold text-red-500">invalidado inmediatamente</span>.
            </p>

            <div class="flex justify-end gap-3">
                <button onclick="closeTokenModal()"
                        class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    Cancelar
                </button>
                <button id="token-generate-btn" onclick="generarToken()"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                    Generar token
                </button>
            </div>
        </div>

        {{-- Paso 2: Token generado --}}
        <div id="token-result-step" class="hidden">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-sm font-medium text-green-600 dark:text-green-400">Token generado correctamente</p>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                Copia este token y pégalo en tu bot como <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">Authorization: Bearer ...</code>
            </p>

            <div class="relative bg-gray-900 rounded-lg px-4 py-3 mb-2">
                <p id="token-value"
                   class="text-xs text-green-400 font-mono break-all pr-8 select-all"></p>
                <button onclick="copyToken()" id="copy-token-btn"
                        class="absolute top-2 right-2 p-1.5 bg-gray-700 hover:bg-gray-600 rounded text-gray-300 hover:text-white transition"
                        title="Copiar">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-5">
                No volverá a mostrarse completo. Guárdalo en un lugar seguro.
            </p>

            <div class="flex justify-end">
                <button onclick="closeTokenModal()"
                        class="px-4 py-2 bg-gray-800 dark:bg-gray-600 hover:bg-gray-900 text-white text-sm font-medium rounded-lg transition">
                    Cerrar
                </button>
            </div>
        </div>

    </div>
</div>

<script>
    // ── Token Modal ──────────────────────────────────────────────
    function openTokenModal() {
        const modal = document.getElementById('token-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Reset
        document.getElementById('token-confirm-step').classList.remove('hidden');
        document.getElementById('token-result-step').classList.add('hidden');
        document.getElementById('token-warning').classList.add('hidden');
        document.getElementById('token-generate-btn').disabled = false;
        document.getElementById('token-generate-btn').textContent = 'Generar token';
    }

    function closeTokenModal() {
        const modal = document.getElementById('token-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function generarToken() {
        const btn = document.getElementById('token-generate-btn');
        btn.disabled = true;
        btn.textContent = 'Generando...';

        fetch('{{ route("admin.surveys.token") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept':       'application/json',
            }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('token-value').textContent = data.token;
            document.getElementById('token-confirm-step').classList.add('hidden');
            document.getElementById('token-result-step').classList.remove('hidden');
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = 'Generar token';
            document.getElementById('token-warning-text').textContent = 'Error al generar el token. Inténtalo de nuevo.';
            document.getElementById('token-warning').classList.remove('hidden');
        });
    }

    function copyToken() {
        const text = document.getElementById('token-value').textContent;
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.getElementById('copy-token-btn');
            btn.classList.add('bg-green-700');
            setTimeout(() => btn.classList.remove('bg-green-700'), 1500);
        });
    }

    document.getElementById('token-modal').addEventListener('click', function(e) {
        if (e.target === this) closeTokenModal();
    });
</script>
</x-app-layout>