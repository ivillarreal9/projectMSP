{{-- resources/views/admin/reports/msp/clientes.blade.php --}}
@extends('admin.reports.msp.layouts.app')
@section('title', 'Clientes MSP')

@section('content')
<div class="space-y-6 fade-in">

    {{-- Filtros --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
        <form method="GET" action="{{ route('admin.msp.clientes') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Período</label>
                <select name="periodo" onchange="this.form.submit()"
                        class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">— Todos —</option>
                    @foreach($periodos as $p)
                        <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Buscar cliente</label>
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nombre del cliente..."
                           class="w-full border rounded-lg pl-9 pr-4 py-2 text-sm focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
            <button type="submit" class="px-5 py-2 text-white rounded-lg text-sm font-medium" style="background:var(--ovni-orange)">
                <i class="fa-solid fa-filter mr-1"></i> Filtrar
            </button>
            {{-- ← AGREGAR ESTO --}}
            @if($periodo)
            <a href="{{ route('admin.msp.pdf.masiva', ['periodo' => $periodo]) }}"
               class="px-5 py-2 text-white rounded-lg text-sm font-medium flex items-center gap-2 hover:opacity-90 transition"
               style="background:#1a1a2e">
                <i class="fa-solid fa-file-zipper"></i> Descarga Masiva PDF
            </a>
            @endif
            <div class="text-xs text-gray-400 self-center">
                {{ $clientes->total() }} clientes
            </div>
        </form>
    </div>

    {{-- Grid app launcher --}}
    <div style="display:grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap:12px;">
        @forelse($clientes as $cliente)
        @php
            $colors = [
                ['bg' => '#FFF0E8', 'text' => '#d4520a'],
                ['bg' => '#E8F4FF', 'text' => '#1d6fb8'],
                ['bg' => '#E8FFF0', 'text' => '#0f8a3c'],
                ['bg' => '#F5E8FF', 'text' => '#7c3aed'],
                ['bg' => '#FFF8E8', 'text' => '#b45309'],
                ['bg' => '#E8FFFC', 'text' => '#0f766e'],
                ['bg' => '#FFE8F0', 'text' => '#be185d'],
                ['bg' => '#F0F8FF', 'text' => '#1e40af'],
                ['bg' => '#F0FFF4', 'text' => '#166534'],
                ['bg' => '#FEF3C7', 'text' => '#92400e'],
            ];
            $colorIdx = abs(crc32($cliente->customer_name)) % count($colors);
            $color    = $colors[$colorIdx];
            $initials = strtoupper(substr($cliente->customer_name, 0, 2));
        @endphp

        <a href="{{ route('admin.msp.clientes.detalle', urlencode($cliente->customer_name)) }}?periodo={{ $periodo }}"
           class="group flex flex-col items-center gap-2 p-3 rounded-2xl
                  hover:bg-white dark:hover:bg-gray-700 hover:shadow-md
                  transition-all duration-200 cursor-pointer text-center">

            {{-- Ícono cuadrado con logo o iniciales --}}
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm overflow-hidden
                        group-hover:scale-105 transition-transform duration-200"
                 style="background: {{ $color['bg'] }}">
                @if($cliente->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($cliente->logo_path))
                    <img src="{{ Storage::url($cliente->logo_path) }}"
                         alt="{{ $cliente->customer_name }}"
                         class="w-10 h-10 object-contain">
                @else
                    <span class="text-lg font-black leading-none" style="color: {{ $color['text'] }}">
                        {{ $initials }}
                    </span>
                @endif
            </div>

            {{-- Nombre del cliente --}}
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 leading-tight w-full"
                  style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word;">
                {{ $cliente->customer_name }}
            </span>
        </a>

        @empty
        <div class="col-span-10 text-center py-20 text-gray-400">
            <i class="fa-solid fa-users-slash text-5xl mb-4 opacity-50"></i>
            <p class="text-lg font-semibold">No se encontraron clientes</p>
            <p class="text-sm mt-1">Importa un archivo Excel o cambia los filtros</p>
        </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    @if($clientes->hasPages())
    <div class="flex justify-center">
        {{ $clientes->links() }}
    </div>
    @endif

</div>
@endsection