<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Form\Type;

use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;


class VideoType
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
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
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
                    'mainCtaText',
                    TextType::class,
                    [
                        'translation_domain' => 'videobasedmarketing.recordings',
                        'label' => 'video_manage_widget.form.label.main_cta_text',
                        'trim' => false,
                        'empty_data' => null
                    ],
                );

                $form->add(
                    'mainCtaLabel',
                    TextType::class,
                    [
                        'translation_domain' => 'videobasedmarketing.recordings',
                        'label' => 'video_manage_widget.form.label.main_cta_label',
                        'trim' => false,
                        'empty_data' => null
                    ],
                );

                $form->add(
                    'mainCtaUrl',
                    TextType::class,
                    [
                        'translation_domain' => 'videobasedmarketing.recordings',
                        'label' => 'video_manage_widget.form.label.main_cta_url',
                        'trim' => true,
                        'empty_data' => null
                    ],
                );


                $form->add(
                    'calendlyText',
                    TextType::class,
                    [
                        'translation_domain' => 'videobasedmarketing.recordings',
                        'label' => 'video_manage_widget.form.label.calendly_text',
                        'trim' => false,
                        'empty_data' => null
                    ],
                );


                $form->add(
                    'calendlyUrl',
                    TextType::class,
                    [
                        'translation_domain' => 'videobasedmarketing.recordings',
                        'label' => 'video_manage_widget.form.label.calendly_url',
                        'trim' => true,
                        'empty_data' => null
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
            }
        );
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
