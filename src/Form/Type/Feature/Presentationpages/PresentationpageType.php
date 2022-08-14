<?php

namespace App\Form\Type\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\Presentationpage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PresentationpageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'trim' => false,
                    'empty_data' => ''
                ]
            )

            ->add(
                'welcomeText',
                TextareaType::class,
                [
                    'trim' => false,
                    'empty_data' => ''
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Presentationpage::class,
        ]);
    }
}