<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

use CLImax\Enum\Spinner;
use CLImax\Environments\Environment;
use CLImax\Environments\Production;
use CLImax\Event\Events;
use CLImax\Plugins\AbstractPlugin;
use Exception;
use ReflectionClass;

/**
 * @property Size $size             A size object defining the size of the CLI prompt
 * @property Environment $environment      The currently chosen environment
 * @property Events $events           A Events object used to handle events
 * @property Question $question         A Question object used to ask questions
 * @property Fullscreen $fullscreen       A Fullscreen object used to run fullscreen applications
 * @property Progress $progress         A Progress object used to show progress
 * @property Clear $clear            A Clear object used to clear text and set cursor
 *           position
 * @property OS $os               An OS object used to figure out what OS we're running
 * @property STD $std              A standard object used for communicating with STDIN,
 *           STDOUT and STDERR
 * @property Cursor $cursor           A cursor object to manipulate the cursor position
 * @property Scroll $scroll           A scroll object to manipulate the scroll bar position
 * @property ProgressMessage $progressMessage           A ProgressMessage object to print single line progress messages ("Computing.. Success")
 * @property ComponentFactory $componentFactory A scroll object to manipulate the scroll bar position
 */
abstract class Application
{
    /**
     * @var bool Whether or not to show padding banners
     */
    public static $showPaddingBanners = true;
    /**
     * @var bool Whether or not to disable the message padding making sure that new lines are padded in the same way as
     *      the first one
     */
    public static $disableMessagePadding = false;
    protected static $instances = [];
    /**
     * @var Arguments $arguments Will end up being an stdClass of arguments parsed either as
     * --foo "bar"
     * or -foo "bar"
     */
    public $arguments;
    /**
     * @var int $timeDecimals How many decimals should be after the time of a message,
     * and how long the applications run time has been
     */
    public $timeDecimals = 4;
    protected $initialized = false;
    protected $scriptName;
    protected $disableAnsi = false;
    /** @var OutputPlugin[] */
    protected $outputPlugins = [];
    protected $automaticallyFlushBuffer = true;
    /**
     * @var int $debugLevel The default debug level - is used to control whether
     * or not to output the text (check the DebugLevel class)
     */
    private $debugLevel = DebugLevel::VERBOSE;
    /**
     * @var int $startTime The start time to be set in the constructor, and checked in the destructor
     */
    private $startTime;
    /**
     * @var int $defaultTextColour The default colour of the text
     */
    private $defaultTextColour = DebugColour::STANDARD;
    /**
     * @var int $defaultBackgroundColour The default colour of the text
     */
    private $defaultBackgroundColour = DebugColour::STANDARD;
    /**
     * @var array $moduleInstances Instances of modules to be called by (and created) using the __get function
     */
    private $moduleInstances = array();
    /**
     * @var bool Whether or not we just printed a line
     */
    private $justPrintedLine = false;
    /**
     * @var array $moduleClasses The classes of the modules to be initiated in $moduleInstances - used for existance
     *     checking and autoloading
     */
    private $moduleClasses = array(
        /**
         * string 'size' A Size object defining the size of the CLI prompt
         */
        'size' => 'Size',
        /**
         * string 'defaults' A Defaults object defining the defaults
         */
        'environment' => 'Environment\Environment',
        /**
         * string 'events' A Events object used to handle events
         */
        'events' => 'Event\Events',
        /**
         * string 'question' A Question object used to ask questions
         */
        'question' => 'Question',
        /**
         * string 'fullscreen' A Fullscreen object used to run fullscreen applications
         */
        'fullscreen' => 'Fullscreen',
        /**
         * string 'progress' A Progress object used to show progress
         */
        'progress' => 'Progress',
        /**
         * string 'clear' A Clear object used to clear text and set cursor position
         */
        'clear' => 'Clear',
        /**
         * string 'os' An OS object used to figure out what OS we're running
         */
        'os' => 'OS',
        /**
         * string 'std' A standard object used for communicating with STDIN, STDOUT and STDERR
         */
        'std' => 'STD',
        /**
         * string 'cursor' A cursor object to manipulate the cursor position
         */
        'cursor' => 'Cursor',
        /**
         * string 'scroll' A scroll object to manipulate the scroll bar position
         */
        'scroll' => 'Scroll',
	    /**
	     * string 'progressMessage' A ProgressMessage object to print single line progress messages ("Computing.. Success")
	     */
        'progressMessage' => 'ProgressMessage',
        /**
         * string 'componentFactory' A factory class to create components
         */
        'componentFactory' => 'ComponentFactory',
    );

	protected $scheduleNewline = false;

