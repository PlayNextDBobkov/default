<?
	class User extends Entity {

		const SESSION					= "user";
		const COOKIE_REMEMBER			= "vUserIdRemember";
		const COOKIE_REMEMBER_EXPIRY	= 1296000;	// 15 days - we rememeber access for explicit login
		const COOKIE_LAST				= "vUserId";
		const COOKIE_LAST_EXPIRY		= 15552000;	// 180 days - we track user implicitly (for admin/stats purposes)

		// @PrimaryKey
		// @Name("Айдиха")
		var $id;

		// @Type("varchar(32)")
		// @Length(32)
		// @Name("Фамилия")
		var $nameLast;
		// @Name("Имя")
		var $nameFirst;
		// @Name("Отчество")
		var $nameMiddle;

/******************************
	Static methods
******************************/

		/**
		 * Detects and optionally fetches current user from DB (Customer/Manager).
		 * First, checks $_SESSION[User::SESSION] - it may contain current user object.
		 * If not - checks $_COOKIE[User::COOKIE_REMEMBER] - it may contain current vUserId.
		 * @return object Customer | Manager | User
		 */
		public static function current() {
			// is this user logged in?
			$userData = $_SESSION[User::SESSION];
			if ( $userData ) {
				// yes, take user object from sesion:
				$user = unserialize($userData);
				if ( is_object($user) ) return $user;
			}
			else {
				// no, but check if this user is remembered in loing cookies:
				$rememberedVUserId = $_COOKIE[User::COOKIE_REMEMBER];
				if ( $rememberedVUserId ) {
					// yes, we can log in this one:
					$user = User::loginById($rememberedVUserId);
					return $user;
				}
			}
			return NULL;
		}

		/**
		 * Passed argument $user is either Customer or Manager.
		 * Make sure, $user contains id property.
		 * @param Customer/Manager $user
		 * @return Customer/Manager 
		*/
		public static function login($user, $remember=false) {
			// store user object in session:
			$_SESSION[User::SESSION] = serialize($user);

			// refresh vUserId in cookies:
			if ( $remember ) setcookie(User::COOKIE_REMEMBER, $user->vUserId(), time()+User::COOKIE_REMEMBER_EXPIRY, $path="/");
			setcookie(User::COOKIE_LAST, $user->vUserId(), time()+User::COOKIE_LAST_EXPIRY, $path="/");
			
			return $user;
		}

		/**
		 * Fetches a Customer/Firm object by a specified v_user.id (vUserId).
		 * @param String $id - Important! This is a vUserId!
		 * @return Customer/Firm 
		 */
		public static function loginById($id) {
			$u = self::fetchById($id);
			return self::login($u);
		}

		/**
		 * Detects and fetches current user from DB (Customer/Manager).
		 * First, checks $_SESSION[User::SESSION] - it may contain current user object.
		 * If not - checks $_COOKIE[User::COOKIE_REMEMBER] - it may contain current vUserId.
		 * Always(!) fetches Customer or Manager from DB.
		 * @return object Customer | Manager
		 */
		public static function fetchCurrent() {
			$user = User::current();	// take some basic data from session
			if ( !$user || !$user->vUserId() ) return NULL;
			
			// refetch full user record from DB (either Customer or Manager):
			$user = User::fetchById($user->vUserId());
			if ( !$user ) return NULL;
			
			// update lastSeenOn:
			$user->lastSeenOn = Date::nowMySQL();
			$user->save(false, false);	// skip validation & triggers
			
			// relogin - update user in session:
			User::login($user);
			
			return $user;	// this is a Customer or Manager
		}

		/**
		 * Fetches a Customer/Manager/... object by a specified column in v_user view.
		 * @param String $by A column in v_user table.
		 * @param Mixed $value
		 * @return Customer/Manager/(any supported entity)
		 */
		public static function fetchBy($by, $value) {
			// first we fetch a virtual record User:
			$u = DB::fetchOne("User", "SELECT u.*"
				." FROM v_user u"
				." WHERE"
				." u.`".$by."`='".s($value,1)."'");
			if ( !$u ) return NULL;

			// now fetch authentic record:
			$entity = $u->data("entity");
			eval($user." = ".$entity."::fetchForUser(\$u);");

			return $user;
		}

		/**
		 * Fetches a Customer/Firm object by a specified v_user.id (vUserId).
		 * @param String $id Important! This is a vUserId!
		 * @return Customer/Firm 
		 */
		public static function fetchById($id) {
			return self::fetchBy("id", $id);
		}



/******************************
	Public methods
******************************/
		
		public function showForm_name() {
?>
<tr>
	<th>ФИО</th>
	<td><input name="name" value="<?= p($this->name) ?>" maxlength="32"></td>
</tr>
<?
		}
	}
?>
