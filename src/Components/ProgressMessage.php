<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 27/11/2018
 * Time: 18:08
 */

namespace CLImax\Components;

use CLImax\Application;
use CLImax\DebugColour;
use CLImax\DebugLevel;

class ProgressMessage {
    protected $application;
	protected $output;
	protected $debugLevel;
	protected $colour = DebugColour::STANDARD;
	protected $backgroundColour = DebugColour::STANDARD;
	protected $prependText;
	protected $printTime = true;

	public function __construct(Application $application, $output, $debugLevel, $colour, $backgroundColour, $prependText, $printTime) {
		$this->output = $output;
		$this->application = $application;
		$this->debugLevel = $debugLevel;
		$this->colour = $colour;
		$this->backgroundColour = $backgroundColour;
		$this->prependText = $prependText;
		$this->printTime = $printTime;

		$this->printAffix($output);
	}

	/**
	 * @param string $message
	 *
	 * @return $this
	 */
	public function message($message, $colour = DebugColour::LIGHT_CYAN) {
		return $this->printSuffix(null, null, $message, $colour);
	}


	/**
	 * @param string $message
	 *
	 * @return $this
	 */
	public function success($message = null) {
		return $this->printSuffix('✔', 'Success', $message, DebugColour::LIGHT_GREEN);
	}

	/**
	 * @param string $message
	 *
	 * @return $this
	 */
	public function error($message = null) {
		return $this->printSuffix('✖', 'Error', $message, DebugColour::LIGHT_RED);
	}

	/**
	 * @param string $startMessage
	 *
	 * @return void
	 */
	private function printAffix($startMessage) {
		$this->application->checkScheduledNewline();

		$this->application->printText($this->debugLevel, $startMessage, $this->colour, $this->backgroundColour, $this->prependText, $this->printTime);
		$this->application->scheduleNewline();
	}

	/**
	 * @param string $utf8Icon
	 * @param string $defaultMessage
	 * @param string $message
	 * @param int $colour
	 *
	 * @return $this
	 */
	private function printSuffix($utf8Icon, $defaultMessage, $message, $colour) {
		if ($this->application->isUtf8() && !empty($utf8Icon)) {
			$intro          = $utf8Icon;
			$introSeparator = ' ';
		} else if (!empty($defaultMessage)) {
			$intro = $defaultMessage;
			$introSeparator = ' ';
		} else {
			$intro = '';
			$introSeparator = '';
		}

		$endMessage = $message;

		if (!empty($intro)) {
			$endMessage = $intro . $introSeparator . $endMessage;
		}

		if (!empty($endMessage)) {
			$endMessage = DebugColour::enclose(' ' . $endMessage, $colour);
		} else {
			return $this; // Not printing anything
		}

		$this->application->printText($this->debugLevel, $endMessage, $this->colour, $this->backgroundColour, null, false);
		$this->application->scheduleNewline();

		return $this;
	}
}
