{{-- resources/views/admin/reports/msp/correos.blade.php --}}
@extends('admin.reports.msp.layouts.app')
@section('title', 'Envío de Correos')

@php $vars = ['[[cliente]]','[[periodo]]','[[incidentes]]','[[solicitudes]]','[[t_inc]]','[[t_sol]]','[[cuenta]]']; @endphp

@section('content')
<div class="space-y-6 fade-in">

    @if(session('success'))
    <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-xl text-sm">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-xl text-sm">
        <i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}
    </div>
    @endif

    {{-- Mostrar errores de validación --}}
    @if($errors->any())
    <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-xl text-sm">
        <div class="font-semibold mb-2"><i class="fa-solid fa-circle-exclamation"></i> Errores de validación:</div>
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Selector de período --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
        <div class="flex flex-wrap items-center gap-4">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Período</label>
                <div class="flex gap-2 flex-wrap">
                    @foreach($periodos as $p)
                    <a href="{{ route('admin.msp.correos', ['periodo' => $p]) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium border transition
                              {{ $periodo === $p ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50 dark:text-gray-300 dark:border-gray-600' }}"
                       style="{{ $periodo === $p ? 'background:var(--ovni-orange)' : '' }}">
                        {{ $p }}
                    </a>
                    @endforeach
                </div>
            </div>
            <div class="ml-auto text-sm text-gray-500 dark:text-gray-400">
                <i class="fa-solid fa-users mr-1"></i>
                <strong>{{ $clientes->count() }}</strong> clientes en este período
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="grid grid-cols-2 gap-4">
        <button onclick="switchTab('individual')" id="tab-individual"
                class="flex items-center gap-3 p-4 rounded-2xl border-2 transition text-left tab-btn"
                style="border-color:var(--ovni-orange); background:#fff7f0;">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white flex-shrink-0" style="background:var(--ovni-orange)">
                <i class="fa-solid fa-envelope"></i>
            </div>
            <div>
                <div class="font-semibold text-gray-800 dark:text-white">Envío Individual</div>
                <div class="text-xs text-gray-500">Envía el reporte PDF a un cliente específico</div>
            </div>
        </button>
        <button onclick="switchTab('masivo')" id="tab-masivo"
                class="flex items-center gap-3 p-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 transition text-left tab-btn">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white flex-shrink-0 bg-blue-600">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <div>
                <div class="font-semibold text-gray-800 dark:text-white">Envío Masivo</div>
                <div class="text-xs text-gray-500">Envía reportes a múltiples clientes a la vez</div>
            </div>
        </button>
    </div>

    {{-- ══ PANEL INDIVIDUAL ══ --}}
    <div id="panel-individual" class="panel">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b dark:border-gray-700 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs" style="background:var(--ovni-orange)">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <h3 class="font-semibold text-gray-700 dark:text-gray-200">Envío Individual</h3>
            </div>

            <div class="grid grid-cols-5 divide-x dark:divide-gray-700" style="min-height:520px">

                {{-- Columna izquierda: lista clientes --}}
                <div class="col-span-2 flex flex-col">
                    <div class="p-3 border-b dark:border-gray-700">
                        <div class="relative">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" id="searchIndividual"
                                   placeholder="Buscar cliente..." autocomplete="off"
                                   oninput="filterIndividual(this.value)"
                                   class="w-full border dark:border-gray-600 rounded-lg pl-8 pr-4 py-2 text-xs
                                          focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-1" id="listaIndividual" style="max-height:440px">
                        @foreach($clientes as $cliente)
                        <div class="cliente-item-individual flex items-center gap-3 px-4 py-3
                                    hover:bg-orange-50 dark:hover:bg-gray-700 cursor-pointer
                                    border-b dark:border-gray-700/50 transition"
                             data-name="{{ strtolower($cliente->customer_name) }}"
                             onclick="elegirCliente('{{ addslashes($cliente->customer_name) }}', '{{ $cliente->email_cliente }}', '{{ $cliente->numero_cuenta }}')">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                 style="background:var(--ovni-orange)">
                                {{ strtoupper(substr($cliente->customer_name, 0, 2)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-gray-800 dark:text-white truncate">{{ $cliente->customer_name }}</div>
                                <div class="text-xs {{ $cliente->email_cliente ? 'text-gray-400' : 'text-red-400' }}">
                                    {{ $cliente->email_cliente ?? 'Sin email' }}
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-xs text-gray-300 flex-shrink-0"></i>
                        </div>
                        @endforeach
                        <div id="no-results-individual" class="hidden px-4 py-6 text-xs text-gray-400 text-center">
                            No se encontraron clientes
                        </div>
                    </div>
                </div>

                {{-- Columna derecha: formulario --}}
                <div class="col-span-3 flex flex-col">
                    <div id="individual-empty" class="flex-1 flex flex-col items-center justify-center p-8 text-center text-gray-400">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4 bg-orange-50 dark:bg-orange-900/20">
                            <i class="fa-solid fa-arrow-left text-2xl" style="color:var(--ovni-orange)"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Selecciona un cliente</p>
                        <p class="text-xs mt-1">para comenzar el envío</p>
                    </div>

                    <div id="individual-form" class="hidden flex-1 flex flex-col">
                        <div class="px-5 py-3 border-b dark:border-gray-700 flex items-center gap-3" style="background:#fff7f0">
                            <div id="ind-avatar" class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                                 style="background:var(--ovni-orange)">--</div>
                            <div class="flex-1 min-w-0">
                                <div id="ind-nombre" class="text-sm font-semibold text-gray-800 truncate"></div>
                                <div id="ind-email" class="text-xs text-gray-500"></div>
                            </div>
                            <button type="button" onclick="limpiarIndividual()"
                                    class="text-xs text-gray-400 hover:text-gray-600 transition px-2 py-1 rounded-lg hover:bg-white">
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
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Email destino</label>
                                        <div class="relative">
                                            <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                            <input type="email" name="email" id="input_email" placeholder="cliente@empresa.com" required
                                                   class="w-full border dark:border-gray-600 rounded-xl pl-9 pr-4 py-2.5 text-sm
                                                          focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Asunto</label>
                                        <input type="text" name="subject" id="input_subject" value="Informe MSP — {{ $periodo }}" required
                                               class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                                      focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="text-xs font-semibold text-gray-500 uppercase">Plantilla</label>
                                            <button type="button" onclick="openModalPlantillas()"
                                                    class="text-xs text-orange-600 hover:underline flex items-center gap-1">
                                                <i class="fa-solid fa-sliders"></i> Gestionar
                                            </button>
                                        </div>
                                        <select id="select_plantilla_individual" onchange="aplicarPlantillaSeleccionada(this.value, 'individual')"
                                                class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                                       focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                                            <option value="">— Sin plantilla —</option>
                                            <optgroup label="Predeterminadas">
                                                <option value="__formal">Formal</option>
                                                <option value="__cordial">Cordial</option>
                                                <option value="__breve">Breve</option>
                                            </optgroup>
                                            <optgroup label="Mis plantillas" id="optgroup_custom_individual"></optgroup>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Insertar variable</label>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($vars as $var)
                                            <button type="button" onclick="insertarVariable('mensaje_individual', '{{ $var }}')"
                                                    class="text-xs px-2 py-1 bg-orange-50 dark:bg-orange-900/20 text-orange-600
                                                           border border-orange-200 dark:border-orange-700 rounded-lg hover:bg-orange-100 transition font-mono">
                                                {{ $var }}
                                            </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Mensaje (opcional)</label>
                                        <textarea name="mensaje" id="mensaje_individual" rows="4" placeholder="Estimado cliente..."
                                                  class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                                         focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                                    </div>

                                    <div class="flex gap-3 pt-1">
                                        <button type="submit" id="btnSubmitIndividual"
                                                class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition"
                                                style="background:var(--ovni-orange)">
                                            <i class="fa-solid fa-paper-plane"></i> <span id="btnSubmitIndividualText">Enviar con PDF</span>
                                        </button>
                                        <a href="#" id="btn-ver-pdf-individual" target="_blank"
                                           class="flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-medium
                                                  text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                            <i class="fa-solid fa-eye"></i> Ver PDF
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
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b dark:border-gray-700 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center text-white text-xs">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <h3 class="font-semibold text-gray-700 dark:text-gray-200">Envío Masivo</h3>
                <span id="badge-masivo" class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full ml-1">0 seleccionados</span>
            </div>

            <form action="{{ route('admin.msp.correos.masivo') }}" method="POST" id="formMasivo">
                @csrf
                <input type="hidden" name="periodo" value="{{ $periodo }}">
                <input type="hidden" name="plantilla_id" id="input_plantilla_id_masivo" value="">

                <div class="grid grid-cols-5 divide-x dark:divide-gray-700" style="min-height:520px">

                    {{-- Columna izquierda: lista con checkboxes --}}
                    <div class="col-span-2 flex flex-col">
                        <div class="p-3 border-b dark:border-gray-700">
                            <div class="relative mb-2">
                                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input type="text" placeholder="Buscar cliente..."
                                       oninput="filterMasivo(this.value)"
                                       class="w-full border dark:border-gray-600 rounded-lg pl-8 pr-4 py-2 text-xs
                                              focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <label class="flex items-center gap-2 px-1 cursor-pointer select-none">
                                <input type="checkbox" id="checkTodos" onchange="toggleTodosMasivo(this)"
                                       class="accent-blue-600 w-3.5 h-3.5">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Seleccionar todos con email</span>
                            </label>
                        </div>

                        {{-- Lista con checkboxes — FIX: hidden del email SOLO si hay email --}}
                        <div class="overflow-y-auto flex-1" id="listaMasivo" style="max-height:440px">
                            @foreach($clientes as $cliente)
                            <label class="cliente-item-masivo flex items-center gap-3 px-4 py-3
                                          hover:bg-blue-50 dark:hover:bg-gray-700 {{ $cliente->email_cliente ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}
                                          border-b dark:border-gray-700/50 transition"
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
                                    <div class="text-xs font-medium {{ $cliente->email_cliente ? 'text-gray-800 dark:text-white' : 'text-gray-400 dark:text-gray-500' }} truncate">
                                        {{ $cliente->customer_name }}
                                    </div>
                                    <div class="text-xs {{ $cliente->email_cliente ? 'text-gray-400' : 'text-red-400' }}">
                                        {{ $cliente->email_cliente ?? 'Sin email' }}
                                    </div>
                                </div>
                            </label>
                            @endforeach
                            <div id="no-results-masivo" class="hidden px-4 py-6 text-xs text-gray-400 text-center">
                                No se encontraron clientes
                            </div>
                        </div>

                        <div class="p-3 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                            <span class="text-xs text-gray-500" id="count-masivo-bottom">0 clientes seleccionados</span>
                        </div>
                    </div>

                    {{-- Columna derecha: formulario masivo --}}
                    <div class="col-span-3 p-5 flex flex-col gap-4 overflow-y-auto">
                        <div id="masivo-info-empty" class="flex items-center gap-3 p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700">
                            <i class="fa-solid fa-circle-info text-blue-500 flex-shrink-0"></i>
                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                Selecciona los clientes de la lista y configura el mensaje a enviar.
                                Se generará un PDF individual por cada cliente.
                            </p>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Asunto</label>
                            <input type="text" name="subject" id="input_subject_masivo"
                                   value="Informe MSP — {{ $periodo }}" required
                                   class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                          focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-gray-500 uppercase">Plantilla</label>
                                <button type="button" onclick="openModalPlantillas()"
                                        class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                                    <i class="fa-solid fa-sliders"></i> Gestionar
                                </button>
                            </div>
                            <select id="select_plantilla_masivo" onchange="aplicarPlantillaSeleccionada(this.value, 'masivo')"
                                    class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                           focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">— Sin plantilla —</option>
                                <optgroup label="Predeterminadas">
                                    <option value="__formal">Formal</option>
                                    <option value="__cordial">Cordial</option>
                                    <option value="__breve">Breve</option>
                                </optgroup>
                                <optgroup label="Mis plantillas" id="optgroup_custom_masivo"></optgroup>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Insertar variable</label>
                            <div class="flex flex-wrap gap-1">
                                @foreach($vars as $var)
                                <button type="button" onclick="insertarVariable('mensaje_masivo', '{{ $var }}')"
                                        class="text-xs px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600
                                               border border-blue-200 dark:border-blue-700 rounded-lg hover:bg-blue-100 transition font-mono">
                                    {{ $var }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Mensaje</label>
                            <textarea name="mensaje" id="mensaje_masivo" rows="6"
                                      placeholder="Estimado cliente, adjunto su informe MSP..."
                                      class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                             focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                            <p class="text-xs text-gray-400 mt-1">Las variables <span class="font-mono">[[cliente]]</span>, <span class="font-mono">[[periodo]]</span>, etc. se reemplazan por cada cliente.</p>
                        </div>

                        {{-- Contenedor donde el JS va a INYECTAR los inputs hidden justo antes del submit --}}
                        <div id="clientes-hidden-container"></div>

                        <button type="submit" id="btnSubmitMasivo" disabled
                                class="flex items-center justify-center gap-2 py-3 rounded-xl text-white text-sm font-semibold bg-blue-600 hover:bg-blue-700 transition mt-auto disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-paper-plane" id="btnSubmitMasivoIcon"></i>
                            <span id="btnSubmitMasivoText">Selecciona al menos un cliente</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ MODAL GESTIONAR PLANTILLAS ══ --}}
<div id="modal-plantillas" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs" style="background:var(--ovni-orange)">
                    <i class="fa-solid fa-palette"></i>
                </div>
                <h3 class="font-bold text-gray-800 dark:text-white">Gestionar Plantillas</h3>
            </div>
            <button onclick="closeModalPlantillas()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="flex flex-1 overflow-hidden divide-x dark:divide-gray-700">
            <div class="w-64 flex flex-col flex-shrink-0">
                <div class="p-3 border-b dark:border-gray-700">
                    <button type="button" onclick="nuevaPlantilla()"
                            class="w-full flex items-center justify-center gap-2 py-2 rounded-lg text-white text-xs font-medium hover:opacity-90 transition"
                            style="background:var(--ovni-orange)">
                        <i class="fa-solid fa-plus"></i> Nueva plantilla
                    </button>
                </div>
                <div class="overflow-y-auto flex-1">
                    <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase border-b dark:border-gray-700">Predeterminadas</div>
                    @foreach(['formal' => 'Formal', 'cordial' => 'Cordial', 'breve' => 'Breve'] as $key => $label)
                    <div class="flex items-center gap-2 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b dark:border-gray-700/50"
                         onclick="verPlantillaPredeterminada('{{ $key }}')">
                        <div class="w-6 h-6 rounded-md bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-file-lines text-xs text-gray-400"></i>
                        </div>
                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        <span class="ml-auto text-xs bg-gray-100 dark:bg-gray-700 text-gray-400 px-1.5 py-0.5 rounded">Base</span>
                    </div>
                    @endforeach
                    <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase border-b dark:border-gray-700 mt-1">Mis plantillas</div>
                    <div id="lista-custom-plantillas">
                        <div class="px-3 py-4 text-xs text-gray-400 text-center" id="no-custom-plantillas">
                            <i class="fa-solid fa-inbox mb-1 text-lg block"></i>
                            Sin plantillas guardadas
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex flex-col overflow-hidden">
                <div class="flex-1 overflow-y-auto p-6">
                    <div id="editor-empty" class="h-full flex flex-col items-center justify-center text-center text-gray-400">
                        <i class="fa-solid fa-arrow-left text-3xl mb-3"></i>
                        <p class="text-sm font-medium">Selecciona una plantilla</p>
                        <p class="text-xs mt-1">o crea una nueva</p>
                    </div>

                    <div id="editor-form" class="hidden space-y-4">
                        <input type="hidden" id="plantilla_id" value="">
                        <input type="hidden" id="plantilla_es_predeterminada" value="0">

                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Nombre</label>
                            <input type="text" id="plantilla_nombre" placeholder="Ej: Plantilla corporativa"
                                   class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Asunto (opcional)</label>
                            <input type="text" id="plantilla_asunto" placeholder="Informe MSP — [[periodo]]"
                                   class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div id="campo-imagen">
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Imagen / Banner (opcional)</label>
                            <div id="imagen-preview-wrap" class="hidden mb-2">
                                <img id="imagen-preview-img" src="" alt="Preview" class="max-h-24 rounded-lg border dark:border-gray-600 object-contain">
                                <button type="button" onclick="quitarImagen()" class="mt-1 text-xs text-red-500 hover:underline block">
                                    <i class="fa-solid fa-trash mr-1"></i> Quitar imagen
                                </button>
                            </div>
                            <label class="flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-gray-300 dark:border-gray-600
                                          rounded-xl cursor-pointer hover:border-orange-400 transition text-sm text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-image text-orange-400"></i>
                                <span id="imagen-label">Subir imagen (JPG, PNG, WEBP — máx. 2MB)</span>
                                <input type="file" id="plantilla_imagen" accept="image/*" class="hidden" onchange="previewImagen(event)">
                            </label>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Insertar variable</label>
                            <div class="flex flex-wrap gap-1">
                                @foreach($vars as $var)
                                <button type="button" onclick="insertarVariable('plantilla_mensaje', '{{ $var }}')"
                                        class="text-xs px-2 py-1 bg-orange-50 dark:bg-orange-900/20 text-orange-600
                                               border border-orange-200 dark:border-orange-700 rounded-lg hover:bg-orange-100 transition font-mono">
                                    {{ $var }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Mensaje</label>
                            <textarea id="plantilla_mensaje" rows="7" placeholder="Escribe el cuerpo del mensaje..."
                                      class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="button" onclick="guardarPlantilla()" id="btn-guardar-plantilla"
                                    class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition"
                                    style="background:var(--ovni-orange)">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar
                            </button>
                            <button type="button" onclick="usarPlantilla()"
                                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-medium
                                           text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <i class="fa-solid fa-check"></i> Usar
                            </button>
                            <button type="button" id="btn-eliminar-plantilla" onclick="eliminarPlantilla()"
                                    class="hidden flex items-center gap-2 px-4 py-2.5 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium transition">
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
        document.querySelectorAll('.tab-btn').forEach(b => { b.style.borderColor = ''; b.style.background = ''; });
        const btn = document.getElementById('tab-' + tab);
        if (tab === 'individual') { btn.style.borderColor = 'var(--ovni-orange)'; btn.style.background = '#fff7f0'; }
        else { btn.style.borderColor = '#2563eb'; btn.style.background = '#eff6ff'; }
    };

    window.openModalPlantillas  = function() { document.getElementById('modal-plantillas').classList.remove('hidden'); cargarPlantillas(); };
    window.closeModalPlantillas = function() { document.getElementById('modal-plantillas').classList.add('hidden'); };

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
                <span class="text-xs text-gray-700 dark:text-gray-300 truncate flex-1">${p.nombre}</span>
                ${p.imagen_path ? '<i class="fa-solid fa-image text-xs text-orange-400 flex-shrink-0" title="Tiene banner"></i>' : ''}
            </div>
        `).join('');
    }

    function actualizarOptgroups() {
        ['individual', 'masivo'].forEach(panel => {
            const og = document.getElementById(`optgroup_custom_${panel}`);
            if (!og) return;
            og.innerHTML = plantillasCustom.map(p =>
                `<option value="${p.id}">${p.nombre}${p.imagen_path ? ' 🖼' : ''}</option>`
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