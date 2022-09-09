<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 22-02-2017
 * Time: 10:19
 */

namespace CLImax;


class LoggerInterface implements \Psr\Log\LoggerInterface
{
    /**
     * A reference to the CLImax application
     *
     * @var Application $application
     */
    protected $application;

    /**
     * The various debug colours to associate with LogerInterface levels
     *
     * @var array
     */
    protected $debugColours = [
        'debug' => DebugColour::WHITE,
        'info' => DebugColour::GREEN,
        'notice' => DebugColour::CYAN,
        'warning' => DebugColour::YELLOW,
        'error' => DebugColour::RED,
        'critical' => [DebugColour::WHITE, DebugColour::YELLOW],
        'alert' => [DebugColour::WHITE, DebugColour::LIGHT_RED],
        'emergency' => [DebugColour::WHITE, DebugColour::RED],
    ];

    /**
     * The various debug levels to associate with LogerInterface levels
     *
     * @var array
     */
    protected $debugLevels = [
        'debug' => DebugLevel::DEBUG,
        'info' => DebugLevel::INFO,
        'notice' => DebugLevel::INFO,
        'warning' => DebugLevel::WARNING,
        'error' => DebugLevel::ERROR,
        'critical' => DebugLevel::ERROR, //DebugLevel::FATAL,
        'alert' => DebugLevel::ERROR, //DebugLevel::FATAL,
        'emergency' => DebugLevel::ERROR, //DebugLevel::FATAL,
    ];

    /**
     * The CLImax LoggerInterface constructor.
     *
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $debugColour = $this->getDebugColour($level);

        $backgroundColour = null;

        if (is_array($debugColour)) {
            $textColour = $debugColour[0];

            if (isset($debugColour[1])) {
                $backgroundColour = $debugColour[1];
            }
        } else {
            $textColour = $debugColour;
        }

        $this->application->printLine($this->getDebugLevel($level), $message, $textColour, $backgroundColour,
            strtoupper($level), $this->printTime());
    }

    /**
     * Gets the debug colour for a specific logging type
     *
     * @param mixed $type
     *
     * @return mixed
     */
    public function getDebugColour($type)
    {
        if (!isset($this->debugColours[$type])) {
            return DebugColour::WHITE;
        }

        return $this->debugColours[$type];
    }

    /**
     * Gets the debug level for a specific logging type
     *
     * @param mixed $type
     *
     * @return mixed
     */
    public function getDebugLevel($type)
    {
        if (!isset($this->debugLevels[$type])) {
            return DebugLevel::ALWAYS_PRINT;
        }

        return $this->debugLevels[$type];
    }

    /**
     * Whether or not to print time
     *
     * @return bool
     */
    public function printTime()
    {
        return true;
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function alert($message, array $context = array())
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->log('error', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->log('info', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->log('debug', $message, $context);
    }
}