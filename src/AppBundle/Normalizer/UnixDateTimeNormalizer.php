<?php


namespace AppBundle\Normalizer;


use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * Нормализатор, приводящий значения времени к int-таймштампу
 *
 * Class IntegerDateTimeNormalizer
 * @package AppBundle\Normalizer
 */
class UnixDateTimeNormalizer extends DateTimeNormalizer
{
    /**
     * @inheritDoc
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return (int)parent::normalize($object, null, [
            self::FORMAT_KEY => 'U'
        ]);
    }
}