	<?php
	
	include_once "sha1.php";
	include_once "xmlparse.php";
	include_once 'pkcs7Encoder.php';
	include_once "errorCode.php";
	include_once 'WXInInfor.php';
	include_once 'include/WeiXinConfig.php';
	
	class WXBasicApi {
		private $encodingAesKey="";
		private $corpId;
		private $pkcs;
		private $token;
		private $timestamp;
		private $nonce;
		
		public function __construct($encodingAesKey,$corpId,$token,$timestamp,$nonce){
			
			$this->encodingAesKey=$encodingAesKey;
			$this->corpId=$corpId;
			$this->pkcs=new Prpcrypt($this->encodingAesKey);
			$this->token=$token;
			$this->timestamp=$timestamp;
			$this->nonce=$nonce;
			logit("construct:".$this->encodingAesKey."-----".$this->corpId."-----".$this->token."-----".$this->timestamp."-----".$this->nonce);
		}
		
		public function responseMsg(){
			$postStr=$GLOBALS["HTTP_RAW_POST_DATA"];
			logit("logpost:".$postStr);
			$de_Encrypt=$this->getDeMsg($postStr);
			//将字符串xml化
			$de_Encrypt_Obj=simplexml_load_string($de_Encrypt);
			logit("Object xml content".$de_Encrypt_Obj->Content);
			$de_Encrypt_MsgType=$de_Encrypt_Obj->MsgType;
			logit("receive Type".$de_Encrypt_MsgType);
			switch ($de_Encrypt_MsgType){
				case "event":
					$this->receiveEvt($de_Encrypt_Obj);
					break;
				case "text":
					$this->receiveText($de_Encrypt_Obj);
					break;
				case "image":
					$this->receiveImg($de_Encrypt_Obj);
					break;
				case "location":
					$this->receiveLoc($de_Encrypt_Obj);
					break;
				case "voice":
					$this->receiveVoice($de_Encrypt_Obj);
					break;
			}
			
		}
		/**
		 * 对事件作出回应
		 * @param object $Object 传入的xml包 
		 * 
		 */
		private function receiveEvt($Object){
			
			logit("evt TYPE:".$Object->Event);
			$content="";
			switch ($Object->Event){
				case "LOCATION":
					$content="经度：".$Object->Longitude."\n纬度：".$Object->Latitude;
					$result=$this->transmitText($Object, $content);
					break;
				case "click":
					$content="Press the key=".$Object->EventKey." button";
					logit("Press the key=".$Object->EventKey." button");
					$result=$this->transmitText($Object, $content);
					break;
				case "scancode_push":
					$content="scancode_push";
					logit($content);
					$result=$this->transmitText($Object, $content);
					break;
				case "scancode_waitmsg":
					$content="scancode_waitmsg";
					logit($content);
					$result=$this->transmitText($Object, $content);
					break;
				case "pic_sysphoto":
					$content="pic_sysphoto";
					logit($content);
					$result=$this->transmitText($Object, $content);
					break;
				case "pic_photo_or_album":
					$content="pic_photo_or_album";
					logit($content);
					$result=$this->transmitText($Object, $content);
					break;
				case "pic_weixin":
					$content="pic_weixin";
					logit($content);
					$result=$this->transmitText($Object, $content);
					break;
				case "location_select":
					$content="Location_Y:".$Object->SendLocationInfo->Location_Y."Location_X".$Object->SendLocationInfo->Location_X."\n您选择了".$Object->SendLocationInfo->Label;
					logit($content);
					$result=$this->transmitText($Object, $content);
					break;
			}
			logit("the xml out put of evt:".$result);
			echo $result;
		}
		
		/**
		 * 对文本作出回应
		 * @param object $Object 传入的xml包
		 */
		private function receiveText($Object){
			logit("text Type：".$Object->MsgType);
			logit("text Content".$Object->Content);
			$a=$Object->Content;
			$echoStr=$this->transmitText($Object,$a);
			echo $echoStr;
		}
		
		/**
		 * 对图片作出回应 
		 * @param object $Object 传入的xml包 
		 */
		private function receiveImg($Object){
			logit("recevieImg:".$Object->MsgType);
			$WXinit=new WXInInfor($this->corpId,SECRET);
			$result=$WXinit->getImg($Object->MediaId);
			$a=print_r($result,true);
			//logit("imgHead:".$a);
			$echoStr="";
			$echoStr=$this->transmitText($Object,"文件已保存至服务器端");
			ECHO $echoStr;
		}
		
		/**
		 * 对地理位置作出回应
		 * @param object $Object 传入的xml包
		 */
		private function receiveLoc($Object){
			logit("Loc类型：".$Object->MsgType);
		}
		
		/**
		 * 对语音作出回应
		 * @param object $Object 传入的xml包
		 */
		private function receiveVoice($Object){
			logit("Voice类型：".$Object->MsgType);
		}
		
		/**
		 * 获取解码后的encrypt
		 * @param $EnMsg:xml字符串
		 * return:成功字符串$de_Encrypt。失败exit()
		 */
		
		private function getDeMsg($EnMsg){
			$postObj=simplexml_load_string($EnMsg);
			$en_Encrypt=$postObj->Encrypt;
			logit("未解密的xml".$en_Encrypt);
			$Arr_Encrypt=$this->pkcs->decrypt($en_Encrypt,$this->corpId);
			
			
			$errCode=$Arr_Encrypt[0];
			if($errCode==0){
				$de_Encrypt=$Arr_Encrypt[1];
				logit("解密后的xml".$de_Encrypt);
				return $de_Encrypt;				
			}
			else {
				logit("错误代码:".$errCode);
				exit();
			}
			
		}
		
		/**
		 * 将content转码为xml字符串
		 * @param unknown $txt
		 * @return string 可直接发送的xml字符串
		 */
		private function transmitText($Object,$content){
			$textTpl="<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[%s]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			</xml>";
			$textTpl=sprintf($textTpl,$Object->FromUserName,$this->corpId,time(),"text",$content);
			logit("before encode xmlStr:".$textTpl);
			//result为对象级明文xml字符串
			$result=$this->pkcs->encrypt($textTpl, $this->corpId);
			logit("after encode xmlStr".$result[1]);
			
			//result为企业级密文xml字符串
			$result=$this->generate($result[1]);
			return $result;			
		}
		
		/**
		 * 将content转码为xml字符串
		 * @param object $object 
		 * @param mediaId 媒体Id
		 */
		private function transmitImg($Object,$MediaId){
			$textTpl="<xml>
						   <ToUserName><![CDATA[%s]]></ToUserName>
						   <FromUserName><![CDATA[%s]]></FromUserName>
						   <CreateTime>%s</CreateTime>
						   <MsgType><![CDATA[%s]]></MsgType>
						   <Image>
						       <MediaId><![CDATA[%s]]></MediaId>
						   </Image>
						</xml>";
			$textTpl=sprintf($textTpl,$Object->FromUserName,$this->corpId,time(),"image",$MediaId);
			logit("before encode xmlStr For img:".$textTpl);
			//result为对象级明文xml字符串
			$result=$this->pkcs->encrypt($textTpl, $this->corpId);
			logit("after encode xmlStr For img".$result[1]);
				
			//result为企业级密文xml字符串
			$result=$this->generate($result[1]);
			return $result;
		}
		/**
		 * 将对象的xml包装成企业xml
		 * @param string $format 对象的xml字符串
		 * @return string 企业级字符串
		 */
		private function generate($xml){
			$format = "<xml>
						<Encrypt><![CDATA[%s]]></Encrypt>
						<MsgSignature><![CDATA[%s]]></MsgSignature>
						<TimeStamp>%s</TimeStamp>
						<Nonce><![CDATA[%s]]></Nonce>
						</xml>";
			
			$sha1=new SHA1();
			$array=$sha1->getSHA1($this->token, $this->timestamp, $this->nonce,$xml);
			$signature=$array[1];
			$format=sprintf($format,$xml,$signature,$this->timestamp,$this->nonce);
			$this->logit("company secret".$format);
			return $format;
		}
		
		/**
		 * 加密字符串
		 * @param string $DeMsg待加密的字符串
		 * @return 加密后的字符串
		 */
		private function getEnMsg($DeMsg){
			return $this->pkcs->encrypt($DeMsg, $this->corpId);
			
		}
		
		
		public function logit($txt){
			$myfile = fopen("newfile.txt", "a+") or die("Unable to open file!");
			ini_set('date.timezone','Asia/Shanghai');
			$txt=date('Y-m-d H:i:s',time())."\t".$txt."\r\n";
			fwrite($myfile, $txt);
			fclose($myfile);
		}
	}
	
	?>