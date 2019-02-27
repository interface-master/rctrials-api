<?php
	/**
	 * TEST 1 : run `phpinfo` to make sure PHP, PDO, MySQL, etc, are all in working order
	 */

	echo phpinfo();
?>

<?php
	/**
	 * TEST 2 : check PHP+PDO+MySQL = sample data output
	 */

	// config
	$host = 'mysql';
	$db   = 'rctrials';
	$user = 'root';
	$pass = 'rooot';
	$charset = 'utf8mb4';

	// connection
	$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
	$opt = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
	];
	$pdo = new PDO($dsn, $user, $pass, $opt);

	// query
  echo "executing SQL: `DESCRIBE users`;<br/>\n";
	$stmt = $pdo->query('DESCRIBE users');

	// output
	while ( $row = $stmt->fetch() ) {
			var_dump( $row );
			echo "&nbsp;<br/>\n";
	}

?>
