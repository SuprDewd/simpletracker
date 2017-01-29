<?php

require_once '../site.php';
db_connect();
require_auth();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!array_key_exists('email', $_POST)) {
        die;
    }

    $email = $_POST['email'];
    if (strpos($email, '@') === false) {
        die('invalid email');
    }

    $res = db_query_params('SELECT 1 FROM users WHERE email = $1', array($email)) or die('db error');
    if (pg_num_rows($res) > 0) {
        printf('email already registered');
        die;
    }

    db_query_params('INSERT INTO invitations (user_id, email, invitation_key) VALUES ($1,$2,$3)', array($_SESSION['user']['user_id'], $email, random_hash())) or die('db error');
    header(sprintf('Location: %s/invitations.php?success', $CONFIG['base_url']));
    die;
}


site_header();

$res = db_query_params('SELECT username, email FROM users WHERE invited_by = $1 ORDER BY username', array($_SESSION['user']['user_id']));
if ($res && pg_num_rows($res) > 0) {
    printf('Invited');
    printf('<br/>');
    while ($row = pg_fetch_assoc($res)) {
        printf('%s', html_escape($row['username']));
        printf(' - ');
        printf('%s', html_escape($row['email']));
        printf('<br/>');
    }
    printf('<br/>');
}

$res = db_query_params('SELECT email, invitation_key FROM invitations WHERE user_id = $1 ORDER BY email', array($_SESSION['user']['user_id']));
if ($res && pg_num_rows($res) > 0) {
    printf('Invitations');
    printf('<br/>');

    if (array_key_exists('success', $_GET)) {
        printf('Invitation successfully created, go ahead and send the corresponding link to your invitee');
        printf('<br/>');
    }

    while ($row = pg_fetch_assoc($res)) {
        printf('%s', html_escape($row['email']));
        printf(' - ');
        printf('%s', $row['invitation_key']);
        printf(' - ');
        printf('<a href="%s/register.php?invite=%s">%s/register.php?invite=%s</a>', $CONFIG['base_url'], $row['invitation_key'], $CONFIG['base_url'], $row['invitation_key']);
        printf('<br/>');
    }
    printf('<br/>');
}

printf('New invitation');
printf('<form method="POST" action="invitations.php">');
csrf_html();

printf('Email: ');
printf('<input type="text" name="email" />');
printf('<br/>');

printf('<input type="submit" value="Create" />');

printf('</form>');

site_footer();

