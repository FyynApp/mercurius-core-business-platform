<?php

namespace App\Shared\Infrastructure\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ValueError;


class ShortIdService
{
    const ALPHABET = '0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ';
    const BASE = 51;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     */
    public function encodeObject(object $o): string
    {
        if (!$this->entityManager->contains($o)) {
            throw new ValueError('Not an entity.');
        }

        if (   method_exists($o, 'getShortId')
            && method_exists($o, 'setShortId')
        ) {
            $shortId = $o->getShortId();
            if (is_null($shortId)) {
                $o->setShortId(self::encode(random_int(1, PHP_INT_MAX)));
                $this->entityManager->persist($o);
                $this->entityManager->flush();
                return $o->getShortId();
            }
            return $shortId;
        } else {
            throw new ValueError("Object of class '{$o::class}' does not support a short id.");
        }
    }

    public static function encode(int $num): string
    {
        if ($num < 1) {
            throw new ValueError('Number must be greater than 0.');
        }

        $str = '';

        while ($num > 0) {
            $str = self::ALPHABET[($num % self::BASE)] . $str;
            $num = (int) ($num / self::BASE);
        }

        return $str;
    }
}
