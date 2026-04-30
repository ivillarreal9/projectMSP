<x-app-layout>
    <x-slot name="header">
        <div>
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                <a href="{{ route('admin.glpi.index') }}" class="hover:text-orange-500 transition">GLPI</a>
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <a href="{{ route('admin.glpi.items', $itemtype) }}" class="hover:text-orange-500 transition">{{ $label }}</a>
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-700 dark:text-gray-300 font-medium">Editar</span>
            </nav>
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                Editar — {{ $item['name'] ?? '#' . $item['id'] }}
            </h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl text-sm text-red-700 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Editar datos del activo</h3>
                </div>

                <form method="POST" action="{{ route('admin.glpi.update', [$itemtype, $item['id']]) }}" class="px-6 py-6 space-y-5">
                    @csrf
                    @method('PUT')

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $item['name'] ?? '') }}" required
                               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-400 transition @error('name') border-red-400 @enderror"/>
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Serial --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                            Número de serie
                        </label>
                        <input type="text" name="serial" value="{{ old('serial', $item['serial'] ?? '') }}"
                               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-400 transition"/>
                    </div>

                    {{-- Inventario --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                            Número de inventario
                        </label>
                        <input type="text" name="otherserial" value="{{ old('otherserial', $item['otherserial'] ?? '') }}"
                               class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-400 transition"/>
                    </div>

                    {{-- Entidad --}}
                    @if(!empty($entities))
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                            Entidad
                        </label>
                        <select name="entities_id"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-400 transition">
                            <option value="">— Seleccionar —</option>
                            @foreach($entities as $entity)
                                <option value="{{ $entity['id'] }}"
                                    {{ old('entities_id', $item['entities_id'] ?? '') == $entity['id'] ? 'selected' : '' }}>
                                    {{ $entity['name'] ?? $entity['completename'] ?? $entity['id'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Comentario --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1.5">
                            Comentario
                        </label>
                        <textarea name="comment" rows="3"
                                  class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-400 transition resize-none">{{ old('comment', $item['comment'] ?? '') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('admin.glpi.show', [$itemtype, $item['id']]) }}"
                           class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                            ← Ver detalle
                        </a>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.glpi.items', $itemtype) }}"
                               class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">
                                Cancelar
                            </a>
                            <button type="submit"
                                    class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                                Guardar cambios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
