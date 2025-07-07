<?php

namespace App\Repository;

use App\Entity\Machine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Machine>
 */
class MachineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Machine::class);
    }
    
    public function save(Machine $machine, bool $flush = true): void
    {
        $this->getEntityManager()->persist($machine);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    
    public function remove(Machine $machine, bool $flush = true): void
    {
        $this->getEntityManager()->remove($machine);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    
    /**
     * @return Machine[] Returns an array of Machine objects
     */
    public function findBySearchTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.categorie', 'c')
            ->where('m.nom LIKE :searchTerm')
            ->orWhere('m.code LIKE :searchTerm')
            ->orWhere('c.nom LIKE :searchTerm')
            ->setParameter('searchTerm', '%'.$searchTerm.'%')
            ->orderBy('m.nom', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return Machine[] Returns an array of Machine objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Machine
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
