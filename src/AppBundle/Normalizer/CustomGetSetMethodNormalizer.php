<?php

namespace AppBundle\Normalizer;

use DateTime;
use Exception;
use ReflectionMethod;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use function is_array;
use function is_callable;

/**
 * Class CustomGetSetMethodNormalizer
 * @package AppBundle\Normalizer
 */
class CustomGetSetMethodNormalizer extends GetSetMethodNormalizer
{
    protected static $customNormalizer;

    /**
     * CustomGetSetMethodNormalizer constructor.
     * @param ClassMetadataFactoryInterface|null $classMetadataFactory
     * @param NameConverterInterface|null $nameConverter
     * @param PropertyTypeExtractorInterface|null $propertyTypeExtractor
     */
    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyTypeExtractorInterface $propertyTypeExtractor = null
    )
    {
        parent::__construct($classMetadataFactory, $nameConverter, $propertyTypeExtractor);
        self::$customNormalizer = $this;
    }

    /**
     * @return CustomGetSetMethodNormalizer
     */
    protected static function getCustomNormalizer(): CustomGetSetMethodNormalizer
    {
        return self::$customNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = parent::normalize($object, $format, $context);

        return array_filter($data, static function ($value) {
            $skip = $value === null || (is_array($value) && empty($value));

            return !$skip;
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributeValue($object, $attribute, $format = null, array $context = array())
    {
        $ucfirsted = ucfirst($attribute);

        $resultData = null;

        $getter = 'get' . $ucfirsted;
        if (is_callable(array($object, $getter))) {
            $resultData = $object->$getter();
        }

        $isser = 'is' . $ucfirsted;
        if (is_callable(array($object, $isser))) {
            $resultData = $object->$isser();
        }

        $haser = 'has' . $ucfirsted;
        if (is_callable(array($object, $haser))) {
            $resultData = $object->$haser();
        }

        return $resultData;
    }

    /**
     * @inheritDoc
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        $setter = 'set' . ucfirst($attribute);
        try {
            // ???????????????? ?????????????????????????? ???????????????????? ???????????? ?? ????????
            $reflection = new ReflectionMethod($object, $setter);
            $params = $reflection->getParameters();
            if ((is_numeric($value) || is_string($value)) && count($params) === 1 && (string)$params[0]->getType() === 'DateTime') {
                if (is_numeric($value)) {
                    $date = DateTime::createFromFormat('U', $value);
                } else {
                    $date = new DateTime($value);
                }
                $value = $date;
            }
        } catch (Exception $e) {
            // do nothing
        }

        parent::setAttributeValue($object, $attribute, $value, $format, $context); // TODO: Change the autogenerated stub
    }
}
