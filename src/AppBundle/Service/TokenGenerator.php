<?php

namespace AppBundle\Service;

use function chr;
use function function_exists;

/**
 * Генерация строк различных форматов для токенов и т.п
 *
 * @author Gleb Bogdevich
 */
class TokenGenerator
{
    /**
     * Генерация токена формата GUID v4
     *
     * @return string
     */
    public static function getGUID(): string
    {
        if (function_exists('com_create_guid')) {
            return trim(com_create_guid(), '{}');
        }

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        $guid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        return strtoupper($guid);
    }
}
