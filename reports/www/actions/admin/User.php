<?
	namespace actions\admin;

	class User {
		public function hello() {
			$u = new \entities\User();
			print "Hello, world! This is class ".get_class($this).".".LF;
			print "And I can user class ".get_class($u).".".LF;
		}
	}
?>
