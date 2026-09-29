<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EconomySettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

// Singleton entity: only ever looked up by its fixed id, no findBy/findOneBy needed
/**
 * @method EconomySettings|null find($id, $lockMode = null, $lockVersion = null)
 *
 * @extends ServiceEntityRepository<EconomySettings>
 */
class EconomySettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EconomySettings::class);
    }
}
