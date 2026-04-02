{{-- resources/views/msp/chat.blade.php --}}
@extends('admin.reports.msp.layouts.app')
@section('title', 'Chat IA — MSP Reports')

@section('content')
<div class="max-w-4xl mx-auto fade-in" style="height:calc(100vh - 140px); display:flex; flex-direction:column;">

    {{-- Header chat --}}
    <div class="bg-white rounded-t-2xl border-x border-t shadow-sm p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white"
             style="background:linear-gradient(135deg,var(--ovni-dark),var(--ovni-orange))">
            <i class="fa-solid fa-robot text-sm"></i>
        </div>
        <div>
            <div class="font-semibold text-gray-800 text-sm">Asistente MSP Ovnicom</div>
            <div class="text-xs text-green-500 flex items-center gap-1">
                <span class="w-2 h-2 bg-green-500 rounded-full inline-block"></span>
                En línea — puede consultar datos, generar y enviar PDFs
            </div>
        </div>
        <div class="ml-auto">
            <button onclick="clearChat()" class="text-xs text-gray-400 hover:text-gray-600 px-3 py-1.5 border rounded-lg">
                <i class="fa-solid fa-trash-can mr-1"></i> Limpiar
            </button>
        </div>
    </div>

    {{-- Mensajes --}}
    <div id="chatMessages"
         class="flex-1 overflow-y-auto bg-white border-x px-5 py-4 space-y-4"
         style="min-height:0">

        {{-- Mensaje inicial --}}
        <div class="flex gap-3 ai-message">
            <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center text-white text-xs"
                 style="background:var(--ovni-orange)">IA</div>
            <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-3 max-w-2xl">
                <p class="text-sm text-gray-800">
                    ¡Hola! Soy el asistente IA de <strong>MSP Reports Ovnicom</strong>. Puedo ayudarte a:
                </p>
                <ul class="text-sm text-gray-700 mt-2 space-y-1 list-disc list-inside">
                    <li>📊 Consultar estadísticas de cualquier cliente</li>
                    <li>📄 Descargar el PDF de un cliente</li>
                    <li>✉️ Enviar el reporte por correo electrónico</li>
                    <li>🔍 Comparar clientes o períodos</li>
                </ul>
                <p class="text-sm text-gray-600 mt-2">¿Con qué puedo ayudarte hoy?</p>
            </div>
        </div>
    </div>

    {{-- Sugerencias rápidas --}}
    <div id="suggestions" class="bg-white border-x px-5 py-3 flex gap-2 flex-wrap border-t">
        @foreach([
            '¿Cuántos incidentes tuvo Casa de las Baterias?',
            'Descarga el PDF de Rootstack',
            'Muéstrame los clientes con más tickets',
            'Envía el reporte de Casa de las Baterias a test@test.com',
        ] as $sug)
        <button onclick="sendSuggestion(this)"
                class="text-xs px-3 py-1.5 border rounded-full text-gray-600 hover:border-orange-400 hover:text-orange-600 transition">
            {{ $sug }}
        </button>
        @endforeach
    </div>

    {{-- Input --}}
    <div class="bg-white rounded-b-2xl border shadow-sm p-4 flex gap-3">
        <input type="text" id="chatInput"
               placeholder="Escribe tu consulta sobre clientes, tickets, PDFs..."
               class="flex-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-400 outline-none"
               onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}">
        <button onclick="sendMessage()" id="sendBtn"
                class="text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:opacity-90 active:scale-95 transition flex items-center gap-2"
                style="background:var(--ovni-orange)">
            <i class="fa-solid fa-paper-plane" id="sendIcon"></i>
        </button>
    </div>
</div>

{{-- Action modal --}}
<div id="actionModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4">
        <div id="actionModalContent"></div>
        <div class="flex gap-3 mt-4">
            <button onclick="confirmAction()" id="confirmBtn"
                    class="flex-1 text-white py-2.5 rounded-lg text-sm font-medium"
                    style="background:var(--ovni-orange)">Confirmar</button>
            <button onclick="closeModal()"
                    class="flex-1 border py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">
                Cancelar
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let history = [];
let pendingAction = null;

