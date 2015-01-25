<?php

namespace Noze\Configure;

use Noze\Configure\Configure\ConfigEngineInterface;
use Noze\Configure\Configure\Engine\PhpConfig;
use Noze\Configure\Exception\Exception;
use Noze\Utility\Hash;

class Configure {

/**
 * Array of values currently stored in Configure.
 *
 * @var array
 */
	protected static $_values = [
		'debug' => 0
	];

/**
 * Configured engine classes, used to load config files from resources.
 *
 * @var array
 */
	protected static $_engines = [];

/**
 * Flag to track whether or not ini_set exists.
 *
 * @return void
 */
	protected static $_hasIniSet = null;

/**
 * Used to store a dynamic variable in Configure.
 *
 * Usage:
 * ```
 * Configure::write('One.key1', 'value of the Configure::One[key1]');
 * Configure::write(['One.key1' => 'value of the Configure::One[key1]']);
 * Configure::write('One', [
 * 'key1' => 'value of the Configure::One[key1]',
 * 'key2' => 'value of the Configure::One[key2]'
 * ]);
 *
 * Configure::write([
 * 'One.key1' => 'value of the Configure::One[key1]',
 * 'One.key2' => 'value of the Configure::One[key2]'
 * ]);
 * ```
 *
 * @param string|array $config The key to write, can be a dot notation value.
 * Alternatively can be an array containing key(s) and value(s).
 * @param mixed $value Value to set for var.
 *
 * @return bool True if write was successful.
 */
	public static function write($config, $value = null) {
		if (!is_array($config)) {
			$config = [$config => $value];
		}
		foreach ($config as $name => $value) {
			static::$_values = Hash::insert(static::$_values, $name, $value);
		}
		if (isset($config['debug'])) {
			if (static::$_hasIniSet === null) {
				static::$_hasIniSet = function_exists('ini_set');
			}
			if (static::$_hasIniSet) {
				ini_set('display_errors', $config['debug'] ? 1 : 0);
			}
		}
		return true;
	}

/**
 * Used to read information stored in Configure. It's not
 * possible to store `null` values in Configure.
 *
 * Usage:
 * ```
 * Configure::read('Name'); will return all values for Name
 * Configure::read('Name.key'); will return only the value of Configure::Name[key]
 * ```
 *
 * @param string $var Variable to obtain. Use '.' to access array elements.
 *
 * @return mixed value stored in configure, or null.
 */
	public static function read($var = null) {
		if ($var === null) {
			return static::$_values;
		}
		return Hash::get(static::$_values, $var);
	}

/**
 * Returns true if given variable is set in Configure.
 *
 * @param string $var Variable name to check for.
 *
 * @return bool True if variable is there.
 */
	public static function check($var) {
		if (empty($var)) {
			return false;
		}
		return Hash::get(static::$_values, $var) !== null;
	}

/**
 * Used to delete a variable from Configure.
 *
 * Usage:
 * ```
 * Configure::delete('Name'); will delete the entire Configure::Name
 * Configure::delete('Name.key'); will delete only the Configure::Name[key]
 * ```
 *
 * @param string $var the var to be deleted.
 *
 * @return void
 */
	public static function delete($var) {
		static::$_values = Hash::remove(static::$_values, $var);
	}

/**
 * Used to read and delete a variable from Configure.
 *
 * This is primarily used during bootstrapping to move configuration data
 * out of configure into the various other classes in Noze.
 *
 * @param string $var The key to read and remove.
 *
 * @return array|null
 */
	public static function consume($var) {
		$simple = strpos($var, '.') === false;
		if ($simple && !isset(static::$_values[$var])) {
			return null;
		}
		if ($simple) {
			$value = static::$_values[$var];
			unset(static::$_values[$var]);
			return $value;
		}
		$value = Hash::get(static::$_values, $var);
		static::$_values = Hash::remove(static::$_values, $var);
		return $value;
	}

/**
 * Add a new engine to Configure. Engines allow you to read configuration
 * files in various formats/storage locations.
 *
 * To add a new engine to Configure:
 *
 * `Configure::config('ini', new PhpConfig());`
 *
 * @param string $name The name of the engine being configured. This alias is used later to
 * read values from a specific engine.
 * @param ConfigEngineInterface $engine The engine to append.
 *
 * @return void
 */
	public static function config($name, ConfigEngineInterface $engine) {
		static::$_engines[$name] = $engine;
	}

/**
 * Gets the names of the configured Engine objects.
 *
 * @param string|null $name Engine name.
 *
 * @return array Array of the configured Engine objects.
 */
	public static function configured($name = null) {
		if ($name) {
			return isset(static::$_engines[$name]);
		}
		return array_keys(static::$_engines);
	}

/**
 * Remove a configured engine. This will unset the engine
 * and make any future attempts to use it cause an Exception.
 *
 * @param string $name Name of the engine to drop.
 *
 * @return bool Success.
 */
	public static function drop($name) {
		if (!isset(static::$_engines[$name])) {
			return false;
		}
		unset(static::$_engines[$name]);
		return true;
	}

/**
 * Loads stored configuration information from a resource. You can add
 * config file resource engines with `Configure::config()`.
 *
 * Loaded configuration information will be merged with the current
 * runtime configuration. You can load configuration files from plugins
 * by preceding the filename with the plugin name.
 *
 * `Configure::load('Users.user', 'default')`
 *
 * Would load the 'user' config file using the default config engine. You can load
 * app config files by giving the name of the resource you want loaded.
 *
 * `Configure::load('setup', 'default');`
 *
 * If using `default` config and no engine has been configured for it yet,
 * one will be automatically created using PhpConfig
 *
 * @param string $key name of configuration resource to load.
 * @param string $config Name of the configured engine to use to read the resource identified by $key.
 * @param bool $merge if config files should be merged instead of simply overridden
 *
 * @return mixed false if file not found, void if load successful.
  */
	public static function load($key, $config = 'default', $merge = true) {
		$engine = static::_getEngine($config);
		if (!$engine) {
			return false;
		}
		$values = $engine->read($key);
		if ($merge) {
			$values = Hash::merge(static::$_values, $values);
		}
		return static::write($values);
	}

/**
 * Dump data currently in Configure into $key. The serialization format
 * is decided by the config engine attached as $config. For example, if the
 * 'default' adapter is a PhpConfig, the generated file will be a PHP
 * configuration file loadable by the PhpConfig.
 *
 * ### Usage
 *
 * Given that the 'default' engine is an instance of PhpConfig.
 * Save all data in Configure to the file `my_config.php`:
 *
 * `Configure::dump('my_config.php', 'default');`
 *
 * Save only the error handling configuration:
 *
 * `Configure::dump('error.php', 'default', ['Error', 'Exception'];`
 *
 * @param string $key The identifier to create in the config adapter.
 * This could be a filename or a cache key depending on the adapter being used.
 * @param string $config The name of the configured adapter to dump data with.
 * @param array $keys The name of the top-level keys you want to dump.
 * This allows you save only some data stored in Configure.
 *
 * @throws \Noze\Configure\Exception\Exception if the adapter does not implement a `dump` method.
 *
 * @return bool On success or error.
 */
	public static function dump($key, $config = 'default', $keys = []) {
		$engine = static::_getEngine($config);
		if (!$engine) {
			throw new Exception(sprintf('There is no "%s" config engine.', $config));
		}
		if (!method_exists($engine, 'dump')) {
			throw new Exception(sprintf('The "%s" config engine, does not have a dump() method.', $config));
		}
		$values = static::$_values;
		if (!empty($keys) && is_array($keys)) {
			$values = array_intersect_key($values, array_flip($keys));
		}
		return (bool)$engine->dump($key, $values);
	}

/**
 * Get the configured engine. Internally used by `Configure::load()` and `Configure::dump()`
 * Will create new PhpConfig for default if not configured yet.
 *
 * @param string $config The name of the configured adapter.
 *
 * @return mixed Engine instance or false.
 */
	protected static function _getEngine($config) {
		if (!isset(static::$_engines[$config])) {
			if ($config !== 'default') {
				return false;
			}
			static::config($config, new PhpConfig());
		}
		return static::$_engines[$config];
	}

/**
 * Used to determine the current version of Noze.
 *
 * Usage `Configure::version();`
 *
 * @return string Current version of Noze.
 */
	public static function version()
	{
		if (!isset(static::$_values['Noze']['version'])) {
			require ROOT . DS . 'config' . DS . 'version.php';
			static::write($config);
		}
		return static::$_values['Noze']['version'];
	}

/**
 * Clear all values stored in Configure.
 *
 * @return bool success.
 */
	public static function clear() {
		static::$_values = [];
		return true;
	}
}