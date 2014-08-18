<?
	class HomePage extends PublicPage {

		const IS_CACHE_ENABLED		= true;

		public function initJSON() {
			$this->jsopn = $_POST;
		}

		protected function checkAccess() {
			parent::checkAccess();
		}

		public function url() {
			return "/";
		}

		protected function initCSS() {
			parent::initCSS();

			$this->css["form#formFilter"] = "width:30em";
			//$this->css["form#formFilter div.iCalendar"] = "width:10em";
		}

		protected function init() {
			parent::init();

			$this->cssFiles["table.css"] = true;
			$this->cssFiles["Calendar.css"] = true;
			$this->jsFiles["Calendar.js"] = true;
		}

		// hide nav path:
		protected function showNavPath() {
		}

		protected static function ydSourceIds() {
			return array("yd", "yandex");
		}

		protected function showBody() {

			$dateStart = $_GET['dateStart'] ? $_GET['dateStart'] : "2014-06-12";
			$dateStop = $_GET['dateStop'] ? $_GET['dateStop'] : "2014-06-24";

			/*$campaigns = $this->campaignsFromYD(Temp::CLIENT_ID);
			foreach ( $campaigns as $item) {
				$item->upsert();
			}*/
			//ksort($ydCampaigns);

			/*$metrikaMarkers = $this->metrikaMarkers(array(
				"date1" => preg_replace("/\D/", "", self::DATE_START),
				"date2" => preg_replace("/\D/", "", self::DATE_STOP),
				//"table_mode" => "tree"
				));

			$ydMarkers = $this->ydMarkers();
			ksort($ydMarkers);

			//$ydStats = $this->ydStats();
			$ydReport = $this->ydReport(array(
				"StartDate" => self::DATE_START,
				"EndDate" => self::DATE_STOP,
				));
			da($ydReport);
			die();*/
?>
<form id="formFilter">
<table class="form">
<tr>
	<td><div calendarName="startsOn" calendarValue="<?= $dateStart ?>"></div></td>
	<td><div class="iStatic">&mdash;</div></td>
	<td><div calendarName="stopsOn" calendarValue="<?= $dateStop ?>"></div></td>
</tr>
</table>
</form>

<?= Campaign::showList(); ?>
<?
		}

		protected function yd() {
			if ( $this->yd ) return $this->yd;
			return $this->yd = new YandexDirect(Temp::LOGIN, Temp::APP_ID, Temp::TOKEN);
		}

		protected function ydReport($params=NULL) {
			if ( true || !self::IS_CACHE_ENABLED || !($markers = Cache::get("YD_REPORT"+md5($params))) ) {

				$yd = $this->yd();
				$campaigns = $this->ydCampaigns();

				// delete all reports:
				$resp = $yd->request("GetReportList");
				foreach($resp->data as $report) {
					print "Удаляем отчет [".$report->ReportID."] ...".LF;
					$r = $yd->request("DeleteReport", $report->ReportID);
				}

				// get reports for all campaigns:
				print "<h2>Сбор отчетов:<h2>";
				$chunk = array();
				$i = 0;
				$campaignsByReport = array();
				foreach($campaigns as $i=>$c){

					$count = 0;
					while ( sizeof($chunk) >= 5 ) {
						$count++;
						if ( $count > 30 ) {
							throw new YandexDirectException("Слишком долго не могу дождаться отчетов...");
						}
						else if ( $count > 1 ) sleep(10);
						
						// check any report is ready:
						$resp = $yd->request("GetReportList");
						$isSmthDone = false;
						foreach($resp->data as $report) {
							if ( $chunk[$report->ReportID] && $report->StatusReport == "Done" ) {
								// this report is ready!
								$cr = $chunk[$report->ReportID];
								print "Отчет [".$report->ReportID."] для кампании ".$cr->name." [".$cr->CampaignID."] готов: ".$report->Url.LF;

								$data = file_get_contents($report->Url);
								Cache::set("YD_REPORT"+md5($params)+$report->ReportID, $data);

								print "Удаляем отчет [".$report->ReportID."] ...".LF;
								$r = $yd->request("DeleteReport", $report->ReportID);

								unset($chunk[$report->ReportID]);
							}
						}
					}

					$p = array_merge(array(
						"CampaignID"		=> $c->CampaignID,
						"GroupByColumns"	=> array("clBanner"),
						), is_array($params)?$params:array());
					$resp = $yd->request("CreateNewReport", $p);
					print ($i+1)." Кампания ".$c->name." [".$c->CampaignID."] ... создается отчет ".$resp->data.".".LF;

					$chunk[$resp->data] = $c;
				}

				die();
				
				print "<h2>Метки из ЯД:</h2>";
			}
			else {
				$campaignsByMarker = Cache::get("YD_CAMPAIGNS_BY_MARKER");
				print "<h2>Метки из кэша:</h2>";
			}

			if ( sizeof($markers) ) {
				$i = 0;
				foreach ( $markers as $marker=>$ads ) {
					$i++;
					$count = sizeof($ads);
					
					print $i.". ".$marker ." [".$campaignsByMarker[$marker]."]: ".$count." ".String::end($count, "объявление", "объявления", "объявлений").LF;
				}
			}
			else print "Нет меток...".LF;
			print "<hr>";

			return $markers;
		}

		/**
			Brings YD campaigns as local Campaign objects and distribute them by UTM_CAMPAIGN found in ads' URLs as Campaign.id.
		*/
		protected function campaignsFromYD($clientId) {
			if ( true || !self::IS_CACHE_ENABLED || !($camps = Cache::get(Campaign::CACHE_LIST)) ) {

				$client = Client::fetchById($clientId);
				if ( !$client ) throw new Exception("Клиент не найден по ID: ".$clientId);

				$yd = $this->yd();
				$campaigns = $yd->campaigns($client->idYD);

				// get all ads of this from all campaigns (by 10 campaigns per request):
				$ads = array();
				$chunk = array();
				$camps = array();
				foreach($campaigns as $i=>$c){
					$chunk[] = $c->CampaignID;
					if ( sizeof($chunk) < Temp::CAMPAIGNS_CHUNK && $i+1<sizeof($campaigns) ) continue;

					//print "Chunk: ".implode(",", $chunk).LF;

					$resp = $yd->request("GetBanners", array(
						"CampaignIDS" => $chunk
						));

					$chunk = array();

					// extract utm_campaign from ads:
					foreach($resp->data as $ad){
						if ( $ad->Domain != Temp::CLIENT_SITE ) {
							/*print "В объявлении ".$ad->BannerID."/".$ad->CampaignID." не целевой домен: ".$ad->Domain.LF;
							print p($ad->Title).LF;
							print p($ad->Text).LF;
							print p($ad->Href).LF;*/
							continue;
						}

						// check URL is with params:
						if($c->IsActive && $ad->IsActive && !preg_match(Temp::SOURCE_MASK_YD, $ad->Href) ) {
							print "В объявлении ".$ad->BannerID."/".$ad->CampaignID." не установлена UTM метка!".LF;
							print p($ad->Title).LF;
							print p($ad->Text).LF;
							print p($ad->Href).LF;
							print "<hr>";
							continue;
						}

						preg_match(Temp::SOURCE_MASK_MARKER, $ad->Href, $m);
						$marker = $m[1];

						/*if ( $markersByCampaignId[$ad->CampaignID] && $markersByCampaignId[$ad->CampaignID] != $marker ) {
							print "В объявлении ".$ad->BannerID."/".$ad->CampaignID." встретились множественные метки: ".$markersByCampaignId[$ad->CampaignID].", ".$marker.LF;
							print p($ad->Title).LF;
							print p($ad->Text).LF;
							print p($ad->Href).LF;
							print "<hr>";
							continue;
						}*/

						// store marker:
						if ( $camps[$marker] ) {
							$camp = $camps[$marker];
						}
						else {
							//print "Created campaign, clientId = ".$clientId.LF;
							$camp = new Campaign();
							$camp->clientId = $clientId;
							$camp->id = $marker;
							$camp->name = $marker;
							$camp->idYD = $ad->CampaignID;
							$camp->objectYD = serialize($c);
							$camp->adsYD = array();
						}

						$camp->adsYD[] = $ad;

						$camps[$marker] = $camp;
					}
				}

				foreach ( $camps as $camp ) {
					$camp->_countAds = sizeof($camp->adsYD);
					$camp->adsYD = serialize($camp->adsYD);
				}
				
				Cache::set(Campaign::CACHE_LIST, $camps, 3600);

				print "<h2>Метки из ЯД:</h2>";
			}
			else {
				$camps = Cache::get(Campaign::CACHE_LIST);
				print "<h2>Метки из кэша:</h2>";
			}

			if ( sizeof($camps) ) {
				$i = 0;
				foreach ( $camps as $marker=>$camp ) {
					$i++;
					
					print $i.". ".$marker ." [".$camp->idYD."]: ".$camp->_countAds." ".String::end($camp->_countAds, "объявление", "объявления", "объявлений").LF;
				}
			}
			else print "Нет меток...".LF;
			print "<hr>";

			return $camps;
		}

		protected function ydStats() {
			if ( !self::IS_CACHE_ENABLED || !($stats = Cache::get("YD_STATS")) ) {

				$yd = $this->yd();
				$campaigns = $this->ydCampaigns();

				// period in days:
				$datetime1 = new DateTime('2009-10-11');
				$datetime2 = new DateTime('2009-10-13');
				$interval = $datetime1->diff($datetime2);
				$days = intval($interval->format('%a'));

				$maxChunk = floor(1000 / $days);
				if ( !$maxChunk ) throw new YandexDirectException("Too big date interval for all campaigns for GetSummaryStat request.");
				
				// get all ads of this from all campaigns (by 10 campaigns per request):
				$ads = array();
				$chunk = array();
				foreach($campaigns as $i=>$c){
					$chunk[] = $c->CampaignID;
					if ( sizeof($chunk) < $maxChunk && $i+1<sizeof($campaigns) ) continue;

					print "Chunk: ".implode(",", $chunk).LF;

					$resp = $yd->request("GetSummaryStat", array(
						"CampaignIDS"	=> $chunk,
						"StartDate"		=> Temp::DATE_START,
						"EndDate"		=> Temp::DATE_STOP,
						));

					$chunk = array();

					// iterate all campaigns:
					$stats = array();
					foreach($resp->data as $statItem){
						$stats[$statItem->CampaignID]['clicks'] += $statItem->ClicksSearch + $statItem->ClicksContext;
						$stats[$statItem->CampaignID]['totalPrice'] += $statItem->SumSearch + $statItem->SumContext;
						//$stats[$statItem->CampaignID]['price'] += $statItem->SumSearch + $statItem->SumContext;
					}

					foreach($stats as $id => $stat){
						$stats[$id]['avgPrice'] = $stats[$id]['totalPrice'] / $stats[$id]['clicks'];
						///$stats[$id]['avgPrice'] = $stats[$id]['totalPrice'] / $stats[$id]['clicks'];
					}
				}
				
				Cache::set("YD_STATS", $stats, 3600);

				print "<h2>Статистика из ЯД:</h2>";
			}
			else print "<h2>Статистика из кэша:</h2>";

			return $stats;
		}

		protected function metrika() {
			if ( $this->metrika ) return $this->metrika;
			return $this->metrika = new YandexMetrika(self::APP_ID, self::TOKEN, self::CLIENT_YM_COUNTER);
		}

		protected function metrikaMarkers($params=NULL) {
			$markers = Cache::get("METRIKA_MARKERS");
			if ( $markers ) return $markers;

			$ym = $this->metrika();
			$resp = $ym->request("/stat/sources/tags", $params);

			$markers = array();
			foreach ( $resp->data as $marker ) {
				if ( $marker->name == self::MARKER_TYPE && in_array($marker->name_2, self::ydSourceIds()) ) {
					$markers[$marker->name_4] = $marker;
				}
			}

			Cache::set("METRIKA_MARKERS", $markers, 3600);

			print "<h2>Обновлены метки Метрики:</h2>";
			$i = 0;
			foreach ( $markers as $key=>$marker ) {
				$i++;
				print $i.". ".$key.": ".LF;
			}
			print "<hr>";
			return $markers;
		}
	}
?>