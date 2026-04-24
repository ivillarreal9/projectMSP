{{-- resources/views/admin/reports/msp/partials/chat-flotante.blade.php --}}

@auth
@php $userName = auth()->user()->name ?? 'usuario'; @endphp
@else
@php $userName = 'usuario'; @endphp
@endauth

<div id="ovni-chat-root">

    {{-- ═══ BOTÓN FLOTANTE ═══ --}}
    <button id="ovniChatFab"
            onclick="OvniChat.toggle()"
            aria-label="Abrir asistente Ovni"
            class="ovni-fab fixed bottom-6 right-6 z-50 w-16 h-16 rounded-full shadow-2xl
                   flex items-center justify-center transition-all duration-300
                   hover:scale-110 active:scale-95 overflow-hidden border-2 border-white/20">

        {{-- Avatar Ovni (cerrado) --}}
        <div id="ovniFabAvatar" class="w-full h-full rounded-full overflow-hidden">
            <img src="{{ asset('storage/logos/ovni.png') }}"
                 alt="Ovni"
                 onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gradient-to-br from-orange-500 to-orange-700\'><span class=\'text-white text-2xl font-black\'>O</span></div>'"
                 class="w-full h-full object-cover">
        </div>

        {{-- Ícono X (abierto) --}}
        <div id="ovniFabClose" class="hidden w-full h-full rounded-full flex items-center justify-center"
             style="background:linear-gradient(135deg,#1a1a2e,#c2410c)">
            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>

        {{-- Badge --}}
        <span id="ovniChatBadge"
              class="hidden absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold
                     rounded-full flex items-center justify-center border-2 border-white animate-bounce">
            1
        </span>

        {{-- Pulso --}}
        <span class="ovni-pulse"></span>
    </button>

    {{-- ═══ PANEL DEL CHAT ═══ --}}
    <div id="ovniChatPanel"
     class="hidden fixed z-50 flex-col overflow-hidden"
     style="width:380px; max-width:100vw; height:580px; max-height:calc(100vh - 2rem);
            bottom:24px; right:24px; border-radius:16px;
            background:#fff; box-shadow:0 25px 60px rgba(0,0,0,0.25);">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-4 py-3 flex-shrink-0 relative overflow-hidden"
             style="background:linear-gradient(135deg,#1a1a2e 0%,#c2410c 100%)">

            <div class="absolute inset-0 opacity-5"
                 style="background-image:radial-gradient(circle,white 1px,transparent 1px);background-size:16px 16px;"></div>

            <div class="relative flex-shrink-0">
                <div class="w-11 h-11 rounded-full overflow-hidden border-2 border-white/30 shadow-lg">
                    <img src="{{ asset('storage/logos/ovni.png') }}"
                         alt="Ovni"
                         onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-orange-500\'><span class=\'text-white font-black\'>O</span></div>'"
                         class="w-full h-full object-cover">
                </div>
                <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></span>
            </div>

            <div class="flex-1 min-w-0 relative">
                <div class="font-bold text-white text-sm">Ovni</div>
                <div class="text-xs text-white/75 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse inline-block"></span>
                    Asistente MSP · En línea
                </div>
            </div>

            <button onclick="OvniChat.clear()" title="Nueva conversación"
                    class="text-white/60 hover:text-white transition p-1.5 rounded-lg hover:bg-white/10 relative">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
            <button onclick="OvniChat.close()" title="Cerrar"
                    class="text-white/60 hover:text-white transition p-1.5 rounded-lg hover:bg-white/10 relative">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Mensajes --}}
        <div id="ovniChatMessages"
             class="flex-1 overflow-y-auto px-4 py-4 space-y-3"
             style="min-height:0; background:#f8f9fb;"></div>

        {{-- Sugerencias --}}
        <div id="ovniChatSuggestions"
             class="px-3 py-2 border-t border-gray-100 flex gap-2 flex-wrap bg-white">
            @foreach(['Resumen del mes', 'Descargar PDF', 'Enviar por correo'] as $sug)
            <button onclick="OvniChat.suggest('{{ $sug }}')"
                    class="text-xs px-3 py-1.5 rounded-full border border-gray-200
                           text-gray-500 hover:border-orange-400 hover:text-orange-600
                           hover:bg-orange-50 transition-all duration-150 flex items-center gap-1">
                {{ $sug }}
            </button>
            @endforeach
        </div>

        {{-- Input --}}
        <div class="px-3 py-3 border-t border-gray-100 flex gap-2 items-end bg-white flex-shrink-0">
            <textarea id="ovniChatInput"
                      rows="1"
                      placeholder="Conversa con Ovni aquí..."
                      class="flex-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm
                             focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none
                             resize-none text-gray-700 placeholder-gray-400"
                      style="max-height:100px; font-family:inherit;"
                      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();OvniChat.send();}"
                      oninput="OvniChat.resize(this)"></textarea>
            <button onclick="OvniChat.send()" id="ovniSendBtn"
                    class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                           text-white shadow-md hover:opacity-90 active:scale-95 transition
                           disabled:opacity-40 disabled:cursor-not-allowed"
                    style="background:linear-gradient(135deg,#f97316,#c2410c)">
                <svg id="ovniSendIcon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>

