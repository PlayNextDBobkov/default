<?
	class Language {
		const CHARSET_UTF8		= "UTF-8";
		const CHARSET_CP1251	= "windows-1251";

		const LOCALE_UTF8		= "en_US.UTF8";
		const LOCALE_CP1251		= "ru_RU.cp1251";

		// known languages:
		const RU				= "ru";
		const RU_NAME			= "Русский";
		const EN				= "en";
		const EN_NAME			= "English";

		var $id;
		var $name;
		var $url;

		public function Language($id, $name, $url) {
			$this->id = $id;
			$this->name = $name;
			$this->url = $url;
		}

		public function url() {
			$url = $_SERVER['REQUEST_URI'];
			return $this->url.$url;
		}
	}
?>