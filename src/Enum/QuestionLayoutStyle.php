<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 18/10/2018
 * Time: 10.24
 */

namespace CLImax\Enum;

/**
 * Class QuestionLayoutStyle
 * @package CLImax
 */
class QuestionLayoutStyle
{
    const GRID = 1; //Compact view to use the whole CLI width to output questions - this is a TODO!
    const VERTICAL_LIST = 2; //Regular view where each option has its own line
}