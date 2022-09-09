<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 22-08-2016
 * Time: 12:57
 */

namespace CLImax\Event;


/**
 * Class Event
 * @package CLImax\Event
 */
class Event
{
    public $nextRun = null;
    private $events;
    private $identifier;
    private $callback;
    private $intervalMicroseconds;

    /**
     * Event constructor.
     *
     * @param $events
     * @param $identifier
     * @param $callback
     * @param $intervalMicroseconds
     */
    public function __construct(&$events, $identifier, $callback, $intervalMicroseconds)
    {
        $this->events = $events;
        $this->identifier = $identifier;
        $this->callback = $callback;
        $this->intervalMicroseconds = $intervalMicroseconds;

        $this->RecalculateNextRun();
    }

    private function recalculateNextRun()
    {
        $this->nextRun = microtime(true) + $this->intervalMicroseconds;
    }

    public function __destruct()
    {
        //Do whatever
    }

    public function run()
    {
    }
}