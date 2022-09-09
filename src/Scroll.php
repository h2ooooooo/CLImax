<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class Scroll
 * @package CLImax
 *
 * @see http://www.termsys.demon.co.uk/vtansi.htm
 */
class Scroll extends Module
{
    /**
     * @param null $startRow
     * @param null $endRow
     *
     * @return $this
     */
    public function enable($startRow = null, $endRow = null)
    {
        if ($startRow !== null && $endRow !== null) {

        } else if ($startRow !== null) {

        } else if ($endRow !== null) {

        }
        return $this->printAnsiCode('r');
    }

    /**
     * @param int $lines
     *
     * @return $this
     */
    public function down($lines = 1)
    {
        return $this->printAnsiCode('D', $lines);
    }

    /**
     * @param int $lines
     *
     * @return $this
     */
    public function up($lines = 1)
    {
        return $this->printAnsiCode('M', $lines);
    }
}