<?php

namespace App\Repository;

use App\Entity\Orders;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Orders>
 *
 * @method Orders|null find($id, $lockMode = null, $lockVersion = null)
 * @method Orders|null findOneBy(array $criteria, array $orderBy = null)
 * @method Orders[]    findAll()
 * @method Orders[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Orders::class);
    }

    public function save(Orders $order, bool $flush = true): void
    {
        $this->getEntityManager()->persist($order);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Orders $order, bool $flush = true): void
    {
        $this->getEntityManager()->remove($order);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Example: find all orders of a specific user
    public function findByUser(\App\Entity\User $user): array
        {
            return $this->createQueryBuilder('o')
                ->andWhere('o.user = :user')
                ->setParameter('user', $user)
                ->orderBy('o.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

    // Example: find recent orders (last X days)
    public function findRecent(int $days = 7): array
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('o')
            ->andWhere('o.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
