<?php

namespace TeamTimer\Pakk\Base;

/**
 * Class Entity
 * @package TeamTimer\Pakk\Base
 */
abstract class Entity
{
    /**
     * Define the table name for the entity
     * @return string
     * @throws \Exception
     */
    public static function tableName(): string
    {
        throw new \Exception('Table name not defined for ' . static::class);
    }

    /**
     * Define the fields for the entity
     * @return array
     */
    public static function fields(): array
    {
        return [];
    }

    /**
     * Links the entity to the ORM registry
     * @param $registry
     * @return void
     * @throws \Exception
     */
    public static function link(&$registry): void
    {
        $entity = new \Cycle\Schema\Definition\Entity();

        $entity
            ->setRole(strtolower((new \ReflectionClass(static::class))->getShortName()))
            ->setClass(static::class);

        $fieldMap = $entity->getFields();

        foreach (static::fields() as $fieldName => $type) {
            $fieldMap->set($fieldName, (new \Cycle\Schema\Definition\Field())->setType($type)->setColumn($fieldName));
        }

        // find repository
        $expectedRepositoryClass = 'App\\Repositories\\' . (new \ReflectionClass(static::class))->getShortName() . 'Repository';

        if (class_exists($expectedRepositoryClass)) {
            $entity->setRepository($expectedRepositoryClass);
        }

        $registry->register($entity);

        $registry->linkTable($entity, 'default', static::tableName());
    }

    /**
     * Returns the repository for the entity
     * If you have a custom repository, make sure to typehint it in the entity for easier use in your IDE
     * @return \Cycle\ORM\Select\Repository|mixed
     */
    public static function getRepository()
    {
        return \TeamTimer\Pakk\App::$orm->getRepository(static::class);
    }

    /**
     * Returns a query builder for the entity
     * @return \Cycle\ORM\Select
     */
    public static function query()
    {
        return static::getRepository()->select();
    }
}