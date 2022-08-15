<?php

namespace App\Form\Type\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Service\Feature\PresentationpageTemplates\PresentationpageTemplatesService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PresentationpageType extends AbstractType
{
    private PresentationpageTemplatesService $presentationpageTemplatesService;

    public function __construct(PresentationpageTemplatesService $presentationpageTemplatesService)
    {
        $this->presentationpageTemplatesService = $presentationpageTemplatesService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Presentationpage $presentationpage */
        $presentationpage = $options['data'];

        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'trim' => false,
                    'empty_data' => ''
                ]
            )

            ->add(
                'welcomeText',
                TextareaType::class,
                [
                    'trim' => false,
                    'empty_data' => ''
                ]
            )

            ->add(
                'calendlyEmbedCode',
                TextareaType::class
            )

            ->add(
                'presentationpageTemplate',
                EntityType::class,
                [
                    'class' => PresentationpageTemplate::class,
                    'expanded' => true,
                    'multiple' => false,
                    'choice_label' => 'title',
                    'choices' => $this
                        ->presentationpageTemplatesService
                        ->getTemplatesForUser($presentationpage->getUser()),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Presentationpage::class
        ]);
    }
}
