<?php
  /**
   * TEST 1 : run `phpinfo` to make sure PHP, PDO, MySQL, etc, are all in working order
   */
?>

<?php
  // echo phpinfo();
?>

<?php
  /**
   * TEST 2 : check PHP+PDO+MySQL = sample data output
   */
?>

<?php

  // config
  $host = 'mysql';
  $db   = 'mrct';
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
  $stmt = $pdo->query('SELECT * FROM users');

  // output
  while ( $row = $stmt->fetch() ) {
      echo "<b>" . $row['id'] . "</b>: " . $row['first_name'] . " " . $row['last_name'] . "\n";
  }

?>
