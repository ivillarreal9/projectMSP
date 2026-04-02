{{-- resources/views/admin/reports/msp/index.blade.php --}}
@extends('admin.reports.msp.layouts.app')
@section('title', 'MSP Reports — Reportes Masivos')

@section('content')
<div class="space-y-6 fade-in">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Reportes Masivos</h2>
            <p class="text-sm text-gray-500 mt-0.5">Importa archivos Excel desde SharePoint para generar reportes</p>
        </div>
        <button onclick="openCredentialsModal()"
                class="flex items-center gap-2 px-4 py-2 border rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <i class="fa-solid fa-gear"></i> Credenciales
        </button>
    </div>

    {{-- Aviso si no hay credenciales --}}
    @if(!$hasCredentials)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-start gap-3">
        <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5"></i>
        <div>
            <div class="font-semibold text-amber-800">Credenciales no configuradas</div>
            <div class="text-sm text-amber-700 mt-1">
                Configura las credenciales de SharePoint y SendGrid para comenzar.
                <button onclick="openCredentialsModal()" class="underline font-medium">Configurar ahora</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Panel SharePoint — carga lazy con botón --}}
    @if($hasCredentials)
    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="p-5 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                <i class="fa-brands fa-microsoft text-blue-600"></i>
                Archivos en SharePoint
                <span id="fileBadge" class="hidden text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full"></span>
            </h3>
            <button onclick="loadSharePointFiles()" id="loadBtn"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-white text-sm font-medium hover:opacity-90 transition active:scale-95"
                    style="background:#0078d4">
                <i class="fa-solid fa-cloud" id="loadIcon"></i>
                <span id="loadText">Consultar archivos</span>
            </button>
        </div>

        {{-- Estado inicial --}}
        <div id="spEmpty" class="px-5 pb-5">
            <div class="border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-xl p-8 text-center text-gray-400">
                <i class="fa-brands fa-microsoft text-3xl mb-2 opacity-40"></i>
                <p class="text-sm">Haz clic en <strong>Consultar archivos</strong> para ver los archivos disponibles en SharePoint</p>
            </div>
        </div>

        {{-- Lista de archivos (se llena con JS) --}}
        <div id="spFilesList" class="hidden divide-y dark:divide-gray-700"></div>

        {{-- Error --}}
        <div id="spError" class="hidden px-5 pb-5">
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                <span id="spErrorMsg"></span>
            </div>
        </div>
    </div>
    @endif

    {{-- Historial de importaciones --}}
    @if($batches->count())
    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-6">
        <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-gray-400"></i> Historial de importaciones
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide border-b dark:border-gray-600">
                        <th class="pb-3 pr-4">Archivo</th>
                        <th class="pb-3 pr-4">Período</th>
                        <th class="pb-3 pr-4">Registros</th>
                        <th class="pb-3 pr-4">Clientes</th>
                        <th class="pb-3">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @foreach($batches as $batch)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="py-3 pr-4 font-medium text-gray-700 dark:text-gray-300">
                            <i class="fa-solid fa-file-excel text-green-600 mr-1"></i>
                            {{ $batch->filename }}
                        </td>
                        <td class="py-3 pr-4">
                            <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-medium">
                                {{ $batch->periodo }}
                            </span>
                        </td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-400">{{ number_format($batch->total_registros) }}</td>
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-400">{{ number_format($batch->clientes_unicos) }}</td>
                        <td class="py-3 text-gray-400 text-xs">{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

{{-- Modal Importar --}}
<div id="importModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                <i class="fa-solid fa-cloud-arrow-down text-blue-600"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 dark:text-white">Importar archivo</h3>
                <p id="modalFilename" class="text-xs text-gray-500"></p>
            </div>
        </div>
        <form action="{{ route('admin.msp.sharepoint.import') }}" method="POST" id="importForm">
            @csrf
            <input type="hidden" name="filename" id="modalFilenameInput">
            <input type="hidden" name="item_id" id="modalItemIdInput">
            <div class="mb-4">
                <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Período del reporte</label>
                <input type="text" name="periodo" placeholder="ej: Febrero 2026"
                       class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-xs text-blue-700">
                <i class="fa-solid fa-info-circle mr-1"></i>
                Se descargará el archivo de SharePoint e importarán todos los registros.
            </div>
            <div class="flex gap-3">
                <button type="submit" id="importSubmitBtn"
                        class="flex-1 text-white py-2.5 rounded-lg text-sm font-medium flex items-center justify-center gap-2"
                        style="background:#0078d4">
                    <i class="fa-solid fa-cloud-arrow-down"></i> Importar
                </button>
                <button type="button" onclick="closeModal('importModal')"
                        class="flex-1 border py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Credenciales --}}
<div id="credentialsModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white"
                     style="background:#1a1a2e">
                    <i class="fa-solid fa-gear text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 dark:text-white">Credenciales MSP</h3>
                    <p class="text-xs text-gray-500">SharePoint & SendGrid</p>
                </div>
            </div>
            <button onclick="closeModal('credentialsModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('admin.msp.settings.save') }}" method="POST">
            @csrf
            <div class="p-6 space-y-6">

                {{-- Azure / SharePoint --}}
                <div>
                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                        <i class="fa-brands fa-microsoft text-blue-600"></i> Azure / SharePoint
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Tenant ID</label>
                            <input type="text" name="azure_tenant_id" value="{{ $settings['azure_tenant_id'] ?? '' }}"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                   class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Client ID</label>
                            <input type="text" name="azure_client_id" value="{{ $settings['azure_client_id'] ?? '' }}"
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                   class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Client Secret</label>
                            <div class="relative">
                                <input type="password" name="azure_client_secret" id="f_secret"
                                       placeholder="Dejar en blanco para no cambiar"
                                       class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <button type="button" onclick="togglePass('f_secret')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Folder ID</label>
                            <input type="text" name="sharepoint_folder_id" value="{{ $settings['sharepoint_folder_id'] ?? '' }}"
                                   placeholder="0162CR6X..."
                                   class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Site URL</label>
                            <input type="url" name="sharepoint_site_url" value="{{ $settings['sharepoint_site_url'] ?? '' }}"
                                   placeholder="https://empresa.sharepoint.com/sites/Nombre"
                                   class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Archivo por defecto</label>
                            <input type="text" name="sharepoint_default_file" value="{{ $settings['sharepoint_default_file'] ?? '' }}"
                                   placeholder="MSP_REPORT.xlsx"
                                   class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                </div>

                {{-- SendGrid --}}
                <div class="border-t dark:border-gray-600 pt-5">
                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-envelope text-orange-500"></i> SendGrid
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">API Key</label>
                            <div class="relative">
                                <input type="password" name="sendgrid_api_key" id="f_sgkey"
                                       placeholder="Dejar en blanco para no cambiar"
                                       class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <button type="button" onclick="togglePass('f_sgkey')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">From Email</label>
                            <input type="email" name="sendgrid_from_email" value="{{ $settings['sendgrid_from_email'] ?? '' }}"
                                   placeholder="reportes@empresa.com"
                                   class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t dark:border-gray-700 flex gap-3">
                <button type="submit"
                        class="flex-1 text-white py-2.5 rounded-lg text-sm font-medium hover:opacity-90"
                        style="background:#d4520a">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Guardar credenciales
                </button>
                <button type="button" onclick="closeModal('credentialsModal')"
                        class="px-6 border py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── SharePoint lazy load ──────────────────────────────────────────────────────
function loadSharePointFiles() {
    const btn    = document.getElementById('loadBtn');
    const icon   = document.getElementById('loadIcon');
    const text   = document.getElementById('loadText');
    const list   = document.getElementById('spFilesList');
    const empty  = document.getElementById('spEmpty');
    const errDiv = document.getElementById('spError');
    const badge  = document.getElementById('fileBadge');

    btn.disabled   = true;
    icon.className = 'fa-solid fa-spinner fa-spin';
    text.textContent = 'Cargando...';

    fetch('{{ route("admin.msp.sharepoint") }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        empty.classList.add('hidden');
        errDiv.classList.add('hidden');

        if (data.error) {
            document.getElementById('spErrorMsg').textContent = data.error;
            errDiv.classList.remove('hidden');
        } else if (data.files && data.files.length > 0) {
            badge.textContent = data.files.length + ' archivos';
            badge.classList.remove('hidden');
            list.innerHTML = data.files.map(f => `
                <div class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-file-excel text-green-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm text-gray-800 dark:text-white">${f.name}</div>
                        <div class="text-xs text-gray-400">${f.size} — Modificado: ${new Date(f.modified).toLocaleDateString('es-PA')}</div>
                    </div>
                    <button onclick="openImportModal('${f.name}', '${f.item_id}')"
                            class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-lg text-white text-xs font-medium hover:opacity-90"
                            style="background:#0078d4">
                        <i class="fa-solid fa-cloud-arrow-down"></i> Importar
                    </button>
                </div>
            `).join('');
            list.classList.remove('hidden');
        } else {
            list.innerHTML = '<div class="p-8 text-center text-gray-400 text-sm">No se encontraron archivos Excel</div>';
            list.classList.remove('hidden');
        }

        icon.className  = 'fa-solid fa-rotate-right';
        text.textContent = 'Actualizar';
        btn.disabled    = false;
    })
    .catch(() => {
        empty.classList.add('hidden');
        document.getElementById('spErrorMsg').textContent = 'Error de conexión con SharePoint';
        errDiv.classList.remove('hidden');
        icon.className  = 'fa-solid fa-cloud';
        text.textContent = 'Reintentar';
        btn.disabled    = false;
    });
}

// ── Modales ───────────────────────────────────────────────────────────────────
function openImportModal(filename, itemId) {
    document.getElementById('modalFilename').textContent = filename;
    document.getElementById('modalFilenameInput').value  = filename;
    document.getElementById('modalItemIdInput').value    = itemId;
    document.getElementById('importModal').classList.remove('hidden');
}

function openCredentialsModal() {
    document.getElementById('credentialsModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function togglePass(id) {
    const el = document.getElementById(id);
    el.type  = el.type === 'password' ? 'text' : 'password';
}

// ── Submit importar ───────────────────────────────────────────────────────────
document.getElementById('importForm').addEventListener('submit', function() {
    const btn = document.getElementById('importSubmitBtn');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Importando...';
    btn.disabled  = true;
});
</script>
@endpush