<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * A simple object that contains the rows and the columns of the CLI prompt
 */
class Size extends Module {
	/**
	 * int $rows The number of characters that are space for horizontally in the CLI prompt
	 */
	public $rows = null;

	/**
	 * int $columns The number of characters that are space for vertically in the CLI prompt
	 */
	public $columns = null;

	/**
	 * int $lastUpdate The time in microseconds that the columns and rows were last updated
	 */
	public $lastUpdate = null;

	private $windowsSize = [];
	private $windowsSizeCacheSeed;

	/**
	 * Runs the internal Update function, that checks the size
	 * of the CLI prompt. See \CLImax\ApplicationSize->Update().
	 *
	 * @param \CLImax\Application $application A reference to the \CLImax\Application whereas this is related to
	 */
	public function __construct( &$application ) {
		parent::__construct( $application );

		$this->update();
	}

	/**
	 * Checks whether or not we should update the lines and columns
	 *
	 * @param int $whatToUpdate
	 */
	public function checkUpdate( $whatToUpdate = SizeType::BOTH ) {
		if ( microtime( true ) - $this->lastUpdate > $this->application->environment->sizeUpdateInterval ) {
			$this->update( $whatToUpdate );
		}
	}

	/**
	 * Runs the tput command with "lines" and "cols" as arguments,
	 * as it'll return the rows and columns of the CLI prompt.
	 * If it fails, rows and columns will remain the assumed default;
	 * Rows: \CLImax\Application_Default->sizeRows, Columns: \CLImax\Application_Default->sizeColumns
	 *
	 * @param int $whatToUpdate Whether to update rows, columns or both (from \CLImax\Application_Size_Type)
	 */
	public function update( $whatToUpdate = SizeType::BOTH ) {
		$cacheSeed = microtime(true);

		if ( $whatToUpdate & SizeType::ROWS ) {
			if ( $this->application->os->isWindows() ) {
				$rowsRaw = $this->getWindowsSize('height', $cacheSeed); // TODO: Get real rows
			} else {
				exec( 'tput lines', $columnsRaw );
			}

			$this->rows = ( ! empty( $rowsRaw ) ? (int) $rowsRaw : $this->application->environment->sizeRows );
		}

		if ( $whatToUpdate & SizeType::COLUMNS ) {
			if ( $this->application->os->isWindows() ) {
				$columnsRaw = $this->getWindowsSize('width', $cacheSeed); // TODO: Get real columns
			} else {
				exec( 'tput cols', $columnsRaw );
			}

			$this->columns = ( ! empty( $columnsRaw ) ? (int) $columnsRaw : $this->application->environment->sizeColumns );
		}

		$this->lastUpdate = microtime( true );
	}

	/**
	 * @param $property
	 * @param $cacheSeed
	 *
	 * @return null
     */
    public function getWindowsSize($property, $cacheSeed) {
		if ($this->windowsSizeCacheSeed !== $cacheSeed) {
			$this->windowsSizeCacheSeed = $cacheSeed;

			exec('mode', $modeOutput);

			$modeOutput = implode(PHP_EOL, $modeOutput);

			if (preg_match_all('~^\s*(Lines|Columns):\s*(\d+)\s*$~mi', $modeOutput, $matches)) {
				for ($i = 0, $len = count($matches[0]); $i < $len; $i++) {
					$key = $matches[1][$i];
					$value = $matches[2][$i];
					if ($matches[1][$i] === 'Lines') {
						$property = 'height';
					} else if ($matches[1][$i] === 'Columns') {
						$property = 'width';
					}

					$this->windowsSize[$property] = (int)$matches[2][$i];
				}
			}
		}

		if (!isset($this->windowsSize[$property])) {
			return null;
		}

		return $this->windowsSize[$property];

	}
}

/**
 * Class SizeType
 * @package CLImax
 */
class SizeType {
	const ROWS = 1;
	const COLUMNS = 2;
	const BOTH = 3; //ROWS & COLUMNS
}