<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax\Event;

use CLImax\Module;

/**
 * Class Events
 * @package CLImax\Event
 */
class Events extends Module {
	private $events = array();

	private $microsecondTranslator = array(
		IntervalType::MILLISECONDS => 1000,
		IntervalType::SECONDS      => 1000000, //MILLISECONDS * 1000
		IntervalType::MINUTES      => 60000000, //SECONDS * 60
		IntervalType::HOURS        => 3600000000, //MINUTES * 60
		IntervalType::DAYS         => 86400000000, //HOURS * 24
		IntervalType::WEEKS        => 604800000000, //DAYS * 7
		IntervalType::MONTHS       => 2629800000000, //DAYS * 30.4375 - http://stackoverflow.com/a/5620337/247893
		IntervalType::YEARS        => 31557600000000 //DAYS * 365.25 - http://stackoverflow.com/a/5620337/247893
	);

	/**
	 * @return string
     */
	private function createIdentifier() {
		$identifier = $this->createIdentifierID();

		while ( isset( $this->events[ $identifier ] ) ) {
			$identifier = $this->createIdentifierID();
		}

		return $identifier;
	}

	/**
	 * @return string
     */
	private function createIdentifierID() {
		return uniqid();
	}

	/**
	 * @param      $callback
	 * @param      $interval
	 * @param      $intervalType
	 * @param null $identifier
	 *
	 * @return bool|null|string
     */
    public function add( $callback, $interval, $intervalType = IntervalType::SECONDS, $identifier = null ) {
		if ( $identifier === null ) {
			$identifier = $this->createIdentifier();
		}
		if ( ! isset( $this->events[ $identifier ] ) ) {
			if ( $intervalType === IntervalType::MILLISECONDS ) {
				$intervalMicroseconds = $interval * 1000;
			} else {
				$intervalMicroseconds = $interval;
			}

			$this->events[ $identifier ] = new Event( $this, $identifier, $callback, $intervalMicroseconds);

			return $identifier;
		} else {
			return false;
		}
	}

	/**
	 * @param $identifier
     */
	public function remove( $identifier ) {
		unset( $this->events[ $identifier ] );
	}

	/**
	 * @param $identifier
	 *
	 * @return mixed
     */
    public function run( $identifier ) {
		return $this->events[ $identifier ]->Run();
	}

	/**
	 * @return array
     */
	public function runAll() {
		$return = array();
		foreach ( $this->events as $identifier => $event ) {
			$return[ $identifier ] = $event->Run();
		}

		return $return;
	}

	/**
	 * @return bool
     */
	public function removeAll() {
		foreach ( $this->events as $identifier => $event ) {
			if ( ! $this->remove( $identifier ) ) {
				return false;
			}
		}

		return true;
	}
}