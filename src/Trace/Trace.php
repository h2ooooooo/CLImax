<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 31-10-2016
 * Time: 12:35
 */

namespace CLImax\Trace;


class Trace
{
    public static function fromException(\Exception $e) {
        return static::init($e->getTrace());
    }

    public static function init($trace) {
        $trace = new Trace($trace);

        return $trace;
    }

    protected $trace;

    /**
     * Trace constructor.
     *
     * @param array $trace
     */
    public function __construct($trace) {
        foreach ($trace as $traceLine) {
            $this->trace[] = new TraceLine($traceLine);
        }
    }

    /**
     * @param int $startIndex
     *
     * @return TraceLine[]
     */
    public function getTrace($startIndex = 0) {
        return array_slice($this->trace, $startIndex);
    }
}