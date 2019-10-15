
CREATE USER simpletracker WITH PASSWORD 'simpletracker';
CREATE DATABASE simpletracker;

\c simpletracker

DROP TABLE IF EXISTS invitations;
DROP TABLE IF EXISTS peers;
DROP TABLE IF EXISTS torrents;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id serial PRIMARY KEY,
    username varchar(255) NOT NULL,
    password varchar(255) NOT NULL,
    passkey varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    invited_by serial REFERENCES users (user_id)
);

CREATE TABLE torrents (
    torrent_id serial PRIMARY KEY,
    user_id serial REFERENCES users (user_id) NOT NULL,
    anonymous boolean NOT NULL DEFAULT false,
    name varchar(1023) NOT NULL,
    description text NOT NULL,
    data bytea NOT NULL,
    submitted timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    info_hash char(40) NOT NULL,
    total_size bigint NOT NULL
);

CREATE TABLE peers (
    peer_id serial PRIMARY KEY,
    user_id serial REFERENCES users (user_id) NOT NULL,
    torrent_id serial REFERENCES torrents (torrent_id) NOT NULL,
    chosen_peer_id char(40) NOT NULL,
    ip varchar(255) NOT NULL,
    port integer NOT NULL,
    completed boolean NOT NULL DEFAULT false,
    last_announce timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX ON peers (user_id, torrent_id, chosen_peer_id);

CREATE TABLE invitations (
    invitation_id serial PRIMARY KEY,
    user_id serial REFERENCES users (user_id),
    email varchar(255) NOT NULL,
    invitation_key varchar(255) NOT NULL
);

CREATE UNIQUE INDEX ON invitations (email, invitation_key);


GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE users TO simpletracker;
GRANT USAGE, SELECT ON SEQUENCE users_user_id_seq TO simpletracker;
GRANT USAGE, SELECT ON SEQUENCE users_invited_by_seq TO simpletracker;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE torrents TO simpletracker;
GRANT USAGE, SELECT ON SEQUENCE torrents_torrent_id_seq TO simpletracker;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE peers TO simpletracker;
GRANT USAGE, SELECT ON SEQUENCE peers_peer_id_seq TO simpletracker;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE invitations TO simpletracker;
GRANT USAGE, SELECT ON SEQUENCE invitations_invitation_id_seq TO simpletracker;


INSERT INTO users (username, password, passkey, email) VALUES ('simpletracker', '$2y$10$6xA.5jOqve6N3OTQ6v1pEe1mUOvP30DtNuk/TAgjhM87YXCuseOOm', 'a087597edaae687d7f3d71da5431fce2', '@');
-- password is simpletracker

