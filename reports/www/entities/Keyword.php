<?
	require_once("Entity.php");

	class Keyword extends Entity {
		const PRIO_LOW			= -100;
		const PRIO_DEFAULT		= 0;
		const PRIO_HI			= 100;
		
		var $id;
		var $adId;

		var $idYD;
		var $idGA;
		var $name;
		var $price;				// price set by user
		var $priceSites;		// price set by user for site network
		var $clicks;
		var $imps;
		var $priceMin;
		var $priceMax;
		var $priceMinSuper;
		var $priceMaxSuper;
		var $hasLowCTR;
		var $hasLowCTRSites;
		var $coverage;
		var $coverageSites;
		var $pricesOfRivals;	// comma separated prices of rivals
		var $withAuto;			// is AutoBroker enabled? (YD)
		var $priceAuto;			// price of autobroker (YD)
		var $priceMinLimit;		// min price to set
		var $prio;				// -100 to 100 - the more, the bigger, 0 = default priority

		var $source;

		var $updatedOn;
		var $createdOn;
	}
?>
