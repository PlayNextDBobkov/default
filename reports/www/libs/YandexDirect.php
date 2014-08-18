<?
	class YandexDirectException extends E7Exception {
	}
	
	class YandexDirect {
		//const SERVICE_URL		= "https://soap.direct.yandex.ru/v4/json/";
		const SERVICE_URL		= "https://api.direct.yandex.ru/v4/json/";

		const ROUBLE_RATE		= 30;	// 30 roubles in 1 payment unit (у.е.);

		const IS_CACHE_ENABLED	= true;

		// Yandex.Direct user login:
		public $login			= NULL;

		// these two are needed for auth (so called token auth):
		public $token			= NULL;
		public $applicationId	= NULL;

		private $http;
		private $client;
		private $campaigns;

		public function YandexDirect($login, $applicationId, $token) {
			$this->login = $login;
			$this->token = $token;
			$this->applicationId = $applicationId;
		}

		public function campaigns($clientLogin) {
			if ( $this->campaigns[$clientLogin] ) return $this->campaigns[$clientLogin];

			if ( !self::IS_CACHE_ENABLED || !($campaigns = Cache::get("YandexDirect::campaigns()")) ) {
				// get all YD campaigns of our target client:
				$resp = $this->request("GetCampaignsList", array($clientLogin));

				// leave only active ones:
				$campaigns = array();
				foreach($resp->data as $c){
					if($c->StatusArchive != "No") continue;
					$campaigns[] = $c;
				}

				//print "<h2>Кампании из ЯД:</h2>";

				Cache::set("YD_CAMPAIGNS", $campaigns, 3600);
			}
			//else print "<h2>Кампании из кэша:</h2>";

			/*foreach ( $campaigns as $i=>$c ) {
				print ($i+1).". ".($c->IsActive!="Yes"?" [ВЫКЛЮЧЕНО] ":"").$c->CampaignID.". ".p($c->Name).LF;
			}
			print "<hr>";*/

			return $this->campaigns[$clientLogin] = $campaigns;
		}

		public function reports() {
			$reports = array();
			$resp = $this->request("GetReportList");
			foreach($resp->data as $r) {
				$report = new YandexDirectReport();
				$report->ydObject = $r;
				$report->ydId = intval($r->ReportID);
				$report->status = $r->StatusReport;
				$report->url = $r->Url;
				$reports[$report->ydId] = $report;
			}

			return $reports;
		}

		protected static function makeReport($campaign, $dateStart, $dateStop) {
			$yd = $this->yd();

			// delete all existing reports:
			/*$resp = $yd->request("GetReportList");
			foreach($resp->data as $report) {
				print "Удаляем отчет [".$report->ReportID."] ...".LF;
				//$r = $yd->request("DeleteReport", $report->ReportID);
			}*/

			$p = array_merge(array(
				"CampaignID"		=> $campaign->ydId,
				"GroupByColumns"	=> array("clBanner"),
				), array(
				"StartDate" => $dateStart,
				"EndDate" => $dateStop,
				));
			$resp = $yd->request("CreateNewReport", $p);
			print "Кампания ".$campaign->name." [".$campaign->ydId."] - создается отчет ".$resp->data."...".LF;

			$report = new YDReport();
			$report->ydObject = $resp;
			$report->ydId = $resp->data;
			return $report;
		}

		/**
			Returns list of clients.
		*/
		public function clients() {
			$r = $this->request("GetClientsList");
			return self::in("Client", $r->data);
		}

		/**
			Export new client.
		*/
		public function createClient($a) {
			///$data = self::outClient($a);
			$data = array(
				"Login"		=> $a->login,
				"Name"		=> $a->nameFirst,
				"Surname"	=> $a->nameLast,
				);
			$r = $this->request("CreateNewSubclient", $data);
			return self::in("Client", $r->data);
		}

		/*public function campaigns($client) {
			$data = array($client->login);
			$r = $this->request("GetCampaignsList", $data);
			return self::in("Campaign", $r->data, $client);
		}

		public function ads($campaign) {
			$data = array(
				"CampaignIDS"	=> array($campaign->idYD)
				);
			$r = $this->request("GetBanners", $data);
			return self::in("Ad", $r->data, $campaign);
		}*/

/**************************************

Static routines.

***************************************/

		/**
			Transfers channel data object into local object.
		*/
		public static function in($entity, $items, $parent=NULL) {
			if ( !is_array($items) ) $items = array($items);

			$newList = array();
			foreach ( $items as $data ) {
				//eval("\$a = new ".$entity."();");
				///$a->applyYD($item);
				eval("\$a = self::in".$entity."(\$data, \$parent);");
				$newList[] = $a;
			}

			return $newList;
		}

		public static function inAd($data, $campaign=NULL) {
			$a = new Ad();
			if ( $campaign ) $a->campaignId = $campaign->id;
			$a->idYD = $data->BannerID;
			$a->name = $data->Title;
			$a->text = $data->Text;
			$a->url = $data->Href;

			$a->contactName = $data->ContactInfo->ContactPerson;
			$a->contactCountry = $data->ContactInfo->Country;
			$a->contactCity = $data->ContactInfo->City;
			$a->contactStreet = $data->ContactInfo->Street;
			$a->contactHouse = $data->ContactInfo->House;
			$a->contactBuild = $data->ContactInfo->Build;
			$a->contactApartment = $data->ContactInfo->Apart;
			$a->contactPhone = $data->ContactInfo->CountryCode." ". $data->ContactInfo->CityCode." ".$data->ContactInfo->Phone." # ".$data->ContactInfo->PhoneExt;
			$a->contactCompany = $data->ContactInfo->CompanyName;
			$a->contactEmail = $data->ContactInfo->ContactEmail;
			$a->contactOGRN = $data->ContactInfo->OGRN;
			$a->contactWorkTime = $data->ContactInfo->WorkTime;
			$a->contactMore = $data->ContactInfo->ExtraMessage;

			$keywords = array();
			foreach ( $data->Phrases as $keywordData ) {
				$k = new Keyword();
				$k->idYD = $keywordData->PhraseID;
				$k->name = $keywordData->Phrase;
				$k->price = $keywordData->Price * self::ROUBLE_RATE;				// price set by user
				$k->priceSites = $keywordData->ContextPrice * self::ROUBLE_RATE;	// price set by user for site network
				$k->clicks = $keywordData->Clicks;
				$k->imps = $keywordData->Shows;
				$k->priceMin = $keywordData->Min;
				$k->priceMax = $keywordData->Max;
				$k->priceMinSuper = $keywordData->PremiumMin * self::ROUBLE_RATE;
				$k->priceMaxSuper = $keywordData->PremiumMax * self::ROUBLE_RATE;
				$k->hasLowCTR = $keywordData->LowCTR=="Yes"?1:0;
				$k->hasLowCTRSites = $keywordData->ContextLowCTR=="Yes"?1:0;
				$k->coverage = $keywordData->Coverage->Probability;
				$k->coverageSites = $keywordData->ContextCoverage->Probability;
				if ( $keywordData->Prices ) {
					$k->pricesOfRivals = array();
					foreach ( $keywordData->Prices as $p ) {
						$k->pricesOfRivals[] = $p * self::ROUBLE_RATE;
					}
					$k->pricesOfRivals = implode(",", $k->pricesOfRivals);
				}
				$k->withAuto = $keywordData->AutoBroker=="Yes"?1:0;
				$k->priceAuto = $keywordData->CurrentOnSearch * self::ROUBLE_RATE;
				$k->priceMinLimit = $keywordData->MinPrice * self::ROUBLE_RATE;
				$prios = array(
					"Low"		=> Keyword::PRIO_LOW,
					"Medium"	=> Keyword::PRIO_DEFAULT,
					"High"		=> Keyword::PRIO_HI,
					);
				$k->prio = $prios[$keywordData->AutoBudgetPriority];
				$k->isModerated = $keywordData->StatusPhraseModerate=="Yes"?1:0;

				$k->source = json_encode($keywordData);

				$keywords[] = $k;
			}
			$a->setData("keywords", $keywords);

			$a->isArchived = $data->StatusArchive=="Yes"?1:0;
			$a->isDraft = $data->StatusBannerModerate=="New "?1:0;
			if ( !$a->isDraft ) {
				$a->isModerated = ($data->StatusBannerModerate=="Yes"||$data->StatusPhrasesModerate=="Yes"||$data->StatusPhoneModerate=="Yes"||$data->StatusSitelinksModerate=="Yes")?1:0;
				$a->isRejected = ($data->StatusBannerModerate=="No"||$data->StatusPhrasesModerate=="No"||$data->StatusPhoneModerate=="No"||$data->StatusSitelinksModerate=="No")?1:0;
				if ( $a->isRejected ) {
					$a->rejectReason = $data->ModerateRejectionReasons->Text;
				}
			}
			$a->isRunning = $data->IsActive=="Yes"?1:0;
			$a->isActive = $data->StatusShow=="Yes"?1:0;
			$a->syncedOn = date("Y-m-d H:i:s");
			$a->minusKeywords = implode(",", $data->MinusKeywords);

			$a->source = json_encode($data);
			return $a;
		}

		public static function inCampaign($data, $client=NULL) {
			/*
				YD fields:
					CampaignID
					Login
					Name
					StartDate
					Sum
					Rest
					SumAvailableForTransfer
					Shows
					Clicks
					Status
					StatusShow
					StatusArchive
					StatusActivating
					StatusModerate
					IsActive
					ManagerName
					AgencyName
			*/
			$a = new Campaign();
			$a->idYD = $data->CampaignID;
			$a->name = $data->Name;
			if ( $client ) $a->clientId = $client->id;
			$a->startsOn = $data->StartDate;
			$a->clicks = $data->Clicks;
			$a->imps = $data->Shows;
			$a->sum = $data->Sum;
			$a->balance = $data->Rest * self::ROUBLE_RATE;
			$a->isArchived = $data->StatusArchive=="Yes"?1:0;
			$a->isModerated = $data->StatusModerate=="Yes"?1:0;
			$a->isRunning = $data->IsActive=="Yes"?1:0;
			$a->isActive = $data->StatusShow=="Yes"?1:0;
			$a->syncedOn = date("Y-m-d H:i:s");

			$a->objectYD = json_encode($data);
			return $a;
		}

		public static function inClient($data) {
			/*
				YD fields:
					Phone
					DateCreate
					FIO
					Email
					Login
					StatusArch
					Discount
					SmsPhone
					CampaignEmails
					ClientRights
					Role
					NonResident
					SendNews
					SendAccNews
					SendWarn
			*/
			$a = new Client();
			$a->name = $data->FIO;
			$a->login = $data->Login;
			$a->registeredOn = $data->DateCreate;
			$a->phone = $data->Phone;
			$a->email = $data->Email;
			$a->discount = $data->Discount;
			$a->isArchived = $data->StatusArch=="Yes"?1:0;
			///$a->isActive = $data->StatusArch=="Yes"?0:1;
			return $a;
		}

		public function outClient($a) {
			/*
				YD fields:
					Phone
					DateCreate
					FIO
					Email
					Login
					StatusArch
					Discount
					SmsPhone
					CampaignEmails
					ClientRights
					Role
					NonResident
					SendNews
					SendAccNews
					SendWarn
			*/
			$data = array(
				"FIO"			=> $a->name,
				"Login"			=> $a->login,
				"DateCreate"	=> $a->registeredOn,
				"Phone"			=> $a->phone,
				"Email"			=> $a->email,
				"Discount"		=> $a->discount,
				"StatusArch"	=> $a->isActive?"Yes":"No",
				);
		}

		public function request($method, $params=NULL) {

			// create request (token variant):
			$request = array(
				'token'=> $this->token,//'ZWAjroW8u4MuQ9q6 ', 
				'application_id'=> $this->applicationId,
				'login'=> $this->login,
				'method'=> $method,
				'param'=> $params?self::utf8($params):NULL,
				'locale'=> 'ru',
			);

			// send this to YD:
			$request = json_encode($request);
			$opts = array(
				'http'=>array(
					'method'=>"POST",
					'content'=>$request,
				)
			); 
			$context = stream_context_create($opts);
			$json = file_get_contents(YandexDirect::SERVICE_URL, 0, $context);
			$resp = json_decode($json);
			if ( $resp->error_code ) throw new YandexDirectException($resp->error_str, $resp->error_code);
			return $resp;
		}

/**************************************

Private routines.

***************************************/

		private function utf8($struct) {
			if ( !is_array($struct) ) return $struct;
			foreach ($struct as $key => $value) {
				if (is_array($value)) {
					$struct[$key] = $this->utf8($value);
				}
				elseif (is_string($value)) {
					$struct[$key] = utf8_encode($value);
				}
			}
			return $struct;
		}
	}
?>