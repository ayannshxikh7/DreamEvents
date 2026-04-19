<?php
session_start();
session_unset();
session_destroy();
header('Location: /DreamEvents/auth/login.php');
exit;
