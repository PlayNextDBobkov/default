<?
	require_once("WebPage.php");

	class ErrPage extends WebPage {
		var $title = "Ошибка";
		var $logger;

		public function init() {
			parent::init();
			$this->cssFiles["_ErrPage.css"] = true;
			$this->cssFiles["message.css"] = true;

			// log error:
			$this->logger = Logger::fileLogger("ErrPage.log");
			$this->logger->err(get_class($this).($this->body?": ".$this->body:""));
		}

		/**
			Overrided to output error message.
		*/
		public function showBeforeBody() {
			eval("\$url = Config::URL_".strtoupper(E5::$languageId).";");
?>
<h3><?= $url.$_SERVER['REQUEST_URI'] ?></h3>
<h2><?= $this->title ?></h2>
<?
		}

		public function showBody() {
			if ( $this->body && $_SERVER['E5_ENV'] ) {
?>
<div class="err"><?= nl2br($this->body) ?></div>
<?
			}
		}
	}
?>