<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 27/11/2018
 * Time: 18:10
 */

namespace CLImax;

use CLImax\Components\ProgressBar;

class ComponentFactory extends Module {
	public function newProgressBar($total, $textColour = null, $backgroundColour = null, $start = 0, $message = null) {
		return new ProgressBar($this->application, $total, $textColour, $backgroundColour, $start, $message);
	}
}