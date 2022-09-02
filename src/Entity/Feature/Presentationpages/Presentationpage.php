<?php

namespace App\Entity\Feature\Presentationpages;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\Video;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'presentationpages')]
class Presentationpage
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
        $this->presentationpageElements = new ArrayCollection();
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


    #[ORM\Column(type: 'string', nullable: false, enumType: PresentationpageType::class)]
    private PresentationpageType $type = PresentationpageType::Page;

    public function getType(): PresentationpageType
    {
        return $this->type;
    }

    public function setType(PresentationpageType $type): void
    {
        $this->type = $type;
    }


    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'presentationpages')]
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


    #[ORM\ManyToOne(targetEntity: Video::class, cascade: ['persist'], inversedBy: 'presentationpages')]
    #[ORM\JoinColumn(name: 'videos_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Video $video;

    public function getVideo(): ?Video
    {
        return $this->video;
    }

    public function setVideo(?Video $video): void
    {
        if (!is_null($video) && $this->getType() === PresentationpageType::Template) {
            throw new InvalidArgumentException('Cannot set video on a presentationpage that acts as a template!');
        }
        $this->video = $video;
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


    #[Assert\NotBlank]
    #[Assert\Length(min: 7, max: 7)]
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

    #[Assert\NotBlank]
    #[Assert\Length(min: 7, max: 7)]
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


    /** @var PresentationpageElement[]|Collection */
    #[ORM\OneToMany(mappedBy: 'presentationpage', targetEntity: PresentationpageElement::class, cascade: ['persist'])]
    private Collection $presentationpageElements;

    /**
     * @return PresentationpageElement[]|Collection
     * @throws Exception
     */
    public function getPresentationpageElements(): Collection
    {
        $iterator = $this->presentationpageElements->getIterator();
        $iterator->uasort(function (PresentationpageElement $first, PresentationpageElement $second) {
            return $first->getPosition() > $second->getPosition() ? 1 : -1;
        });
        return new ArrayCollection($iterator->getArrayCopy());
    }

    public function addPresentationpageElement(PresentationpageElement $element): void
    {
        $element->setPresentationpage($this);
        $this->presentationpageElements->add($element);
    }

    public function removePresentationpageElement(PresentationpageElement $elementToRemove): void
    {
        if (!$this->presentationpageElements->contains($elementToRemove)) {
            return;
        }
        $this->presentationpageElements->removeElement($elementToRemove);
        $elementToRemove->setPresentationpage(null);
    }

    public function hasMercuriusVideoVariantElement(): bool
    {
        foreach ($this->getPresentationpageElements() as $element) {
            if ($element->getElementVariant() === PresentationpageElementVariant::MercuriusVideo) {
                return true;
            }
        }
        return false;
    }
}
