<?php
/**
 * general.php
 *
 * @package MCManager.includes
 * @author Moxiecode
 * @copyright Copyright © 2006, Moxiecode Systems AB, All rights reserved.
 */

function getRequestParam($name, $default_value = false) {
	if (!isset($_REQUEST[$name]))
		return $default_value;

	if (!isset($_GLOBALS['magic_quotes_gpc']))
		$_GLOBALS['magic_quotes_gpc'] = ini_get("magic_quotes_gpc");

	if (isset($_GLOBALS['magic_quotes_gpc'])) {
		if (is_array($_REQUEST[$name])) {
			$newarray = array();

			foreach($_REQUEST[$name] as $name => $value)
				$newarray[stripslashes($name)] = stripslashes($value);

			return $newarray;
		}
		return stripslashes($_REQUEST[$name]);
	}

	return $_REQUEST[$name];
}

?>