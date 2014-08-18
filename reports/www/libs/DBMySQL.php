<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/

	/**
		MySQL operations class.
		This is utility class - all methods and variables are static.
	*/
	class DBMySQL {
		const CHARSET_CP1251			= "cp1251";
		const CHARSET_UTF8				= "UTF8";

		const LOG_FILE					= "DBMySQL.log";

		private static $lastQ;
		//private static $dbLink;			// low-level connection (mysqli)
		private static $dbConnection;	// stored last connection

		// static logger - lazy instantiated with logger():
		protected static $logger;

		// current transaction save point:
		public static $savepoint = 0;

		/**
			Connects to the specified DB.
			If DBConnection is not passed tries to use global DBConfig params.
		*/
		public static function connect($dbConnection=NULL) {
			if ( $dbConnection == NULL && class_exists("DBConfig") ) {
				self::logger()->w("Using default DBConfig class as connection parameters.");

				// take default global DB settings:
				$dbConnection = new DBConnection();
				$dbConnection->db = DBConfig::DB;
				$dbConnection->host = DBConfig::HOST;
				$dbConnection->user = DBConfig::USER;
				$dbConnection->password = DBConfig::PASSWORD;
				$dbConnection->charset = DBConfig::CHARSET;
			}
			else if ( $dbConnection == NULL ) {
				self::logger()->err("Could not connect to DB, neither connection parameters are available.");
				throw new DBException("Could not connect to DB, neither connection parameters are available.");
			}

			// try to connect:
			self::logger()->w("Connecting to MySQL database ".$dbConnection->db."@".$dbConnection->host."...");
			try {
				$dbConnection->link = mysqli_connect($dbConnection->host, $dbConnection->user, $dbConnection->password, $dbConnection->db);
				if ( !$dbConnection->link ) {
					$err = "Could not connect to DB. MySQL error: ".mysqli_connect_error().". Error code: ".mysqli_connect_errno();
					throw new DBException($err);
				}
			}
			catch ( Exception $e ) {
				$err = "Could not connect to DB. Exception: ".$e;
				self::logger()->err($err);
				throw new DBException($err);
			}

			// store connection:
			self::$dbConnection = $dbConnection;
			self::logger()->w("Connected to MySQL database ".$dbConnection->db."@".$dbConnection->host.": ".$dbConnection->link->server_info);
			//print("DB connection is established, MySQL server: ".$dbConnection->link->server_info.LF);

			// set client charset:
			self::setClientCharset($dbConnection->charset);
		
			return self::$dbConnection;
		}

		public static function setClientCharset($charset, $dbConnection=NULL) {
			self::q("SET NAMES '".$charset."'", $dbConnection);
		}

		public static function close($dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			return $dbConnection->link->close();
		}

		/**
			Chooses either last stored connection or specified connection (if passed and is valid).
		*/
		public static function getConnection($dbConnection=NULL) {
			if ( $dbConnection == NULL && !self::$dbConnection ) {
				// try to establish new connection:
				self::connect();
			}
			if ( $dbConnection != NULL ) {
				if ( $dbConnection->link == NULL ) throw new DBException("DB connection is not established (specified link is NULL).");

				// use specified connection:
				return $dbConnection;
			}

			// use last stored connection:
			return self::$dbConnection;
		}

		public static function transactionStart($dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			if ( !$dbConnection->link->autocommit(false) ) throw new DBException(self::err($dbConnection));
			self::logger()->w("START TRANSACTION");
			$q = "SAVEPOINT save".self::$savepoint;
			self::logger()->w($q);
			if ( !$dbConnection->link->query($q) ) throw new DBException(self::err($dbConnection));
			return self::$savepoint++;
		}

		public static function transactionCommit($dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			self::$savepoint--;
			if ( self::$savepoint ) {
				// there are more transactions, so - only release savepoint now:
				$q = "RELEASE SAVEPOINT save".(self::$savepoint);
				self::logger()->w($q);
				if ( !$dbConnection->link->query($q) ) throw new DBException(self::err($dbConnection));
			}
			else {
				if ( !$dbConnection->link->commit() ) throw new DBException(self::err($dbConnection));
				if ( !$dbConnection->link->autocommit(true) ) throw new DBException(self::err($dbConnection));
				self::logger()->w("COMMIT");
			}
			return self::$savepoint;
		}

		public static function transactionRollback($dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			self::$savepoint--;
			if ( self::$savepoint ) {
				// there are more transactions, so - only release savepoint now:
				$q = "ROLLBACK TO SAVEPOINT save".(self::$savepoint);
				self::logger()->w($q);
				if ( !$dbConnection->link->query($q) ) throw new DBException(self::err($dbConnection));
			}
			else {
				if ( !$dbConnection->link->rollback() ) throw new DBException(self::err($dbConnection));
				self::logger()->w("ROLLBACK");
			}
			return self::$savepoint;
		}

		public static function q($q, $dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);

			self::$lastQ = $q;
			self::logger()->w($q);

			$r = $dbConnection->link->query($q);
			if ( !$r ) throw new DBException(self::err($dbConnection));
			return $r;
		}

		public static function checkResult($r) {
			if ( !$r ) throw new DBException(self::err($dbConnection));
		}

		public static function row($r, $dbConnection=NULL) {
			self::checkResult($r);
			return $r->fetch_row();
		}

		public static function assoc($r) {
			self::checkResult($r);
			return $r->fetch_assoc();
		}

		/**
			Alias for fetchAll().
		*/
		public static function fetch($entityName, $q, $dbConnection=NULL, $ignoreKeys=false) {
			return self::fetchList($entityName, $q, $dbConnection, $ignoreKeys);
		}

		/**
			Fetches all records from DB.
		*/
		public static function fetchAll($entityName, $dbConnection=NULL, $ignoreKeys=false) {
			$code = "\$item = new ".$entityName."(\$a);";
			eval($code);
			$r = self::q("SELECT * FROM `".$item->tableName()."`", $dbConnection);
			$items = array();
			while ( $a = self::a($r, $dbConnection) ) {
				eval($code);
				$item->setNew(false);
				if ( $ignoreKeys ) $items[] = $item;
				else $items[$item->id()] = $item;
			}
			return $items;
		}

		/**
			Fetches multiple records from DB via SQL query and makes entity PHP objects.
			Returns an array where keys are PKs and values are related objects.
		*/
		public static function fetchList($entityName, $q, $dbConnection=NULL, $ignoreKeys=false) {
			///require_once($entityName.".php");
			$code = "\$item = new ".$entityName."(\$a);";
			$r = self::q($q, $dbConnection);
			$items = array();
			while ( $a = self::a($r, $dbConnection) ) {
				eval($code);
				$item->setNew(false);
				if ( $ignoreKeys ) $items[] = $item;
				else $items[$item->id()] = $item;
			}
			return $items;
		}

		/**
			Fetches a single record from DB via SQL query and converts it to an entity PHP object.
		*/
		public static function fetchOne($entityName, $q, $dbConnection=NULL) {
			$r = self::q($q, $dbConnection);
			if ( $a = self::a($r, $dbConnection) ) {
				///require_once($entityName.".php");
				$code = "\$item = new ".$entityName."(\$a);";
				eval($code);
				$item->setNew(false);
				return $item;
			}
			// item not found:
			return NULL;
		}

		/**
			Fetches a single record from DB using PK and converts it to an entity PHP object
		*/
		public static function fetchById($entityName, $pkValues) {
			///require_once($entityName.".php");
			$code = "\$object = new ".$entityName."();";
			eval($code);

			// Get primary key property(s):
			$pks = $object->primaryKey();

			// convert pkValues to array if needed:
			if ( !is_array($pkValues) ) $pkValues = explode(Entity::ID_DELIMITER,$pkValues);

			// make SQL for PK:
			$wherePKS = array();
			$i = 0;
			foreach ( $pks as $key ) {
				$wherePKS[] = "`".$key."`='".s($pkValues[$i++], 1)."'";
			}

			//print("SELECT * FROM ".$object->tableName()." WHERE ".implode(" AND ", $wherePKS).LF);
			return self::fetchOne($entityName, "SELECT * FROM `".$object->tableName()."` WHERE ".implode(" AND ", $wherePKS));
		}

		/**
			Makes SELECT query, fetching a single record.
		*/
		public static function selectOne($q, $dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			$r = self::q($q, $dbConnection) or err(self::err($dbConnection).LF);
			$item = self::a($r, $dbConnection);
			return $item;
		}

		/**
			Makes SELECT query returning array of raw records.
		*/
		public static function select($q, $dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			$r = self::q($q, $dbConnection) or err(self::err($dbConnection).LF);
			$items = array();
			while ( $item = self::a($r, $dbConnection) ) $items[] = $item;
			return $items;
		}

		/**
			Makes SELECT query, fetching a single record and returns a value of first column.
		*/
		public static function selectValue($q, $dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			$a = self::selectOne($q, $dbConnection);
			return array_shift($a);
		}		
		
		public static function r($r, $dbConnection=NULL) {
			return self::row($r, $dbConnection);
		}

		public static function a($r, $dbConnection=NULL) {
			return self::assoc($r, $dbConnection);
		}

		public static function numRows($r, $dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			return $dbConnection->link->num_rows($r);
		}

		public static function err($dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			//da(self::$log);
			if ( self::$lastQ ) return $dbConnection->link->error.LF."<p class='msg2'>".self::$lastQ.";</p>";
			return $dbConnection->link->error;
		}

		public static function id($dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			return $dbConnection->link->insert_id;
		}

		public static function affectedRows($dbConnection=NULL) {
			$dbConnection = self::getConnection($dbConnection);
			return $dbConnection->link->affected_rows();
		}

		public static function now($dbConnection=NULL) {
			$q = "SELECT NOW() AS now, DATE_FORMAT(NOW(),'%d.%m.%Y') AS dateNow, DATE_FORMAT(NOW(),'%h:%i') AS timeNow, YEAR(NOW()) AS year, MONTH(NOW()) AS month, DAYOFMONTH(NOW()) AS day, HOUR(NOW()) AS hour, MINUTE(NOW()) AS minute, SECOND(NOW()) AS second";
			$r = self::q($q, $dbConnection);
			$a = self::a($r, $dbConnection);
			return $a;
		}

		/**
			Returns current PHP time in format of DB.
		*/
		public static function nowPHP() {
			return strftime("%Y-%m-%d %H:%M:%S");
		}

		public static function tables($dbConnection=NULL) {
			$r = self::q("SHOW TABLES", $dbConnection);
			$tables = array();
			while ( $row = self::r($r, $dbConnection) ) {
				$tables[] = $row[0];
			}
			return $tables;
		}

		/**
			Converts MySQL timestamp to UNIX timestamp.
			input: 20040629170230
			output: UNIX timestamp for 29 June 2004, 17:02:30
		*/
		public static function mySQL2TimeStamp($mySQLStamp) {
			return mktime (
				substr($mySQLStamp, 8, 2),
				substr($mySQLStamp, 10, 2),
				substr($mySQLStamp, 12),
				substr($mySQLStamp, 4, 2),
				substr($mySQLStamp, 6, 2),
				substr($mySQLStamp, 0, 4));
		}

		public static function tableNameFor($entityName) {
			require_once($entityName.".php");
			$code = "\$object = new ".$entityName."();";
			eval($code);
			return $object->tableName();
		}

		/**
			Deletes specified entity by its PK values.
			Transaction procedure:
				1. Start of transaction.
				2. Fetches item with fetchById().
				3. Calls trigger-method beforeDelete().
				4. Calls delete().
				5. Calls trigger-method afterDelete().
				6. Commits transaction.
			Trigger methods may return null to specify cancel of transaction.
			Returns the item is being deleted.
		*/
		public static function deleteById($entityName, $pkValues, $dbConnection=NULL) {
			// fetch item:
			$item = self::fetchById($entityName, $pkValues);
			if ( !$item ) throw new DBException("Could not fetch item ".$enityName." by PK: ".$pkValues);

			// before delete:
			try {
				$item->beforeDeleteTransaction();
			}
			catch ( Exception $e ) {
				throw new DBException("Could not process afterDeleteTransaction for item ".$item.": ".$e);
			}

			// start transaction:
			self::transactionStart($dbConnection);

			// before delete:
			try {
				$item->beforeDelete();
				$item->delete();
				$item->afterDelete();
			}
			catch ( Exception $e ) {
				// We may cancel transaction even here!
				self::transactionRollback($dbConnection);
				throw new DBException("Could not delete item ".$enityName." by PK: ".$pkValues.": ".$e);
			}

			// end transaction:
			self::transactionCommit($dbConnection);

			// before delete:
			try {
				$item->afterDeleteTransaction();
			}
			catch ( Exception $e ) {
				throw new DBException("Could not process afterDeleteTransaction for item ".$item.": ".$e);
			}

			return $item;
		}

/*		public static function fetchActiveByID($entityName, $id) {
			$code = "\$object = new $entityName();";
			eval($code);
			$data = self::fetchOne("SELECT * FROM ".$object->tableName()." WHERE id='".$id."' AND isActive");
			if ( !$data ) return NULL;
			$object->applyArray($data);
			return $object;
		}*/

		protected static function logger() {
			if ( self::$logger ) return self::$logger;
			self::$logger = Logger::fileLogger(\E7::PATH_LOGS.self::LOG_FILE);
			return self::$logger;
		}
	}
?>