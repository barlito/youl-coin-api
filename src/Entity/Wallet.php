<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Api\CreateWalletController;
use App\Repository\WalletRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\WalletTypeEnum;

/**
 * @ORM\Entity(repositoryClass=WalletRepository::class)
 */
#[ApiResource(
    collectionOperations: [
        'get',
        'post' => [
            'method' => 'POST',
            'path' => '/wallets',
            'controller' => CreateWalletController::class,
        ],
    ],
    itemOperations: ['get'],
)]
class Wallet
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
    private string $amount;
    
    /**
     * @ORM\OneToOne(targetEntity=DiscordUser::class, inversedBy="wallet")
     */
    private ?DiscordUser $discordUser;
    
    /**
     * @ORM\Column(type="string")
     *
     * @Assert\Choice(WalletTypeEnum::VALUES)
     */
    private string $type;
    
    public function getId(): ?string
    {
        return $this->id;
    }
    
    public function getAmount(): string
    {
        return $this->amount;
    }
    
    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        
        return $this;
    }
    
    public function getDiscordUser(): ?DiscordUser
    {
        return $this->discordUser;
    }
    
    public function setDiscordUser(?DiscordUser $discordUser): self
    {
        $this->discordUser = $discordUser;
        
        return $this;
    }
    
    public function getType(): string
    {
        return $this->type;
    }
    
    public function setType(string $type): self
    {
        $this->type = $type;
        
        return $this;
    }
}
