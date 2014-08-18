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
	class CacheException extends E7Exception {
	}

	class Cache {

		public static function get($name) {
			$file = E7::PATH_TMP.base64_encode($name);
			$content = @file_get_contents($file);
			if ( $content ) {
				//print "Restoring: ".$content.LF;
				//@eval("\$c = ".$content.";");
				return unserialize($content);
			}
			return NULL;
		}

		public static function set($name, $value, $exp=3600) {
			$file = E7::PATH_TMP.base64_encode($name);
			$fh = @fopen($file, 'w');
			if ( !$fh ) {
				 throw new CacheException("Could not open file ".$file." (".$name.") for writing.");
			}
			//fwrite($fh, var_export($value, true));
			fwrite($fh, serialize($value));
			fclose($fh);
		}
	}
?>