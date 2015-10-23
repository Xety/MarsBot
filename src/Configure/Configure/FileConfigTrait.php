<?php
namespace Mars\Configure\Configure;

use Mars\Configure\Exception\Exception;

trait FileConfigTrait
{
    /**
     * The path this engine finds files on.
     *
     * @var string
     */
    protected $_path = null;

    /**
     * Get file path.
     *
     * @param string $key The identifier to write to. If the key has a . it will be treated
     *  as a plugin prefix.
     * @param bool $checkExists Whether to check if file exists. Defaults to false.
     *
     * @throws \Mars\Configure\Exception\Exception When files don't exist or when
     *  files contain '..' as this could lead to abusive reads.
     *
     * @return string Full file path.
     */
    protected function _getFilePath($key, $checkExists = false)
    {
        if (strpos($key, '..') !== false) {
            throw new Exception('Cannot load/dump configuration files with ../ in them.');
        }

        list($plugin, $key) = pathSplit($key);

        $file = $this->_path . $key;

        $file .= $this->_extension;

        if ($checkExists && !is_file($file)) {
            throw new Exception(sprintf('Could not load configuration file: %s', $file));
        }

        return $file;
    }
}
