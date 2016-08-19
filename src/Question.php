<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class Question
 * @package CLImax
 */
class Question extends Module {
	/**
	 * Creates a confirm prompt
	 *
	 * @param $question
	 * @param null $default
	 * @param null $options
	 *
	 * @return bool
	 */
	public function confirm( $question, $default = null, $options = null ) {
		if ( empty( $options ) ) {
			$options = array();
		}

		if ( $default !== null ) {
			$options['default'] = ( $default ? 'y' : 'n' );
		}

		$possibleAnswers = array(
			'positive' => array( true, 1, '1', 'y', 'yes', 'true' ),
			'negative' => array( false, 0, '0', 'n', 'no', 'false' )
		);

		$answer = $this->ask( $question, array_merge( $options, array(
			'possibleOptions'     => array_merge( $possibleAnswers['positive'], $possibleAnswers['negative'] ),
			'caseSensitive'       => false,
			'showPossibleOptions' => false,
			'canBeBlank'          => false
		) ) );

		$answer = strtolower( $answer );

		if ( in_array( $answer, $possibleAnswers['positive'], true ) ) {
			return true;
		} else {
			if ( ! in_array( $answer, $possibleAnswers['negative'], true ) ) {
				ob_start();
				var_dump($answer);
				$answer = ob_get_clean();

				$this->application->fatal( sprintf('Did not understand "%s". Defaults to no.', $answer));
			}

			return false;
		}
	}

	/**
	 * Asks a question.
	 *
	 * @param string $question The question to ask the CLI user
	 * @param array $options An array of key-value pair options - the possible keys are as follows:
	 *        array    'possibleOptions'        The possible options. If the answer to the question is not in this key (and it is not null), it will ask the question again
	 *        bool    'showPossibleOptions'    Whether or not to show the possible options after asking the question. Eg. "Should we do it? (y/n): "
	 *        bool    'caseSensitive'        Whether or not the answer is case sensitive. In short, if this is false, it will simply turn the answer and possible options into lowercase
	 *        mixed    'default'                The default value that should be selected, if the user was to just press enter (if null, there is no default, and it will ask the question again if 'canBeBlank' is false)
	 *        bool    'canBeBlank'            Whether or not the answer can be blank. If this is true, and the user doesn't answer anything, it will accept it and return nothing as a response
	 *        int    'textColour'            The colour of the text of the question
	 *        int    'backgroundColour'        The colour of the background colour of the text of the question
	 *        string 'cast' What type this should be cast as
	 *        callable 'callback' The callback to validate the value - function($answer) { return true; }
	 *        string 'argument' If you want an argument to take presedence over the question you can type the name here - if the argument is specified the question will not be asked
	 *
	 * @return string The answer to the question
	 */
	public function ask( $question, $options = null ) {
		$defaultOptions = array(
			'possibleOptions'     => null,
			'showPossibleOptions' => false,
			'caseSensitive'       => false,
			'default'             => null,
			'canBeBlank'          => false,
			'textColour'          => null,
			'backgroundColour'    => null,
			'cast'                => null,
			'callback'            => null,
			'argument' 			 => null,
			'argumentExists' 	=> null,
		);

		$options = ( $options !== null ? array_merge( $defaultOptions, $options ) : $defaultOptions );

		$questionFull = $question . ( $options['default'] !== null ? ' (' . $options['default'] . '): ' : ( $options['showPossibleOptions'] ? ' (' . implode( '/',
					$options['possibleOptions'] ) . '): ' : ' ' ) );
		$this->application->printText( DebugLevel::ALWAYS_PRINT, $questionFull, $options['textColour'],
			$options['backgroundColour'], null, false );

		if (!empty($options['argument'])) {
			if ($value = $this->application->arguments->get($options['argument'])) {
				$this->application->printText( DebugLevel::ALWAYS_PRINT, $value, null, null, false, false);

				echo PHP_EOL;

				if (!empty($options['possibleOptions']) && !in_array($value, $options['possibleOptions'])) {
					$this->application->fatal(sprintf('Argument %s value %s was not allowed as a valid answer', $options['argument'], $value));

					return null;
				}

				return $value;
			}
		}

		if (!empty($options['argumentExists'])) {
			if ($this->application->arguments->has($options['argumentExists'])) {
				return true;
			}
		}

		$response = null;

		while ( ! @feof( STDIN ) ) {
			$inputRead = @fread( STDIN, 1024 );

			if ( $inputRead === false ) {
				break;
			}

			$inputRead = trim( $inputRead );

			if ( $inputRead == '' ) {
				if ( $options['default'] !== null ) {
					$response = $options['default'];
				} else if ( $options['canBeBlank'] ) {
					$response = '';
				}
			} else {
				if ( $options['possibleOptions'] !== null ) {
					if ( $options['caseSensitive'] ) {
						$found = array_search( $inputRead, $options['possibleOptions'] );
					} else {
						$found = array_search( strtolower( $inputRead ),
							array_map( 'strtolower', $options['possibleOptions'] ) );
					}
					if ( $found !== false ) {
						$response = $options['possibleOptions'][ $found ];
					}
				} else {
					$response = $inputRead;
				}
			}

			if ( $response !== null && $response !== false ) {
				if ( ! empty( $options['cast'] ) ) {
					@settype( $response, $options['cast'] );
				}

				if ( ! empty( $options['callback'] ) ) {
					$response = call_user_func( $options['callback'], $response );

					if ( $response === false || $response === null ) {
						$response = null;
					}
				}
			}

			if ( $response !== null ) {
				break;
			} else {
				$this->application->printText( DebugLevel::ALWAYS_PRINT, $questionFull, $options['textColour'],
					$options['backgroundColour'], null, false );
			}
		}

		if ( $response === null ) {
			$this->application->fatal( 'Could not read STDIN' );
		}

		return $response;
	}

