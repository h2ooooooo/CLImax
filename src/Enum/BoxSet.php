<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 18/10/2018
 * Time: 10.17
 */

namespace CLImax\Enum;

use Exception;

/**
 * Class BoxSet
 * @package CLImax
 */
class BoxSet
{
    const SIMPLE = 'simple';
    const DOS_SINGLE = 'dosSingle';
    const DOS_DOUBLE = 'dosDouble';

    private static $sets = [
        BoxSet::SIMPLE => [
            'top' => [
                'left' => '+',
                'cross' => '+',
                'right' => '+',
            ],
            'middle' => [
                'left' => '+',
                'cross' => '+',
                'right' => '+',
            ],
            'bottom' => [
                'left' => '+',
                'cross' => '+',
                'right' => '+',
            ],
            'line' => [
                'horizontal' => '-',
                'vertical' => '|',
            ],
        ],
        BoxSet::DOS_SINGLE => [
            'top' => [
                'left' => 0xda, //'┌',
                'cross' => 0xc2, //'┬',
                'right' => 0xbf, //'┐',
            ],
            'middle' => [
                'left' => 0xc3, //'├',
                'cross' => 0xc5, //'┼',
                'right' => 0xb4, //'┤',
            ],
            'bottom' => [
                'left' => 0xc0, //'└',
                'cross' => 0xc1, //'┴',
                'right' => 0xd9, //'┘',
            ],
            'line' => [
                'horizontal' => 0xc4, //'─',
                'vertical' => 0xb3, //'│',
            ],
        ],
        BoxSet::DOS_DOUBLE => [
            'top' => [
                'left' => 0xc9, //'╔',
                'cross' => 0xcb, //'╦',
                'right' => 0xbb, //'╗',
            ],
            'middle' => [
                'left' => 0xcc, //'╠',
                'cross' => 0xce, //'╬',
                'right' => 0xb9, //'╣',
            ],
            'bottom' => [
                'left' => 0xc8, //'╚',
                'cross' => 0xca, //'╩',
                'right' => 0xbc, //'╝',
            ],
            'line' => [
                'horizontal' => 0xcd, //'═',
                'vertical' => 0xba, //'║',
            ],
        ],
    ];

    private static $_sets = [];

    /**
     * @param $boxSet
     *
     * @return mixed
     * @throws Exception
     */
    public static function get($boxSet)
    {
        if (!isset(self::$_sets[$boxSet])) {
            if (!isset(self::$sets[$boxSet])) {
                throw new Exception(sprintf('Box set %s not found', $boxSet));
            }

            $set = self::$sets[$boxSet];

            foreach ($set as $category => &$characters) {
                foreach ($characters as $name => &$character) {
                    if (is_int($character)) {
                        $character = chr($character);
                    }
                }

                unset($character);
            }

            unset($characters);

            self::$_sets[$boxSet] = $set;
        }

        return self::$_sets[$boxSet];
    }
}