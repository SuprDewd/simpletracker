<?php

require_once '../site.php';
require_once '../bencoding.php';
$db->connect();
require_auth();

$res = $db->query_params('SELECT username, passkey FROM users WHERE user_id = :user_id', array('user_id' => $_SESSION['user']['user_id'])) or die('db error');
$user_row = $res->fetch() or die('no such user');

$row = false;
if (array_key_exists('id', $_GET)) {
    $id = $_GET['id'];
    $res = $db->query_params('SELECT torrent_id, name, data FROM torrents WHERE torrent_id = :torrent_id', array('torrent_id' => $id)) or die('db error');
    $row = $res->fetch();
}

if ($row === false) {
    http_response_code(404);
    site_header();
    printf('404: Not Found');
    site_footer();
} else {

    $modified = bdecode($db->decode_data($row['data'])) or die('bencoding error');
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

