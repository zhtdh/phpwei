<?php

define('GCORPID', '22222222');
define('GSECRET', '222222-2222222');
define('GAESKEY', '222222222222222222222222222222');
define('GTOKEN', '222222222222222');

if (isset($_GET['echostr'])) {
    include_once 'validEcho.php';
    exit;
}

if (isset($_GET['msg_signature'])) {
    include_once 'dealMsg.php';
    exit;
}

