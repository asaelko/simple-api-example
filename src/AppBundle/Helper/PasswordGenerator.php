<?php

namespace AppBundle\Helper;

use Exception;
use RuntimeException;

/**
 * Генерация паролей в заданном формате
 *
 * @author Gleb Bogdevich
 */
class PasswordGenerator
{
    /**
     * Генерация токена формата GUID v4
     *
     * @param int $length
     * @param string $keyspace
     * @return string
     */
    public static function getPassword(int $length = 8, string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;

        if ($length === 0 || empty($keyspace)) {
            throw new RuntimeException('Incorrect password data');
        }

        for ($i = 0; $i < $length; ++$i) {
            try {
                $str .= $keyspace[random_int(0, $max)];
            } catch (Exception $e) {
                $str .= $keyspace[rand(0, $max)];
            }
        }

        return $str;
    }
}
