<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/

	/**
		Exception generated while working with DB.
		TODO: implement to all DB methods.
	*/
	class DBException extends E7Exception {
		public function __construct($msg=null, $code=null) {
			parent::__construct($msg, $code);

			// override file and line - show actual place of error:
			$lines = explode("\n", $this->getTraceAsString());
			preg_match("/\#\d+ (.+)\((\d+)\):.+/", $lines[2], $m);
			$this->file = $m[1];
			$this->line = $m[2];
		}
	}

	/**
		Instance of DB connection.
		TODO: implement instantiation of DB connection and its functionality.
	*/
	class DBConnection {
		public $host;
		public $port;
		public $user;
		public $password;
		public $db;
		public $link;
		//public $charset = DBMySQL::CHARSET_CP1251;
		public $charset = DB::CHARSET_UTF8;

		public function open() {
			DB::connect($this);
		}

		public function q($sql) {
			return DB::q($sql, $this);
		}

		public function row($r) {
			return DB::row($r, $this);
		}

		public function assoc($r) {
			return DB::assoc($r, $this);
		}

		public function fetch($entityName, $q) {
			return DB::fetch($entityName, $q, $this);
		}

		public function fetchAll($entityName, $q) {
			return DB::fetchAll($entityName, $q, $this);
		}

		public function fetchOne($entityName, $q) {
			return DB::fetchOne($entityName, $q, $this);
		}

		public function r($r) {
			return DB::row($r, $this);
		}

		public function a($r) {
			return DB::assoc($r, $this);
		}

		public function numRows($r) {
			return DB::numRows($r, $this);
		}

		public function err() {
			return DB::err($this);
		}

		public function id() {
			return DB::id($this);
		}

		public function affectedRows() {
			return DB::affectedRows($this);
		}

		public function now() {
			return DB::now($this);
		}

		public function tables() {
			return DB::tables($this);
		}

		public function close() {
			DB::close($this);
		}
	}

	/**
		This implements static implementation of DB connection.
		Use this when sinlge connection is used.
		Otherwise use DBConnection.
	*/
	class DB extends DBMySQL {
		const NULL		= "%%%NULL%%%";
		/**
			Takes named array and returns string where related DB table columns set to specified values.
			Intended to be used in SQL queries.
		*/
		public static function sqlSet($item)	{
			if ( !is_array($item) && !is_object($item) ) return "";
			$sql = array();
			reset($item);
			while ( list ( $key, $value ) = each ( $item ) ) {
				//if ( is_null($value) ) $sql[] = "`".$key."`=NULL";
				if ( is_scalar($value) && strcmp($value, DB::NULL)==0 ) $sql[] = "`".$key."`=NULL";
				else $sql[] = "`".$key."`='".s($value)."'";
			}
			$sql = implode(", ", $sql);
			return $sql;
		}
	}
?>