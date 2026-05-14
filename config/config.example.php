<?php

return [
    'app_name' => 'FaceAuth Premium',
    'brand_name' => 'FaceAuth',
    'database' => [
        'driver' => 'sqlite',
        'path' => __DIR__ . '/../storage/faceauth.sqlite',

        // Для MySQL замените driver на mysql и заполните параметры ниже:
        // 'driver' => 'mysql',
        // 'host' => '127.0.0.1',
        // 'port' => '3306',
        // 'name' => 'faceauth',
        // 'user' => 'faceauth_user',
        // 'password' => 'strong-password',
        // 'charset' => 'utf8mb4',
    ],
    'upload_path' => __DIR__ . '/../storage/uploads',
    'admin_user' => 'admin',
    'admin_password_hash' => password_hash('change-me-now', PASSWORD_DEFAULT),
];
