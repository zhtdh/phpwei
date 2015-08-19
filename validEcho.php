<?php
/**
 * User: dh
 * Date: 2015/8/17
 * Time: 13:34
 */
    if (isset($_GET['echostr'])) {
        include_once "inc/WXBizMsgCrypt.php";

        $sVerifyMsgSig = urldecode($_GET['msg_signature']);
        $sVerifyTimeStamp = urldecode($_GET['timestamp']);
        $sVerifyNonce = urldecode($_GET['nonce']);
        $sVerifyEchoStr = urldecode($_GET['echostr']);

        $sEchoStr= '';

        $wxcpt = new WXBizMsgCrypt(GTOKEN, GAESKEY, GCORPID);
        $errCode = $wxcpt->VerifyURL($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr, $sEchoStr);

        echo $sEchoStr;

    }