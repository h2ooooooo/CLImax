<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class Table
 * @package CLImax
 */
class Table extends Module {
    protected $headerCallback;
	protected $headers = [];
	protected $rows = [];
	protected $boxSet = BoxSet::SIMPLE;
	protected $useRowSeparator = false;
	protected $charPadding = ' ';

	protected $paddingTypes = [];
	protected $formats = [];

	/**
	 * Table constructor.
	 *
	 * @param \CLImax\Application $application
	 * @param array               $rows
	 */
	public function __construct(Application &$application, $rows = [] ) {
		parent::__construct($application);

		if (!empty($rows)) {
			$this->addRows($rows);
		}
	}

	/**
	 * @param $row
	 *
	 * @return $this
	 */
	public function addRow($row) {
		$this->rows[] = $row;

		return $this;
	}

	/**
	 * @param $rows
	 *
	 * @return $this
	 */
	public function addRows($rows) {
		foreach ($rows as $row) {
			$this->addRow($row);
		}

		return $this;
	}

	/**
	 * @param $headers
	 *
	 * @return $this
	 */
	public function setHeaders($headers) {
		$this->headers = !empty($headers) ? $headers : [];

		return $this;
	}

	/**
	 * @param $paddingTypes
	 *
	 * @return $this
	 */
	public function setPaddingTypes($paddingTypes) {
		$this->paddingTypes = !empty($paddingTypes) ? $paddingTypes : [];

		return $this;
	}

	/**
	 * @param $formats
	 *
	 * @return $this
	 */
	public function setFormats($formats) {
		$this->formats = !empty($formats) ? $formats : [];

		return $this;
	}

	/**
	 * @param $boxSet
	 *
	 * @throws \Exception
     *
     * @return $this
	 */
	public function setBoxSet($boxSet) {
		BoxSet::get($boxSet); // Let's throw an exception if it does not exist

		$this->boxSet = $boxSet;

        return $this;
	}

