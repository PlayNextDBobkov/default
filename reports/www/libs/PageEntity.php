<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/

	class PageEntity extends CodeEntity {
		const LEVEL_WIDTH		= 4;	// code: maximum 4 chars (9999 items) per level
		const MAX_LEVEL			= 15;	// code: [0-15] 16 levels x 4 = 64 chars needed to store maximum level code

		const TABLE_PAGE					= "page";
		const TABLE_PAGE_ACTION_DATA		= "page_action_data";
		const TABLE_PAGE_ACTION_DATA_PAGE	= "page_action_data_page";

		// DB fields:
		// var $code is in CodeEntity
		var $id;
		var $action;
		var $name;
		var $title;
		var $description;
		var $keywords;
		var $h1;
		var $body;
		var $url;	// should redirect to another URL
		var $mask;	// URL mask to be fetched by
		var $hasAutoImages;
		var $isPureParent;	// should not be clickable in menu
		var $isBlank;	// should open as a _blank window
		var $isNotInMenu;	// should not be in navigation menu
		var $isActive;
		var $createdOn;
		var $updatedOn;

		// non-DB fields:
		protected $json;
		protected $css = array();
		protected $cssScreen = array();
		protected $cssPrint = array();
		protected $cssFiles = array();
		protected $jsFiles = array();
		protected $vbFiles = array();
		protected $isJSRequired = false;
		protected $heads = array();

		// html doctype control:
		protected $isHTML5 = true;
		protected $isStrictHTML = false;

		public function PageEntity($a=NULL) {
			parent::__construct($a);

			// explode cssFiles from DB:
			if ( $this->cssFiles && !is_array($this->cssFiles) ) {
				$this->cssFiles = array_merge(array(1), t(preg_split("/[,; \n]/", $this->cssFiles)));
				$this->cssFiles = array_flip($this->cssFiles);
				array_shift($this->cssFiles);
			}
		}


		/**
			Overrided to always use table `page`:
		*/
		public function tableName($forSave = false) {
			return self::TABLE_PAGE;
		}

		/**
			Returns URL for this page.
		*/
		public function url() {
			if ( $this->url ) return $this->url;

			// we try to use english names:
			if ( $this->name_en ) $name = $this->name_en;
			else $name = $this->name;
			return "/".String::url(strip_tags($name)).".".$this->id.".html";
		}

		public function navPathPage() {
			return new NavPathPage($this->name, $this->title, $this->url());
		}

		/**
			Fetches a page by its action and transforms it to its class (page.action).
		*/
		public static function fetchOneActiveByAction($entityName) {
			$action = $entityName;
			$code = "\$object = new $entityName();";
			eval($code);
			$object = DB::fetchOne($entityName, "SELECT p.*"
				.", (SELECT data FROM ".self::TABLE_PAGE_ACTION_DATA." pad, ".self::TABLE_PAGE_ACTION_DATA_PAGE." padp WHERE pad.id=padp.dataId AND padp.pageId=p.id) AS actionData"
				." FROM ".$object->tableName()." p WHERE p.action='".$action."' AND p.isActive");
			if ( !$object ) return NULL;

			// transform to action class:
			//require_once(E7::PATH_ACT."/".$data['action'].".php");
			//$code = "\$object = new ".$data['action']."();";
			//eval($code);

			//$object->applyArray($data);
			return $object;
		}

		/**
			Fetches a page by its action and transforms it to its class (page.action).
		*/
		public static function fetchActiveByActionAndData($entityName, $data) {
			$action = $entityName;
			require_once(E7::PATH_ACT."/".$entityName.".php");
			$code = "\$object = new $entityName();";
			eval($code);
			$object = DB::fetchOne($entityName, "SELECT p.*"
				.", pad.data AS actionData"
				." FROM ".$object->tableName()." p"
				.", ".E7::TABLE_PAGE_ACTION_DATA." pad, ".E7::TABLE_PAGE_ACTION_DATA_PAGE." padp"
				." WHERE p.action='".$action."'"
				." AND pad.id=padp.dataId AND padp.pageId=p.id"
				." AND pad.data='".s($data, 1)."'"
				." AND p.isActive");
			if ( !$object ) return NULL;

			return $object;
		}

		/**
			Fetches a single record from DB using PK and converts it to page.action object.
			Checks if page.isActive is true.
		*/
		public static function fetchActiveById($pkValues) {
			$code = "\$object = new ".E7::PAGE_CLASS."();";
			eval($code);

			// Get primary key property(s):
			$pks = $object->primaryKey();

			// convert pkValues to array if needed:
			if ( !is_array($pkValues) ) $pkValues = explode(Entity::ID_DELIMITER,$pkValues);

			// make SQL for PK:
			$wherePKS = array();
			$i = 0;
			foreach ( $pks as $key ) {
				$wherePKS[] = "p.`".$key."`='".s($pkValues[$i++], 1)."'";
			}

			//print("SELECT * FROM ".$object->tableName()." WHERE ".implode(" AND ", $wherePKS).LF);
			$object = DB::fetchOne(E7::PAGE_CLASS, "SELECT p.*"
				.", (SELECT data FROM ".E7::TABLE_PAGE_ACTION_DATA." pad, ".E7::TABLE_PAGE_ACTION_DATA_PAGE." padp WHERE pad.id=padp.dataId AND padp.pageId=p.id) AS actionData"
				." FROM ".$object->tableName()." p WHERE ".implode(" AND ", $wherePKS)." AND p.isActive");
			if ( $object->action ) {
				require_once($object->action.".php");
				$code = "\$page = new ".$object->action."();";
				eval($code);
				$page->applyEntity($object);
				return $page;
			}
			return $object;
		}

		/**
			Fetches PageEntity objects from DB and transforms them into classes sepcified in page.action.
		*/
		public static function fetchAndTransform($sql) {
			$r = DB::q($sql);
			$items = array();
			while ( $data = DB::a($r) ) {
				if ( !$data['action'] ) continue;
				require_once(E7::PATH_ACTIONS.$data['action'].".php");
				eval("\$item = new ".$data['action']."(\$data);");
				$items[$item->code] = $item;
			}
			return $items;
		}

		/**
			Fetches a PageEntity object from DB and transforms it into class specified in page.action.
		*/
		public static function fetchOneAndTransform($sql) {
			$r = DB::q($sql);
			if ( $data = DB::a($r) ) {
				if ( !$data['action'] ) return NULL;
				require_once(E7::PATH_ACT.$data['action'].".php");
				eval("\$item = new ".$data['action']."(\$data);");
				return $item;
			}
			return NULL;
		}

		/**
			Initializes session.
			By default session is NOT started!
			Override this if session is needed.
		*/
		protected function initSession() {
			// This can be used in a standard overrider:
			//session_start();
		}

		/**
			Return specified CSS definition.
		*/
		public function css($key) {
			return $this->css[$key];
		}

		/**
			Updates page CSS - merges passed array with existing CSS.
		*/
		public function submitCSS($css) {
			$this->css = array_merge($this->css, $css);
		}

		/**
			Renders CSS output.
			Session is NOT inited by default!
		*/
		public function showCSS() {
			// init session:
			$this->initSession();

			// check access:
			$this->checkAccess();

			$file = realpath(E7::PATH_ACTIONS."/".get_class($this).".php");
			$lastModified = filemtime($file);

			// important! this has to be in quotes:
			$etag = "\"".md5($lastModified)."\"";

			$headers = getAllHeaders();

			// log headers:
			/*$f = fopen(realpath(E7::PATH)."/htdocs/log.txt", "a") or die("!!!");
			fwrite($f, str_repeat("-", 40)."\n");
			foreach ( $headers as $key=>$h ) {
				fwrite($f, $key.": ".$h."\n");
			}
			fclose($f);*/

			// check non-changed request:
			if ( $etag == $headers["If-None-Match"] ) {
				// return non-changed!
				header("HTTP/1.1 304 Not Modified");
				header("ETag: ".$etag);
				exit();
			}

			// output CSS:

			// send content:
			header("Content-type: text/css");
			//header("Content-Length: ".$image->length);
			header("Last-Modified: ".gmdate("D, j M Y G:i:s T", $lastModified) );
			header("ETag: ".$etag);
			header("Accept-Ranges: bytes");

			$this->initCSS();
			print "/*last-modified: ".gmdate("D, j M Y G:i:s T", $lastModified)." (".$file.")*/\n";
			$cssScreen = array_merge($this->css, $this->cssScreen);
			if ( sizeof($cssScreen) ) {
				print "@media screen {\n";
				foreach ( $cssScreen as $key => $value ) {
					print $key."{".$value."}\n";
				}
				print "}\n";
			}
			$cssPrint = array_merge($this->css, $this->cssPrint);
			if ( sizeof($cssPrint) ) {
				print "@media print {\n";
				foreach ( $cssPrint as $key => $value ) {
					print $key."{".$value."}\n";
				}
				print "}\n";
			}
		}

		/**
			Renders JS output.
			Session is NOT inited by default!
		*/
		public function showJS() {
			$file = realpath(E7::PATH_ACT."/".get_class($this).".php");
			$lastModified = filemtime($file);

			// important! this has to be in quotes:
			$etag = "\"".md5($lastModified)."\"";
			$headers = getAllHeaders();

			// log headers:
			/*$f = fopen(realpath(E7::PATH)."/htdocs/log.txt", "a") or die("!!!");
			fwrite($f, str_repeat("-", 40)."\n");
			foreach ( $headers as $key=>$h ) {
				fwrite($f, $key.": ".$h."\n");
			}
			fclose($f);*/

			// check non-changed request:
			if ( $etag == $headers["If-None-Match"] ) {
				// return non-changed!
				header("HTTP/1.1 304 Not Modified");
				header("ETag: ".$etag);
				exit();
			}

			// output JS:

			// send content:
			header("Content-type: application/x-javascript; charset=UTF8");
			//header("Content-Length: ".$image->length);
			header("Last-Modified: ".gmdate("D, j M Y G:i:s T", $lastModified) );
			header("ETag: ".$etag);
			header("Accept-Ranges: bytes");

			$this->initJS();
			//print "/*last-modified: ".gmdate("D, j M Y G:i:s T", $lastModified)." (".$file.")*/\n";
			$this->showBodyJS();
		}

		/**
			Creates JS output.
			Override this to provide specific JS output.
		*/
		protected function showBodyJS() {
			// do nothing by default
		}

		/**
			Renders XML output.
			Session is NOT inited by default!
		*/
		public function showXML() {
			if ( !$this->lastModified ) {
				$file = realpath(E7::PATH_ACT."/".get_class($this).".php");
				$lastModified = filemtime($file);
			}
			else $lastModified = $this->lastModified;

			// important! this has to be in quotes:
			$etag = "\"".md5($lastModified)."\"";

			$headers = getAllHeaders();

			// check non-changed request:
			if ( $etag == $headers["If-None-Match"] ) {
				// return non-changed!
				header("HTTP/1.1 304 Not Modified");
				header("ETag: ".$etag);
				exit();
			}

			// send content:
			header("Content-type: text/xml; charset=UTF8");
			//header("Content-Length: ".$image->length);
			header("Last-Modified: ".gmdate("D, j M Y G:i:s T", $lastModified) );
			header("ETag: ".$etag);
			header("Accept-Ranges: bytes");

			$this->initXML();
			print $this->xmlDoc->saveXML();
		}

		/**
			Renders JSON output.
			Session is NOT inited by default!
		*/
		public function showJSON() {
			// init session:
			$this->initSession();

			// check access:
			$this->checkAccess();

			// send content:
			header("Content-type: text/plain");
			//header("Content-Length: ".$image->length);
			//header("Last-Modified: ".gmdate("D, j M Y G:i:s T", $lastModified) );
			//header("ETag: ".$etag);
			//header("Accept-Ranges: bytes");

			$this->initJSON();
			print json_encode($this->json);
		}

		/**
			ENTRY POINT!
			Renders the page from the begining till the end.
		*/
		public function show() {
			// init session:
			$this->initSession();

			// check access:
			$this->checkAccess();

			// call GET or POST handler:
			if ( $_SERVER['REQUEST_METHOD'] == "POST" ) $this->doPost();
			else $this->doGet();
		}

		/**
			Is called on EVERY request just before processing it.
			Override this function to forbid some pages or implement access control.
		*/
		protected function checkAccess() {
			// allowed by default
			return true;
		}

		/**
			Is called on EVERY CSS request just before processing it.
			$this->css is a named arry which can be overrided with custom CSS values.
			Override this function to include required CSS definitions.
		*/
		protected function initCSS() {
			// do nothing by default:
		}

		/**
			Is called on EVERY JS request just before processing it.
			Override this function to provide specific JS output.
		*/
		protected function initJS() {
			// do nothing by default:
		}

		/**
			Is called on EVERY JSON request just before processing it.
			$this->json is a variable.
			Override this function to provide JSON output.
		*/
		protected function initJSON() {
			// set empty data by default
			//$this->json = NULL;
		}

		/**
			Is called on EVERY XML request just before processing it.
			$this->xml is a DomDocument.
			Override this function to provide XML output.
		*/
		protected function initXML() {
			// set empty root element
			$this->xmlDoc = new DomDocument("1.0", "UTF-8");
			$this->xmlDoc->formatOutput = true;
			$root = $this->xmlDoc->createElement(get_class($this));
			$this->xmlDoc->appendChild($root);
		}

		/**
			Is called on every GET request just before showing page.
			Override to make custom initializations, such as DB fetches or attach CSS and JS.
			In POST requests - use doPost().
		*/
		protected function init() {
			// default heads:
			$this->heads["<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">"] = true;
			$this->heads["<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"] = true;

			// we always include self-named CSS:
			//$ver = Config::VERSION.".".intval(time()/(Config::INVALIDATE_CSS_MS));
			//$this->cssFiles[get_class($this).".".$ver.".css"] = true;
			$this->cssFiles[get_class($this).".css"] = true;
		}

		/**
			Is called on HTTP POST request.
			Override this to get POST handler.
		*/
		protected function doPost() {
			// do nothing and go to the same page but with GET request:
			go($_SERVER['REQUEST_URI']);
		}

		/**
			Is called on HTTP GET request.
			By default - displays HTML header, header, body, footer and HTML footer.
			Override this to get custom GET handler.
		*/
		protected function doGet() {
			$this->init();
			$this->showHTMLHeader();
			$this->showBeforeBody();
			$this->showBody();
			$this->showAfterBody();
			$this->showHTMLFooter();
		}

		/**
			This does nothing by default.
		*/
		protected function showBeforeBody() {
		}

		/**
			This does nothing by default.
		*/
		protected function showAfterBody() {
		}

		/**
			Displays page body.
			Override this to get custom body.
		*/
		protected function showBody() {
			if ( $this->body ) print "<div class='pageBody'>".$this->body. "</div>\n";
			else print "<div class='pageBody'><p>Извините, раздел в разработке.</p></div>\n";
		}

		/**
			Displays HTML header of the page. This is called from showHeader().
			Override this to get custom HTML header.
		*/
		protected function showHTMLHeader() {
			header("Content-type: text/html; charset=".Config::CHARSET);
			if ( $this->isHTML5 ) {
?>
<!DOCTYPE html>
<?
			}
			else if ( $this->isStrictHTML ) {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<?
			}
?>
<html>
<head>
<?
			// strip tags in meta-title:
			$str = strip_tags(($this->title ? $this->title : $this->name));
?>
<title><?= p($str) ?></title>
<?
			if ( $this->description ) {
?>
<meta name="description" content="<?= p($this->description) ?>" />
<?
			}
			if ( $this->keywords ) {
?>
<meta name="keywords" content="<?= p($this->keywords) ?>" />
<?
			}
?>
<meta name="generator" content="PlayNext CMS (http://www.playnext.ru)" />
<meta content="text/html; charset=<?= Config::CHARSET ?>" http-equiv="Content-Type" />
<?
			// CSS
			if ( $this->cssFiles ) {
				if ( !is_array($this->cssFiles) ) $this->cssFiles = array($this->cssFiles);
				foreach ( $this->cssFiles as $file => $isOn ) {
					if ( !$isOn ) continue;

					// add css invalidation:
					if ( preg_match("/^((http)|(\/)).+/", $file) ) {
						// absolute or root URL - no invalidation
					}
					else {
						$ver = Config::VERSION.".".intval(time()/(Config::INVALIDATE_CSS_MS));
						$file = "/css/".substr($file, 0, -4).".".$ver.".css";
					}
?>
<link rel="stylesheet" href="<?= $file ?>" type="text/css" />
<?
				}
			}

			if ( $this->isJSRequired ) {
?>
<? ///<noscript><meta http-equiv="refresh" content="0; URL=/no-java-script.html"/></noscript> ?>
<SCRIPT LANGUAGE="JavaScript">
<!--
var onReadys=new Array();
var d=document;
//-->
</SCRIPT>
<?
			}

			// JS
			if ( $this->jsFiles ) {
				if ( !is_array($this->jsFiles) ) $this->jsFiles = array($this->jsFiles);
				foreach ( $this->jsFiles as $file => $isOn ) {
					if ( !$isOn ) continue;
?>
<script language="javascript" src="<?= preg_match("/^((http)|(\/)).+/", $file)?$file:("/js/".$file) ?>"></script>
<?
				}
			}

			// VB
			if ( $this->vbFiles ) {
				if ( !is_array($this->vbFiles) ) $this->vbFiles = array($this->vbFiles);
				foreach ( $this->vbFiles as $file => $isOn ) {
					if ( !$isOn ) continue;
?>
<script language="vbscript" src="<?= preg_match("/^\/.+/", $file)?$file:("/js/".$file) ?>"></script>
<?
				}
			}

			if ( $this->isJSRequired ) {
?>
<script language="javascript" src="/js/onReady.js"></script>
<?
			}
?>
<? //<link rel="icon" href="/favicon.ico" type="image/x-icon" /> ?>
<link rel="icon" type="image/png" href="/favicon.png" />
<link rel="shortcut icon" href="/favicon.ico"><? //for IE ?>
<?
			foreach ( $this->heads as $item => $isOn ) {
				if ( $isOn ) print $item."\n";
			}
?>
</head>
<body>
<?
		}

		/**
			Displays HTML footer of the page. This is called from showFooter().
			Override this to get custom HTML footer.
		*/
		protected function showHTMLFooter() {
?>
</body>
</html>
<?
		}

		public function alterCSS($class, $style) {
			if ( !$this->css[$class] ) {
				$this->css[$class] = $style;
				return;
			}
			$ss = explode(";", $this->css[$class]);
			$ss2 = explode(";", $style);
			$newStyle = array();
			$restStyle = $ss2;
			foreach ( $ss as $s ) {
				$kv = explode(":", $s);
				$k = strtolower(trim(array_shift($kv)));
				$v = implode(":", $kv);	// in IE CSS we may have many ":"
				if ( !$k ) continue;
				$isFound = false;
				foreach ( $ss2 as $i => $s ) {
					$kv2 = explode(":", $s);
					$k2 = strtolower(trim(array_shift($kv2)));
					$v2 = implode(":", $kv2);	// in IE CSS we may have many ":"
					if ( !$k2 ) continue;
					if ( $k2 == $k ) {
						$newStyle[] = $k.":".$v2;
						$isFound = true;
						array_splice($restStyle, $i, 1);
					}
				}
				if ( !$isFound ) $newStyle[] = $k.":".$v;
			}
			$this->css[$class] = implode(";", $newStyle);
			if ( sizeof($restStyle) ) $this->css[$class] .= ";".implode(";", $restStyle);
		}
	}
?>