    /**
     * The constructor - parses arguments and saves the starting
     * time of the script so we can check how long the entire
     * application took to run, when it dies (see __destruct())
     *
     * @param int $debugLevel Which debug level to start the script on - if null, it will default to
     *                                     $defaults->debugLevel.
     * @param string $environmentClass One of the environments from the Environment class
     * @param array|null $defaultsOverride An array to specify what defaults should be overwritten compared to the
     *                                     environment you're in
     * @param bool $disableAnsi Whether or not to disable ANSI codes from output
     *
     * @throws Exception Throws an exception if we're not in a CLI app
     */
    public function __construct(
        $debugLevel = null,
        $environmentClass = null,
        $defaultsOverride = null,
        $disableAnsi = false
    )
    {
        $this->setEnvironment($environmentClass);

        if ($defaultsOverride !== null) {
            $this->environment->setKVP($defaultsOverride);
        }

        $this->setDebugLevel($debugLevel !== null ? $debugLevel : $this->environment->debugLevel);

        $this->disableAnsi($disableAnsi);

        //Parse arguments
        if ($_SERVER['argc'] > 1) {
            $this->scriptName = $_SERVER['argv'][0];

            $this->arguments = $this->parseArguments(array_slice($_SERVER['argv'], 1));
            if ($debugLevel = $this->arguments->get('debugLevel')) {
                $this->setDebugLevel($debugLevel);
            }
        } else {
            $this->arguments = new Arguments();
        }

        if ($this->arguments->has('climax-disable-padding-banners')) {
            Application::$showPaddingBanners = false;
        }

        $cliRows = $this->arguments->get('cliRows');
        $cliColumns = $this->arguments->get('cliColumns');

        if (!empty($cliRows) && !empty($cliColumns)) {
            $this->size->setStaticSize($cliRows, $cliColumns);
        }

        $this->startTime = microtime(true);

        if ($this->showPaddingBanners()) {
            $this->outputStartBanner();
        } else {
            $this->justPrintedLine = true; // We're not expecting any output before the first message
        }

        if ($this->arguments->has('help')) {
            $this->help();
        }
    }

    /**
     * Sets the defaults to relate to the specified environment
     * Warning: This will reset all custom set defaults
     *
     * @param string $environmentClass A class extending Environments\Environment
     *
     * @return bool Whether the call was OK or not
     */
    public function setEnvironment($environmentClass)
    {
        if (empty($environmentClass)) {
            $environmentClass = Production::className();
        }

        if (!class_exists($environmentClass)) {
            $this->fatal(sprintf('Could not find environment class "%s"', $environmentClass));

            return false;
        }

        $this->moduleInstances['environment'] = new $environmentClass($this);

        return true;
    }

    /**
     * Outputs a FATAL message with 'FATAL' prepended by default
     *
     * @param mixed $output The text or object to be parsed through print_r
     * @param string $prependText The text to prepend
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The colour to print the background of the text in (from the DebugColour class)
     *
     * @param bool $pad
     *
     * @return Application A reference to the application class for chaining
     */
    public function fatal(
        $output,
        $prependText = 'FATAL',
        $colour = DebugColour::RED,
        $backgroundColour = DebugColour::STANDARD,
        $pad = true
    )
    {
        return $this->internalDebug(DebugLevel::FATAL, $output, $colour, $backgroundColour, $prependText, $pad);
    }

    /**
     * Manipulates the text, and adds padding and such
     *
     * @param int $debugLevel The debug level from the DebugLevel class
     * @param mixed $output The text or object to be parsed through print_r
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The background colour to print the text in (from the DebugColour class)
     * @param null|string $prependText The text to prepend, if null or empty nothing will get prepended
     *
     * @param bool $pad
     *
     * @return Application A reference to the application class for chaining
     */
    protected function internalDebug(
        $debugLevel,
        $output,
        $colour = null,
        $backgroundColour = null,
        $prependText = null,
        $pad = true
    )
    {
        if ($debugLevel > $this->debugLevel) {
            return $this;
        }

        if ($output instanceof DebugColourBuilder) {
            $output = $output->toString();
        } else {
            if (is_bool($output)) {
                $output = ($output ? 'TRUE' : 'FALSE');
            } else {
                if ($output === null) {
                    $output = '(NULL)';
                } else {
                    if (is_resource($output)) {
                        $output = '(resource)';
                    } else {
                        if (is_array($output) || is_object($output)) {
                            $output = print_r($output, true);
                        }
                    }
                }
            }
        }

        if ($pad && !Application::$disableMessagePadding) {
            $outputSplit = explode(PHP_EOL, $output);

            $paddingLength = 10 + $this->timeDecimals;

            if (!empty($prependText)) {
                $paddingLength += strlen($prependText) + 2; //1 for the extra space, and 1 for the colon
            }

            $paddingChars = str_repeat(' ', $paddingLength);
            for ($i = 1; $i < count($outputSplit); $i++) {
                $outputSplit[$i] = $paddingChars . $outputSplit[$i];
            }

            $output = implode(PHP_EOL, $outputSplit);
        }

        return $this->printLine($debugLevel, $output, $colour, $backgroundColour, $prependText);
    }

    /**
     * Prints a single line of text (and appends PHP_EOL)
     *
     * @param int $debugLevel The debug level from the DebugLevel class
     * @param mixed $output The text or object to be parsed through print_r
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The background colour to print the text in (from the DebugColour class)
     * @param null|string $prependText The text to prepend, if null or empty nothing will get prepended
     * @param bool $printTime Whether or not to add a timestamp to the start of the line
     *
     * @return Application A reference to the application class for chaining
     */
    public function printLine(
        $debugLevel,
        $output,
        $colour = null,
        $backgroundColour = null,
        $prependText = null,
        $printTime = null
    )
    {
		$this->checkScheduledNewline();

        $this->printText($debugLevel, $output, $colour, $backgroundColour, $prependText,
            $printTime !== null ? $printTime : $this->justPrintedLine);

        if ($this->automaticallyFlushBuffer) {
            $i = 0;

            while ($i++ < 512 && ob_get_level()) {
                ob_end_flush();
            }
        }

        $this->newLine();

        $this->justPrintedLine = true;

        return $this; // For chaining
    }

