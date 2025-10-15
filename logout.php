<?php
session_start(); // session başlat
session_unset(); // tüm session değişkenlerini temizle
session_destroy(); // session'ı yok et

header("Location: index.php"); // ana sayfaya yönlendir
exit;
?>