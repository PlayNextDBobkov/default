<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/

	/**
		This is a record in an hierarchy.
		The hierarchy is described with a unique codes like this:
			001 Record 1
				001.001 First child of Record 1
				001.002 Second child of Record 1
					001.002.001 First child of Second child of Record 1
			002 Record 2
			...
		Each level of the hierarchy has "width". This specifies how many items are allowed on one level.
		In the example above - the level width is 3 symbols, so 999 items are alowed.
		In case of code with level width 6, the Record 1 will look like 000001 and 999 999 items will be allowed on one level.
		In DB the code is stored without dots. Levels are zero-based indexed, so 001 is level 0, 001.001 - level 1.
		Depending on the required level width and maximum number of levels in the hierarchy - set appropriate size for the DB record.
		Eg: VARCHAR(32) may store, say, up to 8 levels with up to 9 999 items in each.
		Eg: VARCHAR(64) may store, say, up to 16 levels with up to 9 999 items in each.
		Eg: VARCHAR(64) may alternatively store up to 8 levels with up to 99 999 999 items in each.
	*/
	class CodeEntity extends Entity {
		// default width of level (override id needed):
		const LEVEL_WIDTH		= 4;	// maximum 4 chars (9999 items) per level
		// default maximum amount of levels (override id needed):
		const MAX_LEVEL			= 15;	// [0-15] 16 levels x 4 = 64 chars needed to store maximum level code

		// main property of CodeEntity
		var $code;

		// this is used when updating:
		private $_oldItem = false;

		protected $_isCodeTriggerSupportDisabled = false;

/* --------------------------------------------- */
/* DB interaction
/* --------------------------------------------- */

		/**
			Overriden!
			Allocates code if it is not specified or moves siblings if code specified.
		*/
		public function beforeInsert() {
			if ( !$this->_isCodeTriggerSupportDisabled ) {
				$this->_logger->w("CodeEntity::beforeInsert() for ".get_class($this));
				$this->_logger->indent();
				if ( !$this->code ) {
					// INSERT without code - take the new one on the root level:
					$this->_logger->w("Code is not specified - will allocate one.");
					if ( !$this->allocateRootCode() ) {
						// cancel insert:
						$msg = "No space for a new item on level 0.";
						$this->_logger->err($msg);
						$this->_logger->indent(-1);
						throw new CodeEntityException($msg);
					}
				}
				else {
					$this->_logger->w("Insert with code: ".$this->code.".");
					//DB::$log[] = "insert with a code ".$this->code;
					// check we are inserting a valid code:
					if ( !$this->isValidCode() ) {
						// cancel insert:
						$msg = "Specified code is invalid: ".$this->code;
						$this->_logger->err($msg);
						$this->_logger->indent(-1);
						throw new CodeEntityException($msg);
					}

					// new item with code - move down all siblings below (if any):
					//DB::$log[] = "move siblings down";
					//$this->_logger->w("Moving siblings down...");

					$this->moveDown();	// move siblings down
				}

				$this->_logger->indent(-1);
			}

			// call parent beforeInsert():
			parent::beforeInsert();
		}

		/**
			Overriden!
			Moves siblings properly.
		*/
		public function beforeUpdate() {
			if ( !$this->_isCodeTriggerSupportDisabled ) {
				$this->_logger->w("CodeEntity::beforeUpdate()");
				$this->_logger->indent();

				if ( !in_array("code", $this->propertiesForSave()) ) {
						$this->_logger->w("Code is not a properties for update.");
						$this->_logger->indent(-1);
						return;
				}

				if ( !$this->code ) {
					$this->_logger->w("Code is not specified - allocate new one.");
					// code is not specified - take the new one on the root level:
					if ( !$this->allocateRootCode() ) {
						// cancel update:
						$msg = "No space for new element at code level 0";
						$this->_logger->err($msg);
						$this->_logger->indent(-1);
						throw new CodeEntityException($msg);
					}
				}

				// fetch and store old item and count max level its children:
				$this->_logger->w("Fetching old item by id ".$this->id());
				//$this->_oldItem = DB::fetchById(get_class($this), $this->id());
				$this->_oldItem = DB::fetchOne(get_class($this),
					"SELECT c.*, (SELECT MAX(LENGTH(c2.code))/".$this->levelWidth()."-1 FROM ".$this->tableName()." c2 WHERE c2.code LIKE CONCAT(c.code,'".str_repeat("_",$this->levelWidth())."%')) AS maxLevel FROM ".$this->tableName()." c"
					." WHERE ".$this->sqlWhereId("c"));
				if ( $this->_oldItem->data("maxLevel") ) {
					$this->_logger->w("Max level of item children: ".$this->_oldItem->data("maxLevel"));
					$wouldLevel = $this->_oldItem->data("maxLevel") - $this->_oldItem->codeLevel() + $this->codeLevel();
					if ( $wouldLevel > $this->maxLevel() ) {
						// cancel update:
						$msg = "Update would exceed max hierarchy level. Requested level: ".($wouldLevel+1).". Max allowed level: ".($this->maxLevel()+1).".";
						$this->_logger->err($msg);
						$this->_logger->indent(-1);
						throw new CodeEntityException($msg);
					}
				}

				// check if code changed and return if not:
				if ( $this->codeEquals($this->_oldItem->code) ) {
					$this->_logger->w("Code is NOT changed");
					$this->_logger->indent(-1);
					// we exit here:
					return;
				}

				// mark old item and all its children to a special '0000' code to prevent duplicate keys:
				$this->_logger->w("Mark ".$this->_oldItem->code."* as 0000*");
				$this->_oldItem->move(str_repeat("0", $this->_oldItem->levelWidth()));

				// is parent changed?
				if ( $this->isCodeSibling($this->_oldItem->code) ) {
					$this->_logger->w("Parent is unchanged");
					// no! are we moving up or down?
					$v = $this->codeValue();
					if ( $v < $this->_oldItem->codeValue() ) {
						// we are moving up, move siblings down:
						$this->_logger->w("Move down [".$this->code.";".$this->_oldItem->code."]");
						$this->moveDown($this->_oldItem->code);	// existing item with $this->code and all below siblings are moved down in DB
					}
					else {
						// we are moving down, move siblings up:
						$this->_logger->w("Move up [".$this->_oldItem->code.";".$this->code."]");
						$this->_oldItem->moveUp($this->code);	// existing item with $this->code and all below siblings are moved up in DB
					}
				}
				else {
					// make space for new code - move down all the siblings below:
					$this->_logger->w("Move down [".$this->code.";]");
					$this->moveDown();	// existing item with $this->code and all below siblings are moved down in DB

					// move down could move old place down - check this:
					if ( $this->codeLevel() < $this->_oldItem->codeLevel() ) {
						// is new place upper or lower?
						$pCode = substr($this->_oldItem->code, 0, strlen($this->code));
						$this->_logger->w("Item moving left, co-level values: old: ".$this->codeValue($pCode).", new: ".$this->codeValue());
						if ( $this->codeValue() <= $this->codeValue($pCode) ) {
							// old item has moved down - we need to set this in code:
							$bCode = $this->_oldItem->code;
							$this->_oldItem->codePlus($this->codeLevel());
							$this->_logger->w("old->code has been moved down from ".$bCode." to ".$this->_oldItem->code." as it was moved down in DB");
						}
					}
				}

				// before going to update - check we are updating with a valid code:
				if ( !$this->isValidCode() ) {
					// cancel update:
					$msg = "Specified code is invalid: ".$this->code." for ".$this->id();
					$this->_logger->err($msg);
					$this->_logger->indent(-1);
					throw new CodeEntityException($msg);
				}

				$this->_logger->indent(-1);
			}

			// call parent beforeUpdate():
			parent::beforeUpdate();
		}

		/**
			Overriden!
			Moves siblings properly.
		*/
		public function afterUpdate() {
			if ( !$this->_isCodeTriggerSupportDisabled ) {
				$this->_logger->w("CodeEntity::afterUpdate()");
				$this->_logger->indent();

				if ( !in_array("code", $this->propertiesForSave()) ) {
						$this->_logger->w("Code is not a properties for update.");
						$this->_logger->indent(-1);
						return;
				}

				if ( !$this->codeEquals($this->_oldItem->code) ) {
					// code was changed!
					// move children to the new position:
					eval("\$o = new ".get_class($this)."();");
					$this->_logger->w("Move children of 0000 to parent ".$this->code);
					$o->code = str_repeat("0", $this->levelWidth());
					$o->moveChildren($this->code);

					// if parent has changed - move up the siblings at the old place:
					if ( !$this->isCodeSibling($this->_oldItem->code) ) {
						$this->_logger->w("Parent has changed, move up [".$this->_oldItem->code.";]");
						$this->_oldItem->moveUp();	// old item already does NOT exist, so only below siblings are moved up in DB

						// the last operation could update the code of this item if it had became child of a below sibling
						if ( $this->codeLevel() > $this->_oldItem->codeLevel() ) {
							// is new parent upper or lower?
							$pCode = substr($this->code, 0, strlen($this->_oldItem->code));
							$this->_logger->w("Item moved right, old code: ".$this->_oldItem->code.", new parent at the same level: ".$pCode);
							if ( $this->_oldItem->codeValue() < $this->_oldItem->codeValue($pCode) ) {
								// this item has moved up - we need to set this in code:
								$bCode = $this->code;
								$this->codeMinus($this->_oldItem->codeLevel());
								$this->_logger->w("this->code has been moved up from ".$bCode." to ".$this->code." as it was moved up in DB");
							}
						}
					}
				}

				$this->_logger->indent(-1);
			}

			// call parent afterUpdate():
			parent::afterUpdate();
		}

		/**
			Overriden!
			Deletes item and children in one query.
		*/
		public function delete() {
			if ( !$this->_isCodeTriggerSupportDisabled ) {
				DB::q("DELETE FROM ".$this->tableName(true)
					." WHERE ".$this->sqlCodeConditionsAndSpace()
					."code LIKE '".$this->code."%'");

				// no call to parent delete()!
			}
			else parent::delete();
		}

		/**
			Overriden!
			Moves up below siblings.
		*/
		public function afterDelete() {
			if ( !$this->_isCodeTriggerSupportDisabled ) {
				// move up all the siblings below.
				$this->moveUp();
			}

			// call parent afterDelete():
			parent::afterDelete();
		}

		/**
			Fetches a CodeEntity by specified code and applies it to this object.
		*/
		public function fetchAndApplyByCode($code=NULL) {
			$p = self::PROPERTY_CODE;
			if ( $code == NULL ) $code = $this->$p;
			$data = DB::selectOne("SELECT * FROM ".$this->tableName()." WHERE ".$this->sqlCodeConditionsAndSpace()."$p='".$code."'");
			if ( !$data ) return NULL;
			$this->applyArray($data);
			return $this;
		}

		/**
			Returns array with all immediate children of this item.
			The children of children are NOT included.
			Additional WHERE sql conditions can be supplied as an array with $where.
		*/
		public function fetchChildren($where=NULL) {
			// convert $where to array if needed:
			if ( $where && !is_array($where) ) $where = array($where);

			// fetch children of entity type:
			$php2 = "\$child = new ".get_class($this)."(\$item);";
			$r = DB::q("SELECT * FROM ".$this->tableName()
				." WHERE ".$this->sqlCodeConditionsAndSpace()
				.($where?implode(" AND ", $where)." AND ":"")
				."code LIKE '".$this->code.str_repeat("_", $this->levelWidth())."'"
				." ORDER BY code");
				$items = array();
			//print "fetched ".dbNumRows($r)." rows<br>";
			//$i = 0;
			while ( $item = DB::a($r) ) {
				//$i++;
				eval($php2);
				//print $i." child: ".$child->code."<br>";
				$items[$child->code] = $child;
			}
			return $items;
		}

		/**
			Fetches all parents of the item sorted by code.
			Returns them in the associated array where codes are the keys.
			The items (the keys) are sorted ascending by code.
			If this item does not have parents (top level item) - returns empty array.
		*/
		public function fetchTreeParents() {
			// this item may do not have parents:
			if ( !$this->codeLevel() ) return array();

			// collect all parent codes:
			$codes = array();
			$level = 0;
			while ( $level < $this->codeLevel() ) {
				$codes[] = $this->codeParentAtLevel($level++);
			}

			// fetch parents of entity type:
			$php2 = "\$parent = new ".get_class($this)."(\$item);";
			$r = DB::q("SELECT * FROM ".$this->tableName()
				." WHERE ".$this->sqlCodeConditionsAndSpace()
				."code IN ('".implode("','", $codes)."')"
				."ORDER BY code");
			$items = array();
			while ( $item = DB::a($r) ) {
				eval($php2);
				$items[$parent->code] = $parent;
			}
			return $items;
		}

		/**
			Allocates next available code for this item on the root level.
			Meant to be called in a transaction.
			Sets code of this item to NULL (and returns NULL) if there are no more available child codes.
			Eg: it returns 01 if there are no root elements.
			Eg: it returns 07 if there are 6 root elements.
			Eg: it returns NULL if there are 99 root elements.
		*/
		protected function allocateRootCode() {
			return $this->allocateCode(NULL);
		}

		/**
			Allocates next available code for this item on the specified level.
			Meant to be called in a transaction.
			Sets code of this item to NULL (and returns NULL) if there are no more available child codes.
			Eg: it returns 01 if there are no root elements.
			Eg: it returns 07 if there are 6 root elements.
			Eg: it returns NULL if there are 99 root elements in 2-digit-level-width-hierarchy.
		*/
		protected function allocateCode($parentCode=NULL) {
			// get next code after maximum and parent at the specified level:
			$fetch = DB::selectOne("SELECT MAX(code) AS maxCode FROM ".$this->tableName()
				." WHERE ".$this->sqlCodeConditionsAndSpace()
				."code LIKE '".($parentCode?$parentCode:"").str_repeat("_",$this->levelWidth())."'");
			if ( !$fetch['maxCode'] ) {
				// this is the first child:
				$this->code = ($parentCode?$parentCode:"").sprintf("%0".$this->levelWidth()."d", 1);
			}
			else {
				// there are children already:
				$this->code = $fetch['maxCode'];
				$this->codePlus($this->codeLevel()); // increment!
				if ( $this->codeEquals($fetch['maxCode']) ) {
					// code is unchanged - there is no space!
					$this->code = NULL;
				}
			}
			return $this->code;
		}

/* --------------------------------------------- */
/* Hierarchy information
/* --------------------------------------------- */

		/**
			Checks whether current code is valid for INSERT / UPDATE to the hierarchy.
			Validity assumes this:
				1. Level of the item does not exceed the maximum level.
				2. If this is a first element on the level - parent must exist or item should be root element.
				3. If this is not a first elemtn on the level - upper sibling must exist.
			Checking should be done before each INSERT action in a transaction.
		*/
		private function isValidCode() {
			// if code is not specified - this is invalid code:
			if ( !$this->code ) return false;

			// 1. Check code level is valid:
			if ( $this->codeLevel() > $this->maxLevel() ) {
				$this->_logger->err("INVALID code state: item with code ".$this->code." has invalid level: ".$this->codeLevel().". Max availabale level is: ".$this->maxLevel());
				return false;
			}

			// 2. What value is it?
			if ( $this->codeValue() == 1 ) {
				// 2. Check parent exists:
				if ( $this->codeLevel() ) {
					// this is level 1+:
					$pCode = $this->codeParent();
					eval("\$p = new ".get_class($this)."();");
					$p->applyArray($this->toArray());
					$p2 = $p->fetchAndApplyByCode($pCode);
					if ( $p2 == NULL ) {
						// parent does not exist:
						$this->_logger->err("INVALID code state: parent with code ".$pCode." does not exist.");
						return false;
					}
				}
				
				// this is a valid code!
				$this->_logger->w("Code ".$this->code." is valid.");
				return true;
			}

			// This is value 2+:
			$sCode = $this->codeMinus($this->codeLevel(), false);
			eval("\$p = new ".get_class($this)."();");
			$p->applyArray($this->toArray());
			$p2 = $p->fetchAndApplyByCode($sCode);
			if ( $p2 == NULL ) {
				// upper sibling does not exist:
				$this->_logger->err("INVALID code state: upper sibling of ".$this->code." with code ".$sCode." does not exist.");
				return false;
			}

			// all is ok:
			$this->_logger->w("Code ".$this->code." is valid.");
			return true;
		}

		/**
			Checks whether current code is valid for UPDATE to the hierarchy.
			UPDATE validity assumes this:
				1. Level of the item does not exceed the maximum level.
				//2. If parent is unchanged this->code must exist.
				//3. If parent is changed:
					3.1. oldItem->code must not be a tree parent of this->code.
					//3.2. If this->code is NOT a root element - parent must exist.
					3.3. If this->code is NOT the first element on the level - upper sibling must exist.
			Checking should be done before each UPDATE action in a transaction.
		*/
		public function isValidCodeForUpdate($oldItem) {
			$this->_logger->indent(1);
			$this->_logger->w("Checking item code for update: ".$item->code.", old code: ".$oldItem->code);

			// if code is not specified - this is invalid code:
			if ( !$this->code ) {
				$this->_logger->err("Item code is null.");
				$this->_logger->indent(-1);
				return false;
			}

			// if code is unchanged - this is a valid case:
			if ( $this->codeEquals($oldItem->code) ) {
				$this->_logger->w("Code is unchanged");
				$this->_logger->indent(-1);
				return true;
			}

			// 1. Check code level is valid:
			if ( $this->codeLevel() > $this->maxLevel() ) {
				$this->_logger->err("INVALID code state: item with code ".$this->code." has invalid level.");
				$this->_logger->indent(-1);
				return false;
			}

			// 2. is parent changed?
			if ( $this->isCodeSibling($oldItem->code) ) {
				// parent is unchaged:
				$this->_logger->w("Parent unchanged.");

				// Check such code exists:
				eval("\$p = new ".get_class($this)."();");
				$p2 = $p->fetchAndApplyByCode($this->code);
				if ( $p2 == NULL ) {
					// such code does not exist:
					$this->_logger->err("INVALID code state: item with code ".$this->code." does not exist.");
					$this->_logger->indent(-1);
					return false;
				}
			}
			else {
				// parent is changed:
				$this->_logger->w("Parent is changed.");

				// 3.1. Is old item is parent of the new item?
				if ( $oldItem->isCodeTreeParentOf($this->code) ) {
					$this->_logger->err("INVALID code state: item with code ".$this->code." would be a child of old itself ".$oldItem->code.".");
					$this->_logger->indent(-1);
					return false;
				}

				// 3.2. Check parent exists or this is a root element:
				if ( $this->codeLevel() ) {
					// this is level 1+:
					$pCode = $this->codeParent();
					eval("\$p = new ".get_class($this)."();");
					$p2 = $p->fetchAndApplyByCode($pCode);
					if ( $p2 == NULL ) {
						// parent does not exist:
						$this->_logger->err("INVALID code state: parent with code ".$pCode." does not exist.");
						$this->_logger->indent(-1);
						return false;
					}
				}

				// 3.3. Check upper sibling exists or this is a first element:
				if ( $this->codeValue() == 1 ) {
					// this is a valid code!
					$this->_logger->w("Code ".$this->code." is valid.");
					$this->_logger->indent(-1);
					return true;
				}

				// This is value 2+:
				$sCode = $this->codeMinus($this->codeLevel(), false);
				eval("\$p = new ".get_class($this)."();");
				$p2 = $p->fetchAndApplyByCode($sCode);
				if ( $p2 == NULL ) {
					// upper sibling does not exist:
					$this->_logger->err("INVALID code state: upper sibling with code ".$sCode." does not exist.");
					$this->_logger->indent(-1);
					return false;
				}
			}

			// all is ok:
			$this->_logger->w("Code ".$this->code." is valid.");
			$this->_logger->indent(-1);
			return true;
		}

		/**
			Returns width of level (protected property).
		*/
		public function levelWidth() {
			eval("\$v = ".get_class($this)."::LEVEL_WIDTH;");
			return $v;
		}

		/**
			Returns maximum level index.
		*/
		public function maxLevel() {
			eval("\$v = ".get_class($this)."::MAX_LEVEL;");
			return $v;
		}

		/**
			Returns true if object's code is tree parent of the supplied code.
			Otherwise returns false.
		*/
		public function isCodeTreeParentOf($code) {
			if ( !$code ) return false;

			// create instance of the same class:
			eval("\$item = new ".get_class($this)."();");
			$item->code = $item->codePad($code);
			$this->code = $this->codePad($this->code);
			if ( $item->codeLevel() <= $this->codeLevel() ) return false;
			if ( $this->codeEquals(substr($item->code, 0, strlen($this->code))) ) return true;
			return false;
		}

		/**
			Returns true if object's code is a sibling of the supplied code.
			Otherwise returns false.
		*/
		public function isCodeSibling($code) {
			if ( strcmp(substr($this->code, 0, -$this->levelWidth()), substr($code, 0, -$this->levelWidth())) == 0 ) return true;
			return false;
		}

		/**
			Forms correct code with needed amount of leading zeros.
			Eg: 0002 for 2;
		*/
		public function codePad($code) {
			while ( strlen($code) % $this->levelWidth() != 0 ) {
				$code = "0" . $code;
			}
			return $code;
		}

		/**
			Returns code without leading zeros and with point between levels.
			Eg: 10.3 for 0010.0003;
		*/
		public function codeView($code=NULL) {
			if ( $code == NULL ) $code = $this->code;
			$code = $this->codePad($code);
			$numerics = array();
			while ( $code ) {
				$numerics[] = intval(substr($code, 0, $this->levelWidth()));
				$code = substr($code, $this->levelWidth());
			}
			return implode($numerics, ".").".";
		}

		/**
			Returns code of the immediate parent of this item.
			Eg: 002.001 for 002.001.005
		*/
		public function codeParent($code=NULL) {
			if ( $code == NULL ) $code = $this->code;
			if ( !$this->codeLevel() ) return NULL;
			return substr($this->code, 0, -$this->levelWidth());
		}

		/**
			Returns code of a parent of this item on specified level.
			Eg: 002 for 002.001.005 and level 0.
			Eg: 002.001 for 002.001.005 and level 1.
		*/
		public function codeParentAtLevel($level=0) {
			if ( !$this->codeLevel() ) return NULL;
			return substr($this->code, 0, $this->levelWidth()*($level+1));
		}

		/**
			Compares the specified with the code of the item.
			Returns true if the specified code equals the code of the item.
		*/
		public function codeEquals($code) {
			$this->code = $this->codePad($this->code);
			$code = $this->codePad($code);
			if ( !strcmp($this->code, $code) ) return true;
			return false;
		}

		/**
			Returns integer value at the last level of the code of the item.
			Eg: 2 for 0002.
			Eg: 4 for 0002.0004.
			Eg: 12 for 0002.0004.0012.
		*/
		public function codeValue($code=NULL) {
			if ( !$code ) $code = $this->code;
			return intval(substr($code, -$this->levelWidth()));
		}

		/**
			Returns level of the code of the item.
			Eg: 2 for 012.012 
			Eg: 3 for 01.20.12
		*/
		public function codeLevel($code=NULL) {
			if ( $code == NULL ) $code = $this->code;
			//$this->_logger->w("before padding: ".$this->code);
			$code = $this->codePad($code);
			//$this->_logger->w("after padding: ".$this->code);
			//$this->_logger->w("level width: ".$this->levelWidth());
			if ( strlen($code) < $this->levelWidth() ) return 0;
			return strlen($code)/$this->levelWidth() - 1;
		}

		/**
			Decreases
		*/
		public function codeMinus($level, $updateThis=true) {
			// codeMinus('003.002') == '003.001'
			// codeMinus('003.001', 0) == '002.001'
			// codeMinus('003.001') == '003.001'
			//DB::$log[] = "codeMinus(): level: ".$level;
			//if ( $level == -1 ) $level = $this->codeLevel();
			if ( $level > $this->codeLevel() ) return $this->code;
			$d = substr($this->code, $level*$this->levelWidth(), $this->levelWidth());
			$v = intval($d);
			//DB::$log[] = "codeMinus(): v = ".$v;
			// if the level is already minimum?
			if ( $v == 1 ) return $this->code;
			$d = sprintf("%0".$this->levelWidth()."d", $v - 1);
			$code = substr($this->code, 0, $level*$this->levelWidth()).$d.substr($this->code, ($level+1)*$this->levelWidth());
			if ( $updateThis ) $this->code = $code;
			return $code;
		}

		public function codePlus($level, $updateThis=true) {
			// codePlus('003.002', 1) == '003.003'
			// codePlus('003.001', 1) == '003.002'
			// codePlus('003.999') == '003.999'
			//$this->_logger->w("codePlus(): level: ".$level);
			//if ( $level == -1 ) $level = $this->codeLevel();
			if ( $level > $this->codeLevel() ) return $this->code;
			$d = substr($this->code, $level*$this->levelWidth(), $this->levelWidth());
			$v = intval($d);
			//$this->_logger->w("codePlus(): v = ".$v);
			// if the level is already maximum?
			if ( $v == pow(10, $this->levelWidth())-1 ) return $this->code;
			$d = sprintf("%0".$this->levelWidth()."d", $v + 1);
			$code = substr($this->code, 0, $level*$this->levelWidth()).$d.substr($this->code, ($level+1)*$this->levelWidth());
			if ( $updateThis ) $this->code = $code;
			return $code;
		}

/* ---------------------------------------------
 * MOVING
 * --------------------------------------------- */

		/**
			Moves item (and its children) to another code.
			This item becomes an item with the specified code.
			This should be done only in a transaction.
			Updates DB.
		*/
		private function move($newCode) {
			$oldCodeLen = strlen($this->code);

			// moving all children from oldCode to code:
			DB::q("UPDATE ".$this->tableName(true)." SET code="
				."CONCAT('".$newCode."'," 
				."SUBSTRING(code,".($oldCodeLen+1)."))"
				." WHERE ".$this->sqlCodeConditionsAndSpace()
				."code LIKE '".$this->code."%'");
		}

		/**
			Moves children of this item to another parent with specified code.
			Updates DB.
		*/
		private function moveChildren($newParentCode) {
			$oldCodeLen = strlen($this->code);

			// moving all children from oldCode to code:
			DB::q("UPDATE ".$this->tableName(true)." SET code="
				."CONCAT('".$newParentCode."'," 
				."SUBSTRING(code,".($oldCodeLen+1)."))"
				." WHERE ".$this->sqlCodeConditionsAndSpace()
				."code LIKE '".$this->code.str_repeat("_",$this->levelWidth())."%'");
		}

		/**
			Moves all items starting with this one DOWN in the hierarchy updating DB.
			Eg: 001.002 becomes 001.003.
			All the siblings below this item are also moved DOWN (with their children).
		*/
		private function moveDown($stopCode=NULL) {
			$this->_logger->w("CodeEntity::moveDown(".$stopCode.")");
			$this->_logger->indent();


			$parentCode = $this->codeParent();
			$parentCodeLen = strlen($parentCode);

			// moving down lower or equeal items (and their children):
			try {
				$sql = "UPDATE ".$this->tableName(true)." SET code="
				."CONCAT("
				."LEFT(code,".$parentCodeLen."),"
				."LPAD(SUBSTRING(code,".($parentCodeLen+1).",".$this->levelWidth().")+1,".$this->levelWidth().", '0'),"
				."SUBSTRING(code,".($parentCodeLen+$this->levelWidth()+1).")"
				.") "
				."WHERE ".$this->sqlCodeConditionsAndSpace()	// this will be either "" or "smth=smth AND "
				."code LIKE '".$parentCode."%'"
				." AND SUBSTRING(code,".($parentCodeLen+1).",".$this->levelWidth().")>=".$this->codeValue()
				.($stopCode?" AND SUBSTRING(code,".($parentCodeLen+1).",".$this->levelWidth().")<=".$this->codeValue($stopCode):"")
				." ORDER BY code DESC";// "ORDER BY code DESC" is important!
				$this->_logger->w($sql);
				DB::q($sql);
			}
			catch (DBException $e) {
				$this->_logger->err($e);
				throw new CodeEntityException($e);
			}

			$this->_logger->indent(-1);
		}

		/**
			Moves all items starting with this one UP in the hierarchy updating DB.
			Eg: 003.002 becomes 003.001.
			All the siblings below this item are also moved UP (so do all their children).
			Any other properties (except for code) of the item are NOT updated in DB.
		*/
		private function moveUp($stopCode=NULL) {
			$parentCode = $this->codeParent();
			$parentCodeLen = strlen($parentCode);

			// moving up upper or equal items (and their children):
			DB::q("UPDATE ".$this->tableName(true)." SET code="
				."CONCAT("
				."LEFT(code,".$parentCodeLen."),"
				."LPAD(SUBSTRING(code,".($parentCodeLen+1).",".$this->levelWidth().")-1,".$this->levelWidth().", '0'),"
				."SUBSTRING(code,".($parentCodeLen+$this->levelWidth()+1).")"
				.") "
				."WHERE ".$this->sqlCodeConditionsAndSpace()	// this will be either "" or "smth=smth AND "
				."code LIKE '".$parentCode."%'"
				." AND SUBSTRING(code,".($parentCodeLen+1).",".$this->levelWidth().")>=".$this->codeValue()
				.($stopCode?" AND SUBSTRING(code,".($parentCodeLen+1).",".$this->levelWidth().")<=".$this->codeValue($stopCode):"")
				." ORDER BY code");	// "ORDER BY code" is important!
		}

/* ---------------------------------------------
 * CODE CONDITIONS
 * --------------------------------------------- */

		/**
			Returns array of SQL conditions which have to be used when altering code.
			Eg: array(catalogID='1')
			By default returns empty array.
		*/
		public function sqlCodeConditions() {
			// no conditions by default:
			return array();
		}

		/**
			Returns SQL with aditional conditions for code.
			Eg: "catalogID='1' "
			If there are not conditions - returns empty string "";
		*/
		public function sqlCodeConditionsSpace() {
			$c = $this->sqlCodeConditions();
			if ( sizeof($c) ) return implode(" AND ", $c)." ";
			return "";
		}

		/**
			Returns SQL with aditional conditions for code and ending ' AND'.
			Eg: "catalogID='1' AND".
			If there are not conditions - returns empty string "";
		*/
		public function sqlCodeConditionsAndSpace() {
			$c = $this->sqlCodeConditions();
			if ( sizeof($c) ) return implode(" AND ", $c)." AND ";
			return "";
		}

/* ---------------------------------------------
 * PRIVATE UTILITIES
 * --------------------------------------------- */
	}

	/**
		Exception generated while working with CodeEntity.
		TODO: implement to all CodeEntity methods.
	*/
	class CodeEntityException extends EntityException {
	}
?>
