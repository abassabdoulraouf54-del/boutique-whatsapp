<?php

namespace App\Form;

use App\Entity\Boutique;
use App\Entity\Categorie;
use App\Entity\Produit;
use App\Repository\CategorieRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $boutique = $options['boutique'];

        $builder
            ->add('nom', TextType::class, ['label' => 'Nom du produit'])
            ->add('description', TextareaType::class, ['required' => false])
            ->add('prix', IntegerType::class, ['label' => 'Prix (FCFA)', 'attr' => ['min' => 0]])
            ->add('stock', IntegerType::class, ['label' => 'Quantité en stock', 'attr' => ['min' => 0]])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie',
                'constraints' => [new NotNull(message: 'Choisissez une catégorie.')],
                'query_builder' => fn (CategorieRepository $r) => $r->createQueryBuilder('c')
                    ->andWhere('c.boutique = :b')
                    ->setParameter('b', $boutique)
                    ->orderBy('c.nom', 'ASC'),
            ])
            ->add('actif', CheckboxType::class, ['required' => false, 'label' => 'Visible dans la boutique'])
            ->add('imageFile', FileType::class, [
                'label' => 'Photo du produit (JPG, PNG ou WebP)',
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
                'constraints' => [new File(
                    maxSize: '5M',
                    mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    mimeTypesMessage: 'Envoyez une image JPG, PNG ou WebP.',
                    maxSizeMessage: 'La photo est trop lourde (5 Mo maximum).',
                )],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Produit::class]);
        $resolver->setRequired('boutique');
        $resolver->setAllowedTypes('boutique', Boutique::class);
    }
}
