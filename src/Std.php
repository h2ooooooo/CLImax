<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 07-03-2016
 * Time: 10:05
 */

namespace CLImax;

/**
 * Class Std
 * @package CLImax
 */
class Std extends Module
{
    protected $streams = [];

    /**
     * @return StdStream
     */
    public function in()
    {
        return $this->open('php://stdin', 'r');
    }

    /**
     * @param string $streamName
     * @param string $fileMode
     *
     * @return StdStream
     */
    protected function open($streamName, $fileMode)
    {
        if (!isset($this->streams[$streamName])) {
            $handle = new StdStream($streamName, $fileMode);

            $this->streams[$streamName] = $handle;
        }

        return $this->streams[$streamName];
    }

    /**
     * @return StdStream
     */
    public function out()
    {
        return $this->open('php://stdout', 'w');
    }

    /**
     * @return StdStream
     */
    public function error()
    {
        return $this->open('php://stderr', 'w');
    }
}