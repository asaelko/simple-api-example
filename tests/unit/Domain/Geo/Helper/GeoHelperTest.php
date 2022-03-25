<?php

namespace App\Domain\Core\Geo\Helper;

use CarlBundle\Service\Geo\LatLng;
use Codeception\Test\Unit;

class GeoHelperTest extends Unit
{
    /**
     * Тестирует попадаение геокоординаты в заданный полигон
     *
     * @dataProvider dataForPointInPolygon
     */
    public function testIsPointInPolygon(LatLng $point, string $polyline, bool $validResult)
    {
        $pointInPolygon = GeoHelper::isPointInPolygon($point, $polyline);
        $this->assertEquals($validResult, $pointInPolygon);
    }

    /**
     * Набор данных для тестирования проверки попадания точки в заданный полигон
     *
     * @return array
     */
    public function dataForPointInPolygon(): array
    {
        /**
         * Базовый квадратный полигон
         * 55.76703, 37.5967 - 55.76703, 37.64305
         * 55.7394, 37.5967 - 55.7394, 37.64305
         */
        $basePolyline = '}~jsIkbndF?u`HtkD??t`H';

        /**
         * Полилайн границ Москвы по МКАДу
         */
        $mskPolyline = 's}lsI{s~eFjy@Ejz@^~r@~CbrAxBzt@pEjt@hEz{@vKdx@b[vu@lRfw@oJjy@kSfr@uQvw@}Izt@la@rf@pw@vf@|~@jr@pjAp`@lr@xd@|kAdd@lbAni@rlA~_@hbAra@hqAt]fhAx_@dtAd_@hqA`]xpArKbxAyEdcCgDrxA_DjgByE``CkKxyAuTfzAeWhdByMfaAi\ftByNb_Aoo@nuA_l@fs@io@rw@ks@r~@qZr\ubAtpAmo@bu@yc@dk@{b@|h@g_Avs@wx@ll@ij@~Vev@zl@mk@zz@yi@bw@yt@pb@m_At]kh@bTezAr\wz@rEut@Zct@qBwr@m@g^]c]kQao@_j@qt@ck@ev@_Ucw@wPsv@wKep@pB_|@hIcu@qYqr@wr@ib@ycAcZeoAiXqwA{Is_BgJwvAuY}_Bi`@epAm`@qgAob@}xAeN_bBsI{cBoHubBhIq{AtVkxA|Vw{BlRgeBvHahAnHm}BrBouAdEyiB|\usApa@oeAzd@}jAnPea@nkAkpC~AeHfhA}iCW{@xe@mjArfA}iCnWij@fo@gm@dz@cJbv@uE|u@aE`w@yB';

        return [
            // базовые тесты на квадратном полигоне
            [new LatLng(55.74964, 37.61902), $basePolyline, true], // точка внутри полигона
            [new LatLng(55.77329, 37.63092), $basePolyline, false], // точка снаружи полигона
            [new LatLng(55.76703, 37.64305), $basePolyline, true], // точка на вершине полигона
            [new LatLng(55.7500, 37.64305), $basePolyline, true], // точка на границе полигона

            // рандомные тесты на реальном полигоне
            [new LatLng(55.749974, 37.537985), $mskPolyline, true], // офис CARL
            [new LatLng(55.764925, 37.366561), $mskPolyline, true], // точка где-то на границе
            [new LatLng(55.79015, 37.36887), $mskPolyline, false], // точка прямо за границей
            [new LatLng(40.99707, 104.86767), $mskPolyline, false], // кажется, это Китай
            [new LatLng(-34.90087, -56.16638), $mskPolyline, false], // где-то в Монтевидео для отрицательных координат

            // 500-ые
            [new LatLng(55.498719, 37.587946), $mskPolyline, false] // Москва, ул Гагарина, д 9А
        ];
    }
}
