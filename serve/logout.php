<?php

require_once '../site.php';

if (array_key_exists('user', $_SESSION)) {
    unset($_SESSION['user']);
}

header(sprintf('Location: %s/', $CONFIG['base_url']));
die;

