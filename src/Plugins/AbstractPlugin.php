<?php

namespace CLImax\Plugins;

use CLImax\Application;

abstract class AbstractPlugin
{
    abstract public function register(Application $application);
}