<?
	/**
		E7 web-site engine.
		(c) PlayNext Ltd. 2003-2013.
		Version: 2013-05-16.
		This code is property of PlayNext Ltd. Neither this code nor its part may be used without written license from PlayNext Ltd.
	*/

	/**
		Exception generated while working with Entity.
		TODO: implement to all Entity methods.
	*/
	class EntityException extends Exception {
		var $errCode;	// error code for passing via URL, can be treated by client by its own way
		var $message;	// full message
	}

	class EntityConfirmationException extends EntityException {
	}

	class EntityValidationException extends EntityException {
		public function EntityValidationException($errCode, $message) {
			$this->errCode = $errCode;
			$this->message = $message;
		}
	}
?>