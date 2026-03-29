@foreach($tickets as $item)
<tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">

    <td class="px-6 py-4">
        <span class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">
            {{ $item['ticket_number'] }}
        </span>
    </td>

    <td class="px-6 py-4">
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
            {{ $item['issue_type'] }}
        </span>
    </td>

    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
        {{ $item['created_date'] }}
    </td>

    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
        {{ $item['completed_date'] ?: '—' }}
    </td>

    {{-- Botón abrir modal --}}
    <td class="px-6 py-4 text-right">
        <button
            data-ticket="{{ e(json_encode($item)) }}"
            class="ticket-btn inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition border border-indigo-200 dark:border-indigo-700">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            Ver detalle
        </button>
    </td>

</tr>
@endforeach