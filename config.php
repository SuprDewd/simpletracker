<?php

$CONFIG = array(
    // Example PostgreSQL configuration
    'db' => array(
        'connection_string' => 'pgsql:host=localhost;dbname=simpletracker',
        'type' => 'pgsql',
        'user' => 'simpletracker',
        'password' => 'simpletracker',
    ),

    // Example MySQL configuration
    // 'db' => array(
    //     'connection_string' => 'mysql:host=localhost;dbname=simpletracker',
    //     'type' => 'mysql',
    //     'user' => 'simpletracker',
    //     'password' => 'simpletracker',
    // ),

    'base_url' => 'https://domain.xyz', // no trailing slash
    'site_title' => 'simpletracker',
    'max_torrent_size' => 20*1024*1024,
);

