<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use App\Form\CommentType;
use App\Form\TicketType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ticket', name: 'ticket_')]
#[IsGranted('ROLE_USER')]
class TicketController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $tickets = $entityManager->getRepository(Ticket::class)->findBy(
            ['requester' => $user],
            ['updatedAt' => 'DESC']
        );

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
            'title' => 'Meus Chamados',
        ]);
    }

    #[Route('/manage', name: 'manage', methods: ['GET'])]
    #[IsGranted('ROLE_TECHNICIAN')]
    public function manage(EntityManagerInterface $entityManager): Response
    {
        $tickets = $entityManager->getRepository(Ticket::class)->findBy([], ['updatedAt' => 'DESC']);

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
            'title' => 'Gerenciar Chamados',
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket, [
            'submit_label' => 'Abrir chamado',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $ticket->setRequester($user);

            $entityManager->persist($ticket);
            $entityManager->flush();

            $this->addFlash('success', 'Chamado aberto com sucesso.');

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('view', subject: 'ticket')]
    public function show(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $comment->setTicket($ticket);
            $comment->setAuthor($user);
            $ticket->setUpdatedAt();

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Comentário adicionado.');

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'comment_form' => $commentForm,
            'statuses' => TicketStatus::cases(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('edit', subject: 'ticket')]
    public function edit(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TicketType::class, $ticket, [
            'submit_label' => 'Atualizar chamado',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setUpdatedAt();
            $entityManager->flush();

            $this->addFlash('success', 'Chamado atualizado com sucesso.');

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/status', name: 'change_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('change_status', subject: 'ticket')]
    public function changeStatus(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('change_status_'.$ticket->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $status = TicketStatus::tryFrom((string) $request->request->get('status'));

        if (!$status instanceof TicketStatus) {
            $this->addFlash('error', 'Status inválido.');

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$ticket->getTechnician() && $user instanceof User) {
            $ticket->setTechnician($user);
        }

        $ticket->setStatus($status);
        $ticket->setUpdatedAt();
        $entityManager->flush();

        $this->addFlash('success', 'Status do chamado atualizado.');

        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('delete', subject: 'ticket')]
    public function delete(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_'.$ticket->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $redirectRoute = $this->isGranted('ROLE_TECHNICIAN') ? 'ticket_manage' : 'ticket_index';

        $entityManager->remove($ticket);
        $entityManager->flush();

        $this->addFlash('success', 'Chamado removido com sucesso.');

        return $this->redirectToRoute($redirectRoute);
    }
}
