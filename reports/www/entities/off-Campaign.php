<?
	class Campaign extends Entity {

		const SYNC_EXPIRY		= 600;	//time to get outof sync in seconds

		var $id;
		var $clientId;
		var $idYD;
		var $name;
		var $startsOn;
		var $clicks;
		var $imps;
		var $sum;
		var $balance;
		var $isArchived;
		var $isModerated;

		var $source;

		var $isRunning;	// remote status
		var $isActive;	// local admin settings
		var $syncedOn;
		var $createdOn;
		var $updatedOn;

		public function url() {
			return PublicPage::AD_LIST."?filter=1&campaignId=".$this->id;
		}

		public function status() {
			if ( $this->isNew() ) return "новая";
			if ( !$this->isSynced() ) return "не синхронизирована";
			if ( $this->isRunning ) return "идет";
			if ( $this->isArchived ) return "архив";
			if ( !$this->isModerated ) return "на модерации";
			if ( !$this->isActive ) return "отключена";
			return "неизвестно";
		}

		public function isNew() {
			if ( $this->syncedOn ) return false;
			return true;
		}

		public function isSynced() {
			if ( !$this->syncedOn ) return false;
			if ( !$this->updatedOn ) return false;
	
			// compare updatedOn and sycnedOn:
			$sd = Date::mysql2timestamp($this->syncedOn);
			$ud = Date::mysql2timestamp($this->updatedOn);
			if ( abs($sd-$ud)>self::SYNC_EXPIRY ) return false;

			return true;
		}

		protected function setDefaultValues() {
			$this->isActive = 1;
		}
	}
?>
