<?
	class ClientList extends InternalPage {

		public $title = "Наши клиенты";
		public $h1 = "Наши клиенты";

		public function url() {
			return PublicPage::CLIENT_LIST;
		}

		public function init() {
			// include css:
			$this->cssFiles["pages.css"] = true;
			$this->cssFiles["table.css"] = true;

			$this->items = DB::fetch("Client", "SELECT a.* FROM client a ORDER BY login");

			parent::init();
		}

		public function appendNavPath() {
			parent::appendNavPath();

			if ( $this->page > 0 ) {
				$np = new NavPathPage("Страница ".($this->page+1), "Страница ".($this->page+1), $this->url($this->page));
				$this->navPath[] = $np;
			}
		}

		public function showSubMenu() {
			Tag::menu(array("/ClientEdit.html"	=> "Добавить"));
		}

		public function showBody() {
			parent::showBody();
			
			if ( !sizeof($this->items) ) {
?>
<div class="info">Нет клиентов...</div>
<?
				return;
			}

				//Tag::pages($this->total, Client::PER_PAGE, $this->page);
?>
<table class="table">
<tr>
	<th width="14%">#</th>
	<th width="85%">Клиент</th>
	<th width="1%"></th>
</tr>
<?
			$i = 0;
			foreach ( $this->items as $item ) {
				$i++;
				$css = array();
				if ( $i % 2 == 0 ) $css[] = "tr2";
				if ( $item->isArchived ) $css[] = "off";
?>
<tr<?= sizeof($css)?" class='".implode(" ", $css)."'":"" ?>>
	<td class="hC"><?= $i ?></td>
	<td><a href="/campaigns/?filter=1&clientId=<?= $item->id ?>" title="Кампании"><?= p($item->login) ?></a> <span class="g">(<?= p($item->name) ?>)</span></td>
	<td class="hC" nowrap>
<a href="<?= PublicPage::CLIENT_EDIT ?>?id=<?= $item->id ?>" title="Редактировать"><img src="/i/icon-edit.gif" class="icon"></a>
	</td>
</tr>
<?
			}
?>
</table>
<?
				//Tag::pages($this->total, Client::PER_PAGE, $this->page, NULL, 1);
		}
	}
?>