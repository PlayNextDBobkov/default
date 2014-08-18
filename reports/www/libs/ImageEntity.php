<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/

	/**
		Represents an image file in DB.
	*/
	class ImageEntity extends ImageFileEntity {

		// ImageEntity is extended with parentId and pos - it is a child image of some other entity (Pub, Page, etc):
		var $parentId;	// images always have a parent entity (PageImage - Page)
		var $pos;		// images always have a position
		var $name;

		/**
			Returns images for specified instance, eg PageImage for Page.
			You may override image enotity with $entity or you may specify custom SQL with $sql.
		*/
		public static function imagesOf($item, $entity=NULL, $sql=NULL) {
			if ( !$item || !is_object($item) ) return;

			// make default entity name if neeeded:
			if ( $entity == NULL ) $entity = get_class($item)."Image";

			// instantiate image file entity:
			require_once($entity.".php");
			eval("\$object = new ".$entity."();");

			// make default query:
			if ( $sql == NULL ) $sql = "SELECT id, ext, width, height, length".(property_exists($entity, "pos")?", pos":"")." FROM ".$object->tableName()." WHERE parentId='".$item->id()."'".(property_exists($entity, "pos")?" ORDER BY POS":"");

			// check if images are in cache:
			$cacheKey = $entity."\t".$sql;
			if ( $item->_images[$cacheKey] ) return $item->_images[$cacheKey];

			// fetch image objects:
			$item->_images[$cacheKey] = DB::fetch($entity, $sql);
			return $item->_images[$cacheKey];
		}

		/**
			By default all such entities have a parent.
		*/
		public function posConditionProperties() {
			return array("parentId");
		}
	}
?>