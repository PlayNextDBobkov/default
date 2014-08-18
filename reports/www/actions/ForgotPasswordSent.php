<?
	require_once("InternalPage.php");

	class ForgotPasswordSent extends InternalPage {

		public function init() {
			$this->cssFiles["message.css"] = true;

			if ( $_SESSION['IN_PAGE'] ) {
				if ( $_SESSION['IN_PAGE_CLASSES'] ) {
					// apply context page:
					$classes = explode(",", $_SESSION['IN_PAGE_CLASSES']);
					foreach ( $classes as $class ) {
						require_once($class.".php");
					}
				}
				$this->parentPage = unserialize($_SESSION['IN_PAGE']);
				if ( is_array($_SESSION['IN_GET']) ) $_GET = array_merge($_SESSION['IN_GET'], $_GET);
				$this->parentPage->init();
			}

			// apply parentPage to this:
			if ( $this->parentPage ) $this->applyEntity($this->parentPage);

			parent::init();

			// fetch texts:
			$this->texts = Content::get(array(
				"FORGOT-PASSWORD-SENT",
				));

			$this->title = "Пароль выслан";
			$this->h1 = "Пароль выслан";
			$this->header = "";
			$this->footer = "";
			//$this->body = $this->texts['FORGOT-PASSWORD-SENT'];
			$this->body = "<p>Спасибо, Ваш пароль выслан на указанный e-mail.</p>";
		}

		public function appendNavPath() {
			if ( $this->parentPage ) {
				$this->navPath[] = new NavPathPage($this->name, $this->title, $this->url());
			}
			else {
				$this->navPath[] = new NavPathPage($this->homePage->name, $this->homePage->title, $this->homePage->url());
			}
			$np = new NavPathPage("Авторизация", NULL, PublicPage::IN);
			$this->navPath[] = $np;

			$np = new NavPathPage("Получить пароль", NULL, PublicPage::FORGOT_PASSWORD);
			$this->navPath[] = $np;

			$np = new NavPathPage("Пароль выслан", NULL, $_SERVER['REQUEST_URI']);
			$this->navPath[] = $np;
		}

		public function showBody() {
			parent::showBody();
?>
<SCRIPT LANGUAGE="JavaScript">
<!--
document.writeln("<"+"f"+"o"+"rm name='formIn' met"+"hod='get' act"+"io"+"n='<?= WebPage::maskedFormURL(PublicPage::IN) ?>' onSubmit='return Form.check(this)'>");
//-->
</SCRIPT>
<table class="form">
<tr>
	<th></th>
	<td><input type="submit" value="Продолжить &gt;" class="btn"></td>
</tr>
</table>
<SCRIPT LANGUAGE="JavaScript">
<!--
document.writeln("</"+"f"+"or"+"m>");
//-->
</SCRIPT>
<?
		}
	}
?>