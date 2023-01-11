<?php

namespace App\VideoBasedMarketing\Mailings\Presentation\Form\Type;

use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;


class VideoMailingType
    extends AbstractType
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
                    'translation_domain' => 'videobasedmarketing.recordings',
                    'label' => 'video_manage_widget.edit_modal.label.title',
                    'trim' => false,
                    'empty_data' => ''
                ],
            );

            $form->add(
                'videoOnlyPresentationpageTemplate',
                EntityType::class,
                [
                    'translation_domain' => 'videobasedmarketing.recordings',
                    'label' => 'video_manage_widget.edit_modal.label.video_only_presentationpage_template',
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
                'data_class' => VideoMailing::class,
            ]
        );
    }
}
