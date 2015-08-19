<?php
/**
 * User: dh
 * Date: 2015/8/17
 * Time: 13:43
 */
include_once "inc/WXBizMsgCrypt.php";

$sReqMsgSig = urldecode($_GET['msg_signature']);
$sReqTimeStamp = urldecode($_GET['timestamp']);
$sReqNonce = urldecode($_GET['nonce']);
$sReqData = file_get_contents("php://input");

$sMsg = '';  //解析之后的明文
$wxcpt = new WXBizMsgCrypt(GTOKEN, GAESKEY, GCORPID);
$errCode = $wxcpt->DecryptMsg($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData, $sMsg);

$mycontent = '';

if ($errCode == 0) {
    $xml = new DOMDocument();
    $xml->loadXML($sMsg);
    $reqToUserName = $xml->getElementsByTagName('ToUserName')->item(0)->nodeValue;
    $reqFromUserName = $xml->getElementsByTagName('FromUserName')->item(0)->nodeValue;
    $reqCreateTime = $xml->getElementsByTagName('CreateTime')->item(0)->nodeValue;
    $reqMsgType = $xml->getElementsByTagName('MsgType')->item(0)->nodeValue;
    $reqContent = $xml->getElementsByTagName('Content')->item(0)->nodeValue;
    $reqMsgId = $xml->getElementsByTagName('MsgId')->item(0)->nodeValue;
    $reqAgentID = $xml->getElementsByTagName('AgentID')->item(0)->nodeValue;
    $reqEvent = $xml->getElementsByTagName('Event')->item(0)->nodeValue;
    $reqEventKey = $xml->getElementsByTagName('EventKey')->item(0)->nodeValue;

    switch ($reqMsgType){
        case "event":
            $mycontent = dealMsgEvent($reqEventKey);
            break;
        case "text":
            $mycontent = dealMsgText($reqContent);
            break;
        case "image":
            break;
        case "location":
            break;
        case "voice":
            break;
        default:
            $mycontent = '未知消息类型：' . $reqMsgType;
            break;
    }

    $sRespData =
        "<xml><ToUserName><![CDATA[" .$reqFromUserName. "]]></ToUserName>
        <FromUserName><![CDATA[". GCORPID ."]]></FromUserName>
        <CreateTime>" . sReqTimeStamp . "</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[".$mycontent."]]></Content>
        </xml>";
    $sEncryptMsg = ""; //xml格式的密文
    $errCode = $wxcpt->EncryptMsg($sRespData, $sReqTimeStamp, $sReqNonce, $sEncryptMsg);
    if ($errCode == 0) {
    //file_put_contents('smg_response.txt', $sEncryptMsg); //debug:查看smg
        print($sEncryptMsg);
    } else {
        print('发送消息处理失败，请通知管理员。 ' . $errCode . "\n\n");
    }
} else {
    print('接收消息处理失败，请通知管理员。 ' . $errCode . "\n\n");
}

function dealMsgEvent($aEventKey)
{
    $lRtn = '';
    switch ($aEventKey) {
        case 'queryBill':
            $lRtn = '请输入：b?提单号';
            break;
        case 'queryVoyage':
            $lRtn = '请输入：v?航次号';
            break;
        case 'queryImport':
            $lRtn = '请输入：i?进口信息';
            break;
        case 'queryExport':
            $lRtn = '请输入：e?出口信息';
            break;
    }
    return $lRtn;
}

function dealMsgText($aTextContent){
    $lRtn = '';
    $lTarget = mb_substr($aTextContent, 2, null, 'utf8');
    $lPreFix = mb_strtoupper(mb_substr($aTextContent, 0, 1, 'utf8'));
    $lMarkStr = mb_substr($aTextContent, 1, 1, 'utf8');

    if ($lMarkStr == '?' or $lMarkStr == '？'){
        switch ($lPreFix) {
            case 'B':
                $lRtn='您要查提单号: ' . $lTarget;
                break;
            case 'I':
                $lRtn='您要查进口: ' . $lTarget;
                break;
            case 'E':
                $lRtn='您要查出口: ' . $lTarget;
                break;
            case 'V':
                $lRtn='您要查航次: ' . $lTarget;
                break;
            default:
                $lRtn= '发来没定义的消息：' . $aTextContent;
        }
    }
    else {
        $lUrl='http://www.tuling123.com/openapi/api?key=xxxxxxxxxxxxxxx&info='.$aTextContent;
        // {"code":100000,"text":"很高兴和你聊天"}
        $lAnswer = json_decode(file_get_contents($lUrl));
        if (property_exists($lAnswer, 'text'))
            $lRtn = $lAnswer->text;
        else
            $lRtn = var_dump($lAnswer);
    }

    return $lRtn;
}

?>