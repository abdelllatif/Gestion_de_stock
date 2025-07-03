<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Supprimer un article
     */
    public function remove(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Sauvegarder un article
     */
    public function save(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Rechercher des articles par nom ou référence
     */
    public function findBySearchTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.nom LIKE :search OR a.reference LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
