<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait IdUlidTrait
{
    /**
     * @ORM\Id
     * @ORM\Column(type="ulid", unique=true, name="id")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UlidGenerator::class)
     *
     * @Assert\Uuid()
     */
    private string $id;

    public function getId(): string
    {
        return $this->id;
    }
}
