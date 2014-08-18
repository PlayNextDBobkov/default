<?
	require_once("Entity.php");

	class Ad extends Entity {

		const SYNC_EXPIRY		= 600;	//time to get outof sync in seconds

		var $id;
		var $campaignId;
		var $idYD;
		var $idGA;
		var $name;
		var $text;
		var $url;

		var $contactName;
		var $contactCountry;
		var $contactCity;
		var $contactStreet;
		var $contactHouse;
		var $contactBuild;
		var $contactApartment;
		var $contactPhone;
		var $contactCompany;
		var $contactEmail;
		var $contactOGRN;
		var $contactWorkTime;
		var $contactMore;

		var $source;

		var $isDraft;
		var $isModerated;
		var $isRejected;
		var $rejectReason;
		var $isRunning;
		var $isActive;
		var $pos;
		var $syncedOn;
		var $createdOn;
		var $updatedOn;

		/**
			Overriden to set pos to 1 for new items:
		*/
		public function afterInsert() {
			parent::afterInsert();
			//$this->pos = 1;
			$this->updatePos(1);
		}

		public function url() {
			return WebPage::AD_EDIT."?id=".$this->id;
		}

		public function status() {
			if ( $this->isNew() ) return "новая";
			if ( !$this->isSynced() ) return "не синхронизирована";
			if ( $this->isRejected ) return "отклонена";
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
	}
?>
