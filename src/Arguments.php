<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class Arguments
 * @package CLImax
 */
class Arguments
{
    /** @var array $arguments The main argument container */
    private $arguments = [
        'anonymous' => []
    ];

    /** @var bool $mbInstalled Whether or not the MultiByte library is installed */
    private $mbInstalled = false;

    private $aliases = [];

    /**
     * @param $argumentsRaw
     *
     * @return \CLImax\Arguments
     * @throws \Exception
     */
    public static function init($argumentsRaw) {
        $arguments = new Arguments();

        $argumentBuffer = '';

        foreach ($argumentsRaw as $argument) {
            // Find arguments that start with either "-" (eg. -h, --help)
            if (strlen($argument) >= 1 && ($argument[0] == '-')) {
                // Strip the extra "-" if one is specified (--user becomes -user) and get the argument value (eg. "foo")
                $argumentBuffer = substr($argument, ($argument[0] == '-' && $argument[1] == '-' ? 2 : 1));

                // Find out if there's an equal sign in our argument (eg.. --user=foo)
                $equalSign = strpos($argumentBuffer, '=');

                if ($equalSign !== false) {
                    // There was an equal sign - the key will be the content before the equal sign and the value will be the content after the equal sign
                    $argumentKey = substr($argumentBuffer, 0, $equalSign);
                    $arguments->set($argumentKey, substr($argumentBuffer, $equalSign + 1));

                    // Clear the buffer
                    $argumentBuffer = '';
                } else {
                    // There wasn't an equal sign - it's either a value-less argument or the value will come later - do NOT clear the buffer
                    $argumentKey = $argumentBuffer;
                    $arguments->set($argumentKey, '');
                }
            } else {
                // We hit a character that isn't "-" or "/" as the first character - most likely a value for a previous argument - get it from the buffer
                if ($argumentBuffer != '') {
                    // We already have a argument name in our buffer
                    $argumentKey = $argumentBuffer;
                    $arguments->set($argumentKey, $argument);

                    // Clear the buffer
                    $argumentBuffer = '';
                } else {
                    // We have a value but no argument to assign it to - it's an anonymous value
                    $arguments->addAnonymous($argument);
                }
            }
        }

        return $arguments;
    }

    /**
     * Constructs an argument class and checks if the MultiByte library is installed
     *
     * @param array|null $arguments A key-value pair array where the key is the argument and the value is the value
     */
    public function __construct($arguments = null)
    {
        $this->mbInstalled = function_exists('mb_strtolower');

        if (!empty($arguments)) {
            $this->setArray($arguments);
        }
    }

    /**
     * @return array
     */
    public function getAll() {
        $arguments = $this->arguments;

        unset($arguments['anonymous']);

        return $arguments;
    }

    /**
     * Gets all the defined aliases added with the alias() method
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Escapes an argument to avoid case sensitivity
     *
     * @param string $argument The name of the argument
     *
     * @return string The escaped name of the argument
     */
    public function escapeArgument($argument)
    {
        if ($this->mbInstalled) {
            return mb_strtolower($argument);
        }

        return strtolower($argument);
    }

    /**
     * Removes an argument if it exists
     *
     * @param string $argument The name of the argument
     * @param bool $escape Whether or not to escape the argument ($this->get() uses this method, and there's no reason
     *     to escape twice)
     * @param bool $useAliases Whether or not to check aliases
     */
    public function remove($argument, $escape = true, $useAliases = true) {
        if ($escape) {
            $argument = $this->escapeArgument($argument);
        }

        if ($useAliases && !empty($this->aliases[$argument])) {
            foreach ($this->aliases[$argument] as $alias) {
                if ($this->has($alias, true, false)) {
                    unset($this->arguments[$alias]);

                    continue;
                }
            }
        }

        unset($this->arguments[$argument]);
    }

    /**
     * Checks whether an argument exists
     *
     * @param string $argument The name of the argument
     * @param bool $escape Whether or not to escape the argument ($this->get() uses this method, and there's no reason
     *     to escape twice)
     * @param bool $useAliases Whether or not to check aliases
     *
     * @return bool
     */
    public function has($argument, $escape = true, $useAliases = true)
    {
        if ($escape) {
            $argument = $this->escapeArgument($argument);
        }

        if ($useAliases && !empty($this->aliases[$argument])) {
            foreach ($this->aliases[$argument] as $alias) {
                if ($this->has($alias, true, false)) {
                    return true;
                }
            }
        }

        return isset($this->arguments[$argument]);
    }

    /**
     * Checks whether an argument equals a value (uses strict comparison)
     *
     * @param string $argument The name of the argument
     * @param mixed $checkValue The value to check
     * @param bool $strict Whether or not to use strict (===) comparison - if FALSE it will be losely compared (==)
     *
     * @return bool Whether or not the argument equals to $checkValue
     */
    public function equals($argument, $checkValue, $strict = true)
    {
        $realValue = $this->get($argument, "\0");

        if (!$strict) {
            return $realValue == $checkValue;
        }

        return $realValue === $checkValue;
    }

