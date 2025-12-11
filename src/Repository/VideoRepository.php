<?php

namespace App\Repository;

use App\Entity\Video;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Video>
 */
class VideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Video::class);
    }

    /**
     * Recherche des vidéos par titre ou description
     * @return Video[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('v')
            ->where('LOWER(v.title) LIKE LOWER(:query)')
            ->orWhere('LOWER(v.description) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les vidéos des propriétaires spécifiés
     * @param array $owners Liste des utilisateurs
     * @return Video[]
     */
    public function findByOwners(array $owners): array
    {
        if (empty($owners)) {
            return [];
        }

        return $this->createQueryBuilder('v')
            ->where('v.owner IN (:owners)')
            ->setParameter('owners', $owners)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
