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
class Event {
    private $events;

    private $identifier;

    private $callback;

    private $intervalMicroseconds;

    public $nextRun = null;

    /**
     * Event constructor.
     *
     * @param $events
     * @param $identifier
     * @param $callback
     * @param $intervalMicroseconds
     */
    public function __construct( &$events, $identifier, $callback, $intervalMicroseconds ) {
        $this->events     = $events;
        $this->identifier = $identifier;
        $this->callback   = $callback;
        $this->intervalMicroseconds = $intervalMicroseconds;

        $this->RecalculateNextRun();
    }

    public function __destruct() {
        //Do whatever
    }

    public function run() {
    }

    private function recalculateNextRun() {
        $this->nextRun = microtime( true ) + $this->intervalMicroseconds;
    }
}