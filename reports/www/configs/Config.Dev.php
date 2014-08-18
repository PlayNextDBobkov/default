<?
	require_once("ConfigBase.php");

	/**
		Overrided for local environment.
	*/
	class Config extends ConfigBase {
		//const IMAGE_MAGICK_PATH		= "e:/ImageMagick-6.3.3-Q16";

		const URL					= "http://carlcar.spider";
		const URL_RU				= self::URL;
		//const URL_EN				= "http://en.carlcar.spider";

		const IS_LOGGER_ENABLED		= true;

		// carjob.spider:
		const YANDEX_MAP_API_KEY	= "AFehRk4BAAAAgXssBQIAVDdXWSBIlS2mxhUNP82xLfQOJOMAAAAAAAAAAACrxCYs0rpPHv8eHQY3tdbGamW0rA==";

		const DB_HOST				= "storage";
	}
?>