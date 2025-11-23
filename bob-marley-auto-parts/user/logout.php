<?php
// user/logout.php

require_once '../includes/init.php';

// Полная очистка сессии
session_unset();
session_destroy();
session_write_close();
setcookie(session_name(), '', 0, '/');

// Жесткое перенаправление на главную
echo '<script>window.location.href = "../index.php";</script>';
exit;


