<?php

namespace App\Entity\Feature\Presentationpages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity]
#[ORM\Table(name: 'presentationpage_elements')]
class PresentationpageElement
{
    public function __construct(
        PresentationpageElementVariant $elementVariant,
        int                            $position = 0
    )
    {
        $this->elementVariant = $elementVariant;
        $this->setPosition($position);
    }


    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function resetId(): void
    {
        $this->id = null;
    }


    #[ORM\ManyToOne(targetEntity: Presentationpage::class, cascade: ['persist'], inversedBy: 'presentationpageElements')]
    #[ORM\JoinColumn(name: 'presentationpages_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Presentationpage $presentationpage;

    public function getPresentationpage(): ?Presentationpage
    {
        return $this->presentationpage;
    }

    public function setPresentationpage(?Presentationpage $presentationpage): void
    {
        $this->presentationpage = $presentationpage;
    }


    #[ORM\Column(type: 'string', nullable: false, enumType: PresentationpageElementVariant::class)]
    private PresentationpageElementVariant $elementVariant = PresentationpageElementVariant::Headline;

    public function getElementVariant(): PresentationpageElementVariant
    {
        return $this->elementVariant;
    }

    public function setElementVariant(PresentationpageElementVariant $elementVariant): void
    {
        $this->elementVariant = $elementVariant;
    }


    #[ORM\Column(type: 'string', nullable: false, enumType: PresentationpageElementHorizontalPosition::class)]
    private PresentationpageElementHorizontalPosition $elementHorizontalPosition = PresentationpageElementHorizontalPosition::Center;

    public function getElementHorizontalPosition(): PresentationpageElementHorizontalPosition
    {
        return $this->elementHorizontalPosition;
    }

    public function setElementHorizontalPosition(PresentationpageElementHorizontalPosition $elementHorizontalPosition): void
    {
        $this->elementHorizontalPosition = $elementHorizontalPosition;
    }


    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    private int $position;

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }


    #[Assert\Length(min: 3, max: 4)]
    #[ORM\Column(type: 'text', length: 32768, nullable: true)]
    private ?string $textContent = null;

    public function getTextContent(): ?string
    {
        return $this->textContent;
    }

    public function setTextContent(?string $textContent): void
    {
        $this->textContent = $textContent;
    }
}
