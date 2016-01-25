<?php
$wxkey="wxaaaaa5a5393b2c14";
$wxsec="aaaaaaaaaaaaa00e1c3c5c7bba039289";

header('Content-Type: application/javascript; charset=utf-8');

$user="";
if(isset($_GET["return"])&&isset($_GET["code"])){
	//微信返回。得到openid等信息。
	$return=base64_decode($_GET["return"]);
	$return.=strstr($return,"?")?"&":"?";
	$return.="YUY_user=";

	require_once "jssdk.php";
	$jssdk=new JSSDK($wxkey,$wxsec);
	$user=json_decode($jssdk->getOpenId($_GET["code"]));
	if(isset($user->openid)){
		if(isset($_GET["state"])&&$_GET["state"]=="userinfo"){
			$user=json_decode($jssdk->getUserInfo($user));						
		}else{
			$user=array("openid"=>$user->openid,"unionid"=>$user->unionid);	
		}
	}	

	$return.=base64_encode(json_encode($user));
	header("HTTP/1.1 301 Moved Permanently"); 
	header("Location: ".$return); 
	exit();
}

echo  	'YUY= window.hasOwnProperty("YUY")?window["YUY"]:{};';
echo	'YUY.user=YUY.hasOwnProperty("user")?YUY["user"]:{};';

//如果cookie里面已经有了。那么直接输出就是了。
if(isset($_COOKIE["YUY_user"])){
	if(stristr($_SERVER['HTTP_USER_AGENT'],"micromessenger")&&isset($_GET["allUserInfo"])){
		$alluserinfo=json_decode($_COOKIE["YUY_user"]);
		if(isset($alluserinfo->nickname)){
			$user=$_COOKIE["YUY_user"];
		}else if(isset($alluserinfo->openid)&&file_exists("openid/".$alluserinfo->openid)){
			$user=file_get_contents("openid/".$alluserinfo->openid);
			setcookie("YUY_user", $user, 2147483647);
		}
	}else{
		$user=$_COOKIE["YUY_user"];
	}
}
if(empty($user)){
	if(stristr($_SERVER['HTTP_USER_AGENT'],"micromessenger")){
		//微信中
		$referer=empty($_SERVER['HTTP_REFERER'])?"":$_SERVER["HTTP_REFERER"];
		parse_str(substr($referer,strpos($referer,"?")+1),$refererkv);	
		if(isset($refererkv["YUY_user"])){
			$user=base64_decode($refererkv["YUY_user"]);
		}else if($referer){
			//没有取得到。需要跳转获得。
			$sdkUrl="http://service.houpix.com/wxsdk.js";
			$type=isset($_GET["allUserInfo"])?"userinfo":"base";
			echo "location.href=\"https://open.weixin.qq.com/connect/oauth2/authorize?appid=${wxkey}&redirect_uri=".urlencode($sdkUrl."?return=".base64_encode($referer))."&response_type=code&scope=snsapi_${type}&state=".$type.'";';
			exit();	
		}

	}else{
		//生成一个cookie就可以了。
		$user="{'openid':'".uniqid()."'}";
		
	}
	//设置cookie，方便下次直接调用。
	setcookie("YUY_user", $user, 2147483647);
}

if(empty($user))$user="{}";
echo	"YUY.user=$user;";


?>
//日志方法
YUY.log=function(msg){
	if(window.hasOwnProperty("console"))
		console.log(msg);
	new Image().src=('https:' == document.location.protocol?"https:":"http:")+"//www.houpix.com/log.gif?uid="+YUY.user.openid+"&event="+msg;
}
//加载别的javascript
YUY.loadScript=function(url,callback){
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url;
    script.onreadystatechange = callback;
    script.onload = callback;
    var s = document.getElementsByTagName("script")[0]; 
    s.parentNode.insertBefore(script, s);
}
YUY.assetRun=function(c,callback){
	var interval=setInterval(function(){
		if(typeof c!=="undefined"){
			clearInterval(interval);
			if(typeof callback == "function")
				callback();
		}
	},1000);
}

if(!navigator.userAgent.match(/micromessenger/i))
	YUY.configWeixinShare=function(title,description,imageUrl,link,shareSuccessCallback){};

