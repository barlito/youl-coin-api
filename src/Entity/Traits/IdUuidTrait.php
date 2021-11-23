<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait IdUuidTrait
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid", unique=true, name="id")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     *
     * @Assert\Uuid()
     */
    private string $id;

    public function getId(): string
    {
        return $this->id;
    }
}
