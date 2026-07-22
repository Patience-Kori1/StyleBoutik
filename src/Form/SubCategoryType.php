<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\SubCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'sb-form-control',
                    'placeholder' => 'Ex: Chaussures homme',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'name',
                'label' => false,
                'attr' => [
                    'class' => 'sb-form-control',
                ],
                'placeholder' => 'Sélectionner une catégorie parente',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubCategory::class,
        ]);
    }
}
