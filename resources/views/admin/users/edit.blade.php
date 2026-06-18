<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Editar Usuario</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Modifica los datos de <span class="font-medium text-gray-700 dark:text-gray-300">{{ $user->name }}</span>.</p>
            </div>
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center gap-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium px-4 py-2.5 rounded-lg transition shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if(session('success'))
            <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 rounded-xl text-sm">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
            @endif

            {{-- Info del usuario --}}
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

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl font-bold shrink-0 {{ $avatarColor }}">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-base font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $user->name }}</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 truncate">{{ $user->email }}</p>
                </div>
                <div class="flex flex-col items-end gap-1.5 shrink-0">
                    @if($user->roleModel)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        {{ $user->roleModel->nombre }}
                    </span>
                    @endif
                    @if($has2fa)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        2FA activo
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                        Sin 2FA
                    </span>
                    @endif
                </div>
            </div>

            {{-- ── Datos generales ── --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Datos generales</h3>
                </div>

                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="p-6 space-y-5">
                    @csrf @method('PUT')

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Nombre completo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               placeholder="Ej. María García"
                               class="w-full px-3.5 py-2.5 text-sm border rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition
                               {{ $errors->has('name') ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-gray-600' }}">
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Correo electrónico <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="w-full px-3.5 py-2.5 text-sm border rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition
                               {{ $errors->has('email') ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-gray-600' }}">
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Rol --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Rol <span class="text-red-500">*</span>
                            </label>
                            <a href="{{ route('admin.roles.index') }}"
                               class="text-xs text-orange-500 hover:text-orange-600 font-medium flex items-center gap-1 transition">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Gestionar roles
                            </a>
                        </div>
                        <select name="role_id"
                                class="w-full px-3.5 py-2.5 text-sm border rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition
                                {{ $errors->has('role_id') ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-gray-600' }}">
                            <option value="">— Selecciona un rol —</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                {{ $role->nombre }}
                            </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="pt-2 flex justify-end border-t border-gray-100 dark:border-gray-700">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>

            {{-- ── Cambiar contraseña ── --}}
            <div x-data="{ showPass: false, showPassConf: false }"
                 class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Cambiar contraseña</h3>
                </div>

                <form action="{{ route('admin.users.password', $user) }}" method="POST" class="p-6 space-y-5">
                    @csrf @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Nueva contraseña <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" name="password"
                                   placeholder="Mínimo 8 caracteres"
                                   class="w-full px-3.5 py-2.5 pr-10 text-sm border rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition
                                   {{ $errors->has('password') ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-gray-600' }}">
                            <button type="button" @click="showPass = !showPass"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                                <svg x-show="!showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Confirmar contraseña <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassConf ? 'text' : 'password'" name="password_confirmation"
                                   placeholder="Repite la contraseña"
                                   class="w-full px-3.5 py-2.5 pr-10 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                            <button type="button" @click="showPassConf = !showPassConf"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                                <svg x-show="!showPassConf" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassConf" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end border-t border-gray-100 dark:border-gray-700">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-700 hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 text-white text-sm font-medium rounded-lg transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Cambiar contraseña
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
