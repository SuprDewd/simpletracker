<?php

require_once '../site.php';
require_once '../bencoding.php';
db_connect();
require_auth();

site_header();

$row = false;
if (array_key_exists('id', $_GET)) {
    $id = $_GET['id'];
    $res = db_query_params('SELECT torrent_id, anonymous, name, description, data, submitted, info_hash, total_size, username FROM torrents JOIN users on users.user_id = torrents.user_id WHERE torrent_id = $1', array($id)) or die('db error');
    $row = pg_fetch_assoc($res);
}

if ($row) {

    $res = db_query_params('SELECT count(nullif(completed,false)) AS complete, count(nullif(completed,true)) AS incomplete FROM peers WHERE torrent_id = $1', array($row['torrent_id'])) or die('db error');
    $comp_res = pg_fetch_assoc($res) or die('db error');

    if (array_key_exists('success', $_GET)) {
        printf('Upload successful, please download the torrent again and start seeding');
        printf('<br/>');
        printf('<br/>');
    }

    printf('Name: <tt><a href="/download.php?id=%s">%s</a></tt>', $row['torrent_id'], html_escape($row['name']));
    printf('<br/>');

    printf('Submitted: <tt>%s</tt>', html_escape(DateTime::createFromFormat('Y-m-d H:i:s.u', $row['submitted'])->format('Y-m-d H:i:s')));
    printf('<br/>');

    if ($row['anonymous'] !== 't') {
        printf('By: <tt>%s</tt>', html_escape($row['username']));
        printf('<br/>');
    }

    printf('Size: <tt>%s</tt>', format_size($row['total_size']));
    printf('<br/>');

    printf('Info hash: <tt>%s</tt>', $row['info_hash']);
    printf('<br/>');

    printf('Seeders: <tt>%d</tt>', $comp_res['complete']);
    printf('<br/>');
    printf('Leechers: <tt>%d</tt>', $comp_res['incomplete']);
    printf('<br/>');

    printf('Description:');
    printf('<br/>');
    printf('<tt>');
    printf('<pre>');
    printf('%s', html_escape($row['description']));
    printf('</pre>');
    printf('</tt>');
    printf('<br/>');

    printf('Files:');
    printf('<br/>');

    $data = pg_unescape_bytea($row['data']);
    $arr = bdecode($data);

    if ($arr !== false && array_key_exists('info', $arr)) {
        if (array_key_exists('files', $arr['info'])) {
            foreach ($arr['info']['files'] as $file) {
                if (array_key_exists('path', $file)) {
                    printf("<tt>");
                    printf("%s", html_escape(implode('/', $file['path'])));
                    printf("</tt>");
                    printf("<br/>");
                }
            }
        } else if (array_key_exists('name', $arr['info'])) {
            printf("<tt>");
            printf("%s", html_escape($arr['info']['name']));
            printf("</tt>");
            printf("<br/>");
        }
    }

} else {
    printf('No such torrent');
}

site_footer();

