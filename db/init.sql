/* DRINKS */
CREATE TABLE drinks (
  id           INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
  drink_name   TEXT NOT NULL UNIQUE,
  drink_desc   TEXT NOT NULL,
  price        REAL NOT NULL,
  img_ext      TEXT
);

/* I have created all images displayed */
INSERT INTO drinks (id, drink_name, drink_desc, price, img_ext) VALUES (1, 'Honey Tea', 'Black tea sweetened with honey', 4.50, 'jpg');
INSERT INTO drinks (id, drink_name, drink_desc, price, img_ext) VALUES (2, 'Peach Oolong Tea', 'Oolong tea with a peach twist', 5.00, 'jpg');
INSERT INTO drinks (id, drink_name, drink_desc, price, img_ext) VALUES (3, 'Winter Melon Green Tea', 'Green tea with winter melon', 5.00, 'jpg');
INSERT INTO drinks (id, drink_name, drink_desc, price, img_ext) VALUES (4, 'Passion Fruit Green Tea', 'Green tea infused with passion fruit', 4.50, 'jpg');
INSERT INTO drinks (id, drink_name, drink_desc, price, img_ext) VALUES (5, 'Lemon Black Tea', 'Black tea with a hint of lemon', 4.00, 'jpg');
INSERT INTO drinks (id, drink_name, drink_desc, price, img_ext) VALUES (6, 'Winter Melon Milk Tea', 'Milk and tea with a little extra fruity sweetness', 4.55, 'jpg');
INSERT INTO drinks (id, drink_name, drink_desc, price, img_ext) VALUES (7, 'Lychee Green Tea', 'Green Tea with lychee fruit for some added fruitiness', 4.99, 'jpg');
INSERT INTO drinks (id, drink_name, drink_desc, price, img_ext) VALUES (8, 'Black Milk Tea', 'As simple as it gets: Black tea with milk', 4.00, 'jpg');


/* TAGS */
CREATE TABLE tags (
  id        INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
  tag       TEXT NOT NULL UNIQUE,
  img_ext   TEXT
);

/* I have created all images displayed */
INSERT INTO tags (id, tag, img_ext) VALUES
(1, 'Classic Teas', 'jpg'),
(2, 'Fruit Teas', 'jpg'),
(3, 'Milk Teas', 'jpg');

/* DRINK_TAGS */
CREATE TABLE drink_tags (
  id           INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
  drink_id     INTEGER NOT NULL,
  tag_id       INTEGER NOT NULL,

  FOREIGN KEY(drink_id) REFERENCES drinks(id),
  FOREIGN KEY(tag_id) REFERENCES tags(id)
);

INSERT INTO drink_tags (id, drink_id, tag_id) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 2),
(4, 4, 2),
(5, 5, 1),
(6, 5, 2),
(7, 6, 2),
(8, 6, 3),
(9, 7, 1),
(10, 7, 2),
(11, 8, 1),
(12, 8, 3);

/* USERS */
CREATE TABLE users (
  id           INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
  username     TEXT NOT NULL UNIQUE,
  password     TEXT NOT NULL
);

INSERT INTO users (id, username, password) VALUES (1, 'kyra', '$2y$10$QtCybkpkzh7x5VN11APHned4J8fu78.eFXlyAMmahuAaNcbwZ7FH.'); -- password: monkey

/* GROUPS */
CREATE TABLE groups (
	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
	name TEXT NOT NULL UNIQUE
);

INSERT INTO groups (id, name) VALUES (1, 'admin');

/* MEMBERSHIPS */
CREATE TABLE memberships (
	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
  group_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,

  FOREIGN KEY(group_id) REFERENCES groups(id),
  FOREIGN KEY(user_id) REFERENCES users(id)
);

INSERT INTO memberships (group_id, user_id) VALUES (1, 1); -- User 'kyra' is a member of the 'admin' group.

/* SESSIONS */

CREATE TABLE sessions (
	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
	user_id INTEGER NOT NULL,
	session TEXT NOT NULL UNIQUE,
  last_login   TEXT NOT NULL,

  FOREIGN KEY(user_id) REFERENCES users(id)
);
