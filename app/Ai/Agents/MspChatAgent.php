<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class MspChatAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    protected string $ticketContext = '';

    public function setTicketContext(string $context): static
    {
        $this->ticketContext = $context;
        return $this;
    }

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

    public function messages(): iterable
    {
        return [];
    }

    public function tools(): iterable
    {
        return [];
    }
}