<?php

require_once '../site.php';
db_connect();

if (array_key_exists('user', $_SESSION)) {
    header(sprintf('Location: %s/', $CONFIG['base_url']));
    die;
}

$errors = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $data = array();
    $keys = array('username', 'password', 'password_again', 'email', 'invitation');
    foreach ($keys as $key) {
        if (!array_key_exists($key, $_POST)) {
            die;
        }
        $data[$key] = $_POST[$key];
    }

    if (strlen($data['username']) < 3) $errors []= 'username too short';
    if (strlen($data['username']) > 25) $errors []= 'username too long';
    if (!preg_match('/^[-_a-zA-Z0-9]*$/', $data['username'])) $errors []= 'invalid characters in username';
    if (strlen($data['password']) < 5) $errors []= 'password too short';
    if (strlen($data['password']) > 40) $errors []= 'password too long';
    if ($data['password'] !== $data['password_again']) $errors []= 'passwords don\'t match';
    if (strpos($data['email'], '@') === false) $errors []= 'invalid email';
    if (strlen($data['email']) > 255) $errors []= 'email too long';

    if (empty($errors)) {
        $res = db_query_params('SELECT 1 FROM users WHERE username=$1 OR email=$2', array($data['username'], $data['email'])) or die('db error');
        if (pg_num_rows($res) > 0) {
            $errors []= 'username or email already taken';
        }
    }

    if (empty($errors)) {
        $res = db_query_params('SELECT invitation_id, user_id FROM invitations WHERE email=$1 AND invitation_key=$2', array($data['email'], $data['invitation']));
        if (!($invitation_row = pg_fetch_assoc($res))) {
            $errors []= 'invitation key is not valid for this email address';
        }
    }

    if (empty($errors)) {
        $pw = password_hash($data['password'], PASSWORD_DEFAULT) or die('password error');
        db_query_params('INSERT INTO users (username, password, passkey, email, invited_by) VALUES ($1,$2,$3,$4,$5)', array($data['username'], $pw, random_hash(), $data['email'], $invitation_row['user_id'])) or die('db error');
        db_query_params('DELETE FROM invitations WHERE invitation_id = $1', array($invitation_row['invitation_id'])) or die('db error');

        header(sprintf('Location: %s/login.php?success', $CONFIG['base_url']));
        die;
    }
}


site_header();

printf('<form method="POST" action="register.php">');
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

printf('Username: ');
printf('<input name="username" type="text" />');
printf('<br/>');

printf('Password: ');
printf('<input name="password" type="password" />');
printf('<br/>');

printf('Password (again): ');
printf('<input name="password_again" type="password" />');
printf('<br/>');

printf('Email: ');
printf('<input name="email" type="text" />');
printf('<br/>');

printf('Invitation key: ');
printf('<input name="invitation" type="text" value="%s" />', array_key_exists('invite', $_GET) ? html_escape($_GET['invite']) : '');
printf('<br/>');

printf('<input type="submit" value="Register" />');

printf('</form>');

site_footer();

