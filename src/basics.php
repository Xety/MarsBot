<?php
use Noze\Configure\Configure;
use Noze\Error\Debugger;

if (!function_exists('debug')) {
/**
 * Prints out debug information about given variable.
 *
 * Only runs if debug level is greater than zero.
 *
 * @param mixed $var Variable to show debug information for.
 *
 * @return void
 */

	function debug($var) {
		if (!Configure::read('debug')) {
			return;
		}

		$trace = Debugger::trace(['start' => 1, 'depth' => 2, 'format' => 'array']);
		$search = [ROOT];

		$file = str_replace($search, '', $trace[0]['file']);
		$line = $trace[0]['line'];
		$lineInfo = sprintf('%s (line %s)', $file, $line);

		$template = <<<TEXT
%s
########## DEBUG ##########
%s
###########################
TEXT;
		$var = Debugger::exportVar($var, 25);
		printf($template, $lineInfo, $var);
	}
}
if (!function_exists('pathSplit')) {
/**
 * Splits a dot syntax path name into its path and class name.
 * If $name does not have a dot, then index 0 will be null.
 *
 * Commonly used like `list($path, $name) = pathSplit($name);`
 *
 * @param string $name The name you want to path split.
 * @param bool $dotAppend Set to true if you want the plugin to have a '.' appended to it.
 * @param string $path Optional default path to use if no path is found. Defaults to null.
 *
 * @return array Array with 2 indexes. 0 => plugin name, 1 => class name
 */

	function pathSplit($name, $dotAppend = false, $path = null) {
		if (strpos($name, '.') !== false) {
			$parts = explode('.', $name, 2);
			if ($dotAppend) {
				$parts[0] .= '.';
			}
			return $parts;
		}
		return [$path, $name];
	}
}
