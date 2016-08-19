<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class Clear
 * @package CLImax
 */
class Clear extends Module {
	/**
	 * Clears the last line and moves the cursor to the start of the line (for use in redrawing lines, such as progress)
	 */
	public function lastLine() {
		$this->line()->application->cursor->previousLine();
	}

	/**
	 * @return \CLImax\Clear
     */
	public function everything() {
		return $this->display(ClearDisplayType::ENTIRE_SCREEN);
	}

	/**
	 * @param int $type
	 *
	 * @return $this
     */
    public function display($type = ClearDisplayType::FROM_CURSOR_TO_END) {
		return $this->printAnsiCode(sprintf('%dJ', $type));
	}

	/**
	 * @param int $type
	 *
	 * @return $this
     */
    public function line($type = ClearInLineType::FROM_CURSOR_TO_END) {
		return $this->printAnsiCode(sprintf('%dK', $type));
	}

	/**
	 * @param $lines
	 *
	 * @return $this
     */
    public function lines($lines) {
		$cursor = $this->application->cursor;

		for ($i = 0; $i < $lines; $i++) {
			$this->line(ClearInLineType::ENTIRE_LINE);

			$cursor->previousLine();
		}

		return $this;
	}
}

/**
 * Class ClearDisplayType
 * @package CLImax
 */
class ClearDisplayType {
	const FROM_CURSOR_TO_END = 0;
	const FROM_CURSOR_TO_BEGINNING = 1;
	const ENTIRE_SCREEN = 2;
}

/**
 * Class ClearInLineType
 * @package CLImax
 */
class ClearInLineType {
	const FROM_CURSOR_TO_END = 0;
	const FROM_CURSOR_TO_BEGINNING = 1;
	const ENTIRE_LINE = 2;
}