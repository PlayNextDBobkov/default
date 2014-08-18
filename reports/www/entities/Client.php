<?
	/**
		Client is the one who orders campaigns from agency.
		He has limited access to the system.
	*/
	class Client extends Entity {
		const PER_PAGE		= 10;

		var $id;
		var $idYD;
		var $idGA;
		var $name;
		var $nameFirst;
		var $nameLast;
		var $login;
		var $password;
		var $phone;
		var $email;
		var $discount;
		var $notes;
		var $isArchived;
		var $registeredOn;	// when registered in system
		var $createdOn;
		var $updatedOn;

		public static function fetchByLogin($login) {
			return DB::fetchOne("Client", "SELECT c.*"
				." FROM client c"
				." WHERE c.login='".s($login,1)."'");
		}

		public function url() {
			return "/campaigns/?filter=1&clientId=".$this->id;
		}

		protected function setDefaultValues() {
			$this->isActive = 1;
		}

		protected function validate($isNew) {
			$err = NULL;
			$this->name = trim($this->name);
			$this->email = trim($this->email);
			$this->login = trim($this->login);

			if ( !$this->name ) {
				throw new EntityValidationException(
					"name",
					"Имя клиента не указано.");
			}

			// check if such login exists and it is not the same user:
			$u = self::fetchByLogin($this->login);
			if ( $u && $u->id != $this->id ) {
				throw new EntityValidationException(
					"loginExists",
					"Клиент с логином ".p($this->login)." уже зарегистрирован (id: ".$u->id.", ".$this->id.").");
			}

			parent::validate($isNew);
		}


	}
?>