    /**
     * Sets a bunch of arguments through a key-value pair array
     *
     * @param array $arguments A key-value pair array where the key is the argument and the value is the value
     */
    public function setArray($arguments)
    {
        foreach ($arguments as $argument => $value) {
            $this->set($argument, $value);
        }
    }

    /**
     * Sets a single argument
     *
     * @param string $argument The name of the argument
     * @param mixed $value The value to set the argument to
     *
     * @throws \Exception An exception will be thrown if you try to set an anonymous variable using this method
     */
    public function set($argument, $value)
    {
        $argument = $this->escapeArgument($argument);

        if ($argument === 'anonymous') {
            throw new \Exception('You have to use setAnonymous() to set an anonymous argument');
        }

        $this->arguments[$argument] = $value;
    }

    /**
     * Checks whether an anonymous argument exists
     *
     * @param int $index The index of the anonymous argument
     *
     * @return bool
     */
    public function hasAnonymous($index)
    {
        return isset($this->arguments['anonymous'][$index]);
    }

    /**
     * @param int|null $index The index of the anonymous argument (or NULL for all anonymous arguments)
     * @param mixed $default The default value if the argument does not exist
     *
     * @return mixed|array
     */
    public function getAnonymous($index = null, $default = null)
    {
        if ($index !== null) {
            if ($this->hasAnonymous($index)) {
                return $this->arguments['anonymous'][$index];
            }

            return $default;
        }

        return $this->arguments['anonymous'];
    }

    /**
     * Adds an anonymous argument (an argument without a key)
     *
     * @param $value
     */
    public function addAnonymous($value)
    {
        $this->arguments['anonymous'][] = $value;
    }

    /**
     * Gets an argument
     *
     * @param string $argument The name of the argument
     * @param mixed $default The default value if the argument does not exist
     * @param bool $useAliases
     *
     * @return mixed
     * @throws \Exception
     */
    public function get($argument, $default = null, $useAliases = true)
    {
        $argument = $this->escapeArgument($argument);

        if (!$this->has($argument, false, $useAliases)) {
            return $default;
        }

        if (!isset($this->arguments[$argument])) {
            // It must be an alias
            if (!isset($this->aliases[$argument])) {
                throw new \Exception(sprintf('Apparently argument %s was not set, but neither was the alias',
                    $argument));
            }

            foreach ($this->aliases[$argument] as $alias) {
                if (!$this->has($alias, false, false)) {
                    continue;
                }

                return $this->get($alias, $default, false);
            }

            return $default;
        }

        return $this->arguments[$argument];
    }

	/**
	 * Creates argument aliases - makes all the specified arguments act the same way (essentially being aliases of
	 * eachother)
	 *
	 * @param string $alias The alias to refer to $argument
	 * @param string $argument The argument to create an alias for
	 *
	 * @return $this An instance of Arguments, for chaining
	 */
	public function alias($alias, $argument)
	{
		$alias = $this->escapeArgument($alias);
		$argument = $this->escapeArgument($argument);

		if (empty($this->aliases[ $alias ])) {
			$this->aliases[ $alias ] = [$argument];
		} else {
			$this->aliases[ $alias ][] = $argument;
		}

		return $this; // For chaining
	}

	/**
	 * @param array $kvpArgumentAliases An array where the key is the alias and the value is the argument to create the alias for
	 *
	 * @return $this An instance of Arguments, for chaining
	 */
	public function aliases($kvpArgumentAliases) {
		foreach ($kvpArgumentAliases as $argument => $aliases) {
			$this->alias($aliases, $argument);
		}

		return $this; // For chaining
	}

    /**
     * Magic alias for $this->has($argument)
     *
     * @param string $argument The name of the argument
     *
     * @return bool
     */
    public function __isset($argument)
    {
        return $this->has($argument);
    }

    /**
     * Magic alias for $this->set($argument, $value)
     **
     *
     * @param string $argument The name of the argument
     * @param mixed $value The value to set the argument to
     */
    public function __set($argument, $value)
    {
        $this->set($argument, $value);
    }

    /**
     * Magic alias for $this->get($argument)
     *
     * @param string $argument The name of the argument
     *
     * @return mixed
     */
    public function __get($argument)
    {
        return $this->get($argument);
    }

    /**
     * @return array
     */
    public function argv() {
        $argv = [];

        foreach ($this->getAnonymous() as $anonymousArgument) {
            $argv[] = $anonymousArgument;
        }

        foreach ($this->arguments as $argument => $value) {
            if ($argument === 'anonymous') {
                continue;
            }

            $argv[] = '--' . $argument;
            $argv[] = $value;
        }

        return $argv;
    }

    /**
     * @return int
     */
    public function argc() {
         $argv = $this->argv();
         
        return count($argv);
    }
}