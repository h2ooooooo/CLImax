<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Colours and functions to generate ANSI colour codes (text/background)
 *
 * @see http://www.tldp.org/HOWTO/Bash-Prompt-HOWTO/x329.html
 */
class DebugColour {
	const ESCAPE_CODE = "\033[";

	const STANDARD = 1; //Used to describe the standard CLI colour
	const LIGHT = 2; //Used bitwisely to describe light colours
	const TEXT = 4; //Used to describe that this is a text colour
	const BACKGROUND = 8; //Used to describe that this is a background colour

	const BLACK = 16;
	const RED = 32;
	const GREEN = 64;
	const YELLOW = 128;
	const BROWN = 128; //According to Wikipedia, this might also be brown even though it looks like yellow in most cases - see: http://en.wikipedia.org/wiki/ANSI_escape_code
	const BLUE = 256;
	const PURPLE = 512;
	const MAGENTA = 512;
	const CYAN = 1024;
	const LIGHT_GRAY = 2048;

	const GRAY = 18; //BLACK & LIGHT
	const LIGHT_RED = 34; //RED & LIGHT
	const LIGHT_GREEN = 66; //GREEN & LIGHT
	const LIGHT_YELLOW = 130; //YELLOW & LIGHT
	const LIGHT_BROWN = 130; //BROWN & LIGHT
	const LIGHT_BLUE = 258; //BLUE & LIGHT
	const LIGHT_PURPLE = 514; //PURPLE & LIGHT
	const LIGHT_MAGENTA = 514; //MAGENTA & LIGHT
	const LIGHT_CYAN = 1026; //CYAN & LIGHT
	const WHITE = 2050; //LIGHT_GRAY & LIGHT

	const STYLE_RESET = 0; // 	Reset all attributes
	const STYLE_BOLD = 1; // 	Set "bright" attribute
	const STYLE_BRIGHT = 1; // Set "bright" attribute
	const STYLE_DIM = 2; // 	Set "dim" attribute
	const STYLE_UNDERSCORE = 4; // 	Set "underscore" (underlined text) attribute
	const STYLE_BLINK = 5; // 	Set "blink" attribute
	const STYLE_REVERSE = 7; // 	Set "reverse" attribute
	const STYLE_HIDDEN = 8; // 	Set "hidden" attribute

	/**
	 * @param mixed $value The input value to colour based on contents/type
	 *
	 * @return string|mixed The DebugColour enclosed output (OR the default, if no colour has been added)
	 */
	public static function colourValue($value) {
		if (is_bool($value)) {
			return DebugColour::enclose(
				$value ? 'TRUE' : 'FALSE',
				$value ? DebugColour::LIGHT_GREEN : DebugColour::LIGHT_RED
			);
		} else if (is_int($value) || is_float($value)) {
			return DebugColour::enclose($value, DebugColour::LIGHT_BLUE);
		} else if (is_string($value)) {
			return DebugColour::enclose($value, DebugColour::LIGHT_MAGENTA);
		}

		return $value;
	}

	/**
	 * @param int $textColour
	 * @param int $backgroundColour
	 * @param int $textStyle
	 *
	 * @return \CLImax\DebugColourBuilder
	 */
	public static function buildString($textColour = DebugColour::STANDARD, $backgroundColour = DebugColour::STANDARD, $textStyle = DebugColour::STYLE_RESET) {
		return new DebugColourBuilder($textColour, $backgroundColour, $textStyle);
	}

	/**
	 * Gets the ANSI escape code is used to change the colour of the CLI prompt
	 *
	 * @param int $textColour A colour for the text based on the constants of this class
	 * @param int $backgroundColour A colour for the background of the text, based on the constants of this class
	 * @param int $textStyle One of the STYLE_ constants
	 *
	 * @return string An ANSI escaped string such as \033[33m
	 */
	public static function getColourCode(
		$textColour = DebugColour::STANDARD,
		$backgroundColour = DebugColour::STANDARD,
		$textStyle = null
	) {
		if ( $textColour === null ) {
			$textColour = DebugColour::STANDARD;
		}

		if ( $backgroundColour === null ) {
			$backgroundColour = DebugColour::STANDARD;
		}

		$escapeCodes = [];

		if ($textColour & DebugColour::LIGHT) {
			$escapeCodes[] = DebugColour::STYLE_BRIGHT;
		}

		if ($textStyle !== null) {
			$escapeCodes[] = $textStyle;
		}

		if ( ! ( $textColour & DebugColour::STANDARD )) {
			$escapeCodes[] = self::getColourCodeCLI($textColour | DebugColour::TEXT);
		}

		if ( ! ( $backgroundColour & DebugColour::STANDARD )) {
			$escapeCodes[] = self::getColourCodeCLI( $backgroundColour | DebugColour::BACKGROUND );
		}

		if (empty($escapeCodes)) {
			$escapeCodes[] = DebugColour::STYLE_RESET;
		}

		sort($escapeCodes); // Make sure the style reset is the absolute first part

		return self::styleCode(implode(';', $escapeCodes));
	}

