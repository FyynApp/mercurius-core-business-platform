<?php

namespace App\Form\Type\Feature\PresentationpageTemplates;

use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplateElement;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplateElementVariant;
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

class PresentationpageTemplateElementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var PresentationpageTemplateElement $presentationpageTemplateElement */
            $presentationpageTemplateElement = $event->getData();
            $form = $event->getForm();

            if (is_null($presentationpageTemplateElement)) {
                return;
            }

            $form
                ->add(
                    'position',
                    HiddenType::class
                );

            switch ($presentationpageTemplateElement->getElementVariant()) {

                case PresentationpageTemplateElementVariant::MercuriusVideo:
                    return;

                case PresentationpageTemplateElementVariant::Headline:
                    $form->add(
                        'textContent',
                        TextType::class,
                        [
                            'required' => true,
                            'trim' => false,
                            'label' => 'feature.presentationpage_templates.editor.edit_form.label_element_variant.headline'
                        ]
                    );
                    break;

                case PresentationpageTemplateElementVariant::Paragraph:
                    $form->add(
                        'textContent',
                        TextareaType::class,
                        [
                            'required' => true,
                            'trim' => false,
                            'label' => 'feature.presentationpage_templates.editor.edit_form.label_element_variant.paragraph'
                        ]
                    );
                    break;

                case PresentationpageTemplateElementVariant::ImageUrl:
                    $form->add(
                        'textContent',
                        UrlType::class,
                        [
                            'required' => true,
                            'trim' => false,
                            'label' => 'feature.presentationpage_templates.editor.edit_form.label_element_variant.image_url'
                        ]
                    );
                    break;

                case PresentationpageTemplateElementVariant::CalendlyEmbed:
                    $form->add(
                        'textContent',
                        TextareaType::class,
                        [
                            'required' => true,
                            'trim' => false,
                            'label' => 'feature.presentationpage_templates.editor.edit_form.label_element_variant.calendly_embed'
                        ]
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
