<?php

namespace App\Repository;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItem>
 *
 * @method OrderItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderItem[]    findAll()
 * @method OrderItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    public function save(OrderItem $orderItem, bool $flush = true): void
    {
        $this->getEntityManager()->persist($orderItem);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderItem $orderItem, bool $flush = true): void
    {
        $this->getEntityManager()->remove($orderItem);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Example: find all items of a specific order
    public function findByOrder(int $orderId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.order = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('oi.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Example: find all items of a specific product
    public function findByProduct(int $productId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.product = :productId')
            ->setParameter('productId', $productId)
            ->orderBy('oi.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
