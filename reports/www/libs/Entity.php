<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/

	/**
		This is a base class for all database entities.
		It implements functionality for mapping between DB records and PHP objects.
		It allows creation, fetching and saving DB records though working with PHP objects.
		It understands the following "magic" properties of entities:
			id - This is a primary key field which is auto incremented - can be overriden with PRIMARY_KEY and AUTO_INCREMENT.
			pos - Used for sorting on-dimension array. See also sqlPosConditions().
			is[A-Z].* - Treated as booleans. Automaticaly resolved to 0 (if is false) or 1 (if is true) before saving.
			Disabled! isActive - Automatically set to true on brand new items.
			createdOn - Object creation time (PHP time). This is set when PHP object is created (not DB record) to current PHP processor time.
			updatedOn - Object last-update time (DB time). When object is saved this property is removed from INSERT/UPDATE SQL to allow database functionality to set this object to current DB time.
		It understands the following "magic" constants:
			PRIMARY_KEY - Specifies primary key property(s), comma separated.
			AUTO_INCREMENT - Specifies property which is set in DB as auto_inctement. Set it to NULL if there should be no such property.
	*/

	// @FormURL("/DefaultSaveAction/")	// default save action
	class Entity {
		// default constants for entities:
		const PRIMARY_KEY				= "id";
		const AUTO_INCREMENT			= self::PRIMARY_KEY;

		// names of magic constants:
		const CONSTANT_AUTO_INCREMENT	= "AUTO_INCREMENT";

		// names of magic properties:
		const PROPERTY_ID				= "id";
		const PROPERTY_IS_ACTIVE		= "isActive";
		const PROPERTY_CREATED_ON		= "createdOn";
		const PROPERTY_UPDATED_ON		= "updatedOn";
		const PROPERTY_CODE				= "code";
		const PROPERTY_POS				= "pos";
		const PROPERTY_NAME				= "name";
		const PROPERTY_NAMES			= "names";
		const PROPERTY_NAME_R			= "nameR";
		const PROPERTY_NAMES_D			= "namesD";
		const PROPERTY_SHORT			= "short";
		const PROPERTY_FULL				= "full";

		const PARAM_FORM_URL			= "formURL";
		const PARAM_FORM_SAVED_URL		= "formSavedURL";

		// these are reinited by E7 if language is not default:
		public static $pIsActive = Entity::PROPERTY_IS_ACTIVE;
		public static $pName = Entity::PROPERTY_NAME;
		public static $pNames = Entity::PROPERTY_NAMES;
		public static $pNameR = Entity::PROPERTY_NAME_R;
		public static $pNamesD = Entity::PROPERTY_NAMES_D;
		public static $pShort = Entity::PROPERTY_SHORT;
		public static $pFull = Entity::PROPERTY_FULL;

		// This delimiter is used to join multiple primary key parts - see id() method:
		const ID_DELIMITER				= ",";

		// Here we store key=>values which are applied but are not public properties:
		protected $_data = array();
		
		// This shows whether this is a new object (record) or a existing one:
		protected $_isNew = true;
		
		// Filled with a first properties() call:
		protected $_properties = NULL;
		protected $_propertiesOfThisEntity = NULL;

		// Filled with a first methods() call:
		protected $_methods = NULL;
		protected $_methodsOfThisEntity = NULL;

		// Filled with a first annotations() call:
		protected $_annotations = NULL;	// [class|variables|methods][tokenName][annotationName] = new Annotation()

		// parent action that instatiated the object:
		protected $parentAction = NULL;

		// logger for this entity:
		protected $_logger = NULL;

		// all object have access to this array, so use it for caching or interchangin data between items:
		protected static $_cache = array();

		/**
			Constructor allows creation of item with public properties initialized from an array.
		*/
		public function Entity($array=NULL, $parentAction=NULL) {
			// init log:
			$this->_logger = Logger::fileLogger("Entity.log");

			$this->setParentAction($parentAction);
			$this->setDefaultValues();

			// if instance supports createdOn - set it:
			if ( $this->hasProperty(self::PROPERTY_CREATED_ON) ) $this->createdOn = DB::nowPHP();

			// apply array if supplied (overriding createdOn):
			if ( $array != NULL ) {
				$this->applyArray($array);
			}
		}

		/**
			Set defaults for each property from @Default annotation.
		*/
		protected function setDefaultValues() {
			$props = $this->properties();
			foreach ( $props as $prop ) {
				$defaultValue = $this->propertyAnnotation($prop, "Default");
				if ( isset($defaultValue) ) $this->$prop = $defaultValue;
			}
		}

/* --------------------------------------------- */
/* Getters/setters
/* --------------------------------------------- */

		/**
			Returns unique identifier for this record composed of all PK values.
			Multiple PK values are joined by ID_DELIMITER in order of appearence in PRIMARY_KEY const of the class.
			EG: If PRIMARY_KEY = "catParentId,catChildId" then id() will return "<catParentId-value<ID_DELIMITER><catChildId-value>"
		*/
		public function id() {
			$pks = $this->primaryKey();
			$values = array();
			foreach ( $pks as $key ) {
				$values[] = $this->$key;
			}
			return implode(self::ID_DELIMITER, $values);
		}

		/**
			Sets values of PK properties.
		*/
		public function setId($pkValues) {
			// convert pkValues to array if needed:
			if ( !is_array($pkValues) ) $pkValues = explode(Entity::ID_DELIMITER,$pkValues);

			$pks = $this->primaryKey();
			$i = 0;
			foreach ( $pks as $key ) {
				$this->$key = $pkValues[$i++];
			}
		}

		/**
			Sets parent action.
		*/
		public function setParentAction($actionObject) {
			$this->_parentAction = $actionObject;
		}

		/**
			Sets specified property.
		*/
		public function set($key, $value) {
			$props = $this->properties();

			// if this is a boolean key - process it:
			if ( preg_match("/^is[A-Z].*$/", $key) ||
				preg_match("/^has[A-Z].*$/", $key) ||
				preg_match("/^with[A-Z].*$/", $key)
				) {
				if ( $value == "1" || strtolower($value) == "on" || strtolower($value) == "yes" || strtolower($value) == "true" ) $value = 1;
				else if ( $value == "0" || strtolower($value) == "off" || strtolower($value) == "no" || strtolower($value) == "false" ) $value = 0;
				else if ( $this->isBooleanNullable($key) ) $value = DB::NULL;
				else $value = 0;
			}
			/*else if ( preg_match("/^has[A-Z].*$/", $key) ) $value = $value && strtolower($value) != "false" && strtolower($value) != "off" && strtolower($value) != "no" ? 1 : 0;
			else if ( preg_match("/^with[A-Z].*$/", $key) ) $value = $value && strtolower($value) != "false" && strtolower($value) != "off" && strtolower($value) != "no" ? 1 : 0;*/

			// apply localized value:
			if ( !preg_match("/^isActive(_.{2})?/", $key) ) {
				if ( E7::$languageId
					&& isset($array[$key."_".E7::$languageId])
					&& $array[$key."_".E7::$languageId] ) {
					// override with localized field:
					$value = $array[$key."_".$_GET[E7::PARAM_LANGUAGE_ID]];
				}
			}

			// is it a public property?
			if ( !in_array($key, $props) ) {
				// store it in data:
				//print "to data".LF;
				$this->_data[$key] = $value;
				return;
			}

			// set value:
			//$this->set($key, $value, true);
			$this->$key = $value;
		}

		/**
			Returns either sinlge value or a named array of values that are not properties, but was applied with applyArray().
			It is useful when fetching records with SQLs that select specific non-object columns.
		*/
		public function data($key=NULL) {
			if ( $key == NULL ) return $this->_data;
			return $this->_data[$key];
		}

		/**
			Sets specified property in data array.
		*/
		public function setData($key, $value) {
			$this->_data[$key] = $value;
		}

		public function isBooleanNullable($key) {
			return false;
		}

		public function isNew() {
			return $this->_isNew;
		}

/* --------------------------------------------- */
/* Array interaction
/* --------------------------------------------- */

		/**
			Takes all public properties and extra data of $entity and copies to $this.
		*/
		public function applyEntity($entity) {
			if ( $entity == NULL || !is_object($entity) ) return;

			// apply public properties:
			$this->applyArray($entity->toArray());

			// apply extra data:
			$this->_data = $entity->_data;
		}

		/**
			Takes array keys and set equivalent public properties in the object.
			Keys which are not public properties can be accessible through data() call.
		*/
		public function applyArray($array=NULL, $prefix=NULL, $suffix=NULL) {
			if ( $array == NULL || !is_array($array) ) return;

			// iterate the array values:
			foreach ( $array as $key => $value ) {
				if ( "".$prefix != "" ) {
					if ( preg_match("/".addslashes($prefix)."(.+)/", $key, $m)) $key = $m[1];
					else continue;
				}
				if ( "".$suffix != "" ) {
					if ( preg_match("/(.+)".addslashes($suffix)."/", $key, $m)) $key = $m[1];
					else continue;
				}
				if ( $key ) $this->set($key, $value);
			}
		}

		/**
		 * Takes array and applies it to data with some prefix/suffix in each key name.
		*/
		public function applyData($array, $prefix="", $suffix="") {
			if ( $array == NULL || !is_array($array) ) return;

			// iterate the array values:
			foreach ( $array as $key => $value ) {
				//$k = $prefix.strtoupper(substr($key, 0, 1)).substr($key, 1);
				$k = $prefix.$key.$suffix;
				$this->setData($k, $value);
			}
		}
		
		/**
		 * Takes items and applies them all with applyData.
		*/
		public function applyDataList($items, $prefix="") {
			if ( $items == NULL || !is_array($items) ) return;

			// iterate the array values:
			$i = 0;
			foreach ( $items as $item ) {
				$this->applyData($item->toArray(), $prefix, $i);
				$i++;
			}
		}
		
		/**
			Returns an array with public properties as keys.
		*/
		public function toArray() {
			$class = new ReflectionClass(get_class($this));

			$array = array();
			$props = $class->getProperties();
			foreach( $props as $prop ) {
				// allow only public non-static:
				if ( !$prop->isPublic() || $prop->isStatic() ) continue;
				$array[$prop->getName()] = $prop->getValue($this);
			}
			return $array;
		}

		/**
			Returns an array with public properties + data properties as keys.
		*/
		public function toEntityArray() {
			return array_merge($this->data(), $this->toArray());
		}
		
		/**
			Returns an array with public and protected properties as keys.
			property_exists() is performed from inside the class so it allows us to see protected properties.
		*/
		public function toArrayWithProtected() {
			$class = get_class($this);
			$array = array();
			foreach( $this as $key=>$value ) {
				// allow only public:
				//print "property_exists(\"$class\", \"$key\"): ".property_exists($class, $key)."\n";
				if ( !property_exists($class, $key) ) continue;
				$array[$key] = $value;
			}
			return $array;
		}

		/**
			Returns an array with public properties, but only those set valid for UPDATE.
		*/
		public function toArrayForSave() {
			return Arr::keep($this->toArray(), $this->propertiesForSave());
		}

/* --------------------------------------------- */
/* Dumping
/* --------------------------------------------- */

		public function dump() {
			echo $this->toString();
		}

		/**
			Returns a string with a dumped list of properties.
		*/
		public function toString() {
			$data = $this->toArray();
			$s  = "<PRE>";
			$s .= get_class($this).":\n";
			foreach( $data as $prop => $val) {
				$s .= "\t$prop = \"".p($val)."\"\n";
			}
			$s .= "</PRE>";
			return $s;
		}

		/**
			Overrided magic function.
		*/
		public function __toString() {
			return $this->toString();
		}

/* --------------------------------------------- */
/* Object-DB mapping (ORM) and reflection
/* --------------------------------------------- */

		/**
			Sets whether this object has to be INSERTed or UPDATEd.
		*/
		public function setNew($isNew) {
			$this->_isNew = $isNew;
		}

		/**
			Returns an array of property names which are primary key.
		*/
		public function primaryKey() {
			$class = get_class($this);
			eval("\$pks = ".$class."::PRIMARY_KEY;");
			eval("\$d = ".$class."::ID_DELIMITER;");
			return explode($d, $pks);
		}

		/**
			Returns auto incremented property.
		*/
		public function autoIncrement() {
			$key = $this->annotation("AutoIncrement");
			if ( in_array(strtolower($key), array("", "null", "no", "false", "0")) ) return NULL;
			if ( $key ) return $key;

			// proceed with old style:
			$const = self::CONSTANT_AUTO_INCREMENT;
			eval("\$a = ".get_class($this)."::".$const.";");
			return $a;
		}

		/**
			Makes sure specified property exists and is public.
		*/
		public function hasProperty($propertyName) {
			if ( !property_exists(get_class($this), $propertyName) ) return false;
			$prop = new ReflectionProperty(get_class($this), $propertyName);
			return $prop->isPublic();
		}

		/**
			Makes sure specified method exists and is public.
		*/
		public function hasMethod($methodName) {
			if ( !method_exists($this, $methodName) ) return false;
			$method = new ReflectionMethod(get_class($this), $methodName);
			return $method->isPublic();
		}

		/**
			Returns all public properties of specified entity.
		*/
		public static function entityProperties($entityName, $ofThisEntityOnly=false) {
			eval("\$i = new ".$entityName."();");
			return $i->properties();
		}


		/**
			Returns all public properties of this entity.
		*/
		public function properties($ofThisEntityOnly=false) {
			if ( $ofThisEntityOnly && $this->_propertiesOfThisEntity ) return $this->_propertiesOfThisEntity;
			else if ( $this->_properties ) return $this->_properties;

			$class = new ReflectionClass(get_class($this));
			$array = array();
			$props = $class->getProperties();
			foreach( $props as $prop ) {
				// allow only public non-static:
				if ( !$prop->isPublic() || $prop->isStatic() ) continue;
				if ( $ofThisEntityOnly && $prop->getDeclaringClass() != $class ) continue;
				$array[] = $prop->getName();
			}
			if ( $ofThisEntityOnly ) return $this->_propertiesOfThisEntity = $array;
			return $this->_properties = $array;
		}

		/**
			Returns all public methods of this entity.
		*/
		public function methods($ofThisEntityOnly=false) {
			if ( $ofThisEntityOnly && $this->_methodsOfThisEntity ) return $this->_methodsOfThisEntity;
			else if ( $this->_methods ) return $this->_methods;

			$class = new ReflectionClass(get_class($this));
			$array = array();
			$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC & ~ReflectionMethod::IS_STATIC);
			foreach( $methods as $method ) {
				if ( $ofThisEntityOnly && $method->getDeclaringClass() != $class ) continue;
				$array[] = $method->getName();
			}
			if ( $ofThisEntityOnly ) return $this->_methodsOfThisEntity = $array;
			return $this->_methods = $array;
		}

		/**
			Returns all properties that were updated since last fetch.
			Called normally only in update();
		*/
		public function propertiesForSave() {
			$props = $this->properties();
			$props = array_flip($props);

			// compare current values with original values:
			foreach ( $props as $p => $v ) {
				if ( !isset($this->$p) ) unset($props[$p]);
			}

			// avoid saving magic field updatedOn:
			if ( $this->hasProperty(self::PROPERTY_UPDATED_ON) ) unset($props[self::PROPERTY_UPDATED_ON]);

			return array_keys($props);
		}

		/**
			Returns DB table name for this object based on its class name.
			By default transforms CamelCasedPHPClass names to lowercased_underscored_names.
			Override if specific mapping is used.
		*/
		public function tableName($forSave=false) {
			if ( $forSave ) return $this->tableNameForSave();
			return self::tableNameForEntity(get_class($this));
		}

		public function tableNameForSave() {
			return self::tableNameForEntity(get_class($this));
		}

		public static function tableNameForEntity($entity) {
			$tableName = $entity;
			$tableName = preg_replace("/(.+?)([A-Z0-9][a-z])/", "$1"."_".strtolower("$2"), $tableName);
			$tableName = preg_replace("/([a-z])([A-Z0-9])/", "$1"."_".strtolower("$2"), $tableName);
			return strtolower($tableName);
		}

