<?
	class GenerateKeywords extends PublicPage {
		protected function checkAccess() {
			parent::checkAccess();
		}

		public function url() {
			return "/";
		}

		protected function initCSS() {
			parent::initCSS();
		}

		protected function init() {
			parent::init();

			$this->cssFiles["table.css"] = true;
		}

		// hide nav path:
		protected function showNavPath() {
		}

		protected function makeWords() {
			$words1 = array(
				array("fiat", "фиат", array("500", "ducato", "dolbo", "cargo", "panorama", "freemont", "punto",
					"дукато", "долбо", "добло", "карго", "панорама", "фримонт", "пунто", "коммерческий", "микроавтобус", "фургон", "автобус", "маршрутка")),
				array("jeep", "джип", array("cherokee", "grand", "grand cherokee", "compass", "wrangler", "libery", "commander",
					"чероки", "гранд", "гранд чероки", "компас", "компасс", "ранглер", "вранглер", "либерти", "командер", "коммандер")),
				array("ssangyong", "ссангйонг", "ссанг йонг", "ssang yong", array("actyon", "action", "aktion", "aktyon", "kyron", "kiron", "actyon sport",
					"actyon sports", "rexton", "rekston", "stavic",
					"актион", "актйон", "эктион", "кирон", "кайрон", "актион спорт", "актион спортс", "рекстон", "ставик")),
				array("citroen", "ситроен", array("с4", "с3", "с5", "с1", "ds3", "ds4", "ds5", "c-elysee", "berlingo", "trek", "berlingo trek", "c5 tourer", "berlingo multispace", "c4 picasso", "c4 grand", "c4 gran picasso", "c3 picasso", "c4 aircross", "jumpy", "jumper", "jumper minibus", "berlingo грузовой", "jumper шасси", "jumpy fourgon", "jumpy furgon", 
					"с4", "с3", "с5", "с1", "дс3", "дс4", "дс5", "с-элизи", "елисей", "с-елизи", "берлинго", "трек", "берлинго трек", "с5 турер", "берлинго мультиспейс", "с4 пикасо", "с4 пикассо", "с4 гранд", "с4 гранд пикассо", "с3 пикассо", "с4 аиркросс", "джампи", "джампер", "джампер минибас", "бас", "коммерческий", "берлинго грузовой", "грузовой", "джампер шасси", "шасси", "джампи фургон", "фурггон", "микроавтобус", "автобус", "маршрутка")),
				array("daewoo", "dewoo", "деу", "дэу", array("matiz", "matis", "nexia", "gentra",
						"матиз", "матис", "нексиа", "нэксиа", "нэксия", "нексия", "гентра", "джентра")),
				array("iveco", "ивеко", array("daily", "dayly",
						"дейли", "дэйли", "дайли",
						"chassis",
						"коммерческий", "грузовой", "фургон", "шасси")),
				);
			$words2 = array("цена", "купить", "дилер", "официальный", "салон", "продажа", "2013", "2014", "2012", "новый", "характеристики", "комплектации",
				"фото", "видео", "отзывы",
				"параметры", "тест-драйв", "тест драйв", "заказать", "форум", "клуб", "дешево", "скидки", "акции", "спецпредложения",
				"кредит", "автокредит", "лизинг", "калькулятор", "мнения", "сравнение", "с пробегом", "обменять", "прайс", "для бизнеса", "конкуренты", "санкт-петербург", "спб", "питер", "москва", "мск", "трейд ин"
				);

			foreach ( $words1 as $set ) {
				$brands = array();
				$cars = array();

				foreach ( $set as $word ) {
					if ( is_array($word) ) $cars = array_merge($cars, $word);
					else $brands[] = $word;
				}

				foreach ( $brands as $brand ) {
					print $brand.LF;
					foreach ( $cars as $car ) {

						print $brand." ".$car.LF;
						print $car.LF;

						foreach ( $words2 as $word2 ) {
							print $brand." ".$car." ".$word2.LF;
						}
					}
				}
			}

		}

		protected function showBody() {
			$this->makeWords();
			die();
?>
<h1><?= p($this->client->name) ?></h1>
<table class="table">
<tr>
	<th width="20%">Кампания</th>
	<th width="10%">Дата запуска</th>
	<th width="10%">Кликов</th>
	<th width="10%">Показов</th>
	<th width="10%">CTR</th>
	<th width="10%">Баланс, руб.</th>
	<th width="10%">Статус</th>
</tr>
<?
				$now = Date::now();
				foreach ( $this->campaigns as $item ) {
?>
<tr>
	<td><a href="<?= $item->url() ?>"><b><?= p($item->name) ?></b></a></td>
	<td class="hC"><?= Date::mysql2DMY($item->startsOn) ?></td>
	<td class="hR"><?= number_format($item->clicks, 0, ",", " ") ?></td>
	<td class="hR"><?= number_format($item->imps, 0, ",", " ") ?></td>
	<td class="hR"><?= $item->imps?number_format(($item->clicks/$item->imps)*100, 1, ",", " "):"-" ?></td>
	<td class="hR"><?= number_format($item->balance, 2, ",", " ") ?></td>
	<td class="hC"><?= $item->status() ?></td>
</tr>
<?
				}
?>
</table>
<?
		}
	}
?>