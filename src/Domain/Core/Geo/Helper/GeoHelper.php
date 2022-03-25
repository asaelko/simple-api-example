<?php

namespace App\Domain\Core\Geo\Helper;

use CarlBundle\Service\Geo\LatLng;

/**
 * Сервис работы с гео
 */
final class GeoHelper
{
    /**
     * Проверяет, находится ли точка в пределах полигона, заданного полилайном
     *
     * @param LatLng $point
     * @param string $polyline
     * @param bool $pointOnVertex
     * @return bool
     */
    final public static function isPointInPolygon(LatLng $point, string $polyline, bool $pointOnVertex = true): bool {
        $vertices = PolylineHelper::decode($polyline);
        bcscale(6);

        // на всякий случай - вдруг это вершина полигона?
        if (self::pointOnVertex($point, $vertices)) {
            return true;
        }

        // не вершина, поехали проверять, где точка
        $intersections = 0;
        $vertices_count = count($vertices);

        for ($i=1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i-1];
            $vertex2 = $vertices[$i];

            // может, на горизонтальной границе полигона?
            if (bccomp($vertex1[1], $vertex2[1]) === 0
                && bccomp($vertex1[1], $point->getLng()) === 0
                && bccomp($point->getLat(), min($vertex1[0], $vertex2[0])) === 1
                && bccomp($point->getLat(), max($vertex1[0], $vertex2[0])) === -1
            ) {
                return true; // на границе
            }

            if (bccomp($point->getLng(), min($vertex1[1], $vertex2[1])) === 1
                && bccomp($point->getLng(), max($vertex1[1], $vertex2[1])) <= 0
                && bccomp($point->getLat(), max($vertex1[0], $vertex2[0])) <= 0
                && bccomp($vertex1[1],$vertex2[1]) !== 0
            ) {
                $operand = bcdiv(
                    bcmul(
                        bcsub($point->getLng(), $vertex1[1]),
                        bcsub($vertex2[0], $vertex1[0])
                        ),
                    bcsub($vertex2[1], $vertex1[1])
                );
                $xinters = bcadd(
                    $operand,
                    $vertex1[0]
                );
                if (bccomp($xinters, $point->getLat()) === 0) {
                    return true; // на границе, но не горизонтальной
                }

                if (bccomp($vertex1[0], $vertex2[0]) === 0 || bccomp($point->getLat(), $xinters) <= 0) {
                    $intersections++;
                }
            }
        }

        // по even-odd rule если количество пересечений луча из точки нечетное - она внутри полигона
        return ($intersections % 2 !== 0);
    }

    /**
     * Проверяет, расположена ли точка на вершине полигона
     */
    private static function pointOnVertex(LatLng $point, array $vertices): bool {
        foreach($vertices as $vertex) {
            $one = bccomp($point->getLat(), $vertex[0]);
            $two = bccomp($point->getLng(), $vertex[1]);
            if (0 === bccomp($point->getLat(), $vertex[0]) && 0 === bccomp($point->getLng(), $vertex[1])) {
                return true;
            }
        }

        return false;
    }
}
