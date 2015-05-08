<?php

require_once '../site.php';
require_once '../bencoding.php';
db_connect();
require_auth();

$errors = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    if (!array_key_exists('description', $_POST)) {
        die;
    }

    $description = $_POST['description'];
    if (strlen($description) > 10000) {
        die('description too long');
    }

    if (!array_key_exists('torrent', $_FILES)) {
        die;
    }

    if ($_FILES['torrent']['size'] > $CONFIG['max_torrent_size']) {
        die('torrent too big');
    }

    $name = $_FILES['torrent']['name'];
    if (strlen($name) > 512) {
        die('torrent name too long');
    }

    $ext = '.torrent';
    if (strlen($name) <= strlen($ext) || strpos($name, $ext, strlen($name) - strlen($ext)) === false) {
        die('not a .torrent file');
    }

    $fp = fopen($_FILES['torrent']['tmp_name'], 'rb');
    $data = '';
    while (!feof($fp)) {
        $data .= fread($fp, 8192);
    }
    fclose($fp);

    $arr = bdecode($data);
    if ($arr === false) {
        die('invalid torrent');
    }

    // TODO: more validity checks for torrent?

    if (!array_key_exists('info', $arr)) {
        die('invalid torrent');
    }

    $infobc = bencode($arr['info']);
    if ($infobc === false) {
        die('bencoding error');
    }

    $info_hash = sha1($infobc);
    $total_size = 0;

    if (array_key_exists('files', $arr['info'])) {
        foreach ($arr['info']['files'] as $file) {
            if (array_key_exists('length', $file)) {
                $total_size += $file['length'];
            }
        }
    } else if (array_key_exists('length', $arr['info'])) {
        $total_size += $arr['info']['length'];
    }

    $res = db_query_params('INSERT INTO torrents (user_id, name, description, anonymous, data, info_hash, total_size) VALUES ($1,$2,$3,$4,\'' . pg_escape_bytea($data) . '\',$5,$6) RETURNING torrent_id', array($_SESSION['user']['user_id'], $name, $description, array_key_exists('anonymous', $_POST) ? 't' : 'f', $info_hash, $total_size)) or die('db error');
    $row = pg_fetch_assoc($res);

    header(sprintf('Location: %s/torrent.php?id=%s&success', $CONFIG['base_url'], $row['torrent_id']));
    die;
}


site_header();

printf('<form method="POST" action="upload.php" enctype="multipart/form-data">');
csrf_html();

if (!empty($errors)) {
    printf('Errors:');
    printf('<br/>');
    printf('<ul>');
    foreach ($errors as $error) {
        printf('<li>%s</li>', html_escape($error));
    }
    printf('</ul>');
}

printf('Torrent: ');
printf('<input type="hidden" name="MAX_FILE_SIZE" value="%d" />', $CONFIG['max_torrent_size']);
printf('<input type="file" name="torrent" />');
printf('<br/>');

printf('Description:');
printf('<br/>');
printf('<textarea name="description" rows="15" cols="70"></textarea>');
printf('<br/>');

printf('<input type="checkbox" name="anonymous" />');
printf('anonymous');
printf('<br/>');

printf('<input type="submit" value="Upload" />');

printf('</form>');

site_footer();