/* --------------------------------------------- */
/* Pos support
/* --------------------------------------------- */

		/**
			Returns array of entity properties which have to be used when altering pos.
		*/
		public function posConditionProperties() {
			// return empty array by default:
			return array();
		}

		/**
			Returns array of SQL conditions which have to be used when altering pos.
		*/
		public function sqlPosConditions() {
			// by default return empty array:
			$props = $this->posConditionProperties();
			$posConditions = array();
			foreach ( $props as $p ) {
				$posConditions[] = $p."='".s($this->$p,1)."'";
			}

			return $posConditions;
		}

		/**
			Allocates pos for this item.
			Meant to be called in a transaction.
		*/
		protected function allocatePos() {
			//if ( !$this->hasProperty(self::PROPERTY_POS) ) return 0;
			$p = self::PROPERTY_POS;
			$posConditions = $this->sqlPosConditions();
			$q = "SELECT MAX(`".self::PROPERTY_POS."`)+1 FROM `".$this->tableName()."`".(sizeof($posConditions)?" WHERE (".implode(" AND ",$posConditions).")":"");
			if ( !($r = DB::q($q)) ) err("Could not execute query: $q.<br>".DB::err());
			list ( $pos ) = DB::row($r);
			if ( !$pos ) $pos = 1;
			$this->set($p, $pos);
			return $pos;
		}

		/**
			Updates pos this of this item.
			Updates all siblings accordinly.
			This is done in transaction.
		*/
		public function updatePos($pos) {
			$this->_logger->w(get_class($this)."->updatePos()");
			$this->_logger->indent();

			$p = self::PROPERTY_POS;
			$oldPos = $this->$p;
			$posConditions = $this->sqlPosConditions();

			try {
				// start transaction:
				DB::transactionStart();

				// trigger:
				$this->beforeUpdatePos();

				// check specified new pos is valid:
				if ( !$this->isValidPos($pos) ) {
					DB::transactionRollback();
					$msg = "Specified pos '".$pos."' is INVALID for ".$this;
					throw new EntityException($msg);
				}

				// As pos is UNIQUE - we have to first set pos of the item to somewhat UNIQUE.
				$q = "UPDATE `".$this->tableName(true)."` SET `$p`='0' WHERE ".$this->sqlWhereId();
				$this->_logger->w($q);
				DB::q($q);

				// move siblings either up or down:
				if ( $oldPos > $pos ) {
					// move affected siblings down:
					$q = "UPDATE `".$this->tableName(true)."` SET `$p`=`$p`+1 WHERE ".($posConditions?"(".implode(" AND ",$posConditions).") AND ":"")."`$p`<'$oldPos' AND `$p`>='".$pos."' ORDER BY `$p`";
					$this->_logger->w($q);
					DB::q($q);
				}
				else if ( $oldPos < $pos ) {
					// move affected siblings up:
					$q = "UPDATE `".$this->tableName(true)."` SET `$p`=$p-1 WHERE ".($posConditions?"(".implode(" AND ",$posConditions).") AND ":"")."`$p`>'$oldPos' AND `$p`<='".$pos."' ORDER BY `$p`";
					$this->_logger->w($q);
					DB::q($q);
				}

				// is pos to be last item?
				$lastPos = DB::selectOne("SELECT COUNT(".$p.") AS lastPos FROM `".$this->tableName()."`".(sizeof($posConditions)?" WHERE (".implode(" AND ",$posConditions).")":""));
				$lastPos = $lastPos['lastPos'];
				$this->_logger->w("Last pos: ".$lastPos.", pos: ".$pos);
				if ( $pos >= $lastPos ) $pos=$lastPos;

				// finally - set pos to this item:
				$q = "UPDATE `".$this->tableName(true)."` SET `$p`='".$pos."' WHERE ".$this->sqlWhereId();
				$this->_logger->w($q);
				DB::q($q);

				$this->$p = $pos;

				// trigger:
				$this->afterUpdatePos();

				DB::transactionCommit();
			}
			catch ( Exception $e ) {
				// we cancel transaction as exception is thrown:
				$t = DB::transactionRollback();
				$msg = get_class($this)."->updatePos() failed, transaction rolled back (".$t."): ".$e;
				$this->_logger->err($msg);
				$this->_logger->indent(-1);
				throw new EntityException($msg);
			}

			return $this->$p;
		}

		/**
			Trigger. Called in update pos cycle - before pos is updated.
			Through an exception to roll back the update.
		*/
		protected function beforeUpdatePos() {
			// do nothing by default
		}

		/**
			Trigger. Called in update pos cycle - after pos is updated.
			Through an exception to roll back the update.
		*/
		protected function afterUpdatePos() {
			// do nothing by default
		}

		public function isValidPos($pos=-1) {
			$p = self::PROPERTY_POS;

			$curPos = $this->$p;
			if ( $pos != -1 ) $this->$p = $pos;

			// if pos is not specified or is negative - this is invalid pos:
			if ( !$this->$p || $this->$p < 0 ) {
				$this->$p = $curPos;
				return false;
			}
			try {
				// check pos is in acceptable range:
				$posConditions = $this->sqlPosConditions();
				$maxPos = DB::selectOne("SELECT count(".$p.")+1 AS maxPos FROM `".$this->tableName()."`".(sizeof($posConditions)?" WHERE (".implode(" AND ",$posConditions).")":""));
				if ( $this->pos > $maxPos['maxPos'] ) {
					$this->$p = $curPos;
					return false;
				}
			}
			catch (Exception $e) {
				$msg = get_class($this)."->isValidPos() failed: ".$e;
				$this->_logger->err($msg);
				$this->$p = $curPos;
				throw new EntityException($msg);
			}

			$this->_logger->w("Pos ".$this->$p." is valid for ".$this);
			$this->$p = $curPos;
			return true;
		}

