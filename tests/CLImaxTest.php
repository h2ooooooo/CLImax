<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 19-08-2016
 * Time: 16:30
 */

namespace CLImax\Tests;

use CLImax\Application;

require_once(dirname(__FILE__) . '/../vendor/autoload.php');
/*class CLImaxTest extends \PHPUnit_Framework_TestCase
{
}*/

/**
 * Class CLImaxTestApplication
 * @package CLImax\Tests
 */
class CLImaxTestApplication extends Application {
    public function init() {
        $answer = $this->question->ask('Test', [
        	'mask' => true,
        ]);

        var_dump($answer);
    }
}

CLImaxTestApplication::launch();