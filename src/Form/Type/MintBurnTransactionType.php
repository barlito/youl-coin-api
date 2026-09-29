<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Transaction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Shared by the Mint and Burn forms on the bank wallet page: amount in coins + a mandatory reason, no wallet fields
/**
 * @extends AbstractType<Transaction>
 */
class MintBurnTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', null, [
                'label' => 'Amount (in coins)',
                'attr' => [
                    'placeholder' => 'Amount',
                ],
            ])

            ->add('reason', TextareaType::class, [
                'label' => 'Reason',
                'attr' => [
                    'placeholder' => 'Reason',
                ],
            ])

            ->add('save', SubmitType::class, [
                'label' => $options['submit_label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            // type/walletFrom/walletTo are filled server-side after submit: the form must not validate the entity as-is
            'validation_groups' => false,
        ]);

        $resolver->setRequired('submit_label');
        $resolver->setAllowedTypes('submit_label', 'string');
    }
}
