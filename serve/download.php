<?php

require_once '../site.php';
require_once '../bencoding.php';
db_connect();
require_auth();

$res = db_query_params('SELECT username, passkey FROM users WHERE user_id = $1', array($_SESSION['user']['user_id'])) or die('db error');
$user_row = pg_fetch_assoc($res) or die('no such user');

$row = false;
if (array_key_exists('id', $_GET)) {
    $id = $_GET['id'];
    $res = db_query_params('SELECT torrent_id, name, data FROM torrents WHERE torrent_id = $1', array($id)) or die('db error');
    $row = pg_fetch_assoc($res);
}

if ($row === false) {
    http_response_code(404);
    site_header();
    printf('404: Not Found');
    site_footer();
} else {

    $modified = bdecode(pg_unescape_bytea($row['data'])) or die('bencoding error');
    // Note: $modified['info'] must not be changed here (it will change the info hash)

    if (array_key_exists('announce-list', $modified)) {
        unset($modified['announce-list']);
    }

    $modified['announce'] = $CONFIG['base_url'] . '/announce.php?username=' . urlencode($user_row['username']) . '&passkey=' . urlencode($user_row['passkey']);

    $output = bencode($modified) or die('bencoding error');

    header('Cache-control: private');
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . strlen($output));
    header('Content-Disposition: filename=' . $row['name']);
    echo $output;
}

