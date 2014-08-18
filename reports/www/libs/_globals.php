<?
	/**
		E7 web-site engine.
		(c) PlayNext 2003-2013.
		Version: 2013-05-16.
	*/

	/**
		Some usefull definitions.
	*/
	define("LF", "<br />");

	/**
		Shorter alias for PHP htmlspecialchars() method.
		Parses string for HTML entities and fixes them for HTML output.
	*/
	function p($s) {
		return htmlspecialchars($s);
	}

	/**
		Dumps array.
	*/
	function da($a)	{
		if ( MODE_HTML ) {
			$cr = "<br>";
			$indent = "<div style='padding-left:8px'>";
			$indent_ = "</div>";
		}
		else {
			$cr = "\n";
			$indent = "  ";
			$indent_ = "";
		}

		reset($a);
		foreach ( $a as $key => $value ) {
			if ( is_array($value) )	{
				echo $key.": array: ".$cr;
				echo $indent;
				da($value);
				echo $indent_;
			}
			else if ( is_object($value) )	{
				echo $key.": object: ".$cr;
				echo $indent;
				da($value);
				echo $indent_;
			}
			else echo $key.": ".$value.$cr;
		}
	}

	/**
		Trims a string or array of string recursivly.
	*/
	function t($s, $exclude=array()) {
		if ( is_array($s) ) {
			foreach ( $s as $key => $value ) {
				if ( ( is_string($value) || is_array($value) ) && !in_array($key, $exclude) ) {
					$s[$key] = t($value);
				}
			}
			return $s;
		}
		return trim($s);
	}

	function js($s) {
		$s = preg_replace("/\"/s", '\\"', $s);
		$s = preg_replace("/\'/s", "\\'", $s);
		$s = preg_replace("/\n/s", "\\n", $s);
		$s = preg_replace("/\r/s", "", $s);
		return $s;
	}

	function s ( $s , $strong=0 ) {
		if ( is_array($s) ) {
			foreach ( $s as $key => $value ) {
				if ( ( is_string($value) || is_array($value) ) && !in_array($key, $exclude) ) {
					$s[$key] = s($value);
				}
			}
			return $s;
		}
		$s = addslashes($s);
		if ( $strong ) $s = str_replace("%", "\\%", $s);
		return $s;
	}

	function go($url, $rnd=0) {
		if ( $rnd )	{
			if ( strstr($url, '#') ) {
				$anchor = preg_replace("/.+?(\#.*)/", "$1", $url);
				$url = preg_replace("/(.*?)\#.*/", "$1", $url);
			}
			else $anchor = "";
			if ( strstr($url, '?') ) header("Location: $url&rnd=".rand(111111,999999).$anchor);
			else header("Location: $url?rnd=".rand(111111,999999).$anchor);
		}
		else header("Location: $url");
		exit();
	}

	function go301($url) {
		header( "HTTP/1.1 301 Moved Permanently" );
		header("Location: $url");
		exit();
	}

	function go302($url) {
		go($url);
	}
?>