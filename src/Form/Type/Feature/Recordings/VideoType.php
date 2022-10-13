<?php

namespace App\Form\Type\Feature\Recordings;

use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Entity\Feature\Recordings\Video;
use App\Service\Feature\Presentationpages\PresentationpagesService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;


class VideoType extends AbstractType
{
    private PresentationpagesService $presentationpagesService;

    private Security $security;


    public function __construct(
        PresentationpagesService $presentationpagesService,
        Security $security
    )
    {
        $this->presentationpagesService = $presentationpagesService;
        $this->security = $security;
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array                $options
    ): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            $form->add(
                'title',
                TextType::class,
                [
                    'label' => 'feature.videos.editor.edit_form.label_title',
                    'trim' => false,
                    'empty_data' => ''
                ],
            );

            $form->add(
                'videoOnlyPresentationpageTemplate',
                EntityType::class,
                [
                    'label' => 'feature.videos.editor.edit_form.label_title',
                    'class' => Presentationpage::class,
                    'choices' => $this
                        ->presentationpagesService
                        ->getVideoOnlyPresentationpageTemplatesForUser($this->security->getUser()),
                    'choice_value' => 'id',
                    'choice_label' => 'title'
                ],
            );
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Video::class,
            ]
        );
    }
}
