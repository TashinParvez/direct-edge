<?php
// Automatically detect base URL (works for localhost and live server)
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$base_url .= "://" . $_SERVER['HTTP_HOST'] . "/direct-edge/";
