<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/


	/**
		Exception generated while working with Logger.
	*/
	class LoggerException extends E7Exception {
	}

	class Logger {
		const MODE_STDOUT			= 1;
		const MODE_FILE				= 2;
		const MODE_FILE_STDOUT		= 3;

		private $fileName;
		private $indent;
		private $isEnabled = true;

		public static function fileLogger($fileName=NULL) {
			return new Logger(Logger::MODE_FILE, $fileName);
		}

		public function Logger($mode=Logger::MODE_FILE_STDOUT, $fileName=NULL) {
			$this->isEnabled = Config::IS_LOGGER_ENABLED;
			$this->mode = $mode;
			if ( !$fileName ) $fileName = "log.txt";
			if ( !preg_match("/.*\/.*/", $fileName) ) $fileName = E7::PATH_LOGS.$fileName;
			$this->fileName = $fileName;
			//$this->sep();
			$this->indent = 0;
		}

		public function fileName() {
			return $this->fileName;
		}

		public function disable() {
			$this->isEnabled = false;
		}

		public function enable() {
			$this->isEnabled = true;
		}

		public function sep() {
			$this->w(str_repeat("-",50), true, true);
		}

		public function indent($value=1) {
			$this->indent+=$value;
		}

		public function err($str="") {
			$this->w("ERROR: ".$str);
		}

		public function w($str="", $skipTime=false, $skipIndent=false) {
			if ( !$this->isEnabled ) return;
			if ( is_array($str) || is_object($str)) {
				// dump array:
				$this->indent();
				foreach ($str as $key => $value ) {
					if ( is_array($value) || is_object($value)) {
						$this->w($key.":", true, $skipIndent);
						$this->w($value, true, $skipIndent);
					}
					else $this->w($key.": ".$value, true, $skipIndent);
				}
				$this->indent(-1);
				return;
			}
			$str = (!$skipIndent ? str_repeat("\t", $this->indent) : "").$str;
			$str = (!$skipTime ? strftime("%d.%m.%Y %H:%M")."\t":"").$str;
			if ( $this->mode & Logger::MODE_STDOUT ) print ($str.LF);
			if ( $this->mode & Logger::MODE_FILE ) {
				$fh = @fopen ( $this->fileName, "a" );
				if ( !$fh ) {
					print $str.LF;
					die("Could not open file ".$this->fileName." for appending.");
					//throw new LoggerException("Could not open file ".$this->fileName." for appending.");
				}
				fwrite ( $fh,  $str."\n");
				fclose ( $fh );
			}
		}
	}
?>