    public function hasRows() {
        return !empty($this->rows);
    }

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function toString() {
		$boxSet = BoxSet::get($this->boxSet);

		$columns = [];

		$rows = $this->rows;

		foreach ($rows as $row) {
			foreach ($row as $column => $value) {
				if (!isset($columns[$column])) {
					$columns[$column] = [];
				}

				$columns[$column][] = $value;
			}
		}

		$paddingTypes = !empty($this->paddingTypes) ? $this->paddingTypes : [];

		$columnMaxLength = [];
		$headerColumns = [];
		$formats = [];

		foreach ($columns as $column => $columnValues) {
			$onlyNumbers = false;

			foreach ($columnValues as $columnValue) {
				if ($columnValue === false || $columnValue === null || $columnValue === '') {
					continue; // Skip empty values
				}

				if (is_int($columnValue) || is_float($columnValue) || is_numeric($columnValue)) {
					$onlyNumbers = true;
				} else {
					$onlyNumbers = false;

					break;
				}
			}

			if ($onlyNumbers && !isset($paddingTypes[$column])) {
				$paddingTypes[$column] = STR_PAD_LEFT;
			}
		}

		$columns = array_keys($columns);

		foreach ($columns as $column) {
			$columnMaxLength[$column] = 0;
			$paddingTypes[$column] = isset($paddingTypes[$column]) ? $paddingTypes[$column] : STR_PAD_RIGHT;
			$formats[$column] = isset($this->formats[$column]) ? $this->formats[$column] : null;
            $headerColumns[$column] = isset($this->headers[$column]) ? $this->headers[$column] : $column;
		}

		if (!empty($this->headerCallback) && is_callable($this->headerCallback)) {
            $headerColumns = array_map($this->headerCallback, $headerColumns);
        }

		foreach ($columns as $column) {
			if ($formats[$column] !== null) {
				$isCallable = is_callable($formats[$column]);

				foreach ($rows as &$row) {
					$value = $row[$column];

					if ($isCallable) {
						$value = call_user_func($formats[$column], $value);
					} else {
						$value = sprintf($formats[$column], $value);
					}

					$row[$column] = $value;
				}

				unset($row);
			}
		}

		$rows = array_merge([$headerColumns], $rows);

		foreach ($rows as &$row) {
			foreach ($row as $column => &$value) {
				$value = $this->charPadding . $value . $this->charPadding;

				// TODO: Work with non-unicode characters or at least convert the value
				$columnMaxLength[$column] = max($columnMaxLength[$column], $this->stringLength($value));
			}

			unset($value);
		}

		unset($row);

		$separatorColumns = [];

		foreach ($columns as $column) {
			$separatorColumns[] = str_repeat($boxSet['line']['horizontal'], $columnMaxLength[$column]);
		}

		$rowBuffer = [];

		foreach ($rows as $i => $row) {
			$rowColumns = [];

			foreach ($row as $column => $value) {
				$rowColumns[] = $this->pad($value, $columnMaxLength[$column], ' ', $paddingTypes[$column]);
			}

			$rowBuffer[$i] = $boxSet['line']['vertical'] . implode($boxSet['line']['vertical'], $rowColumns) . $boxSet['line']['vertical'];
		}

		if ($this->useRowSeparator) {
			$rowGlue = PHP_EOL . $boxSet['middle']['left'] . implode($boxSet['middle']['cross'], $separatorColumns) . $boxSet['middle']['right'] . PHP_EOL;
		} else {
			// Make sure that if we don't use individual row separators that we have a divider between the headers and the content
			/**
			 * ╔═════════╦═══════╗
			 * ║ Statistic     ║ Time taken ║
			 * ╠═════════╬═══════╣
			 * ║ nameLookup    ║          0 ║
			 * ║ connect       ║          0 ║
			 * ║ preTransfer   ║          0 ║
			 * ║ startTransfer ║      0.109 ║
			 * ║ total         ║      0.124 ║
			 * ╚═════════╩═══════╝
			 */
			array_splice($rowBuffer, 1, 0, [$boxSet['middle']['left'] . implode($boxSet['middle']['cross'], $separatorColumns) . $boxSet['middle']['right']]);

			$rowGlue = PHP_EOL;
		}

		$buffer = '';
		$buffer .= $boxSet['top']['left'] . implode($boxSet['top']['cross'], $separatorColumns) . $boxSet['top']['right'] . PHP_EOL;
		$buffer .= implode($rowGlue, $rowBuffer) . PHP_EOL;
		$buffer .= $boxSet['bottom']['left'] . implode($boxSet['bottom']['cross'], $separatorColumns) . $boxSet['bottom']['right'] . PHP_EOL;

		return $buffer;
	}

	/**
	 * @param $string
	 *
	 * @return mixed
	 */
	public function removeAnsiCodes($string) {
		// TODO: Refactor to another place
		// http://stackoverflow.com/a/33925425/247893
		return preg_replace('#(\x9B|\x1B\[)[0-?]*[ -\/]*[@-~]#', '', $string);
	}

	/**
	 * @param $input
	 * @param $length
	 * @param null $padString
	 * @param null $type
	 *
	 * @return string
	 * @internal param $string
	 *
	 * @internal param int $pad_length <p>
	 * If the value of pad_length is negative,
	 * less than, or equal to the length of the input string, no padding
	 * takes place.
	 * </p>
	 * @internal param string $pad_string [optional] <p>
	 * The pad_string may be truncated if the
	 * required number of padding characters can't be evenly divided by the
	 * pad_string's length.
	 * </p>
	 * @internal param int $pad_type [optional] <p>
	 * Optional argument pad_type can be
	 * STR_PAD_RIGHT, STR_PAD_LEFT,
	 * or STR_PAD_BOTH. If
	 * pad_type is not specified it is assumed to be
	 * STR_PAD_RIGHT.
	 * </p>
	 *
	 * @author https://gist.github.com/nebiros/226350
	 */
	public function pad($input, $length, $padString = null, $type = null) {
		//$diff = self::stringLength( $input ) - mb_strlen( $input );

		return str_pad($input, $length, $padString, $type);
	}

