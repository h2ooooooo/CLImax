<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 22-08-2016
 * Time: 12:05
 */

namespace CLImax\Environments;

use CLImax\Application;
use CLImax\DebugLevel;

/**
 * Simple defaults that can be changed to suit another environment
 */
class Environment
{
    /**
     * @var int $sizeRows The number of characters that are space for horizontally in the CLI prompt
     */
    public $sizeRows = 24;
    /**
     * @var int $sizeColumns The number of characters that are space for vertically in the CLI prompt
     */
    public $sizeColumns = 80;
    /**
     * @var bool $exitOnfatal Whether or not we should exit() the application when printing a message with DebugLevel::FATAL
     */
    public $exitOnfatal = true;
    /**
     * @var int $debugLevel The default debug level from DebugLevel
     */
    public $debugLevel = DebugLevel::VERBOSE;
    /**
     * @var bool $internalDebugging Whether or not to use internal debugging and output messages such as 'Loading plugin "x"' etc.
     */
    public $internalDebugging = false;
    /** @var int $sizeUpdateInterval TODO: wut */
    public $sizeUpdateInterval = 120;
    /**
     * @var \CLIMax\Application $application A reference to the Application whereas this is related to
     */
    private $application;

    /**
     * The constructor that sets the default defaults
     *
     * @param Application $application A reference to the Application whereas this is related to
     * @param array $defaults A key-value-pair array with the default defaults
     */
    public function __construct(&$application, $defaults = null)
    {
        $this->application = $application;

        if ($defaults !== null) {
            $this->setKVP($defaults);
        }
    }

    /**
     * Sets the default defaults
     *
     * @param array $kvp A key-value-pair array with the default defaults
     */
    public function setKVP($kvp)
    {
        foreach ($kvp as $key => $value) {
            if (isset($this->{$key})) {
                $this->{$key} = $value;
            } else {
                $this->application->warning(sprintf('There is no such default named "%s"', $key));
            }
        }
    }

    /**
     * Returns the class name of this class
     *
     * @return string
     */
    public static function className()
    {
        return get_called_class();
    }
}