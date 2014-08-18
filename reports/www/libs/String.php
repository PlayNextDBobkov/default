<?
	/**
		Set of static methods for one-byte/multi-byte strings.
		Supports both ASCII and UTF-8 case operations.
	*/
	class String {
		public static function length($str) {
			if ( Config::CHARSET == Language::CHARSET_UTF8 ) return mb_strlen($str);

			// process one-byte string:
			return strlen($str);
		}

		public static function substring($str, $start, $length) {
			if ( Config::CHARSET == Language::CHARSET_UTF8 ) return mb_substr($str, $start, $length);
			/*
				return preg_replace(
					'#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$start.'}'.
					'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$length.'}).*#s',
					'$1',$str);
				}
			}*/

			// process one-byte string:
			return substr($str, $start, $length);
		}

		public static function url($str) {
			if ( Config::CHARSET == Language::CHARSET_UTF8 ) {
				// table for translitiration:
				$a = array(
					chr(208).chr(144)=>"a",
					chr(208).chr(145)=>"b",
					chr(208).chr(146)=>"v",
					chr(208).chr(147)=>"g",
					chr(208).chr(148)=>"d",
					chr(208).chr(149)=>"e",
					chr(208).chr(129)=>"yo",
					chr(208).chr(150)=>"zh",
					chr(208).chr(151)=>"z",
					chr(208).chr(152)=>"i",
					chr(208).chr(153)=>"y",
					chr(208).chr(154)=>"k",
					chr(208).chr(155)=>"l",
					chr(208).chr(156)=>"m",
					chr(208).chr(157)=>"n",
					chr(208).chr(158)=>"o",
					chr(208).chr(159)=>"p",
					chr(208).chr(160)=>"r",
					chr(208).chr(161)=>"s",
					chr(208).chr(162)=>"t",
					chr(208).chr(163)=>"u",
					chr(208).chr(164)=>"f",
					chr(208).chr(165)=>"h",
					chr(208).chr(166)=>"c",
					chr(208).chr(167)=>"ch",
					chr(208).chr(168)=>"sh",
					chr(208).chr(169)=>"sch",
					chr(208).chr(172)=>"",	// Ь
					chr(208).chr(170)=>"",	// Ъ
					chr(208).chr(171)=>"i",
					chr(208).chr(173)=>"e",
					chr(208).chr(174)=>"yu",
					chr(208).chr(175)=>"ya",
					" "=>"-",
					);
				$rl = array_keys($a);

				// parse all characters in the string:
				$strNew = "";
				$len = self::length($str);
				for($i=0; $i<$len; $i++) {
					$l = mb_strtoupper(self::substring($str, $i, 1));
					if ( in_array($l, $rl) ) $strNew .= $a[$l];
					else $strNew .= self::substring($str, $i, 1);
				}

				// leave only allowed chars:
				$strNew = preg_replace("/[^a-zA-Z0-9-_,\.\']/", "", $strNew);

				// if nothing is left in the string:
				if ( !strlen($strNew) ) $strNew = "new_file";

				return $strNew;
			}
			else {
				// TODO. So far we assume Win1251:

				// table for translitiration:
				$a = array(
					chr(192)=>"a",
					chr(193)=>"b",
					chr(194)=>"v",
					chr(195)=>"g",
					chr(196)=>"d",
					chr(197)=>"e",
					chr(168)=>"yo",
					chr(198)=>"zh",
					chr(199)=>"z",
					chr(200)=>"i",
					chr(201)=>"y",
					chr(202)=>"k",
					chr(203)=>"l",
					chr(204)=>"m",
					chr(205)=>"n",
					chr(206)=>"o",
					chr(207)=>"p",
					chr(208)=>"r",
					chr(209)=>"s",
					chr(210)=>"t",
					chr(211)=>"u",
					chr(212)=>"f",
					chr(213)=>"h",
					chr(214)=>"c",
					chr(215)=>"ch",
					chr(216)=>"sh",
					chr(217)=>"sch",
					chr(220)=>"",	// Ь
					chr(218)=>"",	// Ъ
					chr(219)=>"i",
					chr(221)=>"e",
					chr(222)=>"yu",
					chr(223)=>"ya",
					" "=>"-",
					);
				$rl = array_keys($a);

				// parse all characters in the string:
				$strNew = "";
				for($i=0; $i<strlen($str); $i++) {
					$l = strtoupper($str[$i]);
					if ( in_array($l, $rl) ) $strNew .= $a[$l];
					else $strNew .= $l;
				}

				// leave only allowed chars:
				$strNew = preg_replace("/[^a-zA-Z0-9-_,\.\']/", "", $strNew);

				// if nothing is left in the string:
				if ( !strlen($strNew) ) $strNew = "new_file";

				return $strNew;
			}
		}

		public static function maxChars($str, $max, $padStr="") {
			if ( strlen($str) <= $max ) return $str;
			$a = wordwrap($str, $max, "\t");
			$a = explode("\t", $a);
			return $a[0].$padStr;
		}

		public static function maxWords($str, $maxWords, $padStr="") {
			$words = preg_split("/[ \t\n]/", $str);
			$ww = array();
			foreach ( $words as $w ) {
				$w = trim($w);
				if ( !$w ) continue;
				$ww[] = $w;
				if ( sizeof($ww) >= $maxWords ) break;
			}
			$newStr = implode(" ", $ww);
			if ( String::length($newStr) < String::length($str) ) $newStr = $newStr.$padStr;
			return $newStr;
		}

		public static function end($total, $var1, $var2, $var3) {
			$e = intval(substr($total, strlen($total)-1));
			$e2 = intval(substr($total, strlen($total)-2));
			if ( $e >= 2 && $e <= 4 && !($e2 >= 11 && $e2 <= 19) ) return $var2;
			else if ( $e == 1 && !($e2 >= 11 && $e2 <= 19) ) return $var1;
			return $var3;
		}
	}
?>