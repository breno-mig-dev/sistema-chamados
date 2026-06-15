<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome',
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Permissões (Roles)',
                'choices' => [
                    'Usuário' => 'ROLE_USER',
                    'Técnico' => 'ROLE_TECHNICIAN',
                    'Administrador' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Senha',
                'mapped' => false,
                'required' => $options['require_password'],
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => $options['require_password'] ? [
                    new NotBlank([
                        'message' => 'Por favor insira uma senha',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'A senha deve ter no mínimo {{ limit }} caracteres',
                        'max' => 4096,
                    ]),
                ] : [],
                'help' => $options['require_password'] ? '' : 'Deixe em branco para manter a senha atual',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => true,
        ]);
    }
}
