<?php

namespace App\VideoBasedMarketing\Presentationpages\Presentation\Form\Type;

use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class PresentationpageType
    extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array                $options
    ): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var \App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage $presentationpage */
            $presentationpage = $event->getData();
            $form = $event->getForm();

            $form->add(
                'title',
                TextType::class,
                [
                    'label' => 'editor.edit_form.label_title_' . $presentationpage->getType()->value,
                    'trim' => false,
                ],
            );
        }
        );

        $builder
            ->add(
                'presentationpageElements',
                CollectionType::class,
                [
                    'entry_type' => PresentationpageElementType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => \App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage::class,
            ]
        );
    }
}
