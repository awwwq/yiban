<?php
/**
 *******************************************
 *    Yiban - Developer URL Verification   *
 *******************************************
 *
 * GUIDE:
 * - Please put this file to your server where you specified location on our site.
 * - We will check the Url right after you submit
 * 
 * If any questions come up, you may contact our custom service 
 *   by phone: 60161000 or mail: contact@yiban.cn
 * 
 */


/**
 * define your token
 */
define('TOKEN', 'test');

$yibanUrlObj = new Yiban_URL_VALIDATE();
if(isset($_GET['echostr'])){
$yibanUrlObj->valid();}
else{
	$yibanUrlObj->responseMsg();}


class Yiban_URL_VALIDATE
{
    public function valid()
    {
		echo $this->checkSignature() ? $_GET["echostr"] : '';
		exit;
    }
    
    private function checkSignature()
    {
		$tmpArr = array(TOKEN, $_GET['timestamp'], $_GET['nonce']);
		sort($tmpArr, SORT_STRING);
		return sha1(implode($tmpArr)) == $_GET['signature'];
    }
	public function responseMsg()
    {
		$postStr = file_get_contents("php://input");//获取输入
		if (!empty($postStr)){
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$fromUsername = $postObj->FromUserName;
			$toUsername = $postObj->ToUserName;
			$keyword = trim($postObj->Content);
			$time = time();
			$textTpl = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[%s]]></MsgType>
				<Content><![CDATA[%s]]></Content>
				</xml>";
			$keyarry=explode("+",$keyword);
			$contentStr="请输入正确指令";
			if(preg_match("/^(\d{8})$/",$keyarry[0])){
				$dbhost='localhost';
				$dbuser='root';
				$dbpass='abcd';//databasepassword
				$conn=new MySQLi($dbhost,$dbuser,$dbpass,'test');
				if(mysqli_connect_errno())
				{
					$contentStr= "Failed to connect to SQL" ;
				}
				else{	   
					$sql=sprintf("SELECT * FROM`4` WHERE id=%s AND name='%s'",$keyarry[0],$keyarry[1]);
					mysqli_query($conn,"SET NAMES 'utf8'");
					$result=mysqli_query($conn,$sql);
					$row = mysqli_fetch_assoc($result);
					if($row==FALSE){$contentStr="未查找到相关信息";}
					else{
						$contentStr=sprintf("%s   %s",$row["depart"],$row["domitory"]);
						$domitory=$row["domitory"];
						mysqli_free_result($result);
						$sql=sprintf("SELECT * FROM`4` WHERE domitory='%s'",$domitory);
						$result=mysqli_query($conn,$sql);
						$allrow=array();
						$rownum=mysqli_num_rows($result);
						while($row = mysqli_fetch_row($result)){
							$allrow[]=$row;
						}
						for($i=0;$i<$rownum;$i++){
							$namestr=sprintf("%s  %s",$namestr,$allrow[$i][1]);
						}
						$contentStr=sprintf("%s \n全寝室成员：%s",$contentStr,$namestr); 
					}
				   
					mysqli_free_result($result);
					$conn->close();
				}	
			}
			if($keyword=="天气"){
				
				$urlweather="http://api.map.baidu.com/telematics/v3/weather?location=%E9%87%8D%E5%BA%86&output=xml&ak=RFvuE869shSLF8P1rbkGFn5h9c0KQYbx";
				$weatherxml=file_get_contents($urlweather);
				$weather=simplexml_load_string($weatherxml);
				$weatherdate=preg_replace('/\(/','',$weather->results->weather_data->date[0]);
				$weatherdate=preg_replace('/\)/','',$weatherdate);
				$daytemperature=$weather->results->weather_data->temperature[0];
				$dayweather=$weather->results->weather_data->weather[0];
				$PM25=$weather->results->pm25;
				$chuanyi=$weather->results->index->des[0];
				$tomorrowweather=$weather->results->weather_data->weather[1];
				$tomorrowtemperature=$weather->results->weather_data->temperature[1];
				$contentStr=sprintf(" 重庆     %s\n%s \n%s\nPM2.5:    %s\n%s\n明天： %s  %s",$daytemperature,$weatherdate,$dayweather,$PM25,$chuanyi,$tomorrowweather,$tomorrowtemperature);	
			}
			
			$msgType = "text";  
			$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType,$contentStr);
			echo $resultStr;
 
		}else{
			echo "";
			exit;
		}
	}
	
}
?>
