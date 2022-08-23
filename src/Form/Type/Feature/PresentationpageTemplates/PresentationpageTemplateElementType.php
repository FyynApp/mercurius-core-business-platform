<?php

namespace App\Form\Type\Feature\PresentationpageTemplates;

use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplateElement;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplateElementVariant;
use InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

class PresentationpageTemplateElementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'position',
                HiddenType::class
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var PresentationpageTemplateElement $presentationpageTemplateElement */
            $presentationpageTemplateElement = $event->getData();
            $form = $event->getForm();

            if (is_null($presentationpageTemplateElement)) {
                $form->add(
                    'textContent',
                    TextType::class
                );
                return;
            }

            switch ($presentationpageTemplateElement->getElementVariant()) {
                case PresentationpageTemplateElementVariant::Headline:
                    $form->add(
                        'textContent',
                        TextType::class
                    );
                    break;

                case PresentationpageTemplateElementVariant::Paragraph:
                    $form->add(
                        'textContent',
                        TextareaType::class
                    );
                    break;

                default:
                    throw new InvalidArgumentException($presentationpageTemplateElement->getElementVariant()->value);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PresentationpageTemplateElement::class,
        ]);
    }
}
