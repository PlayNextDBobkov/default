<?php

	class NavPathPage {
		var $url;
		var $name;
		var $title;

		public function NavPathPage($name, $title, $url) {
			$this->name = $name;
			$this->title = $title;
			$this->url = $url;
		}
	}
?>
