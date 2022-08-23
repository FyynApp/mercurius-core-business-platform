<?php

namespace App\Entity\Feature\PresentationpageTemplates;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'presentationpage_template_elements')]
class PresentationpageTemplateElement
{
    public function __construct()
    {
        $this->setElementVariant(PresentationpageTemplateElementVariant::Headline);
    }


    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'guid', unique: true)]
    private string $id;

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\ManyToOne(targetEntity: PresentationpageTemplate::class, cascade: ['persist'], inversedBy: 'presentationpageTemplateElements')]
    #[ORM\JoinColumn(name: 'presentationpage_templates_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PresentationpageTemplate $presentationpageTemplate;

    public function getPresentationpageTemplate(): PresentationpageTemplate
    {
        return $this->presentationpageTemplate;
    }

    public function setPresentationpageTemplate(PresentationpageTemplate $user): void
    {
        $this->presentationpageTemplate = $user;
    }


    #[ORM\Column(type: 'string', nullable: false, enumType: PresentationpageTemplateElementVariant::class)]
    private PresentationpageTemplateElementVariant $elementVariant;

    public function getElementVariant(): PresentationpageTemplateElementVariant
    {
        return $this->elementVariant;
    }

    public function setElementVariant(PresentationpageTemplateElementVariant $elementVariant): void
    {
        $this->elementVariant = $elementVariant;
    }


    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $position;

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = (int)$position;
    }


    #[Assert\Length(max: 32768)]
    #[ORM\Column(type: 'text', length: 32768, nullable: true)]
    private ?string $textContent;

    public function getTextContent(): ?string
    {
        return $this->textContent;
    }

    public function setTextContent(?string $textContent): void
    {
        $this->textContent = $textContent;
    }
}