    /**
     * Prints the text to the CLI
     *
     * @param int $debugLevel The debug level from the DebugLevel class
     * @param mixed $output The text or object to be parsed through print_r
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The background colour to print the text in (from the DebugColour class)
     * @param null|string $prependText The text to prepend, if null or empty nothing will get prepended
     * @param bool $printTime Whether or not to add a timestamp to the start of the line
     *
     * @return Application A reference to the application class for chaining
     */
    public function printText(
        $debugLevel,
        $output,
        $colour = null,
        $backgroundColour = null,
        $prependText = null,
        $printTime = true
    )
    {
        if ($debugLevel > $this->debugLevel) {
            return $this; // For chaining
        }

        if ($colour === null) {
            $colour = $this->defaultTextColour;
        }
        if ($backgroundColour === null) {
            $backgroundColour = $this->defaultBackgroundColour;
        }

        if ($printTime) {
            $microTime = microtime(true);
            $microSeconds = substr($microTime, strpos($microTime, '.') + 1);
            $this->outputText(DebugColour::getColourCode(DebugColour::LIGHT_GRAY) . date('H:i:s') . ($this->timeDecimals > 0 ? ',' . substr(sprintf('%0' . $this->timeDecimals . 's',
                        $microSeconds), 0, $this->timeDecimals) : '') . ' ', false);
        }

        // Make sure we can print the debug output
        $outputRaw = print_r($output, true);

        // Build the output text
        $outputText = DebugColour::getColourCode($colour, $backgroundColour);

        if (!empty($prependText)) {
            $outputText .= $prependText . ': ';
        }

        $outputText .= $outputRaw;

        // Mutate the text
        $outputText = $this->mutateTextWithOutputPlugins($outputText, $colour, $backgroundColour);

        // Replace "reset" colours with reset colours for this particular colour
        $outputText = str_replace(DebugColour::reset(), DebugColour::reset($colour, $backgroundColour), $outputText);

        // Add a reset tag at the end to clear the colour for the next line
        $outputText .= DebugColour::reset();

        if ($this->decodeUtf8()) {
            $outputText = utf8_decode($outputText);
        }

        // Output the text
        $this->outputText($outputText, false);

        // If it's a fatal error let's exit straight away
        if ($debugLevel === DebugLevel::FATAL) {
            // We're bypassing $this->internalDebug, so we won't get our newline
            $this->exitFatal(PHP_EOL);
        }

        return $this; // For chaining
    }

    /**
     * Simply outputs the text (maybe it can do something else here in the future, like adding text to a log
     *
     * @param string $text The text to be output
     * @param bool $mutateTextWithOutputPlugins
     *
     * @return Application A reference to the application class for chaining
     */
    public function outputText($text, $mutateTextWithOutputPlugins = true)
    {
        if ($mutateTextWithOutputPlugins) {
            $text = $this->mutateTextWithOutputPlugins($text);
        }

        if ($this->disableAnsi) {
            $text = preg_replace('~\x1b\[[0-9;]*[a-zA-Z]~', '', $text);
        }

        echo $text;

        return $this; // For chaining
    }

    /**
     * Mutates text with output plugins added by the addOutputPlugin() method
     *
     * @param string $text The text we want to run our output plugins on
     * @param int $textColour The text colour being printed on the line, if available (from the DebugColour class)
     * @param int $backgroundColour The background colour being printed on the line, if available (from the DebugColour class)
     *
     * @return string The finalized string after the output plugins have done their mutations
     */
    public function mutateTextWithOutputPlugins($text, $textColour = null, $backgroundColour = null)
    {
        if (!empty($this->outputPlugins)) {
            // Mutate output with our output plugins
            foreach ($this->outputPlugins as $outputPlugin) {
                $text = $outputPlugin->mutateOutput($text, $textColour, $backgroundColour);
            }
        }

        return $text;
    }

    /**
     * Whether or not to decode utf8 in the application - use ApplicationUtf8 if you want this done automatically
     *
     * @return bool
     */
    public function decodeUtf8()
    {
        return true;
    }

    /**
     * To be called after caling fatal() either manually or automatically
     *
     * @param string $prepend
     *
     * @return Application A reference to the application class for chaining
     */
    public function exitFatal($prepend = null)
    {
        if ($this->environment->exitOnfatal) {
            if ($prepend !== null && $prepend !== '') {
                $this->outputText($prepend);
            }

            $this->environment->exitOnfatal = false;
            $this->verbose('Exiting application, as a fatal message was printed, and exitOnfatal is true');
            exit(0);
        }

        return $this; // For chaining
    }

    /**
     * Outputs a VERBOSE message with 'VERBOSE' prepended by default
     *
     * @param mixed $output The text or object to be parsed through print_r
     * @param string $prependText The text to prepend
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The colour to print the background of the text in (from the DebugColour class)
     *
     * @param bool $pad
     *
     * @return Application A reference to the application class for chaining
     */
    public function verbose(
        $output,
        $prependText = 'VERBOSE',
        $colour = DebugColour::LIGHT_PURPLE,
        $backgroundColour = DebugColour::STANDARD,
        $pad = true
    )
    {
        return $this->internalDebug(DebugLevel::VERBOSE, $output, $colour, $backgroundColour, $prependText, $pad);
    }

