<?
	require_once("ErrPage.php");

	class Err404 extends ErrPage {
		var $title = "Ошибка 404";

		public function init() {
			header("HTTP/1.0 404 Not Found");
			parent::init();
		}

		/**
			Overrided to output error message.
		*/
		public function showBody() {
?>
<h1>Страница не найдена</h1>
<p>Возможно страница была удалена или переехала на другой адрес.<br />
Пожалуйста, <a href="/site-map.html">попробуйте найти ее на карте сайта</a>.</p>
<p><a href="/">Вернуться на главную страницу</a>.</p>
<?
		}
	}
?>