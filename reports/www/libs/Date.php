<?
	class Date {
		public static function microtime() {
			list($usec, $sec) = explode(" ",microtime()); 
			return ((float)$usec + (float)$sec); 
		}
		
		public static function now() {
			$now = getdate();
			$now["day"] = $now["mday"];
			$now["month"] = $now["mon"];
			return $now;
		}

		public static function nowMySQL() {
			$now = getdate();
			return $now['year'].".".sprintf("%02d", $now['mon']).".".sprintf("%02d", $now['mday'])
					." ".sprintf("%02d", $now['hours']).":".sprintf("%02d", $now['minutes']).":".sprintf("%02d", $now['seconds']);
		}
		
		public static function mySQL2DMRY($mySqlDate, $lang=NULL) {
			if ( $lang == NULL ) $lang = E5::$languageId;
			$months = self::monthsR($lang);
			return sprintf("%02d", intval(substr($mySqlDate, 8,2)))." ".$months[intval(substr($mySqlDate, 5,2))]." ".substr($mySqlDate, 0,4);
		}

		public static function dmy2DMRY($date, $lang=NULL) {
			if ( $lang == NULL ) $lang = E5::$languageId;
			$months = self::monthsR($lang);
			return sprintf("%02d", intval(substr($date, 0,2)))." ".$months[intval(substr($date, 3,2))]." ".substr($date, 6, 4);
		}

		public static function mySQL2MY($mySqlDate) {
			if ( $lang == NULL ) $lang = E5::$languageId;
			$months = self::months($lang);
			return $months[intval(substr($mySqlDate, 5,2))]." ".substr($mySqlDate, 0,4);
		}
		
		public static function mySQL2DMY($mySqlDate) {
			return sprintf("%02d", intval(substr($mySqlDate, 8,2))).".".sprintf("%02d", intval(substr($mySqlDate, 5,2))).".".substr($mySqlDate, 0,4);
		}

		public static function mySQL2DMYHM($mySqlDate) {
			return sprintf("%02d", intval(substr($mySqlDate, 8,2))).".".sprintf("%02d", intval(substr($mySqlDate, 5,2))).".".substr($mySqlDate, 0,4)." ".sprintf("%02d", intval(substr($mySqlDate, 11,2))).":".sprintf("%02d", intval(substr($mySqlDate, 14,2)));
		}

		public static function mySQL2HM($mySqlDate) {
			return sprintf("%02d", intval(substr($mySqlDate, 11,2))).":".sprintf("%02d", intval(substr($mySqlDate, 14,2)));
		}

		public static function DMY2MySQL($date) {
			return substr($date, 6,4)
				."-".sprintf("%02d", intval(substr($date, 3,2)))
				."-".sprintf("%02d", intval(substr($date, 0,2)));
		}

		public static function monthsShort($lang="ru") {
			if ( $lang == "ru" ) return array(
				1 => "янв",
				2 => "фев",
				3 => "мар",
				4 => "апр",
				5 => "май",
				6 => "июн",
				7 => "июл",
				8 => "авг",
				9 => "сен",
				10 => "окт",
				11 => "ноя",
				12 => "дек");
			else if ( $lang == "de" ) return array(
				1 => "Jan",
				2 => "Feb",
				3 => "M"."&#228;"."r",
				4 => "Apr",
				5 => "Mai",
				6 => "Jun",
				7 => "Jul",
				8 => "Aug",
				9 => "Sep",
				10 => "Okt",
				11 => "Nov",
				12 => "Dez");
			else if ( $lang == "no" ) return array(
				1 => "jan",
				2 => "feb",
				3 => "mar",
				4 => "apr",
				5 => "mai",
				6 => "jun",
				7 => "jul",
				8 => "aug",
				9 => "sep",
				10 => "okt",
				11 => "nov",
				12 => "des");
			else return array(
				1 => "Jan",
				2 => "Feb",
				3 => "Mar",
				4 => "Apr",
				5 => "May",
				6 => "Jun",
				7 => "Jul",
				8 => "Aug",
				9 => "Sep",
				10 => "Oct",
				11 => "Nov",
				12 => "Dec"); 
		}

		public static function monthsR($lang="ru") {
			if ( $lang == "ru" ) return array(
				1 => "января",
				2 => "февраля",
				3 => "марта",
				4 => "апреля",
				5 => "мая",
				6 => "июня",
				7 => "июля",
				8 => "августа",
				9 => "сентября",
				10 => "октября",
				11 => "ноября",
				12 => "декабря");
			else if ( $lang == "de" ) return array(
				1 => "Januar",
				2 => "Februar",
				3 => "M"."&#228;"."rz",
				4 => "April",
				5 => "Mai",
				6 => "Juni",
				7 => "Juli",
				8 => "August",
				9 => "September",
				10 => "Oktober",
				11 => "November",
				12 => "Dezember");
			else if ( $lang == "no" ) return array(
				1 => "januar",
				2 => "februar",
				3 => "mars",
				4 => "april",
				5 => "mai",
				6 => "juni",
				7 => "juli",
				8 => "august",
				9 => "september",
				10 => "oktober",
				11 => "november",
				12 => "desember");
			else return array(
				1 => "January",
				2 => "February",
				3 => "March",
				4 => "April",
				5 => "May",
				6 => "June",
				7 => "July",
				8 => "August",
				9 => "September",
				10 => "October",
				11 => "November",
				12 => "December");
		}

		public static function months($lang="ru") {
			if ( $lang == "ru" ) return array(
				1 => "январь",
				2 => "февраль",
				3 => "март",
				4 => "апрель",
				5 => "май",
				6 => "июнь",
				7 => "июль",
				8 => "август",
				9 => "сентябрь",
				10 => "октябрь",
				11 => "ноябрь",
				12 => "декабрь");
			else if ( $lang == "de" ) return array(
				1 => "Januar",
				2 => "Februar",
				3 => "M"."&#228;"."rz",
				4 => "April",
				5 => "Mai",
				6 => "Juni",
				7 => "Juli",
				8 => "August",
				9 => "September",
				10 => "Oktober",
				11 => "November",
				12 => "Dezember");
			else if ( $lang == "no" ) return array(
				1 => "januar",
				2 => "februar",
				3 => "mars",
				4 => "april",
				5 => "mai",
				6 => "juni",
				7 => "juli",
				8 => "august",
				9 => "september",
				10 => "oktober",
				11 => "november",
				12 => "desember");
			else return array(
				1 => "January",
				2 => "February",
				3 => "March",
				4 => "April",
				5 => "May",
				6 => "June",
				7 => "July",
				8 => "August",
				9 => "September",
				10 => "October",
				11 => "November",
				12 => "December");
		}

		public static function weekDays($lang="ru") {
			//if ( $lang == "ru" ) 
			return array(
				0 => "воскресенье",
				1 => "понедельник",
				2 => "вторник",
				3 => "среда",
				4 => "четверг",
				5 => "пятница",
				6 => "суббота",
				7 => "воскресенье");
		}

		public static function weekDayAbbreviations($lang="ru") {
			//if ( $lang == "ru" )
			return array(
				0 => "Вс",
				1 => "Пн",
				2 => "Вт",
				3 => "Ср",
				4 => "Чт",
				5 => "Пт",
				6 => "Сб",
				7 => "Вс");
		}

		public static function mysql2timestamp($mySqlDate) {
			//yyyy-mm-dd hh:mm:ss
			return mktime(
				intval(substr($mySqlDate, 11,2)),
				intval(substr($mySqlDate, 14,2)),
				intval(substr($mySqlDate, 17,2)),
				intval(substr($mySqlDate, 5,2)),
				intval(substr($mySqlDate, 8,2)),
				intval(substr($mySqlDate, 0,4)));
		}

		public static function mysql2Array($mySqlDate) {
			//yyyy-mm-dd hh:mm:ss
			$ts = self::mysql2timestamp($mySqlDate);
			$a = getdate($ts);
			$a["day"] = $a["mday"];
			$a["month"] = $a["mon"];
			return $a;
		}
	}
?>