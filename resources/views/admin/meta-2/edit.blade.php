<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Editar Registro META 2</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Actualiza los datos del registro.</p>
            </div>
            <a href="{{ route('admin.meta-2.index') }}"
               class="text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium">
                ← Volver
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Alertas de errores --}}
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded-xl text-sm">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="font-semibold mb-2">Por favor revisa los siguiente errores:</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">

                <form action="{{ route('admin.meta-2.update', $meta2) }}" method="POST" class="p-6 sm:p-8">
                    @csrf
                    @method('PUT')

                    {{-- Nombre --}}
                    <div class="mb-6">
                        <label for="nombre" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $meta2->nombre) }}"
                               class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition @error('nombre') border-red-500 focus:ring-red-500 @enderror"
                               placeholder="Ingrese el nombre del registro">
                        @error('nombre')
                            <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18.101 12.93a1 1 0 00-1.414-1.414L9 16.586 6.313 13.9a1 1 0 00-1.414 1.413l3.414 3.414a1 1 0 001.414 0l9.9-9.9z" clip-rule="evenodd"/></path></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Descripción --}}
                    <div class="mb-6">
                        <label for="descripcion" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Descripción
                        </label>
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition @error('descripcion') border-red-500 focus:ring-red-500 @enderror"
                                  placeholder="Agregue una descripción (opcional)">{{ old('descripcion', $meta2->descripcion) }}</textarea>
                        @error('descripcion')
                            <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18.101 12.93a1 1 0 00-1.414-1.414L9 16.586 6.313 13.9a1 1 0 00-1.414 1.413l3.414 3.414a1 1 0 001.414 0l9.9-9.9z" clip-rule="evenodd"/></path></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Valor --}}
                    <div class="mb-6">
                        <label for="valor" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Valor <span class="text-red-500">*</span>
                        </label>
                        <textarea id="valor" name="valor" rows="4"
                                  class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition font-mono @error('valor') border-red-500 focus:ring-red-500 @enderror"
                                  placeholder="Ingrese el valor del registro">{{ old('valor', $meta2->valor) }}</textarea>
                        @error('valor')
                            <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18.101 12.93a1 1 0 00-1.414-1.414L9 16.586 6.313 13.9a1 1 0 00-1.414 1.413l3.414 3.414a1 1 0 001.414 0l9.9-9.9z" clip-rule="evenodd"/></path></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Estado --}}
                    <div class="mb-8">
                        <label for="estado" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center p-3 border-2 border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer transition hover:border-indigo-500 @error('estado') border-red-500 @enderror"
                                   :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30': $wire.estado === 'activo' }">
                                <input type="radio" name="estado" value="activo" {{ old('estado', $meta2->estado) === 'activo' ? 'checked' : '' }}
                                       class="w-4 h-4 text-indigo-600">
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Activo</span>
                            </label>
                            <label class="flex items-center p-3 border-2 border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer transition hover:border-indigo-500 @error('estado') border-red-500 @enderror"
                                   :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30': $wire.estado === 'inactivo' }">
                                <input type="radio" name="estado" value="inactivo" {{ old('estado', $meta2->estado) === 'inactivo' ? 'checked' : '' }}
                                       class="w-4 h-4 text-indigo-600">
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Inactivo</span>
                            </label>
                        </div>
                        @error('estado')
                            <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18.101 12.93a1 1 0 00-1.414-1.414L9 16.586 6.313 13.9a1 1 0 00-1.414 1.413l3.414 3.414a1 1 0 001.414 0l9.9-9.9z" clip-rule="evenodd"/></path></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Botones de acción --}}
                    <div class="flex gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Actualizar Registro
                        </button>
                        <a href="{{ route('admin.meta-2.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-semibold rounded-lg transition">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
