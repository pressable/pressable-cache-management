<?php
/**
 * WordPress readme.txt parser.
 *
 * Parses a readme.txt file formatted according to the WordPress.org plugin readme standard.
 * This is the vendor file expected by plugin-update-checker (Puc v5) at:
 *   plugin-update-checker/vendor/PucReadmeParser.php
 *
 * Based on the WordPress.org readme parser. Restored because the vendor/ directory
 * was missing from the plugin package, causing:
 *   Fatal error: Class "PucReadmeParser" not found in .../Puc/v5p6/Vcs/Api.php:139
 */

if ( ! class_exists( 'PucReadmeParser', false ) ) :

class PucReadmeParser {

	/**
	 * Parse the contents of a readme.txt file.
	 *
	 * @param string $contents The raw contents of a readme.txt file.
	 * @return array Associative array of parsed fields.
	 */
	public function parse_readme_contents( $contents ) {
		$contents = (string) $contents;
		$contents = preg_replace( '/\r\n|\r/', "\n", $contents );
		$contents = trim( $contents );

		if ( empty( $contents ) ) {
			return array();
		}

		$data = array(
			'name'              => '',
			'tags'              => array(),
			'requires'          => '',
			'tested'            => '',
			'requires_php'      => '',
			'contributors'      => array(),
			'stable_tag'        => '',
			'donate_link'       => '',
			'short_description' => '',
			'sections'          => array(),
		);

		$lines = explode( "\n", $contents );

		// First line: plugin name (== Plugin Name ==)
		$name_line = array_shift( $lines );
		if ( preg_match( '/^=+\s*(.+?)\s*=+$/', $name_line, $m ) ) {
			$data['name'] = $m[1];
		}

		// Parse header fields up to the first blank line after the name
		$in_header  = true;
		$remaining  = array();

		foreach ( $lines as $line ) {
			if ( $in_header ) {
				if ( trim( $line ) === '' ) {
					// blank line ends the header block only if we've seen at least one header
					if ( ! empty( $data['requires'] ) || ! empty( $data['stable_tag'] ) || ! empty( $data['short_description'] ) ) {
						$in_header = false;
					}
					$remaining[] = $line;
					continue;
				}

				if ( preg_match( '/^([^:]+):\s*(.*)$/', $line, $m ) ) {
					$key   = strtolower( trim( $m[1] ) );
					$value = trim( $m[2] );

					switch ( $key ) {
						case 'tags':
							$data['tags'] = array_map( 'trim', explode( ',', $value ) );
							break;
						case 'requires at least':
							$data['requires'] = $value;
							break;
						case 'tested up to':
							$data['tested'] = $value;
							break;
						case 'requires php':
							$data['requires_php'] = $value;
							break;
						case 'stable tag':
							$data['stable_tag'] = $value;
							break;
						case 'contributors':
							$data['contributors'] = array_map( 'trim', explode( ',', $value ) );
							break;
						case 'donate link':
							$data['donate_link'] = $value;
							break;
					}
				} else {
					// Not a header field — treat as short description or start of body
					$trimmed = trim( $line );
					if ( $trimmed !== '' && empty( $data['short_description'] ) ) {
						$data['short_description'] = $trimmed;
					}
					$remaining[] = $line;
				}
			} else {
				$remaining[] = $line;
			}
		}

		// Short description: first non-empty, non-header line before sections
		if ( empty( $data['short_description'] ) ) {
			foreach ( $remaining as $line ) {
				$trimmed = trim( $line );
				if ( $trimmed !== '' && ! preg_match( '/^=+/', $trimmed ) ) {
					$data['short_description'] = $trimmed;
					break;
				}
			}
		}

		// Truncate short description at 150 chars
		if ( strlen( $data['short_description'] ) > 150 ) {
			$data['short_description'] = substr( $data['short_description'], 0, 150 );
		}

		// Parse sections (== Section Name ==)
		$body          = implode( "\n", $remaining );
		$section_parts = preg_split( '/^==\s*(.+?)\s*==\s*$/m', $body, -1, PREG_SPLIT_DELIM_CAPTURE );

		// section_parts: [ text_before, name1, body1, name2, body2, ... ]
		for ( $i = 1; $i < count( $section_parts ); $i += 2 ) {
			$section_name = strtolower( trim( $section_parts[ $i ] ) );
			$section_name = str_replace( ' ', '_', $section_name );
			$section_body = isset( $section_parts[ $i + 1 ] ) ? trim( $section_parts[ $i + 1 ] ) : '';

			$data['sections'][ $section_name ] = $section_body;
		}

		return $data;
	}

	/**
	 * Parse a readme.txt file by path.
	 *
	 * @param string $file_path Absolute path to the readme.txt file.
	 * @return array|WP_Error Parsed data or WP_Error on failure.
	 */
	public function parse_readme( $file_path ) {
		if ( ! is_readable( $file_path ) ) {
			if ( function_exists( 'is_wp_error' ) ) {
				return new WP_Error( 'puc_readme_not_found', 'Readme file not found: ' . $file_path );
			}
			return array();
		}
		return $this->parse_readme_contents( file_get_contents( $file_path ) );
	}
}

endif;
