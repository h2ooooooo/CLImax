<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Just like application except this defaults to NOT decoding utf8, so make sure that your console is utf8 compatible
 *
 * @mixin Application
 */
abstract class ApplicationUtf8 extends Application {
	public function decodeUtf8() {
		return false;
	}

	public function isUtf8() {
		return true;
	}
}