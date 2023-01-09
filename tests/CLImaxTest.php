<?php
/**
 * Created by PhpStorm.
 * User: aj
 * Date: 19-08-2016
 * Time: 16:30
 */

namespace CLImax\Tests;

use CLImax\Application;
use CLImax\ApplicationUtf8;

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

/**
 * Class CLImaxTestApplication
 * @package CLImax\Tests
 */
class CLImaxTestApplication extends ApplicationUtf8 {
    public function init() {
    	$this->attachWebInterface();

    	$this->verbose('verbose');
	    $this->debug('debug');
	    $this->info('info');
	    $this->success('success');
	    $this->warning('warning');
	    $this->error('error');

		$progressMessage = $this->progressMessage->verbose('Computing..');
		usleep(1000000);
		$progressMessage->success();

	    $progressMessage = $this->progressMessage->verbose('Computing..');
	    usleep(1000000);
	    $progressMessage->success('Successfully computed for 1 second');

	    $progressMessage = $this->progressMessage->verbose('Computing..');
	    usleep(1000000);
	    $progressMessage->error();

	    $progressMessage = $this->progressMessage->verbose('Computing..');
	    usleep(1000000);
	    $progressMessage->error('Could not compute');

	    $answer = $this->question->ask('What is your answer?', [
		    'default' => 'nothing',
	    ]);

	    for ($i = 10; $i > 0; $i--) {
		    $this->verbose(sprintf('Countdown: %d', $i));

		    $this->sleep(1, null);
	    }

	    $this->verbose(sprintf('Your answer was: %s', $answer));
    }

    public function attachWebInterface() {

    }
}

CLImaxTestApplication::launch();
