<div class="flex items-center justify-between w-full">
    <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-xl p-1 w-fit">
        <a href="{{ route('admin.sincronizar.index') }}"
           class="px-4 py-1.5 text-sm font-medium rounded-lg transition
                  {{ request()->routeIs('admin.sincronizar.index')
                      ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm'
                      : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
            Coincidencias
        </a>
        <a href="{{ route('admin.sincronizar.sin-coincidencia') }}"
           class="px-4 py-1.5 text-sm font-medium rounded-lg transition
                  {{ request()->routeIs('admin.sincronizar.sin-coincidencia')
                      ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm'
                      : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
            Sin coincidencia
        </a>
        <a href="{{ route('admin.sincronizar.ejecutar') }}"
           class="px-4 py-1.5 text-sm font-medium rounded-lg transition
                  {{ request()->routeIs('admin.sincronizar.ejecutar')
                      ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm'
                      : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
            Ejecutar
        </a>
    </div>

    <form action="{{ route('admin.sincronizar.clear-cache') }}" method="POST">
        @csrf
        <button type="submit"
                class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400
                       hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
            <i class="fa-solid fa-rotate mr-1"></i>
            Refrescar datos
        </button>
    </form>
</div>
