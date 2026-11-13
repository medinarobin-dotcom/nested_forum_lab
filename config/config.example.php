<?php
// Copy this file to config/config.php and put your own local values in it.
// config/config.php is in .gitignore so credentials are never committed.
return [
    'db' => [
        'dsn'      => 'mysql:host=127.0.0.1;port=3306;dbname=nested_forum;charset=utf8mb4',
        'user'     => 'root',
        'password' => '',
    ],
    // Indentation stops growing after this displayed depth (labels stay accurate).
    'indent_cap'      => 8,
    // Server-side limit for new replies (displayed depth, top-level = 0).
    'max_reply_depth' => 20,
    // Traversal limit when rendering a thread (protects against huge or broken data).
    'max_render_nodes' => 2000,
    'max_comment_length' => 2000,
];