    /**
     * Outputs a new line
     */
    public function newLine()
    {
        $this->outputText(PHP_EOL);
    }

    /**
     * Disables ANSI output
     *
     * @param bool $disableAnsi
     *
     * @return $this
     */
    public function disableAnsi($disableAnsi)
    {
        $this->disableAnsi = $disableAnsi;

        return $this;
    }

    /**
     * Is used to parse the contents of $_SERVER['argv']
     *
     * @param array $argumentsRaw The arguments from $_SERVER['argv']
     *
     * @return Arguments The arguments in an Arguments class
     */
    private function parseArguments($argumentsRaw)
    {
        return Arguments::init($argumentsRaw);
    }

    /**
     * The abstract init method that has to be implemented - will be called after the inbuilt construct method
     */
    abstract public function init();

    /**
     * Returns whether or not padding banners should be shown
     *
     * @return bool
     */
    public function showPaddingBanners()
    {
        return Application::$showPaddingBanners;
    }

    /**
     * Outputs the start banner
     */
    public function outputStartBanner()
    {
        $this->outputText(PHP_EOL);

        $this->fullLineMessage('START', DebugColour::LIGHT_CYAN, null, null, true, '-', false);
    }

    /**
     * Is used to print a full line message (according to the CLI prompt columns).
     * Note that this message will ALWAYS be output, unless $debugLevel is changed
     *
     * @param string $message The message to be printed in the middle
     * @param int $colour The colour of the text, null is default
     * @param int $backgroundColour The colour of the background of the text, null is default
     * @param int $debugLevel The debug level from DebugLevel
     * @param boolean $addBrackets Whether or not to add [ and ]'s to the string
     * @param string $padString The string to pad the message towards the center with
     *
     * @param bool $padTopBottom
     *
     * @return Application A reference to the application class for chaining
     */
    public function fullLineMessage(
        $message,
        $colour = null,
        $backgroundColour = null,
        $debugLevel = DebugLevel::ALWAYS_PRINT,
        $addBrackets = true,
        $padString = '-',
        $padTopBottom = true,
        $maxLength = null
    )
    {
        $size = $this->size->columns;

        if ($maxLength !== null && $size > $maxLength) {
            $size = $maxLength;

            $fullSize = false;
        } else {
            $fullSize = true;
        }

        $messageLine = str_pad(($addBrackets ? '[' . $message . ']' : $message), $size, $padString,
            STR_PAD_BOTH);

	    $this->checkScheduledNewline();

        if ($padTopBottom) {
            $padLine = str_repeat($padString, $size);

            $this->printText($debugLevel, $padLine, $colour, $backgroundColour, '', false);
        }

        if (!$fullSize) {
            $this->newLine();
        }

        $this->printText($debugLevel, $messageLine, $colour, $backgroundColour, '', false);

        if (!$fullSize) {
            $this->newLine();
        }

        if ($padTopBottom) {
            if ($fullSize) {
                $this->printText($debugLevel, substr($padLine, 0, -2), $colour, $backgroundColour, '', false);
            } else {
                $this->printText($debugLevel, $padLine, $colour, $backgroundColour, '', false);
            }
        }

        $this->newLine();

        $this->justPrintedLine = true;

        return $this; // For chaining
    }

    protected function help()
    {
        $this->info('Used arguments');

        // TODO: Support aliases - this is empty at the moment as no aliases have ever been defined at this point
        $aliases = $this->arguments->getAliases();

        foreach ($this->getUsedArguments() as $argument => $description) {
            $colourStringBuilder = DebugColour::buildString()->write('* ')->write($argument, DebugColour::LIGHT_RED);

            if (isset($aliases[$argument])) {
                foreach ($aliases[$argument] as $alias) {
                    $colourStringBuilder->write('*   [')->write($alias, DebugColour::LIGHT_BROWN)->write(']');
                }
            }

            if (!empty($description)) {
                $colourStringBuilder->write(' - ')->write($description, DebugColour::LIGHT_BLUE);
            }

            $this->verbose($colourStringBuilder);
        }

        $this->quit();
    }

    /**
     * Outputs an INFO message with 'INFO' prepended by default
     *
     * @param mixed $output The text or object to be parsed through print_r
     * @param string $prependText The text to prepend
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The colour to print the background of the text in (from the DebugColour class)
     *
     * @param bool $pad
     *
     * @return Application A reference to the application class for chaining
     */
    public function info(
        $output,
        $prependText = 'INFO',
        $colour = DebugColour::LIGHT_GREEN,
        $backgroundColour = DebugColour::STANDARD,
        $pad = true
    )
    {
        return $this->internalDebug(DebugLevel::INFO, $output, $colour, $backgroundColour, $prependText, $pad);
    }

