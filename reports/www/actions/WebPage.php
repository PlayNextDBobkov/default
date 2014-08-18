<?
	/**
		Most base class for all web pages of this site.
		All pages of this site are derived from this class.
		This is not yet extended for specific DB properties.
	*/
	class WebPage extends PageEntity {
		/**
			Creates a string of "smth"+"smth-part2"+"url-part3" to mask URLs.
			Intended to be used in JS secured forms.
		*/
		public static function maskedFormURL($url) {
			if ( strlen($url) > 4 ) return substr($url, 0, 2)."\"+\"".substr($url, 2, 2)."\"+\"".substr($url, 4);
			return substr($url, 0, 1)."\"+\"".substr($url, 1);
		}

		protected function initCSS() {
			//$this->css["div"] = "box-sizing:border-box;-moz-box-sizing:border-box;";
			$this->css[".clear"] = "font-size:0;clear:both;width:100%;overflow:hidden;height:0;line-height:0";
			$this->css[".s"] = "font-weight:normal;font-size:0.7em;";
			$this->css[".t"] = "border-collapse:collapse;border-spacing:0;";
			$this->css[".t td"] = "padding:0";
			$this->css[".hL"] = "text-align:left";
			$this->css[".hC"] = "text-align:center";
			$this->css[".hR"] = "text-align:right";
			$this->css[".vC"] = "vertical-align:middle";
			$this->css[".vT"] = "vertical-align:top";
			$this->css[".vB"] = "vertical-align:bottom";
			$this->css[".nw"] = "white-space:nowrap";
			$this->css[".m0"] = "margin:0";
			$this->css[".p0"] = "padding:0";
			$this->css[".b0"] = "border:0";
			$this->css[".mt0"] = "margin-top:0";
			$this->css[".clickable"] = "cursor:pointer";
			$this->css[".hidden"] = "display:none !important";
			$this->css[".visible"] = "display:block";
			$this->css[".visibleTR"] = "display:table-row";
			$this->css[".invisible"] = "visibility:hidden";
			$this->css[".display"] = "display:block";
			$this->css["ul"] = "margin:0;padding:0;list-style:none";
			$this->css["li"] = "margin:0;padding:0;";
			$this->css["table"] = "border-spacing:0;border-collapse:collapse;";
			$this->css["td"] = "padding:0;";
			$this->css["th"] = "padding:0;";
			$this->css["img"] = "border:0;";

			// size classes:
			$r = 100 / 12; //12 columns
			$this->css[".w1"] = "width:".round($r*1,1)."%";
			$this->css[".w2"] = "width:".round($r*2,1)."%";
			$this->css[".w3"] = "width:".round($r*3,1)."%";
			$this->css[".w4"] = "width:".round($r*4,1)."%";
			$this->css[".w5"] = "width:".round($r*5,1)."%";
			$this->css[".w6"] = "width:".round($r*6,1)."%";
			$this->css[".w7"] = "width:".round($r*7,1)."%";
			$this->css[".w8"] = "width:".round($r*8,1)."%";
			$this->css[".w9"] = "width:".round($r*9,1)."%";
			$this->css[".w10"] = "width:".round($r*10,1)."%";
			$this->css[".w11"] = "width:".round($r*11,1)."%";
			$this->css[".w12"] = "width:100%";
			$this->css[".w100"] = "width:100%";
			$this->css[".wa"] = "width:auto";
			$this->css[".w32px"] = "width:32px";
			$this->css[".w64px"] = "width:64px";
			$this->css[".w128px"] = "width:128px";
			$this->css[".w256px"] = "width:256px";
			$this->css[".w512px"] = "width:512px";
			$this->css[".w640px"] = "width:640px";

			$this->css[".mb0-25"] = "margin-bottom:0.25em";
			$this->css[".mb0-5"] = "margin-bottom:0.5em";
			$this->css[".mb1"] = "margin-bottom:1em";
			$this->css[".mb1-5"] = "margin-bottom:1.5em";
			$this->css[".mb2"] = "margin-bottom:2em";
			$this->css[".mb3"] = "margin-bottom:3em";

			// dummy - needed for acquiring screen width:
			$this->css["div#dummy-width"] = "position:absolute;width:100%;top:0;left:0;";
		}

		protected function init() {
			parent::init();
		}

		protected function showBeforeBody() {
?>
<div id="content"><div id="contentInner">
<?
		}

		protected function showAfterBody() {
?>
</div><? //contentInner ?>
<?
			$this->showBeforeContentEnd();
?>
</div><? //content ?>
<?
			$this->showAfterContentEnd();
		}

		protected function showBeforeContentEnd() {
		}

		protected function showAfterContentEnd() {
		}
	}
?>