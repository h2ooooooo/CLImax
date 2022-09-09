<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

use CLImax\Enum\ClearDisplayType;
use CLImax\Enum\ClearInLineType;

/**
 * Class Clear
 * @package CLImax
 */
class Clear extends Module
{
    /**
     * Clears the last line and moves the cursor to the start of the line (for use in redrawing lines, such as progress)
     */
    public function lastLine()
    {
        $this->line()->application->cursor->previousLine();
    }

    /**
     * @param int $type
     *
     * @return $this
     */
    public function line($type = ClearInLineType::FROM_CURSOR_TO_END)
    {
        return $this->printAnsiCode(sprintf('%dK', $type));
    }

    /**
     * @return Clear
     */
    public function everything()
    {
        return $this->display(ClearDisplayType::ENTIRE_SCREEN);
    }

    /**
     * @param int $type
     *
     * @return $this
     */
    public function display($type = ClearDisplayType::FROM_CURSOR_TO_END)
    {
        return $this->printAnsiCode(sprintf('%dJ', $type));
    }

    /**
     * @param $lines
     *
     * @return $this
     */
    public function lines($lines)
    {
        $cursor = $this->application->cursor;

        for ($i = 0; $i < $lines; $i++) {
            $this->line(ClearInLineType::ENTIRE_LINE);

            $cursor->previousLine();
        }

        return $this;
    }
}