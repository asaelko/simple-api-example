<?php

namespace Tests;

/**
 * Trait ReflectionHelperTrait
 *
 * @package Tests
 */
trait ReflectionHelperTrait
{
    /**
     * @param $object
     * @param string $propertyName
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function invokeGetProperty(&$object, string $propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @param $object
     * @param string $propertyName
     * @param mixed $value
     *
     * @throws \ReflectionException
     */
    public function invokeSetProperty(&$object, string $propertyName, $value)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * @param $object
     * @param $methodName
     * @param array $parameters
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function invokeMethod(&$object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