	/**
	 * @param      $question
	 * @param      $choices
	 * @param null $options
	 * @param bool $defaultToggleValue
	 *
	 * @return array
	 * @throws \Exception
     */
    public function askToggled($question, $choices, $options = null, $defaultToggleValue = true) {
		$toggleStatus = [];

		if (is_array($defaultToggleValue)) {
			foreach ( $choices as $key => $value ) {
				$toggleStatus[ $key ] = isset($defaultToggleValue[$key]) ? $defaultToggleValue[$key] : false;
			}
		} else {
			foreach ( $choices as $key => $value ) {
				$toggleStatus[ $key ] = $defaultToggleValue;
			}
		}

		$defaultOptions = [
			'canBeBlank' => true,
			'return' => QuestionReturn::KEY
		];

		if (!empty($options)) {
			$options = array_merge($defaultOptions, $options);
		} else {
			$options = $defaultOptions;
		}

		if (isset($options['displayCallback'])) {
			throw new \Exception('displayCallback cannot be overwritten');
		}

		$selectedFormat = DebugColour::buildString(DebugColour::LIGHT_GREEN)->bold()->write('[X]')->reset()->write(' %s')->toString();
		$deselectedFormat = DebugColour::buildString(DebugColour::LIGHT_RED)->bold()->write('[ ]')->reset()->write(' %s')->toString();

		do {
			$answer = $this->askMultipleChoice($question, $choices, array_merge($options, [
				'displayCallback' => function($key, $value) use ($toggleStatus, $selectedFormat, $deselectedFormat) {
					if ($toggleStatus[$key]) {
						return sprintf($selectedFormat, $value);
					} else {
						return sprintf($deselectedFormat, $value);
					}
				}
			]));

			if (!empty($answer)) {
				$toggleStatus[$answer] = !$toggleStatus[$answer];

				// It's going to run again, let's clean the buffer
				$this->application->clear->lines(count($toggleStatus) + 2)->application->scroll->up();
			}
		} while ($answer !== null);

		$toggledAnswers = [];

		foreach ($toggleStatus as $answer => $toggled) {
			if ($toggled) {
				$toggledAnswers[$answer] = $choices[$answer];
			}
		}

		return $toggledAnswers;
	}

