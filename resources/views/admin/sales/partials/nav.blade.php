<div class="flex items-center gap-1 mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-1">
    <a href="{{ route('admin.sales.index') }}"
       class="px-4 py-2 text-sm rounded-lg transition {{ request()->routeIs('admin.sales.index') ? 'bg-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
        KPIs
    </a>
    <a href="{{ route('admin.sales.pipeline') }}"
       class="px-4 py-2 text-sm rounded-lg transition {{ request()->routeIs('admin.sales.pipeline') ? 'bg-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
        Pipeline
    </a>
    <a href="{{ route('admin.sales.clients') }}"
       class="px-4 py-2 text-sm rounded-lg transition {{ request()->routeIs('admin.sales.clients') ? 'bg-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
        Clientes
    </a>
    <a href="{{ route('admin.sales.executives') }}"
       class="px-4 py-2 text-sm rounded-lg transition {{ request()->routeIs('admin.sales.executives') ? 'bg-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
        Ejecutivas
    </a>
    <a href="{{ route('admin.sales.reassign') }}"
       class="px-4 py-2 text-sm rounded-lg transition {{ request()->routeIs('admin.sales.reassign') ? 'bg-purple-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
        Reasignación
    </a>
</div>