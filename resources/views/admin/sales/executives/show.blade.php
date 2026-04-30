<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition">Dashboard</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('admin.sales.index') }}" class="hover:text-gray-600 transition">Ventas</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('admin.sales.executives') }}" class="hover:text-gray-600 transition">Ejecutivas</a>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200 font-medium">{{ $exec['name'] }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @include('admin.sales.partials.nav')

            @php
                $wr       = $exec['win_rate'] ?? 0;
                $wrColor  = $wr >= 30 ? 'text-emerald-500' : ($wr >= 15 ? 'text-amber-500' : 'text-red-500');
                $wrStroke = $wr >= 30 ? '#10b981'          : ($wr >= 15 ? '#f59e0b'         : '#ef4444');
                $wrBadge  = $wr >= 30
                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400'
                    : ($wr >= 15
                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'
                        : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400');
                $nc       = $exec['noContact'] ?? 0;
                $hasPhoto = !empty($exec['image_128']) && !str_starts_with($exec['image_128'], 'PD94');
                $initials = collect(explode(' ', $exec['name']))->take(2)->map(fn($w) => strtoupper(substr($w, 0, 1)))->join('');
                $avatarColors = ['bg-violet-500','bg-blue-500','bg-emerald-500','bg-amber-500','bg-rose-500','bg-sky-500'];
                $color = $avatarColors[abs(crc32($exec['name'])) % count($avatarColors)];

                // Gauge: semicírculo radio=54 → longitud arco = π×54 ≈ 169.6
                $arcLen     = M_PI * 54;
                $dashOffset = $arcLen - ($arcLen * min($wr, 100) / 100);
            @endphp

            {{-- ── HEADER PERFIL ──────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    @if($hasPhoto)
                        <img src="data:image/png;base64,{{ $exec['image_128'] }}"
                             class="w-20 h-20 rounded-full object-cover flex-shrink-0 ring-2 ring-gray-100 dark:ring-gray-700"
                             alt="{{ $exec['name'] }}">
                    @else
                        <div class="w-20 h-20 rounded-full {{ $color }} flex items-center justify-center
                                    text-white text-2xl font-bold flex-shrink-0 ring-2 ring-gray-100 dark:ring-gray-700">
                            {{ $initials }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-3 mb-1">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $exec['name'] }}</h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $wrBadge }}">
                                WR {{ $wr }}%
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-400">
                            @if(!empty($exec['email']))
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $exec['email'] }}
                                </span>
                            @endif
                            @if(!empty($exec['mobile']) && $exec['mobile'] !== false)
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $exec['mobile'] }}
                                </span>
                            @elseif(!empty($exec['phone']) && $exec['phone'] !== false)
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $exec['phone'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('admin.sales.executives') }}"
                       class="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2
                              rounded-lg border border-gray-200 dark:border-gray-600
                              text-sm font-medium text-gray-600 dark:text-gray-300
                              hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>

            {{-- ── KPI CARDS ───────────────────────────────── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Leads activos</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($exec['leads'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">en seguimiento</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Ganadas</p>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($exec['won'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">oportunidades cerradas</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Pipeline</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($exec['pipeline'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">oportunidades abiertas</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Sin contacto</p>
                    <p class="text-3xl font-bold {{ $nc > 10 ? 'text-red-500' : 'text-gray-900 dark:text-gray-100' }}">
                        {{ number_format($nc) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">clientes sin actividad</p>
                </div>
            </div>

            {{-- ── GAUGE + RESUMEN ─────────────────────────── --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Gauge semicircular --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 flex flex-col">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-6">Win Rate</p>
                    <div class="flex flex-col items-center justify-center flex-1">
                        <div class="relative" style="width:160px;height:88px;">
                            <svg viewBox="0 0 160 88" xmlns="http://www.w3.org/2000/svg" style="width:160px;height:88px;overflow:visible;">
                                {{-- Track gris --}}
                                <path d="M 10 84 A 70 70 0 0 1 150 84"
                                      fill="none"
                                      stroke="#e5e7eb"
                                      stroke-width="12"
                                      stroke-linecap="round"/>
                                {{-- Fill color --}}
                                <path d="M 10 84 A 70 70 0 0 1 150 84"
                                      fill="none"
                                      stroke="{{ $wrStroke }}"
                                      stroke-width="12"
                                      stroke-linecap="round"
                                      stroke-dasharray="{{ number_format(M_PI * 70, 2, '.', '') }}"
                                      stroke-dashoffset="{{ number_format(M_PI * 70 - (M_PI * 70 * min($wr,100) / 100), 2, '.', '') }}"/>
                            </svg>
                            {{-- Texto centrado en el hueco del gauge --}}
                            <div class="absolute inset-0 flex items-end justify-center" style="padding-bottom:4px;">
                                <span class="text-3xl font-bold {{ $wrColor }}">{{ $wr }}%</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-400 mt-3 text-center">
                            {{ number_format($exec['won'] ?? 0) }} de {{ number_format($exec['total_oport'] ?? 0) }} oportunidades
                        </p>
                    </div>
                </div>

                {{-- Resumen --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-4">Resumen</p>
                    <dl class="divide-y divide-gray-50 dark:divide-gray-700/60">
                        <div class="flex justify-between py-2.5 text-sm">
                            <dt class="text-gray-400">Total oportunidades</dt>
                            <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($exec['total_oport'] ?? 0) }}</dd>
                        </div>
                        <div class="flex justify-between py-2.5 text-sm">
                            <dt class="text-gray-400">Ganadas</dt>
                            <dd class="font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($exec['won'] ?? 0) }}</dd>
                        </div>
                        <div class="flex justify-between py-2.5 text-sm">
                            <dt class="text-gray-400">Pipeline activo</dt>
                            <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($exec['pipeline'] ?? 0) }}</dd>
                        </div>
                        <div class="flex justify-between py-2.5 text-sm">
                            <dt class="text-gray-400">Leads asignados</dt>
                            <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($exec['leads'] ?? 0) }}</dd>
                        </div>
                        <div class="flex justify-between py-2.5 text-sm">
                            <dt class="text-gray-400">Sin contacto</dt>
                            <dd class="font-semibold {{ $nc > 10 ? 'text-red-500' : 'text-gray-800 dark:text-gray-200' }}">
                                {{ number_format($nc) }}
                            </dd>
                        </div>
                    </dl>
                </div>

            </div>

            {{-- ── OPORTUNIDADES CRM ───────────────────────── --}}
            @if(!empty($exec['opportunities']))
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Oportunidades activas</h2>
                    <span class="text-xs text-gray-400">{{ count($exec['opportunities']) }} registros</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-6 py-3 text-left  text-xs font-semibold text-gray-400 uppercase tracking-wider">Oportunidad</th>
                                <th class="px-6 py-3 text-left  text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left  text-xs font-semibold text-gray-400 uppercase tracking-wider">Etapa</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Ingreso esperado</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Probabilidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                            @foreach($exec['opportunities'] as $opp)
                            @php
                                $prob        = $opp['probability'] ?? 0;
                                $probColor   = $prob >= 70 ? 'text-emerald-600 dark:text-emerald-400' : ($prob >= 40 ? 'text-amber-600 dark:text-amber-400' : 'text-red-500');
                                $probBg      = $prob >= 70 ? 'bg-emerald-500' : ($prob >= 40 ? 'bg-amber-500' : 'bg-red-500');
                                $partnerName = is_array($opp['partner_id']) ? ($opp['partner_id'][1] ?? '—') : ($opp['partner_id'] ?? '—');
                                $stageName   = is_array($opp['stage_id'])   ? ($opp['stage_id'][1]   ?? '—') : ($opp['stage_id']   ?? '—');
                            @endphp
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition">
                                <td class="px-6 py-3">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $opp['name'] ?? '—' }}</p>
                                    @if(!empty($opp['date_deadline']) && $opp['date_deadline'] !== false)
                                        <p class="text-xs text-gray-400 mt-0.5">Cierre: {{ \Carbon\Carbon::parse($opp['date_deadline'])->format('d/m/Y') }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $partnerName }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        {{ $stageName }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-gray-800 dark:text-gray-200">
                                    ${{ number_format($opp['expected_revenue'] ?? 0, 0) }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2 justify-end">
                                        <div class="w-16 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $probBg }}" style="width:{{ min($prob,100) }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold {{ $probColor }} w-8 text-right">{{ $prob }}%</span>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ── ACTIVIDAD RECIENTE ──────────────────────── --}}
            @if(!empty($exec['activities']))
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Actividad reciente</h2>
                    <span class="text-xs text-gray-400">{{ count($exec['activities']) }} registros</span>
                </div>
                <ul class="divide-y divide-gray-50 dark:divide-gray-700/60">
                    @foreach($exec['activities'] as $act)
                    @php
                        $actType   = $act['activity_type'] ?? 'Actividad';
                        $lower     = strtolower($actType);
                        $iconBg    = str_contains($lower,'call')||str_contains($lower,'llam')
                            ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-500'
                            : (str_contains($lower,'email')||str_contains($lower,'correo')
                                ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500'
                                : (str_contains($lower,'meet')||str_contains($lower,'reun')
                                    ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-500'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-400'));
                        $isOverdue = !empty($act['date_deadline']) && $act['date_deadline'] !== false
                                     && \Carbon\Carbon::parse($act['date_deadline'])->isPast();
                    @endphp
                    <li class="px-6 py-4 flex items-start gap-4">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full {{ $iconBg }} flex items-center justify-center mt-0.5">
                            @if(str_contains($lower,'call')||str_contains($lower,'llam'))
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            @elseif(str_contains($lower,'email')||str_contains($lower,'correo'))
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            @elseif(str_contains($lower,'meet')||str_contains($lower,'reun'))
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            @endif
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ $act['summary'] ?: ($act['res_name'] ?? $actType) }}
                            </p>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="text-xs text-gray-400">{{ $actType }}</span>
                                @if(!empty($act['date_deadline']) && $act['date_deadline'] !== false)
                                    <span class="text-xs {{ $isOverdue ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                                        {{ \Carbon\Carbon::parse($act['date_deadline'])->format('d/m/Y') }}
                                        @if($isOverdue) · vencida @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- ── ESTADO VACÍO ────────────────────────────── --}}
            @if(empty($exec['opportunities']) && empty($exec['activities']))
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm py-14 text-center">
                <svg class="w-10 h-10 text-gray-200 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm text-gray-400">No hay oportunidades ni actividad registrada para esta ejecutiva.</p>
                <p class="text-xs text-gray-300 dark:text-gray-500 mt-1">Los datos se cargan desde Odoo en tiempo real.</p>
            </div>
            @endif

        </div>
    </div>

</x-app-layout>