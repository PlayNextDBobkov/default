<?
	/**
		Base class for all entities used in transactions with channels.
	*/
	class ChannelEntity extends Entity {

		/**
			Applies Yandex.Direct object to this record.
		*/
		public function applyYD($ydObject) {
			// do nothing by default
		}

		/**
			Applies Google AdWords object to this record.
		*/
		public function applyGA($gaObject) {
			// do nothing by default
		}
	}
?>
