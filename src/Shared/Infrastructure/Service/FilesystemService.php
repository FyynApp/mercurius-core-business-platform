<?php

namespace App\Shared\Infrastructure\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class FilesystemService
{
    private const CONTENT_STORAGE_ROOT_FOLDER_PATH = '/var/tmp/mercurius-core-business-platform';

    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /** @param $pathParts string[] */
    public function getContentStoragePath(array $pathParts): string
    {
        $path = implode('/', $pathParts);
        if ($path === '') {
            return self::CONTENT_STORAGE_ROOT_FOLDER_PATH;
        }

        return self::CONTENT_STORAGE_ROOT_FOLDER_PATH . '/' . $path;
    }

    /** @param $pathParts string[] */
    public function getPublicWebfolderGeneratedContentPath(array $pathParts): string
    {
        $path = implode('/', $pathParts);
        if ($path === '') {
            return $this->parameterBag->get('kernel.project_dir') . '/public/generated-content';
        }

        return $this->parameterBag->get('kernel.project_dir') . '/public/generated-content/' . $path;
    }
}
