<?
	// All these includes will be available on all INTERNAL pages:
	require_once("PublicPage.php");

	class InternalPage extends PublicPage {

		protected function initCSS() {
			parent::initCSS();

			// printThis button:
			$this->css["div#printThis"] = "float:right;margin:0.25em 0 0 1em;width:122px;cursor:pointer;color:".PublicPage::CSS_A_COLOR;
			$this->css["div#printThis div.box"] = "padding:0 8px 4px 8px";
			$this->css["div#printThis div.link"] = "font-size:0.83em;line-height:1em;padding:6px 0 6px 0;margin:0;background:url('/i/10.gif') no-repeat right 6px;";
			$this->css["div#printThis.over div.link span.a2"] = "color:".PublicPage::CSS_A_COLOR_HOVER;
			$this->css["div#printThis.over div.box"] = "background:#eef;";
		}

		protected function init() {
			parent::init();

		}

		protected function showBody() {
			if ( $this->isPrintable ) {
?>
<div id="printThis" title="Распечатать эту страницу" onMouseOver="CSS.addClass(this,'over')" onMouseOut="CSS.removeClass(this,'over')" onClick="self.print()"><div class="box"><div class="link"><span class="a2">Распечатать</span></div></div></div>
<?
			}
			$this->showBodyH1();
			$this->showBodyText();
		}

	}
?>
