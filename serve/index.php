<?php

require_once '../site.php';
db_connect();
require_auth();

site_header();

$res = db_query_params("SELECT torrent_id, users.user_id, anonymous, name, username, length(data) AS bytes, submitted FROM torrents JOIN users ON users.user_id = torrents.user_id ORDER BY submitted DESC;");
if ($res) {
    while ($row = pg_fetch_assoc($res)) {

        $submitted = DateTime::createFromFormat('Y-m-d H:i:s.u', $row['submitted']);
        printf('%s - <a href="download.php?id=%s">%s</a> - <a href="torrent.php?id=%s">info</a>', html_escape($submitted->format('Y-m-d H:i:s')), $row['torrent_id'], html_escape($row['name']), $row['torrent_id']);

        if ($row['anonymous'] !== 't') {
            printf(' - by %s', html_escape($row['username']));
        }

        printf('<br/>');
    }
}

site_footer();

