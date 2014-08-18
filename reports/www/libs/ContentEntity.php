<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/

	/**
	 * This class is responsible for getting/setting content under particular identificators.
	 * This can be used for simple content management.
	 * Underlying content is treated as ImageEntity.
	 */
	class ContentEntity extends Entity {
		var $id;
		var $full;
		var $name;
		var $isHTML;
		var $isActive;
		var $createdOn;
		var $updatedOn;

		private static $_cachedValues		= array();

		/**
		 * Saves content under specified identificator.
		 */
		public function save() {
			// see if old record exists:
			$old = DB::fetchOne("Content", "SELECT * FROM content where id='".s($this->id,1)."'");

			$data = $this->toArray();
			unset($data['createdOn']);
			unset($data['updatedOn']);
			if ( isset($data['isHTML']) ) $data['isHTML'] = $data['isHTML']?1:0;
			if ( $old ) {
				unset($data['id']);
				if ( property_exists(get_class($this), "pos") && !$data['pos'] ) unset($data['pos']);
				DB::q("UPDATE ".$this->tableName()." SET ".DB::sqlSet($data)." WHERE id='".s($this->id,1)."'");
			}
			else {
				// new item
				if ( property_exists(get_class($this), "pos") ) {
					// for pos field - take the last pos available:
					$this->pos = $data['pos'] = $this->lastPos();
				}

				DB::q("INSERT ".$this->tableName()." SET ".DB::sqlSet($data).", createdOn=NOW()");
			}
			return $this->id;
		}

		/**
		 * Returns content associated with specified identificator.
		 * Specified $id can be an array.
		 * Language ID ("ru" or "en") may be also specified to receive localized content (default is "ru").
		 */
		public static function get($id, $languageID=NULL) {
			if ( $languageID == NULL ) $languageID = E7::$languageId;
			if ( !is_array($id) && isset($_cachedValues[$id][$languageID]) && $_cachedValues[$id][$languageID] != NULL ) return $_cachedValues[$id][$languageID];
			if ( is_array($id) && sizeof($id) ) $q = " IN ('".implode("','", $id)."')";
			else $q = "='".s($id,1)."'";

			$full = "full".($languageID!=Config::DEFAULT_LANGUAGE_ID?"_".$languageID:"");

			$contents = DB::select("SELECT id, full AS 'default', ".$full." AS ".$languageID." FROM content WHERE isActive and id".$q);
			$arrayContents = array();
			foreach ( $contents as $content ) {
				$cid = $content['id'];
				unset($content['id']);
				$_cachedValues[$cid] = $content;
				if ( $_cachedValues[$cid][$languageID] ) $arrayContents[$cid] = $_cachedValues[$cid][$languageID];
				else $arrayContents[$cid] = $_cachedValues[$cid]['default'];
			}
			if ( is_array($id) ) return $arrayContents;
			return $_cachedValues[$id][$languageID];
		}

		/**
		 * Returns name for this content identificator.
		 */
		public static function nameForID($entityName, $id) {
			$code = "\$object = new $entityName();";
			eval($code);
			return $object->name($id);
		}

		/**
		 * Returns presentation name for identificator.
		 */
		public function name($id) {
			$names = $this->names();
			return $names[$id];
		}

		/**
		 * Returns array of identificator names.
		 */
		public static function names() {
			return array();
		}
	}
?>