<?php

require_once '../site.php';
$db->connect();
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

    $res = $db->query_params("SELECT email FROM users WHERE email = :email", array('email' => $email)) or die('db error');
    if ($res->fetch()) {
        printf('email already registered');
        die;
    }

    $db->query_params('INSERT INTO invitations (user_id, email, invitation_key) VALUES (:user_id, :email, :invitation_key)', array('user_id' => $_SESSION['user']['user_id'], 'email' => $email, 'invitation_key' => random_hash())) or die('db error');
    header(sprintf('Location: %s/invitations.php?success', $CONFIG['base_url']));
    die;
}


site_header();

printf('<section class="info">');
$res = $db->query_params('SELECT username, email FROM users WHERE invited_by = :invited_by ORDER BY username', array('invited_by' => $_SESSION['user']['user_id']));
if ($res) {
    $any = false;
    while ($row = $res->fetch()) {
        if (!$any) {
            $any = true;
            printf('<h1>Invited</h1>');
        }
        printf('<tt>%s</tt>', html_escape($row['username']));
        printf(' / ');
        printf('<tt>%s</tt>', html_escape($row['email']));
        printf('<br/>');
    }
    if ($any) {
        printf('<br/>');
    }
}

$res = $db->query_params('SELECT email, invitation_key FROM invitations WHERE user_id = :user_id ORDER BY email;', array('user_id' => $_SESSION['user']['user_id']));
if ($res) {
    $any = false;
    while ($row = $res->fetch()) {
        if (!$any) {
            $any = true;
            printf('Invitations');
            printf('<br/>');

            if (array_key_exists('success', $_GET)) {
                printf('Invitation successfully created, go ahead and send the corresponding link to your invitee');
                printf('<br/>');
            }
        }

        printf('%s', html_escape($row['email']));
        printf(' - ');
        printf('%s', $row['invitation_key']);
        printf(' - ');
        printf('<a href="%s/register.php?invite=%s">%s/register.php?invite=%s</a>', $CONFIG['base_url'], $row['invitation_key'], $CONFIG['base_url'], $row['invitation_key']);
        printf('<br/>');
    }
    if ($any) {
        printf('<br/>');
    }
}

printf('<h1>New invitation</h1>');
printf('<form method="POST" action="invitations.php">');
csrf_html();

printf('<input class="text" type="text" name="email" placeholder="Email">');

printf('<input class="submit" type="submit" value="Create">');

printf('</form>');
printf('</section>');

site_footer();

