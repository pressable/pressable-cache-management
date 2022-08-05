<?php
function pressable_cache_extend()
{

}

// http://www.php.net/is_writable
function is_writeable_wp_config($path)
{

    if ((defined('PHP_OS_FAMILY') && 'Windows' !== constant('PHP_OS_FAMILY')) || stristr(PHP_OS, 'DAR') || !stristr(PHP_OS, 'WIN'))
    {
        return is_writeable($path);
    }

    // PHP's is_writable does not work with Win32 NTFS
    if ($path[strlen($path) - 1] == '/')
    { // recursively return a temporary file path
        return is_writeable_wp_config($path . uniqid(mt_rand()) . '.tmp');
    }
    elseif (is_dir($path))
    {
        return is_writeable_wp_config($path . '/' . uniqid(mt_rand()) . '.tmp');
    }

    // check tmp file for read/write capabilities
    $rm = file_exists($path);
    $f = @fopen($path, 'a');
    if ($f === false) return false;
    fclose($f);
    if (!$rm)
    {
        unlink($path);
    }

    return true;
}

// function wp_cache_setting( $field, $value ) {
// 	global $wp_cache_config_file;
// 	$GLOBALS[ $field ] = $value;
// 	if ( is_numeric( $value ) ) {
// 		return wp_config_file_replace_line( '^ *\$' . $field, "\$$field = $value;", $wp_cache_config_file );
// 	} elseif ( is_bool( $value ) ) {
// 		$output_value = $value === true ? 'true' : 'false';
// 		return wp_config_file_replace_line( '^ *\$' . $field, "\$$field = $output_value;", $wp_cache_config_file );
// 	} elseif ( is_object( $value ) || is_array( $value ) ) {
// 		$text = var_export( $value, true );
// 		$text = preg_replace( '/[\s]+/', ' ', $text );
// 		return wp_config_file_replace_line( '^ *\$' . $field, "\$$field = $text;", $wp_cache_config_file );
// 	} else {
// 		return wp_config_file_replace_line( '^ *\$' . $field, "\$$field = '$value';", $wp_cache_config_file );
// 	}
// }
function wp_config_file_replace_line($old, $new, $my_file)
{
    if (@is_file($my_file) == false)
    {
        if (function_exists('set_transient'))
        {
            set_transient('wpsc_config_error', 'config_file_missing', 10);
        }
        return false;
    }
    if (!is_writeable_wp_config($my_file))
    {
        if (function_exists('set_transient'))
        {
            set_transient('wpsc_config_error', 'config_file_ro', 10);
        }
        trigger_error("Error: file $my_file is not writable.");
        return false;
    }

    $found = false;
    $loaded = false;
    $c = 0;
    $lines = array();
    while (!$loaded)
    {
        $lines = file($my_file);
        if (!empty($lines) && is_array($lines))
        {
            $loaded = true;
        }
        else
        {
            $c++;
            if ($c > 100)
            {
                if (function_exists('set_transient'))
                {
                    set_transient('wpsc_config_error', 'config_file_not_loaded', 10);
                }
                trigger_error("wp_config_file_replace_line: Error  - file $my_file could not be loaded.");
                return false;
            }
        }
    }
    foreach ((array)$lines as $line)
    {
        if (trim($new) != '' && trim($new) == trim($line))
        {
            pressable_cache_extend("wp_config_file_replace_line: setting not changed - $new");
            return true;
        }
        elseif (preg_match("/$old/", $line))
        {
            pressable_cache_extend("wp_config_file_replace_line: changing line " . trim($line) . " to *$new*");
            $found = true;
        }
    }

    global $cache_path;
    $tmp_config_filename = tempnam($GLOBALS['cache_path'], 'wpsc');
    rename($tmp_config_filename, $tmp_config_filename . ".php");
    $tmp_config_filename .= ".php";
    pressable_cache_extend('wp_config_file_replace_line: writing to ' . $tmp_config_filename);
    $fd = fopen($tmp_config_filename, 'w');
    if (!$fd)
    {
        if (function_exists('set_transient'))
        {
            set_transient('wpsc_config_error', 'config_file_ro', 10);
        }
        trigger_error("wp_config_file_replace_line: Error  - could not write to $my_file");
        return false;
    }
    if ($found)
    {
        foreach ((array)$lines as $line)
        {
            if (!preg_match("/$old/", $line))
            {
                fputs($fd, $line);
            }
            elseif ($new != '')
            {
                fputs($fd, "$new\n");
            }
        }
    }
    else
    {
        $done = false;
        foreach ((array)$lines as $line)
        {
            // if ( $done || ! preg_match( '/\brequire_once\b/i', $line ) ) {
            if ($done || !preg_match('/\b(require_once)\b/', $line))
            {
                fputs($fd, $line);
            }
            else
            {
                //add fputs($fd, "$new\n"); here to write function above require_once
                
                fputs($fd, $line);
                //Write function at the button of require_once
                fputs($fd, "$new\n");
                $done = true;

            }
        }
    }
    fclose($fd);
    rename($tmp_config_filename, $my_file);
    pressable_cache_extend('wp_config_file_replace_line: moved ' . $tmp_config_filename . ' to ' . $my_file);

    if (function_exists("opcache_invalidate"))
    {
        @opcache_invalidate($my_file);
    }

    return true;
}