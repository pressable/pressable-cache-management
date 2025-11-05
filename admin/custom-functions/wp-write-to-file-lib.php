<?php
/**
 * Library for writing to WordPress configuration files.
 *
 * @package Pressable
 */

/**
 * Extends the Pressable cache.
 */
function pressable_cache_extend() {
}

/**
 * Checks if the wp-config.php file is writable.
 *
 * @param string $path The path to the wp-config.php file.
 * @return bool True if the file is writable, false otherwise.
 */
function is_writeable_wp_config( $path ) {
	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
	}

	return $wp_filesystem->is_writable( $path );
}

/**
 * Replaces a line in the wp-config.php file.
 *
 * @param string $old      The old line to replace.
 * @param string $new_line The new line to insert.
 * @param string $my_file  The path to the wp-config.php file.
 * @return bool True on success, false on failure.
 */
function wp_config_file_replace_line( $old, $new_line, $my_file ) {
	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
	}

	if ( ! $wp_filesystem->is_file( $my_file ) ) {
		if ( function_exists( 'set_transient' ) ) {
			set_transient( 'wpsc_config_error', 'config_file_missing', 10 );
		}
		return false;
	}

	if ( ! is_writeable_wp_config( $my_file ) ) {
		if ( function_exists( 'set_transient' ) ) {
			set_transient( 'wpsc_config_error', 'config_file_ro', 10 );
		}
		return false;
	}

	$lines = $wp_filesystem->get_contents_array( $my_file );
	if ( false === $lines ) {
		if ( function_exists( 'set_transient' ) ) {
			set_transient( 'wpsc_config_error', 'config_file_not_loaded', 10 );
		}
		return false;
	}

	$found = false;
	foreach ( $lines as $line ) {
		if ( trim( $new_line ) !== '' && trim( $new_line ) === trim( $line ) ) {
			return true;
		} elseif ( preg_match( "/$old/", $line ) ) {
			$found = true;
		}
	}

	$new_contents = '';
	if ( $found ) {
		foreach ( $lines as $line ) {
			if ( ! preg_match( "/$old/", $line ) ) {
				$new_contents .= $line;
			} elseif ( '' !== $new_line ) {
				$new_contents .= "$new_line\n";
			}
		}
	} else {
		$done = false;
		foreach ( $lines as $line ) {
			if ( $done || ! preg_match( '/\b(require_once)\b/', $line ) ) {
				$new_contents .= $line;
			} else {
				$new_contents .= $line;
				$new_contents .= "$new_line\n";
				$done          = true;
			}
		}
	}

	if ( ! $wp_filesystem->put_contents( $my_file, $new_contents ) ) {
		if ( function_exists( 'set_transient' ) ) {
			set_transient( 'wpsc_config_error', 'config_file_ro', 10 );
		}
		return false;
	}

	if ( function_exists( 'wp_opcache_invalidate' ) ) {
		wp_opcache_invalidate( $my_file );
	}

	return true;
}
