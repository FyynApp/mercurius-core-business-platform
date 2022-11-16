<?php

namespace App\VideoBasedMarketing\Account\Presentation\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;


class ClaimUnregisteredUserType
    extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array                $options
    ): void
    {
        $builder
            ->add('email', EmailType::class)
            /*
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
            )*/;
    }
}
