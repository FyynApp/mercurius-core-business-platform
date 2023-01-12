<?php

namespace App\VideoBasedMarketing\Mailings\Presentation\Form\Type;

use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class VideoMailingType
    extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array                $options
    ): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            $form->add(
                'receiverMailAddress',
                EmailType::class,
                [
                    'translation_domain' => 'videobasedmarketing.mailings',
                    'label' => 'video_mailing_editor.form_labels.receiver_mail_address',
                    'trim' => false,
                    'empty_data' => ''
                ],
            );

            $form->add(
                'subject',
                TextType::class,
                [
                    'translation_domain' => 'videobasedmarketing.mailings',
                    'label' => 'video_mailing_editor.form_labels.subject',
                    'trim' => false,
                    'empty_data' => ''
                ],
            );

            $form->add(
                'bodyAboveVideo',
                TextareaType::class,
                [
                    'translation_domain' => 'videobasedmarketing.mailings',
                    'label' => 'video_mailing_editor.form_labels.body_above_video',
                    'trim' => false,
                    'empty_data' => ''
                ],
            );

            $form->add(
                'bodyBelowVideo',
                TextareaType::class,
                [
                    'translation_domain' => 'videobasedmarketing.mailings',
                    'label' => 'video_mailing_editor.form_labels.body_below_video',
                    'trim' => false,
                    'empty_data' => ''
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
