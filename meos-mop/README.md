# meos-mop

## Modifications needed to database

### Create the `meos-defaults` database:

Create a database called `meos-defaults`.

Run the following commands to setup the database:

```
CREATE TABLE `defaultEvents` (
  `property` varchar(255) NOT NULL,
  `value` int(11) NOT NULL,
  `data` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `defaultEvents` (`property`, `value`, `data`) VALUES
('marqueeShow', 0, ''),
('marqueeText', 60, 'Marquee text goes here'),
('meosEventId', -1, 'meos_1234_45678'),
('meosMopId', -1, '');

ALTER TABLE `defaultEvents`
  ADD PRIMARY KEY (`property`);
COMMIT;
```

### Modify the `mopRadio` table in the MOP database

We need to modify the `mopRadio` table to add a timestamp field which is set whenever a row is inserted - we use this for latest punches.

```
ALTER TABLE `mopRadio` ADD `rt_timestamp` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `rt`;
```

### Modify the `mopCompetitor` table in the MOP database

We need to modify the `mopCompetitor` table to add a timestamp field which is updated whenever the finish time is changed - we use this for latest punches at the finish.

This is a bit tricker, as we only want to update the timestamp when the "rt" field is updated, not when anything in a row is updated (otherwise it will trigger when their name, SI card, etc. are changed). So, to do this we need to use some database triggers.

First, add a new column to the `mopCompetitor` table.

```
ALTER TABLE `mopCompetitor` ADD `rt_timestamp` TIMESTAMP NULL AFTER `it`;
```

Second, add a database trigger:

```
drop trigger if exists rt_timestamp_upd;
delimiter //
create trigger rt_timestamp_upd BEFORE UPDATE ON mopCompetitor
FOR EACH ROW
BEGIN
  IF ( (new.rt != 0) AND ((old.rt is not null and new.rt is not null and old.rt <> new.rt)
      OR (old.rt is null and new.rt is not null)) ) THEN
    SET NEW.rt_timestamp = CURRENT_TIMESTAMP;
  END IF;
END;//
delimiter ;
```

Note, phpMyAdmin might complain about syntax of the above SQL. Just ignore it and run it.