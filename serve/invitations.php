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
            echo '<h1>Invited</h1><figure class="highlight">';
            
        }
        printf('<tt>%s</tt>', html_escape($row['username']));
        printf(' | ');
        printf('<tt>%s</tt>', html_escape($row['email']));
        printf('<br/>');
    }
    echo '</figure>';
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
            printf('<h1>Pending Invitations</h1>');

            if (array_key_exists('success', $_GET)) {
                printf('<div class="good notification">Invitation successfully created, go ahead and send the corresponding link to your invitee</div>');
            }
            echo '<div class="row">';
        }
        
        ?>
        <div class="col-md-3">
            <div class="card">
                <h5 class="card-header">Invitation</h5>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $row['email'];?></h5>
                    <p class="card-text">Invite Key: <?php echo $row['invitation_key'];?></p>
                    <a href="<?php echo $CONFIG['base_url'];?>/register.php?invite=<?php echo $row['invitation_key'];?>" class="btn btn-primary">Invite Link</a>
                </div>
            </div>
        </div>
        <?php
    }
    echo '</div>';
    if ($any) {
        print('<br/>');
    }
}

printf('<h1>New invitation</h1>');
printf('<form method="POST" action="invitations.php">');
csrf_html();

printf('<input class="text" type="text" name="email" placeholder="Email address">');

printf('<input class="submit" type="submit" value="Create">');

printf('</form>');
printf('</section>');

site_footer();

