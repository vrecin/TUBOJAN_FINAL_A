<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function filterLogs(?string $username, ?string $action, ?string $date, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('l');

        if ($username) {
            $qb->andWhere('l.username LIKE :username')
            ->setParameter('username', '%' . $username . '%');
        }

        if ($action) {
            $qb->andWhere('l.action = :action')
            ->setParameter('action', $action);
        }

        if ($date) {
            $qb->andWhere('DATE(l.createdAt) = :date')
            ->setParameter('date', $date);
        }

        return $qb->orderBy('l.createdAt', 'DESC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
    }

    public function countFilteredLogs(?string $username, ?string $action, ?string $date): int
    {
        $qb = $this->createQueryBuilder('l')
                ->select('COUNT(l.id)');

        if ($username) {
            $qb->andWhere('l.username LIKE :username')
            ->setParameter('username', '%' . $username . '%');
        }

        if ($action) {
            $qb->andWhere('l.action LIKE :action')
            ->setParameter('action', '%' . $action . '%');
        }


        if ($date) {
            $qb->andWhere('DATE(l.createdAt) = :date')
            ->setParameter('date', $date);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    //    /**
    //     * @return ActivityLog[] Returns an array of ActivityLog objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ActivityLog
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
