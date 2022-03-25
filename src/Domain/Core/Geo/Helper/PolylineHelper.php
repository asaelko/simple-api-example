<?php

namespace App\Domain\Core\Geo\Helper;

/**
 * Polyline encoding & decoding class
 *
 * Convert list of points to encoded string following Google's Polyline
 * Algorithm.
 */
class PolylineHelper
{
    /**
     * Default precision level of 1e-5.
     *
     * @var int $precision
     */
    protected static $precision = 5;

    /**
     * Apply Google Polyline algorithm to list of points.
     *
     * @param array $points List of points to encode. Can be a list of tuples,
     *                      or a flat on dimensional array.
     *
     * @return string encoded string
     */
    final public static function encode($points): string
    {
        $points = self::flatten($points);
        $encodedString = '';
        $index = 0;
        $previous = array(0, 0);
        foreach ($points as $number) {
            $number = (float)$number;
            $number = (int)round($number * (10 ** static::$precision));
            $diff = $number - $previous[$index % 2];
            $previous[$index % 2] = $number;
            $number = $diff;
            $index++;
            $number = ($number < 0) ? ~($number << 1) : ($number << 1);
            $chunk = '';
            while ($number >= 0x20) {
                $chunk .= \chr((0x20 | ($number & 0x1f)) + 63);
                $number >>= 5;
            }
            $chunk .= \chr($number + 63);
            $encodedString .= $chunk;
        }
        return $encodedString;
    }

    /**
     * Reverse Google Polyline algorithm on encoded string.
     *
     * @param string $string Encoded string to extract points from.
     *
     * @return array points
     */
    final public static function decode($string): array
    {
        $points = array();
        $index = $i = 0;
        $previous = array(0, 0);
        while ($i < \strlen($string)) {
            $shift = $result = 0x00;
            do {
                $bit = \ord(\substr($string, $i++)) - 63;
                $result |= ($bit & 0x1f) << $shift;
                $shift += 5;
            } while ($bit >= 0x20);

            $diff = ($result & 1) ? ~($result >> 1) : ($result >> 1);
            $number = $previous[$index % 2] + $diff;
            $previous[$index % 2] = $number;
            $index++;
            $points[] = $number * 1 / (10 ** static::$precision);
        }
        return self::pair($points);
    }

    /**
     * Reduce multi-dimensional to single list
     *
     * @param array $array Subject array to flatten.
     *
     * @return array flattened
     */
    final public static function flatten($array): array
    {
        $flatten = array();
        array_walk_recursive(
            $array, // @codeCoverageIgnore
            function ($current) use (&$flatten) {
                $flatten[] = $current;
            }
        );
        return $flatten;
    }

    /**
     * Concat list into pairs of points
     *
     * @param array $list One-dimensional array to segment into list of tuples.
     *
     * @return array pairs
     */
    final public static function pair($list): array
    {
        return \is_array($list) ? \array_chunk($list, 2) : array();
    }
}