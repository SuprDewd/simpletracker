SET FOREIGN_KEY_CHECKS=0;

GRANT USAGE ON *.* TO 'simpletracker'@'localhost';
DROP USER 'simpletracker'@'localhost';
CREATE USER 'simpletracker'@'localhost' IDENTIFIED BY 'simpletracker';
CREATE DATABASE simpletracker;
USE simpletracker;

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
    user_id int(10) unsigned NOT NULL AUTO_INCREMENT,
    invited_by int(10) unsigned DEFAULT NULL,
    username varchar(255) NOT NULL,
    password varchar(255) NOT NULL,
    passkey varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `torrents`;
CREATE TABLE IF NOT EXISTS `torrents` (
    torrent_id int(10) unsigned NOT NULL AUTO_INCREMENT,
    user_id int(10) unsigned NOT NULL,
    anonymous tinyint(1) DEFAULT 0,
    name varchar(1023) NOT NULL,
    description text NOT NULL,
    data LONGBLOB NOT NULL,
    submitted timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    info_hash char(40) NOT NULL,
    total_size int(20) unsigned NOT NULL,
    PRIMARY KEY (`torrent_id`),
    CONSTRAINT torrent_user_fk FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `peers`;
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE IF NOT EXISTS `peers` (
    peer_id int(10) unsigned NOT NULL AUTO_INCREMENT,
    user_id int(10) unsigned NOT NULL,
    torrent_id int(10) unsigned NOT NULL,
    chosen_peer_id char(40) NOT NULL,
    ip varchar(255) NOT NULL,
    port integer NOT NULL,
    completed tinyint(1) NOT NULL DEFAULT 0,
    last_announce timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`peer_id`),
    CONSTRAINT `peer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `peer_torrent` FOREIGN KEY (`torrent_id`) REFERENCES `torrents` (`torrent_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `peers` ADD UNIQUE `unique_index`(`user_id`, `torrent_id`, `chosen_peer_id`);

DROP TABLE IF EXISTS `invitations`;
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE IF NOT EXISTS `invitations` (
    invitation_id int(10) unsigned NOT NULL AUTO_INCREMENT,
    user_id int(10) unsigned NOT NULL,
    email varchar(255) NOT NULL,
    invitation_key varchar(255) NOT NULL,
    PRIMARY KEY (`invitation_id`),
    CONSTRAINT `invited` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `invitations` ADD UNIQUE `unique_index`(`email`, `invitation_key`);

GRANT SELECT, INSERT, UPDATE, DELETE ON *.* TO 'simpletracker'@'localhost';

INSERT INTO users (username, password, passkey, email) VALUES ('simpletracker', '$2y$10$6xA.5jOqve6N3OTQ6v1pEe1mUOvP30DtNuk/TAgjhM87YXCuseOOm', 'a087597edaae687d7f3d71da5431fce2', '@');
-- password is simpletracker

SET FOREIGN_KEY_CHECKS=1;
