<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository): Response
    {
        $statusCounts = $ticketRepository->getTicketsCountByStatus();
        $oldTickets = $ticketRepository->getOldOpenTickets(7); // older than 7 days

        return $this->render('admin/dashboard.html.twig', [
            'statusCounts' => $statusCounts,
            'oldTickets' => $oldTickets,
        ]);
    }
}
