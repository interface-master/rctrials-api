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
SELECT u.id AS uid, u.email, u.name, u.role FROM users AS u INNER JOIN tokens AS t ON (t.uid = u.id) WHERE t.tid=...;


-- LOGIN:
-- multiline
SELECT
  u.id AS uid,
  u.email,
  u.name,
  u.role,
  CONCAT(CONCAT(LEFT(t.tid,10),"..."),RIGHT(t.tid,10)) AS `tid`,
  CONCAT(CONCAT(LEFT(t.token,10),".."),RIGHT(t.token,10)) AS `token`
FROM
  users AS u
INNER JOIN
  tokens AS t
  ON (t.uid = u.id);
-- singleline
SELECT u.id AS uid, u.email, u.name, u.role, CONCAT(CONCAT(LEFT(t.tid,10),"..."),RIGHT(t.tid,10)) AS `tid`, CONCAT(CONCAT(LEFT(t.token,10),".."),RIGHT(t.token,10)) AS `token` FROM users AS u INNER JOIN tokens AS t ON (t.uid = u.id);
