<?
	class ClientSyncYD extends InternalPage {

		public $title = "Синхронизация списка клиентов с Яндекс.Директ";
		public $h1 = "Синхронизация списка клиентов с Яндекс.Директ";

		public function url() {
			return PublicPage::CLIENT_SYNC_YD;
		}

		public function doPost() {
			// remember input in session:
			if ( !$_POST['pt'] ) $_POST['pt'] = WebPage::token();
			$_SESSION[$_POST['pt']] = $_POST;

			$logins = $_POST['logins'];
			$e = NULL;

			$yd = self::yandexDirect();

			if ( $_POST['isExport'] ) {
				//export:
				$items = DB::fetch("Client", "SELECT a.*, a.login AS id, a.id AS realId FROM client a WHERE login IN ('".implode("','", $logins)."')");
				foreach ( $items as $item ) {
					try {
						$yd->createClient($item);
					}
					catch ( Exception $e ) {
						break;
					}
				}
			}
			else {
				//import:
				$items = $yd->clients();
				foreach ( $items as $item ) {
					if ( in_array($item->login, $logins) ) {
						// insert new item:
						try {
							$item->save();
						}
						catch ( Exception $e ) {
							break;
						}
					}
				}
			}

			if ( $e ) {
				$_SESSION[$_POST['pt']]['errMessage'] = $e->message;
				$url = URL::appendParam($_SERVER['REQUEST_URI'], "err", 1);
			}
			else {
				unset($_SESSION[$_POST['pt']]['errMessage']);
				$url = URL::appendParam($_SERVER['REQUEST_URI'], "s", 1);
				$url = URL::removeParams($url, "err");
			}

			$url = URL::appendParam($url, "pt", $_POST['pt']);
			go($url);
		}

		public function initCSS() {
			parent::initCSS();
		}

		public function init() {
			// include css:
			$this->cssFiles["table.css"] = true;

			parent::init();

			// get clients by login as id:
			$this->clients = DB::fetch("Client", "SELECT a.*, a.login AS id, a.id AS realId FROM client a");

			$y = self::yandexDirect();
			$this->clientsYD = $y->clients();
		}

		public function appendNavPath() {
			parent::appendNavPath();
		}

		public function showBody() {
			parent::showBody();

			if ( $_GET['err'] ) {
?>
<div class="err"><?= p($_SESSION[$_GET['pt']]['errMessage']) ?></div>
<?
			}

			$all = array();
			foreach ( $this->clients as $item ) {
				$all[$item->login] = array("local" => $item);
			}
			if ( is_array($this->clientsYD) ) {
				foreach ( $this->clientsYD as $item ) {
					if ( !is_array($all[$item->login]) ) $all[$item->login] = array();
					$all[$item->login]["remote"] = $item;
				}
			}
			ksort($all);

			if ( sizeof($all) ) {

				//Tag::pages($this->total, Client::PER_PAGE, $this->page);
?>
<form action="<?= $_SERVER['REQUEST_URI'] ?>" method="POST">
<input type="hidden" name="pt" value="<?= p($_GET['pt']) ?>">
<table class="table">
<tr>
	<th width="4%">#</th>
	<th width="56%">Логин</th>
	<th width="20%">Локальная база</th>
	<th width="20%">База Яндекс.Директ</th>
</tr>
<?
			$i = 0;
			foreach ( $all as $items ) {
				$i++;
				$css = array();
				if ( $i % 2 == 0 ) $css[] = "tr2";
				//if ( !$item->isActive ) $css[] = "off";
				if ( ($items["local"] && !$items["remote"]) || (!$items["local"] && $items["remote"]) ) $css[] = "hot";

				if ( $items["local"] ) $item = $items["local"];
				else $item = $items["remote"];
?>
<tr<?= sizeof($css)?" class='".implode(" ", $css)."'":"" ?> onMouseOver="CSS.a(this,'over')" onMouseOut="CSS.r(this,'over')">
	<td class="hC"><?= $i ?></td>
	<td>
<input type="checkbox" id="item<?= $i ?>" name="logins[]" value="<?= p($item->login) ?>">
<label for="item<?= $i ?>"><?= p($item->login) ?> <span class="g">(<?= p($item->name) ?>)</span></label>
	</td>
	<td class="hC"><?= $items["local"]?Date::MySQL2DMYHM($item->createdOn):"" ?></td>
	<td class="hC"><?= $items["remote"]?($item->registeredOn?Date::MySQL2DMYHM($item->registeredOn):"создан без даты"):"" ?></td>
</tr>
<?
			}
?>
</table>
<div style="margin:1em 0 0 0"><input type="submit" value="Импорт" class="btn"> <input type="submit" name="isExport" value="Экспорт" class="btn"></div>
</form>
<?
				//Tag::pages($this->total, Client::PER_PAGE, $this->page, NULL, 1);
			}
			else {
?>
<div class="info">Нет клиентов...</div>
<?
			}
		}
	}
?>