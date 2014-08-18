<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/

	/**
		Exception generated while working with ImageFileEntity.
		TODO: implement to all ImageEntity related methods.
	*/
	class ImageFileEntityException extends Exception {
	}

	/**
		Represents an image file in DB.
	*/
	class ImageFileEntity extends FileEntity {
		public static $formats		= array("jpg", "jpeg", "tif", "bmp", "gif", "png");//, "swf");
		public static $formatsWeb	= array("jpg", "jpeg", "gif", "png", "swf");
		const DEFAULT_FORMAT		= "png";
		const WIDTH					= 0;	// width to resize image to
		const DIMENSION				= 0;	// width or height (depending on what is bigger) to resize image to
		const MAX_WIDTH				= 0;	// if applied image is larger in width - it will be resized
		const MAX_DIMENSION			= 1000;	// if applied image is larger in dimension - it will be resized
		const CONVERT_OPTIONS		= "";
		const CONVERT_OPTIONS_AFTER	= "";
		const CONVERT_FORMAT		= "";
		const QUALITY				= NULL;

		private static $_convertIndex = 0;

		// width and height of the stored image:
		var $width;		// image width
		var $height;	// image height

		// protected:
		protected $_overrideWidth;
		protected $_overrideDimension;
		protected $urlWidth;
		protected $urlHeight;
		protected $urlDimension;
		protected $urlMinDimension;

		/**
			Use this to set what width the image should be when requested by URL.
		*/
		public function setURLWidth($w) {
			$this->urlWidth = $w;
		}

		/**
			Use this to set what width the image should be when requested by URL.
		*/
		public function setURLHeight($h) {
			$this->urlHeight = $h;
		}

		/**
			Use this to set what maximum dimension the image should be when requested by URL.
		*/
		public function setURLDimension($d) {
			$this->urlDimension = $d;
		}

		/**
			Use this to set what minimum dimension the image should be when requested by URL.
		*/
		public function setURLMinDimension($d) {
			$this->urlMinDimension = $d;
		}

		/**
			Use this to manually specify width to which this image must be resized when applied.
			By default ImageEntity::WIDTH is used.
		*/
		public function overrideWidth($value) {
			$_overrideWidth = $value;
		}

		/**
			Use this to manually specify dimension to which this image must be resized when applied.
			By default ImageEntity::DIMENSION is used.
		*/
		public function overrideDimension($width) {
			$_overrideDimension = $value;
		}

		/**
			Overriden to apply image transformations with the file.
		*/
		public function applyFile($file, $realName=NULL) {
			$this->_logger->w("Applying image file ".$file.":");
			$this->_logger->indent();
			// is file in web format?
			$ext = strtolower($realName ? fileExt($realName) : fileExt($file));
			$this->_logger->w("The image is of format: ".$ext);
			$isConvertNeeded = false;
			if ( !in_array($ext, self::$formatsWeb) ) {
				$ext = self::DEFAULT_FORMAT;
				$isConvertNeeded = true;
				$this->_logger->w("Default web image format will be applied for this image: ".$ext);
			}

			$entityName = get_class($this);

			// detect current size:
			list($w, $h, $type, $attr) = getimagesize($file);

			// check image does not need resize:
			eval("\$maxWidth = ".$entityName."::MAX_WIDTH;");
			eval("\$maxDimension = ".$entityName."::MAX_DIMENSION;");
			if ( $maxWidth && $w > $maxWidth ) $isConvertNeeded = true;
			else if ( $maxDimension && ($w > $maxDimension || $h > $maxDimension) ) $isConvertNeeded = true;

			// determine resize is applied:
			if ( $this->_resizeWidth ) $width = $this->_resizeWidth;
			else if ( $this->_resizeDimension ) $dimension = $this->_resizeWidth;
			else {
				eval("\$width = ".$entityName."::WIDTH;");
				eval("\$dimension = ".$entityName."::DIMENSION;");
			}
			if ( $width || $dimension ) $isConvertNeeded = true;

			// determine options:
			eval("\$convertOptions = ".$entityName."::CONVERT_OPTIONS;");
			if ( $convertOptions ) $isConvertNeeded = true;

			// determine options "after":
			eval("\$convertOptionsAfter = ".$entityName."::CONVERT_OPTIONS_AFTER;");
			if ( $convertOptionsAfter ) $isConvertNeeded = true;

			// determine format:
			eval("\$convertFormat = ".$entityName."::CONVERT_FORMAT;");
			if ( $convertFormat ) $isConvertNeeded = true;

			// determine quality:
			eval("\$quality = ".$entityName."::QUALITY;");
			///if ( $quality ) $isConvertNeeded = true;

			// is convert needed?
			if ( $isConvertNeeded ) {
				// resize/convert is needed - create resized image:
				$options = array();
				if ( $quality ) $options[] = "-quality ".$quality;
				if ( $width ) $options[] = "-geometry ".$width;
				else if ( $dimension ) {
					if ( $w < $h ) $options[] = "-geometry ".$dimension;
					else $options[] = "-geometry x".$dimension;
				}
				else if ( $maxWidth ) "-geometry ".$width;
				else if ( $maxDimension ) {
					if ( $w >= $h ) $options[] = "-geometry ".$maxDimension;
					else $options[] = "-geometry x".$maxDimension;
				}

				if ( $convertOptions ) $options[] = $convertOptions;

				if ( $convertFormat ) $fileOut = $file.".".self::$_convertIndex.".".$convertFormat;
				else $fileOut = $file.".".self::$_convertIndex.".".$ext;
				self::$_convertIndex++;
				
				//print implode(" ", $options)."<BR>";
				$cmd = (sizeof($options)?implode(" ", $options)." ":"")."\"".$file."\"".($convertOptionsAfter?" ".$convertOptionsAfter." ":"")." \"".$fileOut."\"";
				$this->_logger->w($cmd);
				imgConvertCmd($cmd);
			}
			else $fileOut = $file;

			// set wile width and height:
			list($this->width, $this->height, $type, $attr) = getimagesize($fileOut);
			$this->_logger->indent(-1);
			return parent::applyFile($fileOut, $realName);
		}

		/**
			Returns an array of names of classes based on ChildImageEntity.
			All such records also are created from this image after this record is inserted into DB.
			The created records will be children of this record.
			Override this array to auto-create child images of this image, such us thumbs or specially processed images.
		*/
		public function autoImages() {
			// by default - empty array
			return array();
		}

		/**
			Overriden to create automated images depending on this (thumbs, etc).
			See ImageEntity::autoImages().
		*/
		public function save() {
			parent::save();
			if ( !$this->_originalFile ) return;

			try {
				// create and save child images (autoImages):
				$autoImages = $this->autoImages();
				foreach ( $autoImages as $entityName ) {
					$this->_logger->w("Auto creating ".$entityName);
					// create image object:
					if ( !require_once($entityName.".php") ) throw new Exception("Could not include ".$entityName.".php");
					$code = "\$image = new ".$entityName."();";
					eval($code);

					// apply original image file:
					$fileName = $entityName."-of-".$this->id.".".$this->ext;
					$image->applyFile($this->_originalFile, $fileName);
					// parent ID for child image is id of this image:
					$image->parentId = $this->id;
					///usleep(1000);
					$image->save();
				}
			}
			catch ( Exception $e ) {
				$this->_logger->err("Could not process auto-images: ".$e);
			}
		}

		/**
			Overriden to download images from /i/ directory.
		*/
		public function url() {
			if ( $this->urlWidth ) return "/i/".get_class($this).".".$this->urlWidth.".".$this->id.".".$this->ext;
			if ( $this->urlHeight ) return "/i/".get_class($this).".h".$this->urlHeight.".".$this->id.".".$this->ext;
			if ( $this->urlDimension ) return "/i/".get_class($this).".d".$this->urlDimension.".".$this->id.".".$this->ext;
			else if ( $this->urlMinDimension ) return "/i/".get_class($this).".s".$this->urlMinDimension.".".$this->id.".".$this->ext;
			return "/i/".get_class($this).".".$this->id.".".$this->ext;
		}

		/**
			Special URL: forced width
		*/
		public function urlWidth($v, $full=true) {
			$str = $full?"w".$v."px":$v;
			return "/i/".get_class($this).".".$str.".".$this->id.".".$this->ext;
		}

		/**
			Special URL: forced height
		*/
		public function urlHeight($v, $full=true) {
			$str = $full?"h".$v."px":"h".$v;
			return "/i/".get_class($this).".".$str.".".$this->id.".".$this->ext;
		}

		/**
			Special URL: forced max dimension - fit into a square
		*/
		public function urlMaxDimension($v, $full=true) {
			$str = $full?"d".$v."px":"d".$v;
			return "/i/".get_class($this).".".$str.".".$this->id.".".$this->ext;
		}

		/**
			Special URL: forced min dimension - cover a square
		*/
		public function urlMinDimension($v, $full=true) {
			$str = $full?"s".$v."px":"s".$v;
			return "/i/".get_class($this).".".$str.".".$this->id.".".$this->ext;
		}

		/**
			Special URL: forced cropping
		*/
		public function urlCrop($x, $y, $width, $height, $full=true) {
			$str = "c".$x."x".$y."px-".$width."x".$height."px";
			return "/i/".get_class($this).".".$str.".".$this->id.".".$this->ext;
		}

		/**
			Special URL: forced resize by width & cropping
		*/
		public function urlWidthCrop($v, $x, $y, $width, $height, $full=true) {
			$str = "w".$v."px.c".$x."x".$y."px-".$width."x".$height."px";
			return "/i/".get_class($this).".".$str.".".$this->id.".".$this->ext;
		}

		/**
			Special URL: forced resize by height & cropping
		*/
		public function urlheightCrop($v, $x, $y, $width, $height, $full=true) {
			$str = "h".$v."px.c".$x."x".$y."px-".$width."x".$height."px";
			return "/i/".get_class($this).".".$str.".".$this->id.".".$this->ext;
		}
	}
?>