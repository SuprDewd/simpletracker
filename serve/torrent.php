<?php

require_once '../site.php';
require_once '../bencoding.php';
$db->connect();
require_auth();

site_header();

$row = false;
if (array_key_exists('id', $_GET)) {
    $id = $_GET['id'];
    $res = $db->query_params('SELECT torrent_id, anonymous, name, description, data, submitted, info_hash, total_size, username FROM torrents JOIN users on users.user_id = torrents.user_id WHERE torrent_id = :torrent_id', array('torrent_id' => $id)) or die('db error');
    $row = $res->fetch();
}

printf('<section class="info">');

if ($row) {

    $res = $db->query_params('SELECT count(nullif(completed,false)) AS complete, count(nullif(completed,true)) AS incomplete FROM peers WHERE torrent_id = :torrent_id', array('torrent_id' => $row['torrent_id'])) or die('db error');
    $comp_res = $res->fetch() or die('db error');

    if (array_key_exists('success', $_GET)) {
        printf('Upload successful, please download the torrent again and start seeding');
        printf('<br/>');
    }

    printf('<h1><a href="download.php?id=%s">%s</a></h1>', $row['torrent_id'], html_escape($row['name']));

    printf('<div class="table"><table id="torrents" style="width:100%%;margin:0 0 20px 0">');

    printf('<tr><th>Submitted</th><td><tt>%s</tt></td></tr>', html_escape($db->get_datetime($row['submitted'])->format('Y-m-d H:i:s')));

    if (!$row['anonymous']) {
        printf('<tr><th>By</th><td><tt>%s</tt></td></tr>', html_escape($row['username']));
    }

    printf('<tr><th>Size</th><td><tt>%s</tt></td></tr>', format_size($row['total_size']));
    printf('<tr><th>Info hash</th><td><tt>%s</tt></td></tr>', $row['info_hash']);
    printf('<tr><th>Seeders</th><td><tt>%d</tt></td></tr>', $comp_res['complete']);
    printf('<tr><th>Leechers</th><td><tt>%d</tt></td></tr>', $comp_res['incomplete']);

    printf('</table></div>');

    printf('<h1>Description</h1>');
    printf('<pre>%s</pre>', html_escape($row['description']));

    printf('<h1>Files</h1>');

    $data = $db->decode_data($row['data']);
    $arr = bdecode($data);

    if ($arr !== false && array_key_exists('info', $arr)) {
        if (array_key_exists('files', $arr['info'])) {
            foreach ($arr['info']['files'] as $file) {
                if (array_key_exists('path', $file)) {
                    printf("<tt>%s</tt>", html_escape(implode('/', $file['path'])));
                    printf("<br/>");
                }
            }
        } else if (array_key_exists('name', $arr['info'])) {
            printf("<tt>%s</tt>", html_escape($arr['info']['name']));
            printf("<br/>");
        }
    }

} else {
    printf('<div class="bad notification">No such torrent</div>');
}

printf('</section>');

site_footer();

