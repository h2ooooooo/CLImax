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

/**
 * Class CLImaxTestApplication
 * @package CLImax\Tests
 */
class CLImaxTestApplication extends Application {
    public function init() {
    	$this->attachWebinterface();

    	$this->verbose('verbose');
	    $this->debug('debug');
	    $this->info('info');
	    $this->success('success');
	    $this->warning('warning');
	    $this->error('error');

	    $answer = $this->question->ask('What is your answer?', [
		    'default' => 'nothing',
	    ]);

	    for ($i = 10; $i > 0; $i--) {
		    $this->verbose(sprintf('Countdown: %d', $i));

		    $this->sleep(1, null);
	    }

	    $this->verbose(sprintf('Your answer was: %s', $answer));
    }

    public function attachWebinterface() {

    }
}

CLImaxTestApplication::launch();