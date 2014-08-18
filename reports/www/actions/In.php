<?
	require_once("InternalPage.php");

	class In extends InternalPage {

		protected $skipSignInForm = true;

		protected function checkAccess() {
			parent::checkAccess();

			if ( $this->user ) go(PublicPage::MY);
		}

		public function url() {
			return PublicPage::IN;
		}

		public function doPost() {
			// trim params:
			$_POST = t($_POST);
			
			// remember in session:
			if ( !$_POST['pt'] ) $_POST['pt'] = WebPage::token();
			$_SESSION[$_POST['pt']] = $_POST;

			// err url:
			$errURL = urlRemoveParams($_SERVER['REQUEST_URI'], "err");
			$errURL = urlRemoveAnchor($errURL);
			$errURL = urlAppendParam($errURL, "pt", $_POST['pt']);
			$errURL = urlAppendParam($errURL, "err")."=";

			$user = User::fetchByEmail($_POST['email']);
			if ( !$user ) go($errURL."auth_email");
			if ( $user->password != $_POST['password'] ) go($errURL."auth_password");
			if ( !$user->isActive ) go($errURL."nonActive");
			//if ( !$user->isConfirmed ) go($errURL."nonConfirmed");

			// log in:
			User::login($user, $_POST['rememberMe']?1:0);
			
			if ( $_POST['redirect'] ) go($_POST['redirect']);
			if ( $_SESSION['IN_REDIRECT'] ) go($_SESSION['IN_REDIRECT']);
			go(PublicPage::MY);
		}

		public function initCSS() {
			parent::initCSS();

			//$this->css["h1"] ="font:2em 'PT Sans', sans-serif;color:#a9a9a9;margin:0 0 1em 0;padding:0 0 0.35em 0;border-bottom:1px solid #c5c5c5";
			$this->alterCSS("h1", "font-weight:normal;color:#a9a9a9;border-bottom:1px solid #c5c5c5");

			$this->css["form#formIn"] = "margin:2em 0 0 0";
			$this->css["form#formIn div.rememberMe"] = "width:48%;float:left;font-style:italic";
			$this->css["form#formIn div.forgetReg"] = "width:50%;float:right;text-align:right;font-style:italic;height:5em";
			$this->css["form#formIn div.forgetReg div.forget"] = "margin:0 0 0.75em 0";
		}

		public function init() {
			if ( $_GET['n'] ) {
				unset($_SESSION['IN_PAGE']);
				unset($_SESSION['IN_REDIRECT']);
			}

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
				///$_SESSION['FORGOT_PASSWORD_PAGE'] = $_SESSION['IN_PAGE'];
				$_SESSION['REGISTRATION_PAGE'] = $_SESSION['IN_PAGE'];
				$_SESSION['REGISTRATION_PAGE_CLASSES'] = $_SESSION['IN_PAGE_CLASSES'];
				$_SESSION['REGISTRATION_GET'] = $_SESSION['IN_GET'];
			}
			
			// pass redirect to registration:
			$_SESSION['REGISTRATION_REDIRECT'] = $_SESSION['IN_REDIRECT'];

			// apply parentPage to this:
			if ( $this->parentPage ) $this->applyEntity($this->parentPage);

			parent::init();

			// include css & js:
			$this->cssFiles["message.css"] = true;
			//$this->cssFiles["http://fonts.googleapis.com/css?family=PT+Sans&subset=latin,cyrillic"] = true;

			$this->title = "Авторизация пользователя";
			$this->h1 = "Авторизация пользователя";
			if ( $_SESSION['IN_TEXT'] ) $this->body = $_SESSION['IN_TEXT'];
			else $this->body = "<p>Зарегистрируйтесь или войдите, чтобы воспользоваться сервисом CarlCar. При возникновении вопросов свяжитесь со Службой поддержки: <a href='mailto:support@carlcar.ru'>support@carlcar.ru</a></p>";
			$this->footer = "";
		}

		public function appendNavPath() {
			parent::appendNavPath();

			$np = new NavPathPage("Авторизация", NULL, $_SERVER['REQUEST_URI']);
			$this->navPath[] = $np;
		}

		public function showBody() {
			parent::showBody();
			
			if ( $_GET['err'] == "email" ) $err = "Пожалуйста, введите Ваш e-mail.";
			else if ( $_GET['err'] == "password" ) $err = "Пожалуйста, введите Ваш пароль.";
			else if ( $_GET['err'] == "auth_email" ) $err = "Данный e-mail не зарегистрирован на нашем сайте.<br />Если Вы новый пользователь - пожалуйста, <a href='".PublicPage::REGISTRATION."'>пройдите регистрацию</a>.";
			else if ( $_GET['err'] == "auth_password" ) $err = "Введенный пароль неверен.<br />Пожалуйста, проверьте регистр, язык ввода и попробуйте снова.";
			else if ( $_GET['err'] == "nonActive" ) $err = "Данная учетная запись приостановлена.<br />Пожалуйста, свяжитесь с администратором.";
			else if ( $_GET['err'] == "nonConfirmed" ) $err = "<p>Данный e-mail не подтвержден.<br />Вам необходимо подтвердить регистрацию, проследовав по ссылке, которую мы высылали Вам на почту.</p>Если Вы не получили данное письмо &mdash;<br />попробуйте <a href='".PublicPage::REGISTRATION_CONFIRMATION_NEEDED."?pt=".$_GET['pt']."'>запросить ссылку повторно</a>.";

			if ( $err ) {
				$form = $_SESSION[$_GET['pt']];
?>
<div class="err"><?= $err ?></div>
<?
			}
?>
<SCRIPT LANGUAGE="JavaScript">
<!--
document.writeln("<"+"f"+"o"+"rm met"+"hod='po"+"st' id='formIn' act"+"io"+"n='<?= WebPage::maskedFormURL(PublicPage::IN) ?>' onSubmit='return Form.check(this)'>");
//-->
</SCRIPT>
<input type="hidden" name="pt" value="<?= p($_GET['pt']) ?>">
<input type="hidden" name="redirect" value="<?= p($_GET['redirect']) ?>">
<table class="form">
<tr<?= $_GET['err']=="auth_email"?" class='err'":"" ?>>
	<th class="r">E-mail<span class='star'>*</span></th>
	<td><div class="i"><input name="email" value="<?= p($form["email"]) ?>" maxlength="64" hint="E-mail*" validation="E-mail"></div></td>
</tr>
<tr<?= $_GET['err']=="auth_password"?" class='err'":"" ?>>
	<th class="r">Пароль<span class='star'>*</span></th>
	<td><div class="i"><input type="password" name="password" value="<?= p($form["password"]) ?>" maxlength="32" hint="Пароль*" validation="Пароль"></div></td>
</tr>
<tr>
	<th></th>
	<td>
<div class="rememberMe"><input type="checkbox" id="rememberMe" name="rememberMe" title="Поставьте галочку, чтобы система не требовала авторизацию при активном посещении сайта" checked> <label for="rememberMe" title="Поставьте галочку, чтобы система не требовала авторизацию при активном посещении сайта">запомнить меня</label></div>

<div class="forgetReg">
<div class="forget"><a href="<?= PublicPage::FORGOT_PASSWORD ?>">Забыли пароль?</a></div>
<div class="reg"><a href="<?= PublicPage::REGISTRATION ?>">Регистрация</a></div>
</div>
	</td>
</tr>
<tr class="continue">
	<th></th>
	<td>
<input type="submit" value="Войти" class="btn2">
	</td>
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