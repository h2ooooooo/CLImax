<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 18/10/2018
 * Time: 10.22
 */

namespace CLImax\Enum;

/**
 * Class QuestionReturn
 * @package CLImax
 */
class QuestionReturn
{
    /**
     * Consider the following array as the possible choices: array('foo' => 'bar')
     */
    const VALUE = 1; //Will return the value of the option chosen in the choices array, eg. bar
    const KEY = 2; //Will return the value of the option chosen in the choices array, eg. foo
}