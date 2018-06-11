-- TOKENS:
-- multline
SELECT
  t.uid,
  t.tid,
  u.email,
  CONCAT(CONCAT(LEFT(t.token,10),".."),RIGHT(t.token,10)) AS `token`,
  t.expires
FROM
  tokens AS t
INNER JOIN
  users AS u
  ON (u.id = t.uid);
-- singleline
SELECT t.uid, t.tid, u.email, CONCAT(CONCAT(LEFT(t.token,10),".."),RIGHT(t.token,10)) AS `token`, t.expires FROM tokens AS t INNER JOIN users AS u ON (u.id = t.uid);


-- USERS:
-- multiline
SELECT
  u.id AS uid,
  u.email,
  u.name,
  u.role
FROM
  users AS u
INNER JOIN
  tokens AS t
  ON (t.uid = u.id);
-- singleline
SELECT u.id AS uid, u.email, u.name, u.role FROM users AS u INNER JOIN tokens AS t ON (t.uid = u.id);
