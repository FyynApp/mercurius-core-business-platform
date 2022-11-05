<?php

namespace App\VideoBasedMarketing\Account\Presentation\Form\Type;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class SignUpType
    extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array                $options
    ): void
    {
        $builder
            ->add('email')
            ->add(
                'agreeTerms',
                CheckboxType::class, [
                    'translation_domain' => 'videobasedmarketing.account',
                    'mapped' => false,
                    'constraints' => [
                        new IsTrue(
                            [
                                'message' => 'validation.agree_terms.must_be_true',
                            ]
                        ),
                    ],
                ]
            )
            ->add(
                'plainPassword',
                PasswordType::class, [
                    // instead of being set onto the object directly,
                    // this is read and encoded in the controller
                    'translation_domain' => 'videobasedmarketing.account',
                    'mapped' => false,
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new NotBlank(
                            [
                                'message' => 'validation.password.not_blank',
                            ]
                        ),
                        new Length(
                            [
                                'min' => 6,
                                'minMessage' => 'sign_up.validation.password.min_length',
                                // max length allowed by Symfony for security reasons
                                'max' => 4096,
                            ]
                        ),
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class,
            ]
        );
    }
}
