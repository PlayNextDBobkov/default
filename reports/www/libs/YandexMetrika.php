<?
	class YandexMetrika {
		const AUTH_URL		= "https://oauth.yandex.ru/token";
		const SERVICE_URL	= "http://api-metrika.yandex.ru";

		// these two are needed for auth (so called token auth):
		public $token			= NULL;
		public $applicationId	= NULL;
		public $counterId	= NULL;

		private $http;
		private $client;
		private $campaigns;

		public function YandexMetrika($applicationId, $token, $counterId=NULL) {
			$this->token = $token;
			$this->applicationId = $applicationId;
			$this->counterId = $counterId;
		}

		public function request($method, $params=NULL) {

			// create request:
			/*$request = array(
				'oauth_token' => $this->token,
				'client_id' => $this->applicationId,
				'param' => $params?self::utf8($params):NULL,
				'locale' => 'ru',
			);

			// send this to YD:
			$request = json_encode($request);
			$opts = array(
				'http'=>array(
					'method'=>"GET",
					'content'=>$request,
				)
			); 
			$context = stream_context_create($opts);
			$json = file_get_contents(self::SERVICE_URL."/".$method.".json", 0, $context);*/

			$kvs = array();
			$kvs[] = "oauth_token=".$this->token;
			if ( $this->counterId ) $kvs[] = "id=".$this->counterId;
			if ( is_array($params) ) {
				foreach ( $params as $k=>$v ) {
					$kvs[] = $k."=".$v;
				}
			}
			$params = implode("&", $kvs);
			$json = file_get_contents(self::SERVICE_URL.$method.".json?".$params);
			return json_decode($json);
		}

/**************************************

Private routines.

***************************************/

		private function utf8($struct) {
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