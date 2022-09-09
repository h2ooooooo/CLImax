<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 18/10/2018
 * Time: 10.20
 */

namespace CLImax;


/**
 * Class DebugColourBuilder
 * @package CLImax
 */
class DebugColourBuilder
{
    protected $string = '';

    protected $savedTextColour = DebugColour::STANDARD;
    protected $savedBackgroundColour = DebugColour::STANDARD;
    protected $savedTextStyle = DebugColour::STYLE_RESET;

    protected $textColour;
    protected $backgroundColour;
    protected $textStyle;

    /**
     * DebugColourBuilder constructor.
     *
     * @param int $textColour
     * @param int $backgroundColour
     * @param int $textStyle
     */
    public function __construct($textColour = DebugColour::STANDARD, $backgroundColour = DebugColour::STANDARD, $textStyle = DebugColour::STYLE_RESET)
    {
        $this->string .= DebugColour::reset();

        if ($textColour !== null || $backgroundColour !== null || $textStyle !== null) {
            $this->setColour($textColour, $backgroundColour, $textStyle);
        }
    }

    /**
     * @return DebugColourBuilder
     */
    public function reset()
    {
        return $this->resetColour()->resetStyle();
    }

    /**
     * @return DebugColourBuilder
     */
    public function resetStyle()
    {
        return $this->setStyle(DebugColour::STYLE_RESET);
    }

    /**
     * @param $textStyle
     *
     * @return $this
     */
    public function setStyle($textStyle)
    {
        $this->textStyle = $textStyle;

        //$this->string .= str_replace("\033", "\\033", DebugColour::styleCode($textStyle));

        return $this; // For chaining
    }

    /**
     * @return DebugColourBuilder
     */
    public function resetColour()
    {
        return $this->setColour(DebugColour::STANDARD, DebugColour::STANDARD);
    }

    /**
     * @param null $textColour
     * @param null $backgroundColour
     * @param null $textStyle
     *
     * @return $this
     */
    public function setColour($textColour = null, $backgroundColour = null, $textStyle = null)
    {
        $this->textColour = $textColour;
        $this->backgroundColour = $backgroundColour;

        if ($textStyle !== null) {
            $this->setStyle($textStyle);
        }

        //$this->string .= DebugColour::getColourCode($textColour, $backgroundColour, $this->textStyle);

        return $this; // For chaining
    }

    /**
     * @return DebugColourBuilder
     */
    public function blink()
    {
        return $this->setStyle(DebugColour::STYLE_BLINK);
    }

    /**
     * @return DebugColourBuilder
     */
    public function bold()
    {
        return $this->setStyle(DebugColour::STYLE_BOLD);
    }

    /**
     * @return DebugColourBuilder
     */
    public function bright()
    {
        return $this->setStyle(DebugColour::STYLE_BRIGHT);
    }

    /**
     * @return DebugColourBuilder
     */
    public function dim()
    {
        return $this->setStyle(DebugColour::STYLE_DIM);
    }

    /**
     * @return DebugColourBuilder
     */
    public function hidden()
    {
        return $this->setStyle(DebugColour::STYLE_HIDDEN);
    }

    /**
     * @return DebugColourBuilder
     */
    public function reverse()
    {
        return $this->setStyle(DebugColour::STYLE_REVERSE);
    }

    /**
     * @return DebugColourBuilder
     */
    public function underscore()
    {
        return $this->setStyle(DebugColour::STYLE_UNDERSCORE);
    }

    /**
     * @return $this
     */
    public function save()
    {
        return $this->saveColour()->saveStyle(); // For chaining
    }

    /**
     * @return $this
     */
    public function saveStyle()
    {
        $this->savedTextStyle = $this->textStyle;

        return $this; // For chaining
    }

    /**
     * @return $this
     */
    public function saveColour()
    {
        $this->savedTextColour = $this->textColour;
        $this->savedBackgroundColour = $this->backgroundColour;

        return $this; // For chaining
    }

    /**
     * @return DebugColourBuilder
     */
    public function revert()
    {
        return $this->revertColour()->revertStyle();
    }

    /**
     * @return DebugColourBuilder
     */
    public function revertStyle()
    {
        return $this->setStyle($this->savedTextStyle);
    }

    /**
     * @return DebugColourBuilder
     */
    public function revertColour()
    {
        return $this->setColour($this->savedTextColour, $this->savedBackgroundColour);
    }

    /**
     * @param $bool
     *
     * @return $this
     */
    public function writeBool($bool)
    {
        if ($bool) {
            $this->write('YES', DebugColour::WHITE, DebugColour::GREEN);
        } else {
            $this->write('NO', DebugColour::WHITE, DebugColour::RED);
        }

        return $this;
    }

    /**
     * @param      $text
     * @param null $textColour
     * @param null $backgroundColour
     * @param null $textStyle
     *
     * @return $this
     */
    public function write($text, $textColour = null, $backgroundColour = null, $textStyle = null)
    {
        $currentTextColour = $this->textColour;
        $currentBackgroundColour = $this->backgroundColour;
        $currentTextStyle = $this->textStyle;

        if (!empty($textColour) || !empty($backgroundColour)) {
            $this->setColour($textColour, $backgroundColour);
        }

        if (!empty($textStyle)) {
            $this->setStyle($textStyle);
        }

        $this->string .= DebugColour::getColourCode($this->textColour, $this->backgroundColour, $this->textStyle);

        $this->string .= $text;

        if (!empty($textColour) || !empty($backgroundColour)) {
            $this->setColour($currentTextColour, $currentBackgroundColour);
        }

        if (!empty($textStyle)) {
            $this->setStyle($currentTextStyle);
        }

        return $this; // For chaining
    }

    /**
     * @param      $format
     * @param      $arguments
     * @param null $textColour
     * @param null $backgroundColour
     * @param null $textStyle
     *
     * @return DebugColourBuilder
     */
    public function writef($format, $arguments, $textColour = null, $backgroundColour = null, $textStyle = null)
    {
        return $this->write(vsprintf($format, $arguments), $textColour, $backgroundColour, $textStyle);
    }

    /**
     * @param      $line
     * @param null $lineColour
     * @param null $lineBackgroundColour
     * @param null $lineStyle
     *
     * @return DebugColourBuilder
     */
    public function writeLine($line, $lineColour = null, $lineBackgroundColour = null, $lineStyle = null)
    {
        return $this->write($line . PHP_EOL, $lineColour, $lineBackgroundColour, $lineStyle);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->string;
    }
}