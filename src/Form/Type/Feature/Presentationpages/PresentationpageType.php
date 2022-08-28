<?php

namespace App\Form\Type\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\Presentationpage;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

class PresentationpageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'label' => 'feature.presentationpages.editor.edit_form.label_title',
                    'trim' => false,
                ],
            )

            ->add(
                'bgColor',
                TextType::class,
                [
                    'label' => 'feature.presentationpages.editor.edit_form.label_bg_color'
                ],
            )

            ->add(
                'textColor',
                TextType::class,
                [
                    'label' => 'feature.presentationpages.editor.edit_form.label_text_color'
                ],
            )

            ->add(
                'presentationpageElements',
                CollectionType::class,
                [
                    'entry_type' => PresentationpageElementType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Presentationpage::class,
        ]);
    }
}