<?php
if(stristr($_SERVER['HTTP_USER_AGENT'],"micromessenger")){
	//如果在微信里面。可以加载微信js sdk
	require_once "jssdk.php";
	$jssdk=new JSSDK($wxkey,$wxsec);
	$signPackage = $jssdk->GetSignPackage();
?>
YUY.loadScript(('https:' == document.location.protocol?"https:":"http:")+"//res.wx.qq.com/open/js/jweixin-1.0.0.js",function(){
  wx.config({
    debug: <?=isset($_GET["debug"])?"true":"false"?>,
    appId: '<?php echo $signPackage["appId"];?>',
    timestamp: <?php echo $signPackage["timestamp"];?>,
    nonceStr: '<?php echo $signPackage["nonceStr"];?>',
    signature: '<?php echo $signPackage["signature"];?>',
    jsApiList: [
      'checkJsApi',
        'onMenuShareTimeline',
        'onMenuShareAppMessage',
        'onMenuShareQQ',
        'onMenuShareWeibo',
        'onMenuShareQZone',
        'hideMenuItems',
        'showMenuItems',
        'hideAllNonBaseMenuItem',
        'showAllNonBaseMenuItem',
        'translateVoice',
        'startRecord',
        'stopRecord',
        'onVoiceRecordEnd',
        'playVoice',
        'onVoicePlayEnd',
        'pauseVoice',
        'stopVoice',
        'uploadVoice',
        'downloadVoice',
        'chooseImage',
        'previewImage',
        'uploadImage',
        'downloadImage',
        'getNetworkType',
        'openLocation',
        'getLocation',
        'hideOptionMenu',
        'showOptionMenu',
        'closeWindow',
        'scanQRCode',
        'chooseWXPay',
        'openProductSpecificView',
        'addCard',
        'chooseCard',
        'openCard'
    ]
  });
YUY.configWeixinShare=function(title,description,imageUrl,link,shareSuccessCallback){
  wx.ready(function () {
	title=title?title:document.title;
	description=description?description:title;
	imageUrl=imageUrl?imageUrl:"http://www.houpix.com/logo.jpg";
	link=link?link:document.URL.replace(/YUY_user.*[&|$]/,"");

	wx.onMenuShareAppMessage({
	      title: title,
	      desc: description,
	      link: link,
	      imgUrl: imageUrl,
	
	      success: function (res) {
		YUY.log("MenuShareAppMessageSuccess");
		if(shareSuccessCallback)
		  shareSuccessCallback();
	      },
	      cancel: function (res) {
		YUY.log("MenuShareAppMessageCancel");
	      },
	      fail:function(res){
		YUY.log("MenuShareAppMessageFail");
	      }
    	});

	
	wx.onMenuShareTimeline({
	      title: title,
	      link: link,
	      imgUrl: imageUrl,
	      
	      success: function (res) {
	        YUY.log("MenuShareTimelineSuccess");
	        if(shareSuccessCallback)
	          shareSuccessCallback();
	      },
	      cancel: function (res) {
	        YUY.log("MenuShareTimelineCancel");
	      },
	      fail:function(res){
	        YUY.log("MenuShareTimelineFail");
	      }
	 });
	
	 wx.onMenuShareQQ({
      		 title: title,
      		desc: description,
      		link: link,
      		imgUrl: imageUrl,
      		
      		success: function (res) {
      		  YUY.log("MenuShareQQSuccess");
      		  if(shareSuccessCallback)
      		    shareSuccessCallback();
      		},
      		cancel: function (res) {
      		  YUY.log("MenuShareQQCancel");
      		},
      		fail:function(res){
      		  YUY.log("MenuShareQQFail");
      		}
    	});

	 wx.onMenuShareWeibo({
      		 title: title,
      		desc: description,
      		link: link,
      		imgUrl: imageUrl,
      		
      		success: function (res) {
      		  YUY.log("MenuShareWeiboSuccess");
      		  if(shareSuccessCallback)
      		    shareSuccessCallback();
      		},
      		cancel: function (res) {
      		  YUY.log("MenuShareWeiboCancel");
      		},
      		fail:function(res){
      		  YUY.log("MenuShareWeiboFail");
      		}
	});	
	
	wx.onMenuShareQZone({
      		title: title,
      		desc: description,
      		link: link,
      		imgUrl: imageUrl,
      		
      		success: function (res) {
      		  YUY.log("MenuShareQZoneSuccess");
      		  if(shareSuccessCallback)
      		    shareSuccessCallback();
      		},
      		cancel: function (res) {
      		  YUY.log("MenuShareQZoneCancel");
      		},
      		fail:function(res){
      		  YUY.log("MenuShareQZoneFail");
      		}
    	});
  });
};



//一加载就默认配置分享。
YUY.configWeixinShare();

});
<?php
}

