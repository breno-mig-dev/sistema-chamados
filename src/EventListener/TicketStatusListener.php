<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Ticket;
use App\Service\MailerService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Ticket::class)]
class TicketStatusListener
{
    public function __construct(private MailerService $mailer)
    {
    }

    public function preUpdate(Ticket $ticket, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('status')) {
            $this->mailer->sendStatusChangedNotification($ticket);
        }
    }
}
