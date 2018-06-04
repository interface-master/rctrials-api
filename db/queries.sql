SELECT
  t.uid,
  u.email,
  CONCAT(CONCAT(LEFT(t.token,10),".."),RIGHT(t.token,10)) AS `token`,
  t.expires
FROM
  tokens AS t
INNER JOIN
  users AS u
  ON (u.id = t.uid);
