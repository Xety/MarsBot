<?php
namespace Noze\Configure\Configure\Engine;

use Noze\Configure\Configure\ConfigEngineInterface;
use Noze\Configure\Configure\FileConfigTrait;
use Noze\Configure\Exception\Exception;

/**
* PHP engine allows Configure to load configuration values from
* files containing simple PHP arrays.
*
* Files compatible with PhpConfig should define a `$config` variable, that
* contains all of the configuration data contained in the file.
*/
class PhpConfig implements ConfigEngineInterface {

	use FileConfigTrait;

/**
 * File extension.
 *
 * @var string
 */
	protected $_extension = '.php';

/**
 * Constructor for PHP Config file reading.
 *
 * @param string|null $path The path to read config files from. Defaults to CONFIG.
 */
	public function __construct($path = null) {
		if ($path === null) {
			$path = CONFIG;
		}
		$this->_path = $path;
	}

/**
 * Read a config file and return its contents.
 *
 * Files with `.` in the name will be treated as values in plugins. Instead of
 * reading from the initialized path, plugin keys will be located using Plugin::path().
 *
 * @param string $key The identifier to read from. If the key has a . it will be treated
 *  as a plugin prefix.
 *
 * @throws \Cake\Core\Exception\Exception when files don't exist or they don't contain `$config`.
*  Or when files contain '..' as this could lead to abusive reads.
*
 * @return array Parsed configuration values.
 */
	public function read($key) {
		$file = $this->_getFilePath($key, true);

		$return = include $file;
		if (is_array($return)) {
			return $return;
		}

		if (!isset($config)) {
			throw new Exception(sprintf('Config file "%s" did not return an array', $key . '.php'));
		}

		return $config;
	}

/**
 * Converts the provided $data into a string of PHP code that can
 * be used saved into a file and loaded later.
 *
 * @param string $key The identifier to write to. If the key has a . it will be treated
 *  as a plugin prefix.
 * @param array $data Data to dump.
 * @return int Bytes saved.
 */
	public function dump($key, array $data) {
		$contents = '<?php' . "\n" . 'return ' . var_export($data, true) . ';';

		$filename = $this->_getFilePath($key);
		return file_put_contents($filename, $contents);
	}
}
