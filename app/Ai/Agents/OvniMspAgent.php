<?php

namespace App\Ai\Agents;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

#[Provider(Lab::Anthropic)]
#[Model('claude-haiku-4-5-20251001')]
#[MaxTokens(1024)]
class OvniMspAgent implements Agent, Conversational
{
    use Promptable;

    public function __construct(
        private string $userMessage = '',
        private array  $chatHistory = []
    ) {}

    public function instructions(): string
    {
        $context = $this->buildContext();

        return <<<PROMPT
Eres Ovni, el asistente inteligente de MSP Reports Ovnicom. Eres experto en análisis de tickets de soporte técnico.

PERSONALIDAD:
- Profesional pero amigable
- Respuestas concisas
- Usa emojis con moderación
- Responde siempre en español

DATOS DE LA BASE DE DATOS:
{$context}

INSTRUCCIONES GENERALES:
- Usa SOLO los datos proporcionados para responder
- Usa markdown con bullets para listas
- NO inventes datos
- Revisa el HISTORIAL antes de hacer preguntas — si ya se respondió algo NO lo preguntes de nuevo

════════════════════════════════════
FLUJO PARA DESCARGA DE PDF
════════════════════════════════════
Cuando el usuario quiera descargar un PDF sigue estos pasos:

PASO 1 — Si no mencionó cliente: pregunta cuál cliente y muestra todos_los_clientes.
PASO 2 — Si ya mencionó cliente: identifica nombre exacto en todos_los_clientes, confirma y pregunta período mostrando periodos_disponibles.
PASO 3 — Cuando tengas cliente Y período confirmados, responde ÚNICAMENTE con este JSON:
{"action":"download_pdf","customer":"NOMBRE_EXACTO","periodo":"PERIODO_EXACTO"}

════════════════════════════════════
FLUJO PARA ENVÍO DE CORREO
════════════════════════════════════
Cuando el usuario quiera enviar el reporte por correo sigue estos pasos:

PASO 1 — Si no mencionó cliente: pregunta cuál cliente y muestra todos_los_clientes.
PASO 2 — Si ya mencionó cliente: identifica nombre exacto en todos_los_clientes, confirma y pregunta período mostrando periodos_disponibles.
PASO 3 — Cuando tengas cliente Y período confirmados:
  - Busca el email en clientes_con_email usando el nombre exacto del cliente
  - Si TIENE email: responde ÚNICAMENTE con este JSON:
    {"action":"send_email","customer":"NOMBRE_EXACTO","periodo":"PERIODO_EXACTO","email":"EMAIL_DEL_CLIENTE"}
  - Si NO TIENE email registrado: informa al usuario que ese cliente no tiene correo registrado y sugiere que lo agregue en la sección de Clientes.

IMPORTANTE PARA AMBOS FLUJOS:
- Usa el nombre EXACTO de todos_los_clientes en el JSON
- Usa el período EXACTO de periodos_disponibles en el JSON
- Solo responde con el JSON cuando tengas TODOS los datos confirmados
- Si solo hay un período disponible y el usuario confirma, úsalo directamente
PROMPT;
    }

    public function messages(): iterable
    {
        $messages = [];
        foreach ($this->chatHistory as $turn) {
            if (in_array($turn['role'] ?? '', ['user', 'assistant'])) {
                $messages[] = new Message($turn['role'], $turn['content']);
            }
        }
        return $messages;
    }

    private function buildContext(): string
    {
        $context = [];

        $ultimoPeriodo = DB::table('msp_reports')
            ->where('activo', 1)
            ->orderByDesc('periodo')
            ->value('periodo');

        $context['ultimo_periodo'] = $ultimoPeriodo ?? 'Sin datos';

        if ($ultimoPeriodo) {
            $totales = DB::table('msp_reports')
                ->where('periodo', $ultimoPeriodo)
                ->where('activo', 1)
                ->selectRaw('COUNT(*) as total_tickets, COUNT(DISTINCT customer_name) as total_clientes')
                ->first();

            $context['totales'] = (array) $totales;

            $context['top_clientes'] = DB::table('msp_reports')
                ->where('periodo', $ultimoPeriodo)
                ->where('activo', 1)
                ->select('customer_name', DB::raw('COUNT(*) as tickets'))
                ->groupBy('customer_name')
                ->orderByDesc('tickets')
                ->limit(10)
                ->get()
                ->map(fn($r) => (array)$r)
                ->toArray();

            $context['tipos_ticket'] = DB::table('msp_reports')
                ->where('periodo', $ultimoPeriodo)
                ->where('activo', 1)
                ->select('ticket_type', DB::raw('COUNT(*) as total'))
                ->groupBy('ticket_type')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn($r) => (array)$r)
                ->toArray();

            $clienteMencionado = $this->detectarCliente($this->userMessage);
            if ($clienteMencionado) {
                $context['cliente_especifico'] = [
                    'nombre'  => $clienteMencionado,
                    'tickets' => DB::table('msp_reports')
                        ->where('activo', 1)
                        ->where('customer_name', 'LIKE', "%{$clienteMencionado}%")
                        ->where('periodo', $ultimoPeriodo)
                        ->select('ticket_number', 'ticket_title', 'ticket_type', 'fecha_creacion', 'clasificacion_eventos', 'causa_dano', 'solucion')
                        ->limit(20)
                        ->get()
                        ->map(fn($r) => (array)$r)
                        ->toArray(),
                ];
            }
        }

        // Cargar lista completa solo cuando sea necesario
        $msgLower = strtolower($this->userMessage);
        $esPDFoCorreo = str_contains($msgLower, 'pdf')
            || str_contains($msgLower, 'descargar')
            || str_contains($msgLower, 'informe')
            || str_contains($msgLower, 'reporte')
            || str_contains($msgLower, 'correo')
            || str_contains($msgLower, 'enviar')
            || str_contains($msgLower, 'mandar')
            || str_contains($msgLower, 'si')
            || str_contains($msgLower, 'sí')
            || str_contains($msgLower, 'correcto')
            || str_contains($msgLower, 'enero')
            || str_contains($msgLower, 'febrero')
            || str_contains($msgLower, 'marzo');

        if ($esPDFoCorreo) {
            $context['todos_los_clientes'] = DB::table('msp_reports')
                ->where('activo', 1)
                ->distinct()
                ->orderBy('customer_name')
                ->pluck('customer_name')
                ->toArray();

            // Clientes con su email para flujo de correo
            $context['clientes_con_email'] = DB::table('msp_clients')
                ->whereNotNull('email_cliente')
                ->where('email_cliente', '!=', '')
                ->select('customer_name', 'email_cliente')
                ->orderBy('customer_name')
                ->get()
                ->map(fn($r) => (array)$r)
                ->toArray();
        }

        $context['periodos_disponibles'] = DB::table('msp_reports')
            ->where('activo', 1)
            ->distinct()
            ->orderByDesc('periodo')
            ->pluck('periodo')
            ->toArray();

        return json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function detectarCliente(string $message): ?string
    {
        if (empty($message)) return null;

        $clientes = DB::table('msp_reports')
            ->where('activo', 1)
            ->distinct()
            ->pluck('customer_name');

        foreach ($clientes as $cliente) {
            if (stripos($message, $cliente) !== false) return $cliente;
            $palabras = explode(' ', $cliente);
            if (count($palabras) > 1
                && stripos($message, $palabras[0]) !== false
                && stripos($message, $palabras[1]) !== false) {
                return $cliente;
            }
        }
        return null;
    }
}