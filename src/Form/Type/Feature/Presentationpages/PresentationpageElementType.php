<?php

namespace App\Form\Type\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\PresentationpageElement;
use App\Entity\Feature\Presentationpages\PresentationpageElementVariant;
use InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

class PresentationpageElementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var PresentationpageElement $presentationpageElement */
            $presentationpageElement = $event->getData();
            $form = $event->getForm();

            if (is_null($presentationpageElement)) {
                return;
            }

            $form
                ->add(
                    'position',
                    HiddenType::class
                );

            switch ($presentationpageElement->getElementVariant()) {

                case PresentationpageElementVariant::MercuriusVideo:
                    return;

                case PresentationpageElementVariant::Headline:
                    $form->add(
                        'textContent',
                        TextType::class,
                        [
                            'required' => true,
                            'trim' => false,
                            'label' => 'feature.presentationpages.editor.edit_form.label_element_variant.headline'
                        ]
                    );
                    break;

                case PresentationpageElementVariant::Paragraph:
                    $form->add(
                        'textContent',
                        TextareaType::class,
                        [
                            'required' => true,
                            'trim' => false,
                            'label' => 'feature.presentationpages.editor.edit_form.label_element_variant.paragraph'
                        ]
                    );
                    break;

                case PresentationpageElementVariant::ImageUrl:
                    $form->add(
                        'textContent',
                        UrlType::class,
                        [
                            'required' => true,
                            'trim' => false,
                            'label' => 'feature.presentationpages.editor.edit_form.label_element_variant.image_url'
                        ]
                    );
                    break;

                case PresentationpageElementVariant::CalendlyEmbed:
                    $form->add(
                        'textContent',
                        UrlType::class,
                        [
                            'required' => true,
                            'trim' => true,
                            'label' => 'feature.presentationpages.editor.edit_form.label_element_variant.calendly_embed'
                        ]
                    );
                    break;

                default:
                    throw new InvalidArgumentException($presentationpageElement->getElementVariant()->value);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PresentationpageElement::class,
        ]);
    }
}
