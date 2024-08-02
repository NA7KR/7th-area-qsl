<?php
/*
Copyright © 2024 NA7KR Kevin Roberts. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 */
session_start();

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$title = "Users do not want cards ";
$config = include('config.php');
include("$root/backend/header.php"); 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
$root = "/var/www/Working/";
include_once("$root/backend/Exception.php");
include_once("$root/backend/PHPMailer.php");
include_once("$root/backend/SMTP.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
$config = include("config.php");
$emailConfig = $config['email'];
