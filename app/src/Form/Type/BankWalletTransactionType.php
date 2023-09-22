<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\DTO\BankWallet\BankWalletTransactionDTO;
use App\Entity\Wallet;
use App\Enum\TransactionTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BankWalletTransactionType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', null, [
                'label' => 'Amount',
                'attr' => [
                    'placeholder' => 'Amount',
                ],
            ])

            ->add('transactionType', ChoiceType::class, [
                'choices' => array_flip(TransactionTypeEnum::VALUES),
                'label' => 'Transaction Type',
            ])

            ->add('walletFrom', EntityType::class, [
                'class' => Wallet::class,
                'label' => 'Wallet From',
                'attr' => [
                    'placeholder' => 'WalletFrom',
                ],
            ])

            ->add('walletTo', EntityType::class, [
                'class' => Wallet::class,
                'label' => 'Wallet To',
                'attr' => [
                    'placeholder' => 'WalletTo',
                ],
            ])

            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BankWalletTransactionDTO::class,
        ]);
    }
}
