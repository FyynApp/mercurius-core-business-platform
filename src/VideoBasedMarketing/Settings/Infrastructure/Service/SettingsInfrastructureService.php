<?php

namespace App\VideoBasedMarketing\Settings\Infrastructure\Service;

use App\Shared\Infrastructure\Message\ClearTusCacheCommandMessage;
use App\Shared\Infrastructure\Service\FilesystemService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Settings\Domain\Service\SettingsDomainService;
use App\VideoBasedMarketing\Settings\Infrastructure\Entity\LogoUpload;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;
use TusPhp\Events\UploadComplete;
use TusPhp\Tus\Server;

readonly class SettingsInfrastructureService
{
    private const ROOT_FOLDER_NAME = 'settings';
    private const LOGO_UPLOADS_SUBFOLDER_NAME = 'logo-uploads';

    public function __construct(
        private FilesystemService      $filesystemService,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface    $messageBus,
        private SettingsDomainService  $settingsDomainService
    )
    {

    }
    public function prepareLogoUpload(
        User   $user,
        Server $server
    ): void
    {
        $path = $this->filesystemService->getContentStoragePath(
            [
                self::ROOT_FOLDER_NAME,
                self::LOGO_UPLOADS_SUBFOLDER_NAME,
                $user->getId()
            ]
        );

        $fs = new Filesystem();
        $fs->mkdir($path);

        $server->setUploadDir($path);
    }

    /**
     * @throws Exception
     */
    public function handleCompletedLogoUpload(
        User           $user,
        string         $token,
        UploadComplete $event
    ): void
    {
        $fileMeta = $event->getFile()->details();

        $logoUpload = new LogoUpload(
            $user,
            $token,
            $fileMeta['metadata']['filename'],
            $fileMeta['metadata']['filetype']
        );

        $this->entityManager->persist($logoUpload);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        if (sizeof($user->getLogoUploads()) === 1) {
            $this->settingsDomainService->makeLogoUploadActive($logoUpload);
        }

        $fs = new Filesystem();

        $fs->mkdir(
            $this->filesystemService->getPublicWebfolderGeneratedContentPath(
                [
                    self::ROOT_FOLDER_NAME,
                    self::LOGO_UPLOADS_SUBFOLDER_NAME,
                    $user->getId(),
                    $logoUpload->getId()
                ]
            )
        );

        $fs->rename(
            $this->filesystemService->getContentStoragePath(
                [
                    self::ROOT_FOLDER_NAME,
                    self::LOGO_UPLOADS_SUBFOLDER_NAME,
                    $user->getId(),
                    $logoUpload->getFileName()
                ]
            ),
            $this->filesystemService->getPublicWebfolderGeneratedContentPath(
                [
                    self::ROOT_FOLDER_NAME,
                    self::LOGO_UPLOADS_SUBFOLDER_NAME,
                    $user->getId(),
                    $logoUpload->getId(),
                    $logoUpload->getFileName()
                ]
            )
        );

        $this->messageBus->dispatch(
            new ClearTusCacheCommandMessage($user, $token)
        );
    }

    /**
     * @return LogoUpload[]|Collection
     */
    public function getLogoUploads(
        User $user
    ): array|Collection
    {
        $logoUploads = $user->getLogoUploads()->toArray();

        usort($logoUploads, function(LogoUpload $a, LogoUpload $b) {
            return (int)($a->getCreatedAt() < $b->getCreatedAt());
        });

        return $logoUploads;
    }
}