	/**
	 * Gets either black/white based on the background colour, and what would be most visible to see
	 *
	 * @param int $backgroundColour A colour for the background of the text, based on the constants of this class
	 *
	 * @return int A colour that looks OK on the background chosen in $backgroundColour
	 */
	public static function getVisibleTextColour( $backgroundColour = DebugColour::STANDARD ) {
		$colours = array(
			DebugColour::BLACK => array(
				DebugColour::YELLOW,
				DebugColour::CYAN,
				DebugColour::GRAY,
				DebugColour::LIGHT_GREEN,
				DebugColour::LIGHT_YELLOW,
				DebugColour::LIGHT_BLUE,
				DebugColour::LIGHT_PURPLE,
				DebugColour::LIGHT_CYAN,
				DebugColour::WHITE
			),
			DebugColour::WHITE => array(
				DebugColour::BLACK,
				DebugColour::RED,
				DebugColour::GREEN,
				DebugColour::BLUE,
				DebugColour::PURPLE,
				DebugColour::GRAY,
				DebugColour::RED
			)
		);
		foreach ( $colours as $textColour => $visibleBackgroundColours ) {
			if ( in_array( $backgroundColour, $visibleBackgroundColours ) ) {
				return $textColour;
			}
		}

		return DebugColour::STANDARD;
	}

	/**
	 * @param      $text
	 * @param int  $textColour
	 * @param int  $backgroundColour
	 * @param null $textStyle
	 * @param callable|bool $reset
	 *
	 * @return string
	 */
	public static function enclose($text, $textColour = DebugColour::STANDARD,
		$backgroundColour = DebugColour::STANDARD, $textStyle = null, $reset = true) {

		$enclosedText = self::getColourCode($textColour, $backgroundColour, $textStyle) . $text;

		if ($reset === true) {
			$enclosedText .= self::reset();
		} else if (is_callable($reset)) {
			$enclosedText .= call_user_func($reset, $text, $textColour, $backgroundColour, $textStyle);
		}

		return $enclosedText;
	}

