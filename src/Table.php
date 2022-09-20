<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

use CLImax\Enum\BoxSet;
use CLImax\Enum\TableDirection;

/**
 * Class Table
 * @package CLImax
 */
class Table extends Module {
    protected $headerCallback;
    protected $headers = [];
    protected $rows = [];
    protected $boxSet = BoxSet::SIMPLE;
    protected $useRowSeparator = false;
    protected $charPadding = ' ';

    protected $paddingTypes = [];
    protected $formats = [];

    protected $direction = TableDirection::TOP_TO_BOTTOM;

    protected $formatNumbers = false;
    protected $formatNumbersDecimals = 0;

    protected $includeHeaders = true;

    protected $convertValues = true;

    /**
     * Table constructor.
     *
     * @param \CLImax\Application $application
     * @param array               $rows
     */
    public function __construct(Application &$application, $rows = [])
    {
        parent::__construct($application);

        if (!empty($rows)) {
            $this->addRows($rows);
        }
    }

    /**
     * @return $this
     */
    public function addSeparator()
    {
        $this->rows[] = null;

        return $this;
    }

    /**
     * @return array
     */
    public function popRow() {
        return array_pop($this->rows);
    }

    /**
     * @param array $row
     *
     * @return $this
     */
    public function addRow($row)
    {
        $this->rows[] = $row;

        return $this;
    }

    /**
     * @param array[] $rows
     *
     * @return $this
     */
    public function addRows($rows)
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    /**
     * @param callable|bool $formatNumbers
     * @param int $formatNumbersDecimals
     *
     * @return $this
     */
    public function setFormatNumbers($formatNumbers, $formatNumbersDecimals = 0)
    {
        if (!empty($formatNumbers)) {
            $this->formatNumbers = $formatNumbers;
        } else {
            $this->formatNumbers = false;
        }

        if (!is_callable($formatNumbers)) {
            $this->formatNumbersDecimals = $formatNumbersDecimals;
        } else {
            $this->formatNumbersDecimals = 0;
        }

        return $this;
    }

    /**
     * @param $headers
     *
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->headers = !empty($headers) ? $headers : [];

        return $this;
    }

    /**
     * @param $paddingTypes
     *
     * @return $this
     */
    public function setPaddingTypes($paddingTypes)
    {
        $this->paddingTypes = !empty($paddingTypes) ? $paddingTypes : [];

        return $this;
    }

    public function setDirection($direction)
    {
        if ($direction !== TableDirection::LEFT_TO_RIGHT && $direction !== TableDirection::TOP_TO_BOTTOM) {
            throw new \InvalidArgumentException('$direction must be one of the Table::DIRECTION_ constants');
        }

        $this->direction = $direction;

        return $this;
    }

    /**
     * @param $formats
     *
     * @return $this
     */
    public function setFormats($formats)
    {
        $this->formats = !empty($formats) ? $formats : [];

        return $this;
    }

