<?php

require_once '../site.php';
require_once '../bencoding.php';
db_connect();

db_query_params('DELETE FROM peers WHERE last_announce + interval \'1 hour\' < CURRENT_TIMESTAMP');

header('Content-Type: text/plain');

function fail($reason) {
    die(bencode(array('failure reason' => $reason)));
}

$keys = array(
    array('username', true),
    array('passkey', true),
    array('info_hash', true),
    array('peer_id', true),
    array('port', true),
    array('no_peer_id', false),
    array('ip', false),
    array('numwant', false),
    array('event', false),
    array('left', false),
);

$data = array();
foreach ($keys as $key) {
    if (array_key_exists($key[0], $_GET)) {
        $data[$key[0]] = $_GET[$key[0]];
    } else if ($key[1]) {
        fail(sprintf('Missing key: %s', $key[0]));
    }
}

$data['info_hash'] = bin2hex($data['info_hash']);
$data['peer_id'] = bin2hex($data['peer_id']);

$res = db_query_params('SELECT user_id FROM users WHERE username = $1 AND passkey = $2', array($data['username'], $data['passkey'])) or fail('db error');
$user_row = pg_fetch_assoc($res) or fail('access denied');

if (!is_numeric($data['port'])) fail('invalid port');
$data['port'] = intval($data['port']);
if ($data['port'] < 1 || $data['port'] >= 65536) fail('invalid port');

if (array_key_exists('left', $data)) {
    if (!is_numeric($data['left'])) fail('invalid left');
    $data['left'] = intval($data['left']);
    if ($data['left'] < 0) fail('invalid left');
}

if (!array_key_exists('ip', $data)) {
    $data['ip'] = $_SERVER['REMOTE_ADDR'];
}

if (array_key_exists('numwant', $data)) {
    if (!is_numeric($data['numwant'])) fail('invalid numwant');
    $data['numwant'] = intval($data['numwant']);
    if ($data['numwant'] < 0) $data['numwant'] = 1000;
    if ($data['numwant'] > 1000) $data['numwant'] = 1000;
} else {
    $data['numwant'] = 30;
}

$res = db_query_params('SELECT torrent_id FROM torrents WHERE info_hash = $1', array($data['info_hash'])) or fail('db error');
$torrent_row = pg_fetch_assoc($res) or fail('no such torrent');

$res = db_query_params('SELECT peer_id FROM peers WHERE user_id = $1 AND torrent_id = $2 AND chosen_peer_id = $3', array($user_row['user_id'], $torrent_row['torrent_id'], $data['peer_id'])) or fail('db error');

if (!($peer_row = pg_fetch_assoc($res))) {
    $peer_row = db_query_params('INSERT INTO peers (user_id, torrent_id, chosen_peer_id, ip, port) VALUES ($1,$2,$3,$4,$5) RETURNING peer_id', array($user_row['user_id'], $torrent_row['torrent_id'], $data['peer_id'], $data['ip'], $data['port'])) or fail('db error');
    $peer_row = pg_fetch_assoc($peer_row);
} else {
    db_query_params('UPDATE peers SET ip = $1, port = $2, last_announce = CURRENT_TIMESTAMP WHERE peer_id = $3', array($data['ip'], $data['port'], $peer_row['peer_id'])) or fail('db error');
}

if (array_key_exists('left', $data)) {
    db_query_params('UPDATE peers SET completed = $1 WHERE peer_id = $2', array($data['left'] === 0 ? 't' : 'f', $peer_row['peer_id']));
}

if (array_key_exists('event', $data) && $data['event'] === 'stopped') {
    db_query_params('DELETE FROM peers WHERE peer_id = $1', array($peer_row['peer_id']));
}

$res = db_query_params('SELECT count(nullif(completed,false)) AS complete, count(nullif(completed,true)) AS incomplete FROM peers WHERE torrent_id = $1', array($torrent_row['torrent_id'])) or fail('db error');
$comp_res = pg_fetch_assoc($res) or fail('db error');


$output = array();
$output['interval'] = 30 * 60;
$output['complete'] = intval($comp_res['complete']);
$output['incomplete'] = intval($comp_res['incomplete']);
$output['peers'] = array();

$res = db_query_params('SELECT chosen_peer_id, ip, port FROM peers WHERE torrent_id = $1 AND peer_id != $2 ORDER BY random() LIMIT $3', array($torrent_row['torrent_id'], $peer_row['peer_id'], $data['numwant'])) or fail('db error');
while ($row = pg_fetch_assoc($res)) {
    $peer = array();
    $peer['ip'] = $row['ip'];
    $peer['port'] = intval($row['port']);
    if (!array_key_exists('no_peer_id', $data)) {
        $peer['peer_id'] = hex2bin($row['chosen_peer_id']);
    }
    $output['peers'] []= $peer;
}

echo bencode($output);

