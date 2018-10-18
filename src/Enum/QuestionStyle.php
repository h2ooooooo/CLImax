<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 18/10/2018
 * Time: 10.21
 */

namespace CLImax\Enum;

/**
 * Class QuestionStyle
 * @package CLImax
 */
class QuestionStyle {
    /**
     * Consider the following array of possible choices:
     *     array(
     *         'foo' => 'oof',
     *           'bar' => 'rab',
     *           'something' => 'else'
     *     )
     */

    /**
     * NUMBERS would output:
     *     [1] oof
     *     [2] rab
     *     [3] else
     * and the possible answers are 1/2/3
     */
    const NUMBERS = 1;

    /**
     * KEYS would output:
     *     [foo      ] oof
     *     [bar      ] rab
     *     [something] else
     * and the possible answers are oof/rab/something
     */
    const KEYS = 2;

    /**
     * TODO: What do?
     */
    const NUMBERS_ACCEPT_KEYS = 3;

    /**
     * NUMBERS would output:
     *     oof
     *     rab
     *     else
     * and the possible answers are oof/rab/else
     */
    const VALUES = 4;
}