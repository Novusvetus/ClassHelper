<?php

declare(strict_types=1);

/*
 * This file is part of ClassHelper.
 *
 * (c) Novusvetus / Marcel Rudolf, Germany <development@novusvetus.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Novusvetus\ClassHelper;

use ReflectionClass;

/**
 * This adds some helper for classes
 */
class ClassHelper
{
    private static $overwriteClasses = [];
    private static $singletons = [];

    /**
     * The class name
     *
     * @return string the class name
     */
    public function __toString(): string
    {
        return $this->class;
    }

    /**
     * This class allows you to overload classes with other classes when they
     * are constructed using the factory method
     * {@link ClassHelper::create()}
     *
     * @param string $oldClass the class to replace
     * @param string $newClass the class to replace it with
     * @param bool $force When true, the new class don't need to be a child
     * class of the old
     *
     * @return bool Returns true, if everything was okay
     */
    public static function useOverwriteClass(string $oldClass, string $newClass, bool $force = false): bool
    {
        if (self::exists($newClass)) {
            if ($force || (is_a(self::singleton($oldClass), $newClass))) {
                self::$overwriteClasses[$oldClass] = $newClass;
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * Returns true if a class or interface name exists.
     */
    public static function exists(string $class): bool
    {
        return class_exists($class, false) || interface_exists($class, false);
    }

    /**
     * Creates a class instance by the "singleton" design pattern.
     * It will always return the same instance for this class.
     *
     * @param string $class Optional classname to create, if the called class should not be used
     *
     * @return static The singleton instance
     */
    public static function singleton(?string $class = null): ClassHelper
    {
        if (! $class) {
            $class = static::class;
        }

        if (! isset(self::$singletons[$class])) {
            self::$singletons[$class] = self::create($class);
        }

        return self::$singletons[$class];
    }

    /**
     * The factory method, allows you to create an instance of a class.
     *
     * This can be called in two ways - calling via the class directly,
     * or calling on Object and passing the class name as the first parameter.
     *
     * @param string $classOrArgument The first argument, or class name (if
     * called directly on Object).
     * @param mixed $argument,... arguments to pass to the constructor
     *
     * @return static
     */
    public static function create(): ClassHelper
    {
        $args = func_get_args();

        // Class to create should be the calling class if not Object,
        // otherwise the first parameter
        $class = static::class;
        if ($class === 'Novusvetus\\ClassHelper\\ClassHelper') {
            $class = array_shift($args);
        }

        $class = self::getOverwriteClass($class);

        return (new ReflectionClass($class))->newInstanceArgs($args);
    }

    /**
     * If a class has been overloaded, get the class name it has been
     * overloaded with - otherwise return the class name
     *
     * @param string $class the class to check
     *
     * @return string the class that would be created if you called
     * {@link ClassHelper::create()} with the class
     */
    public static function getOverwriteClass(string $class): string
    {
        return self::$overwriteClasses[$class] ?? $class;
    }

    /**
     * Get the value of a static property of a class, even in that property
     * is declared protected (but not private),
     * without any inheritance, merging or parent lookup if it doesn't exist
     * on the given class.
     *
     * @param string $class The class to get the static from
     * @param string $name The property to get from the class
     * @param mixed $default The value to return if property doesn't exist on
     * class
     *
     * @return mixed The value of the static property $name on class $class,
     * or $default if that property is not defined
     */
    public static function staticLookup(string $class, string $name, mixed $default = null): mixed
    {
        $reflection = new ReflectionClass($class);
        $static_properties = $reflection->getStaticProperties();

        if (isset($static_properties[$name])) {
            $value = $static_properties[$name];

            $parent = get_parent_class($class);
            if (! $parent) {
                return $value;
            }

            $reflection = new ReflectionClass($parent);
            $static_properties = $reflection->getStaticProperties();

            if (! isset($static_properties[$name]) || $static_properties[$name] !== $value) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Returns the parent class
     *
     * @return string this classes parent class
     */
    public function parentClass(): mixed
    {
        return get_parent_class($this);
    }

    /**
     * Check if this class is an instance of a specific class, or has that
     * class as one of its parents
     */
    public function isA(string $class): bool
    {
        return $this instanceof $class;
    }

    /**
     * The class name
     *
     * @return string the class name
     */
    public function getClass(): string
    {
        return static::class;
    }
}
