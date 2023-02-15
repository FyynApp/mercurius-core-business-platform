<?php

namespace App\VideoBasedMarketing\Account\Presentation\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class ClaimUnregisteredUserType
    extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array                $options
    ): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add(
                'plainPassword',
                PasswordType::class, [
                    'translation_domain' => 'videobasedmarketing.account',
                    'constraints' => [
                        new NotBlank(
                            [
                                'message' => $this->translator->trans(
                                    'sign_up.validation.password.min_length',
                                    [],
                                    'videobasedmarketing.account'
                                )
                            ]
                        ),
                        new Length(
                            [
                                'min' => 6,
                                'minMessage' => $this->translator->trans(
                                    'sign_up.validation.password.min_length',
                                    [],
                                    'videobasedmarketing.account'
                                ),
                                'max' => 4096,
                            ]
                        ),
                    ],
                ]
            );
    }
}
