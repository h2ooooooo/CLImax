<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 18/10/2018
 * Time: 10.25
 */

namespace CLImax;

/**
 * Class SubApplication
 * @package CLImax
 */
abstract class SubApplication extends Application
{
    /** @var Application $_application */
    private $_application;

    /**
     * Creates a new sub application
     *
     * @param Application $application A handle to the main application running this sub application
     */
    public function __construct(Application $application)
    {
        $this->_application = $application;
    }

    /**
     * Magically calls a method of the main application
     *
     * @param string          $method
     * @param array|Arguments $arguments
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->_application, $method)) {
            return call_user_func_array(array($this->_application, $method), $arguments);
        }

        throw new \Exception(sprintf('Application method "%s" not found', $method));
    }
}