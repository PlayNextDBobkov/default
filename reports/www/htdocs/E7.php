<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.

		Core changes from e5 engine:
		1. Auto class loading
		2. Support of namespacing as physical directories.
		3. Annotations.
		4. Automatic web form building methods for entities.
		5. PageEntity->mask property. URL rewrite rules in .htaccess not needed anymore.
		6. URL envelopiing through page parents' URLs.
		7. Extended use of PHP Exceptions + readable fatal error messaging.
		8. Less global methods.
		9. Better logging.
		10. Better performance measurement tools.
	*/

	// some global definitions:
	require_once(E7::PATH_LIBS."_globals.php");

	/**
		Engine class.
		This is an entry point for any HTTP request.
	*/
	class E7 {
		// version & information:
		const VERSION					= "7.0a";
		const PHP_MIN_VERSION			= "5.3.15";

		// paths modifiers from current PHP directory (by default this is Apache DocumentRoot):
		const PATH						= "../";
		const PATH_ENTITIES				= "../entities/";
		const PATH_CONFIGS				= "../configs/";
		const PATH_ACTIONS				= "../actions/";
		const PATH_ACTIONS_ADMIN		= "../actions/admin/";
		const PATH_LIBS					= "../libs/";
		const PATH_HTDOCS				= "../htdocs/";
		const PATH_TMP					= "../tmp/";
		const PATH_LOGS					= "../logs/";

		// reserved URL request params:
		const PARAM_ACTION				= "a";
		const PARAM_CSS					= "css";
		const PARAM_JS					= "js";
		const PARAM_XML					= "xml";
		const PARAM_JSON				= "json";
		const PARAM_DEBUG				= "debug";
		const PARAM_PRINT				= "print";
		const PARAM_PAGE_ID				= "pid";
		const PARAM_LANGUAGE_ID			= "l";
		const PARAM_VERSION				= "v";

		// this class is taken as default page for action-based pages:
		const PAGE_CLASS_HOME			= "HomePage";
		const PAGE_CLASS_404			= "Error404";	// from E5::PATH_ACT
		const PAGE_CLASS_505			= "Error505";	// from E5::PATH_ACT

		// page tables:
		const TABLE_PAGE_ACTION_DATA			= "page_action_data";
		const TABLE_PAGE_ACTION_DATA_PAGE		= "page_action_data_page";

		// misc consts:
		const ENV						= "E7_ENV";
		const PHP_FILE_EXT				= "php";

		// operation systems name constatns:
		const OS_WINDOWS				= "Windows";
		const OS_UNIX					= "Unix";

		// static vars for public use:
		public static $os				= "Unknown";
		public static $osPathDelimiter	= ":";
		public static $isUnderWindows	= false;

		// request params:
		public static $languageId;
		public static $actionId;

		/**
			Private constructor. Forbids initialization.
		*/
		private function E7() {
		}


		/**
			Engine and environment initialization.
		*/
		protected static function init() {

			// show errors in custom envs, not in live:
			error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
			ini_set("display_errors", $_SERVER[self::ENV]?1:0);

			// verify PHP version:
			list ( $major, $minor, $release ) = explode(".", E7::PHP_MIN_VERSION);
			$verNeeded = intval(str_pad($major, 3, "0", STR_PAD_LEFT).str_pad($minor, 3, "0", STR_PAD_LEFT).str_pad($release, 4, "0", STR_PAD_LEFT));
			$verNow = intval(str_pad(PHP_MAJOR_VERSION, 3, "0", STR_PAD_LEFT).str_pad(PHP_MINOR_VERSION, 3, "0", STR_PAD_LEFT).str_pad(PHP_RELEASE_VERSION, 4, "0", STR_PAD_LEFT));
			if ( $verNeeded > $verNow ) {
				die("Sorry, this site needs PHP version ".E7::PHP_MIN_VERSION." or better. Your one is "
					.PHP_MAJOR_VERSION."."
					.PHP_MINOR_VERSION."."
					.PHP_RELEASE_VERSION);
			}

			// init auto loader:
			spl_autoload_register("E7::autoloadHandler");
			register_shutdown_function("E7::fatalHandler");
			set_exception_handler(_E7UnhandledExceptionsHandler);
			
			// set timezone:
			if ( constant("Config::TIMEZONE") ) date_default_timezone_set(Config::TIMEZONE);
		}

		/**
			Language selection.
		*/
		protected static function initLanguage() {
			// set language:
			if ( isset($_GET[self::PARAM_LANGUAGE_ID]) && in_array($_GET[self::PARAM_LANGUAGE_ID], array_keys(Config::supportedLanguages())) ) {
				// language is specified:
				E7::$languageId = $_GET[self::PARAM_LANGUAGE_ID];

				// set language-oriented well-known entity property names:
				if ( E7::$languageId != Config::DEFAULT_LANGUAGE_ID ) {
					Entity::$pIsActive = Entity::PROPERTY_IS_ACTIVE."_".E7::$languageId;
					Entity::$pName = Entity::PROPERTY_NAME."_".E7::$languageId;
					Entity::$pNames = Entity::PROPERTY_NAMES."_".E7::$languageId;
					Entity::$pNameR = Entity::PROPERTY_NAME_R."_".E7::$languageId;
					Entity::$pNamesD = Entity::PROPERTY_NAMES_D."_".E7::$languageId;
					Entity::$pShort = Entity::PROPERTY_SHORT."_".E7::$languageId;
					Entity::$pFull = Entity::PROPERTY_FULL."_".E7::$languageId;
				}
			}
			else {
				// language is not specified - set default language:
				E7::$languageId = Config::DEFAULT_LANGUAGE_ID;
			}
		}

		/**
			HTTP request processing.
		*/
		protected static function processRequest() {
			// What action is requested?
			$actionId = isset($_POST[E7::PARAM_ACTION]) ? $_POST[E7::PARAM_ACTION] : $_GET[E7::PARAM_ACTION];
			E7::$actionId = $actionId;

			// should we fetch some page from DB for this request?
			if ( Config::DB_PAGES_ENABLED ) {
				// What page ID (pid) is requested?
				$pageId = isset($_POST[E7::PARAM_PAGE_ID]) ? intval($_POST[E7::PARAM_PAGE_ID]) : intval($_GET[E7::PARAM_PAGE_ID]);

				// whether pageId or actionID is specified?
				if ( !$pageId && !$actionId ) {
					// neither pageID, not actionID is specified - we show home page:
					$page = PageEntity::fetchOneActiveByAction(self::PAGE_CLASS_HOME);
					if ( !$page ) {
						// page not found in DB by ID - show 404
						E7::error404();
						return;
					}

					// show home page and exit:
					E7::showPage($page);
					return;
				}
				else if ( $pageId ) {
					// PID is specified - fetch page from DB:
					// is it page by action or a valid PID?
					if ( $pageId == -1 && $actionId ) {
						// fetch page by action - will be transfored:
						$page = PageEntity::fetchOneActiveByAction($actionId);
					}
					else {
						// fetch by valid page ID:
						$page = PageEntity::fetchActiveById($pageId);
					}

					if ( !$page ) {
						// page not found in DB by ID - show 404
						E7::error404();
						return;
					}

					// show page and exit:
					E7::showPage($page);
					return;
				}
			}

			// pageID is not specified... check actionID:
			if ( !$actionId ) {
				// if it is not specified - display home page:
				$actionId = self::PAGE_CLASS_HOME;
			}
			else {
				// convert action-name to ActionName:
				$actionId = preg_replace("/(^|-)([a-z])/", "strtoupper('$2')", $actionId);
			}

			// include action file:
			if ( preg_match("/^Admin.*/", $actionId) ) {
				// admin actions are included from "admin" or "admin/lite" folders
				$actionFile = E7::PATH_ACTIONS."admin/".$actionId.".php";
			}
			else $actionFile = E7::PATH_ACTIONS.$actionId.".php";

			// This "is_file" check is important against hacker attacks:
			if ( !is_file ( $actionFile ) ) {
				if ( $page ) {
					// action is specified in DB, but there is no file with such action (logic)
					throw new E7Exception("Action file '".p($actionFile)."' not found.");
				}
				else self::error404();	// there is no file with such action (logic)
				return;
			}

			// check there is a logic class (it has the same name as the action):
			$actionClassName = preg_replace("/[-_]([A-Za-z0-9])/", strtoupper("$1"), $actionId);
			//$actionClassName = preg_replace("/^([a-z])/", strtoupper("$1"), $actionClassName);
			if ( class_exists($actionClassName) ) {
				// yes, there is a page class, instantiate it:
				$pageObject = new $actionClassName();

				// is it CSS request?
				if ( $_GET[self::PARAM_CSS] ) E7::showPage($pageObject, "CSS");
				// is it JS request?
				else if ( $_GET[self::PARAM_JS] ) E7::showPage($pageObject, "JS");
				// is it XML request?
				else if ( $_GET[self::PARAM_XML] ) E7::showPage($pageObject, "XML");
				// is it JSON request?
				else if ( $_GET[self::PARAM_JSON] ) E7::showPage($pageObject, "JSON");
				else {
					// show HTML page:
					E7::showPage($pageObject);
				}
				return;
			}
			else throw new E7Exception("Action class '".p($actionClassName)."' is not declared in action file '".p($actionFile)."'.");
		}

		/**
			Renders page.
			If $page->pageClass (design) is specified - transforms page to be displayed under as class.
		*/
		protected static function showPage($page, $method="") {
			$method = "show".$method;
			$page->$method();
		}

		/**
			Show 404 error page.
		*/
		public static function error404() {
			eval("\$page = new ".self::PAGE_CLASS_404."();");
			self::showPage($page);
			exit();
		}

		/**
			Show 505 error page.
		*/
		public static function error505($message=NULL) {
			eval("\$page = new ".self::PAGE_CLASS_505."();");
			$page->body = $message;
			self::showPage($page);
			exit();
		}

		/**
			Class autoload routine.
			PHP uses this method to find classes.
			As we split namespaces into directories, we have to require_once files with proper path.
		*/
		public static function autoloadHandler($class) {

			$name = str_replace('\\', DIRECTORY_SEPARATOR, $class);
			$file = $name . ".".E7::PHP_FILE_EXT;
			$fileEnv = $_SERVER[E7::ENV] ? ($name . ".".$_SERVER[E7::ENV].".".E7::PHP_FILE_EXT) : NULL;

			$tries = array();
			$tries[] = array(E7::PATH.$file, $class);	// first, try exact path
			if ( $fileEnv ) $tries[] = array(E7::PATH_ENTITIES.$fileEnv, $class);	// then in entities (enved)
			$tries[] = array(E7::PATH_ENTITIES.$file, $class);	// then in entities
			if ( $fileEnv ) $tries[] = array(E7::PATH_ACTIONS.$fileEnv, $class);	// then in actions (enved)
			$tries[] = array(E7::PATH_ACTIONS.$file, $class);	// then in actions
			if ( $fileEnv ) $tries[] = array(E7::PATH_ACTIONS_ADMIN.$fileEnv, $class);	// then in actions/admin (enved)
			$tries[] = array(E7::PATH_ACTIONS_ADMIN.$file, $class);	// then in actions/admin
			if ( $fileEnv ) $tries[] = array(E7::PATH_CONFIGS.$fileEnv, $class);	// then in configs (enved)
			$tries[] = array(E7::PATH_CONFIGS.$file, $class);	// then in configs
			$tries[] = array(E7::PATH_LIBS.$file, $class);	// then in libs

			foreach ( $tries as $try ) {
				$tryFile = $try[0];
				$tryClass = $try[1];
				//print "Trying to include file '".$tryFile."' for class ".$tryClass.".".LF;
				if ( file_exists($tryFile)) {
					// yes, here it is:
					require_once($tryFile);
					if ( class_exists($tryClass) ) return;

					// could not find class in the file, through error
					throw new E7AutoloadException("File '".p($tryFile)."' does not declare class '".p($tryClass)."'.");
				}
			}

			// could not find such file, through error:
			throw new E7AutoloadException("File".($file?" '".p($file)."'":"")." for class '".p($class)."' not found.");
		}

		/**
			Processes fatal errors.
		*/
		public static function fatalHandler() {
			if ( $error = error_get_last() ) {
				if ( in_array($error['type'], array(E_ERROR, E_CORE_ERROR)) )
					self::showException(new E7FatalException($error));
			}
		}

		/**
			HTTP request entry point.
		*/
		public static function start() {
			E7::init();
			E7::initLanguage();
			E7::processRequest();
		}

		public static function hello() {
			print "Hello, world!".LF;
			print "This is PlayNext E7 web-engine, ver. ".E7::VERSION.LF;
			print "PHP version: ".PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION.".".PHP_RELEASE_VERSION." (needed: ".E7::PHP_MIN_VERSION.")".LF;
			print "Namespace: ".__NAMESPACE__.LF;
			print "OS: ".PHP_OS.LF;
			print "Path separator: '".PATH_SEPARATOR."'".LF;
		}

		public static function showException($e) {

			// do not show any errors if live env:
			if ( !isset($_SERVER[self::ENV]) ) return;

			$trace = $e->getTraceAsString();
			$file = $e->getFile();
			$line = $e->getLine();
?>
<style>
div.E7Exception{border:2px solid #f00;background:#fed;padding:24pt;margin:24px 0;clear:both;color:#000;font-family:Arial;}
div.E7Exception h1{margin:0;padding:0;font:bold 24pt Arial;color:#f66}
div.E7Exception p{margin:0 0 8pt 0}
div.E7Exception p.fileLine{}
div.E7Exception p.fileLine span.file{font-weight:bold}
div.E7Exception p.msg{font-size:18pt;}
div.E7Exception p.msg2{font-size:12pt;font-style:italic}
div.E7Exception p.trace{font:12px Courier;color:#666}
</style>
<div class="E7Exception">
<h1><?= get_class($e) ?></h1>
<p class="msg"><?= $e->getMessage() ?></p>
<p class="fileLine"><?= preg_replace("/(.+\/)([^\/]+)$/", "$1<span class='file'>$2", $file) ?>:<?= $line ?></span><p>
<p class="trace"><?= nl2br(p($trace)) ?></p>
</div>
<?
		}
	}

	/**
		Processes unhanlded exceptions.
	*/
	function _E7UnhandledExceptionsHandler() {
		throw new E7UnhandledException($e);
	}

	/**
		Base class for all exceptions.
	*/
	class E7Exception extends Exception {
		protected static $logger;

		public function __construct($msg=null, $code=null) {
			parent::__construct($msg, $code);

			// try to log the error:
			try {
				self::logger()->err($msg);
			}
			catch ( LoggerException $e ) {
				die("!!!".$e);
			}
		}
		
		protected static function logger() {
			if ( self::$logger ) return self::$logger;
			self::$logger = Logger::fileLogger(get_class($this).".log");
			return self::$logger;
		}
	}

	class E7AutoloadException extends E7Exception {
		public function __construct($msg=null, $code=null) {
			parent::__construct($msg, $code);

			// override file and line - show actual place of error:
			$lines = explode("\n", $this->getTraceAsString());
			preg_match("/\#\d+ (.+)\((\d+)\):.+/", $lines[1], $m);
			$this->file = $m[1];
			$this->line = $m[2];
		}
	}

	class E7UnhandledException extends E7Exception {
	}

	class E7FatalException extends E7Exception {
		protected $error;

		public function __construct($error) {
			$this->code = $error["type"];
			$this->file = $error["file"];
			$this->line = $error["line"];
			$this->message = "Fatal error. ".$error["message"];
			$this->error = $error;
			parent::__construct($this->message, $errno);
		}
	}

	// Let's go!
	try {
		E7::start();
	}
	catch (Exception $e) {
		E7::showException($e);
	}
?>