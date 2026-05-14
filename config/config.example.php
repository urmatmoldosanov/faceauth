<?php

return [
    'app_name' => 'FaceAuth Premium',
    'brand_name' => 'FaceAuth',
    'database_path' => __DIR__ . '/../storage/faceauth.sqlite',
    'upload_path' => __DIR__ . '/../storage/uploads',
    'admin_user' => 'admin',
    'admin_password_hash' => password_hash('change-me-now', PASSWORD_DEFAULT),
];
