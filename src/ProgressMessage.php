<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class Progress
 * @package CLImax
 *
 * @method \CLImax\Components\ProgressMessage success($output, $prependText = 'SUCCESS', $colour = DebugColour::GREEN, $backgroundColour = DebugColour::STANDARD, $printTime = true)
 * @method \CLImax\Components\ProgressMessage warning($output, $prependText = 'WARNING', $colour = DebugColour::YELLOW, $backgroundColour = DebugColour::STANDARD, $printTime = true)
 * @method \CLImax\Components\ProgressMessage debug($output, $prependText = 'DEBUG', $colour = DebugColour::STANDARD, $backgroundColour = DebugColour::STANDARD, $printTime = true)
 * @method \CLImax\Components\ProgressMessage error($output, $prependText = 'ERROR', $colour = DebugColour::LIGHT_RED, $backgroundColour = DebugColour::STANDARD, $printTime = true)
 * @method \CLImax\Components\ProgressMessage info($output, $prependText = 'INFO', $colour = DebugColour::LIGHT_GREEN, $backgroundColour = DebugColour::STANDARD, $printTime = true)
 * @method \CLImax\Components\ProgressMessage verbose($output, $prependText = 'VERBOSE', $colour = DebugColour::LIGHT_PURPLE, $backgroundColour = DebugColour::STANDARD, $printTime = true)
 */
class ProgressMessage extends Module
{
	private $calls = [
		'success' => [
			'prependText' => 'SUCCESS',
			'colour' => DebugColour::GREEN,
			'debugLevel' => DebugLevel::SUCCESS,
		],
		'warning' => [
			'prependText' => 'WARNING',
			'colour' => DebugColour::YELLOW,
			'debugLevel' => DebugLevel::WARNING,
		],
		'debug' => [
			'prependText' => 'DEBUG',
			'colour' => DebugColour::STANDARD,
			'debugLevel' => DebugLevel::DEBUG,
		],
		'error' => [
			'prependText' => 'ERROR',
			'colour' => DebugColour::LIGHT_RED,
			'debugLevel' => DebugLevel::ERROR,
		],
		'info' => [
			'prependText' => 'INFO',
			'colour' => DebugColour::LIGHT_GREEN,
			'debugLevel' => DebugLevel::INFO,
		],
		'verbose' => [
			'prependText' => 'VERBOSE',
			'colour' => DebugColour::LIGHT_PURPLE,
			'debugLevel' => DebugLevel::VERBOSE,
		],
	];

	public function __call($name, $arguments = []) {
		if (!isset($this->calls[$name])) {
			throw new \Exception(sprintf('Could not find call by name "%s"', $name));
		}

		$call = $this->calls[$name];

		$debugLevel = $call['debugLevel'];
		$output = $arguments[0];
		$prependText = !empty($arguments[1]) ? $arguments[1] : $call['prependText'];
		$colour = !empty($arguments[2]) ? $arguments[2] : $call['colour'];
		$backgroundColour = !empty($arguments[3]) ? $arguments[3] : DebugColour::STANDARD;
		$printTime = !empty($arguments[4]) ? $arguments[4] : true;

		return new \CLImax\Components\ProgressMessage(
			$this->application,
			$output,
			$debugLevel,
			$colour,
			$backgroundColour,
			$prependText,
			$printTime,
		);
	}
}
