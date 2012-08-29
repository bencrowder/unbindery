<?php
// Alibaba
// PHP authentication library
//
// by Ben Crowder

class Alibaba {
	private static $app_name = '';
	private static $database_name = '';
	private static $database_host = '';
	private static $database_username = '';
	private static $database_password = '';
	private static $user_table_name = '';
	private static $username_field = '';
	private static $password_field = '';
	private static $cookie_expiration = '';
	private static $hash_function = '';
	private static $login_page_url = '';

	public static function AlibabaInit($params) {
		self::$app_name = $params['app_name'];
		self::$database_name = $params['database_name'];
		self::$database_host = $params['database_host'];
		self::$database_username = $params['database_username'];
		self::$database_password = $params['database_password'];
		self::$user_table_name = $params['user_table_name'];
		self::$username_field = $params['username_field'];
		self::$password_field = $params['password_field'];
		self::$cookie_expiration = $params['cookie_expiration'];
		self::$hash_function = $params['hash_function'];
		self::$login_page_url = $params['login_page_url'];
	}

	// The main methods to use

	public static function forceAuthentication() {
		if (!self::authenticated()) {
			self::redirectToLogin();
		}
	}

	public static function authenticated() {
		if (isset($_SESSION["alibaba_" . self::$app_name . "_username"])) {
			return true;
		} else {
			return false;
		}
	}

	public static function login($username, $password) {
		// Connect to the database
		$db = self::db_connect();

		// Hash the password with the correct function
		$password = self::hashpass($password);

		// Check the database
		$query = "SELECT * FROM " . mysql_real_escape_string(self::$user_table_name) . " WHERE " . mysql_real_escape_string(self::$username_field) . "='" . mysql_real_escape_string($username) . "' AND " . mysql_real_escape_string(self::$password_field) . "='" . mysql_real_escape_string($password) . "'";

		$result = mysql_query($query) or die("Couldn't run: $query");

		if (mysql_numrows($result)) { 
			// We're logged in, set the session variable
			$logged_in = true;
			$_SESSION['alibaba_' . self::$app_name . '_username'] = $username;
		} else {
			// Login failed, wipe the session variable
			$logged_in = false;
			$_SESSION['alibaba_' . self::$app_name . '_username'] = '';
		}

		self::db_close($db);

		return $logged_in;
	}

	public static function redirectToLogin($message = '', $login = '') {
		if ($login == '') { $login = self::$login_page_url; }

		$locstr = "Location: $login";
		if ($message) { $locstr .= "?message=$message"; }

		header($locstr);
	}

	public static function getUsername() {
		return $_SESSION["alibaba_" . self::$app_name . "_username"];
	}

	public static function logout($url = '') {
		setcookie("alibaba_" . self::$app_name . "_username", "", time() - 3600, "/");

		if ($url == '') { $url = self::$login_page_url; }

		header("Location: $url");
	}

	// Innards

	private static function hashpass($password) {
		switch(self::$hash_function) {
			case "md5": $password = md5($password); break;
			case "sha1": $password = sha1($password); break;
			case "md5sha1" : $password = md5(sha1($password)); break;
			case "sha1md5" : $password = sha1(md5($password)); break;
		}

		return $password;
	}

	private static function db_connect() {
		$conn = mysql_connect(self::$database_host, self::$database_username, self::$database_password);
		if (!$conn) { echo "Error connecting to database.\n"; }

		@mysql_select_db(self::$database_name, $conn) or die("Unable to select database.");
		
		return $conn;
	}

	private static function db_close($conn) {
		mysql_close($conn);
	}
}

?>