/* --------------------------------------------- */
/* DB interaction
/* --------------------------------------------- */

		/**
			Saves this object to database.
			It will INSERT a record if this is a new object - created via new operator.
			AUTO_INCREMENTted primary key property then will be initialized from DB.
			It will UPDATE a record if property(s) has been fetched from DB or created somehow neither via "new" operator approach.
			Returns true on success or false on failure.
			Trigger events: beforeSave, [beforeUpdate, [afterUpdate]], [beforeInsert, [afterInsert]], afterSave.
		*/
		public function save($withValidate=true, $withTriggers=true) {
			$this->_logger->w(get_class($this)."->save()");
			//$this->_logger->w($this->toArray());
			$this->_logger->indent();

			// is it INSERT or UPDATE?
			$wasNew = $this->_isNew;
			if( $withValidate ) $this->validate($this->_isNew);
			if ( $this->_isNew ) {
				$this->saveInsert($withTriggers);	// INSERT
			}
			else {
				$this->saveUpdate($withTriggers);	// UPDATE
			}

			$this->_logger->indent(-1);
			return $wasNew;
		}

		/**
			Same as save(), but saves this object in UPSERT manner.
			First fetches this object by id. If it exists in DB - updates.
			If this object is not found in DB - inserts it.
			All happens inside a transaction, so it is safe.
			Changes isNew() flag.
			Trigger events: beforeSave, [beforeUpdate, [afterUpdate]], [beforeInsert, [afterInsert]], afterSave.
		*/
		public function upsert($withValidate=true, $withTriggers=true) {
			$this->_logger->w(get_class($this)."->upsert()");
			//$this->_logger->w($this->toArray());
			$this->_logger->indent();

			// start transaction:
			$t = DB::transactionStart();
			$this->_logger->w("Transaction started: ".$t);

			$item = DB::fetchById(get_class($this), $this->id());
			if ( $item ) $this->setNew(false);
			else $this->setNew(true);
			$this->save();

			// end transaction:
			$t = DB::transactionCommit();
			$this->_logger->w("Transaction commited: ".$t);

			$this->_logger->indent(-1);
			return $wasNew;
		}
		/**
			Forces saving this item as new record.
		*/
		protected function saveInsert($withTriggers=true) { 
			$this->_logger->w("Saving new item: ".get_class($this));
			$this->_logger->indent();

			// start transaction:
			$t = DB::transactionStart();
			$this->_logger->w("Transaction started: ".$t);

			try {
				// before save:
				$this->_logger->w(get_class($this)."->beforeSave() started.");
				$this->_logger->indent();
				if( $withTriggers ) $this->beforeSave();
				$this->_logger->indent(-1);
				$this->_logger->w(get_class($this)."->beforeSave() complete.");
				
				// before insert:
				$this->_logger->w(get_class($this)."->beforeInsert() started.");
				$this->_logger->indent();
				if( $withTriggers ) $this->beforeInsert();
				$this->_logger->indent(-1);
				$this->_logger->w(get_class($this)."->beforeInsert() complete.");

				// insert:
				$this->_logger->w(get_class($this)."->insert() started.");
				$this->_logger->indent();
				$this->insert();
				$this->_logger->indent(-1);
				$this->_logger->w(get_class($this)."->insert() complete.");

				// after insert:
				$this->_logger->w(get_class($this)."->afterInsert() started.");
				$this->_logger->indent();
				if( $withTriggers ) $this->afterInsert();
				$this->_logger->indent(-1);
				$this->_logger->w(get_class($this)."->afterInsert() complete.");

				// after save:
				$this->_logger->w(get_class($this)."->afterSave() started.");
				$this->_logger->indent();
				if( $withTriggers ) $this->afterSave();
				$this->_logger->indent(-1);
				$this->_logger->w(get_class($this)."->afterSave() complete.");
			}
			catch (Exception $e) {
				// we cancel transaction as exception is thrown:
				$t = DB::transactionRollback();
				$msg = get_class($this)."->saveInsert() failed, transaction rolled back (".$t."): ".$e;
				$this->_logger->err($msg);
				$this->_logger->indent(-1);
				throw new EntityException($msg);
			}

			// end transaction:
			$t = DB::transactionCommit();
			$this->_logger->w("Transaction commited: ".$t);

			$this->_logger->indent(-1);
		}

		/**
			Forces saving this item as existing record.
		*/
		protected function saveUpdate($withTriggers=true) {
			$this->_logger->w("Saving existing item: ".get_class($this));
			$this->_logger->indent();

			// start transaction:
			$t = DB::transactionStart();
			$this->_logger->w("Transaction started: ".$t);

			try {
				// before save:
				$this->_logger->w(get_class($this)."->beforeSave() started.");
				$this->_logger->indent();
				if( $withTriggers ) $this->beforeSave();
				$this->_logger->indent(-1);
				$this->_logger->w(get_class($this)."->beforeSave() complete.");

				// before update:
				$this->_logger->w(get_class($this)."->beforeUpdate() started.");
				$this->_logger->indent();
				if( $withTriggers ) $this->beforeUpdate();
				$this->_logger->indent(-1);
				$this->_logger->w(get_class($this)."->beforeUpdate() complete.");

				// update:
				$this->_logger->w(get_class($this)."->update() started.");
				$this->_logger->indent();
				$this->update();
				$this->_logger->indent(-1);
				$this->_logger->w(get_class($this)."->update() complete.");

				// after update:
				$this->_logger->w(get_class($this)."->afterUpdate() started.");
				$this->_logger->indent();
				if( $withTriggers ) $this->afterUpdate();
				$this->_logger->indent(-1);
				$this->_logger->w(get_class($this)."->afterUpdate() complete.");

				// after save:
				$this->_logger->w(get_class($this)."->afterSave() started.");
				$this->_logger->indent();
				if( $withTriggers ) $this->afterSave();
				$this->_logger->indent(-1);
				$this->_logger->w(get_class($this)."->afterSave() complete.");
			}
			catch (Exception $e) {
				// we cancel transaction as exception is thrown:
				$t = DB::transactionRollback();
				$msg = get_class($this)."->saveUpdate() failed, transaction rolled back (".$t."): ".$e;
				$this->_logger->err($msg);
				$this->_logger->indent(-1);
				throw new EntityException($msg);
			}

			// end transaction:
			$t = DB::transactionCommit();
			$this->_logger->w("Transaction commited: ".$t);
			$this->_logger->indent(-1);
		}

		/**
			Returns SQL of PK fields suitable for WHERE statement.
		*/
		public function sqlWhereId($tableAlias=NULL) {
			$pks = $this->primaryKey();
			$wherePKS = array();
			foreach ( $pks as $key ) {
				$wherePKS[] = ($tableAlias?"`".$tableAlias."`.":"")."`".$key."`='".s($this->$key, 1)."'";
			}
			return implode(" AND ", $wherePKS);
		}

		/**
			DB insert process.
		*/
		protected function insert() {
			// take current data that is set:
			$data = $this->toArrayForSave();
			if ( !sizeof($data) ) return;

			// unset AUTO_INCREMENT properties:
			$autos = t(explode(",", $this->autoIncrement()));
			foreach ( $autos as $key ) {
				unset($data[$key]);
			}
			
			$sql = "INSERT `".$this->tableName(true)."` SET ".DB::sqlSet($data);
			DB::q($sql);

			// if AUTO_INCREMENT is supported - set the generated id:
			$autoPK = $this->autoIncrement();
			if ( $autoPK ) $this->$autoPK = DB::id();

			// set this item is not new anymore:
			$this->setNew(false);
		}

		/**
			Trigger. Called in save cycle - before beforeInsert() and beforeUpdate().
		*/
		protected function beforeSave() {
			// do nothing by default
		}

		/**
			Trigger. Called in save cycle - after afterInsert() or afterUpdate().
			This is the same transaction, so this still can roll everything back.
		*/
		protected function afterSave() {
			// if this entity has updatedOn - apply it to this item:
			$p = self::PROPERTY_UPDATED_ON;
			if ( $this->hasProperty($p) ) {
				$lastSave = DB::selectOne("SELECT `".$p."` FROM `".$this->tableName(true)."` WHERE ".$this->sqlWhereId());
				$this->$p = $lastSave[$p];
			}
		}

		/**
			Trigger. Called before insert() (single transaction).
			By default we allocate pos here for new inserts in pos-enabled entities.
		*/
		protected function beforeInsert() {
			if ( $this->hasProperty(self::PROPERTY_POS) ) {
				// if pos is not set - allocate it:
				$p = self::PROPERTY_POS;
				if ( !$this->$p ) $this->allocatePos();
			}
			//return $this;
		}

		/**
			Trigger. Called after insert() (single transaction).
		*/
		protected function afterInsert() {
			// do nothing by default:
		}

		/**
			DB update process.
		*/
		protected function update() {
			// take current data that is set:
			$data = $this->toArrayForSave();

			// avoid saving createdOn:
			if ( $this->hasProperty(self::PROPERTY_CREATED_ON) ) unset($data[self::PROPERTY_CREATED_ON]);
			
			if ( !sizeof($data) ) return;

			$sql = "UPDATE `".$this->tableName(true)."` SET ".DB::sqlSet($data)." WHERE ".$this->sqlWhereId();
			DB::q($sql);
		}

		/**
			Trigger. Called before update() (single transaction).
			By default we process pos-enabled records here.
		*/
		protected function beforeUpdate() {
			// process pos:
			if ( $this->hasProperty(self::PROPERTY_POS) ) {
				
				// check if posConditionProperties are there:
				$props = $this->posConditionProperties();
				$pos = self::PROPERTY_POS;
				if ( sizeof($props) ) {
					// we need to check the pos condition properties are not changed - otherwise we'll need to update pos:
					$old = DB::fetchOne(get_class($this), "SELECT $pos, ".implode(", ", $props)." FROM `".$this->tableName(true)."` WHERE ".$this->sqlWhereId());
					$isChanged = false;
					foreach ( $props as $p ) {
						if ( $this->$p != $old->$p ) {
							$isChanged = true;
							break;
						}
					}
					if ( $isChanged ) {
						// yes, values of pos-condition-properties have changed; let's update pos properly:
						// 1. update pos to be last in the new place (allocate pos):
						$this->set($pos, $this->allocatePos());
						// 2. move up siblings in old place
						$posConditions = $old->sqlPosConditions();
						DB::q("UPDATE `".$old->tableName(true)."` SET ".$pos."=".$pos."-1 WHERE "
							.($posConditions?"(".implode(" AND ",$posConditions).") AND ":"")
							.$pos.">'".$old->$pos."' ORDER BY ".$pos);
					}
				}
			}
		}

		/**
			Trigger. Called after update() (single transaction).
		*/
		protected function afterUpdate() {
			// do nothing by default
		}

		/**
			Deletes this record in DB.
			This is not usually called directly, but overriden to add specific logic in deleting process.
			The whole deleting process is a transaction with beforeDelete(), delete() and afterDelete() calls.
		*/
		public function delete() {
			try {
				// delete record itself:
				DB::q("DELETE FROM `".$this->tableName(true)."` WHERE ".$this->sqlWhereId());
			}
			catch (DBException $e) {
				$err = "Could not delete record from ".$this->tableName(true).": ".$e;
				$this->_logger->err($err);
				throw new EntityException($err);
			}
		}

		/**
			Trigger BEFORE DELETE.
			This is not usually called directly, but overriden to add specific logic in deleting process.
			The core deleting process is done in a transaction with beforeDelete(), delete() and afterDelete() calls.
		*/
		public function beforeDelete($isConfirmed=false) {
			// do nothing by default
		}

		/**
			Trigger AFTER DELETE.
			This is not usually called directly, but overriden to add specific logic in deleting process.
			The core deleting process is done in a transaction with beforeDelete(), delete() and afterDelete() calls.
			By default we process 'pos' column here.
		*/
		public function afterDelete() {
			// if this item supports pos - update below siblings:
			if ( !$this->hasProperty(self::PROPERTY_POS) ) return $this;

			$p = self::PROPERTY_POS;

			// Move below siblings up:
			$posConditions = $this->sqlPosConditions();
			DB::q("UPDATE `".$this->tableName(true)."` SET ".$p."=".$p."-1 WHERE "
				.($posConditions?"(".implode(" AND ",$posConditions).") AND ":"")
				."$p>='".$this->$p."' ORDER BY $p");
		}

		/**
			Trigger BEFORE DELETE TRANSACTION.
			This is not usually called directly, but overriden to add specific logic in deleting process.
			The core deleting process is done in a transaction with beforeDelete(), delete() and afterDelete() calls.
			Sometimes we need actions out of this transaction.
			This method is called to process any activity just BEFORE the delete transaction is started.
		*/
		public function beforeDeleteTransaction() {
		}

		/**
			Trigger AFTER DELETE TRANSACTION.
			This is not usually called directly, but overriden to add specific logic in deleting process.
			The core deleting process is done in a transaction with beforeDelete(), delete() and afterDelete() calls.
			Sometimes we need actions out of this transaction.
			This method is called to process any activity just AFTER the delete transaction is successfully committed.
		*/
		public function afterDeleteTransaction() {
		}

		/**
			Refetches this object from database.
			This is usefull to get item updated with insert/update triggers.
		*/
		public function refresh() {
			$sql = "SELECT * FROM `".$this->tableName()."` WHERE ".$this->sqlWhereId();
			$this->_logger->w("Refresh for ".get_class($this).": ".$sql);
			$item = DB::fetchOne(get_class($this), $sql);
			$this->applyArray($item->toArray());
		}


