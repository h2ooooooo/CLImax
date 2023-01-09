<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class Progress
 * @package CLImax
 */
class Progress extends Module
{
    const MIN_WIDTH = 50;

    private $inProgress = false;

    private $debugLevel;

    private $textColour;

    private $backgroundColour;

    private $showClock;

    private $paddingLeft;

    /**
     * @param null $message
     * @param null $textColour
     * @param null $backgroundColour
     * @param int $debugLevel
     * @param bool $showClock
     *
     * @return bool
     */
    public function start(
        $message = null,
        $textColour = null,
        $backgroundColour = null,
        $debugLevel = DebugLevel::INFO,
        $showClock = false
    )
    {
        if ($this->inProgress) {
            $this->application->fatal('Could not start the progress module as it is already started');

            return false;
        }

        if ($message !== null) {
            $this->application->info($message);
        } else {
            echo PHP_EOL;
        }

        $this->debugLevel = $debugLevel;
        $this->textColour = $textColour;
        $this->backgroundColour = $backgroundColour;
        $this->showClock = $showClock;
        $this->inProgress = true;
        $this->paddingLeft = ($showClock ? ($this->application->timeDecimals > 0 ? 10 + $this->application->timeDecimals : 9) : 0);
        $this->set(0, 'Starting..');

        return true;
    }

    /**
     * @param        $progressPercent
     * @param null $message
     * @param string $progressBarCharacter
     * @param string $progressBarTipCharacter
     *
     * @return bool
     */
    public function set(
        $progressPercent,
        $message = null,
        $progressBarCharacter = '=',
        $progressBarTipCharacter = '>'
    )
    {
        if (!$this->inProgress) {
            $this->application->fatal('Could not set progress as the progress module is not started');

            return false;
        }

        $this->application->clear->lastLine();

        $progressPercent = min(100, max(0, $progressPercent));
        $prepend = round($progressPercent) . '% [';
        $append = '] ' . (!empty($message) ? $message : '') . ' ';
        $progressBarMaxLength = max(self::MIN_WIDTH,
            $this->application->size->columns - strlen($prepend) - strlen($append) - $this->paddingLeft);
        $progressBarLength = round($progressBarMaxLength * ($progressPercent / 100));

        $progressBar = str_pad(str_repeat($progressBarCharacter[0],
                $progressBarLength) . ($progressBarLength < $progressBarMaxLength ? $progressBarTipCharacter[0] : ''),
            $progressBarMaxLength, ' ');

        $output = $prepend . $progressBar . $append;

	    $this->application->checkScheduledNewline();

        $this->application->printText($this->debugLevel, $output, $this->textColour, $this->backgroundColour,
            false, $this->showClock);

        return true;
    }

    /**
     * @return bool
     */
    public function end()
    {
        if (!$this->inProgress) {
            $this->application->fatal('Could not end the progress module as it is not ended');

            return false;
        }

        $this->set(100, 'Finished!');
        $this->inProgress = false;

        echo PHP_EOL;

        return true;
    }
}
