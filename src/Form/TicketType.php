<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Ticket;
use App\Enum\TicketPriority;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Título',
                'attr' => [
                    'placeholder' => 'Resumo do problema',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Categoria',
                'placeholder' => 'Selecione uma categoria',
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => $this->priorityChoices(),
                'label' => 'Prioridade',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descrição',
                'attr' => [
                    'rows' => 8,
                    'placeholder' => 'Descreva o problema, impacto e passos já tentados',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['submit_label'],
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'submit_label' => 'Salvar',
        ]);
    }

    private function priorityChoices(): array
    {
        $choices = [];

        foreach (TicketPriority::cases() as $priority) {
            $choices[$priority->label()] = $priority;
        }

        return $choices;
    }
}