/* --------------------------------------------- */
/* Validation
/* --------------------------------------------- */

		/**
			Called in beforeSave() to validate this record.
			Should throw an exception if the record does not validate.
		*/
		protected function validate($isNew) {
			// by default - split validation into insert/update situation:
			if ( $isNew ) $this->validateForInsert();
			else $this->validateForUpdate();
		}


		/**
			Called from validate() before insert to validate this record is proper.
			Should throw an EntityValidationException if the record does not validate.
		*/
		protected function validateForInsert() {
			// do nothing by default
		}

		/**
			Called from validate() before update to validate this record is proper.
			Should throw an EntityValidationException if the record does not validate.
		*/
		protected function validateForUpdate() {
			// do nothing by default
		}

/* --------------------------------------------- */
/* Utility functions
/* --------------------------------------------- */

		static public function cache($item) {
			$entity = get_class($item);
			$key = $entity.".".$item->id();
			Entity::$_cache[$key] = $item;
		}

		static public function fromCache($key) {
			return Entity::$_cache[$key];
		}

		static public function fetchById($id) {
			$entity = get_called_class();
			
			// check cache:
			$key = $entity.".".$id;
			if ( Entity::$_cache[$key] ) return Entity::$_cache[$key];

			Entity::$_cache[$key] = DB::fetchById($entity, $id);
			return Entity::$_cache[$key];
		}

		static public function fetch($sql) {
			return DB::fetch(get_called_class(), $sql);
		}

		public function isIn($list) {
			if ( !is_array($list) ) return false;
			foreach ( $list as $item ) {
				if ( $item->id == $this->id && get_class($item) == get_class($this) ) return true;
			}
			return false;
		}

		public static function item($entity, $array=NULL, $parentAction=NULL) {
			///require_once($entity.".php");
			eval("\$item = new ".$entity."(\$array, \$parentAction);");
			return $item;
		}

