<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       $builder
    ->add('username')
    ->add('email')
    ->add('roles', ChoiceType::class, [
        'choices' => [
            'User' => 'ROLE_USER',
            'Admin' => 'ROLE_ADMIN',
            'Staff' => 'ROLE_STAFF',
        ],
        'multiple' => false,
        'expanded' => false,
        'label' => 'Roles',
    ])
    ->add('password', PasswordType::class, [
        'required' => false,
        'mapped' => false,
        'attr' => ['placeholder' => 'Leave blank to keep current password'],
    ])
    ->add('name')
    ->add('isActive', CheckboxType::class, [
        'required' => false,
        'label' => 'Active',
    ]);

// Add transformer **after** adding the field
$builder->get('roles')
    ->addModelTransformer(new CallbackTransformer(
        function ($rolesArray) {
            return count($rolesArray) ? $rolesArray[0] : null;
        },
        function ($roleString) {
            return [$roleString];
        }
    ));

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
