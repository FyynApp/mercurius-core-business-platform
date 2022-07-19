<?php

namespace App\Entity\Feature\Presentationpage;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSessionFullVideo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'presentationpages')]
class Presentationpage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
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


    #[Assert\NotBlank]
    #[Assert\Length(max: 8192)]
    #[ORM\Column(type: 'string', length: 8192, unique: false, nullable: true)]
    private ?string $welcomeText;

    public function getWelcomeText(): ?string
    {
        return $this->welcomeText;
    }

    public function setWelcomeText(?string $welcomeText): void
    {
        $this->welcomeText = $welcomeText;
    }


    #[ORM\ManyToOne(targetEntity: RecordingSessionFullVideo::class, cascade: ['persist'], inversedBy: 'presentationPages')]
    #[ORM\JoinColumn(name: 'recording_session_full_videos_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?RecordingSessionFullVideo $recordingSessionFullVideo;

    public function getRecordingSessionFullVideo(): ?RecordingSessionFullVideo
    {
        return $this->recordingSessionFullVideo;
    }

    public function setRecordingSessionFullVideo(?RecordingSessionFullVideo $recordingSessionFullVideo): void
    {
        $this->recordingSessionFullVideo = $recordingSessionFullVideo;
    }


    #[ORM\ManyToOne(targetEntity: PresentationpageTemplate::class, cascade: ['persist'], inversedBy: 'presentationPages')]
    #[ORM\JoinColumn(name: 'presentationpage_templates_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PresentationpageTemplate $presentationpageTemplate;

    public function getPresentationpageTemplate(): ?PresentationpageTemplate
    {
        return $this->presentationpageTemplate;
    }

    public function setPresentationpageTemplate(?PresentationpageTemplate $presentationpageTemplate): void
    {
        $this->presentationpageTemplate = $presentationpageTemplate;
    }
}
