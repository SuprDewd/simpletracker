<?php

require_once '../site.php';
require_once '../bencoding.php';
$db->connect();
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

    $arr['info']['private'] = 1;

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

    $data = bencode($arr);

    $an = array_key_exists('anonymous', $_POST);
    $torrent_id = $db->query_params("INSERT INTO torrents (user_id, name, description, anonymous, data, info_hash, total_size) VALUES (:user_id, :name, :description, :anonymous, :data, :info_hash, :total_size)", array('user_id' => $_SESSION['user']['user_id'], 'name' => $name, 'description' => $description, 'anonymous' => $db->encode_bool($an), 'data' => $data, 'info_hash' => $info_hash, 'total_size' => $total_size), 'torrent_id') or die('db error');

    header(sprintf('Location: %s/torrent.php?id=%s&success', $CONFIG['base_url'], $torrent_id));
    die;
}


site_header();
printf('<section class="info">');
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

printf('<h1>Torrent</h1>');
printf('<input type="hidden" name="MAX_FILE_SIZE" value="%d" />', $CONFIG['max_torrent_size']);
printf('<input type="file" name="torrent" />');
printf('<br/>');

printf('<h1>Description</h1>');
printf('<textarea name="description" rows="15" cols="70"></textarea>');
printf('<br/>');

printf('<label class="container">Anonymous');
printf('<input type="checkbox" name="anonymous" />');
printf('<span class="checkmark"></span>');
printf('</label>');
printf('<br/>');

printf('<input class="submit" type="submit" value="Upload" />');

printf('</form>');
printf('</section>');
site_footer();

