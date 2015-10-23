<?php
namespace Mars\Core;

use Mars\Configure\Configure;

class App
{
    /**
     * Return the class name namespaced. This method checks if the class is defined on the
     * application/plugin, otherwise try to load from the MarsFramework core.
     *
     * @param string $class Class name
     * @param string $type Type of class
     * @param string $suffix Class name suffix
     *
     * @return bool|string False if the class is not found or namespaced class name
     */
    public static function className($class, $type = '', $suffix = '')
    {
        if (strpos($class, '\\') !== false) {
            return $class;
        }

        list($plugin, $name) = pathSplit($class);
        if ($plugin) {
            $base = $plugin;
        } else {
            $base = Configure::read('App.namespace');
        }
        $base = str_replace('/', '\\', rtrim($base, '\\'));

        $fullname = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;

        if (static::_classExistsInBase($fullname, $base)) {
            return $base . $fullname;
        }
        if ($plugin) {
            return false;
        }
        if (static::_classExistsInBase($fullname, 'Mars')) {
            return 'Mars' . $fullname;
        }
        return false;
    }

    /**
     * _classExistsInBase
     *
     * Test isolation wrapper
     *
     * @param string $name Class name.
     * @param string $namespace Namespace.
     *
     * @return bool
     */
    protected static function _classExistsInBase($name, $namespace)
    {
        return class_exists($namespace . $name);
    }

    /**
     * Returns the full path to a package inside the MarsFramework core
     *
     * Usage:
     *
     * `App::core('Cache/Engine');`
     *
     * Will return the full path to the cache engines package.
     *
     * @param string $type Package type.
     *
     * @return array Full path to package
     */
    public static function core($type)
    {
        return [ROOT . DS . str_replace('/', DS, $type) . DS];
    }
}
