<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 27/11/2018
 * Time: 18:08
 */

namespace CLImax\Components;

use CLImax\DebugLevel;

class ProgressBar {
	protected $application;

	protected $current = 0;
	protected $total = null;

	protected $message = null;

	protected $textColour;
	protected $backgroundColour;

	/** @var ProgressBar */
	protected $subProgressBar;

	/** @var ProgressBar  */
	protected $parentProgressBar;

	protected $previouslyOutput = false;

	protected $disposed = false;
	protected $debugLevel = DebugLevel::ALWAYS_PRINT;

	public function dispose() {
		$this->reset();

		$this->disposed = true;
	}

	public function isDisposed() {
		return $this->disposed;
	}

	public function __construct(\CLImax\Application $application, $total, $textColour = null, $backgroundColour = null, $start = 0, $message = null, $parentProgressBar = null) {
		$this->application = $application;
		$this->total = $total;
		$this->textColour = $textColour;
		$this->backgroundColour = $backgroundColour;
		$this->current = $start;
		$this->message = $message;
		$this->parentProgressBar = $parentProgressBar;
	}

	public function setMessage($message, $draw = true) {
		$this->message = $message;

		if ($draw) {
			if ($this->parentProgressBar) {
				$this->parentProgressBar->draw();
			} else {
				$this->draw();
			}
		}
	}

	public function setCurrent($current, $draw = true) {
		$this->current = $current;

		if ($draw) {
			if ($this->parentProgressBar) {
				$this->parentProgressBar->draw();
			} else {
				$this->draw();
			}
		}
	}

	protected function getSubProgressBar() {
		if (!empty($this->subProgressBar)) {
			if (!$this->subProgressBar->isDisposed()) {
				return $this->subProgressBar;
			} else {
				$this->subProgressBar = null;
			}
		}

		return null;
	}

	public function draw() {
		if ($this->previouslyOutput) {
			$this->reset();
		}

		$message = $this->getProgressString();

		$this->application->printLine(
			$this->debugLevel, // debugLevel
			$message,  // output
			$this->textColour,  // textColour
			$this->backgroundColour, // backgroundColour
			null,  // prependText
			false // printTime
		);

		$this->previouslyOutput = true;

		$subProgressBar = $this->getSubProgressBar();

		if (!empty($subProgressBar)) {
			$subProgressBar->draw();
		}
	}

	public function reset() {
		$this->application->clear->lastLine();
	}

	public function getProgressString() {
		$fraction = $this->current / $this->total;
		$currentFormatted = number_format($this->current, 2);
		$totalFormatted = number_format($this->total, 2);
		$percentFormatted = number_format($fraction * 100, 2);

		if (!empty($this->message)) {
			$message = sprintf('%s | %d / %d (%s%%)', $this->message, $currentFormatted, $totalFormatted, $percentFormatted);
		} else {
			$message = sprintf('%d / %d (%s%%)', $currentFormatted, $totalFormatted, $percentFormatted);
		}

		$progressBarLength = 50;
		$progressBarCurrent = floor($progressBarLength * $fraction);
		$progressBarString = '';

		$completeChar = json_decode('"\u2588"');
		$incompleteChar = json_decode('"\u2591"');

		for ($i = 0; $i < $progressBarCurrent; $i++) {
			$progressBarString .= $completeChar;
		}

		for ($i = $progressBarCurrent; $i < $progressBarLength; $i++) {
			$progressBarString .= $incompleteChar;
		}

		return $progressBarString . ' | ' . $message;
	}

	public function createSubProgressBar($total, $textColour = null, $backgroundColour = null, $start = 0, $message = null) {
		if (empty($textColour)) {
			$textColour = $this->textColour;
		}

		if (empty($backgroundColour)) {
			$backgroundColour = $this->backgroundColour;
		}

		$subProgressBar = new ProgressBar($this->application, $total, $textColour, $backgroundColour, $start, $message);

		$this->subProgressBar = $subProgressBar;

		return $subProgressBar;
	}
}