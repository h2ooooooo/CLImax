<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class ListView
 * @package CLImax
 */
class ListView extends Module
{
    const TYPESET_ASCII_SINGLE_LINED = 'singleLined';
    const TYPESET_ASCII_DOUBLE_LINED = 'doubleLined';
    const TYPESET_DEFAULT = 'default';

    protected $typeSets = [
        ListView::TYPESET_DEFAULT => [
            'item' => '*',
            'whitespace' => ' ',
            'verticalLine' => '',
            'horizontalLine' => '',
            'endItem' => '*',
        ],
        ListView::TYPESET_ASCII_SINGLE_LINED => [
            'item' => 0xc3,
            'verticalLine' => 0xb3,
            'horizontalLine' => 0xc4,
            'endItem' => 0xc0,
        ],
        ListView::TYPESET_ASCII_DOUBLE_LINED => [
            'item' => 0xcc,
            'verticalLine' => 0xba,
            'horizontalLine' => 0xcd,
            'endItem' => 0xc8,
        ],
    ];

    protected $data;

    public function __construct(Application $application, $data = null)
    {
        parent::__construct($application);

        $this->data($data);
    }

    public function data($data)
    {
        $this->data = $data;
    }

    public function output($colour = null, $backgroundColour = null, $debugLevel = DebugLevel::ALWAYS_PRINT)
    {
        $text = $this->getPrint();

	    $this->application->checkScheduledNewline();

        return $this->application->printText($debugLevel, $text, $colour, $backgroundColour);
    }

    public function getPrint($typeSet = ListView::TYPESET_ASCII_SINGLE_LINED, $eol = PHP_EOL)
    {
        $typeSetCharacters = $this->typeSets[$typeSet];

        foreach ($typeSetCharacters as &$value) {
            $value = chr($value);
        }

        unset($value);

        return $this->_getPrint($this->data, $typeSetCharacters, '', $eol);
    }

    protected function _getPrint($data, $typeSetCharacters, $prefix = '', $eol = PHP_EOL, $indentationLevel = 0)
    {
        $lines = [];

        $subIndentationLevel = $indentationLevel + 1;
        $subPrefix = $typeSetCharacters['horizontalLine'] . ' ';

        $itemCount = 0;

        foreach ($data as $value) {
            $itemCount++;
        }

        $iteratorCount = 0;

        foreach ($data as $key => $value) {
            $iteratorCount++;

            // TODO: Do something with key?

            if (is_object($value) || is_array($value)) {
                $lines[] = $this->_getPrint($value, $typeSetCharacters, $subPrefix, $eol, $subIndentationLevel);
            } else {
                if ($iteratorCount === $itemCount) {
                    $lines[] = $prefix . $typeSetCharacters['endItem'] . ' ' . $value;
                } else {
                    $lines[] = $prefix . $typeSetCharacters['item'] . ' ' . $value;
                }
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
