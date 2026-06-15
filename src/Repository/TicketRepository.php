<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 *
 * @method Ticket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticket[]    findAll()
 * @method Ticket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * @return array<string, int>
     */
    public function getTicketsCountByStatus(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as count')
            ->groupBy('t.status');

        $result = $qb->getQuery()->getArrayResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']->value] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * @return Ticket[]
     */
    public function getOldOpenTickets(int $days = 7): array
    {
        $date = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('t')
            ->andWhere('t.status NOT IN (:closedStatuses)')
            ->andWhere('t.createdAt < :date')
            ->setParameter('closedStatuses', ['resolved', 'closed']) // matching TicketStatus values
            ->setParameter('date', $date)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
