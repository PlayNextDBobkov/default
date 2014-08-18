<?
	/**
		Base page entity.
	*/
	class Page extends WebPage {

		protected function beforeSave() {
			parent::beforeSave();

			if ( !$this->departmentId ) unset($this->departmentId);
		}

		protected function afterSave() {
			parent::afterSave();

			// save actionData:
			$data = $this->data();
			if ( isset($data["actionDataId"]) ) {
				DB::q("DELETE FROM page_action_data_page WHERE pageId='".$this->id."'");
				if ( $data["actionDataId"] ) DB::q("INSERT page_action_data_page SET pageId='".$this->id."', dataId='".s($this->data("actionDataId"))."'");
			}
		}

		protected function setDefaultValues() {
			$this->isActive = 1;
			$this->action = "InternalPage";
		}
	}
?>
