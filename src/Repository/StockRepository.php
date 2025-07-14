<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stock>
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    //    /**
    //     * @return Stock[] Returns an array of Stock objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Stock
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findStockForArticleAndChantier($articleId, $chantierId): ?\App\Entity\Stock
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.article = :articleId')
            ->andWhere('s.chantier = :chantierId')
            ->setParameter('articleId', $articleId)
            ->setParameter('chantierId', $chantierId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findStockForMachineAndChantier($machineId, $chantierId): ?\App\Entity\Stock
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.machine = :machineId')
            ->andWhere('s.chantier = :chantierId')
            ->setParameter('machineId', $machineId)
            ->setParameter('chantierId', $chantierId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllStockForChantier($chantierId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.chantier = :chantierId')
            ->setParameter('chantierId', $chantierId)
            ->getQuery()
            ->getResult();
    }
}
