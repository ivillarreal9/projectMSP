{{-- resources/views/admin/reports/msp/descarga_masiva.blade.php --}}
@extends('admin.reports.msp.layouts.app')
@section('title', 'Descarga Masiva de PDFs')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 fade-in">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white"
             style="background:#d4520a">
            <i class="fa-solid fa-file-zipper text-sm"></i>
        </div>
        <div>
            <h2 class="text-lg font-bold text-gray-800">Descarga Masiva de PDFs</h2>
            <p class="text-xs text-gray-500">Selecciona el período y los clientes para generar un ZIP</p>
        </div>
        <a href="{{ route('admin.msp.clientes') }}"
           class="ml-auto text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    {{-- Filtro de período --}}
    <div class="bg-white rounded-2xl border shadow-sm p-5">
        <label class="text-xs font-semibold text-gray-500 uppercase mb-2 block">Período</label>
        <div class="flex gap-3 flex-wrap">
            @foreach($periodos as $p)
            <a href="{{ route('admin.msp.pdf.masiva', ['periodo' => $p]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium border transition
                      {{ $periodo === $p ? 'text-white border-transparent' : 'text-gray-600 border-gray-200 hover:bg-gray-50' }}"
               style="{{ $periodo === $p ? 'background:#d4520a' : '' }}">
                {{ $p }}
            </a>
            @endforeach
        </div>
    </div>

    @if($clientes->isNotEmpty())
    <form action="{{ route('admin.msp.pdf.masiva.zip') }}" method="POST" id="descargaForm">
        @csrf
        <input type="hidden" name="periodo" value="{{ $periodo }}">

        {{-- Barra de acciones --}}
        <div class="bg-white rounded-2xl border shadow-sm p-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleTodos()"
                        class="text-xs font-medium text-gray-600 hover:text-gray-800 flex items-center gap-1 border px-3 py-1.5 rounded-lg hover:bg-gray-50">
                    <i class="fa-solid fa-check-double"></i>
                    <span id="toggleLabel">Seleccionar todos</span>
                </button>
                <span class="text-xs text-gray-400">
                    <span id="selectedCount">0</span> de {{ $clientes->count() }} seleccionados
                </span>
            </div>
            <button type="submit" id="downloadBtn" disabled
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg text-white text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed hover:opacity-90 transition"
                    style="background:#d4520a">
                <i class="fa-solid fa-file-zipper"></i>
                Descargar ZIP
            </button>
        </div>

        {{-- Lista de clientes --}}
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden mt-4">
            <div class="p-4 border-b">
                <input type="text" id="searchCliente" placeholder="Buscar cliente..."
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:outline-none"
                       style="focus:ring-color:#d4520a"
                       oninput="filtrarClientes(this.value)">
            </div>

            <div class="divide-y" id="clientesList">
                @foreach($clientes as $cliente)
                <label class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 cursor-pointer transition cliente-row">
                    <input type="checkbox" name="clientes[]" value="{{ $cliente->customer_name }}"
                           class="cliente-check w-4 h-4 rounded accent-orange-600"
                           onchange="updateCount()">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background:#1a1a2e">
                        {{ strtoupper(substr($cliente->customer_name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-800 text-sm truncate">{{ $cliente->customer_name }}</div>
                        @if($cliente->email_cliente)
                        <div class="text-xs text-gray-400">{{ $cliente->email_cliente }}</div>
                        @endif
                    </div>
                    @if($cliente->numero_cuenta)
                    <div class="text-xs text-gray-400 flex-shrink-0">{{ $cliente->numero_cuenta }}</div>
                    @endif
                </label>
                @endforeach
            </div>
        </div>
    </form>

    @else
    <div class="bg-white rounded-2xl border shadow-sm p-12 text-center text-gray-400">
        <i class="fa-solid fa-users-slash text-5xl mb-4"></i>
        <p class="font-medium">No hay clientes para este período</p>
        <p class="text-sm mt-1">Selecciona otro período o importa un archivo Excel</p>
    </div>
    @endif

</div>

{{-- Modal de progreso --}}
<div id="progressModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4 text-center">
        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
             style="background:#fff3ed">
            <i class="fa-solid fa-spinner fa-spin text-2xl" style="color:#d4520a"></i>
        </div>
        <h3 class="font-bold text-gray-800 mb-1">Generando PDFs...</h3>
        <p class="text-sm text-gray-500">Esto puede tardar unos segundos dependiendo de la cantidad de clientes seleccionados.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateCount() {
    const checks  = document.querySelectorAll('.cliente-check:checked');
    const total   = checks.length;
    document.getElementById('selectedCount').textContent = total;
    document.getElementById('downloadBtn').disabled = total === 0;

    const allChecks = document.querySelectorAll('.cliente-check');
    document.getElementById('toggleLabel').textContent =
        total === allChecks.length ? 'Deseleccionar todos' : 'Seleccionar todos';
}

function toggleTodos() {
    const checks  = document.querySelectorAll('.cliente-check:not([style*="display:none"])');
    const checked = document.querySelectorAll('.cliente-check:checked').length;
    const total   = checks.length;

    checks.forEach(c => c.checked = checked < total);
    updateCount();
}

function filtrarClientes(q) {
    const rows = document.querySelectorAll('.cliente-row');
    rows.forEach(row => {
        const nombre = row.querySelector('.font-medium').textContent.toLowerCase();
        row.style.display = nombre.includes(q.toLowerCase()) ? '' : 'none';
    });
}

document.getElementById('descargaForm')?.addEventListener('submit', function() {
    document.getElementById('progressModal').classList.remove('hidden');
    document.getElementById('downloadBtn').disabled = true;
    document.getElementById('downloadBtn').innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i> Generando...';
});
</script>
@endpush