<?php
session_start();

// Data user hardcoded (bisa juga di file terpisah)
$users = [
    'resepsionis1' => [
        'password' => password_hash('resepsionis123', PASSWORD_DEFAULT),
        'role' => 'resepsionis',
        'name' => 'Budi Siregar'
    ],
    'admin1' => [
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
        'name' => 'Admin Hotel'
    ]
];

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if(isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
    $_SESSION['user'] = [
        'username' => $username,
        'role' => $users[$username]['role'],
        'name' => $users[$username]['name']
    ];
    // Redirect berdasarkan role
    if($users[$username]['role'] == 'admin') {
        header("Location: ../public/admin/laporan.php");
    } else {
        header("Location: ../public/Resepsionis/index.php");
    }
} else {
    header("Location: ../public/login.php?error=1");
}
exit;