	/**
	 * Asks a multiple choice question, and shows the user the possible options.
	 *
	 * @param string $question The question to ask the CLI user
	 * @param array $choices The possible choices. If the answer to the question is not in this key (and it is not null), it will ask the question again
	 * @param array $options An array of key-value pair options - the possible keys are as follows:
	 *        int    'style'             The style from QuestionStyle (see this class for more info)
	 *        int    'display'           The display style from QuestionDisplay (see this class for more info)
	 *      bool    'acceptValueAnswers' Whether or not to accept the actual answers as an answer or to only accept keys (answers have first priority)
	 *        int    'layoutStyle'         The layout style from QuestionLayoutStyle (see this class for more info)
	 *        mixed    'default'             The default value that should be selected, if the user was to just press enter
	 *        int    'textColour'         The colour of the text of the question
	 *        int    'backgroundColour'   The colour of the background colour of the text of the question
	 *        bool    'padKeys'             Whether or not to pad keys to the key with the maximum length
	 *            Turns
	 *                [red] Red
	 *                [blue] Blue
	 *            into
	 *                [red ] Red
	 *                [blue] Blue
	 *        int    'padType'             The padding type if padKeys is true (anything that works with str_pad works here - eg. STR_PAD_LEFT, STR_PAD_RIGHT, STR_PAD_BOTH)
	 *        int    'return'             The return method from QuestionReturn (see this class for more info)
	 *        bool   'canBeBlank'         Specifies whether or not the answer may be blank
	 *        string 'argument' If you want an argument to take presedence over the question you can type the name here - if the argument is specified the question will not be asked
	 *
	 * @return string The answer to the question
	 * @throws \Exception
	 */
	public function askMultipleChoice( $question, $choices, $options = null ) {
		$defaultOptions = array(
			'style'              => QuestionStyle::NUMBERS,
			'display'            => QuestionDisplay::VALUE,
			'acceptValueAnswers' => true,
			'acceptKeyAnswers' => false,
			'layoutStyle'        => QuestionLayoutStyle::GRID,
			'default'            => null,
			'textColour'         => null,
			'canBeBlank'         => false,
			'backgroundColour'   => null,
			'padKeys'            => true,
			'padType'            => null,
			'return'             => QuestionReturn::VALUE,
			'argument' 			 => null,
		);

		$options = ( $options !== null ? array_merge( $defaultOptions, $options ) : $defaultOptions );

		$questionFull = $question;

		if ($options['display'] === QuestionDisplay::KEY) {
			$choices = array_keys($choices);

			$options['return'] = $options['return'] === QuestionReturn::KEY ? QuestionReturn::VALUE : QuestionReturn::KEY;
		}

		if (!empty($options['argument'])) {
			$options['style'] = QuestionStyle::VALUES;
		}

		$choiceIndex    = array();
		$choiceIndexKey = array();

		if ( $options['style'] === QuestionStyle::NUMBERS || $options['style'] === QuestionStyle::NUMBERS_ACCEPT_KEYS ) {
			$i = 1;

			foreach ( $choices as $key => $choice ) {
				$index                    = ( $i ++ );
				$choiceIndex[ $index ]    = $choice;
				$choiceIndexKey[ $index ] = $key;
			}

			$i = 1;

			foreach ( $choices as $key => $choice ) {
				$index = ( $i ++ );
				if ( $options['default'] !== null && $options['default'] === $key ) {
					$options['default'] = $index;

					break;
				}
			}

		} else if ( $options['style'] === QuestionStyle::KEYS ) {
			foreach ( $choices as $key => $choice ) {
				$choiceIndex[ $key ] = $choice;
			}
		} else if ($options['style'] === QuestionStyle::VALUES) {
			foreach ( $choices as $key => $choice ) {
				$choiceIndex[ $choice ] = $choice;
			}
		}

		$optionColourCode  = DebugColour::getColourCode( DebugColour::GREEN );
		$regularColourCode = DebugColour::getColourCode( $options['textColour'], $options['backgroundColour'] );

		$hasDisplayCallback = false;

		$choiceIndexDisplay = [];

		if (!empty($options['displayCallback'])) {
			if (!is_callable($options['displayCallback'])) {
				throw new \Exception(sprintf('displayCallback was not a callable'));
			}

			foreach ($choiceIndex as $key => $value) {
				$choiceIndexDisplay[$key] = call_user_func($options['displayCallback'], $choiceIndexKey[$key], $value);
			}

			$hasDisplayCallback = true;
		} else {
			$choiceIndexDisplay = $choiceIndex; // Do nothing with it
		}

		if ($options['style'] === QuestionStyle::VALUES) {
			foreach ( $choiceIndexDisplay as $key => $choice ) {
				if ($hasDisplayCallback) {
					$choice = call_user_func($options['displayCallback'], $choiceIndexKey[$key], $choice);
				}

				$questionFull .= "\n" . $optionColourCode . ' * ' . $regularColourCode . $choice;
			}

			$questionFull .= "\n " . $optionColourCode . '>';
		} else {
			if ( $options['padKeys'] ) {
				if ( $options['padType'] === null ) {
					if ( $options['style'] === QuestionStyle::NUMBERS ) {
						$options['padType'] = STR_PAD_LEFT;
					} else {
						$options['padType'] = STR_PAD_RIGHT;
					}
				}

				$maxKeyLength = 0;

				foreach ( $choiceIndexDisplay as $key => $choice ) {
					$maxKeyLength = max( $maxKeyLength, strlen( $key ) );
				}

				foreach ( $choiceIndexDisplay as $key => $choice ) {
					$questionFull .= "\n" . $optionColourCode . '[' . str_pad( $key, $maxKeyLength, ' ',
							$options['padType'] ) . '] ' . $regularColourCode . $choice;
				}

				$questionFull .= str_pad( "\n ", $maxKeyLength + 2, ' ' ) . $optionColourCode . '>';
			} else {
				foreach ( $choiceIndexDisplay as $key => $choice ) {
					if ($hasDisplayCallback) {
						$choice = call_user_func($options['displayCallback'], $choiceIndexKey[$key], $choice);
					}

					$questionFull .= "\n" . $optionColourCode . '[' . $key . '] ' . $regularColourCode . $choice;
				}
				$questionFull .= "\n " . $optionColourCode . '> ';
			}
		}

		$possibleOptions = array_keys( $choiceIndex );

		if ( $options['acceptValueAnswers'] ) {
			foreach ( $choices as $choice ) {
				$choiceIndexKey[ $choice ] = $choice;
				$choiceIndex[ $choice ]    = $choice;
			}
		}

		if ( $options['acceptKeyAnswers'] ) {
			foreach ( $choices as $key => $choice ) {
				$choiceIndexKey[ $key ] = $choice;
				$choiceIndex[ $key ]    = $choice;
			}
		}

		$answer = $this->ask( $questionFull, array(
			'default'          => $options['default'],
			'canBeBlank'       => $options['canBeBlank'],
			'textColour'       => $options['textColour'],
			'backgroundColour' => $options['backgroundColour'],
			'possibleOptions'  => $possibleOptions,
			'argument' => $options['argument'],
		) );

		if (empty($answer) && $options['canBeBlank']) {
			return null;
		}

		if ( $options['return'] === QuestionReturn::KEY ) {
			if ( $options['style'] === QuestionStyle::NUMBERS ) {
				return $choiceIndexKey[ $answer ];
			} else {
				return $answer;
			}
		} else {
			return $choiceIndex[ $answer ];
		}
	}

