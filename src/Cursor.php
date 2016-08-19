<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class Cursor
 * @package CLImax
 *
 * @see http://www.termsys.demon.co.uk/vtansi.htm
 * @see https://en.wikipedia.org/wiki/ANSI_escape_code#CSI_codes
 */
class Cursor extends Module {
	/**
	 * @param int $cells
	 *
	 * @return $this
     */
    public function up($cells = 1) {
		return $this->printAnsiCode(sprintf('%dA', $cells));
	}

	/**
	 * @param int $cells
	 *
	 * @return $this
     */
    public function down($cells = 1) {
		return $this->printAnsiCode(sprintf('%dB', $cells));
	}

	/**
	 * @param int $cells
	 *
	 * @return $this
     */
    public function forward($cells = 1) {
		return $this->printAnsiCode(sprintf('%dC', $cells));
	}

	/**
	 * @param int $cells
	 *
	 * @return $this
     */
    public function back($cells = 1) {
		return $this->printAnsiCode(sprintf('%dD', $cells));
	}

	/**
	 * @param int $line
	 *
	 * @return $this
     */
    public function nextLine($line = 1) {
		return $this->printAnsiCode(sprintf('%dE', $line));
	}

	/**
	 * @param int $line
	 *
	 * @return $this
     */
    public function previousLine($line = 1) {
		return $this->printAnsiCode(sprintf('%dF', $line));
	}

	/**
	 * @param int $column
	 *
	 * @return $this
     */
    public function horizontalAbsolute($column = 1) {
		return $this->printAnsiCode(sprintf('%dG', $column));
	}

	/**
	 * @param int $row
	 * @param int $column
	 *
	 * @return $this
     */
    public function position($row = 1, $column = 1) {
		return $this->printAnsiCode(sprintf('%d;%dH', $row, $column));
	}

	/**
	 * @return $this
     */
	public function savePosition() {
		return $this->printAnsiCode('s');
	}

	/**
	 * @return $this
     */
	public function restorePosition() {
		return $this->printAnsiCode('u');
	}

	/**
	 * @return $this
     */
	public function savePositionAndAttributes() {
		return $this->printAnsiCode('7');
	}

	/**
	 * @return $this
     */
	public function restorePositionAndAttributes() {
		return $this->printAnsiCode('8');
	}
}