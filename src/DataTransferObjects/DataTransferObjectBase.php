<?php

namespace Volistx\FrameworkKernel\DataTransferObjects;

use ReflectionClass;
use ReflectionProperty;
use stdClass;

abstract class DataTransferObjectBase
{
    protected $entity;

    public function __construct($entity)
    {
        $this->entity = $entity;

        $class = new ReflectionClass(static::class);

        $parameters = $entity instanceof stdClass ? get_object_vars($entity) : (is_array($entity) ? $entity : $entity->toArray());

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
            $property = $reflectionProperty->getName();
            $this->{$property} = $parameters[$property];
        }
    }
}
