<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Detalle de Usuario</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Información y permisos del usuario.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex items-center gap-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium px-4 py-2.5 rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver
                </a>
                <a href="{{ route('admin.users.edit', $user) }}"
                   class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar Usuario
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if(session('success'))
                <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-xl text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Card perfil --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold shrink-0
                        {{ $user->role === 'admin' || $user->roleModel ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $user->name }}</h3>
                            {{-- ✅ Badge de rol dinámico --}}
                            @if($user->roleModel)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    {{ $user->roleModel->nombre }}
                                </span>
                            @elseif($user->role)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                    {{ ucfirst($user->role) }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400 border border-yellow-100 dark:border-yellow-800">
                                    Sin rol asignado
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $user->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Card info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">

                <div class="px-6 py-4">
                    <h4 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Información General</h4>
                    <div class="grid grid-cols-2 gap-6 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 block mb-1">Nombre completo</span>
                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ $user->name }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 block mb-1">Correo electrónico</span>
                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ $user->email }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 block mb-1">Estado</span>
                            @if($user->active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Inactivo
                                </span>
                            @endif
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 block mb-1">Registrado</span>
                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 block mb-1">Última conexión</span>
                            <p class="font-medium text-gray-800 dark:text-gray-200">
                                @if($user->last_login_at)
                                    {{ $user->last_login_at->diffForHumans() }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 italic">Sin conexión registrada</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 block mb-1">2FA</span>
                            @if($user->two_factor_confirmed)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-300 border border-green-100 dark:border-green-800">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Activado
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-600">
                                    No activado
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ✅ Módulos accesibles según rol dinámico --}}
                @if($user->roleModel && count($user->roleModel->modulos ?? []) > 0)
                <div class="px-6 py-4">
                    <h4 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Módulos con Acceso</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($user->roleModel->modulos as $modulo)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border border-green-100 dark:border-green-800">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ ucfirst(str_replace('_', ' ', $modulo)) }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @elseif(!$user->roleModel && !$user->role)
                <div class="px-6 py-4">
                    <div class="flex items-center gap-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-800 rounded-lg text-sm text-yellow-700 dark:text-yellow-400">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Este usuario no tiene un rol asignado. <a href="{{ route('admin.users.edit', $user) }}" class="underline font-medium ml-1">Asignar rol →</a>
                    </div>
                </div>
                @endif

            </div>

        </div>
    </div>
</x-app-layout>