<?php

declare(strict_types=1);

namespace App\Enum;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingUser = 'waiting_user';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::InProgress => 'Em Atendimento',
            self::WaitingUser => 'Aguardando Usuário',
            self::Resolved => 'Resolvido',
            self::Closed => 'Fechado',
        };
    }
}
