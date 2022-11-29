<?php

namespace App\VideoBasedMarketing\Presentationpages\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\BgColor;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\FgColor;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageBackground;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageCategory;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageElementVariant;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageType;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\TextColor;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use DateTime;
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
    implements UserOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->presentationpageElements = new ArrayCollection();
        $this->createdAt = DateAndTimeService::getDateTimeUtc();
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


    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }


    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $updatedAt;

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }


    public function createdOrUpdatedAt(): DateTime
    {
        if (is_null($this->updatedAt)) {
            return $this->createdAt;
        }

        return $this->updatedAt;
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


    #[ORM\Column(type: 'string', nullable: false, enumType: PresentationpageCategory::class)]
    private PresentationpageCategory $category = PresentationpageCategory::Default;

    public function getCategory(): PresentationpageCategory
    {
        return $this->category;
    }

    public function setCategory(PresentationpageCategory $category): void
    {
        $this->category = $category;
    }


    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $isDraft = false;

    public function isDraft(): bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): void
    {
        $this->isDraft = $isDraft;
    }


    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $hasScreenshot = false;

    public function hasScreenshot(): bool
    {
        return $this->hasScreenshot;
    }

    public function setHasScreenshot(bool $hasScreenshot): void
    {
        $this->hasScreenshot = $hasScreenshot;
    }


    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $screenshotCaptureOutstanding = false;

    public function screenshotCaptureOutstanding(): bool
    {
        return $this->screenshotCaptureOutstanding;
    }

    public function setScreenshotCaptureOutstanding(bool $screenshotCaptureOutstanding): void
    {
        $this->screenshotCaptureOutstanding = $screenshotCaptureOutstanding;
    }


    #[ORM\ManyToOne(targetEntity: Presentationpage::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'draft_of_presentationpages_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Presentationpage $draftOfPresentationpage;

    public function setDraftOfPresentationpage(?Presentationpage $draftOfPresentationpage): void
    {
        if (!is_null($draftOfPresentationpage) && !$this->isDraft()) {
            throw new InvalidArgumentException('Cannot set draftOfPresentationpage because this is not a draft.');
        }
        $this->draftOfPresentationpage = $draftOfPresentationpage;
    }

    public function getDraftOfPresentationpage(): ?Presentationpage
    {
        return $this->draftOfPresentationpage;
    }


    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'presentationpages')]
    #[ORM\JoinColumn(name: 'users_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }


    #[ORM\ManyToOne(targetEntity: Video::class, cascade: ['persist'], inversedBy: 'presentationpages')]
    #[ORM\JoinColumn(name: 'videos_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Video $video = null;

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
    private ?string $title = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }


    #[ORM\Column(type: 'string', length: 64, nullable: false, enumType: PresentationpageBackground::class)]
    private PresentationpageBackground $background = PresentationpageBackground::BgColor;

    public function getBackground(): PresentationpageBackground
    {
        return $this->background;
    }

    public function setBackground(PresentationpageBackground $background): void
    {
        $this->background = $background;
    }


    #[ORM\Column(type: 'string', length: 7, nullable: false, enumType: BgColor::class)]
    private BgColor $bgColor = BgColor::_FFFFFF;

    public function getBgColor(): BgColor
    {
        return $this->bgColor;
    }

    public function setBgColor(BgColor $bgColor): void
    {
        $this->bgColor = $bgColor;
    }

    #[ORM\Column(type: 'string', length: 7, nullable: false, enumType: FgColor::class)]
    private FgColor $fgColor = FgColor::_37474F;

    public function getFgColor(): FgColor
    {
        return $this->fgColor;
    }

    public function setFgColor(FgColor $fgColor): void
    {
        $this->fgColor = $fgColor;
    }

    #[ORM\Column(type: 'string', length: 7, nullable: false, enumType: TextColor::class)]
    private TextColor $textColor = TextColor::_000000;

    public function getTextColor(): TextColor
    {
        return $this->textColor;
    }

    public function setTextColor(TextColor $textColor): void
    {
        $this->textColor = $textColor;
    }


    /** @var PresentationpageElement[]|Collection */
    #[ORM\OneToMany(mappedBy: 'presentationpage', targetEntity: PresentationpageElement::class, cascade: ['persist'])]
    private array|Collection $presentationpageElements;

    /**
     * @return PresentationpageElement[]|Collection
     * @throws Exception
     */
    public function getPresentationpageElements(): array|Collection
    {
        $iterator = $this->presentationpageElements->getIterator();
        $iterator->uasort(
            function (
                PresentationpageElement $first,
                PresentationpageElement $second
            ) {
                return $first->getPosition() > $second->getPosition() ? 1 : -1;
            }
        );

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

    /**
     * @throws Exception
     */
    public function hasMercuriusVideoVariantElement(): bool
    {
        foreach ($this->getPresentationpageElements() as $element) {
            if ($element->getElementVariant() === PresentationpageElementVariant::MercuriusVideo) {
                return true;
            }
        }

        return false;
    }

    public function getPossibleElementVariants(): array
    {
        return PresentationpageElementVariant::cases();
    }

    public function getPossibleBackgrounds(): array
    {
        return PresentationpageBackground::cases();
    }

    public function getPossibleBgColors(): array
    {
        return BgColor::cases();
    }

    public function getPossibleFgColors(): array
    {
        return FgColor::cases();
    }

    public function getPossibleTextColors(): array
    {
        return TextColor::cases();
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getHeadlines(): array
    {
        $headlines = [];
        foreach ($this->getPresentationpageElements() as $presentationpageElement) {
            if ($presentationpageElement->getElementVariant() === PresentationpageElementVariant::Headline) {
                $headlines[] = $presentationpageElement->getTextContent();
            }
        }

        return $headlines;
    }
}
