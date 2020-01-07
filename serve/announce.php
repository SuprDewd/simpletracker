<?php

require_once '../site.php';
require_once '../bencoding.php';
$db->connect();

$db->query_params('DELETE FROM peers WHERE last_announce + ' . $db->interval('1 hour') . ' < CURRENT_TIMESTAMP');

header('Content-Type: text/plain');

function fail($reason) {
    die(bencode(array('failure reason' => $reason)));
}

function ip2bytes($ip) {
    $res = '';
    $parts = explode('.', $ip);
    if (count($parts) !== 4) return null;
    for ($i = 0; $i < 4; $i++) {
        $b = $parts[$i];
        if (!is_numeric($b)) return null;
        $b = intval($b);
        if ($b < 0 || $b >= 256) return null;
        $res .= chr($b);
    }
    return $res;
}

$keys = array(
    'username' => true,
    'passkey' => true,
    'info_hash' => true,
    'peer_id' => true,
    'port' => true,
    'no_peer_id' => false,
    'ip' => false,
    'numwant' => false,
    'event' => false,
    'left' => false,
    'compact' => false,
);

$data = array();
foreach ($keys as $key => $req) {
    if (array_key_exists($key, $_GET)) {
        $data[$key] = $_GET[$key];
    } else if ($req) {
        fail(sprintf('missing key: %s', $key));
    }
}

$decoded_token = openssl_decrypt(base64_decode(urldecode($data['token'])), "AES-128-ECB", 'CUSTOMKEY');

$pieces = explode("&", $decoded_token);
if(isset($pieces[0])){
    $username = $pieces[0];
}else{
    fail(sprintf('missing key: username'));
}
if(isset($pieces[1])){
    $passkey = $pieces[1];
}else{
    fail(sprintf('missing key: passkey'));
}


$data['info_hash'] = bin2hex($data['info_hash']);
$data['peer_id'] = bin2hex($data['peer_id']);

$res = $db->query_params('SELECT user_id FROM users WHERE username = :username AND passkey = :passkey', array('username' => $username, 'passkey' => $passkey)) or fail('db error');
$user_row = $res->fetch() or fail('access denied');

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

    $try_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP');
    foreach ($try_keys as $key) {
        if (array_key_exists($key, $_SERVER)) {
            $potential_ip = trim(explode(',', $_SERVER[$key])[0]);

            // Ignore private IP addresses
            if (strpos($potential_ip, '10.') === 0) continue;
            if (strpos($potential_ip, '192.168.') === 0) continue;
            // TODO: 172.16.0.0/12 is also private

            $data['ip'] = $potential_ip;
            break;
        }
    }
}

if (array_key_exists('numwant', $data)) {
    if (!is_numeric($data['numwant'])) fail('invalid numwant');
    $data['numwant'] = intval($data['numwant']);
    if ($data['numwant'] < 0) $data['numwant'] = 1000;
    if ($data['numwant'] > 1000) $data['numwant'] = 1000;
} else {
    $data['numwant'] = 30;
}

$res = $db->query_params('SELECT torrent_id FROM torrents WHERE info_hash = :info_hash', array('info_hash' => $data['info_hash'])) or fail('db error');
$torrent_row = $res->fetch() or fail('no such torrent');

$res = $db->query_params('SELECT peer_id FROM peers WHERE user_id = :user_id AND torrent_id = :torrent_id AND chosen_peer_id = :chosen_peer_id', array('user_id' => $user_row['user_id'], 'torrent_id' => $torrent_row['torrent_id'], 'chosen_peer_id' => $data['peer_id'])) or fail('db error');

if (!($peer_row = $res->fetch())) {
    $res = $db->query_params('INSERT INTO peers (user_id, torrent_id, chosen_peer_id, ip, port) VALUES (:user_id, :torrent_id, :chosen_peer_id, :ip, :port)', array('user_id' => $user_row['user_id'], 'torrent_id' => $torrent_row['torrent_id'], 'chosen_peer_id' => $data['peer_id'], 'ip' => $data['ip'], 'port' => $data['port']), 'peer_id');
    $peer_row = array('peer_id' => $res);
} else {
    $db->query_params('UPDATE peers SET ip = :ip, port = :port, last_announce = CURRENT_TIMESTAMP WHERE peer_id = :peer_id', array('ip' => $data['ip'], 'port' => $data['port'], 'peer_id' => $peer_row['peer_id'])) or fail('db error');
}

if (array_key_exists('left', $data)) {
    $db->query_params('UPDATE peers SET completed = :completed WHERE peer_id = :peer_id', array('completed' => $db->encode_bool($data['left'] === 0), 'peer_id' => $peer_row['peer_id']));
}

if (array_key_exists('event', $data) && $data['event'] === 'stopped') {
    $db->query_params('DELETE FROM peers WHERE peer_id = :peer_id', array('peer_id' => $peer_row['peer_id']));
}

$res = $db->query_params('SELECT count(nullif(completed,false)) AS complete, count(nullif(completed,true)) AS incomplete FROM peers WHERE torrent_id = :torrent_id', array('torrent_id' => $torrent_row['torrent_id'])) or fail('db error');
$comp_res = $res->fetch() or fail('db error');


$output = array(
    'interval' => 30 * 60,
    'complete' => intval($comp_res['complete']),
    'incomplete' => intval($comp_res['incomplete']),
    'peers' => array(),
);

$res = $db->query_params('SELECT chosen_peer_id, ip, port FROM peers WHERE torrent_id = :torrent_id AND peer_id != :peer_id ORDER BY ' . $db->random() . ' LIMIT :limit', array('torrent_id' => $torrent_row['torrent_id'], 'peer_id' => $peer_row['peer_id'], 'limit' => $data['numwant'])) or fail('db error');
while ($row = $res->fetch()) {
    $peer = array(
        'ip' => $row['ip'],
        'port' => intval($row['port']),
    );
    if (!array_key_exists('no_peer_id', $data)) {
        $peer['peer_id'] = hex2bin($row['chosen_peer_id']);
    }
    $output['peers'] []= $peer;
}

if (array_key_exists('compact', $data)) {
    $peer_data = '';
    foreach ($output['peers'] as $peer) {
        $bs = ip2bytes($peer['ip']);
        if (is_null($bs)) continue;
        $peer_data .= $bs;
        $peer_data .= chr($peer['port'] >> 8);
        $peer_data .= chr($peer['port'] & 0xff);
    }

    if (empty($output['peers']) || !empty($peer_data)) {
        $output['peers'] = $peer_data;
    }
}

echo bencode($output);

