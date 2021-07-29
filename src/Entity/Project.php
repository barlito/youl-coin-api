<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=ProjectRepository::class)
 * @method string getUserIdentifier()
 */
class Project implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    private ?string $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="ulid", unique=true)
     */
    private ?string $apiKey;

    /**
     * @ORM\OneToOne(targetEntity=Wallet::class, mappedBy="project", cascade={"persist"})
     */
    private ?Wallet $wallet;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getRoles(): array
    {
        return ["ROLE_API"];
    }

    public function getPassword()
    {
        return null;
    }

    public function getSalt()
    {
        return null;
    }

    public function eraseCredentials()
    {
        return null;
    }

    public function getUsername(): ?string
    {
        return $this->apiKey;
    }

    public function __call(string $name, array $arguments)
    {
        return $this->apiKey;
    }

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): self
    {
        // unset the owning side of the relation if necessary
        if ($wallet === null && $this->wallet !== null) {
            $this->wallet->setProject(null);
        }

        // set the owning side of the relation if necessary
        if ($wallet !== null && $wallet->getProject() !== $this) {
            $wallet->setProject($this);
        }

        $this->wallet = $wallet;

        return $this;
    }
}
