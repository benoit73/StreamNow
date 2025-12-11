<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Video;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Url;

class VideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la vidéo',
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200',
                    'placeholder' => 'Ex: Mon super tutoriel...',
                ],
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300 mb-2'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un titre'),
                    new Length(min: 3, max: 255, minMessage: 'Le titre doit contenir au moins {{ limit }} caractères'),
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionnez une catégorie',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200',
                ],
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300 mb-2'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200 resize-none',
                    'placeholder' => 'Décrivez votre vidéo...',
                    'rows' => 4,
                ],
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300 mb-2'],
            ])
            ->add('url', TextType::class, [
                'label' => 'URL de la vidéo',
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200',
                    'placeholder' => 'https://www.youtube.com/watch?v=...',
                ],
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300 mb-2'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer l\'URL de la vidéo'),
                    new Url(message: 'Veuillez entrer une URL valide'),
                ],
            ])
            ->add('thumbnail', TextType::class, [
                'label' => 'URL de la miniature',
                'attr' => [
                    'class' => 'w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200',
                    'placeholder' => 'https://exemple.com/image.jpg',
                ],
                'label_attr' => ['class' => 'block text-sm font-medium text-gray-300 mb-2'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer l\'URL de la miniature'),
                    new Url(message: 'Veuillez entrer une URL valide'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Video::class,
        ]);
    }
}
