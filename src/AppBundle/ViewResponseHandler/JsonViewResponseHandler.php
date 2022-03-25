<?php

namespace AppBundle\ViewResponseHandler;

use AppBundle\Normalizer\CustomGetSetMethodNormalizer;
use AppBundle\Normalizer\UnixDateTimeNormalizer;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class JsonViewResponseHandler
 * @package AppBundle\ViewResponseHandler
 */
class JsonViewResponseHandler
{
    /** @var Serializer */
    private $serializer;

    /**
     * Конструируем сериализатор данных с нужными нам параметрами
     */
    public function __construct()
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        $this->serializer = new Serializer(
            [
                new UnixDateTimeNormalizer(),
                new CustomGetSetMethodNormalizer(
                    $classMetadataFactory,
                    $metadataAwareNameConverter,
                    new PropertyInfoExtractor()
                ),
                new ObjectNormalizer()
            ],
            [new JsonEncoder()]
        );
    }

    /**
     * Обрабатываем полученные данные через свой сериализатор
     *
     * @param ViewHandler $ViewHandler
     * @param View        $View
     * @param Request     $Request
     * @param string      $format
     *
     * @return Response
     */
    public function createResponse(ViewHandler $ViewHandler, View $View, Request $Request, $format): Response
    {
        $data = $View->getData();

        if ($data === null) {
            $data = new stdClass();
        }

        $context = $this->convertContext($View->getContext());
        $serializedData = $this->serializer->serialize($data, $format, $context);

        // костыль, чтобы пустой объект не сериализовывался как массив
        if ($serializedData === '[]' && is_object($data) && !($data instanceof Collection)) {
            $serializedData = '{}';
        }

        return new Response($serializedData, $View->getStatusCode() ?? 200, $View->getHeaders());
    }

    /**
     * Разворачиваем переданный нам контекст
     *
     * @param Context $context
     *
     * @return array
     */
    private function convertContext(Context $context): array
    {
        $newContext = array();
        foreach ($context->getAttributes() as $key => $value) {
            $newContext[$key] = $value;
        }

        if (null !== $context->getGroups()) {
            $newContext['groups'] = $context->getGroups();
        }
        $newContext['version'] = $context->getVersion();
        $newContext['maxDepth'] = $context->getMaxDepth(false);
        $newContext['enable_max_depth'] = $context->isMaxDepthEnabled();
        $newContext[AbstractObjectNormalizer::SKIP_NULL_VALUES] = true;

        return $newContext;
    }
}
