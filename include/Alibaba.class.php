<?php
// Alibaba
// PHP authentication library
//
// by Ben Crowder

include_once("alibaba_config.php");

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

	public static function AlibabaInit($APP_NAME, $DATABASE_NAME, $DATABASE_HOST, $DATABASE_USERNAME, $DATABASE_PASSWORD, $USER_TABLE_NAME, $USERNAME_FIELD, $PASSWORD_FIELD, $COOKIE_EXPIRATION, $HASH_FUNCTION, $LOGIN_PAGE_URL) {
		self::$app_name = $APP_NAME;
		self::$database_name = $DATABASE_NAME;
		self::$database_host = $DATABASE_HOST;
		self::$database_username = $DATABASE_USERNAME;
		self::$database_password = $DATABASE_PASSWORD;
		self::$user_table_name = $USER_TABLE_NAME;
		self::$username_field = $USERNAME_FIELD;
		self::$password_field = $PASSWORD_FIELD;
		self::$cookie_expiration = $COOKIE_EXPIRATION;
		self::$hash_function = $HASH_FUNCTION;
		self::$login_page_url = $LOGIN_PAGE_URL;
	}

	// The main methods to use

	public static function forceAuthentication() {
		if (!self::authenticated()) {
			self::redirectToLogin();
		}
	}

	public static function authenticated() {
		if (isset($_COOKIE["alibaba_" . self::$app_name . "_username"])) {
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
			// We're logged in, set the cookie
			$logged_in = true;
			setcookie("alibaba_" . self::$app_name . "_username", $username, time() + 60 * 60 * 24 * self::$cookie_expiration, "/");
		} else {
			// Login failed
			$logged_in = false;
			setcookie("alibaba_" . self::$app_name . "_username", "", time() - 3600, "/");
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
		return $_COOKIE["alibaba_" . self::$app_name . "_username"];
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
} Alibaba::AlibabaInit($APP_NAME, $DATABASE_NAME, $DATABASE_HOST, $DATABASE_USERNAME, $DATABASE_PASSWORD, $USER_TABLE_NAME, $USERNAME_FIELD, $PASSWORD_FIELD, $COOKIE_EXPIRATION, $HASH_FUNCTION, $LOGIN_PAGE_URL);

?>