<script>
(function() {
    'use strict';

    const STORAGE_KEY = 'ovni_chat_history';
    const MAX_HISTORY = 20;
    const USER_NAME   = @json($userName);
    const CSRF_TOKEN  = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const API_URL     = '{{ route("admin.msp.chat.api") }}';

    let history = [];
    let isOpen  = false;
    let unread  = 0;

    const $ = id => document.getElementById(id);

    function loadHistory() {
        try { history = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
        catch(e) { history = []; }
    }
    function saveHistory() {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(history.slice(-MAX_HISTORY))); }
        catch(e) {}
    }

    function time() {
        return new Date().toLocaleTimeString('es-PA', { hour:'2-digit', minute:'2-digit' });
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ══ Detectar JSON de acción en cualquier parte del texto ══
    function extractAction(text) {
        const match = text.match(/\{[^{}]*"action"\s*:\s*"download_pdf"[^{}]*\}/s);
        if (match) {
            try { return JSON.parse(match[0]); }
            catch(e) {}
        }
        return null;
    }

    // ══ Limpiar JSON crudo del texto visible ══
    function cleanResponse(text) {
        return text.replace(/\{[^{}]*"action"\s*:\s*"download_pdf"[^{}]*\}/gs, '').trim();
    }

    function renderMsg(role, content, animate = true, ts = null) {
        const wrap = document.createElement('div');
        wrap.className = 'flex gap-2 ' + (role === 'user' ? 'justify-end' : 'justify-start');
        if (animate) wrap.style.animation = 'ovniFadeIn .22s ease-out';

        const html = role === 'assistant'
            ? (typeof marked !== 'undefined' ? marked.parse(content) : escHtml(content))
            : escHtml(content);

        const t = ts || time();

        if (role === 'user') {
            wrap.innerHTML = `
                <div class="flex flex-col items-end gap-0.5 max-w-[82%]">
                    <div class="text-white text-sm px-3.5 py-2.5 rounded-2xl rounded-tr-sm shadow-sm"
                         style="background:linear-gradient(135deg,#f97316,#c2410c)">${html}</div>
                    <span class="text-[10px] text-gray-400 px-1">${t}</span>
                </div>`;
        } else {
            wrap.innerHTML = `
                <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 shadow-sm mt-0.5 border border-orange-100">
                    <img src="{{ asset('storage/logos/ovni.png') }}"
                         onerror="this.parentElement.innerHTML='<div style=\'background:linear-gradient(135deg,#f97316,#c2410c)\' class=\'w-full h-full flex items-center justify-center\'><span class=\'text-white text-xs font-black\'>O</span></div>'"
                         class="w-full h-full object-cover">
                </div>
                <div class="flex flex-col gap-0.5 max-w-[82%]">
                    <div class="bg-white text-gray-800 text-sm px-3.5 py-2.5 rounded-2xl rounded-tl-sm shadow-sm
                                ovni-md border border-gray-100">${html}</div>
                    <span class="text-[10px] text-gray-400 px-1">${t}</span>
                </div>`;
        }

        $('ovniChatMessages').appendChild(wrap);
        scrollDown();
        return wrap;
    }

    function renderLoading() {
        const div = document.createElement('div');
        div.id = 'ovniLoading';
        div.className = 'flex gap-2 items-end';
        div.innerHTML = `
            <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 border border-orange-100 shadow-sm">
                <img src="{{ asset('storage/logos/ovni.png') }}"
                     onerror="this.parentElement.innerHTML='<div style=\'background:linear-gradient(135deg,#f97316,#c2410c)\' class=\'w-full h-full flex items-center justify-center\'><span class=\'text-white text-xs font-black\'>O</span></div>'"
                     class="w-full h-full object-cover">
            </div>
            <div class="bg-white rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm border border-gray-100">
                <div class="flex gap-1 items-center">
                    <span class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                    <span class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                    <span class="w-2 h-2 bg-orange-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                </div>
            </div>`;
        $('ovniChatMessages').appendChild(div);
        scrollDown();
        return div;
    }

    function greeting() {
        const h = new Date().getHours();
        const g = h < 12 ? '¡Buenos días' : h < 19 ? '¡Buenas tardes' : '¡Buenas noches';
        const firstName = USER_NAME.split(' ')[0];
        renderMsg('assistant',
            `${g}, **${firstName}**! 👋\n\nSoy **Ovni**, tu asistente de MSP Reports Ovnicom. Puedo ayudarte a:\n\n- 📊 Consultar estadísticas de clientes\n- 📄 Descargar reportes PDF\n- ✉️ Enviar reportes por correo\n- 🔍 Comparar clientes o períodos\n\n¿Con qué puedo ayudarte hoy?`,
            false
        );
    }

    function renderAll() {
        $('ovniChatMessages').innerHTML = '';
        if (history.length === 0) { greeting(); return; }
        history.forEach(m => renderMsg(m.role, m.content, false, m.time));
    }

    function scrollDown() {
        const c = $('ovniChatMessages');
        setTimeout(() => c.scrollTop = c.scrollHeight, 50);
    }

    async function send(override = null) {
        const input   = $('ovniChatInput');
        const sendBtn = $('ovniSendBtn');
        const msg     = override || input.value.trim();
        if (!msg) return;

        input.value = '';
        input.style.height = 'auto';
        sendBtn.disabled = true;
        $('ovniSendIcon').outerHTML = `<svg id="ovniSendIcon" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="white" stroke-width="4"></circle>
            <path class="opacity-75" fill="white" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>`;

        const ts = time();
        renderMsg('user', msg);
        history.push({ role:'user', content:msg, time:ts });
        saveHistory();
        $('ovniChatSuggestions').classList.add('hidden');

        const loader = renderLoading();

        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: msg, history: history.slice(-MAX_HISTORY) }),
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            loader.remove();

            let reply = data.response || data.message || 'Sin respuesta.';

            // ══ Detectar acción: primero del servidor, luego del texto crudo ══
            let action = data.action || extractAction(reply);

            if (action && action.action === 'download_pdf') {
                // Limpiar el JSON del texto visible
                reply = cleanResponse(reply);
                if (!reply) reply = `✅ Descargando PDF de **${action.customer}** — **${action.periodo}**...`;
            }

            renderMsg('assistant', reply);
            history.push({ role:'assistant', content:reply, time:time() });
            saveHistory();

            // Disparar descarga
            if (action) handleAction(action);

        } catch(e) {
            loader.remove();
            renderMsg('assistant', '❌ Error al conectar. <button onclick="OvniChat.retry()" style="color:#f97316;text-decoration:underline">Reintentar</button>');
            console.error('OvniChat:', e);
        }

        sendBtn.disabled = false;
        $('ovniSendIcon').outerHTML = `<svg id="ovniSendIcon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>`;
        input.focus();
    }

    function handleAction(action) {
        if (action.action === 'download_pdf') {
            const url = action.download_url
                || `/admin/reports/msp/pdf/${encodeURIComponent(action.customer)}/download?periodo=${encodeURIComponent(action.periodo || '')}`;
            window.open(url, '_blank');
        }

        if (action.action === 'send_email') {
            // Enviar correo via POST al endpoint existente
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action.email_url || '/admin/reports/msp/correos/enviar';
            form.style.display = 'none';

            const fields = {
                '_token':        document.querySelector('meta[name="csrf-token"]')?.content || '',
                'customer_name': action.customer,
                'email':         action.email,
                'periodo':       action.periodo,
                'subject':       `Informe MSP — ${action.periodo}`,
                'mensaje':       '',
            };

            Object.entries(fields).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.name  = name;
                input.value = value;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            setTimeout(() => form.remove(), 1000);
        }
    }

    function retry() {
        const last = [...history].reverse().find(m => m.role === 'user');
        if (last) send(last.content);
    }

    function suggest(text) { send(text); }

    function clear() {
        if (!confirm('¿Iniciar nueva conversación? Se borrará el historial.')) return;
        history = [];
        saveHistory();
        renderAll();
        $('ovniChatSuggestions').classList.remove('hidden');
    }

    function resize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 100) + 'px';
    }

    function open() {
        isOpen = true;
        const panel = $('ovniChatPanel');
        panel.classList.remove('hidden');
        panel.classList.add('flex');
        $('ovniFabAvatar').classList.add('hidden');
        $('ovniFabClose').classList.remove('hidden');
        unread = 0;
        updateBadge();
        setTimeout(() => $('ovniChatInput').focus(), 300);
    }

    function close() {
        isOpen = false;
        const panel = $('ovniChatPanel');
        panel.classList.add('hidden');
        panel.classList.remove('flex');
        $('ovniFabClose').classList.add('hidden');
        $('ovniFabAvatar').classList.remove('hidden');
    }

    function toggle() { isOpen ? close() : open(); }

    function updateBadge() {
        const b = $('ovniChatBadge');
        if (!b) return;
        unread > 0 && !isOpen ? b.classList.remove('hidden') : b.classList.add('hidden');
    }

    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === '/') { e.preventDefault(); toggle(); }
        if (e.key === 'Escape' && isOpen) close();
    });

    window.OvniChat = { send, suggest, clear, resize, retry, open, close, toggle };

    function init() {
        loadHistory();
        renderAll();
        if (history.length > 0) $('ovniChatSuggestions').classList.add('hidden');
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();
})();
</script>

