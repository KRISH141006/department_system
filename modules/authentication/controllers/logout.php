<?php
session_start();
session_destroy();
header("Location: $base_path/public/login.php");
exit;
