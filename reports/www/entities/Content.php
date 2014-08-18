<?
	class Content extends ContentEntity {

		public static function names() {
			return array(
				"FOOTER-COPYRIGHTS"			=> "Копирайт внизу страницы",

				"COUNTERS"					=> "Вэб-счетчики",

				"MAIL-TEMPLATE"				=> "!E-mail, базовый шаблон",

				"EMAIL-REGISTRATION-SUBJECT"	=> "!E-mail уведомление о регистрации, тема письма",
				"EMAIL-REGISTRATION-HTML"		=> "E-mail уведомление о регистрации, HTML",
				"EMAIL-REGISTRATION-PLAIN"		=> "E-mail уведомление о регистрации, текст",

				"EMAIL-CONFIRMATION-SUBJECT"	=> "!E-mail для подтверждения e-mail, тема письма",
				"EMAIL-CONFIRMATION-HTML"		=> "E-mail для подтверждения e-mail, HTML",
				"EMAIL-CONFIRMATION-PLAIN"		=> "E-mail для подтверждения e-mail, текст",

				"EMAIL-REGISTRATION-CONFIRMATION-SUBJECT"	=> "!E-mail уведомление о регистрации + подтверждение e-mail, тема письма",
				"EMAIL-REGISTRATION-CONFIRMATION-HTML"		=> "E-mail уведомление о регистрации + подтверждение e-mail, HTML",
				"EMAIL-REGISTRATION-CONFIRMATION-PLAIN"		=> "E-mail уведомление о регистрации + подтверждение e-mail, текст",

				"EMAIL-CONFIRMATION-NEEDED"	=> "!Текст после регистрации, перед подтверждением e-mail",
				"EMAIL-CONFIRMATION-RETRY"	=> "Текст после регистрации, перед повторным подтверждением e-mail",
				"REGISTERED"				=> "Текст после регистрации, после успешного подтверждения e-mail",
				"EMAIL-CONFIRMATION-ERROR"	=> "Текст после регистрации, после неудачного подтверждения e-mail",
			);
		}
	}
?>
