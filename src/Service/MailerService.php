<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Ticket;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailerService
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function sendStatusChangedNotification(Ticket $ticket): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@helpdesk.local', 'HelpDesk Support'))
            ->to(new Address($ticket->getRequester()->getEmail(), $ticket->getRequester()->getName()))
            ->subject(sprintf('Atualização no chamado #%d', $ticket->getId()))
            ->htmlTemplate('email/ticket_status_changed.html.twig')
            ->context([
                'ticket' => $ticket,
            ]);

        $this->mailer->send($email);
    }
}