<style>
.ovni-fab {
    background: transparent; border: none; padding: 0; cursor: pointer;
    transition: transform .2s ease, box-shadow .2s ease;
    box-shadow: 0 8px 30px rgba(201,72,12,.4);
}
.ovni-fab:hover { box-shadow: 0 12px 40px rgba(201,72,12,.5); }

.ovni-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    background: #f97316; opacity: .35;
    animation: ovniPulse 2.5s ease-out infinite;
    pointer-events: none; z-index: -1;
}
@keyframes ovniPulse {
    0%   { transform:scale(1); opacity:.4; }
    100% { transform:scale(1.5); opacity:0; }
}
@keyframes ovniFadeIn {
    from { opacity:0; transform:translateY(8px); }
    to   { opacity:1; transform:translateY(0); }
}
.ovni-md p { margin:0; }
.ovni-md p+p { margin-top:.4rem; }
.ovni-md ul, .ovni-md ol { margin:.4rem 0; padding-left:1.2rem; }
.ovni-md li { margin:.1rem 0; }
.ovni-md strong { font-weight:700; }
.ovni-md code {
    background:rgba(249,115,22,.1); color:#c2410c;
    padding:1px 5px; border-radius:4px;
    font-size:.85em; font-family:ui-monospace,monospace;
}
.ovni-md a { color:#f97316; text-decoration:underline; }
#ovniChatMessages::-webkit-scrollbar { width:5px; }
#ovniChatMessages::-webkit-scrollbar-thumb { background:rgba(0,0,0,.12); border-radius:3px; }
#ovniChatMessages::-webkit-scrollbar-track { background:transparent; }
@media (max-width:640px) {
    #ovniChatPanel {
        bottom:0 !important; right:0 !important;
        width:100vw !important; max-width:100vw !important;
        height:100dvh !important; max-height:100dvh !important;
        border-radius:0 !important;
    }
}
</style>