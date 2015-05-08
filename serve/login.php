<?php

require_once '../site.php';
db_connect();

if (array_key_exists('user', $_SESSION)) {
    header(sprintf('Location: %s/', $CONFIG['base_url']));
    die;
}

$error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $data = array();
    $keys = array('username', 'password');
    foreach ($keys as $key) {
        if (!array_key_exists($key, $_POST)) {
            die;
        }
        $data[$key] = $_POST[$key];
    }

    $res = db_query_params('SELECT user_id, password FROM users WHERE username = $1 LIMIT 1', array($data['username'])) or die('db error');
    if ($row = pg_fetch_assoc($res)) {
        if (!password_verify($data['password'], $row['password'])) {
            $error = true;
        }
    } else {
        $error = true;
    }

    if (!$error) {
        $_SESSION['user'] = array(
            'username' => $data['username'],
            'user_id' => $row['user_id'],
        );

        header(sprintf('Location: %s/', $CONFIG['base_url']));
        die;
    }
}


printf('<form method="POST" action="login.php">');
csrf_html();

if (array_key_exists('success', $_GET)) {
    printf('Registration successful, go ahead and log in');
    printf('<br/>');
    printf('<br/>');
}

if ($error) {
    printf('Invalid credentials');
    printf('<br/>');
    printf('<br/>');
}

printf('Username: ');
printf('<input name="username" type="text" />');
printf('<br/>');

printf('Password: ');
printf('<input name="password" type="password" />');
printf('<br/>');

printf('<input type="submit" value="Login" /> or <a href="/register.php">Register</a>');

printf('</form>');