function addMessage(role, content, isLoading = false) {
    const container = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = 'flex gap-3 ' + (role === 'user' ? 'justify-end' : 'ai-message');

    if (role === 'user') {
        div.innerHTML = `
            <div class="bg-orange-500 text-white rounded-2xl rounded-tr-none px-4 py-3 max-w-2xl text-sm">${content}</div>
            <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center bg-gray-200 text-gray-600 text-xs font-bold">TÚ</div>
        `;
    } else {
        const body = isLoading
            ? `<div class="flex gap-1 items-center h-5">
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></div>
               </div>`
            : `<p class="text-sm text-gray-800 whitespace-pre-wrap">${content}</p>`;

        div.innerHTML = `
            <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center text-white text-xs" style="background:var(--ovni-orange)">IA</div>
            <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-3 max-w-2xl" id="${isLoading ? 'loadingMsg' : ''}">${body}</div>
        `;
    }

    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return div;
}

async function sendMessage(overrideMsg = null) {
    const input   = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const message = overrideMsg || input.value.trim();
    if (!message) return;

    input.value = '';
    sendBtn.disabled = true;
    document.getElementById('sendIcon').className = 'fa-solid fa-spinner fa-spin';

    addMessage('user', message);

    const loadingDiv = addMessage('assistant', '', true);

    try {
        const res = await fetch('{{ route("admin.msp.chat.api") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ message, history }),
        });

        const data = await res.json();
        loadingDiv.remove();

        history.push({ role: 'user', content: message });
        history.push({ role: 'assistant', content: data.response });

        if (data.action) {
            handleAction(data.action);
        } else {
            addMessage('assistant', data.response);
        }
    } catch (e) {
        loadingDiv.remove();
        addMessage('assistant', '❌ Error al conectar con el servidor. Intenta de nuevo.');
    }

    sendBtn.disabled = false;
    document.getElementById('sendIcon').className = 'fa-solid fa-paper-plane';
    document.getElementById('suggestions').classList.add('hidden');
}

function handleAction(action) {
    pendingAction = action;
    const modal   = document.getElementById('actionModal');
    const content = document.getElementById('actionModalContent');

    if (action.action === 'download_pdf') {
        content.innerHTML = `
            <div class="text-center">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-file-pdf text-orange-600 text-xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Descargar PDF</h3>
                <p class="text-sm text-gray-600">¿Descargar el reporte de <strong>${action.customer}</strong> para el período <strong>${action.periodo}</strong>?</p>
            </div>`;
    } else if (action.action === 'send_email') {
        content.innerHTML = `
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-envelope text-blue-600 text-xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-1">Enviar por correo</h3>
                <p class="text-sm text-gray-600">¿Enviar el PDF de <strong>${action.customer}</strong> a <strong>${action.email}</strong>?</p>
            </div>`;
    }

    modal.classList.remove('hidden');
}

function confirmAction() {
    if (!pendingAction) return;
    closeModal();

    const customer = encodeURIComponent(pendingAction.customer);
    const periodo  = encodeURIComponent(pendingAction.periodo || '');

    if (pendingAction.action === 'download_pdf') {
        window.location.href = `/msp/pdf/${customer}/download?periodo=${periodo}`;
        addMessage('assistant', `✅ Iniciando descarga del PDF de **${pendingAction.customer}**...`);
    } else if (pendingAction.action === 'send_email') {
        // Trigger envío vía form POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.msp.correos.enviar") }}';
        form.innerHTML = `
            <input name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
            <input name="customer_name" value="${pendingAction.customer}">
            <input name="email" value="${pendingAction.email}">
            <input name="periodo" value="${pendingAction.periodo}">
            <input name="subject" value="Informe MSP — ${pendingAction.periodo}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function closeModal() {
    document.getElementById('actionModal').classList.add('hidden');
    pendingAction = null;
}

function sendSuggestion(btn) {
    sendMessage(btn.textContent.trim());
}

function clearChat() {
    history = [];
    const container = document.getElementById('chatMessages');
    container.innerHTML = '';
    document.getElementById('suggestions').classList.remove('hidden');
}
</script>
@endpush
