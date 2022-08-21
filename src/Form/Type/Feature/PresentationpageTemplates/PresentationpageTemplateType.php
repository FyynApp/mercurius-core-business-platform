<?php

namespace App\Form\Type\Feature\PresentationpageTemplates;

use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplateElementVariant;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

class PresentationpageTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'title',
                TextType::class,
                ['label' => 'feature.presentationpage_templates.add_form.formfield.title'],
            )

            ->add('bgColor', TextType::class)

            ->add('textColor', TextType::class)

            ->add(
                'presentationpageTemplateElements',
                CollectionType::class,
                ['entry_type' => PresentationpageTemplateElementType::class]
            )
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var PresentationpageTemplate $presentationpageTemplate */
            $presentationpageTemplate = $event->getData();
            $form = $event->getForm();

            foreach ($presentationpageTemplate->getPresentationpageTemplateElements() as $element) {
                if ($element->getElementVariant() === PresentationpageTemplateElementVariant::Headline) {
                }
            }

            if (!$presentationpageTemplate || null === $presentationpageTemplate->getId()) {
                $form->add('name', TextType::class);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PresentationpageTemplate::class,
        ]);
    }
}
