<?
	// @AutoIncrement(false);
	class Campaign extends Entity {

		const CACHE_LIST	= "campaignsFromYD";

		// @PrimaryKey
		var $id;
		var $clientId;
		var $idYD;
		var $idGA;
		var $name;
		var $objectYD;
		var $objectGA;
		var $adsYD;
		var $adsGA;
		var $clicks;
		var $imps;
		var $sum;
		var $balance;
		var $isArchived;
		var $isModerated;
		var $isRunning;

		// @Default(1)
		var $isActive;
		var $syncedOn;
		
		var $createdOn;
		var $updatedOn;

		function reportCache($dateStart, $dateStop) {
			return Cache::get("YD_REPORT_".$this->utm."_".$dateStart."_".$dateStop);
		}

		function urlStats() {
			return "/stats/".$this->utm."/";
		}

		/*function fetchList($view=NULL) {
			switch ( $view ) {
				default:
					return Campaign::fetch("SELECT * FROM campaign");
			}
		}*/

		static function viewList($view=NULL, $items=NULL) {
			if ( !sizeof($items) ) return;
?>
<table class="table">
<tr>
	<th rowspan="2">Кампания</th>
	<th rowspan="2">Кол-во объявлений</th>
	<th colspan="2">Клики</th>
	<th rowspan="2">Отказы</th>
</tr>
<tr>
	<th>ЯД</th>
	<th>Метрика</th>
</tr>
<?
			foreach ( $items as $id=>$item ) {
?>
<tr>
	<td><a href="<?= $item->urlStats() ?>"><?= p($id) ?></a></td>
	<td class="hC"><?= sizeof($item->ads) ?></td>
	<td class="hC">требуется отчет</td>
	<td class="hC">требуется отчет</td>
	<td class="hC">требуется отчет</td>
</tr>
<?
			}
?>
</table>
<?
		}
	}
?>
