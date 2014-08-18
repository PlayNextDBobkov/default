<?
	require_once("ConfigBase.php");

	/**
		Overrided for local environment.
	*/
	class Config extends ConfigBase {
		const IMAGE_MAGICK_PATH		= "C:/server/ImageMagick-6.6.2-Q16";

		const URL					= "http://carlcar.dimas";
		const URL_RU				= self::URL;
		//const URL_EN				= "http://en.carlcar.spider";

		const IS_LOGGER_ENABLED		= true;

		// carjob.spider:
		const YANDEX_MAP_API_KEY	= "AFG2504BAAAAQcc3ewIAiQRdTgTuRVQU2mNflXCuny3u0jgAAAAAAAAAAADTY_STUjwGeLA1cHs--e_Cv4CroQ==";

		const DB_HOST				= "localhost";
	}
?>