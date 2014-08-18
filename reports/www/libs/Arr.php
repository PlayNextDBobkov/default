<?
	/**
		Some helpfull array methods.
	*/
	class Arr {
		public static function oneTrue($a) {
			foreach ( $a as $key => $i ) {
				if ( $i ) return 1;
			}
			return 0;
		}

		public static function allTrue($a) {
			foreach ( $a as $i ) {
				if ( !$i ) return 0;
			}
			return 1;
		}

		public static function keep($array, $keepKeys) {
			$keepKeys = array_flip($keepKeys);
			foreach ( $array as $key => $value ) {
				if ( !isset($keepKeys[$key]) ) unset($array[$key]);
			}
			return $array;
		}

		public static function skip($object, $unsetKeys) {
			if ( !is_array($unsetKeys) ) $unsetKeys = array($unsetKeys);
			foreach ( $object as $key => $value ) {
				if ( in_array($key, $unsetKeys) ) unset($object[$key]);
			}
			return $object;
		}

		public static function listKeys($items, $key, $sep=", ")	{
			// gives a string of all $child_of_items[$key]

			$names = array();
			foreach ( $items as $item )
			{
				$names[] = $item[$key];
			}
			return implode($sep, $names);
		}
	}
?>