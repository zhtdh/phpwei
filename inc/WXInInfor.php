<?php
/**
 * 微信主动调用的接口类
 * @author Administrator
 *
 */
include_once 'include/WeiXinConfig.php';
class WXInInfor {
	private $CorpId;
	private $Secret;
	private $AccessToken;
	
	/**
	 * 构造函数，为accessToken赋值
	 * @param string $CorpId
	 * @param string $Secret
	 */
	public function __construct($CorpId=NULL,$Secret=NULL){
		$this->CorpId=$CorpId;
		$this->Secret=$Secret;
		$url="https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=".$this->CorpId."&corpsecret=".$this->Secret;
		$result=$this->https_request($url);
		$result=json_decode($result,true);
		$this->AccessToken=$result['access_token'];
	}
	
	/**
	 * 获取图片
	 * @param string $mediaId
	 * @return array 图片头文报
	 */
	public function getImg($mediaId){
		$url="https://qyapi.weixin.qq.com/cgi-bin/media/get?access_token=".$this->AccessToken."&media_id=".$mediaId;
		$result=$this->downloadWeixinFile($url);
		//$result=$this->downloadWeixinFile($url);
		//$result=json_decode($result,true);
		//print_r($result);
		//$result=print_r($result,true);
		logit("imge package:".$result);
		$filename=$mediaId.".jpg";
		//logit("result['body']".$result['body']);
		logit("after shift4".$result['header']['url']);
		$this->saveImg($filename,$result['body']);
		return $result;
	}
	
	/**
	 * 文件写入
	 * @param string filename
	 * @param string 文件体
	 */
	private function saveImg($filename,$filecontent){
		logit($filename."---".$filecontent);
		$local_file=fopen($filename,"w");
		if(false!==$local_file){
			if(false!==fwrite($local_file,$filecontent)){
				fclose($local_file);
			}
			
		}
	}
	
	/**
	 * 下载图片
	 * @param string $url
	 * @return array 数组:
	 */
	private function downloadWeixinFile($url){
		logit("url for download img:".$url);
		$ch=curl_init($url);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_NOBODY, 0);//只取body头
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		$package=curl_exec($ch);
		$httpinfo=curl_getinfo($ch);
		curl_close($ch);
		$imageAll=array_merge(array('header'=>$httpinfo),array('body'=>$package));
		//logit("package".print_r($imageAll,true));
		return $imageAll;
	}
	
	public function logit($txt){
		$myfile = fopen("newfile.txt", "a+") or die("Unable to open file!");
		ini_set('date.timezone','Asia/Shanghai');
		$txt=date('Y-m-d H:i:s',time())."\t".$txt."\r\n";
		fwrite($myfile, $txt);
		fclose($myfile);
	}
	/**
	 * 根据code,agentid换取userId
	 * @param string code:企业号返回的code
	 * @param string agentid:应用的id
	 * @return string $UserId
	 */
	public function getUseId($code,$agentid){
		
		$url="https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token=".$this->AccessToken."&code=".$code."&agentid=".$agentid;
		$result=$this->https_request($url);
		$result=json_decode($result,true);
		/*echo "result:";
		print_r($result);
		echo "<Br>";*/
		$UserId=$result['UserId'];
		return $UserId;
	}
	
	/**
	 * curl传递数据，支持get与post
	 * @param string $url
	 * @param array $data
	 * @return json
	 */
	
	protected function https_request($url,$data=null)
	{
		//echo "<br>url".$url."<br>";
		$curl=curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if(!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output=curl_exec($curl);
		curl_close($curl);
		logit("output".$output);
		return $output;
	}
}

?>