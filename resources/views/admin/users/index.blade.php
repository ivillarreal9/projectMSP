<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Gestión de Usuarios</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Administra los accesos y roles de la plataforma.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.roles.index') }}"
                   class="inline-flex items-center gap-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium px-4 py-2.5 rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Roles
                </a>
                <a href="{{ route('admin.users.create') }}"
                   class="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo Usuario
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        search: '{{ request('search') }}',
        roleFilter: '{{ request('role_id') }}',
    }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Alertas --}}
            @if(session('success'))
            <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-xl text-sm">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-xl text-sm">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ session('error') }}
            </div>
            @endif

            {{-- Stats strip --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                {{-- Total --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['total'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total usuarios</p>
                    </div>
                </div>
                {{-- Con 2FA --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['con_2fa'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Con 2FA</p>
                    </div>
                </div>
                {{-- Sin 2FA --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['sin_2fa'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sin 2FA</p>
                    </div>
                </div>
                {{-- Verificados --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats['verificados'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Email verificado</p>
                    </div>
                </div>
            </div>

            {{-- Card principal --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">

                {{-- Filtros --}}
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <form method="GET" action="{{ route('admin.users.index') }}"
                          class="flex flex-col sm:flex-row gap-3">

                        <div class="relative flex-1">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z"/>
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Buscar por nombre o email..."
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400">
                        </div>

                        <select name="role_id"
                                class="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-400">
                            <option value="">Todos los roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->nombre }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                            </svg>
                            Filtrar
                        </button>

                        @if(request('search') || request('role_id'))
                        <a href="{{ route('admin.users.index') }}"
                           class="inline-flex items-center justify-center gap-1.5 px-4 py-2 border border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Limpiar
                        </a>
                        @endif
                    </form>
                </div>

                {{-- Tabla --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-700/30">
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rol</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Seguridad</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Miembro desde</th>
                                <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @forelse($users as $user)
                            @php
                                $avatarColors = [
                                    'bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-300',
                                    'bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300',
                                    'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300',
                                    'bg-violet-100 dark:bg-violet-500/20 text-violet-600 dark:text-violet-300',
                                    'bg-pink-100 dark:bg-pink-500/20 text-pink-600 dark:text-pink-300',
                                    'bg-cyan-100 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-300',
                                    'bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300',
                                ];
                                $avatarColor = $avatarColors[$user->id % count($avatarColors)];
                                $has2fa = $user->two_factor_secret && $user->two_factor_confirmed;
                            @endphp
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/20 transition group">

                                {{-- Usuario --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold shrink-0 {{ $avatarColor }}">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-tight">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Rol --}}
                                <td class="px-6 py-4">
                                    @if($user->roleModel)
                                        @php
                                            $slug = strtolower($user->roleModel->slug ?? '');
                                            $roleStyle = match(true) {
                                                str_contains($slug, 'admin') => 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-500/30',
                                                str_contains($slug, 'editor') => 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-500/30',
                                                str_contains($slug, 'venta') => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30',
                                                default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border {{ $roleStyle }}">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                            {{ $user->roleModel->nombre }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500 italic">Sin rol</span>
                                    @endif
                                </td>

                                {{-- 2FA --}}
                                <td class="px-6 py-4">
                                    @if($has2fa)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            2FA activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                            </svg>
                                            Sin 2FA
                                        </span>
                                    @endif
                                </td>

                                {{-- Miembro desde --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $user->created_at->format('d/m/Y') }}
                                    </div>
                                </td>

                                {{-- Acciones --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.users.show', $user) }}"
                                           title="Ver detalle"
                                           class="p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           title="Editar"
                                           class="p-2 rounded-lg text-gray-400 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-500/10 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar a {{ addslashes($user->name) }}? Esta acción no se puede deshacer.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Eliminar"
                                                    class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                        @else
                                        <div class="p-2 w-8"></div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-3 text-gray-400 dark:text-gray-500">
                                        <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">No se encontraron usuarios</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Intenta ajustar los filtros o crea un nuevo usuario.</p>
                                        </div>
                                        <a href="{{ route('admin.users.create') }}"
                                           class="inline-flex items-center gap-2 mt-1 text-sm text-orange-500 hover:text-orange-600 font-medium transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Crear nuevo usuario
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer: conteo + paginación --}}
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        @if($users->total() > 0)
                            Mostrando <span class="font-medium text-gray-700 dark:text-gray-300">{{ $users->firstItem() }}–{{ $users->lastItem() }}</span>
                            de <span class="font-medium text-gray-700 dark:text-gray-300">{{ $users->total() }}</span> usuarios
                        @else
                            Sin resultados
                        @endif
                    </p>
                    @if($users->hasPages())
                        {{ $users->links() }}
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
