<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Agente de IA básico para análisis de tickets MSP en modo chat.
 *
 * Proporciona un asistente conversacional que responde preguntas sobre
 * tickets de soporte técnico MSP a partir de un contexto inyectado externamente.
 * A diferencia de {@see OvniMspAgent}, este agente no accede a la base de datos
 * directamente; en su lugar recibe los datos como texto pre-formateado mediante
 * {@see setTicketContext()}.
 *
 * Implementa las interfaces del paquete `laravel/ai`:
 * - Agent: contrato base del agente.
 * - Conversational: habilita el historial de conversación multi-turno.
 * - HasTools: contrato para declarar herramientas (actualmente sin herramientas configuradas).
 *
 * Uso típico:
 * ```php
 * $agent = new MspChatAgent();
 * $agent->setTicketContext($textoCsvOJson);
 * $respuesta = (string) $agent->prompt('¿Cuántos incidentes tuvo el cliente X?');
 * ```
 *
 * Comportamiento del agente:
 * - Responde siempre en español.
 * - Se ciñe estrictamente a los datos del contexto inyectado.
 * - No inventa información no presente en el contexto.
 * - Puede buscar tickets por número, título o nombre de cliente.
 */
class MspChatAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Contexto de tickets inyectado externamente como texto plano o JSON.
     *
     * Se incluye verbatim en el prompt de instrucciones para que el modelo
     * tenga acceso a los datos sin realizar consultas propias a la BD.
     *
     * @var string
     */
    protected string $ticketContext = '';

    /**
     * Inyecta el contexto de tickets que el agente usará para responder.
     *
     * Se debe llamar antes de invocar {@see prompt()}. El contexto puede ser
     * JSON, CSV o texto libre con los datos de los tickets del cliente.
     *
     * @param  string $context Texto con los datos de tickets (JSON, CSV o texto libre).
     * @return static          Retorna la misma instancia para encadenamiento fluido.
     */
    public function setTicketContext(string $context): static
    {
        $this->ticketContext = $context;
        return $this;
    }

    /**
     * Define el prompt de instrucciones del sistema para el modelo de IA.
     *
     * Establece el rol del agente, las reglas de comportamiento y embebe
     * el contexto de tickets inyectado mediante {@see setTicketContext()}.
     *
     * @return string Prompt de sistema completo con contexto de tickets.
     */
    public function instructions(): string
    {
        return <<<PROMPT
Eres un asistente especializado en análisis de tickets de soporte técnico MSP.
Responde siempre en español, de forma clara y concisa.
Cuando des números o estadísticas, sé preciso.
Si te piden buscar un ticket específico, busca por número, título o cliente.
No inventes datos que no estén en el contexto.

DATOS DE TICKETS DISPONIBLES:
{$this->ticketContext}
PROMPT;
    }

    /**
     * Retorna el historial de mensajes previos de la conversación.
     *
     * Esta implementación no mantiene historial (stateless). Para soporte
     * de conversaciones multi-turno con historial, usar {@see OvniMspAgent}.
     *
     * @return iterable<\Laravel\Ai\Messages\Message> Lista vacía de mensajes.
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Retorna las herramientas disponibles para el agente.
     *
     * Esta implementación no expone herramientas (function calling desactivado).
     *
     * @return iterable<mixed> Lista vacía de herramientas.
     */
    public function tools(): iterable
    {
        return [];
    }
}