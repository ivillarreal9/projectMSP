<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold">Dashboard de Ventas</h2>
    </x-slot>

    <div class="p-6">

        @if(auth()->user()->isVentas())

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @if(isset($sales) && count($sales) > 0)
                    @foreach($sales as $sale)
                        <div class="bg-white p-4 rounded shadow">
                            <h3 class="font-bold text-lg">
                                {{ $sale['name'] }}
                            </h3>

                            <p class="text-sm text-gray-500">
                                Total: ${{ $sale['amount_total'] }}
                            </p>

                            <p class="text-xs mt-2">
                                Estado: {{ $sale['state'] }}
                            </p>
                        </div>
                    @endforeach
                @else
                    <p>No hay datos de ventas</p>
                @endif
            </div>

        @else
            <p class="text-red-500">Acceso restringido</p>
        @endif

    </div>
</x-app-layout>