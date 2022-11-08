<?php

namespace App\VideoBasedMarketing\Presentationpages\Presentation\Form\Type;

use App\VideoBasedMarketing\Presentationpages\Domain\Entity\PresentationpageElement;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageElementVariant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class PresentationpageElementType
    extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array                $options
    ): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA, function (FormEvent $event) {
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

                case PresentationpageElementVariant::Divider:
                    $form->add(
                        'textContent',
                        HiddenType::class,
                        [
                            'required' => true,
                            'trim' => false,
                            'translation_domain' => 'videobasedmarketing.presentationpages',
                            'label' => 'editor.edit_form.label_element_variant.divider'
                        ]
                    );
                    break;

                case PresentationpageElementVariant::Headline:
                    $form->add(
                        'textContent',
                        TextType::class,
                        [
                            'required' => true,
                            'trim' => false,
                            'translation_domain' => 'videobasedmarketing.presentationpages',
                            'label' => 'editor.edit_form.label_element_variant.headline'
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
                            'translation_domain' => 'videobasedmarketing.presentationpages',
                            'label' => 'editor.edit_form.label_element_variant.paragraph'
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
                            'translation_domain' => 'videobasedmarketing.presentationpages',
                            'label' => 'editor.edit_form.label_element_variant.image_url'
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
                            'translation_domain' => 'videobasedmarketing.presentationpages',
                            'label' => 'editor.edit_form.label_element_variant.calendly_embed'
                        ]
                    );
                    break;
            }
        }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => PresentationpageElement::class,
            ]
        );
    }
}
