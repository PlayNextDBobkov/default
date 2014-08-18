<?
	class CampaignStatsPage extends PublicPage {

		public function initJSON() {
			$this->jsopn = $_POST;
		}

		protected function checkAccess() {
			parent::checkAccess();
		}

		protected function initCSS() {
			parent::initCSS();

			$this->css["form#formFilter"] = "width:30em";
			//$this->css["form#formFilter div.iCalendar"] = "width:10em";
		}

		protected function init() {
			$this->campaigns = Cache::get(Campaign::CACHE_LIST);
			$this->campaign = $this->campaigns[$_GET['utm']];
			if ( !$this->campaign ) go("/");
			
			parent::init();

			$this->cssFiles["table.css"] = true;
			$this->cssFiles["Calendar.css"] = true;
			$this->jsFiles["Calendar.js"] = true;
			$this->jsFiles["CampaignStatsPage.js"] = true;

			$this->h1 = $this->campaign->name;
		}

		protected function showBody() {
			parent::showBody();

			$dateStart = $_GET['dateStart'] ? $_GET['dateStart'] : "2014-06-12";
			$dateStop = $_GET['dateStop'] ? $_GET['dateStop'] : "2014-06-24";

			$report = $this->campaign->reportCache($dateStart, $dateStop);
			if ( !$report ) {
?>
<h2>Создаем отчет...</h2>
<div><img src="/i/busy.gif"></div>
<SCRIPT LANGUAGE="JavaScript">
<!--
CampaignStatsPage.isWaitingForReport=true;
//-->
</SCRIPT>
<?
				// first, check is the report for this campaign ready?
				$reports = $this->yd()->reports();
				if ( sizeof($reports) ) {
					foreach($reports as $report) {
						print "Отчет [".$report->ydId."]: ".$report->status().($report->isReady()?", ".$report->url():"").LF;
						if ( $report->isReady() ) {
							///$report->download();
							if ( $report->campaignYdId() == $this->campaign->ydId ) {
								print "Это отчет по искомой кампании [".$report->campaignYdId()."].".LF;

							}
						}
						//$r = $yd->request("DeleteReport", $report->ReportID);
					}
				}
				else {
					print "Нет готовых отчетов. Создаем новый:".LF;
					$report = $this->makeReport($this->campaign, $dateStart, $dateStop);
					print "Создается отчет [".$report->ydId."]...".LF;
				}

				return;
			}
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

<table class="table">
<tr>
	<th rowspan="2">Кампания</th>
	<th colspan="2">Клики</th>
	<th rowspan="2">Отказы</th>
</tr>
<tr>
	<th>ЯД</th>
	<th>Метрика</th>
</tr>
<?
			$datetime1 = new DateTime($dateStart);
			$datetime2 = new DateTime($dateStop);
			$interval = $datetime1->diff($datetime2);
			$days = intval($interval->format('%a'));
			for ( $i=0; $i<=$days; $i++ ) {
				$date = $datetime1->add(new DateInterval('P'.$i.'D'));
?>
<tr>
	<td><?= $date->format('d.m.Y') ?></td>
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

		protected function yd() {
			if ( $this->yd ) return $this->yd;
			return $this->yd = new YandexDirect(Temp::LOGIN, Temp::APP_ID, Temp::TOKEN);
		}

		protected function makeReport($campaign, $dateStart, $dateStop) {
			$yd = $this->yd();

			// delete all existing reports:
			/*$resp = $yd->request("GetReportList");
			foreach($resp->data as $report) {
				print "Удаляем отчет [".$report->ReportID."] ...".LF;
				//$r = $yd->request("DeleteReport", $report->ReportID);
			}*/

			$p = array_merge(array(
				"CampaignID"		=> $campaign->ydId,
				"GroupByColumns"	=> array("clBanner", "clDate"),
				), array(
				"StartDate" => $dateStart,
				"EndDate" => $dateStop,
				));
			$resp = $yd->request("CreateNewReport", $p);
			print "Кампания ".$campaign->name." [".$campaign->ydId."] - создается отчет ".$resp->data."...".LF;

			$report = new YandexDirectReport();
			$report->ydObject = $resp;
			$report->ydId = intval($resp->data);
			return $report;


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



				$chunk[$resp->data] = $c;
			}

			die();
		}

		protected function ydReport($campaign, $dateStart, $dateStop) {
			if ( true || !self::IS_CACHE_ENABLED || !($markers = Cache::get("YD_REPORT"+md5($params))) ) {

				$yd = $this->yd();

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
	}
?>