<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalle de Usuario</h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto px-4">
        <div class="bg-white shadow rounded-lg p-6 space-y-4">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-gray-800">{{ $user->name }}</h3>
                    <p class="text-gray-500 text-sm">{{ $user->email }}</p>
                </div>
            </div>

            <div class="border-t pt-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Rol</span>
                    <p class="font-medium text-gray-800">{{ ucfirst($user->role) }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Registrado</span>
                    <p class="font-medium text-gray-800">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <a href="{{ route('admin.users.index') }}"
                   class="text-gray-600 hover:underline text-sm">← Volver</a>
                <a href="{{ route('admin.users.edit', $user) }}"
                   class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm">
                    Editar Usuario
                </a>
            </div>
        </div>
    </div>
</x-app-layout>