<?php

require_once '../site.php';
$db->connect();
require_auth();

site_header();

$res = $db->query_params("SELECT torrent_id, anonymous, name, username, submitted FROM torrents JOIN users ON users.user_id = torrents.user_id ORDER BY submitted DESC;");
if ($res) {
    while ($row = $res->fetch()) {

        $submitted = $db->get_datetime($row['submitted']);
        printf('%s - <a href="download.php?id=%s">%s</a> - <a href="torrent.php?id=%s">info</a>', $submitted->format('Y-m-d H:i:s'), $row['torrent_id'], html_escape($row['name']), $row['torrent_id']);

        if (!$row['anonymous']) {
            printf(' - by %s', html_escape($row['username']));
        }

        printf('<br/>');
    }
}

site_footer();

