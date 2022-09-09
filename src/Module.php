<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class Module
 * @package CLImax
 */
class Module
{
    /**
     * \CLImax\Application $application A reference to the \CLImax\Application whereas this is related to
     */
    protected $application;

    /**
     * Sets the reference to the application
     *
     * @param Application $application A reference to the \CLImax\Application whereas this is related to
     */
    public function __construct(Application &$application)
    {
        $this->application = $application;
    }

    /**
     * @param     $ansiCode
     * @param int $amount
     *
     * @return $this
     */
    protected function printAnsiCode($ansiCode, $amount = 1)
    {
        $fullAnsiCode = "\033[" . $ansiCode;

        for ($i = 1; $i <= $amount; $i++) {
            $this->application->outputText($fullAnsiCode);
        }

        return $this;
    }
}