<?
	/**
		Annotation implementation.
	*/
	class Annotation {

		var $name;
		var $args;

		/**
			T_VARIABLE | T_CLASS | T_FUNCTION
		*/
		var $tokenId;
		var $tokenName;

		public function __toString() {
			return $this->value();
		}

		public function value() {
			return $this->args[0];
		}

		/**
			Static staff.
		*/
		
		protected static $_registeredClasses;

		public static function registerClass($annName, $className=NULL) {
			if ( !is_array(self::$_registeredClasses) ) self::$_registeredClasses = array();

			$names = t(explode(",", $annName));
			foreach ( $names as $annName ) {
				if ( !$className ) $className = "Annotation".$annName;

				// if class is not defined - define it runtime:
				if ( !class_exists($className, false) ) {
					eval("class ".$className." extends Annotation {"
						." var \$name = '".$annName."';"
						."}");
				}

				self::$_registeredClasses[$annName] = $className;
			}
		}

		public static function parseObject($object) {
			return self::parseFile($object->classFile());
		}

		public static function parseFile($file) {
			if ( !is_file($file) ) return NULL;
			$tokens = token_get_all(file_get_contents($file));

			$annotations = array();
			$comments = array();
			$isFunction = false;
			foreach ( $tokens as $token ) {
				if(is_array($token)) {
					list($code, $value) = $token;

					//print "---".token_name($code).":".$value.LF;

					switch($code) {
						case T_COMMENT:
						//case T_DOC_COMMENT: 
							$comments[] = $value;
							//print "Comment: ".$value.LF;
							break;

						case T_FUNCTION:
							$isFunction = true;
							break;

						case T_STRING:
							if ( !$isFunction ) break;
							$isFunction = false;
							$code = T_FUNCTION;

						case T_CLASS:
						case T_VARIABLE:

							if ( sizeof($comments) ) {
								//print token_name($code)." ".$value." has comments:".LF;
								//da($comments);

								if ( $code == T_CLASS ) $name = NULL;	// classes do not have names sub array
								else if ( $code == T_FUNCTION ) $name = $value;
								else $name = substr($value, 1);	// remove "$"

								if ( !is_array($annotations[$code]) ) $annotations[$code] = array();
								if ( $name && !is_array($annotations[$code][$name]) ) $annotations[$code][$name] = array();

								$parsed = self::parseComments($comments, $code, $name);
								if ( is_array($parsed) ) {
									if ( $name ) $annotations[$code][$name] = array_merge(
											$annotations[$code][$name],
											$parsed);
									else $annotations[$code] = array_merge(
											$annotations[$code],
											$parsed);
								}

								$comments = array();
							}

							break;

						// ignore
						case T_WHITESPACE: 
						case T_PUBLIC: 
						case T_PROTECTED: 
						case T_PRIVATE: 
						case T_ABSTRACT: 
						case T_FINAL: 
						case T_VAR: 
							break;

						default: 
							$comments = array();
							break;
					}
				} else {
					$comments = array();
				}
			}
			return $annotations;
		}

		/**
			Parses text of a comment and returns detected annotations.
			Currently we understand these 2 types of annotation syntax:
			1.	// @Type("varchar(32)")
			2.	/* @Type("varchar(32)") * /
			This is not parsed as concerned to be a clean doccomment:
				/**
					@Type("varchar(32)")
				* /
		*/
		public static function parseComments($texts, $tokenId, $tokenName) {
			if ( !is_array($texts) ) $texts = array($texts);

			$annotations = array();
			foreach ( $texts as $text ) {
				//print "Parsing \"".$text."\"".LF;
				
				if ( preg_match("/^\/\/(.+)$/", $text, $m) ) {
					// 1
					//print "Annotation type 1: ".$m[1].LF;
				}
				else if ( preg_match("/\/\*(.+)\*\//s", $text, $m) ) {
					// 2
					//print "Annotation type 2: ".$m[1].LF;
				}
				else if ( false && preg_match("/\/\*\*(.+)\*\//s", $text, $m) ) {
					// 3
					//print "Annotation type 3: ".$m[1].LF;
				}
				else return array();	// nothing

				$text = $m[1];
				preg_match("/^\@([A-Z][^\(]+)(\(.+\))?/", trim($text), $m);

				//print "Annotation: ".$m[1].LF;
				$class = self::$_registeredClasses[$m[1]];
				if ( !$class ) $class = "Annotation";

				//print "Annotation class: ".$class.LF;

				eval("\$a = new ".$class."();");
				$a->name = $m[1];
				// TODO: limited to a sinlge param as string param may have comma inside.
				//$args = t(explode(",", substr($m[2], 1, -1)));
				$args = t(array(substr($m[2], 1, -1)));
				// remove quotes:
				foreach ( $args as $i => $arg ) {
					$args[$i] = preg_replace("/^[\"\'](.+)[\"\']$/", "$1", $arg);
				}
				$a->args = $args;
				$a->tokenId = $tokenId;
				$a->tokenName = $tokenName;

				$annotations[] = $a;
			}
			return $annotations;
		}
	}
?>