	/**
	 * resets the colour of the UNIX console to the default
	 *
	 * @param null $textColour
	 * @param null $backgroundColour
	 * @param null $textStyle
	 *
	 * @return string The escape code with the flags "0m" (equal to "all attributes off)
	 */
	public static function reset(
		$textColour = null,
		$backgroundColour = null,
		$textStyle = null) {

		$resetCode = self::styleCode(self::STYLE_RESET);

		if (!empty($textColour) || !empty($backgroundColour) || !empty($textStyle)) {
			$resetCode .= self::getColourCode(
				!empty($textColour) ? $textColour : self::STANDARD,
				!empty($backgroundColour) ? $backgroundColour : self::STANDARD,
				!empty($textStyle) ? $textStyle : null
			);
		}
		return $resetCode;
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	public static function styleCode($value) {
		return self::ESCAPE_CODE . $value . 'm';
	}

	/**
	 * Gets the raw colour code (without escape characters)
	 *
	 * @param int $colour A colour with DebugColour::TEXT or DebugColour::BACKGROUND bitwisely added to it
	 *
	 * @return int Returns the colour code
	 */
	private static function getColourCodeCLI( $colour ) {
		$code = 0;

		if ( $colour & DebugColour::TEXT ) {
			$code = 30;
		} else if ( $colour & DebugColour::BACKGROUND ) {
			$code = 40;
		}

		if ( $colour & DebugColour::BLACK ) {
			$code += 0;
		} else if ( $colour & DebugColour::RED ) {
			$code += 1;
		} else if ( $colour & DebugColour::GREEN ) {
			$code += 2;
		} else if ( $colour & DebugColour::YELLOW ) {
			$code += 3;
		} else if ( $colour & DebugColour::BLUE ) {
			$code += 4;
		} else if ( $colour & DebugColour::PURPLE ) {
			$code += 5;
		} else if ( $colour & DebugColour::CYAN ) {
			$code += 6;
		} else if ( $colour & DebugColour::LIGHT_GRAY ) {
			$code += 7;
		}

		return $code;
	}

	/**
	 * Cleans a string from all the escaped ANSI characters
	 * Useful for eg. logging
	 *
	 * @param $string
	 *
	 * @return string string clean of \033[0m ANSI characters
	 */
	public static function cleanForColours( $string ) {
		$colours = array(
			self::STANDARD,
			self::BLACK,
			self::RED,
			self::GREEN,
			self::YELLOW,
			self::BLUE,
			self::PURPLE,
			self::CYAN,
			self::LIGHT_GRAY,
			self::GRAY,
			self::LIGHT_RED,
			self::LIGHT_GREEN,
			self::LIGHT_YELLOW,
			self::LIGHT_BLUE,
			self::LIGHT_PURPLE,
			self::LIGHT_CYAN,
			self::WHITE
		);

		return str_replace( $colours, '', $string );
	}

	/**
	 * Escape the escape sequences
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function escapeSequences($string) {
		$colourReplacements = [
			self::STANDARD => 'STANDARD',
			self::BLACK => 'BLACK',
			self::RED => 'RED*',
			self::GREEN => 'GREEN',
			self::YELLOW => 'YELLOW',
			self::BLUE => 'BLUE',
			self::PURPLE => 'PURPLE',
			self::CYAN => 'CYAN',
			self::LIGHT_GRAY => 'LIGHT_CYAN',
			self::GRAY => 'GRAY',
			self::LIGHT_RED => 'LIGHT_RED',
			self::LIGHT_GREEN => 'LIGHT_GREEN',
			self::LIGHT_YELLOW => 'LIGHT_YELLOW',
			self::LIGHT_BLUE => 'LIGHT_BLUE',
			self::LIGHT_PURPLE => 'LIGHT_PURPLE',
			self::LIGHT_CYAN => 'LIGHT_CYAN',
			self::WHITE	  => 'WHITE'
		];

		$string = preg_replace_callback('~\\033\[(.+?)m~', function($match) {
			$matchSplit = explode(';', $match[1]);

			foreach ($matchSplit as &$matchPart) {
				if (isset($colourReplacements[$matchPart])) {
					//$matchPart = $colourReplacements[$matchPart];
				}
			}

			unset($matchPart);

			return sprintf('\\033[%sm', implode(';', $matchSplit));
		}, $string);

		return $string;
	}
}

/**
 * Class DebugColourBuilder
 * @package CLImax
 */
class DebugColourBuilder {
	protected $string = '';

	protected $savedTextColour = DebugColour::STANDARD;
	protected $savedBackgroundColour = DebugColour::STANDARD;
	protected $savedTextStyle = DebugColour::STYLE_RESET;

	protected $textColour;
	protected $backgroundColour;
	protected $textStyle;

	/**
	 * DebugColourBuilder constructor.
	 *
	 * @param int $textColour
	 * @param int $backgroundColour
	 * @param int $textStyle
	 */
	public function __construct($textColour = DebugColour::STANDARD, $backgroundColour = DebugColour::STANDARD, $textStyle = DebugColour::STYLE_RESET) {
		$this->string .= DebugColour::reset();

		if ($textColour !== null || $backgroundColour !== null || $textStyle !== null) {
			$this->setColour($textColour, $backgroundColour, $textStyle);
		}
	}

	/**
	 * @param null $textColour
	 * @param null $backgroundColour
	 * @param null $textStyle
	 *
	 * @return $this
	 */
	public function setColour($textColour = null, $backgroundColour = null, $textStyle = null) {
		$this->textColour = $textColour;
		$this->backgroundColour = $backgroundColour;

		if ($textStyle !== null) {
			$this->setStyle($textStyle);
		}

		//$this->string .= DebugColour::getColourCode($textColour, $backgroundColour, $this->textStyle);

		return $this; // For chaining
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function blink() {
		return $this->setStyle(DebugColour::STYLE_BLINK);
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function bold() {
		return $this->setStyle(DebugColour::STYLE_BOLD);
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function bright() {
		return $this->setStyle(DebugColour::STYLE_BRIGHT);
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function dim() {
		return $this->setStyle(DebugColour::STYLE_DIM);
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function hidden() {
		return $this->setStyle(DebugColour::STYLE_HIDDEN);
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function reverse() {
		return $this->setStyle(DebugColour::STYLE_REVERSE);
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function underscore() {
		return $this->setStyle(DebugColour::STYLE_UNDERSCORE);
	}

	/**
	 * @param $textStyle
	 *
	 * @return $this
	 */
	public function setStyle($textStyle) {
		$this->textStyle = $textStyle;

		//$this->string .= str_replace("\033", "\\033", DebugColour::styleCode($textStyle));

		return $this; // For chaining
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function resetColour() {
		return $this->setColour(DebugColour::STANDARD, DebugColour::STANDARD);
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function resetStyle() {
		return $this->setStyle(DebugColour::STYLE_RESET);
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function reset() {
		return $this->resetColour()->resetStyle();
	}

	/**
	 * @return $this
	 */
	public function saveColour() {
		$this->savedTextColour = $this->textColour;
		$this->savedBackgroundColour = $this->backgroundColour;

		return $this; // For chaining
	}

	/**
	 * @return $this
	 */
	public function saveStyle() {
		$this->savedTextStyle = $this->textStyle;

		return $this; // For chaining
	}

	/**
	 * @return $this
	 */
	public function save() {
		return $this->saveColour()->saveStyle(); // For chaining
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function revertColour() {
		return $this->setColour($this->savedTextColour, $this->savedBackgroundColour);
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function revertStyle() {
		return $this->setStyle($this->savedTextStyle);
	}

	/**
	 * @return \CLImax\DebugColourBuilder
	 */
	public function revert() {
		return $this->revertColour()->revertStyle();
	}

	/**
	 * @param $bool
	 *
	 * @return $this
	 */
	public function writeBool($bool) {
		if ($bool) {
			$this->write('YES', DebugColour::WHITE, DebugColour::GREEN);
		} else {
			$this->write('NO', DebugColour::WHITE, DebugColour::RED);
		}

		return $this;
	}

	/**
	 * @param      $format
	 * @param      $arguments
	 * @param null $textColour
	 * @param null $backgroundColour
	 * @param null $textStyle
	 *
	 * @return \CLImax\DebugColourBuilder
	 */
	public function writef($format, $arguments, $textColour = null, $backgroundColour = null, $textStyle = null) {
		return $this->write(vsprintf($format, $arguments), $textColour, $backgroundColour, $textStyle);
	}

	/**
	 * @param      $text
	 * @param null $textColour
	 * @param null $backgroundColour
	 * @param null $textStyle
	 *
	 * @return $this
	 */
	public function write($text, $textColour = null, $backgroundColour = null, $textStyle = null) {
		$currentTextColour = $this->textColour;
		$currentBackgroundColour = $this->backgroundColour;
		$currentTextStyle = $this->textStyle;

		if (!empty($textColour) || !empty($backgroundColour)) {
			$this->setColour($textColour, $backgroundColour);
		}

		if (!empty($textStyle)) {
			$this->setStyle($textStyle);
		}

		$this->string .= DebugColour::getColourCode($this->textColour, $this->backgroundColour, $this->textStyle);

		$this->string .= $text;

		if (!empty($textColour) || !empty($backgroundColour)) {
			$this->setColour($currentTextColour, $currentBackgroundColour);
		}

		if (!empty($textStyle)) {
			$this->setStyle($currentTextStyle);
		}

		return $this; // For chaining
	}

	/**
	 * @param      $line
	 * @param null $lineColour
	 * @param null $lineBackgroundColour
	 * @param null $lineStyle
	 *
	 * @return \CLImax\DebugColourBuilder
	 */
	public function writeLine($line, $lineColour = null, $lineBackgroundColour = null, $lineStyle = null) {
		return $this->write($line . PHP_EOL, $lineColour, $lineBackgroundColour, $lineStyle);
	}

	/**
	 * @return string
	 */
	public function toString() {
		return $this->string;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}
}