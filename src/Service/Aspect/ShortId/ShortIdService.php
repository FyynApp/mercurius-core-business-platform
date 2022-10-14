<?php

namespace App\Service\Aspect\ShortId;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;


class ShortIdService
{
    const ALPHABET = '23456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ-_';
    const BASE = 51;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function encodeObjectId(object $o): string
    {
        if (!$this->entityManager->contains($o)) {
            throw new InvalidArgumentException('Not an entity.');
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
            throw new InvalidArgumentException('Object of class ' . get_class($o) . ' does not support a short id.');
        }
    }

    public static function encode($num): string
    {
        $str = '';

        while ($num > 0) {
            $str = self::ALPHABET[($num % self::BASE)] . $str;
            $num = (int) ($num / self::BASE);
        }

        return $str;
    }

    public static function decode(string $str): string
    {
        $num = 0;
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $num = $num * self::BASE + strpos(self::ALPHABET, $str[$i]);
        }

        return $num;
    }
}
