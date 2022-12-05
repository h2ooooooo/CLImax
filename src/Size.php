<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

use CLImax\Enum\SizeType;

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

	public $staticSize = false;

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

	public function setStaticSize($rows, $columns) {
		if (!empty($rows) && !empty($columns)) {
			$this->staticSize = true;

			$this->rows = $rows;
			$this->columns = $columns;
		} else {
			$this->staticSize = false;

			$this->rows = null;
			$this->columns = null;
		}

		return $this;
	}

	/**
	 * Checks whether or not we should update the lines and columns
	 *
	 * @param int $whatToUpdate
	 */
	public function checkUpdate( $whatToUpdate = SizeType::BOTH ) {
		if ($this->staticSize) {
			return;
		}

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
		if ($this->staticSize) {
			return;
		}

		$cacheSeed = microtime(true);

		if ( $whatToUpdate & SizeType::ROWS ) {
			if ( $this->application->os->isWindows() ) {
				$rowsRaw = $this->getWindowsSize('height', $cacheSeed); // TODO: Get real rows
			} else {
				$rowsRaw = $this->getCommandOutput('tput lines');
			}

			if (!empty($rowsRaw)) {
				$rowsRaw = (int)$rowsRaw;

				if (empty($rowsRaw)) {
					$rowsRaw = null;
				}
			}

			$this->rows = ! empty( $rowsRaw ) ? (int) $rowsRaw : $this->application->environment->sizeRows;
		}

		if ( $whatToUpdate & SizeType::COLUMNS ) {
			if ( $this->application->os->isWindows() ) {
				$columnsRaw = $this->getWindowsSize('width', $cacheSeed); // TODO: Get real columns
			} else {
				$columnsRaw = $this->getCommandOutput('tput cols');
			}

			if (!empty($columnsRaw)) {
				$columnsRaw = (int)$columnsRaw;

				if (empty($columnsRaw)) {
					$columnsRaw = null;
				}
			}

			$this->columns = ! empty( $columnsRaw ) ? (int) $columnsRaw : $this->application->environment->sizeColumns;
		}

		$this->lastUpdate = microtime( true );
	}

	private function getCommandOutput($command) {
		try {
			if (php_sapi_name() !== 'cli') {
				throw new \Exception(sprintf('running in non CLI context'));
			}

			if (!function_exists('\exec')) {
				// Some installations have this disabled for security reasons
				throw new \Exception(sprintf('exec method does not exist'));
			}

			@exec($command, $output);
		} catch (\Exception $e) {
			$output = null;
		}

		return $output;
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

			$modeOutput = $this->getCommandOutput('mode');

			if (!empty($modeOutput)) {
				$modeOutput = implode(PHP_EOL, $modeOutput);

				if (preg_match_all('~^\s*(Lines|Columns):\s*(\d+)\s*$~mi', $modeOutput, $matches)) {
					for ($i = 0, $len = count($matches[0]); $i < $len; $i++) {
						$key   = $matches[1][ $i ];
						$value = $matches[2][ $i ];
						if ($matches[1][ $i ] === 'Lines') {
							$property = 'height';
						} else if ($matches[1][ $i ] === 'Columns') {
							$property = 'width';
						}

						$this->windowsSize[ $property ] = (int) $matches[2][ $i ];
					}
				}
			}
		}

		if (!isset($this->windowsSize[$property])) {
			return null;
		}

		return $this->windowsSize[$property];

	}
}
