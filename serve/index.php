<?php

require_once '../site.php';
$db->connect();
require_auth();

site_header();
printf('<div class="table"><table id="torrents"><tr><th>Torrent Name</th><th class="center">Download</th><th class="center">Date</th><th class="center">Uploader</th></tr>');
$res = $db->query_params("SELECT torrent_id, anonymous, name, username, submitted FROM torrents JOIN users ON users.user_id = torrents.user_id ORDER BY submitted DESC;");
if ($res) {
    while ($row = $res->fetch()) {

        $submitted = $db->get_datetime($row['submitted']);
        printf('<tr><td><a href="torrent.php?id=%s">%s</a></td><td class="center"><a href="download.php?id=%s">DL</a></td><td class="center">%s</td>', $row['torrent_id'], html_escape($row['name']), $row['torrent_id'], html_escape($submitted->format('Y-m-d H:i:s')));

        if ($row['anonymous']) {
            printf('<td class="center"><i>anonymous</i></td>');
        } else {
            printf('<td class="center">%s</td>', html_escape($row['username']));
        }

        printf('</tr>');
    }
}
print('</tbody></table></div>');
site_footer();

