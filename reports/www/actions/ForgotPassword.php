<?
	require_once("InternalPage.php");

	class ForgotPassword extends InternalPage {

		protected $skipSignInForm = true;
		protected $skipHotEvents = true;

		public function doPost() {
			// trim params:
			$_POST = t($_POST);
			
			// remember in session:
			if ( !$_POST['pt'] ) $_POST['pt'] = WebPage::token();
			$_SESSION[$_POST['pt']] = $_POST;

			// fetch user
			$u = User::fetchByEmail($_POST['email']);
			if ( !$u ) go(PublicPage::FORGOT_PASSWORD."?err=email&pt=".$_POST['pt']);

			$u->sendRegistration();

			go(PublicPage::FORGOT_PASSWORD_SENT);
		}

		public function init() {
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
			$this->textsForgotPassword = Content::get(array(
				"FORGOT-PASSWORD",
				));

			// include css & js:
			$this->cssFiles["form.css"] = true;
			$this->cssFiles["message.css"] = true;
			$this->jsFiles["form.js"] = true;

			$this->title = "Получить пароль";
			$this->h1 = "Получить пароль";
			//$this->body = $this->textsForgotPassword['FORGOT-PASSWORD'];
			$this->body = "<p>Пожалуйста, введите e-mail, который Вы указывали при регистрации. Пароль будет выслан на этот e-mail.</p>";
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

			$np = new NavPathPage("Получить пароль", NULL, $_SERVER['REQUEST_URI']);
			$this->navPath[] = $np;
		}

		public function showBody() {
			parent::showBody();

			if ( $_GET['err'] == "email" ) $err = "Введенный e-mail не зарегистрирован на нашем сайте.<br />Проверьте и повторите ввод.";
			if ( $err ) {
				$form = $_SESSION[$_GET['pt']];
?>
<div class="err"><?= $err ?></div>
<?
			}
?>
<SCRIPT LANGUAGE="JavaScript">
<!--
document.writeln("<"+"f"+"o"+"rm name='formIn' met"+"hod='po"+"st' act"+"io"+"n='<?= WebPage::maskedFormURL(PublicPage::FORGOT_PASSWORD) ?>' onSubmit='return Form.check(this)'>");
//-->
</SCRIPT>
<input type="hidden" name="pt" value="<?= p($_GET['pt']) ?>">
<table class="form">
<tr<?= $_GET['err'] == "email" ? " class='err'":"" ?>>
	<th class="r">E-mail<sup>*</sup></td>
	<td><div class="i"><input name="email" value="<?= p($form['email']) ?>" validation='E-mail' hint="E-mail*"></div></td>
</tr>
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