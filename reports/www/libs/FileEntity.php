<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/

	/**
		This is a file stored in DB.
	*/
	class FileEntity extends Entity {
		const MAX_FILESIZE			= 8388608;	// 8Mb

		var $id;		// PK - this can be use to serve unique file URLs
		var $file;		// file name (with extension), applied with upload
		var $content;	// file content, applied with upload
		var $length;	// file length in bytes, applied with upload
		var $ext;		// file extension (format), applied with upload
		var $createdOn;	// we neeed this to properly serve image to browser

		/**
			Here we store path to original file from which this file was created.
		*/
		protected $_originalFile = NULL;

		/**
			Reads content from file in this item.
			Sets length, file and ext.
			If real name is specified it will be used for file and ext.
		*/
		public function applyFile($file, $realName=NULL) {
			if ( !$realName ) $realName = basename($file);

			// save path to originaFile:
			$this->setOriginalFile($file);

			// read content:
			clearstatcache();
			$this->set("content", fileLoad($file));
			// set content length:
			$this->set("length", filesize($file));
			// set file name:
			$this->set("ext", strtolower(fileExt($realName)));
			$this->set("file", $realName);
			return true;
		}

		public function setOriginalFile($file) {
			$this->_originalFile = $file;
		}

		/**
			Each file can be downloaded (not necesarilly).
			Override this to make specific URL for this file.
		*/
		public function url() {
			return "/i/file.".$this->id.".".$this->ext;
		}

		/**
			Returns size of item as string with appropriate measurement.
		*/
		public function fileSize() {
			if ( $this->length < 1024 ) return $this->length." б";
			if ( $this->length < 1048576 ) return number_format($this->length/1024, 1, ",", " ")." Кб";
			if ( $this->length < 1073741824 ) return number_format($this->length/1048576, 1, ",", " ")." Мб";
			return number_format($this->length/1073741824, 1, ",", " ")." Гб";
		}
	}
?>