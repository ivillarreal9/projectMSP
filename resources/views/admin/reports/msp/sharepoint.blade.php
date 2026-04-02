{{-- resources/views/admin/reports/msp/sharepoint.blade.php --}}
@extends('admin.reports.msp.layouts.app')
@section('title', 'Importar desde SharePoint')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 fade-in">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white"
             style="background:#0078d4">
            <i class="fa-brands fa-microsoft text-sm"></i>
        </div>
        <div>
            <h2 class="text-lg font-bold text-gray-800">Importar desde SharePoint</h2>
            <p class="text-xs text-gray-500">{{ config('services.sharepoint.site_url') }}</p>
        </div>
        <a href="{{ route('admin.msp.index') }}"
           class="ml-auto text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    {{-- Error de conexión --}}
    @if($error)
    <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation text-red-500 mt-0.5"></i>
            <div>
                <div class="font-semibold text-red-700 mb-1">Error de conexión con SharePoint</div>
                <div class="text-sm text-red-600 font-mono">{{ $error }}</div>
                <div class="mt-3 text-xs text-red-500">
                    Verifica que las credenciales en <strong>.env</strong> sean correctas y que la app de Azure tenga permisos <strong>Sites.Read.All</strong> y <strong>Files.Read.All</strong>.
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Lista de archivos --}}
    @if(count($files))
    <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
        <div class="p-5 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                <i class="fa-solid fa-folder-open text-yellow-500"></i>
                Archivos Excel en SharePoint
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ count($files) }} archivos</span>
            </h3>
            <button onclick="document.getElementById('refreshBtn').click()"
                    class="text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1">
                <i class="fa-solid fa-rotate-right"></i> Actualizar
            </button>
        </div>

        <div class="divide-y">
            @foreach($files as $file)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition">
                <div class="w-9 h-9 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-file-excel text-green-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-gray-800 text-sm truncate">{{ $file['name'] }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">
                        {{ $file['size'] }} —
                        Modificado: {{ \Carbon\Carbon::parse($file['modified'])->format('d/m/Y H:i') }}
                    </div>
                </div>
                <button onclick="openImportModal('{{ $file['name'] }}')"
                        class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-lg text-white text-xs font-medium hover:opacity-90"
                        style="background:#0078d4">
                    <i class="fa-solid fa-cloud-arrow-down"></i> Importar
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @elseif(!$error)
    <div class="bg-white rounded-2xl border shadow-sm p-12 text-center text-gray-400">
        <i class="fa-solid fa-folder-open text-5xl mb-4"></i>
        <p class="font-medium">No se encontraron archivos Excel</p>
        <p class="text-sm mt-1">en la carpeta <strong>{{ config('services.sharepoint.folder') }}</strong></p>
    </div>
    @endif

</div>

{{-- Modal de importación --}}
<div id="importModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                <i class="fa-solid fa-cloud-arrow-down text-blue-600"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800">Importar archivo</h3>
                <p id="modalFilename" class="text-xs text-gray-500"></p>
            </div>
        </div>

        <form action="{{ route('admin.msp.sharepoint.import') }}" method="POST" id="importForm">
            @csrf
            <input type="hidden" name="filename" id="modalFilenameInput">

            <div class="mb-4">
                <label class="text-xs font-semibold text-gray-500 uppercase mb-1 block">Período del reporte</label>
                <input type="text" name="periodo" placeholder="ej: Febrero 2026"
                       class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500" required>
                <p class="text-xs text-gray-400 mt-1">Escribe el mes y año que corresponde a este archivo</p>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-xs text-amber-700">
                <i class="fa-solid fa-info-circle mr-1"></i>
                Se descargará el archivo de SharePoint y se importarán todos los registros a la base de datos.
            </div>

            <div class="flex gap-3">
                <button type="submit" id="importSubmitBtn"
                        class="flex-1 text-white py-2.5 rounded-lg text-sm font-medium flex items-center justify-center gap-2"
                        style="background:#0078d4">
                    <i class="fa-solid fa-cloud-arrow-down"></i> Importar
                </button>
                <button type="button" onclick="closeModal()"
                        class="flex-1 border py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openImportModal(filename) {
    document.getElementById('modalFilename').textContent = filename;
    document.getElementById('modalFilenameInput').value  = filename;
    document.getElementById('importModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('importModal').classList.add('hidden');
}

document.getElementById('importForm').addEventListener('submit', function() {
    const btn = document.getElementById('importSubmitBtn');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Importando...';
    btn.disabled = true;
});
</script>
@endpush