    /**
     * @return array
     */
    protected function getUsedArguments()
    {
        $this->verbose('The Application->getUsedArguments method has not been overwritten, so we are just guessing..');

        $reflectionClass = new ReflectionClass($this);

        $path = $reflectionClass->getFileName();

        $this->verbose($path);

        $applicationContents = file_get_contents($path);

        $regex = '~' . preg_quote('$this->arguments->', '~') . '([\w\_]+)\(([^\)]+)\)~i';

        $foundArguments = [];

        $filename = pathinfo($path, PATHINFO_BASENAME);

        if (preg_match_all($regex, $applicationContents, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $key => $match) {
                $matchOffset = $matches[0][$key][1];
                $method = $matches[1][$key][0]; // 1 = offset
                $arguments = $matches[2][$key][0]; // 1 = offset

                if (preg_match("~^(?:'([^']+)'|([^,]+))~", $arguments, $match)) {
                    $argumentName = $match[1];
                } else {
                    $argumentName = $arguments;
                }

                $argumentName = $this->arguments->escapeArgument($argumentName);

                if (!empty($argumentName)) {
                    // We don't know the description soo we're printing generic info

                    // Line 1 will always have 0 newlines in front of it, so add 1 to the number of matches
                    $line = preg_match_all('~(\r\n|\n\r|\r|\n)~', substr($applicationContents, 0, $matchOffset)) + 1;

                    $foundArguments[$argumentName] = DebugColour::buildString(DebugColour::LIGHT_BLUE)->write($filename)->write(':')->write($line,
                        DebugColour::BROWN)->write(' (')->write($method, DebugColour::WHITE,
                        DebugColour::RED)->write(')')->toString();
                }
            }
        }

        return $foundArguments;
    }

    /**
     * Writes some text (and does not care about the debug level, as it is ALWAYS_PRINT)
     *
     * @param mixed $output The text or object to be parsed through print_r
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The background colour to print the text in (from the DebugColour class)
     *
     * @return Application A reference to the application class for chaining
     */
    public function write(
        $output,
        $colour = null,
        $backgroundColour = null
    )
    {
        $this->printText(DebugLevel::ALWAYS_PRINT, $output, $colour, $backgroundColour, null, false);

        $this->justPrintedLine = false;

        return $this; // For chaining
    }

    /**
     * Quits the program
     *
     * @param int $exitCode
     */
    public function quit($exitCode = 0)
    {
        exit($exitCode);
    }

    /**
     * @param int $debugLevel Which debug level to start the script on - if null, it will default to
     *                                     $defaults->debugLevel.
     * @param string $environmentClass One of the environments from the Environment class
     * @param array|null $defaultsOverride An array to specify what defaults should be overwritten compared to the
     *                                     environment you're in
     * @param bool $disableAnsi Whether or not to disable ANSI codes from output
     *
     * @return static
     */
    public static function instance(
        $debugLevel = null,
        $environmentClass = null,
        $defaultsOverride = null,
        $disableAnsi = false
    )
    {
        $class = get_called_class();

        if (!isset(static::$instances[$class])) {
            static::launch($debugLevel, $environmentClass, $defaultsOverride, $disableAnsi);
        }

        return static::$instances[$class];
    }

    /**
     * The static constructor - parses arguments and saves the starting
     * time of the script so we can check how long the entire
     * application took to run, when it dies (see __destruct())
     *
     * @param int $debugLevel Which debug level to start the script on - if null, it will default to
     *                                     $defaults->debugLevel.
     * @param string $environmentClass One of the environments from the Environment class
     * @param array|null $defaultsOverride An array to specify what defaults should be overwritten compared to the
     *                                     environment you're in
     * @param bool $disableAnsi Whether or not to disable ANSI codes from output
     *
     * @return static
     * @throws \Exception Throws an exception if we're not in a CLI app
     *
     */
    public static function launch(
        $debugLevel = null,
        $environmentClass = null,
        $defaultsOverride = null,
        $disableAnsi = false
    )
    {
        $class = get_called_class();

        $application = new $class($debugLevel, $environmentClass, $defaultsOverride, $disableAnsi);

        static::$instances[$class] = $application;

        $application->doInit();

        return $application;
    }

    public function doInit()
    {
        if (!$this->initialized) {
            try {
                $returnCode = $this->init();

                if ($returnCode !== null) {
                    $returnCodeInt = (int)$returnCode;

                    if ($returnCodeInt < 0 || $returnCodeInt > 254) {
                        throw new Exception(sprintf('Could not return a code that is not between 0 and 254 - you tried to return ""'));
                    }

                    exit((int)$returnCode);
                }

                exit(0);
            } catch (Exception $e) {
                $this->error(sprintf('The main doInit function received an exception: %s', $e->getMessage()));
                $this->printLine(DebugLevel::ERROR, $e->getTraceAsString(), DebugColour::LIGHT_RED,
                    DebugColour::STANDARD, '', false);

                exit(1);
            }
        }
    }

    /**
     * Outputs an ERROR message with 'ERROR' prepended by default
     *
     * @param mixed $output The text or object to be parsed through print_r
     * @param string $prependText The text to prepend
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The colour to print the background of the text in (from the DebugColour class)
     *
     * @param bool $pad
     *
     * @return Application A reference to the application class for chaining
     */
    public function error(
        $output,
        $prependText = 'ERROR',
        $colour = DebugColour::LIGHT_RED,
        $backgroundColour = DebugColour::STANDARD,
        $pad = true
    )
    {
        return $this->internalDebug(DebugLevel::ERROR, $output, $colour, $backgroundColour, $prependText, $pad);
    }

    /**
     * Checks whether the current application is an utf8 application
     *
     * @return bool
     */
    public function isUtf8()
    {
        return false;
    }

    /**
     * Checks whether or not the current cli can actually prompt (if you run it from a browser this will return false)
     *
     * @return bool
     */
    public function canPrompt()
    {
        return (php_sapi_name() === 'cli');
    }

