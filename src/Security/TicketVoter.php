<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TicketVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const CHANGE_STATUS = 'change_status';

    public function __construct(private Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CHANGE_STATUS], true)
            && $subject instanceof Ticket;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !$subject instanceof Ticket) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::CHANGE_STATUS => $this->canChangeStatus(),
            default => false,
        };
    }

    private function canView(Ticket $ticket, User $user): bool
    {
        return $ticket->getRequester() === $user
            || $ticket->getTechnician() === $user
            || $this->security->isGranted('ROLE_TECHNICIAN');
    }

    private function canEdit(Ticket $ticket, User $user): bool
    {
        if ($this->security->isGranted('ROLE_TECHNICIAN')) {
            return true;
        }

        return $ticket->getRequester() === $user && $ticket->getStatus() === TicketStatus::Open;
    }

    private function canChangeStatus(): bool
    {
        return $this->security->isGranted('ROLE_TECHNICIAN');
    }

    private function canDelete(Ticket $ticket, User $user): bool
    {
        if ($this->security->isGranted('ROLE_TECHNICIAN')) {
            return true;
        }

        return $ticket->getRequester() === $user && $ticket->getStatus() === TicketStatus::Open;
    }
}