/* --------------------------------------------- */
/* Annotations & Web-form functions
/* --------------------------------------------- */

		/*public static function info($entityName) {
			$entityInfo = new EntityInfo($entityName);
			return $entityInfo;
		}*/

		/**
			Precesses form by saving (insert or update) an obejct described in the form.
			By default - will redirect to URL from @FormURL or form[Entity::PARAM_FORM_URL].
		*/
		public static function processForm($entityName, $params=NULL, $parentAction=NULL, $noRedirect=false) {
			if ( !$params ) $params = sizeof($_POST)?$_POST:$_GET;
			// trim params:
			$params = t($params);

			// remember in session:
			if ( !$params['pt'] ) $params['pt'] = uniqid();
			$_SESSION[$params['pt']] = $params;

			// create an item:
			$item = Entity::item($entityName, $params, $parentAction);
			$wasNew = $item->isNew();
			///$user->id = NULL;		// always new registration

			// saving:
			try {
				$item->save();
			}
			catch(EntityException $e) {
				if ( $wasNew ) $errMsg = "".$item->annotation("InsertErrMsg");
				else $errMsg = "".$item->annotation("UpdateErrMsg");
				
				$_SESSION[$params['pt']]['errMsg'] = $errMsg ? $errMsg : $e->message;
				$_SESSION[$params['pt']]['object'] = $item;

				if ( $noRedirect ) {
					// just forward error
					throw new EntityException($_SESSION[$params['pt']]['errMsg'], $e->errCode);
				}
				else {
					$url = $params[Entity::PARAM_FORM_URL];
					if ( !$url ) $url = "".$item->annotation("FormURL");
					if ( !$url ) $url = $_SERVER['REQUEST_URI'];

					$url = URL::appendParam($url, "err", $e->errCode);
					$url = URL::appendParam($url, "pt", $params['pt']);

					go($url);
				}
			}

			// success
			if ( $noRedirect ) {
				return $item;
			}

			$url = $params[Entity::PARAM_FORM_SAVED_URL];
			if ( !$url ) {
				if ( $wasNew ) $url = "".$item->annotation("AfterInsertURL");
				else $url = "".$item->annotation("AfterUpdateURL");
			}
			if ( !$url ) $url = $_SERVER['REQUEST_URI'];

			$url = URL::removeParams($url, array("pt", "err"));
			$url = URL::appendParam($url, "s", "1");
			go($url);

			// attach image:
			/*try {
				Uploader::upload("UserImage", "UserImage".$_POST['pt'], array("parentId"=>$user->id), false);
			}
			catch ( EntityException $e ) {
				// error during saving:
				$_SESSION[$_POST['pt']]['errMsg'] = $e->message;
				$_SESSION[$_POST['pt']]['object'] = $user;

				// do not return user back - just skip...
				//go(PublicPage::REGISTRATION."?err=".$e->errCode."&pt=".$_POST['pt']);
			}

			$user->sendRegistrationWithEmailConfirmation();
			$user->sendSMSConfirmation();

			// log in:
			User::login($user);*/
		}

		public function classFile() {
			$file = str_replace('\\', DIRECTORY_SEPARATOR, get_class($this));
			$file = getcwd().DIRECTORY_SEPARATOR.E7::PATH."entities".DIRECTORY_SEPARATOR.$file . ".".E7::PHP_FILE_EXT;
			return $file;
		}

		public function annotations() {
			if ( $this->_annotations ) return $this->_annotations;
			return $this->_annotations = Annotation::parseObject($this);
		}

		public function propertyAnnotation($propName, $annName) {
			$ans = $this->annotations();
			if ( !$ans[T_VARIABLE] || !$ans[T_VARIABLE][$propName] ) return NULL;

			// annName can be an array:
			$names = t(explode(",", $annName));
			
			$ans = $ans[T_VARIABLE][$propName];
			foreach ( $ans as $an ) {
				foreach ( $names as $name ) {
					if ( $an->name == $name ) return $an;
				}
			}
			return NULL;
		}

		public function methodAnnotation($methodName, $name) {
			$ans = $this->annotations();
			if ( !$ans[T_FUNCTION] || !$ans[T_FUNCTION][$methodName] ) return NULL;
			
			$ans = $ans[T_FUNCTION][$methodName];
			foreach ( $ans as $an ) {
				if ( $an->name == $name ) return $an;
			}
			return NULL;
		}

		public function annotation($name) {
			$ans = $this->annotations();
			if ( !$ans[T_CLASS] ) return NULL;
			
			$ans = $ans[T_CLASS];
			foreach ( $ans as $an ) {
				if ( $an->name == $name ) return $an;
			}
			return NULL;
		}

		public function formName($view=0) {
			return "form".get_class($this);
		}

		public static function showSessionError() {
			if ( !(isset($_GET['err']) && $_GET['pt'] && $_SESSION[$_GET['pt']] && $_SESSION[$_GET['pt']]['errMsg']) ) return;
?>
<div class="err"><?= $_SESSION[$_GET['pt']]['errMsg'] ?></div>
<?
		}

		public static function showSessionForm($entityName, $view=0) {
			if ( isset($_GET['err']) && $_GET['pt'] && $_SESSION[$_GET['pt']] && is_object($_SESSION[$_GET['pt']]['object']) ) $item = $_SESSION[$_GET['pt']]['object'];
			else $item = Entity::item($entityName);
			
			$item->showForm($view);
		}

		public function showForm($view=0) {
			//da($this->annotations());
			
			$this->showFormHeader($view);

			// iterate all props:
			$props = $this->properties();
			foreach ( $props as $prop ) {
				$propView = $this->propertyAnnotation($prop, "View");
				if ( $propView ) $propView = t(explode(",", "".$propView));
				if ( !is_array($propView) ) $this->showFormField($prop, $view);
				else if ( is_array($propView) && in_array($view, $propView) ) $this->showFormField($prop, $view);
			}

			$this->showFormSubmit($view);
			$this->showFormFooter($view);
		}

		static protected function htmlList($view=0, $items=NULL, $id=NULL, $css=NULL) {
			ob_start();
			eval(get_called_class()."::showList(\$view, \$items, \$id, \$css);");
			$str = ob_get_contents();
			ob_end_clean();
			return $str;
		}

		/**
			Fetches a list of items of static entity.
			By default fetches all items with no specific order.
		*/
		public static function fetchList($view=NULL) {
			$fake = Entity::item(get_called_class());
			return DB::fetch(get_called_class(), "SELECT * FROM `".$fake->tableName()."`");
		}

		/**
			Fetches an item of static entity by id.
		*/	
		public static function fetchItem($id, $view=NULL){
			return DB::fetchById(get_called_class(), $id);
		}

		/**
			Shows list of items.
			Fetches items if needed.
		*/
		public static function showList($view=NULL, $items=NULL, $id=NULL, $css=NULL) {
			if ( !$items ) eval("\$items = ".get_called_class()."::fetchList(\$view);");

			eval(get_called_class()."::viewList(\$view, \$items, \$id, \$css);");
		}

		/**
			Fetches and shows an item by id.
			Returns fetched item.
		*/
		public static function showItem($id, $view=NULL) {
			eval("\$item = ".get_called_class()."::fetchItem(\$id, \$view);");
			$item->view($view);
			return $item;
		}

		/**
			View list of items.
			No fetching from DB.
		*/
		public static function viewList($view=NULL, $items=NULL, $id=NULL, $css=NULL) {
			if ( !$id ) eval("\$id = ".get_called_class()."::htmlListId(\$view);");

			$allCSS = array();
			$item = Entity::item(get_called_class());
			if ( $item->annotation("WithListViewCSS") ) $allCSS[] = "view".$view;
			if ( !$css ) eval("\$css = ".get_called_class()."::htmlListCSS(\$view);");
			if ( $css ) $allCSS[] = $css;
?>
<ul<?= ($id?" id='".$id."'":"").(sizeof($allCSS)>0?" class='".implode(" ", $allCSS)."'":"") ?>>
<?
			foreach ( $items as $item ) {
				$item->viewListItem($view);
			}
?>
</ul>
<?
		}

		/**
			Shows this item inside a list.
			Usualliy this is called from ::showList().
			No fetching from DB.
		*/
		public function viewListItem($view=0, $id=NULL, $css=NULL) {
			$id = $id ? $id : $this->htmlListItemId($view);
			$css = $css ? $css : $this->htmlListItemCSS($view);

			$allCSS = array();
			if ( $this->annotation("WithListItemViewCSS") ) $allCSS[] = "view".$view;
			if ( $css ) $allCSS[] = $css;
?>
<li<?= ($id?" id='".$id."'":"").(sizeof($allCSS)>0?" class='".implode(" ", $allCSS)."'":"") ?>>
<?
			$this->view($view);
?>
</li>
<?
		}

		/**
			Shows an item.
		*/
		public function view($view=0) {
			print $this->toString();
		}

		/**
			Return HTML element identifier for lists (<ul>).
		*/
		public static function htmlListId($view=0) {
			// first - try annotation:
			$item = Entity::item(get_called_class());
			$id = $item->annotation("HTMLListId");
			if ( $id ) return $id;

			return get_called_class()."s"; 
		}

		/**
			Return HTML element identifier for list items (<li>).
		*/
		public function htmlListItemId($view=0) {
			$id = $this->annotation("HTMLId");
			$id = $id ? $id : get_class($this);

			return $id.$this->id(); 
		}

		/**
			Return CSS selector for lists (<ul>).
		*/
		public function htmlListCSS($view=0) {
			// first - try annotation:
			$item = Entity::item(get_called_class());
			$css = $item->annotation("HTMLListCSS");
			if ( $css ) return $css;

			return NULL; 
		}

		/**
			Return CSS selector for lists (<ul>).
		*/
		public function htmlListItemCSS($view=0) {
			// first - try annotation:
			$css = $this->annotation("HTMLListItemCSS");
			if ( $css ) return $css;

			return NULL; 
		}

		public function html($view=0) {
			ob_start();
			$this->show($view);
			$str = ob_get_contents();
			ob_end_clean();
			return $str;
		}

		protected function showFormHeader($view=0) {
			if ( !$_GET['pt'] ) $_GET['pt'] = uniqid();

			$formURL = $this->annotation("FormURL");
?>
<SCRIPT LANGUAGE="JavaScript">
<!--
document.writeln("<"+"f"+"o"+"rm met"+"hod='po"+"st' name='<?= $this->formName() ?>' act"+"io"+"n='<?= WebPage::maskedFormURL($formURL) ?>' onSubmit='return Form.check(this)'>");
//-->
</SCRIPT>
<input type="hidden" name="pt" value="<?= p($_GET['pt']) ?>">
<table class="form">
<?
		}

		public function showFormSubmit($view=0) {
			$submitValue = $this->annotation("SubmitValue");
			if ( $this->_isNew ) $submitValue = $this->annotation("InsertSubmitValue");
			else if ( !$submitValue ) $submitValue = $this->annotation("UpdateSubmitValue");
?>
<tr>
	<th>&nbsp;</th>
	<td><input type="submit" class="btn" value="<?= p($submitValue?$submitValue:"Сохранить") ?>"></td>
</tr>
<?
		}

		protected function showFormFooter($view=0) {
?>
</table>
<SCRIPT LANGUAGE="JavaScript">
<!--
document.writeln("</"+"f"+"or"+"m>");
//-->
</SCRIPT>
<?
		}

		protected function showFormField($prop, $view=0) {
			if ( $this->propertyAnnotation($prop, "PrimaryKey,PK") ) $this->showFormPrimaryKey($prop);
			else if ( $this->propertyAnnotation($prop, "Password") ) $this->showFormPassword($prop);
			else if ( $this->hasMethod("showForm_".$prop) ) {
				eval("\$this->showForm_".$prop."();");
			}
			else $this->showFormText($prop);
		}

		protected function showFormPrimaryKey($prop) {
?>
<input type="hidden" name="<?= $prop ?>" value="<?= p($this->$prop) ?>">
<?
		}

		protected function showFormPassword($prop) {
			$this->showFormText($prop, array("type"=>"password"));
			$prop2 = $prop."2";
			$this->showFormText($prop2, array("type"=>"password","value"=>$this->$prop));
		}

		protected function showFormText($prop, $params=NULL) {
			$name = $this->propertyAnnotation($prop, "Name");
			if ( !$name ) $name = $prop;

			if ( !is_array($params) ) $params = array();
			$params["name"] = $prop;
			$hint = $this->propertyAnnotation($prop, "Hint");
			$params["hint"] = $hint?$hint:$name;
			if ( $this->$prop ) $params["value"] = $this->$prop;
			$length = intval("".$this->propertyAnnotation($prop, "Length"));
			if ( $length ) $params["maxlength"] = $length;
			$required = $this->propertyAnnotation($prop, "Required");
			if ( $required ) {
				$params["validation"] = $required->args[0] ? $required->args[0] : $name;
			}

			$attrs = array();
			foreach ( $params as $key => $value ) {
				$attrs[] = $key."=\"".p($value)."\"";
			}
?>
<tr>
	<th><?= $name ?></th>
	<td><div class="i"><input <?= implode(" ", $attrs) ?>></div></td>
</tr>
<?
		}
	};

	/**
		Exception generated while working with Entity.
		TODO: implement to all Entity methods.
	*/
	class EntityException extends E7Exception {
		var $errCode;	// error code for passing via URL, can be treated by client by its own way
		var $message;	// full message
	}

	class EntityConfirmationException extends EntityException {
	}

	class EntityValidationException extends EntityException {
		public function EntityValidationException($message, $errCode) {
			$this->errCode = $errCode;
			$this->message = $message;
		}
	}
?>