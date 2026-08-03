<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TCMP_Logger {
	private $name;
	private $context = array();

	public function __construct( $name = 'TCMP' ) {
		if ( '' == $name ) {
			$name = 'TCMP';
		}
		$this->name = $name;
	}

	public function pushContext( $context ) {
		array_push( $this->context, $context );
	}
	public function popContext() {
		array_pop( $this->context );
	}

	public function fatal( $message, $v1 = null, $v2 = null, $v3 = null, $v4 = null, $v5 = null, $v6 = null ) {
		$what = $this->write( '[FATAL]', $message, $v1, $v2, $v3, $v4, $v5, $v6 );
		// The message can interpolate snippet names and other stored values, so
		// escape before it reaches the browser.
		die( esc_html( $what ) );
	}
	public function debug( $message, $v1 = null, $v2 = null, $v3 = null, $v4 = null, $v5 = null, $v6 = null ) {
		$this->write( '[DEBUG]', $message, $v1, $v2, $v3, $v4, $v5, $v6 );
	}
	public function info( $message, $v1 = null, $v2 = null, $v3 = null, $v4 = null, $v5 = null, $v6 = null ) {
		$this->write( '[INFO] ', $message, $v1, $v2, $v3, $v4, $v5, $v6 );
	}
	public function error( $message, $v1 = null, $v2 = null, $v3 = null, $v4 = null, $v5 = null, $v6 = null ) {
		$this->write( '[ERROR]', $message, $v1, $v2, $v3, $v4, $v5, $v6 );
	}
	private function dump( $v ) {
		if ( is_array( $v ) && 0 == count( $v ) ) {
			$v = '[]';
		}
		if ( null != $v ) {
			if ( is_array( $v ) || is_object( $v ) ) {
				$v = print_r( $v, true );
			}
		}
		if ( is_bool( $v ) ) {
			$v = ( $v ? 'TRUE' : 'FALSE' );
		}
		return $v;
	}
	private function write( $verbosity, $message, $v1 = null, $v2 = null, $v3 = null, $v4 = null, $v5 = null, $v6 = null ) {
		global $tcmp;

		$text    = sprintf(
			$message,
			$this->dump( $v1 ),
			$this->dump( $v2 ),
			$this->dump( $v3 ),
			$this->dump( $v4 ),
			$this->dump( $v5 ),
			$this->dump( $v6 )
		);
		$message = date( 'd/m/Y H:i:s' ) . ' ' . $verbosity . ' ';
		if ( count( $this->context ) > 0 ) {
			$message .= '{' . $this->context[ count( $this->context ) - 1 ] . '} ';
		}
		$message = "\n" . $message . $text;
		if ( ! $tcmp->options->isLoggerEnable() ) {
			return $message;
		}

		$hasErrors = false;
		$dir       = $this->get_log_dir();
		if ( '' == $dir ) {
			return $message;
		}
		// The filename carries a site-specific, unguessable suffix so the log is
		// not fetchable by guessing the URL, even on servers where the deny
		// rules below are not honoured (e.g. nginx ignores .htaccess). See F-07.
		$name     = sanitize_file_name( $this->name );
		$filename = $dir . $name . '_' . gmdate( 'Ym' ) . '_' . $this->log_token() . '.txt';
		if ( ! $handle = fopen( $filename, 'a' ) ) {
			$hasErrors = true;
		}

		if ( ! $hasErrors && fwrite( $handle, $message ) === false ) {
			$hasErrors = true;
		}

		if ( ! $hasErrors ) {
			fclose( $handle );
		}
		return $message;
	}

	// Resolve (and lazily create + protect) the directory logs are written to.
	// Logs live under wp-content/uploads/ rather than inside the plugin folder,
	// and the directory is hardened with deny rules so it is not web-readable
	// (see F-07).
	private function get_log_dir() {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return '';
		}
		$upload = wp_upload_dir();
		if ( ! is_array( $upload ) || empty( $upload['basedir'] ) ) {
			return '';
		}
		$dir = trailingslashit( $upload['basedir'] ) . 'tcm-logs/';
		if ( ! is_dir( $dir ) && function_exists( 'wp_mkdir_p' ) ) {
			wp_mkdir_p( $dir );
		}
		if ( ! is_dir( $dir ) ) {
			return '';
		}
		$this->protect_dir( $dir );
		return $dir;
	}

	// Drop the standard "deny all" guard files into the log directory so the
	// logs cannot be downloaded over HTTP on either Apache or IIS, plus a blank
	// index.php to prevent directory listing.
	private function protect_dir( $dir ) {
		$guards = array(
			'index.php'  => "<?php\n// Silence is golden.\n",
			'.htaccess'  => "Order Allow,Deny\nDeny from all\n",
			'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n\t<system.webServer>\n\t\t<authorization>\n\t\t\t<deny users=\"*\" />\n\t\t</authorization>\n\t</system.webServer>\n</configuration>\n",
		);
		foreach ( $guards as $file => $contents ) {
			$path = $dir . $file;
			if ( ! file_exists( $path ) ) {
				@file_put_contents( $path, $contents );
			}
		}
	}

	// Short, deterministic, site-specific token used only to obfuscate the log
	// filename. Derived from WordPress salts when available; this is filename
	// obfuscation, not a security-sensitive digest.
	private function log_token() {
		$seed = 'tcm-log';
		if ( defined( 'AUTH_SALT' ) && '' != AUTH_SALT ) {
			$seed .= AUTH_SALT;
		} elseif ( defined( 'ABSPATH' ) ) {
			$seed .= ABSPATH;
		}
		if ( function_exists( 'wp_hash' ) ) {
			return substr( wp_hash( $seed ), 0, 12 );
		}
		return substr( md5( $seed ), 0, 12 );
	}
}