	/**
	 * @param string $message
     */
	public function pressToContinue( $message = 'Press ENTER to continue' ) {
		$answer = $this->ask( $message, array(
			'caseSensitive'       => false,
			'showPossibleOptions' => false,
			'canBeBlank'          => false
		) );
	}
}

/**
 * Class QuestionStyle
 * @package CLImax
 */
class QuestionStyle {
	/**
	 * Consider the following array of possible choices:
	 *     array(
	 *         'foo' => 'oof',
	 *           'bar' => 'rab',
	 *           'something' => 'else'
	 *     )
	 */

	/**
	 * NUMBERS would output:
	 *     [1] oof
	 *     [2] rab
	 *     [3] else
	 * and the possible answers are 1/2/3
	 */
	const NUMBERS = 1;

	/**
	 * KEYS would output:
	 *     [foo      ] oof
	 *     [bar      ] rab
	 *     [something] else
	 * and the possible answers are oof/rab/something
	 */
	const KEYS = 2;

	/**
	 * TODO: What do?
	 */
	const NUMBERS_ACCEPT_KEYS = 3;

	/**
	 * NUMBERS would output:
	 *     oof
	 *     rab
	 *     else
	 * and the possible answers are oof/rab/else
	 */
	const VALUES = 4;
}

/**
 * Class QuestionLayoutStyle
 * @package CLImax
 */
class QuestionLayoutStyle {
	const GRID = 1; //Compact view to use the whole CLI width to output questions - this is a TODO!
	const VERTICAL_LIST = 2; //Regular view where each option has its own line
}

/**
 * Class QuestionDisplay
 * @package CLImax
 */
class QuestionDisplay {
	/**
	 * Consider the following array as the possible choices: array('foo' => 'bar')
	 */
	const VALUE = 1; //Will return the value of the option chosen in the choices array, eg. bar
	const KEY = 2; //Will return the value of the option chosen in the choices array, eg. foo
}

/**
 * Class QuestionReturn
 * @package CLImax
 */
class QuestionReturn {
	/**
	 * Consider the following array as the possible choices: array('foo' => 'bar')
	 */
	const VALUE = 1; //Will return the value of the option chosen in the choices array, eg. bar
	const KEY = 2; //Will return the value of the option chosen in the choices array, eg. foo
}