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

		$this->printStartMessage($output);
	}

	public function success($message = null) {
		$this->printEndMessage($message, '✔', 'Success', $message, DebugColour::LIGHT_GREEN);
	}

	public function error($message = null) {
		$this->printEndMessage($message, '✖', 'Error', $message, DebugColour::LIGHT_RED);
	}

	private function printStartMessage($startMessage) {
		$this->application->printText($this->debugLevel, $startMessage, $this->colour, $this->backgroundColour, $this->prependText, $this->printTime);
	}

	private function printEndMessage($endMessage, $utf8Icon, $defaultMessage, $extraMessage, $colour) {
		if ($this->application->isUtf8() && !empty($utf8Icon)) {
			$endMessage = $utf8Icon . (!empty($endMessage) ? ' ' . $endMessage : '');
		} else if (!empty($endMessage)) {
			$endMessage = $defaultMessage . ' - ' . $endMessage;
		} else {
			$endMessage = $defaultMessage;
		}

		$endMessage = DebugColour::enclose(' ' . $endMessage, $colour);

		$this->application->printText($this->debugLevel, $endMessage, $this->colour, $this->backgroundColour, null, false);
		$this->application->newLine();
	}
}
