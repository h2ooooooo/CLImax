<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 22-08-2016
 * Time: 12:06
 */

namespace CLImax\Environments;


/**
 * Suggested application defaults for developement
 */
class DevelopmentNoInternalDebugging extends Development
{
    public $internalDebugging = false;
}