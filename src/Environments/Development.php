<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 22-08-2016
 * Time: 12:05
 */

namespace CLImax\Environments;

/**
 * Suggested application defaults for developement
 */
class Development extends Environment {
    public $exitOnfatal = false;
    public $internalDebugging = true;
}