    /**
     * @param $boxSet
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setBoxSet($boxSet)
    {
        BoxSet::get($boxSet); // Let's throw an exception if it does not exist

        $this->boxSet = $boxSet;

        return $this;
    }

    public function hasRows()
    {
        return !empty($this->rows);
    }

    /**
     * Sets whether or not to include headers in the output
     *
     * @param array $includeHeaders
     *
     * @return $this
     */
    public function setIncludeHeaders($includeHeaders) {
        $this->includeHeaders = $includeHeaders;

        return $this;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function toString()
    {
        $boxSet = BoxSet::get($this->boxSet);

        $columns = [];

        $rows = $this->rows;

        if ($this->direction === TableDirection::LEFT_TO_RIGHT) {
            $columnValues = [];
            $mainColumn = null;

            foreach ($rows as $row) {
                if (!empty($row)) {
                    foreach ($row as $column => $value) {
                        if ($mainColumn === null) {
                            $mainColumn = $column;
                        }

                        $columnValues[$column][] = $value;
                    }
                }
            }

            foreach ($columnValues as $column => $_columnValues) {
                if ($column === $mainColumn) {
                    continue;
                }

                $columnValuesNew = [$mainColumn => $column];

                foreach ($_columnValues as $i => $columnValue) {
                    $key = $columnValues[$mainColumn][$i];

                    $columnValuesNew[$key] = $columnValue;
                }

                $columnValues[$column] = $columnValuesNew;
            }

            unset($columnValues[$mainColumn]);

            $rows = array_values($columnValues);
        }

        foreach ($rows as $row) {
            if (!empty($row)) {
                foreach ($row as $column => $value) {
                    if (!isset($columns[$column])) {
                        $columns[$column] = [];
                    }

                    $columns[$column][] = $value;
                }
            }
        }

        $paddingTypes = !empty($this->paddingTypes) ? $this->paddingTypes : [];

        $columnMaxLength = [];
        $headerColumns = [];
        $formats = [];

        foreach ($columns as $column => $columnValues) {
            $onlyNumbers = false;

            foreach ($columnValues as $columnValue) {
                if ($columnValue === false || $columnValue === null || $columnValue === '') {
                    continue; // Skip empty values
                }

                if (is_int($columnValue) || is_float($columnValue) || is_numeric($columnValue)) {
                    $onlyNumbers = true;
                } else {
                    $onlyNumbers = false;

                    break;
                }
            }

            if ($onlyNumbers && !isset($paddingTypes[$column])) {
                $paddingTypes[$column] = STR_PAD_LEFT;
            }
        }

        $columns = array_keys($columns);

        foreach ($columns as $column) {
            $columnMaxLength[$column] = 0;
            $paddingTypes[$column] = isset($paddingTypes[$column]) ? $paddingTypes[$column] : STR_PAD_RIGHT;
            $formats[$column] = isset($this->formats[$column]) ? $this->formats[$column] : null;
            $headerColumns[$column] = isset($this->headers[$column]) ? $this->headers[$column] : $column;
        }

        if (!empty($this->headerCallback) && is_callable($this->headerCallback)) {
            $headerColumns = array_map($this->headerCallback, $headerColumns);
        }

        foreach ($columns as $column) {
            if ($formats[$column] !== null) {
                $isCallable = is_callable($formats[$column]);

                foreach ($rows as &$row) {
                    foreach ($row as &$rowValue) {
                        if ($isCallable) {
                            $rowValue = call_user_func($formats[$column], $rowValue);
                        } else {
                            $rowValue = sprintf($formats[$column], $rowValue);
                        }
                    }

                    unset($rowValue);
                }

                unset($row);
            }
        }

        if (!empty($rows)) {
            foreach ($rows as &$row) {
                if (!empty($row)) {
                    foreach ($row as &$rowValue) {
                        $valueColour = DebugColour::getValueColour($rowValue);

                        if (!empty($this->formatNumbers) && (is_int($rowValue) || is_float($rowValue))) {
                            if (is_callable($this->formatNumbers)) {
                                $rowValue = call_user_func($this->formatNumbers, $rowValue, $this->formatNumbersDecimals);
                            } else {
                                $rowValue = number_format($rowValue, $this->formatNumbersDecimals);
                            }
                        }

                        if ($this->convertValues) {
                            if (is_bool($rowValue)) {
                                $rowValue = $rowValue ? 'true' : 'false';
                            } else if ($rowValue === null) {
                                $rowValue = 'NULL';
                            }
                        }

                        $rowValue = DebugColour::enclose($rowValue, $valueColour);
                    }

                    unset($rowValue);
                }
            }

            unset($row);
        }

        if ($this->includeHeaders && !empty($headerColumns)) {
            $rows = array_merge([$headerColumns], $rows);
        }

        // Figure out what the max column lengths are
        foreach ($rows as &$row) {
            if ($row !== null) {
                foreach ($row as $column => &$value) {
                    $lines = $this->splitLines($value);

                    foreach ($lines as &$line) {
                        $line = $this->charPadding . $line . $this->charPadding;
                    }

                    unset($line);

                    $value = implode(PHP_EOL, $lines);

                    // TODO: Work with non-unicode characters or at least convert the value
                    $columnMaxLength[$column] = max($columnMaxLength[$column], $this->stringMaxLineLength($value));
                }

                unset($value);
            }
        }

        unset($row);

        // Define each separator column with the max lengths
        $separatorColumns = [];

        foreach ($columns as $column) {
            $separatorColumns[] = str_repeat($boxSet['line']['horizontal'], $columnMaxLength[$column]);
        }

        // Define the row separator line
        $rowSeparatorLine = $boxSet['middle']['left'] . implode($boxSet['middle']['cross'],
                $separatorColumns) . $boxSet['middle']['right'];

        $rowBuffer = [];
        $columnAnsiBuffer = [];

        // Add the rows to the output buffer
        foreach ($rows as $rowNumber => $row) {
            if ($row === null) {
                $rowBuffer[] = $rowSeparatorLine;
            } else {
                // Output a single row, but possibly split it into multiple rows if it has newlines in it
                $columnRowLines = [];
                $columnRowLineCounts = [];

                foreach ($row as $column => $value) {
                    $calculatedEscapeSequencePrefixes[ $column ] = [];

                    $lines = $this->splitLines($value);

                    foreach ($lines as $lineIndex => $line) {
                        if (!empty($calculatedEscapeSequencePrefixes[$column])) {
                            $lastCalculatedEscapeSequence = end($calculatedEscapeSequencePrefixes[ $column ]);
                        } else {
                            $lastCalculatedEscapeSequence = '';
                        }

                        $calculatedEscapeSequence = DebugColour::getCalculatedSequence($lastCalculatedEscapeSequence . $line);

                        if (!empty($calculatedEscapeSequence)) {
                            $calculatedEscapeSequencePrefixes[ $column ][$lineIndex + 1] = $calculatedEscapeSequence;
                        }
                    }

                    $columnRowLines[$column] = $lines;
                    $columnRowLineCounts[$column] = count($lines);
                }

                // Make sure all rows are equal in length of lines
                $columnRowLineCount = max($columnRowLineCounts);

                foreach ($columnRowLines as $column => &$columnRowLine) {
                    for ($lineIndex = $columnRowLineCounts[$column]; $lineIndex < $columnRowLineCount; $lineIndex++) {
                        $columnRowLine[] = '';
                    }
                }

                unset($columnRowLine);

                $columnNames = array_keys($columnRowLines);

                $rowBufferSingle = [];

                for ($lineIndex = 0; $lineIndex < $columnRowLineCount; $lineIndex++) {
                    $rowValues = [];

                    foreach ($columnNames as $column) {
                        $columnRowLine = $columnRowLines[$column][$lineIndex];

                        $columnRowLinePadded = $this->pad($columnRowLine, $columnMaxLength[$column], ' ', $paddingTypes[$column]);

                        if (!empty($calculatedEscapeSequencePrefixes[$column][$lineIndex])) {
                            $columnRowLineAffix = $calculatedEscapeSequencePrefixes[$column][$lineIndex];
                        } else {
                            $columnRowLineAffix = '';
                        }

                        $rowValues[$column] = $columnRowLineAffix . $columnRowLinePadded . DebugColour::reset();
                    }

                    $rowContents = $boxSet['line']['vertical']; // Before first column for this line
                    $rowContents .= implode($boxSet['line']['vertical'], $rowValues); // Output all row columns for this line
                    $rowContents .= $boxSet['line']['vertical']; // After last column for this line

                    $rowBufferSingle[] = $rowContents;
                }

                $rowBuffer[] = implode(PHP_EOL, $rowBufferSingle);
            }
        }

        if ($this->useRowSeparator) {
            $rowGlue = PHP_EOL . $rowSeparatorLine . PHP_EOL;
        } else {
            // Make sure that if we don't use individual row separators that we have a divider between the headers and the content

            // ╔═════════════════╦══════════════╗
            // ║ Statistic       ║ Time taken   ║
            // ╠═════════════════╬══════════════╣
            // ║ nameLookup      ║            0 ║
            // ║ connect         ║            0 ║
            // ║ preTransfer     ║            0 ║
            // ║ startTransfer   ║        0.109 ║
            // ╠═════════════════╬══════════════╣
            // ║ total           ║        0.124 ║
            // ╚═════════════════╩══════════════╝

            array_splice($rowBuffer, 1, 0, [$rowSeparatorLine]);

            $rowGlue = PHP_EOL;
        }

        $buffer = '';
        $buffer .= $boxSet['top']['left'] . implode($boxSet['top']['cross'],
                $separatorColumns) . $boxSet['top']['right'] . PHP_EOL;
        $buffer .= implode($rowGlue, $rowBuffer) . PHP_EOL;
        $buffer .= $boxSet['bottom']['left'] . implode($boxSet['bottom']['cross'],
                $separatorColumns) . $boxSet['bottom']['right'] . PHP_EOL;

        return $buffer;
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    public function removeAnsiCodes($string)
    {
        // TODO: Refactor to another place
        // http://stackoverflow.com/a/33925425/247893
        return preg_replace('#(\x9B|\x1B\[)[0-?]*[ -\/]*[@-~]#', '', $string);
    }

    /**
     * @param      $input
     * @param      $length
     * @param null $padString
     * @param null $type
     *
     * @return string
     * @internal param $string
     *
     * @internal param int $pad_length <p>
     * If the value of pad_length is negative,
     * less than, or equal to the length of the input string, no padding
     * takes place.
     * </p>
     * @internal param string $pad_string [optional] <p>
     * The pad_string may be truncated if the
     * required number of padding characters can't be evenly divided by the
     * pad_string's length.
     * </p>
     * @internal param int $pad_type [optional] <p>
     * Optional argument pad_type can be
     * STR_PAD_RIGHT, STR_PAD_LEFT,
     * or STR_PAD_BOTH. If
     * pad_type is not specified it is assumed to be
     * STR_PAD_RIGHT.
     * </p>
     *
     * @author   https://gist.github.com/nebiros/226350
     */
    public function pad($input, $length, $padString = null, $type = null)
    {
        // str_pad uses strlen but we know that ANSI characters aren't going to be printed,
        // so let's strip them to get the REAL length we want to pad the string
        // TODO: Make custom pad function that can do this as well as work with unicode
        $length += strlen($input) - self::stringLength($input);

        return str_pad($input, $length, $padString, $type);
    }

    /**
     * @param $string
     *
     * @return int
     */
    public function stringLength($string)
    {
        $string = $this->application->mutateTextWithOutputPlugins($string);

        $string = self::removeAnsiCodes($string);

        return mb_strlen($string, 'UTF-8');
    }

    /**
     * @param $string
     *
     * @return string[]
     */
    private function splitLines($string) {
        return preg_split('~\R~', $string);
    }

    /**
     * @param $string
     *
     * @return int
     */
    private function stringMaxLineLength($string)
    {
        $stringLines = $this->splitLines($string);

        $maxStringLineLength = 0;

        foreach ($stringLines as $stringLine) {
            $maxStringLineLength = max($maxStringLineLength, static::stringLength($stringLine));
        }

        return $maxStringLineLength;
    }

    public function setHeaderCallback($headerCallback)
    {
        $this->headerCallback = $headerCallback;

        return $this;
    }

    /**
     * @param int $debugLevel
     * @param int $colour
     * @param int $backgroundColour
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function output(
        $debugLevel = DebugLevel::ALWAYS_PRINT,
        $colour = DebugColour::STANDARD,
        $backgroundColour = DebugColour::STANDARD
    ) {
        $output = $this->toString();

        $this->application->printText($debugLevel, $output, $colour, $backgroundColour, null,
            false);

        return $this;
    }

    /**
     * Sets whether or not to convert values when outputting the table (eg. turn a boolean into TRUE/FALSE, null into "NULL" etc.)
     *
     * @param bool $convertValues
     */
    public function setConvertValues($convertValues) {
        $this->convertValues = $convertValues;
    }

    /**
     * Outputs a table where the array key is displayed in the "Label" column and the array value is displayed in the "Value" column
     *
     * @param array $labelValueArray The full array with keys and values
     * @param string $labelText The header text for the label column (the array key)
     * @param string $valueText The header text for the value column (the array value)
     * @param callable $labelCallback - function($label):String|int A callback to modify the "label" column values before they're output
     * @param callable $valueCallback - function($label):mixed A callback to modify the "value" column values before they're output
     *
     * @return Table An instance of this for chaining or outputting
     */
    public function labelTable($labelValueArray, $labelText = 'Label', $valueText = 'Value', $labelCallback = null, $valueCallback = null) {
        $this->rows = [];

        foreach ($labelValueArray as $label => $value) {
            if (!empty($labelCallback)) {
                $label = call_user_func($labelCallback, $label);
            }

            if (!empty($valueCallback)) {
                $value = call_user_func($valueCallback, $value);
            }

            if (is_int($label) && $value === null) {
                $this->addRow(null);
            } else {
                $this->addRow([
                    $labelText => $label,
                    $valueText => $value,
                ]);
            }
        }

        return $this;
    }
}
