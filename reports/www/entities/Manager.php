<?
	class Manager extends User {

		/**
		 * Fetches a Manager object by specified User record.
		 * @param User $user Valid user record (from v_user view).
		 * @return Manager
		 */
		public static function fetchForUser($user) {
			return DB::fetchOne("Manager", "SELECT u.*"
				.", ui.id AS imageId, ui.ext as imageExt, ui.width AS imageWidth, ui.height AS imageHeight"
				.", ui.cropX AS imageCropX, ui.cropY as imageCropY, ui.cropWidth AS imageCropWidth, ui.cropHeight AS imageCropHeight"
				.", (SELECT c.name FROM city c WHERE c.id=u.cityId) AS cityName"
				." FROM manager u"
				." LEFT JOIN manager_image ui ON u.id=ui.parentId"
				." WHERE u.id=".$user->data("realId"));
		}
	}
?>