	/**
	 * @param $string
	 *
	 * @return int
	 */
	public function stringLength($string) {
		$string = self::removeAnsiCodes($string);

		// something goes wrong here, probably because the cmd can't show unicode c
		//if (function_exists('mb_strlen')) {
		//return mb_strlen($string);
		//}

		return strlen($string);
	}

	public function setHeaderCallback($headerCallback) {
	    $this->headerCallback = $headerCallback;

	    return $this;
    }

	/**
	 * @param int $debugLevel
	 * @param int $colour
	 * @param int $backgroundColour
	 *
	 * @return $this
	 */
	public function output($debugLevel = DebugLevel::ALWAYS_PRINT, $colour = DebugColour::STANDARD, $backgroundColour = DebugColour::STANDARD) {
		$output = $this->toString();

		$this->application->printText(DebugLevel::ALWAYS_PRINT, utf8_encode($output), $colour, $backgroundColour, null, false);

		return $this;
	}
}

/**
 * Class BoxSet
 * @package CLImax
 */
class BoxSet {
	const SIMPLE = 'simple';
	const DOS_SINGLE = 'dosSingle';
	const DOS_DOUBLE = 'dosDouble';

	private static $sets = [
		BoxSet::SIMPLE => [
			'top' => [
				'left' => '+',
				'cross' => '+',
				'right' => '+',
			],
			'middle' => [
				'left' => '+',
				'cross' => '+',
				'right' => '+',
			],
			'bottom' => [
				'left' => '+',
				'cross' => '+',
				'right' => '+',
			],
			'line' => [
				'horizontal' => '-',
				'vertical' => '|',
			],
		],
		BoxSet::DOS_SINGLE => [
			'top' => [
				'left' => 0xda, //'┌',
				'cross' => 0xc2, //'┬',
				'right' => 0xbf, //'┐',
			],
			'middle' => [
				'left' => 0xc3, //'├',
				'cross' => 0xc5, //'┼',
				'right' => 0xb4, //'┤',
			],
			'bottom' => [
				'left' => 0xc0, //'└',
				'cross' => 0xc1, //'┴',
				'right' => 0xd9, //'┘',
			],
			'line' => [
				'horizontal' => 0xc4, //'─',
				'vertical' => 0xb3, //'│',
			],
		],
		BoxSet::DOS_DOUBLE => [
			'top' => [
				'left' => 0xc9, //'╔',
				'cross' => 0xcb, //'╦',
				'right' => 0xbb, //'╗',
			],
			'middle' => [
				'left' => 0xcc, //'╠',
				'cross' => 0xce, //'╬',
				'right' => 0xb9, //'╣',
			],
			'bottom' => [
				'left' => 0xc8, //'╚',
				'cross' => 0xca, //'╩',
				'right' => 0xbc, //'╝',
			],
			'line' => [
				'horizontal' => 0xcd, //'═',
				'vertical' => 0xba, //'║',
			],
		],
	];

	private static $_sets = [];

	/**
	 * @param $boxSet
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public static function get($boxSet) {
		if (!isset(self::$_sets[$boxSet])) {
			if (!isset(self::$sets[$boxSet])) {
				throw new \Exception(sprintf('Box set %s not found', $boxSet));
			}

			$set = self::$sets[$boxSet];

			foreach ($set as $category => &$characters) {
				foreach ($characters as $name => &$character) {
					if (is_int($character)) {
						$character = chr($character);
					}
				}

				unset($character);
			}

			unset($characters);

			self::$_sets[$boxSet] = $set;
		}

		return self::$_sets[$boxSet];
	}
}