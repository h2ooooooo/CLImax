<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

class OutputPlugin {
    /** @var string */
    protected $regexPattern;

    /** @var callable */
    protected $callback;

    /**
     * OutputPlugin constructor.
     *
     * @param string $format
     * @param callable $callback
     * @param bool $multiLine Whether or not we should add the "s" regex modifier to the regex pattern
     */
    public function __construct($format, $callback, $multiLine = false) {
        $format = preg_quote($format, '~');
        $format = str_replace('%s', '(.+?)', $format);

        $this->regexPattern = '~' . $format . '~' . ($multiLine ? 's' : '');

        $this->callback = $callback;
    }

    /**
     * @param string $output
     *
     * @return string
     */
    public function mutateOutput($output) {
        return preg_replace_callback($this->regexPattern, function($matches) {
            return call_user_func($this->callback, $matches[1]);
        }, $output);
    }
}