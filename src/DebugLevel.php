<?php
/**
 * CLImax
 * @author Andreas Jalsøe
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Debug levels to be used for describing how critical a message
 * is to be shown in the application
 */
class DebugLevel {
	const ALWAYS_PRINT = 0; //Will always be printed, no matter what the debug level is - eg. "Starting application"
	const SUCCESS = 1; //When something is a success - eg. "Successfully ran command"
	const FATAL = 2; //An unrecoverable error - you should always exit() after this, since it's unrecoverable - eg. "Config not found"
	const ERROR = 3; //An error happened, but the application can still continue - eg. "User with ID 123 not found"
	const WARNING = 4; //A warning to pay attention to, but is not critical - eg. "The users 'last_login' column is empty"
	const INFO = 5; //info - eg. "The application is located at /home/climax/app.php"
	const DEBUG = 6; //For messages which are fairly verbose, but not completely - eg. "Scanning DB.." or "Computing.."
	const VERBOSE = 7; //Complete verboseness - should output lots of junk that you usually don't want to show - eg. "Scanning subfolder /path/to/folder"
}