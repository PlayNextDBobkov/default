<?
	class YandexDirectReportException extends E7Exception {
	}
	class YandeDirectReportStatusException extends YandexDirectReportException {
		var $errCode = "YandeDirectReportStatusException";
		var $message = "Отчет не готов.";
	}
	class YandeDirectReportURLException extends YandexDirectReportException {
		var $errCode = "YandeDirectReportURLException";
		var $message = "У отчета не инициализированный URL.";
	}
	class YandeDirectReportDownloadException extends YandexDirectReportException {
		var $errCode = "YandeDirectReportDownloadException";
		var $message = "Скачивание отчета не удалось.";
	}

	class YandexDirectReport extends Entity {

		const CACHE_LIST	= "report";

		var $id;
		var $ydId;
		var $status;
		var $url;
		var $ydObject;
		var $content;
		var $createdOn;
		var $updatedOn;

		function status() {
			switch ( $this->status ) {
				case NULL: return "Инициализация";
				case "Done": return "Готов";
				case "Pending": return "Готовится";
				default: return "Сбой";
			}
		}

		function url() {
			switch ( $this->status ) {
				case "Done": return $this->url;
				default: return NULL;
			}
		}
	
		function isReady() {
			switch ( $this->status ) {
				case "Done": return true;
				default: return false;
			}
		}

		function download() {
			if ( !$this->isReady() ) throw new YandeDirectReportStatusException();
			if ( !($url = $this->url()) ) throw new YandeDirectReportURLException();

			$this->content = file_get_contents($url);
			if ( !$this->content ) throw new YandeDirectReportDownloadException();
			return $this->content;
		}

		function campaignYdId() {
			if ( !$this->content ) $this->download();

			$xml = simplexml_load_string($this->content);
			return $xml->campaignID;
		}

	}
?>
