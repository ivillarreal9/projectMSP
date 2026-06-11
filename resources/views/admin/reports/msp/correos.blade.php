{{-- resources/views/admin/reports/msp/correos.blade.php --}}
@extends('admin.reports.msp.layouts.app')
@section('title', 'Envío de Correos')

@php $vars = ['[[cliente]]','[[periodo]]','[[incidentes]]','[[solicitudes]]','[[t_inc]]','[[t_sol]]','[[cuenta]]']; @endphp

@section('content')
<style>
/* ── Correos: tab styles ── */
.correo-tab-active-orange {
    border-color: var(--ovni-orange) !important;
    background: rgba(232,97,10,.07) !important;
}
.dark .correo-tab-active-orange {
    background: rgba(232,97,10,.12) !important;
}
.correo-tab-active-blue {
    border-color: #2563eb !important;
    background: rgba(37,99,235,.07) !important;
}
.dark .correo-tab-active-blue {
    background: rgba(37,99,235,.12) !important;
}
/* ── Form field base ── */
.correo-input {
    width:100%;
    border:1px solid #d1d5db;
    border-radius:.75rem;
    padding:.6rem 1rem;
    font-size:.85rem;
    color:#111827;
    background:#fff;
    transition: border-color .15s, box-shadow .15s;
    outline:none;
}
.correo-input:focus { border-color:var(--ovni-orange); box-shadow:0 0 0 3px rgba(232,97,10,.12); }
.dark .correo-input { background:#374151; border-color:#4b5563; color:#f9fafb; }
.dark .correo-input:focus { border-color:var(--ovni-orange); box-shadow:0 0 0 3px rgba(232,97,10,.18); }
.correo-input-icon { padding-left:2.4rem; }
.correo-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .75rem center; background-size:1rem; padding-right:2.5rem; }
.correo-label { display:block; font-size:.72rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.35rem; }
.dark .correo-label { color:#9ca3af; }
/* ── Client list row ── */
.cliente-row { display:flex; align-items:center; gap:.75rem; padding:.65rem 1rem; border-bottom:1px solid #f3f4f6; transition:background .12s; cursor:pointer; }
.dark .cliente-row { border-bottom-color:rgba(55,65,81,.6); }
.cliente-row:hover { background:#fff7f0; }
.dark .cliente-row:hover { background:#374151; }
.cliente-row.selected { background:rgba(232,97,10,.08); }
.dark .cliente-row.selected { background:rgba(232,97,10,.13); }
.cliente-row-masivo { display:flex; align-items:center; gap:.75rem; padding:.65rem 1rem; border-bottom:1px solid #f3f4f6; transition:background .12s; }
.dark .cliente-row-masivo { border-bottom-color:rgba(55,65,81,.6); }
.cliente-row-masivo:hover { background:#eff6ff; }
.dark .cliente-row-masivo:hover { background:#374151; }
</style>

<div class="space-y-5 fade-in">

    @if(session('success'))
    <div class="flex items-center gap-3 p-3.5 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-xl text-sm">
        <i class="fa-solid fa-circle-check flex-shrink-0"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="flex items-center gap-3 p-3.5 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-xl text-sm">
        <i class="fa-solid fa-circle-xmark flex-shrink-0"></i> {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="p-3.5 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-xl text-sm">
        <div class="font-semibold mb-1.5"><i class="fa-solid fa-circle-exclamation mr-1"></i>Errores de validación:</div>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- ── Top bar: período + contador ── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 py-3.5 flex flex-wrap items-center gap-4">
        <div>
            <span class="correo-label" style="margin-bottom:.5rem">Período</span>
            <div class="flex gap-1.5 flex-wrap">
                @foreach($periodos as $p)
                <a href="{{ route('admin.msp.correos', ['periodo' => $p]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition
                          {{ $periodo === $p
                               ? 'text-white border-transparent'
                               : 'text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                   style="{{ $periodo === $p ? 'background:var(--ovni-orange)' : '' }}">
                    {{ \App\Models\MspReport::translatePeriodo($p) }}
                </a>
                @endforeach
            </div>
        </div>
        <div class="ml-auto flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                <i class="fa-solid fa-users text-xs text-gray-400"></i>
            </span>
            <span><strong class="text-gray-700 dark:text-gray-200">{{ $clientes->count() }}</strong> clientes</span>
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <div class="flex gap-2">
        <button onclick="switchTab('individual')" id="tab-individual"
                class="tab-btn flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 transition text-left correo-tab-active-orange">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs flex-shrink-0" style="background:var(--ovni-orange)">
                <i class="fa-solid fa-envelope"></i>
            </span>
            <div>
                <div class="text-sm font-semibold text-gray-800 dark:text-white">Individual</div>
                <div class="text-xs text-gray-400 dark:text-gray-500 hidden sm:block">Un cliente a la vez</div>
            </div>
        </button>
        <button onclick="switchTab('masivo')" id="tab-masivo"
                class="tab-btn flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 transition text-left">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs bg-blue-600 flex-shrink-0">
                <i class="fa-solid fa-paper-plane"></i>
            </span>
            <div>
                <div class="text-sm font-semibold text-gray-800 dark:text-white">Masivo</div>
                <div class="text-xs text-gray-400 dark:text-gray-500 hidden sm:block">Múltiples clientes</div>
            </div>
        </button>
    </div>

    {{-- ══ PANEL INDIVIDUAL ══ --}}
    <div id="panel-individual" class="panel">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

            {{-- Header --}}
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2.5 bg-gray-50 dark:bg-gray-800/80">
                <span class="w-6 h-6 rounded-md flex items-center justify-center text-white text-xs flex-shrink-0" style="background:var(--ovni-orange)">
                    <i class="fa-solid fa-envelope" style="font-size:.65rem"></i>
                </span>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Envío Individual</h3>
                <span class="ml-auto text-xs text-gray-400">Selecciona un cliente de la lista</span>
            </div>

            <div class="grid grid-cols-5 divide-x divide-gray-100 dark:divide-gray-700" style="min-height:520px">

                {{-- Left: client list --}}
                <div class="col-span-2 flex flex-col">
                    <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 dark:text-gray-500" style="font-size:.7rem"></i>
                            <input type="text" id="searchIndividual"
                                   placeholder="Buscar cliente…" autocomplete="off"
                                   oninput="filterIndividual(this.value)"
                                   class="correo-input correo-input-icon" style="padding:.5rem .75rem .5rem 2.2rem; font-size:.8rem">
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-1" id="listaIndividual" style="max-height:460px">
                        @foreach($clientes as $cliente)
                        <div class="cliente-item-individual cliente-row"
                             data-name="{{ strtolower($cliente->customer_name) }}"
                             onclick="elegirCliente('{{ addslashes($cliente->customer_name) }}', '{{ $cliente->email_cliente }}', '{{ $cliente->numero_cuenta }}')">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                 style="background:linear-gradient(135deg,var(--ovni-orange),#f97316)">
                                {{ strtoupper(substr($cliente->customer_name, 0, 2)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-gray-800 dark:text-gray-100 truncate">{{ $cliente->customer_name }}</div>
                                <div class="text-xs {{ $cliente->email_cliente ? 'text-gray-400' : 'text-red-400' }} truncate">
                                    {{ $cliente->email_cliente ?? 'Sin email' }}
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-gray-300 dark:text-gray-600 flex-shrink-0" style="font-size:.6rem"></i>
                        </div>
                        @endforeach
                        <div id="no-results-individual" class="hidden px-4 py-8 text-xs text-gray-400 text-center">
                            <i class="fa-solid fa-magnifying-glass mb-2 block text-base opacity-40"></i>
                            No se encontraron clientes
                        </div>
                    </div>
                </div>

                {{-- Right: form --}}
                <div class="col-span-3 flex flex-col">
                    {{-- Empty state --}}
                    <div id="individual-empty" class="flex-1 flex flex-col items-center justify-center gap-3 p-8 text-center">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center bg-orange-50 dark:bg-orange-900/20">
                            <i class="fa-solid fa-arrow-pointer text-xl" style="color:var(--ovni-orange)"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">Selecciona un cliente</p>
                            <p class="text-xs text-gray-400 mt-0.5">para ver el formulario de envío</p>
                        </div>
                    </div>

                    {{-- Form --}}
                    <div id="individual-form" class="hidden flex-1 flex flex-col">
                        {{-- Selected client header --}}
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3 bg-orange-50/60 dark:bg-orange-900/10">
                            <div id="ind-avatar" class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                                 style="background:linear-gradient(135deg,var(--ovni-orange),#f97316)">--</div>
                            <div class="flex-1 min-w-0">
                                <div id="ind-nombre" class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate"></div>
                                <div id="ind-email" class="text-xs text-gray-400 truncate"></div>
                            </div>
                            <button type="button" onclick="limpiarIndividual()"
                                    class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1.5 rounded-lg hover:bg-white dark:hover:bg-gray-700">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="p-5 flex-1 overflow-y-auto">
                            <form action="{{ route('admin.msp.correos.enviar') }}" method="POST" id="formIndividual">
                                @csrf
                                <input type="hidden" name="periodo" value="{{ $periodo }}">
                                <input type="hidden" name="customer_name" id="input_customer_name">
                                <input type="hidden" name="plantilla_id" id="input_plantilla_id_individual" value="">

                                <div class="space-y-4">
                                    {{-- Email + Asunto side by side --}}
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="correo-label">Email destino</label>
                                            <div class="relative">
                                                <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 dark:text-gray-500" style="font-size:.7rem"></i>
                                                <input type="email" name="email" id="input_email"
                                                       placeholder="cliente@empresa.com" required
                                                       class="correo-input correo-input-icon">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="correo-label">Asunto</label>
                                            <input type="text" name="subject" id="input_subject"
                                                   value="Informe MSP — {{ $periodo }}" required
                                                   class="correo-input">
                                        </div>
                                    </div>

                                    {{-- Plantilla --}}
                                    <div>
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="correo-label" style="margin:0">Plantilla</label>
                                            <button type="button" onclick="openModalPlantillas()"
                                                    class="text-xs font-medium flex items-center gap-1 transition hover:opacity-80"
                                                    style="color:var(--ovni-orange)">
                                                <i class="fa-solid fa-sliders"></i> Gestionar
                                            </button>
                                        </div>
                                        <select id="select_plantilla_individual"
                                                onchange="aplicarPlantillaSeleccionada(this.value, 'individual')"
                                                class="correo-input correo-select">
                                            <option value="">— Sin plantilla —</option>
                                            <optgroup label="Predeterminadas">
                                                <option value="__formal">Formal</option>
                                                <option value="__cordial">Cordial</option>
                                                <option value="__breve">Breve</option>
                                            </optgroup>
                                            <optgroup label="Mis plantillas" id="optgroup_custom_individual"></optgroup>
                                        </select>
                                    </div>

                                    {{-- Variables --}}
                                    <div>
                                        <label class="correo-label">Insertar variable</label>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($vars as $var)
                                            <button type="button" onclick="insertarVariable('mensaje_individual', '{{ $var }}')"
                                                    class="text-xs px-2 py-1 rounded-md border font-mono transition hover:opacity-80"
                                                    style="background:rgba(232,97,10,.07);color:var(--ovni-orange);border-color:rgba(232,97,10,.25)">
                                                {{ $var }}
                                            </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Mensaje --}}
                                    <div>
                                        <label class="correo-label">Mensaje <span class="normal-case font-normal text-gray-400">(opcional)</span></label>
                                        <textarea name="mensaje" id="mensaje_individual" rows="5"
                                                  placeholder="Estimado cliente…"
                                                  class="correo-input resize-none"></textarea>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex gap-2 pt-1">
                                        <button type="submit" id="btnSubmitIndividual"
                                                class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition"
                                                style="background:var(--ovni-orange)">
                                            <i class="fa-solid fa-paper-plane text-xs"></i>
                                            <span id="btnSubmitIndividualText">Enviar con PDF</span>
                                        </button>
                                        <a href="#" id="btn-ver-pdf-individual" target="_blank"
                                           class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                            <i class="fa-solid fa-eye text-xs"></i> Ver PDF
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ PANEL MASIVO ══ --}}
    <div id="panel-masivo" class="panel hidden">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

            {{-- Header --}}
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2.5 bg-gray-50 dark:bg-gray-800/80">
                <span class="w-6 h-6 rounded-md bg-blue-600 flex items-center justify-center text-white text-xs flex-shrink-0">
                    <i class="fa-solid fa-paper-plane" style="font-size:.65rem"></i>
                </span>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Envío Masivo</h3>
                <span id="badge-masivo" class="text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded-full font-medium">0 seleccionados</span>
            </div>

            <form action="{{ route('admin.msp.correos.masivo') }}" method="POST" id="formMasivo">
                @csrf
                <input type="hidden" name="periodo" value="{{ $periodo }}">
                <input type="hidden" name="plantilla_id" id="input_plantilla_id_masivo" value="">

                <div class="grid grid-cols-5 divide-x divide-gray-100 dark:divide-gray-700" style="min-height:520px">

                    {{-- Left: checkbox list --}}
                    <div class="col-span-2 flex flex-col">
                        <div class="p-3 border-b border-gray-100 dark:border-gray-700 space-y-2">
                            <div class="relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 dark:text-gray-500" style="font-size:.7rem"></i>
                                <input type="text" placeholder="Buscar cliente…"
                                       oninput="filterMasivo(this.value)"
                                       class="correo-input correo-input-icon" style="padding:.5rem .75rem .5rem 2.2rem; font-size:.8rem">
                            </div>
                            <label class="flex items-center gap-2 px-1 cursor-pointer select-none">
                                <input type="checkbox" id="checkTodos" onchange="toggleTodosMasivo(this)"
                                       class="accent-blue-600 w-3.5 h-3.5 rounded">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Todos los que tienen email</span>
                            </label>
                        </div>

                        <div class="overflow-y-auto flex-1" id="listaMasivo" style="max-height:440px">
                            @foreach($clientes as $cliente)
                            <label class="cliente-item-masivo cliente-row-masivo {{ $cliente->email_cliente ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' }}"
                                   data-name="{{ strtolower($cliente->customer_name) }}"
                                   data-has-email="{{ $cliente->email_cliente ? '1' : '0' }}">

                                @if($cliente->email_cliente)
                                    <input type="checkbox"
                                           data-customer="{{ $cliente->customer_name }}"
                                           data-email="{{ $cliente->email_cliente }}"
                                           onchange="updateMasivoCount()"
                                           class="cliente-check-masivo accent-blue-600 w-4 h-4 flex-shrink-0 rounded">
                                @else
                                    <input type="checkbox" disabled
                                           class="cliente-check-masivo accent-blue-600 w-4 h-4 flex-shrink-0 rounded">
                                @endif

                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0
                                            {{ $cliente->email_cliente ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    {{ strtoupper(substr($cliente->customer_name, 0, 2)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium {{ $cliente->email_cliente ? 'text-gray-800 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500' }} truncate">
                                        {{ $cliente->customer_name }}
                                    </div>
                                    <div class="text-xs {{ $cliente->email_cliente ? 'text-gray-400' : 'text-red-400' }} truncate">
                                        {{ $cliente->email_cliente ?? 'Sin email' }}
                                    </div>
                                </div>
                            </label>
                            @endforeach
                            <div id="no-results-masivo" class="hidden px-4 py-8 text-xs text-gray-400 text-center">
                                <i class="fa-solid fa-magnifying-glass mb-2 block text-base opacity-40"></i>
                                No se encontraron clientes
                            </div>
                        </div>

                        <div class="p-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                            <span class="text-xs text-gray-500 dark:text-gray-400" id="count-masivo-bottom">0 clientes seleccionados</span>
                        </div>
                    </div>

                    {{-- Right: masivo form --}}
                    <div class="col-span-3 p-5 flex flex-col gap-4 overflow-y-auto">

                        <div class="flex items-start gap-3 p-3.5 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800" id="masivo-info-empty">
                            <i class="fa-solid fa-circle-info text-blue-500 flex-shrink-0 mt-0.5"></i>
                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                Selecciona clientes de la lista. Se generará un PDF individualizado por cada cliente seleccionado.
                            </p>
                        </div>

                        {{-- Asunto --}}
                        <div>
                            <label class="correo-label">Asunto</label>
                            <input type="text" name="subject" id="input_subject_masivo"
                                   value="Informe MSP — {{ $periodo }}" required
                                   class="correo-input">
                        </div>

                        {{-- Plantilla --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="correo-label" style="margin:0">Plantilla</label>
                                <button type="button" onclick="openModalPlantillas()"
                                        class="text-xs font-medium text-blue-600 flex items-center gap-1 hover:opacity-80 transition">
                                    <i class="fa-solid fa-sliders"></i> Gestionar
                                </button>
                            </div>
                            <select id="select_plantilla_masivo" onchange="aplicarPlantillaSeleccionada(this.value, 'masivo')"
                                    class="correo-input correo-select">
                                <option value="">— Sin plantilla —</option>
                                <optgroup label="Predeterminadas">
                                    <option value="__formal">Formal</option>
                                    <option value="__cordial">Cordial</option>
                                    <option value="__breve">Breve</option>
                                </optgroup>
                                <optgroup label="Mis plantillas" id="optgroup_custom_masivo"></optgroup>
                            </select>
                        </div>

                        {{-- Variables --}}
                        <div>
                            <label class="correo-label">Insertar variable</label>
                            <div class="flex flex-wrap gap-1">
                                @foreach($vars as $var)
                                <button type="button" onclick="insertarVariable('mensaje_masivo', '{{ $var }}')"
                                        class="text-xs px-2 py-1 rounded-md border font-mono transition hover:opacity-80"
                                        style="background:rgba(37,99,235,.07);color:#2563eb;border-color:rgba(37,99,235,.2)">
                                    {{ $var }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Mensaje --}}
                        <div class="flex-1">
                            <label class="correo-label">Mensaje</label>
                            <textarea name="mensaje" id="mensaje_masivo" rows="7"
                                      placeholder="Estimado cliente, adjunto su informe MSP…"
                                      class="correo-input resize-none"></textarea>
                            <p class="text-xs text-gray-400 mt-1.5">
                                Las variables <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">[[cliente]]</span>,
                                <span class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded">[[periodo]]</span>, etc.
                                se reemplazan individualmente por cada cliente.
                            </p>
                        </div>

                        <div id="clientes-hidden-container"></div>

                        <button type="submit" id="btnSubmitMasivo" disabled
                                class="flex items-center justify-center gap-2 py-2.5 rounded-xl text-white text-sm font-semibold bg-blue-600 hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-paper-plane text-xs" id="btnSubmitMasivoIcon"></i>
                            <span id="btnSubmitMasivoText">Selecciona al menos un cliente</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ MODAL GESTIONAR PLANTILLAS ══ --}}
<div id="modal-plantillas" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs flex-shrink-0" style="background:var(--ovni-orange)">
                    <i class="fa-solid fa-palette"></i>
                </span>
                <h3 class="font-bold text-gray-800 dark:text-white">Gestionar Plantillas</h3>
            </div>
            <button onclick="closeModalPlantillas()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="flex flex-1 overflow-hidden divide-x divide-gray-200 dark:divide-gray-700">
            {{-- Sidebar --}}
            <div class="w-60 flex flex-col flex-shrink-0">
                <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                    <button type="button" onclick="nuevaPlantilla()"
                            class="w-full flex items-center justify-center gap-2 py-2 rounded-lg text-white text-xs font-semibold hover:opacity-90 transition"
                            style="background:var(--ovni-orange)">
                        <i class="fa-solid fa-plus"></i> Nueva plantilla
                    </button>
                </div>
                <div class="overflow-y-auto flex-1 text-xs">
                    <div class="px-3 py-2 font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">Predeterminadas</div>
                    @foreach(['formal' => 'Formal', 'cordial' => 'Cordial', 'breve' => 'Breve'] as $key => $label)
                    <div class="flex items-center gap-2 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer border-b border-gray-100 dark:border-gray-700/50 transition"
                         onclick="verPlantillaPredeterminada('{{ $key }}')">
                        <div class="w-6 h-6 rounded-md bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-file-lines text-gray-400" style="font-size:.65rem"></i>
                        </div>
                        <span class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        <span class="ml-auto bg-gray-100 dark:bg-gray-700 text-gray-400 px-1.5 py-0.5 rounded text-xs">Base</span>
                    </div>
                    @endforeach
                    <div class="px-3 py-2 font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 mt-1">Mis plantillas</div>
                    <div id="lista-custom-plantillas">
                        <div class="px-3 py-6 text-gray-400 text-center" id="no-custom-plantillas">
                            <i class="fa-solid fa-inbox mb-2 block text-xl opacity-30"></i>
                            Sin plantillas guardadas
                        </div>
                    </div>
                </div>
            </div>

            {{-- Editor --}}
            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="flex-1 overflow-y-auto p-5">
                    <div id="editor-empty" class="h-full flex flex-col items-center justify-center text-center text-gray-400 gap-2">
                        <i class="fa-solid fa-arrow-left text-3xl opacity-30"></i>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Selecciona una plantilla</p>
                        <p class="text-xs">o crea una nueva</p>
                    </div>

                    <div id="editor-form" class="hidden space-y-4">
                        <input type="hidden" id="plantilla_id" value="">
                        <input type="hidden" id="plantilla_es_predeterminada" value="0">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="correo-label">Nombre</label>
                                <input type="text" id="plantilla_nombre" placeholder="Ej: Plantilla corporativa"
                                       class="correo-input">
                            </div>
                            <div>
                                <label class="correo-label">Asunto <span class="normal-case font-normal text-gray-400">(opcional)</span></label>
                                <input type="text" id="plantilla_asunto" placeholder="Informe MSP — [[periodo]]"
                                       class="correo-input">
                            </div>
                        </div>

                        <div id="campo-imagen">
                            <label class="correo-label">Imagen / Banner <span class="normal-case font-normal text-gray-400">(opcional)</span></label>
                            <div id="imagen-preview-wrap" class="hidden mb-2">
                                <img id="imagen-preview-img" src="" alt="Preview" class="max-h-24 rounded-lg border border-gray-200 dark:border-gray-600 object-contain">
                                <button type="button" onclick="quitarImagen()" class="mt-1 text-xs text-red-500 hover:underline block">
                                    <i class="fa-solid fa-trash mr-1"></i> Quitar imagen
                                </button>
                            </div>
                            <label class="flex items-center gap-2 px-4 py-3 border-2 border-dashed border-gray-200 dark:border-gray-600
                                          rounded-xl cursor-pointer hover:border-orange-400 transition text-sm text-gray-400 dark:text-gray-500">
                                <i class="fa-solid fa-image" style="color:var(--ovni-orange)"></i>
                                <span id="imagen-label">Subir imagen (JPG, PNG, WEBP — máx. 2MB)</span>
                                <input type="file" id="plantilla_imagen" accept="image/*" class="hidden" onchange="previewImagen(event)">
                            </label>
                        </div>

                        <div>
                            <label class="correo-label">Insertar variable</label>
                            <div class="flex flex-wrap gap-1">
                                @foreach($vars as $var)
                                <button type="button" onclick="insertarVariable('plantilla_mensaje', '{{ $var }}')"
                                        class="text-xs px-2 py-1 rounded-md border font-mono transition hover:opacity-80"
                                        style="background:rgba(232,97,10,.07);color:var(--ovni-orange);border-color:rgba(232,97,10,.25)">
                                    {{ $var }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="correo-label">Mensaje</label>
                            <textarea id="plantilla_mensaje" rows="7" placeholder="Escribe el cuerpo del mensaje…"
                                      class="correo-input resize-none"></textarea>
                        </div>

                        <div class="flex gap-2 pt-1">
                            <button type="button" onclick="guardarPlantilla()" id="btn-guardar-plantilla"
                                    class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition"
                                    style="background:var(--ovni-orange)">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar
                            </button>
                            <button type="button" onclick="usarPlantilla()"
                                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <i class="fa-solid fa-check"></i> Usar
                            </button>
                            <button type="button" id="btn-eliminar-plantilla" onclick="eliminarPlantilla()"
                                    class="hidden items-center gap-2 px-3 py-2.5 rounded-xl border border-red-200 dark:border-red-800 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium transition">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    const PERIODO_CORREOS = '{{ $periodo }}';
    let plantillasCustom  = [];
    let plantillaActualId = null;
    let imagenQuitada     = false;

    const PLANTILLAS_BASE = {
        formal:  `Estimado equipo de [[cliente]],\n\nPor medio del presente, nos complace remitirle el informe mensual de servicios MSP correspondiente al período [[periodo]].\n\nDurante este mes se atendieron [[incidentes]] incidente(s) y [[solicitudes]] solicitud(es), con un tiempo promedio de atención de [[t_inc]] días para incidentes y [[t_sol]] días para solicitudes.\n\nQuedamos a su entera disposición para cualquier consulta.\n\nAtentamente,\nEquipo Ovnicom MSP`,
        cordial: `Hola equipo de [[cliente]],\n\nAdjunto encontrarán su reporte MSP del mes de [[periodo]].\n\nEste mes gestionamos [[incidentes]] incidente(s) y [[solicitudes]] solicitud(es). Fue un placer servirles.\n\nCualquier duda, con gusto les atendemos.\n\nSaludos,\nEquipo Ovnicom`,
        breve:   `Estimado [[cliente]],\n\nAdjunto su informe MSP — [[periodo]].\n\nIncidentes: [[incidentes]] | Solicitudes: [[solicitudes]]\n\nSaludos,\nOvnicom MSP`
    };

    function setPlantillaId(panel, id) {
        const el = document.getElementById(panel === 'individual' ? 'input_plantilla_id_individual' : 'input_plantilla_id_masivo');
        if (el) el.value = id || '';
    }

    // ── Individual ────────────────────────────────────────────────────────
    window.filterIndividual = function(q) {
        let visible = 0;
        document.querySelectorAll('.cliente-item-individual').forEach(item => {
            const show = (item.dataset.name || '').includes(q.toLowerCase());
            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const noRes = document.getElementById('no-results-individual');
        if (noRes) noRes.classList.toggle('hidden', visible > 0);
    };

    window.elegirCliente = function(name, email, cuenta) {
        document.getElementById('input_customer_name').value = name;
        document.getElementById('input_email').value         = email || '';
        document.getElementById('btn-ver-pdf-individual').href =
            `/admin/reports/msp/pdf/${encodeURIComponent(name)}/preview?periodo=${PERIODO_CORREOS}`;
        document.getElementById('individual-empty').classList.add('hidden');
        document.getElementById('individual-form').classList.remove('hidden');
        const initials = name.substring(0, 2).toUpperCase();
        document.getElementById('ind-avatar').textContent    = initials;
        document.getElementById('ind-nombre').textContent    = name;
        document.getElementById('ind-email').textContent     = email || 'Sin email';
        document.querySelectorAll('.cliente-item-individual').forEach(el => {
            el.classList.toggle('bg-orange-50', el.dataset.name === name.toLowerCase());
            el.classList.toggle('dark:bg-orange-900/20', el.dataset.name === name.toLowerCase());
        });
    };

    window.limpiarIndividual = function() {
        document.getElementById('individual-empty').classList.remove('hidden');
        document.getElementById('individual-form').classList.add('hidden');
        document.getElementById('input_customer_name').value = '';
        document.getElementById('input_email').value         = '';
        document.querySelectorAll('.cliente-item-individual').forEach(el => {
            el.classList.remove('bg-orange-50', 'dark:bg-orange-900/20');
        });
    };

    // ── Submit individual con spinner ─────────────────────────────────────
    document.getElementById('formIndividual').addEventListener('submit', function(e) {
        const btn  = document.getElementById('btnSubmitIndividual');
        const txt  = document.getElementById('btnSubmitIndividualText');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando correo...';
    });

    // ── Masivo ────────────────────────────────────────────────────────────
    window.filterMasivo = function(q) {
        let visible = 0;
        document.querySelectorAll('.cliente-item-masivo').forEach(item => {
            const show = (item.dataset.name || '').includes(q.toLowerCase());
            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const noRes = document.getElementById('no-results-masivo');
        if (noRes) noRes.classList.toggle('hidden', visible > 0);
    };

    window.updateMasivoCount = function() {
        const checked = document.querySelectorAll('.cliente-check-masivo:checked').length;
        const badge   = document.getElementById('badge-masivo');
        const bottom  = document.getElementById('count-masivo-bottom');
        const btn     = document.getElementById('btnSubmitMasivo');
        const btnTxt  = document.getElementById('btnSubmitMasivoText');

        if (badge)  badge.textContent  = checked + ' seleccionados';
        if (bottom) bottom.textContent = checked + ' clientes seleccionados';

        // Habilitar/deshabilitar botón de envío
        if (checked === 0) {
            btn.disabled = true;
            btnTxt.textContent = 'Selecciona al menos un cliente';
        } else {
            btn.disabled = false;
            btnTxt.textContent = `Enviar a ${checked} cliente${checked > 1 ? 's' : ''}`;
        }

        const total = document.querySelectorAll('.cliente-check-masivo:not(:disabled)').length;
        const chk   = document.getElementById('checkTodos');
        if (chk) chk.checked = checked === total && total > 0;
    };

    window.toggleTodosMasivo = function(checkbox) {
        document.querySelectorAll('.cliente-check-masivo:not(:disabled)').forEach(c => c.checked = checkbox.checked);
        window.updateMasivoCount();
    };

    // ── Submit masivo: construir inputs hidden ANTES de enviar ──────────
    document.getElementById('formMasivo').addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.cliente-check-masivo:checked');

        if (checked.length === 0) {
            e.preventDefault();
            alert('Selecciona al menos un cliente antes de enviar.');
            return;
        }

        // Limpiar inputs previos
        const container = document.getElementById('clientes-hidden-container');
        container.innerHTML = '';

        // Crear un par de hidden inputs (customer_name + email) por cada cliente marcado
        checked.forEach((cb, index) => {
            const customerInput = document.createElement('input');
            customerInput.type  = 'hidden';
            customerInput.name  = `clientes[${index}][customer_name]`;
            customerInput.value = cb.dataset.customer;
            container.appendChild(customerInput);

            const emailInput = document.createElement('input');
            emailInput.type  = 'hidden';
            emailInput.name  = `clientes[${index}][email]`;
            emailInput.value = cb.dataset.email;
            container.appendChild(emailInput);
        });

        console.log(`[Envío masivo] Enviando ${checked.length} clientes`);

        // Spinner y deshabilitar botón
        const btn     = document.getElementById('btnSubmitMasivo');
        const btnTxt  = document.getElementById('btnSubmitMasivoText');
        const btnIcon = document.getElementById('btnSubmitMasivoIcon');
        btn.disabled = true;
        btnIcon.className = 'fa-solid fa-spinner fa-spin';
        btnTxt.textContent = `Enviando a ${checked.length} cliente${checked.length > 1 ? 's' : ''}...`;
    });

    // ── Plantillas ────────────────────────────────────────────────────────
    window.aplicarPlantillaSeleccionada = function(value, panel) {
        const msgId  = panel === 'individual' ? 'mensaje_individual' : 'mensaje_masivo';
        const subjId = panel === 'individual' ? 'input_subject' : 'input_subject_masivo';
        if (!value) { setPlantillaId(panel, ''); return; }
        if (value.startsWith('__')) {
            document.getElementById(msgId).value = PLANTILLAS_BASE[value.replace('__', '')] || '';
            setPlantillaId(panel, '');
        } else {
            const p = plantillasCustom.find(x => x.id == value);
            if (p) {
                document.getElementById(msgId).value = p.mensaje || '';
                if (p.asunto) document.getElementById(subjId).value = p.asunto;
                setPlantillaId(panel, p.id);
            }
        }
    };

    window.insertarVariable = function(textareaId, variable) {
        const ta    = document.getElementById(textareaId);
        const start = ta.selectionStart;
        const end   = ta.selectionEnd;
        ta.value    = ta.value.substring(0, start) + variable + ta.value.substring(end);
        ta.selectionStart = ta.selectionEnd = start + variable.length;
        ta.focus();
    };

    window.switchTab = function(tab) {
        document.querySelectorAll('.panel').forEach(p => p.classList.add('hidden'));
        document.getElementById('panel-' + tab).classList.remove('hidden');
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('correo-tab-active-orange', 'correo-tab-active-blue');
            b.style.borderColor = '';
        });
        const btn = document.getElementById('tab-' + tab);
        if (tab === 'individual') btn.classList.add('correo-tab-active-orange');
        else btn.classList.add('correo-tab-active-blue');
    };

    window.openModalPlantillas  = function() { document.getElementById('modal-plantillas').classList.remove('hidden'); cargarPlantillas(); };
    window.closeModalPlantillas = function() { document.getElementById('modal-plantillas').classList.add('hidden'); };

    // Escapa texto interpolado en innerHTML (los nombres de plantilla los escribe el usuario)
    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    }

    async function cargarPlantillas() {
        try {
            const res = await fetch('{{ route('admin.msp.plantillas.index') }}', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if (!res.ok) return;
            plantillasCustom = await res.json();
            renderListaCustom();
            actualizarOptgroups();
        } catch (e) { console.error('Error cargando plantillas:', e); }
    }

    function renderListaCustom() {
        const container = document.getElementById('lista-custom-plantillas');
        if (!container) return;
        const empty = document.getElementById('no-custom-plantillas');
        if (!plantillasCustom.length) {
            container.innerHTML = '';
            if (empty) { container.appendChild(empty); empty.classList.remove('hidden'); }
            return;
        }
        if (empty) empty.classList.add('hidden');
        container.innerHTML = plantillasCustom.map(p => `
            <div class="flex items-center gap-2 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b dark:border-gray-700/50 transition"
                 onclick="editarPlantilla(${p.id})">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 text-white text-xs" style="background:var(--ovni-orange)">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <span class="text-xs text-gray-700 dark:text-gray-300 truncate flex-1">${escHtml(p.nombre)}</span>
                ${p.imagen_path ? '<i class="fa-solid fa-image text-xs text-orange-400 flex-shrink-0" title="Tiene banner"></i>' : ''}
            </div>
        `).join('');
    }

    function actualizarOptgroups() {
        ['individual', 'masivo'].forEach(panel => {
            const og = document.getElementById(`optgroup_custom_${panel}`);
            if (!og) return;
            og.innerHTML = plantillasCustom.map(p =>
                `<option value="${p.id}">${escHtml(p.nombre)}${p.imagen_path ? ' 🖼' : ''}</option>`
            ).join('');
        });
    }

    window.nuevaPlantilla = function() {
        plantillaActualId = null; imagenQuitada = false;
        ['plantilla_id','plantilla_nombre','plantilla_asunto','plantilla_mensaje'].forEach(id => {
            document.getElementById(id).value = '';
        });
        document.getElementById('plantilla_imagen').value = '';
        document.getElementById('imagen-preview-wrap').classList.add('hidden');
        document.getElementById('imagen-label').textContent = 'Subir imagen (JPG, PNG, WEBP — máx. 2MB)';
        document.getElementById('plantilla_es_predeterminada').value = '0';
        document.getElementById('editor-empty').classList.add('hidden');
        document.getElementById('editor-form').classList.remove('hidden');
        document.getElementById('btn-eliminar-plantilla').classList.add('hidden');
        document.getElementById('btn-guardar-plantilla').classList.remove('hidden');
        document.getElementById('campo-imagen').classList.remove('hidden');
    };

    window.verPlantillaPredeterminada = function(key) {
        plantillaActualId = null;
        document.getElementById('plantilla_es_predeterminada').value = '1';
        document.getElementById('plantilla_nombre').value  = key.charAt(0).toUpperCase() + key.slice(1);
        document.getElementById('plantilla_asunto').value  = '';
        document.getElementById('plantilla_mensaje').value = PLANTILLAS_BASE[key] || '';
        document.getElementById('imagen-preview-wrap').classList.add('hidden');
        document.getElementById('campo-imagen').classList.add('hidden');
        document.getElementById('editor-empty').classList.add('hidden');
        document.getElementById('editor-form').classList.remove('hidden');
        document.getElementById('btn-eliminar-plantilla').classList.add('hidden');
        document.getElementById('btn-guardar-plantilla').classList.add('hidden');
    };

    window.editarPlantilla = function(id) {
        const p = plantillasCustom.find(x => x.id === id);
        if (!p) return;
        plantillaActualId = id; imagenQuitada = false;
        document.getElementById('plantilla_id').value      = p.id;
        document.getElementById('plantilla_nombre').value  = p.nombre;
        document.getElementById('plantilla_asunto').value  = p.asunto || '';
        document.getElementById('plantilla_mensaje').value = p.mensaje;
        document.getElementById('plantilla_es_predeterminada').value = '0';
        document.getElementById('campo-imagen').classList.remove('hidden');
        document.getElementById('btn-eliminar-plantilla').classList.remove('hidden');
        document.getElementById('btn-guardar-plantilla').classList.remove('hidden');
        if (p.imagen_path) {
            document.getElementById('imagen-preview-img').src = `/storage/${p.imagen_path}`;
            document.getElementById('imagen-preview-wrap').classList.remove('hidden');
            document.getElementById('imagen-label').textContent = 'Cambiar imagen';
        } else {
            document.getElementById('imagen-preview-wrap').classList.add('hidden');
            document.getElementById('imagen-label').textContent = 'Subir imagen (JPG, PNG, WEBP — máx. 2MB)';
        }
        document.getElementById('editor-empty').classList.add('hidden');
        document.getElementById('editor-form').classList.remove('hidden');
    };

    window.previewImagen = function(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imagen-preview-img').src = e.target.result;
            document.getElementById('imagen-preview-wrap').classList.remove('hidden');
            document.getElementById('imagen-label').textContent = file.name;
        };
        reader.readAsDataURL(file);
    };

    window.quitarImagen = function() {
        document.getElementById('plantilla_imagen').value = '';
        document.getElementById('imagen-preview-wrap').classList.add('hidden');
        document.getElementById('imagen-label').textContent = 'Subir imagen (JPG, PNG, WEBP — máx. 2MB)';
        imagenQuitada = true;
    };

    window.guardarPlantilla = async function() {
        const nombre  = document.getElementById('plantilla_nombre').value.trim();
        const mensaje = document.getElementById('plantilla_mensaje').value.trim();
        if (!nombre || !mensaje) { alert('El nombre y el mensaje son obligatorios.'); return; }
        const formData = new FormData();
        formData.append('nombre',  nombre);
        formData.append('asunto',  document.getElementById('plantilla_asunto').value);
        formData.append('mensaje', mensaje);
        if (imagenQuitada) formData.append('quitar_imagen', '1');
        const imagenFile = document.getElementById('plantilla_imagen').files[0];
        if (imagenFile) formData.append('imagen', imagenFile);
        const id  = document.getElementById('plantilla_id').value;
        const url = id ? `{{ route('admin.msp.plantillas.store') }}/${id}` : '{{ route('admin.msp.plantillas.store') }}';
        formData.append('_token', '{{ csrf_token() }}');
        if (id) formData.append('_method', 'PUT');
        try {
            const res = await fetch(url, { method: 'POST', body: formData, headers: { 'Accept': 'application/json' } });
            if (!res.ok) { console.error(await res.text()); alert('Error al guardar.'); return; }
            const data = await res.json();
            if (data.success) { await cargarPlantillas(); window.editarPlantilla(data.plantilla.id); }
        } catch (e) { alert('Error al guardar la plantilla.'); }
    };

    window.eliminarPlantilla = async function() {
        if (!plantillaActualId || !confirm('¿Eliminar esta plantilla?')) return;
        try {
            const res  = await fetch(`{{ url('admin/reports/msp/plantillas') }}/${plantillaActualId}`, {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                plantillaActualId = null;
                document.getElementById('editor-form').classList.add('hidden');
                document.getElementById('editor-empty').classList.remove('hidden');
                await cargarPlantillas();
            }
        } catch (e) { alert('Error al eliminar.'); }
    };

    window.usarPlantilla = function() {
        const mensaje = document.getElementById('plantilla_mensaje').value;
        const asunto  = document.getElementById('plantilla_asunto').value;
        const id      = document.getElementById('plantilla_id').value;
        const esPred  = document.getElementById('plantilla_es_predeterminada').value === '1';
        const panelInd = !document.getElementById('panel-individual').classList.contains('hidden');
        if (panelInd) {
            document.getElementById('mensaje_individual').value = mensaje;
            if (asunto) document.getElementById('input_subject').value = asunto;
        } else {
            document.getElementById('mensaje_masivo').value = mensaje;
            if (asunto) document.getElementById('input_subject_masivo').value = asunto;
        }
        setPlantillaId(panelInd ? 'individual' : 'masivo', esPred ? '' : id);
        window.closeModalPlantillas();
    };

    // Inicialización
    window.switchTab('individual');
    window.updateMasivoCount();
    cargarPlantillas();
})();
</script>
@endpush