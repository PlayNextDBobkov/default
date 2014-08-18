<?
	/**
		This is base class for all public pages of the site.
		Admin pages are derived from AdminPage.
	*/
	class PublicPage extends WebPage {
		const IN								= "/in.html";
		const OUT								= "/out.html";
		const FORGOT_PASSWORD					= "/forgot-password/";
		const FORGOT_PASSWORD_SENT				= "/forgot-password/sent.html";
		const REGISTRATION						= "/registration/";
		const REGISTERED						= "/registration/done.html";
		const REGISTRATION_CONFIRMATION			= "/registration/confirmation.html";
		const REGISTRATION_CONFIRMATION_NEEDED	= "/registration/confirmation-needed.html";
		const REGISTRATION_CONFIRMATION_RETRY	= "/registration/confirmation-needed.html?retry=1";

		// restricted pages:
		const MY								= "/my/";
		const POS_ITEM							= "/pos.html";
		const DEL_ITEM							= "/del.html";
		const DEL_FILE							= "/del.html";
		const TOGGLE_ITEM						= "/toggle.html";
		
		const CLIENT_LIST						= "/clients/";
		const CLIENT_EDIT						= "/clients/edit.html";

		const AD_LIST							= "/ads/";
		const AD_EDIT							= "/ads/edit.html";

		const CSS_A_COLOR			= "#ff4526";
		const CSS_A_COLOR_HOVER		= "#282828";
		const CSS_A_COLOR_VISITED	= "#ff4526";
		const CSS_H1_TEXT			= "color:#000;font:bold 1.8em Arial,sans-serif;line-height:1em;border-bottom:4px solid #000;padding:0 0 0.5em 0;";
		const CSS_BG_LITE			= "#ffc";
		const CSS_COLOR_GRAY		= "#999";

		// miscellanious consts:
		const FORM_PLS_SELECT		= "-- пожалуйста, выберите --";

		var $menuID;
		var $textKeys = array();

		protected $isHome = false;
		protected $navPath = array();

/******************************
Статические методы
******************************/

		/**
			Returns instance of Yandex Direct channel.
		*/
		public static function yandexDirect() {
			$y = new YandexDirectJSON("playnext-promo");
			$y->applicationId = '2cfe2f2184d0445e9355d421569615f8';
			$y->token = 'bd031dba936a431ca3af615194e8bc53';
			return $y;
		}

		/**
			Returns current logged in User.
		*/
		public static function user() {
			return User::current();
		}

		/**
		*	Use this method to show form errors.
		*	It returns error text if error happens.
		*/
		public static function showFormErrors() {
			if ( isset($_GET['err']) && $_GET['pt'] && $_SESSION[$_GET['pt']] ) {
				$errMsg = $_SESSION[$_GET['pt']]['errMsg'];
?>
<div class="err"><?= $errMsg ?></div>
<?
				return $errMsg;
			}
			else if ( isset($_GET['err']) ) {
?>
<div class="err">Произошла неопознанная ошибка.<br />Пожалуйста, <a href="<?= WebPage::QUERY ?>">сообщите администрации сайта</a>.<br />Приносим свои извинения.</div>
<?
			}
		}

		/**
		*	Processes page text (edited on Admin pages) just before publishing it.
		*/
		public static function preProcessText($str) {
			return $str;
		}

/******************************
Публичные методы
******************************/

		protected function initSession() {
			session_start();
		}

		protected function checkAccess() {
			parent::checkAccess();

			// detect & fetch current user - always from DB:
			$this->user = User::fetchCurrent();

			// detect city:
			/*$this->city = City::current();
			if ( !$this->city ) {
				// determine city by IP:
				$this->city = IP2City::resolve();
				$this->cityLive = IP2City::wasLive();

				City::remember($this->city);
			}*/
		}

		protected function initCSS() {
			parent::initCSS();

			$this->css["body"] = "font:0.75em Arial,Tahoma,sans-serif;color:#5c5c5c;padding:0;margin:0;background:#fff;";
			$this->css["h1"] = "margin:0 0 1em 0;".self::CSS_H1_TEXT;
			$this->css["h1 a"] = "text-decoration:none";
			$this->css["h1 a:hover"] = "text-decoration:underline;color:".self::CSS_A_COLOR;
			$this->css["h1.right"] = "float:right;border:0";
			$this->css["h2"] = "font:bold 1.4em Arial,Tahoma,sans-serif;color:#282828;margin:1.5em 0 0.5em 0;letter-spacing:-1px;line-height:1em;";
			$this->css["h2.preH1"] = "font-size:1em;margin:0;letter-spacing:0;color:#484848";
			$this->css["h3"] = "font:bold 1em Arial,Tahoma,sans-serif;color:#282828;margin:1em 0 0.5em 0;line-height:1.2em;";
			$this->css["h4"] = "font:bold 1em Arial,sans-serif;color:#484848;margin:1.5em 0 0.5em 0;line-height:1em;letter-spacing:-1px";
			$this->css["a"] = "outline: 0;color:".self::CSS_A_COLOR;
			$this->css["a:hover"] = "color:".self::CSS_A_COLOR_HOVER;
			$this->css["p"] = "margin:0 0 0.75em 0;padding:0;";
			//$this->css["*"] = "behavior:url(/css/iepngfix.htc);";

			// pseudo headers:
			$this->css[".h1"] = $this->css["h1"];
			$this->css[".h2"] = $this->css["h2"];
			$this->css[".h3"] = $this->css["h3"];
			$this->css[".h4"] = $this->css["h4"];
			$this->css[".h5"] = $this->css["h5"];
			$this->css[".b"] = "font-weight:bold";

			// special classes:
			$this->css[".g"] = "font-size:0.7em;color:#999;";
			$this->css[".g a"] = "color:#999;";
			$this->css[".a"] = $this->css["a"].";cursor:pointer;border-bottom:1px solid ".self::CSS_A_COLOR;
			$this->css[".a2"] = "color:".self::CSS_A_COLOR.";border-bottom:1px dotted ".self::CSS_A_COLOR.";cursor:pointer";
			$this->css[".a2:hover"] = "color:".CSS_A_COLOR_HOVER.";border-bottom:1px dotted ".CSS_A_COLOR_HOVER.";";
			$this->css[".increased"] = "font-size:1.4em;color:#282828;letter-spacing:-1px;line-height:1em;";
			$this->css[".clickable"] = "cursor:pointer";
			$this->css[".hidden"] = "display:none";
			//$this->css["div.video"] = "border:1px solid #d4a86e;margin:0 0 0.75em 0;padding:1px;";

			// layout:
			$this->css["div#layout"] = "position:relative;z-index:2;margin:0 auto 3em auto;width:95%;min-width:980px;max-width:1280px;padding:80px 0 3em 0";
			$this->css["div#content"] = "width:100%;";

			// header - inside layout:
			$this->css["div#header"] = "position:absolute;z-index:10;left:0;top:0;width:100%;height:72px;";
			$this->css["div#header h1"] = "position:absolute;left:134px;top:7px;margin:0;font-size:2.2em;color:#333;font-weight:bold;letter-spacing:-1px;border:0;text-transform:uppercase";

			// logo - inside header:
			$this->css["div#logo"] = "position:absolute;top:0;left:1em;width:115px;height:39px;";
			$this->css["div#logo img.logo"] = "width:115px;height:39px;border:0;display:block;";

			// menu - inside header:
			$this->css["div#menu"] = "position:absolute;top:25px;left:120px;";
			$this->css["div#menu ul.l0"] = "width:100%;position:relative;z-index:2;";
			$this->css["div#menu li.l0"] = "float:left;margin:0;position:relative;";
			$this->css["div#menu li.l0 a.l0"] = "display:block;padding:5px 15px 7px 15px;color:#000;font-size:1.4em;text-decoration:none;";
			$this->css["div#menu li.l0 div.strip"] = "display:none";
			$this->css["div#menu li.l0 a.l0.over"] = "text-decoration:underline";
			$this->css["div#menu li.l0.over"] = "box-shadow:0 0 5px #666;border-radius:5px;";
			$this->css["div#menu li.l0.withChildren.over"] = "border-radius:5px 5px 0 0;";
			$this->css["div#menu li.l0.withChildren.over div.strip"] = "position:absolute;width:100%;z-index:6;left:0;display:block;top:32px;height:10px;background:#fff";
			$this->css["div#menu li.l0.i4.withChildren.over div.strip"] = "left:auto;right:0";

			// menu l1:
			$this->css["div#menu div.children"] = "position:absolute;z-index:5;top:38px;display:none;left:0px;width:240px;";
			$this->css["div#menu li.over div.children"] = "display:block;";
			$this->css["div#menu li.i4.over div.children"] = "right:0;left:auto";

			$this->css["div#menu div.children ul"] = "position:relative; z-index:5; padding:0 0 3px 0;width:100%;background:#fff;
				box-shadow:0 0 5px #666;
				border-radius:0 5px 5px 5px;background:#fff;";
			$this->css["div#menu li.i4 div.children ul"] = "border-radius:5px 0 5px 5px;";
			$this->css["div#menu div.children ul li"] = "";
			$this->css["div#menu div.children a"] = "display:block;margin:0 6px;padding:10px 9px;color:#636363;text-decoration:none;font-size:1.2em;border-top:1px solid #ccc";
			$this->css["div#menu div.children li.over a"] = "text-decoration:underline;color:#000";
			$this->css["div#menu div.children a.first"] = "border-top:0";

			// navPath - inside content
			$this->css["div#navPath"] = "position:absolute;z-index:4;top:-21px;left:0;font-size:0.7em;font-weight:bold;color:#aaa;text-transform:lowercase;width:100%;height:18px;o_verflow:hidden";
			$this->css["div#navPath a"] = "text-decoration:none;color:#888";
			//$this->css["div#navPath a:hover"] = "text-decoration:underline;color:#282828";
			$this->css["div#navPath ul"] = "margin:0;padding:0;list-style:none";
			$this->css["div#navPath ul li"] = "margin:0 0 0 8px;padding:0;float:left;width:auto;height:18px;overflow:hidden;cursor:pointer";
			$this->css["div#navPath ul li.last"] = "display:none;padding:1px 0 0 0;cursor:default";
			$this->css["div#navPath div.l"] = "background:url('/i/13.gif') no-repeat right 0;padding:0 4px 0 0";
			$this->css["div#navPath div.r"] = "background:url('/i/12.gif') no-repeat left 0;padding:1px 2px 0 6px;height:18px;";
			$this->css["div#navPath li.first"] = "margin:0";
			$this->css["div#navPath li.first div.l"] = "background:url('/i/14.gif') no-repeat 0 0;padding:0";
			$this->css["div#navPath li.first div.r"] = "background:none;padding:0;width:18px;";
			$this->css["div#navPath li.over div.l"] = "background-position:right -18px;";
			$this->css["div#navPath li.over div.r"] = "background-position:left -18px;";
			$this->css["div#navPath li.over a"] = "color:#fff";
			
			// dummy and fade:
			$this->css["div#dummy-width"] = "position:absolute;top:0;width:100%;";
			$this->css["div#fade"] = "display:none;position:absolute;z-index:10000;top:0;left:0;width:100%;background:#000;opacity:0.75;filter:alpha(opacity=75);";
			$this->css["div#fade.visible"] = "display:block;";

			// test title:
			$this->css["div#test-title"] = "position:absolute;top:0;left:0;width:100%;font-size:0.7em;background:#fc0;color:#000;text-align:center;";

			// in-page menu
			$this->css["ul.menu"] = "padding:0.5em 0 0.5em 0;margin:0;list-style:none;font:bold 1.4em Arial,Tahoma,sans-serif;letter-spacing:-1px;";
			$this->css["ul.menu li"] = "background-image:url('/i/li.gif');background-repeat:no-repeat;margin:0 0 0.5em 0;padding:0 0 0 16px;background-position:0 11px;";
			//$this->css["ul.menu a"] = "font:1em Arial";

			// in-page small menu
			$this->css["ul.small-menu"] = "padding:1em 0 1em 0;margin:0;list-style:none;";
			$this->css["ul.small-menu li"] = "float:right;";
			$this->css["ul.small-menu a"] = "font:10px Tahoma";

			// footer - outside layout:
			$this->css["div#footer"] = "position:relative;z-index:2;width:100%;height:80px;background:url('/i/1.gif') repeat 0 0";
			$this->css["div#footer.fixed"] = "position:fixed;bottom:0;left:0;";
			$this->css["div#footerLayout"] = "position:relative;z-index:2;margin:0 auto 0 auto;width:90%;min-width:980px;max-width:1280px;width:expression(document.body.clientWidth<1078?'980px':(document.body.clientWidth>1422?'1280px':'90%'));";
			$this->css["div#copyrights"] = "text-transform:uppercase;font-size:0.83em;font-weight:bold;color:#000;padding:0 24px 0 0";
			$this->css["div#footerLayout div#matreshka"] = "position:absolute;width:56px;height:17px;right:0;top:24px;";
			$this->css["div#footerLayout div#matreshka img"] = "width:56px;height:17px;border:0";

			// feedback:
			$this->css["div#feedback"] = "height:26px;margin:0 0 0 0;font-size:1em;text-align:center;padding:1em 1em 0 1em;";
			$this->css["div#feedback span.a2"] = "color:".self::CSS_A_COLOR.";border-bottom:1px dotted ".self::CSS_A_COLOR;
			$this->css["div#feedback span.a2:hover"] = "color:".self::CSS_A_COLOR_HOVER.";border-bottom:1px dotted ".self::CSS_A_COLOR_HOVER;

			// feedback form:
			$this->css["div#feedbackForm"] = "display:none;position:absolute;top:0;left:0;z-index:10001;width:500px;padding:1em;height:320px;background:#fff;border:2px solid #adadad;border-radius:10px;box-shadow:0 0 15px #999";
			$this->css["div#feedbackForm h1"] = "margin:0 0 0.25em 0;padding:0;border:0";
			$this->css["div#feedbackForm div.close"] = "position:absolute;right:0.5em;top:0.5em;width:2em;background:#eee;text-align:center;cursor:pointer;font-size:1em;padding:0.25em 0";
			$this->css["div#feedbackForm div.close:hover"] = "background:#ed1c24;color:#fff;";
			$this->css["div#feedbackForm div.pad"] = "padding:1em";
			$this->css["div#feedbackFormMain"] = "width:460px;margin:0 auto 0 auto;";
			$this->css["div#feedbackFormLoading"] = "display:none;width:32px;padding:130px 0 0 0;margin:0 auto;";

			// feedback form:
			$this->css["div#alert"] = "display:none;position:absolute;top:0;left:0;z-index:10001;width:350px;padding:25px;height:170px;background:#fff;box-shadow:0 0 7px #777";
			$this->css["div#alert div#alertText"] = "text-align:center;font-size:1.4em;color:#000;width:350px;height:100px;border-bottom:1px solid #ccc;display:table-cell;vertical-align:middle;text-align:center;";
			$this->css["div#alert table"] = "width:350px;position:absolute;left:25px;bottom:25px;";
			$this->css["div#alert td.ok"] = "width:100%;text-align:center";
			$this->css["div#alert td.no"] = "width:50%;text-align:center";
			$this->css["div#alert.confirm td.ok"] = "width:50%;";
			$this->css["div#alert.alert td.no"] = "display:none";
			$this->css["div#alert table input"] = "width:60%;height:37px;font-size:1.1em;text-transform:uppercase";

			// disable some styles in print media:
			$this->cssPrint[".noPrint"] = "display:none";
		}

		/**
			Called in PublicPage::init() when making sub menu.
			It accepts $callingPage - reference to current page (Internal page or its derived page) where init() is called.
			Override this method to change how a page should look in sub menu or to append page children.
		*/
		protected function menuPage($callingPage) {
			$smi = new Page($this->toArray());
			$smi->url = $this->url();
			$smi->children = array();

			if ( $this->data("imageId") ) {
				$smi->image = new PageImageIcon();
				$smi->image->id = $this->data("imageId");
				$smi->image->ext = $this->data("imageExt");
			}
			else $smi->image = NULL;

			return $smi;
		}

		protected function isPureParentForNavPath() {
			return false;//$this->codeLevel()==1 ? true : false;
		}

		protected function init() {
			parent::init();

			// javascript is always required:
			$this->isJSRequired = true;
			$this->jsFiles["Browser.js"] = true;
			$this->jsFiles["Event.js"] = true;
			$this->jsFiles["Screen.js"] = true;
			$this->jsFiles["CSS.js"] = true;
			$this->jsFiles["Ajax.js"] = true;
			$this->jsFiles["HTML.js"] = true;
			$this->jsFiles["Calendar.js"] = true;
			$this->jsFiles["Uploader.js"] = true;
			$this->jsFiles["Form.js"] = true;
			$this->jsFiles["FX.js"] = true;
			$this->jsFiles["PublicPage.js"] = true;

			$this->cssFiles["Form.css"] = true;
			$this->cssFiles["Calendar.css"] = true;
			$this->cssFiles["Uploader.css"] = true;

			$this->fetchMainMenu();

			// append nav path - after content is fetched:
			$this->appendNavPath();
		}

		protected function appendNavPath() {
			/*if ( $this->code ) {
				// add parent pages to navPath:
				$parentCodes = array();
				for ( $i = 0; $i<strlen($this->code)/Page::LEVEL_WIDTH-1; $i++ ) {
					$parentCodes[] = $this->codeParentAtLevel($i);
				}
				$topParents = PageEntity::fetchAndTransform("SELECT p.*"
					.", (SELECT pad.data FROM ".E5::TABLE_PAGE_ACTION_DATA." pad, ".E5::TABLE_PAGE_ACTION_DATA_PAGE." padp WHERE pad.id=padp.dataId AND padp.pageId=p.id) AS actionData"
					." FROM page p"
					." WHERE p.code IN ('".implode("','",$parentCodes)."') ORDER BY p.code");
				foreach ( $topParents as $page ) {
					if ( !$page->isActive ) continue;
					$this->navPath[] = new NavPathPage($page->name, $page->title, !$page->isPureParentForNavPath()?$page->url():NULL);
					///$this->navPath[] = new NavPathPage($page->name, $page->title, $page->codeLevel()!=1&&!$page->isPureParent?$page->url():NULL);
				}
			}
			else {
				// add home page to nav path:
				$np = new NavPathPage($this->homePage->name, $this->homePage->title, $this->homePage->url());
				$this->navPath[] = $np;
			}
			if ( $this->name ) {
				$np = new NavPathPage($this->name, $this->title, $this->url());
				$this->navPath[] = $np;
			}*/
		}

		protected function showBeforeBody() {
?>
<div id="layout">
<?
			parent::showBeforeBody();	// start of content
		}

		protected function showBodyH1() {
			if ( $this->h2 ) {
?>
<h2 class="preH1"><?= $this->h2 ?></h2>
<?
			}
			if ( $this->h1Right ) {
?>
<h1 class="right"><?= $this->h1Right ?></h1>
<?
			}
			if ( $this->h1 ) {
?>
<h1><?= $this->h1 ?></h1>
<?
			}
		}

		protected function showBodyText() {
			$hasAutoImages = $this->hasAutoImages && is_array($this->images) && sizeof($this->images)>0;
			if ( $this->body || $hasAutoImages ) {
?>
<div class="pageBody">
<?
				if ( $hasAutoImages ) {
					$str = "";
					$str .= "<ul class='pageImages'>\n";

					$i = 0;
					foreach ( $this->images as $image ) {
						$i++;
						$image->setURLWidth(PageImage::WIDTH_AUTO);
						$str .= "<li".($i==1?" class='first'":"")."><img viewIndex=\"".($i-1)."\" src='".$image->url()."'".($image->name?" alt='".p($image->name)."'":"")."></li>\n";
					}
					$str .= "</ul>\n";
					$this->body = $str . $this->body;
				}

				if ( $this->body ) {
					//$this->body = $this->applyImageViewer($this->body);
					//$this->body = $this->applyVideos($this->body);
?>
<?= self::preProcessText($this->body) ?>
<?
				}
?>
</div>
<?
			}
		}

		protected function showBody() {
			$this->showBodyH1();
			$this->showBodyText();
		}

		protected function showSubMenuLevel($items, $level=0, $maxLevel=99, $forceChildren=false, $forceLI=false) {
			if ( !sizeof($items) ) return;
			if ( $level > $maxLevel ) return;
?>
<ul>
<?
			$i = 0;
			foreach ( $items as $page ) {

				$isSelected = $this->id == $page->id;
				$isChild = false;
				$isOpen = false;
				if ( $page->code ) {
					$isChild = $page->codeLevel() > 2 ? true : false;
					$isSelected = $isSelected || $this->codeEquals($page->code);
					//$isOpen = ($page->codeLevel() > 1 && is_array($page->children) && sizeof($page->children) && !$page->isCollapsed ) || ($isSelected && !$isChild) || $page->isCodeTreeParentOf($this->code);
					$isOpen = ($isSelected && !$isChild) || $page->isCodeTreeParentOf($this->code);
				}
				if ( $isSelected ) $selectedPage = $page;
				if ( $isOpen ) $openPage = $page;
				$i++;

				$classes = array();

				if ( $i == 1 ) $classes[] = "first";
				if ( $isSelected ) $classes[] = "sel";
				if ( $isOpen ) $classes[] = "open";
				if ( sizeof($page->children) ) $classes[] = "withChildren";
				$classes[] = "l".$level;
				$classes[] = "i".($i-1);

				///if ( $i == 1 ) $classes[] = "over";

				if ( preg_match("/^NEW\!.+$/i", $page->name) ) {
					$classes[] = "new";
					$name = trim(preg_replace("/^NEW\!(.+)$/i", "$1", $page->name));
				}
				else if ( preg_match("/^HOT\!.+$/i", $page->name) ) {
					$classes[] = "hot";
					$name = trim(preg_replace("/^HOT\!(.+)$/i", "$1", $page->name));
				}
				else $name = $page->name;

				//if ( $page->codeLevel() == 1 ) $zIndex = sizeof($items) - $i + 10;
				//else
				$zIndex = NULL;

				if ( $page->css ) $classes[] = $page->css;
?>
<li id="menu_<?= $page->id ?>"<?= (sizeof($classes)?" class='".implode(" ", $classes)."'":"") ?> onMouseOver="CSS.a(this,'over')" onMouseOut="CSS.r(this,'over')"<?= $zIndex?" style='z-index:".$zIndex."'":"" ?>>
<?
				if ( $page->image ) {
?>
<div class="i" style="width:<?= $page->image->width ?>px;height:<?= $page->image->height ?>px"><img src="<?= $page->image->url() ?>"></div>
<?
				}
?>
<div class="name<?= (sizeof($classes)?" ".implode(" ", $classes):"") ?>" onMouseOver="CSS.a(this,'over')" onMouseOut="CSS.r(this,'over')"<?
				if ( !$page->blank ) {
?>
onClick="<?= $page->isPureParent&&sizeof($page->children)&&$page->codeLevel()>1?"toggleMenu('".$page->id."')":"self.location.href='".$page->url()."'" ?>"<?
				}
?>>
<div class="inner"><?
				//if ( $page->codeLevel()>1 ) print "&mdash;&nbsp;";
				if  ( $page->isPureParent && sizeof($page->children) && $page->codeLevel()>1 ) $url = "javascript:toggleMenu('".$page->id."')";
				else $url = $page->url();
?>
<a<?= (sizeof($classes)?" class='".implode(" ", $classes)."'":"") ?> href="<?= $url ?>"<?= $page->blank?" target='_blank'":"" ?> title="<?= $page->title ?>"><?= $name ?></a></div></div>
<?
				if ( $page->icon ) {
?>
<div class="icon"><?= $page->icon ?></div>
<?
				}
?>
<?
				//if ( ($isOpen || $isSelected ) && is_array($page->children) && sizeof($page->children) && $level < $maxLevel ) {
				if ( $level < $maxLevel && is_array($page->children) && sizeof($page->children) && ( $isOpen || $forceChildren ) ) {
?>
<div class="children" id="subMenu_<?= $page->id ?>">
<?
					//$page->showBeforeSubMenu();
					$this->showSubMenuLevel($page->children, $level+1, $maxLevel, $forceChildren);
?>
</div>
<?
				}
?>
</li>
<?
			}
?>
</ul>
<?
		}

		protected function showAfterBody() {

			$this->showNavPath();

			parent::showAfterBody();	// end of content

			$this->showAfterBodyEnd();
?>
<div class="clear"></div>

<div id="header">
<?
			$this->showInHeader();
?>
</div><? // header ?>
<?
			$this->showBeforeLayoutEnd();
?>
</div><? //layout ?>
<?
			$this->showAfterLayout();

			$this->showFooter();
		}

		protected function showFooter() {
			return;
?>
<div id="footer">
<div id="footerLayout">

<div id="feedback">
Нужна помощь? Нашли ошибку? <span class="a2" onClick="PublicPage.showFeedback()">Сообщите нам!</span>
</div><?//feedback ?>

</div><? //footerLayout ?>
</div><? //footer ?>
<?
		}

		protected function showAfterBodyEnd() {
		}

		protected function showAfterLayout() {
			return;
?>
<div id="fade"></div>
<div id="dummy-width"></div>

<div id="alert">
<form>
<div id="alertText"></div>
<table class="form">
<tr>
	<td class="ok"><input type="button" value="Ок" class="btn2 ok" onClick="PP.closeAlert(1)"></td>
	<td class="no"><input type="button" value="Отмена" class="btn2 no" onClick="PP.closeAlert(0)"></td>
</tr>
</table>
</form>
</div><?//alert ?>

<div id="feedbackForm">
<div class='close' onClick="PublicPage.closeFeedbackForm()">X</div>
<div class="pad">
<div id="feedbackFormLoading"><img src="/i/busy.gif"></div>
<div id="feedbackFormMain">
<SCRIPT LANGUAGE="JavaScript">
<!--
document.writeln("<"+"f"+"o"+"rm name='formFeedback' id='formFeedback' onSubmit='return PublicPage.submitFeedback(this)'>");
//-->
</SCRIPT>
<input type="hidden" name="url" value="http://<?= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] ?>">
<?
		if ( $this->name ) $h1 = $this->name;
		else if ( $this->h1 ) $h1 = $this->h1;
		else $h1 = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
?>
<input type="hidden" name="h1" value="<?= p($h1) ?>">
<h1>Обратная связь с CarlCar.ru</h1>
<table class="form">
<tr>
	<th class="r">Ваш вопрос, cообщение<br />или комментарий<br />об этой странице<sup>*</sup></th>
	<td><div class="i" style="height:100px"><textarea validation="Ваш вопрос, cообщение или комментарий об этой странице" hint="Ваш вопрос, cообщение или комментарий об этой странице*" name="msg" style="height:100px"></textarea></div></td>
</tr>
<tr>
	<th>Ваше имя</th>
	<td><div class="i"><input hint="Ваше имя" name="name" maxlength="64"></div></td>
</tr>
<tr>
	<th>Контактный E-Mail</th>
	<td><div class="i"><input hint="Контактный E-Mail" name="email" maxlength="128"></div></td>
</tr>
<tr>
	<th>&nbsp;</th>
	<td><input type="submit" value="Отправить" class="btn"></td>
</tr>
</table>
<SCRIPT LANGUAGE="JavaScript">
<!--
document.writeln("</"+"f"+"or"+"m>");
//-->
</SCRIPT>
</div><?//pad ?>
</div><?//feedbackFormMain ?>
</div><?//feedbackForm ?>
<?
		}

		protected function showNavPath() {
			// show nav path
			if  ( sizeof($this->navPath) ) {
?>
<div id="navPath">
<ul>
<?
				//print " / ";
				$i = 0;
				foreach ( $this->navPath as $navPage ) {
					$i++;
					
					$css = array();
					
					if ( $i == 1 ) {
						$css[] = "first";
						$name = "&nbsp;";
					}
					else $name = $navPage->name;
					
					if ( $navPage->url && $i<sizeof($this->navPath) ) {
?>
<li<?= sizeof($css)?" class='".implode(" ", $css)."'":"" ?> onClick="self.location.href='<?= $navPage->url ?>'" onMouseOver="CSS.addClass(this,'over')" onMouseOut="CSS.removeClass(this,'over')"><div class="l"><div class="r"><a href="<?= $navPage->url ?>" title="<?= p($navPage->title) ?>"><?= strMaxWords($name, 5, "...") ?></a></div></div></li>
<?
					}
					else {
?>
<li class="last"><?= strMaxWords($name, 5, "...") ?></li>
<?
					}
					//if ( $i < sizeof($this->navPath) ) print " / ";
				}
?>
</ul>
</div>
<?
			}
		}

		protected function showBeforeLayoutEnd() {
		}

		protected function showInHeader() {
?>
<div id="logo">
<?
			if ( $this->isHome ) {
?>
<img class="logo" src="/i/logo.png" title="<?= p($this->title) ?>">
<?
			}
			else {
?>
<a href="/" title="<?= p($this->homePage->title) ?>"><img class="logo" src="/i/logo.png"></a>
<?
			}
?>
</div> <h1>Отчётность</h1><?// logo ?>
<?
			//$this->showMenu();
			//$this->showUserMenu();
		}

		protected function showMenu() {
?>
<div id="menu">
<?
			$this->showSubMenuLevel($this->menu, 0, 2, true);
?>
</div><? //menu ?>
<?
		}

		protected function showUserMenu() {
?>
<div id="userMenu">
<ul>
	<?/*<li class="in"><span class="a2" id="userInBtn">вход</span></li>*/?>
	<li class="in"><a href="<?= PublicPage::IN ?>">вход</a></li>
	<li class="reg"><a href="<?= PublicPage::REGISTRATION ?>">регистрация</a></li>
</ul>
</div><? //userMenu ?>
<?
		}

		protected function fetchMainMenu($maxLevel=2) {
			// fetch menu (one or several levels) with page action data:
			$sql = "SELECT p.*"
			//.", t.*"
			.", (SELECT COUNT(*) FROM page p2 WHERE code LIKE CONCAT(p.code, '".str_repeat("_", $this->levelWidth()*2)."')) AS countChildren"
			.", (SELECT pad.data FROM ".E7::TABLE_PAGE_ACTION_DATA." pad, ".E7::TABLE_PAGE_ACTION_DATA_PAGE." padp WHERE pad.id=padp.dataId AND padp.pageId=p.id) AS actionData"
			." FROM page p"
			//." LEFT JOIN (SELECT pi.parentId AS imageParentId, pi.id AS imageId, pi.ext AS imageExt, pi.pos AS imagePos, pi.width AS imageWidth, pi.height AS imageHeight FROM page_image_icon pi WHERE pi.pos=1) AS t ON p.id=t.imageParentId"
			//." WHERE p.code LIKE '".$this->codeParentAtLevel(1)."%'"
			." WHERE LENGTH(p.code)>=".($this->levelWidth()*2)	// from 2nd level
			." AND LENGTH(p.code)<=".($this->levelWidth()*$maxLevel)	// to some level
			//." GROUP by p.id"
			." ORDER BY p.code";
			//die($sql);
			$this->menuRaw = Page::fetchAndTransform($sql);

			// form Menu and exclude inactive pages:
			$allMenuItems = array();
			if ( !is_array($this->menu) ) $this->menu = array();
			$pIsActive = Entity::$pIsActive;

			foreach ( $this->menuRaw as $id => $item ) {
				// exclude inactive:
				if ( !$item->$pIsActive ) continue;

				// take parent code:
				$pCode = $item->codeParent();

				// if this item has parent in the sub menu - save it as a child, else - skip:
				foreach ( $allMenuItems as $pc => $p ) {
					if ( $p->codeEquals($pCode) ) {
						// this sub mneu item has a parent in menu - $p is a parent of this item:
						$p->children[$item->code] = $item->menuPage($this);
						$allMenuItems[$item->code] = $p->children[$item->code];
						continue 2;
					}
				}

				if ( $item->codeLevel()!=1 ) continue;

				// this is a top level menu item - append menu:
				$this->menu[$item->code] = $item->menuPage($this);
				$allMenuItems[$item->code] = $this->menu[$item->code];
			}
		}
	}
?>