    /**
     * Gets and loads a dynamic module
     *
     * @param string $module The identifier of the module (the key in $this->moduleClasses)
     *
     * @return mixed A handle for whatever module was requested
     */
    public function __get($module)
    {
        if (isset($this->moduleClasses[$module])) {
            if (!isset($this->moduleInstances[$module])) {
                $class = '\\CLImax\\' . $this->moduleClasses[$module];

                $this->moduleInstances[$module] = new $class($this);
            }

            return $this->moduleInstances[$module];
        } else {
            $this->fatal('Unknown module "' . $module . '"');
            $this->verbose('Available modules are:');

            foreach ($this->moduleClasses as $moduleIdentifier => $moduleClass) {
                $this->verbose('  ' . $moduleIdentifier . ' (' . $moduleClass . ')');
            }

            return null;
        }
    }

    /**
     * The destructor - prints the amount of time that the application has run
     */
    public function __destruct()
    {
        if (!static::isCli()) {
            return;
        }

        if ($this->showPaddingBanners()) {
            $this->outputEndBanner();
        }

        $this->outputText(DebugColour::reset()); // Make sure everything is reset before we end the application
    }

    /**
     * Checks whether we're in a CLI
     *
     * @return bool
     */
    public static function isCli()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Outputs the end banner
     */
    public function outputEndBanner()
    {
        if (!empty($this->startTime)) {
            $seconds = microtime(true) - $this->startTime;
            $minutes = 0;
            $hours = 0;

            while ($seconds > 3600) {
                $seconds -= 3600;
                $hours++;
            }

            while ($seconds > 60) {
                $seconds -= 60;
                $minutes++;
            }

            $tookTime = $hours . ' hour' . ($hours != 1 ? 's' : '') . ', ' . $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ', ' . sprintf('%01.' . $this->timeDecimals . 'f',
                    $seconds) . ' second' . ($seconds != 1 ? 's' : '');

            $this->fullLineMessage('END', DebugColour::LIGHT_CYAN, null, null, true, '-', false);

            $colorHighlight = DebugColour::getColourCode(DebugColour::LIGHT_GREEN);
            $colorMessage = DebugColour::getColourCode(DebugColour::LIGHT_CYAN);
            $colorStandard = DebugColour::getColourCode(DebugColour::STANDARD);

            $message = sprintf("Script [%s] ended at [%s]\n\nIt took [%s]\n\n", $this->scriptName,
                date('Y-m-d H:i:s'), $tookTime);

            $this->outputText($colorMessage);
            $this->outputText(preg_replace('~\[([^\]]+)\]~',
                $colorHighlight . '$1' . $colorStandard . $colorMessage, $message));
            $this->outputText($colorStandard);
        } else {
            $this->verbose('We do not know how long it took seeing as $this->startTime was not set. Did you remember to call parent::__construct?');
            $this->fullLineMessage('END', DebugColour::LIGHT_CYAN, null, null, true, '-', false);
        }
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function getPath($path)
    {
        return realpath(dirname(__FILE__) . '../' . preg_replace('~[/\\]+~', DIRECTORY_SEPARATOR, $path));
    }

    /**
     * Gets the current debug level ENUM
     *
     * @return int The debug level from the DebugLevel
     */
    public function getDebugLevel()
    {
        return $this->debugLevel;
    }

    /**
     * Sets the current debug level. A message will not be
     * output unless the current debug level is higher or
     * equal to the printed message's debug level.
     *
     * @param string|int $debugLevel The debug level from the DebugLevel class or 'always', 'success', 'fatal',
     *                               'error', 'warning', 'info', 'debug' or 'verbose' with any capitalization
     *
     * @return Application A reference to the application class for chaining
     */
    public function setDebugLevel($debugLevel = DebugLevel::VERBOSE)
    {
        if (is_string($debugLevel)) {
            $debugTextLevels = array(
                'always' => DebugLevel::ALWAYS_PRINT,
                'success' => DebugLevel::SUCCESS,
                'fatal' => DebugLevel::FATAL,
                'error' => DebugLevel::ERROR,
                'warning' => DebugLevel::WARNING,
                'info' => DebugLevel::INFO,
                'debug' => DebugLevel::DEBUG,
                'verbose' => DebugLevel::VERBOSE,
            );

            $debugLevel = strtolower($debugLevel);

            if (isset($debugTextLevels[$debugLevel])) {
                $debugLevel = $debugTextLevels[$debugLevel];
            } else {
                $this->fatal('Cannot set debug level to ' . $debugLevel . ' as there is no such debug level');
                $debugLevel = null;
            }
        } else {
            $debugLevel = (int)$debugLevel;

            if ($debugLevel < DebugLevel::ALWAYS_PRINT || $debugLevel > DebugLevel::VERBOSE) {
                $this->fatal('Cannot set debug level to ' . $debugLevel . ' as it needs to be between ' . DebugLevel::ALWAYS_PRINT . ' and ' . DebugLevel::VERBOSE);
                $debugLevel = null;
            }
        }

        if ($debugLevel !== null) {
            $this->debugLevel = $debugLevel;
        }

        return $this; // For chaining
    }

    /**
     * Prints a single line of text (and appends PHP_EOL, and does not care about the debug level, as it is
     * ALWAYS_PRINT)
     *
     * @param mixed $output The text or object to be parsed through print_r
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The background colour to print the text in (from the DebugColour class)
     * @param null|string $prependText The text to prepend, if null or empty nothing will get prepended
     * @param bool $printTime Whether or not to add a timestamp to the start of the line
     *
     * @return Application A reference to the application class for chaining
     */
    public function writeLine(
        $output,
        $colour = null,
        $backgroundColour = null,
        $prependText = null,
        $printTime = true
    )
    {
        return $this->printLine(DebugLevel::ALWAYS_PRINT, $output, $colour, $backgroundColour, $prependText,
            $printTime);
    }

    /**
     * Disables or enables message padding and return the previous value
     *
     * @param bool $disable Whether or not to disable message padding
     *
     * @return bool Returns the old value
     */
    public function disableMessagePadding($disable = true)
    {
        $currentValue = Application::$disableMessagePadding;

        Application::$disableMessagePadding = $disable;

        return $currentValue;
    }

    /**
     * Outputs a SUCCESS message with 'SUCCESS' prepended by default
     *
     * @param mixed $output The text or object to be parsed through print_r
     * @param string $prependText The text to prepend
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The colour to print the background of the text in (from the DebugColour class)
     *
     * @param bool $pad
     *
     * @return Application A reference to the application class for chaining
     */
    public function success(
        $output,
        $prependText = 'SUCCESS',
        $colour = DebugColour::GREEN,
        $backgroundColour = DebugColour::STANDARD,
        $pad = true
    )
    {
        return $this->internalDebug(DebugLevel::SUCCESS, $output, $colour, $backgroundColour, $prependText, $pad);
    }

    /**
     * Outputs a FATAL message with 'FATAL' prepended by default (however it does NOT exit even though exitOnfatal is
     * true)
     *
     * @param mixed $output The text or object to be parsed through print_r
     * @param string $prependText The text to prepend
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The colour to print the background of the text in (from the DebugColour class)
     *
     * @param bool $pad
     *
     * @return Application A reference to the application class for chaining
     */
    public function fatalSilent(
        $output,
        $prependText = 'FATAL',
        $colour = DebugColour::RED,
        $backgroundColour = DebugColour::STANDARD,
        $pad = true
    )
    {
        $exitOnFatal = $this->environment->exitOnfatal;

        $this->environment->exitOnfatal = false;

        $this->internalDebug(DebugLevel::FATAL, $output, $colour, $backgroundColour, $prependText, $pad);

        $this->environment->exitOnfatal = $exitOnFatal;

        return $this; // For chaining
    }

    /**
     * Outputs a WARNING message with 'WARNING' prepended by default
     *
     * @param mixed $output The text or object to be parsed through print_r
     * @param string $prependText The text to prepend
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The colour to print the background of the text in (from the DebugColour class)
     *
     * @param bool $pad
     *
     * @return Application A reference to the application class for chaining
     */
    public function warning(
        $output,
        $prependText = 'WARNING',
        $colour = DebugColour::YELLOW,
        $backgroundColour = DebugColour::STANDARD,
        $pad = true
    )
    {
        return $this->internalDebug(DebugLevel::WARNING, $output, $colour, $backgroundColour, $prependText, $pad);
    }

    /**
     * Outputs a DEBUG message with 'DEBUG' prepended by default
     *
     * @param mixed $output The text or object to be parsed through print_r
     * @param string $prependText The text to prepend
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The colour to print the background of the text in (from the DebugColour class)
     *
     * @param bool $pad
     *
     * @return Application A reference to the application class for chaining
     */
    public function debug(
        $output,
        $prependText = 'DEBUG',
        $colour = DebugColour::STANDARD,
        $backgroundColour = DebugColour::STANDARD,
        $pad = true
    )
    {
        return $this->internalDebug(DebugLevel::DEBUG, $output, $colour, $backgroundColour, $prependText, $pad);
    }

    /**
     * Sleeps for X amount of seconds
     *
     * @param float $seconds The amount of seconds to sleep for (0.1 would be 1/10th of a second)
     * @param string $spinner The spinner sprite to use when animating (or an empty() value if you don't want to use a
     *                        spinner) - see the \CLImax\Enum\Spinner class
     * @param float $spinnerUpdateIntervalSeconds
     *
     * @return Application A reference to the application class for chaining
     */
    public function sleep($seconds, $spinner = Spinner::SIMPLE, $spinnerUpdateIntervalSeconds = 0.1)
    {
        if ($seconds <= 0) {
            return $this;
        }

        $microUpdateInterval = $spinnerUpdateIntervalSeconds * 1000000;
        $microSecondsSleepTime = $seconds * 1000000;

        if (!empty($spinner) && $microSecondsSleepTime > $microUpdateInterval) {
            $sleepTimeRemaining = $microSecondsSleepTime;

            if ($this->decodeUtf8()) {
                // Since we encode utf8 we want to make sure this isn't already utf8 encoded
                $spinner = utf8_encode($spinner);
            }

            $spinnerAmount = mb_strlen($spinner, 'utf-8');
            $spinnerIndex = 0;
            $iteration = 0;

            while ($sleepTimeRemaining > 0) {
                $sleepTime = min($sleepTimeRemaining, $microUpdateInterval);

                if ($iteration >= 1) {
                    $this->clear->lastLine();
                }

                $spinnerCharacter = mb_substr($spinner, $spinnerIndex, 1, 'utf-8');

                $this->info(sprintf(
                    'Sleeping.. %s | %s',
                    $spinnerCharacter,
                    $this->secondsToTime($sleepTimeRemaining / 1000000)
                ));

                usleep($sleepTime);

                $sleepTimeRemaining -= $sleepTime;

                $spinnerIndex++;

                if ($spinnerIndex >= $spinnerAmount) {
                    $spinnerIndex = 0;
                }

                $iteration++;
            }
        } else {
            // Seconds is under our update limit, so we'll just sleep with no animation
            usleep(ceil($microSecondsSleepTime));
        }

        return $this;
    }

    private function secondsToTime($seconds)
    {
        $oneHour = 3600;
        $oneMinute = 60;

        $hours = 0;
        $minutes = 0;

        while ($seconds >= $oneHour) {
            $hours++;

            $seconds -= $oneHour;
        }

        while ($seconds >= $oneMinute) {
            $minutes++;

            $seconds -= $oneMinute;
        }

        $_seconds = floor($seconds);
        $milliseconds = round(($seconds - $_seconds) * 10);

        return sprintf('%02s:%02s:%02s,%s', $hours, $minutes, $_seconds, $milliseconds);
    }

    /**
     * (this is an alias of $this->separator for crappy spelling)
     *
     * Outputs a separator that stretches to the edge of the CMD
     *
     * @param string $separator What character the separator should be made of
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The colour to print the background of the text in (from the DebugColour class)
     *
     * @return Application A reference to the application class for chaining
     */
    public function seperator($separator = '-', $colour = DebugColour::LIGHT_RED, $backgroundColour = null)
    {
        return call_user_func_array([$this, 'separator'], func_get_args());
    }

    /**
     * Outputs a separator that stretches to the edge of the CMD
     *
     * @param string $separator What character the separator should be made of
     * @param int $colour The colour to print the text in (from the DebugColour class)
     * @param int $backgroundColour The colour to print the background of the text in (from the DebugColour class)
     *
     * @return Application A reference to the application class for chaining
     */
    public function separator($separator = '-', $colour = DebugColour::LIGHT_RED, $backgroundColour = null)
    {
        $seperatorLength = mb_strlen($separator);

        $columns = $this->size->columns;

        $repetitions = floor($columns / $seperatorLength);
        $repetitionsLength = $seperatorLength * $repetitions;

        $seperatorLine = str_repeat($separator, $repetitions) . mb_substr($separator, 0, $columns - $repetitionsLength);

        return $this->printLine(DebugLevel::ALWAYS_PRINT, $seperatorLine, $colour, $backgroundColour, '', false);
    }

    /**
     * Pauses the application (and waits for enter)
     *
     * @param null $pauseMessage
     * @param string $pauseMessageType
     *
     * @return string
     */
    public function pause($pauseMessage = null, $pauseMessageType = 'info')
    {
        if (empty($pauseMessage)) {
            $pauseMessage = 'Press enter to continue';
        }

        call_user_func([$this, $pauseMessageType], $pauseMessage);

        // Thank you windows for always blocking STDIN
        fread(STDIN, 1);
    }

    /**
     * @param array $rows
     *
     * @return Table
     */
    public function table($rows = [])
    {
        $table = new Table($this, $rows);

        return $table;
    }

    /**
     * @param array $data
     *
     * @return ListView
     */
    public function listView($data = null)
    {
        $listView = new ListView($this, $data);

        return $listView;
    }

    /**
     * @param int $offset
     * @param bool $escape
     *
     * @return string
     */
    public function getRawArguments($offset = 0, $escape = true)
    {
        if (empty($_SERVER['argv'])) {
            return '';
        }

        $arguments = [];

        for ($i = $offset; $i < $_SERVER['argc']; $i++) {
            $arguments[] = $_SERVER['argv'][$i];
        }

        if ($escape) {
            $arguments = array_map('escapeshellarg', $arguments);
        }

        return implode(' ', $arguments);
    }

    /**
     * Gets the module classes
     *
     * @return array
     */
    public function getModuleClasses()
    {
        return $this->moduleClasses;
    }

    /**
     * @param string $format The format with the content replaced by %s (eg. "{{%s}}" would call the callback
     *                                 with whatever is inside of the double brackets)
     *
     * @param callable $pluginCallback The callback to be called to mutate text
     *
     * @return $this An instance of the application for chaining
     */
    public function addOutputPlugin($format, $pluginCallback)
    {
        $this->outputPlugins[] = new OutputPlugin($format, $pluginCallback);

        return $this;
    }

    /**
     * Change whether or not all buffers should automatically be flushed when outputting
     *
     * @param bool $automaticallyFlushBuffer
     *
     * @return $this
     */
    public function automaticallyFlushBuffer($automaticallyFlushBuffer)
    {
        $this->automaticallyFlushBuffer = $automaticallyFlushBuffer;

        return $this;
    }

    /**
     * @param AbstractPlugin $plugin
     */
    public function registerPlugin(AbstractPlugin $plugin)
    {
        $plugin->register($this);
    }

	/**
	 * @param bool $scheduleNewline
	 *
	 * @return void
	 */
	public function scheduleNewline($scheduleNewline = true) {
		$this->scheduleNewline = $scheduleNewline;
	}

	/**
	 * @return void
	 */
	public function checkScheduledNewline() {
		if ($this->scheduleNewline) {
			$this->newLine();

			$this->scheduleNewline = false;
		}
	}
}
