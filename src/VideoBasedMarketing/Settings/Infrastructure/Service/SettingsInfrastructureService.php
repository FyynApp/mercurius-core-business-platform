<?php

namespace App\VideoBasedMarketing\Settings\Infrastructure\Service;

use App\Shared\Infrastructure\Service\FilesystemService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomLogoSetting;
use App\VideoBasedMarketing\Settings\Infrastructure\Entity\LogoUpload;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use TusPhp\Events\UploadComplete;
use TusPhp\Tus\Server;

readonly class SettingsInfrastructureService
{
    private const UPLOADED_LOGO_ASSETS_SUBFOLDER_NAME = 'settings-uploaded-logo-assets';

    public function __construct(
        private FilesystemService      $filesystemService,
        private EntityManagerInterface $entityManager
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
                self::UPLOADED_LOGO_ASSETS_SUBFOLDER_NAME,
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

        $fs = new Filesystem();

        $fs->rename(
            $this->filesystemService->getContentStoragePath(
                [
                    self::UPLOADED_LOGO_ASSETS_SUBFOLDER_NAME,
                    $user->getId(),
                    $logoUpload->getFileName()
                ]
            ),
            $this->filesystemService->getContentStoragePath(
                [
                    self::UPLOADED_LOGO_ASSETS_SUBFOLDER_NAME,
                    $user->getId(),
                    "{$logoUpload->getId()}_{$logoUpload->getFileName()}"
                ]
            )
        );
    }

    private function getContentStoragePathForLogoUpload(
        LogoUpload $logoUpload
    ): string
    {
        return $this->filesystemService->getContentStoragePath(
            [
                self::UPLOADED_LOGO_ASSETS_SUBFOLDER_NAME,
                $logoUpload->getCustomLogoSetting()->getUser()->getId(),
                "{$logoUpload->getId()}_{$logoUpload->getFileName()}"
            ]
        );
    }
}
