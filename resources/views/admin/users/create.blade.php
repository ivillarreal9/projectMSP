<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Nuevo Usuario</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Completa los datos para crear una cuenta en la plataforma.</p>
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
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div x-data="{
                    name: '{{ old('name') }}',
                    showPass: false,
                    showPassConf: false,
                    get initials() {
                        return this.name.trim().substring(0, 2).toUpperCase() || 'NU';
                    }
                }"
                 class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

                {{-- Header de la card --}}
                <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-orange-100 dark:bg-orange-500/20 flex items-center justify-center text-orange-600 dark:text-orange-300 text-xl font-bold shrink-0"
                         x-text="initials"></div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                            x-text="name || 'Nombre del usuario'"></h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Vista previa del avatar</p>
                    </div>
                </div>

                <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-5">
                    @csrf

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Nombre completo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="name" value="{{ old('name') }}"
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
                        <input type="email" name="email" value="{{ old('email') }}"
                               placeholder="usuario@ovni.com"
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
                                Crear nuevo rol
                            </a>
                        </div>
                        <select name="role_id"
                                class="w-full px-3.5 py-2.5 text-sm border rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition
                                {{ $errors->has('role_id') ? 'border-red-400 dark:border-red-500' : 'border-gray-200 dark:border-gray-600' }}">
                            <option value="">— Selecciona un rol —</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
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

                    {{-- Divider --}}
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-1"></div>

                    {{-- Contraseña --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Contraseña <span class="text-red-500">*</span>
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

                    {{-- Confirmar contraseña --}}
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

                    {{-- Footer botones --}}
                    <div class="pt-2 flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('admin.users.index') }}"
                           class="px-5 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
