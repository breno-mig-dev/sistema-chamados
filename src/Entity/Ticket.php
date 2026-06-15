<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TicketRepository::class)]
#[ORM\Table(name: 'tickets')]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(type: 'string', enumType: TicketStatus::class)]
    private TicketStatus $status = TicketStatus::Open;

    #[ORM\Column(type: 'string', enumType: TicketPriority::class)]
    private TicketPriority $priority = TicketPriority::Medium;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ticketsAsRequester')]
    #[ORM\JoinColumn(nullable: false)]
    private User $requester;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ticketsAsTechnician')]
    private ?User $technician = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'ticket', cascade: ['remove'])]
    private Collection $comments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function getStatus(): TicketStatus { return $this->status; }
    public function setStatus(TicketStatus $status): self { $this->status = $status; return $this; }
    public function getPriority(): TicketPriority { return $this->priority; }
    public function setPriority(TicketPriority $priority): self { $this->priority = $priority; return $this; }
    public function getRequester(): User { return $this->requester; }
    public function setRequester(User $requester): self { $this->requester = $requester; return $this; }
    public function getTechnician(): ?User { return $this->technician; }
    public function setTechnician(?User $technician): self { $this->technician = $technician; return $this; }
    public function getCategory(): Category { return $this->category; }
    public function setCategory(Category $category): self { $this->category = $category; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(): self { $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function getComments(): Collection { return $this->comments; }
}
