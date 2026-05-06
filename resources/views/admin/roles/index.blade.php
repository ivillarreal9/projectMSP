{{-- resources/views/admin/roles/index.blade.php --}}
@extends('admin.reports.msp.layouts.app')
@section('title', 'Roles')

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

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Roles y Permisos</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Gestiona los roles y los módulos a los que tienen acceso</p>
        </div>
        <button onclick="openModal()"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-medium hover:opacity-90 transition"
                style="background:var(--ovni-orange)">
            <i class="fa-solid fa-plus"></i> Nuevo rol
        </button>
    </div>

    {{-- Lista de roles --}}
    <div class="grid grid-cols-1 gap-4">
        @forelse($roles as $role)
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                         style="background:var(--ovni-orange)">
                        {{ strtoupper(substr($role->nombre, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">{{ $role->nombre }}</h3>
                            <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 px-2 py-0.5 rounded-full font-mono">
                                {{ $role->slug }}
                            </span>
                            <span class="text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-full">
                                {{ $role->users_count }} {{ $role->users_count === 1 ? 'usuario' : 'usuarios' }}
                            </span>
                        </div>
                        @if($role->descripcion)
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $role->descripcion }}</p>
                        @endif

                        {{-- Módulos del rol --}}
                        <div class="flex flex-wrap gap-1.5 mt-3">
                            @php
                                $modulosConfig = [
                                    'msp_reports' => ['label' => 'MSP Reports', 'color' => 'orange'],
                                    'api_msp'     => ['label' => 'API MSP',     'color' => 'purple'],
                                    'meta2'       => ['label' => 'META 2',      'color' => 'green'],
                                    'encuestas'   => ['label' => 'Encuestas',   'color' => 'blue'],
                                    'usuarios'    => ['label' => 'Usuarios',    'color' => 'pink'],
                                    'sales'       => ['label' => 'Sales',       'color' => 'teal'],
                                ];
                                $colorMap = [
                                    'orange' => 'bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-700',
                                    'purple' => 'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-700',
                                    'green'  => 'bg-green-50 text-green-600 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-700',
                                    'blue'   => 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-700',
                                    'pink'   => 'bg-pink-50 text-pink-600 border-pink-200 dark:bg-pink-900/20 dark:text-pink-400 dark:border-pink-700',
                                    'teal'   => 'bg-teal-50 text-teal-600 border-teal-200 dark:bg-teal-900/20 dark:text-teal-400 dark:border-teal-700',
                                ];
                            @endphp
                            @forelse($role->modulos ?? [] as $modulo)
                                @if(isset($modulosConfig[$modulo]))
                                <span class="text-xs px-2 py-0.5 rounded-lg border font-medium {{ $colorMap[$modulosConfig[$modulo]['color']] }}">
                                    {{ $modulosConfig[$modulo]['label'] }}
                                </span>
                                @endif
                            @empty
                            <span class="text-xs text-gray-400 dark:text-gray-500 italic">Sin módulos asignados</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button onclick="openModal({{ $role->id }})"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium
                                   text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <i class="fa-solid fa-pen-to-square"></i> Editar
                    </button>
                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST"
                          onsubmit="return confirm('¿Eliminar el rol {{ $role->nombre }}?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-xs font-medium
                                       text-red-600 hover:bg-red-50 transition">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 p-12 text-center">
            <i class="fa-solid fa-shield-halved text-4xl text-gray-300 dark:text-gray-600 mb-3 block"></i>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No hay roles creados</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Crea tu primer rol para gestionar permisos</p>
        </div>
        @endforelse
    </div>
</div>

{{-- ══ MODAL CREAR/EDITAR ROL ══ --}}
<div id="modal-rol" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs" style="background:var(--ovni-orange)">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="font-bold text-gray-800 dark:text-white" id="modal-title">Crear nuevo rol</h3>
            </div>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        {{-- Form --}}
        <form id="form-rol" action="{{ route('admin.roles.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="form-method" value="POST">
            <input type="hidden" name="role_id" id="form-role-id" value="">

            <div class="px-6 py-5 space-y-5">

                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Nombre del rol</label>
                    <input type="text" name="nombre" id="input-nombre" required
                           placeholder="Ej: Supervisor, Analista, Editor..."
                           class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                  focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Descripción (opcional)</label>
                    <input type="text" name="descripcion" id="input-descripcion"
                           placeholder="Describe brevemente este rol..."
                           class="w-full border dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm
                                  focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="text-xs font-semibold text-gray-500 uppercase mb-2 block">Módulos con acceso</label>
                    <div class="grid grid-cols-2 gap-2" id="modulos-grid">

                        @php
                        $modulosList = [
                            ['slug' => 'msp_reports', 'nombre' => 'MSP Reports',  'desc' => 'Reportes y correos',    'color' => '#e8610a', 'bg' => '#fff3e8', 'icon' => 'fa-file-chart-column'],
                            ['slug' => 'api_msp',     'nombre' => 'API MSP',       'desc' => 'Consulta de la API',    'color' => '#7c3aed', 'bg' => '#f0edfe', 'icon' => 'fa-code'],
                            ['slug' => 'meta2',       'nombre' => 'META 2',        'desc' => 'Metas y objetivos',     'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'fa-bolt'],
                            ['slug' => 'encuestas',   'nombre' => 'Encuestas',     'desc' => 'Satisfacción clientes', 'color' => '#2563eb', 'bg' => '#eff6ff', 'icon' => 'fa-clipboard-list'],
                            ['slug' => 'usuarios',    'nombre' => 'Usuarios',      'desc' => 'Gestión de accesos',    'color' => '#a21caf', 'bg' => '#fdf4ff', 'icon' => 'fa-users'],
                            ['slug' => 'sales',       'nombre' => 'Sales',         'desc' => 'Dashboard de ventas',   'color' => '#0d9488', 'bg' => '#f0fdfa', 'icon' => 'fa-chart-line'],
            ['slug' => 'glpi',     'nombre' => 'GLPI',         'desc' => 'Inventario de activos',  'color' => '#0891b2', 'bg' => '#ecfeff', 'icon' => 'fa-server'],
                        ];
                        @endphp

                        @foreach($modulosList as $mod)
                        <label class="modulo-card flex items-center gap-3 p-3 border dark:border-gray-600 rounded-xl cursor-pointer
                                      hover:border-orange-300 dark:hover:border-orange-700 transition select-none"
                               style="background: var(--color-background-primary)">
                            <input type="checkbox" name="modulos[]" value="{{ $mod['slug'] }}"
                                   class="modulo-check hidden"
                                   onchange="updateCardStyle(this)">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                 style="background: {{ $mod['bg'] }}">
                                <i class="fa-solid {{ $mod['icon'] }} text-xs" style="color: {{ $mod['color'] }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="block text-xs font-medium text-gray-800 dark:text-white">{{ $mod['nombre'] }}</span>
                                <span class="block text-xs text-gray-400 dark:text-gray-500">{{ $mod['desc'] }}</span>
                            </div>
                            <div class="modulo-check-icon w-5 h-5 rounded-md border-2 border-gray-200 dark:border-gray-600
                                        flex items-center justify-center flex-shrink-0 transition">
                                <i class="fa-solid fa-check text-xs text-white hidden check-icon"></i>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2" id="count-modulos">0 módulos seleccionados</p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t dark:border-gray-700 flex justify-end gap-3">
                <button type="button" onclick="closeModal()"
                        class="px-4 py-2 rounded-xl border text-sm font-medium text-gray-600 dark:text-gray-300
                               hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition"
                        style="background:var(--ovni-orange)">
                    <i class="fa-solid fa-floppy-disk mr-1"></i>
                    <span id="btn-submit-text">Crear rol</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const ROLES_DATA = @json($roles->keyBy('id'));

function openModal(roleId = null) {
    const modal     = document.getElementById('modal-rol');
    const title     = document.getElementById('modal-title');
    const btnText   = document.getElementById('btn-submit-text');
    const form      = document.getElementById('form-rol');
    const method    = document.getElementById('form-method');
    const roleIdInp = document.getElementById('form-role-id');

    // Reset form
    document.getElementById('input-nombre').value      = '';
    document.getElementById('input-descripcion').value = '';
    document.querySelectorAll('.modulo-check').forEach(cb => {
        cb.checked = false;
        updateCardStyle(cb);
    });

    if (roleId) {
        const role = ROLES_DATA[roleId];
        if (!role) return;

        title.textContent   = 'Editar rol';
        btnText.textContent = 'Guardar cambios';
        form.action         = `/admin/roles/${roleId}`;
        method.value        = 'PUT';
        roleIdInp.value     = roleId;

        document.getElementById('input-nombre').value      = role.nombre;
        document.getElementById('input-descripcion').value = role.descripcion ?? '';

        // Marcar módulos
        const modulos = role.modulos ?? [];
        document.querySelectorAll('.modulo-check').forEach(cb => {
            cb.checked = modulos.includes(cb.value);
            updateCardStyle(cb);
        });
    } else {
        title.textContent   = 'Crear nuevo rol';
        btnText.textContent = 'Crear rol';
        form.action         = '{{ route('admin.roles.store') }}';
        method.value        = 'POST';
        roleIdInp.value     = '';
    }

    updateCount();
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal-rol').classList.add('hidden');
}

function updateCardStyle(checkbox) {
    const card      = checkbox.closest('.modulo-card');
    const checkIcon = card.querySelector('.modulo-check-icon');
    const icon      = card.querySelector('.check-icon');

    if (checkbox.checked) {
        card.style.borderColor      = 'var(--ovni-orange, #e8610a)';
        card.style.backgroundColor  = '#fff8f4';
        checkIcon.style.background  = '#e8610a';
        checkIcon.style.borderColor = '#e8610a';
        icon.classList.remove('hidden');
    } else {
        card.style.borderColor      = '';
        card.style.backgroundColor  = '';
        checkIcon.style.background  = '';
        checkIcon.style.borderColor = '';
        icon.classList.add('hidden');
    }

    updateCount();
}

function updateCount() {
    const count = document.querySelectorAll('.modulo-check:checked').length;
    const el    = document.getElementById('count-modulos');
    if (el) el.textContent = count + (count === 1 ? ' módulo seleccionado' : ' módulos seleccionados');
}

// Cerrar al hacer click fuera
document.getElementById('modal-rol').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endpush