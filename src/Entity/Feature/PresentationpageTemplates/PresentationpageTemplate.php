<?php

namespace App\Entity\Feature\PresentationpageTemplates;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Presentationpages\Presentationpage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'presentationpage_templates')]
class PresentationpageTemplate
{
    const ALLOWED_BG_COLORS = [
        '#ffffff',
        '#ffebee',
        '#fce4ec',
        '#f3e5f5',
        '#ede7f6',
        '#e8eaf6',
        '#e3f2fd',
        '#e0f7fa',
        '#e0f2f1',
        '#e8f5e9',
        '#f1f8e9',
        '#f9fbe7',
        '#fffde7',
        '#fff8e1',
        '#fff3e0',
        '#fbe9e7',
        '#efebe9',
        '#fafafa',
        '#eceff1',
        '#424242',
        '#37474f',
    ];

    const ALLOWED_TEXT_COLORS = [
        '#000000',
        '#b71c1c',
        '#880e4f',
        '#4a148c',
        '#311b92',
        '#1a237e',
        '#0d47a1',
        '#006064',
        '#004d40',
        '#1b5e20',
        '#33691e',
        '#827717',
        '#f57f17',
        '#ff6f00',
        '#e65100',
        '#bf360c',
        '#3e2723',
        '#212121',
        '#263238',
        '#fafafa',
        '#eceff1',
    ];


    public function __construct()
    {
        $this->presentationpages = new ArrayCollection();
        $this->presentationpageTemplateElements = new ArrayCollection();
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


    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'presentationpageTemplates')]
    #[ORM\JoinColumn(name: 'users_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }


    #[Assert\NotBlank]
    #[Assert\Length(max: 256)]
    #[ORM\Column(type: 'string', length: 256, unique: false, nullable: true)]
    private ?string $title;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }


    #[ORM\Column(type: 'string', length: 7, unique: false, nullable: false)]
    private string $bgColor = self::ALLOWED_BG_COLORS[0];

    public function getBgColor(): string
    {
        return $this->bgColor;
    }

    public function setBgColor(string $bgColor): void
    {
        $this->bgColor = $bgColor;
    }


    #[ORM\Column(type: 'string', length: 7, unique: false, nullable: false)]
    private string $textColor = self::ALLOWED_TEXT_COLORS[0];

    public function getTextColor(): string
    {
        return $this->textColor;
    }

    public function setTextColor(string $textColor): void
    {
        $this->textColor = $textColor;
    }


    /** @var PresentationpageTemplateElement[]|Collection */
    #[ORM\OneToMany(mappedBy: 'presentationpageTemplate', targetEntity: PresentationpageTemplateElement::class, cascade: ['persist'])]
    private Collection $presentationpageTemplateElements;

    /**
     * @return PresentationpageTemplateElement[]|Collection
     * @throws Exception
     */
    public function getPresentationpageTemplateElements(): Collection
    {
        $iterator = $this->presentationpageTemplateElements->getIterator();
        $iterator->uasort(function (PresentationpageTemplateElement $first, PresentationpageTemplateElement $second) {
            return $first->getPosition() > $second->getPosition() ? 1 : -1;
        });
        return new ArrayCollection($iterator->getArrayCopy());
    }

    public function addPresentationpageTemplateElement(PresentationpageTemplateElement $element): void
    {
        $element->setPresentationpageTemplate($this);
        $this->presentationpageTemplateElements->add($element);
    }

    public function removePresentationpageTemplateElement(PresentationpageTemplateElement $elementToRemove): void
    {
        $this->presentationpageTemplateElements->removeElement($elementToRemove);
    }

    /** @var Presentationpage[]|Collection */
    #[ORM\OneToMany(mappedBy: 'presentationpageTemplate', targetEntity: Presentationpage::class, cascade: ['persist'])]
    private Collection $presentationpages;

    /**
     * @return Presentationpage[]|Collection
     */
    public function getPresentationpages(): Collection
    {
        return $this->presentationpages;
    }
}
