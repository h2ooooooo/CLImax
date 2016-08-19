<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 19-08-2016
 * Time: 15:14
 */

namespace CLImax;


/**
 * Class StdStream
 * @package CLImax
 */
class StdStream
{
    protected $handle;

    /**
     * StdStream constructor.
     *
     * @param string $stream
     * @param string $mode
     */
    public function __construct($stream, $mode = 'r') {
        $this->handle = fopen($stream, $mode);
    }

    public function __destruct() {
        fclose($this->handle);
    }
}