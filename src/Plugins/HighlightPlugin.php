<?php


namespace CLImax\Plugins;

use CLImax\Application;
use CLImax\DebugColour;

/**
 * Class HighlightPlugin
 * @package CLImax\Plugins
 *
 * Allows messages to be highlighted using {{foobar}} syntax
 *
 * Also supports {{foobar:red}} for red text
 * Also supports {{foobar:red:blue}} for red text on a blue background
 */
class HighlightPlugin extends AbstractPlugin
{
    private $colourNames = [
        'r' => DebugColour::RED,
        'g' => DebugColour::GREEN,
        'b' => DebugColour::BLUE,

        'black' => DebugColour::BLACK,
        'red' => DebugColour::RED,
        'green' => DebugColour::GREEN,
        'yellow' => DebugColour::YELLOW,
        'brown' => DebugColour::BROWN,
        'blue' => DebugColour::BLUE,
        'purple' => DebugColour::PURPLE,
        'magenta' => DebugColour::MAGENTA,
        'cyan' => DebugColour::CYAN,
        'gray' => DebugColour::GRAY,
        'white' => DebugColour::WHITE,

        'lightblack' => DebugColour::BLACK, // No such thing as LIGHT_BLACK
        'lightred' => DebugColour::LIGHT_RED,
        'lightgreen' => DebugColour::LIGHT_GREEN,
        'lightyellow' => DebugColour::LIGHT_YELLOW,
        'lightbrown' => DebugColour::LIGHT_BROWN,
        'lightblue' => DebugColour::LIGHT_BLUE,
        'lightpurple' => DebugColour::LIGHT_PURPLE,
        'lightmagenta' => DebugColour::LIGHT_MAGENTA,
        'lightcyan' => DebugColour::LIGHT_CYAN,
        'lightgray' => DebugColour::LIGHT_GRAY,
        'lightwhite' => DebugColour::WHITE, // No such thing as LIGHT_WHITE
    ];
    private $lineColourHighlighter = [
        DebugColour::BLACK => [DebugColour::WHITE, DebugColour::BLACK],
        DebugColour::WHITE => [DebugColour::GRAY, DebugColour::WHITE],

        DebugColour::RED => [DebugColour::WHITE, DebugColour::RED],
        DebugColour::GREEN => [DebugColour::WHITE, DebugColour::GREEN],
        DebugColour::YELLOW => [DebugColour::BLACK, DebugColour::YELLOW],
        DebugColour::BLUE => [DebugColour::WHITE, DebugColour::BLUE],
        DebugColour::PURPLE => [DebugColour::WHITE, DebugColour::PURPLE],
        DebugColour::CYAN => [DebugColour::WHITE, DebugColour::CYAN],
        DebugColour::GRAY => [DebugColour::WHITE, DebugColour::GRAY],

        DebugColour::LIGHT_RED => [DebugColour::WHITE, DebugColour::LIGHT_RED],
        DebugColour::LIGHT_GREEN => [DebugColour::WHITE, DebugColour::LIGHT_GREEN],
        DebugColour::LIGHT_YELLOW => [DebugColour::GRAY, DebugColour::LIGHT_YELLOW],
        DebugColour::LIGHT_BLUE => [DebugColour::GRAY, DebugColour::LIGHT_BLUE],
        DebugColour::LIGHT_PURPLE => [DebugColour::WHITE, DebugColour::LIGHT_PURPLE],
        DebugColour::LIGHT_CYAN => [DebugColour::WHITE, DebugColour::LIGHT_CYAN],
        DebugColour::LIGHT_GRAY => [DebugColour::WHITE, DebugColour::LIGHT_GRAY],
    ];

    public function register(Application $application)
    {
        $application->addOutputPlugin('{{%s}}', [$this, 'encloseOutputPlugin']);
    }

    public function encloseOutputPlugin($text, $lineTextColour = null, $lineBackgroundColour = null)
    {
        $textColour = DebugColour::GRAY;
        $backgroundColour = DebugColour::WHITE;

        if (!empty($lineTextColour)) {
            if (isset($this->lineColourHighlighter[$lineTextColour])) {
                $textColour = $this->lineColourHighlighter[$lineTextColour][0];
                $backgroundColour = $this->lineColourHighlighter[$lineTextColour][1];
            }
        }

        $colourRegex = '((?:light)?(?:black|red|green|yellow|brown|blue|purple|magenta|cyan|gray|white))';
        $matchRegex = sprintf('~^(.+?)\:%s(?:\:%s)?$~i', $colourRegex, $colourRegex);

        if (preg_match($matchRegex, $text, $match)) {
            $text = $match[1];
            $textColour = !empty($match[2]) ? $this->colourNames[strtolower($match[2])] : DebugColour::STANDARD;
            $backgroundColour = !empty($match[3]) ? $this->colourNames[strtolower($match[3])] : DebugColour::STANDARD;
        }

        return DebugColour::enclose($text, $textColour, $backgroundColour);
    }
}