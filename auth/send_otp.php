<?php
session_start();
include "../config/database.php";

// Include PHPMailer from the 'sms' folder as requested
require '../sms/Exception.php';
require '../sms/PHPMailer.php';
require '../sms/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



?>
