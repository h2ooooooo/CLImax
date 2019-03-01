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
	 * @param mixed $value The input value to get the colour of based on contents/type
	 *
	 * @return int The DebugColour to use for this type
	 */
	public static function getValueColour($value) {
		if (is_null($value)) {
			return DebugColour::GRAY;
		} else if (is_bool($value)) {
			return $value ? DebugColour::LIGHT_GREEN : DebugColour::LIGHT_RED;
		} else if (is_int($value) || is_float($value)) {
			return DebugColour::LIGHT_BLUE;
		} else if (is_string($value)) {
			return DebugColour::LIGHT_MAGENTA;
		}

		return DebugColour::STANDARD;
	}

	/**
	 * @param mixed $value The input value to colour based on contents/type
	 *
	 * @return string|mixed The DebugColour enclosed output (OR the default, if no colour has been added)
	 */
	public static function colourValue($value) {
		$colour = static::getValueColour($value);

		if ($value === null) {
			$value = 'NULL';
		} else if (is_bool($value)) {
			$value = $value ? 'TRUE' : 'FALSE';
		}

		return DebugColour::enclose($value, $colour);
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
	 * @param bool $replaceColours
	 *
	 * @return string
	 */
	public static function escapeSequences($string, $replaceColours = false) {
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

		$string = preg_replace_callback('~\\033\[(.+?)m~', function($match) use ($replaceColours, $colourReplacements) {
			$matchSplit = explode(';', $match[1]);

			if ($replaceColours) {
				foreach ($matchSplit as $key => $matchPart) {
					if (isset($colourReplacements[ $matchPart ])) {
						$matchSplit[$key] = $colourReplacements[ $matchPart ];
					}
				}
			}

			return sprintf('\\033[%sm', implode(';', $matchSplit));
		}, $string);

		return $string;
	}

	/**
	 * Returns the calculated end sequence after iterating through all the sequences in the string
	 *
	 * @param string $string
	 *
	 * @return string The calculated string or an empty string if no escape sequences were found
	 */
	public static function getCalculatedSequence($string) {
		// Let's try to find some escape sequences, but let's return an empty string if we don't find any
		if (!preg_match_all('~\\033\[([\d;]+)m~', $string, $matches)) {
			return '';
		}

		$style = null;
		$text = null;
		$background = null;

		foreach ($matches[1] as $fullEscapeSequence) {
			// The full escape sequence is separated by semicolons
			$escapeSequences = explode(';', $fullEscapeSequence);

			foreach ($escapeSequences as $escapeSequence) {
				// Make sure it's an int to avoid awkward type juggling
				$escapeSequence = (int)$escapeSequence;

				if ($escapeSequence >= 0 && $escapeSequence <= 8) {
					$style = $escapeSequence;
				} else if ($escapeSequence >= 30 && $escapeSequence <= 37) {
					$text = $escapeSequence;
				} else if ($escapeSequence >= 40 && $escapeSequence <= 47) {
					$background = $escapeSequence;
				}
			}
		}

		$styles = implode(';', array_filter([$style, $text, $background]));

		return DebugColour::styleCode($styles);
	}

    /**
     * @param string|array $json The raw JSON (or an array that can be converted to JSON)
     *
     * @note If an array is passed to $json, it will be converted using json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
     *
     * @return string The CLI coloured JSON
     *
     * @see https://stackoverflow.com/a/7220510/247893 The regex, but converted from javascript
     *
     * @throws \Exception
     */
    public static function colourJson($json) {
        if (is_array($json)) {
            if (!function_exists('json_encode')) {
                throw new \Exception(sprintf('An array was passed to DebugCOlour::colourJson, but the JSON extension was not found in your PHP installation'));
            }

            $json = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $regex = <<<REGEXP
/("(\\\\u[a-zA-Z0-9]{4}|\\\\[^u]|[^\\\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/
REGEXP;

        return preg_replace_callback(
            $regex,
            function ($matches) {
                $value  = $matches[0];

                if ($value === 'null') {
                    // The value is NULL
                    return DebugColour::enclose($value, DebugColour::CYAN);
                } else if ($value === 'false' || $value === 'true') {
                    // The value is a boolean
                    return DebugColour::enclose($value, DebugColour::LIGHT_YELLOW);
                } else if (preg_match('/^"/', $value)) {
                    if (preg_match('/:$/', $value)) {
                        // The value is a JSON key
                        return DebugColour::enclose($value, DebugColour::LIGHT_GREEN);
                    } else {
                        // The value is a JSON string in a value
                        return DebugColour::enclose($value, DebugColour::GREEN);
                    }
                } else if (preg_match('~^\d+$~', $value)) {
                    // The value is an integer
                    return DebugColour::enclose($value, DebugColour::LIGHT_CYAN);
                } else if (preg_match('~^\d+\.\d+$~', $value)) {
                    // The value is a float
                    return DebugColour::enclose($value, DebugColour::LIGHT_CYAN);
                } else {
                    return $value;
                }
            },
            $json
        );
    }
}