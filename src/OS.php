<?php
/**
 * CLImax
 * @author Andreas Jals�e
 * @website http://www.jalsoedesign.net
 */

namespace CLImax;

/**
 * Class OS
 * @package CLImax
 */
class OS extends Module {
	private $osUname;
	private $os;

	/**
	 * @return string
	 * @throws \Exception
     */
	public function getOS() {
		if ( $this->os === null ) {
			$this->osUname = php_uname( 's' );

			if ( preg_match( '/^Windows/i', $this->osUname ) ) {
				$this->os = 'windows';
			} else if ( preg_match( '/^Linux/i', $this->osUname ) ) {
				$this->os = 'linux';
            } else if ( preg_match( '/^Darwin/i', $this->osUname ) ) {
                $this->os = 'darwin'; // MacOS
			} else {
				throw new \Exception( sprintf( 'Could not parse uname "%s"', $this->osUname ) );
			}
		}

		return $this->os;
	}

	/**
	 * @return bool
	 * @throws \Exception
     */
	public function isWindows() {
		return $this->getOS() === 'windows';
	}

    /**
     * @return bool
     * @throws \Exception
     */
    public function isUnix() {
        return $this->isLinux() || $this->isDarwin();
    }

	/**
	 * @return bool
	 * @throws \Exception
     */
	public function isLinux() {
		return $this->getOS() === 'linux';
	}

    /**
     * @return bool
     * @throws \Exception
     */
    public function isDarwin() {
        return $this->getOS() === 'darwin';
    }
}