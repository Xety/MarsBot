<?php
use Mars\Configure\Configure;
use Mars\Error\Debugger;

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
    function debug($var)
    {
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
%s
TEXT;
        $var = Debugger::exportVar($var, 25);
        printf($template, $lineInfo, $var, PHP_EOL);
    }
}
