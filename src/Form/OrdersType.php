<?php

namespace App\Form;

use App\Entity\Orders;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class OrdersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Only allow editing 'status'
        $builder
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'Pending',
                    'Approved' => 'Approved',
                    'Completed' => 'Completed',
                    'Cancelled' => 'Cancelled',
                ],
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => OrderItemType::class, // This is a form for OrderItem
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                // label is handled in the template; disable automatic label to avoid duplicate/leftover label
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Orders::class,
        ]);
    }
}
