<?php

namespace App\Repository;

use App\Entity\Bon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bon>
 */
class BonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bon::class);
    }

    /**
     * Trouver les bons pour DataTables avec filtrage, tri et pagination
     */
    public function findForDataTable(string $search = '', string $orderColumn = 'date', string $orderDir = 'DESC', int $start = 0, int $length = 10): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.chantier', 'c')
            ->addSelect('c');
        
        // Appliquer le filtre de recherche
        if (!empty($search)) {
            $qb->andWhere('b.numero_serie LIKE :search OR b.type LIKE :search OR c.nom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Appliquer le tri
        switch ($orderColumn) {
            case 'numeroSerie':
                $qb->orderBy('b.numero_serie', $orderDir);
                break;
            case 'type':
                $qb->orderBy('b.type', $orderDir);
                break;
            case 'date':
                $qb->orderBy('b.date', $orderDir);
                break;
            case 'chantier':
                $qb->orderBy('c.nom', $orderDir);
                break;
            case 'details':
                // On ne peut pas trier directement par le nombre de détails
                $qb->orderBy('b.date', $orderDir);
                break;
            default:
                $qb->orderBy('b.date', 'DESC');
        }
        
        // Appliquer la pagination
        $qb->setFirstResult($start)
           ->setMaxResults($length);
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Compter le nombre total de bons
     */
    public function countTotal(): int
    {
        try {
            return $this->createQueryBuilder('b')
                ->select('COUNT(b.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Compter le nombre de bons filtrés
     */
    public function countFiltered(string $search = ''): int
    {
        try {
            $qb = $this->createQueryBuilder('b')
                ->select('COUNT(b.id)')
                ->leftJoin('b.chantier', 'c');
            
            if (!empty($search)) {
                $qb->andWhere('b.numero_serie LIKE :search OR b.type LIKE :search OR c.nom LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
            }
            
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
