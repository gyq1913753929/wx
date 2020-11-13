<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Model\Fans;
use App\Model\Messa;
class TestController extends Controller
{

    public function test()
    {
        $signature = request()->get("signature");
        $timestamp = request()->get("timestamp");
        $nonce = request()->get("nonce");

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {        //验证通过
            //1接收数据
            $xml_str = file_get_contents("php://input");
            //记录日志
            file_put_contents('wx_event.log', $xml_str);
            $obj = simplexml_load_string($xml_str, "SimpleXMLElement", LIBXML_NOCDATA);

            //回复
            //关注事件
            if ($obj->MsgType == "event") {
                if ($obj->Event == "subscribe") {
                    //获取token
                    $access_token = $this->getAccessToken();
                    $openid = $obj->FromUserName;
                    //获取用户信息
                    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
                    $user = file_get_contents($url);
                    $res = json_decode($user, true);
                    if (isset($res['errcode'])) {
                        file_put_contents('wx_event.log', $res['errcode']);
                    } else {
                        $user_id = Fans::where('openid', $openid)->first();
                        if ($user_id) {
                            $user_id->subscribe = 1;
                            $user_id->save();
                            $content = "感谢再次关注";
                        } else {
                            $res = [
                                'subscribe' => $res['subscribe'],
                                'openid' => $res['openid'],
                                'nickname' => $res['nickname'],
                                'sex' => $res['sex'],
                                'city' => $res['city'],
                                'country' => $res['country'],
                                'province' => $res['province'],
                                'language' => $res['language'],
                                'headimgurl' => $res['headimgurl'],
                                'subscribe_time' => $res['subscribe_time'],
                                'subscribe_scene' => $res['subscribe_scene']
                            ];
                            Fans::insert($res);
                            $content = "欢迎老铁关注";
                        }
                    }
                }
                echo $this->responseText($obj, $content);
            }

            if($obj->MsgType == "text") {
                $city = urlencode(str_replace("天气:", "", $obj->Content));
                $key = "e2ca2bb61958e6478028e72b8a7a8b60";
                $url = "http://apis.juhe.cn/simpleWeather/query?city=" . $city . "&key=" . $key;
                $tianqi = file_get_contents($url);
                //file_put_contents('tianqi.txt',$tianqi);
                $res = json_decode($tianqi, true);
                $content = "";
                if ($res['error_code'] == 0) {
                    $today = $res['result']['realtime'];
                    $content .= "查询天气的城市:" . $res['result']['city'] . "\n";
                    $content .= "天气详细情况" . $today['info'] . "\n";
                    $content .= "温度" . $today['temperature'] . "\n";
                    $content .= "湿度" . $today['humidity'] . "\n";
                    $content .= "风向" . $today['direct'] . "\n";
                    $content .= "风力" . $today['power'] . "\n";
                    $content .= "空气质量指数" . $today['aqi'] . "\n";

                    //获取一个星期的天气
                    $future = $res['result']['future'];
                    foreach ($future as $k => $v) {
                        $content .= "日期:" . date("Y-m-d", strtotime($v['date'])) . $v['temperature'] . ",";
                        $content .= "天气:" . $v['weather'] . "\n";
                    }
                } else {
                    $content = "你查寻的天气失败，请输入正确的格式:天气、城市";
                }
                //file_put_contents("tianqi.txt",$content);

                echo $this->responseText($obj, $content);

            }else
            if($obj->MsgType=="image"){
                $res = Messa::where("media_id",$obj->MediaId)->first();
                $access_token = $this->getAccessToken();
                if(empty($res)){
                    $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$access_token."&media_id=".$obj->MediaId;
                    $url=file_get_contents($url);
                    $data=[
                        "time"=>time(),
                        'msg_type'=>$obj->MsgType,
                        'openid'=>$obj->FromUserName,
                        "msg_id"=>$obj->MsgId
                    ];
                    //图片
                    if($obj->MsgType=="image"){
                        $file_type='.jpg';
                        $data["url"] = $obj->PicUrl;
                        $data["media_id"]=$obj->MediaId;
                    }
                    //视频
                    if($obj->MsgType=="video"){
                        $file_type='.mp4';
                        $data["media_id"]=$obj->MediaId;
                    }
                    //文本
                    if($obj->MsgType=="text"){
                        $file_type='.txt';
                        $data["content"]=$obj->Content;
                    }
                    //音频
                    if($obj->MsgType=="voice"){
                        $file_type=".amr";
                        $data["media_id"]=$obj->MediaId;
                    }
                    if(!empty($file_type)){
                        file_put_contents("dwaw".$file_type,$url);
                    }
                    Messa::insert($data);

                }else{
                    return $res;
                }
                return true;
            }
            //签名
            if($obj->MsgType=="click"){

            }


        }




    }







    //获取accrss_token
    public function getAccessToken()
    {
        $key = 'wx:access_token';       //$key = 建
        //检查是否有token
        $token = Redis::get($key);
        //判断$token
        if($token)
        {
            echo "有缓存";echo '<br>';
        }else{
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');

            $client = new Client();         //实列化 客户端
            $response = $client->request('GET',$url,['verify'=>false]);   //发起请求并接收响应
            $json_str = $response->getBody();       //服务器的响应数据

            $data = json_decode($json_str,true); //存到redis要转成字符串
            $token = $data['access_token'];

            //保存到redis 时间为3600
            $key = 'wx:access_token';       //$key = 建
            Redis::set($key,$token);
            Redis::expire($key,3600);       //过期时间自动删除
        }

        return $token;

    }

    //接收平台消息
    public function receiveMsg()
    {

        $data = file_get_contents("php://input");
        $xml_obj = simplexml_load_string($data);
        //echo '<pre>';print_r($xml_obj);echo '</pre>';

        echo $xml_obj->ToUserName;
    }
    //测试GET
    public function guzzle1()
    {
        echo __METHOD__;
        $url ="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC')."";
        echo $url;

        //使用guzzle请求
        $client = new Client();         //实列化 客户端
        $response = $client->request('GET',$url,['verify'=>false]);   //发起请求并接收响应
        $json_str = $response->getBody();       //服务器的响应数据
        echo $json_str;
    }
    //测试POST
    public function guzzle2()
    {
        $access_token = $this->getAccessToken();
        $type = 'image';
        $url ='https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;

        //使用guzzle发起git请求
        $client = new Client();
        $response = $client->request('POST',$url,[
            'verify' => false,
            'multipart'=>[
                [
                    'name' => 'media',
                    'contents'=> fopen('1.jpg','r')
                ],
            ]
        ]);    //发起请求并接收响应

          $data = $response->getBody();
        echo $data;
    }


    //回复给微信公众平台一个文本类型
    private function responseText($obj,$content){
        $toUserName=$obj->FromUserName;
        $fromUserName=$obj->ToUserName;
        $time=time();
        $msgType="text";
        $xml="<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
        echo sprintf($xml,$toUserName,$fromUserName,$time,$msgType,$content);
    }

    //回复给微信公众平台一个图类型
    private function responseimg($obj,$media_id){
        $toUserName=$obj->FromUserName;
        $fromUserName=$obj->ToUserName;
        $time=time();
        $msgType="image";
        $xml="<xml>
                  <ToUserName><![CDATA[%s]]></ToUserName>
                  <FromUserName><![CDATA[%s]]></FromUserName>
                  <CreateTime>%s</CreateTime>
                  <MsgType><![CDATA[%s]]></MsgType>
                  <Image>
                    <MediaId><![CDATA[%s]]></MediaId>
                  </Image>
               </xml>";
        echo sprintf($xml,$toUserName,$fromUserName,$time,$msgType,$media_id);
    }





    //自定义菜单
    public function cd()
    {
        $access_token = $this->getAccessToken();
        $url ="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $menu = [
            "button"=>[
              [
                  "name"=>"游戏",
                  "sub_button"=>[
                [
                    "type"=>"view",
                    "name"=>"baidu",
                    "url" =>"http://www.baidu.com",
                ],
                  [
                      "type"=>"click",
                      "name"=>"签到",
                      "key" =>"aaaa",
                  ]
                      ]
              ],

                [
                    "type"=>"click",
                    "name"=>"天气预报",
                    "key"=>"",
                ],

                [
                    "name"=>"发图",
                    "sub_button"=>[
                        [
                            "type"=>"pic_sysphoto",
                            "name"=>"拍照图片",
                            "key"=>"rselfmenu_1",
                            "sub_button"=>[ ]
                        ],

                        [
                            "type"=>"pic_photo_or_album",
                            "name"=>"拍照或相册图片",
                            "key"=>"rselfmenu_2",
                            "sub_button"=>[ ]
                        ],

                        [
                            "type"=>"pic_weixin",
                            "name"=>"微信相册图片",
                            "key"=>"rselfmenu_3",
                            "sub_button"=>[ ]
                        ],

                    ]

                ]

            ]
        ];

        $client = new Client();

        $response = $client->request('POST',$url,[
            'verify' =>false,
            'body'=>json_encode($menu,JSON_UNESCAPED_UNICODE)
        ]);
        $data = $response->getBody();
        echo $data;

    }




    //素材
    public function typeContent($obj)
    {
        $res = Messa::where("media_id",$obj->MediaId)->first();
        $access_token = $this->getAccessToken();
        if(empty($res)){
            $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$access_token."&media_id=".$obj->MediaId;
           $url=file_get_contents($url);
            $data=[
                "time"=>time(),
                'msg_type'=>$obj->MsgType,
                'openid'=>$obj->FromUserName,
                "msg_id"=>$obj->MsgId
            ];
            //图片
            if($obj->MsgType=="image"){
                $file_type='.jpg';
                $data["url"] = $obj->PicUrl;
                $data["media_id"]=$obj->MediaId;
            }
            //视频
            if($obj->MsgType=="video"){
                $file_type='.mp4';
                $data["media_id"]=$obj->MediaId;
            }
            //文本
            if($obj->MsgType=="text"){
                $file_type='.txt';
                $data["content"]=$obj->Content;
            }
            //音频
            if($obj->MsgType=="voice"){
                $file_type=".amr";
                $data["media_id"]=$obj->MediaId;
            }
            if(!empty($file_type)){
                file_put_contents("dwaw".$file_type,$url);
            }
            Messa::insert($data);

        }else{
            return $res;
        }
        return true;

    }







    //post类型
    public function http_post($url,$menu){
        //1.初始化
        $ch = curl_init();
        //2.设置
        curl_setopt($ch,CURLOPT_URL,$url);//设置提交地址
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);//设置返回值返回字符串
        curl_setopt($ch,CURLOPT_POST,1);//post提交方式
        curl_setopt($ch,CURLOPT_POSTFIELDS,$menu);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        //3.执行
        $output = curl_exec($ch);
        //关闭
        curl_close($ch);
        return $output;
    }

    //Get类型
    public function http_get($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);//向那个url地址上面发送
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);//设置发送http请求时需不需要证书
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置发送成功后要不要输出1 不输出，0输出
        $output = curl_exec($ch);//执行
        curl_close($ch);    //关闭
        return $output;
    }




    public function cdtui($obj,$content)
    {
        $toUserName=$obj->FromUserName;
        $fromUserName=$obj->ToUserName;
        $time=time();
        $msgType="text";
        $xml = "<xml>
                     <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime></CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Event><![CDATA[%s]]></Event>
                    <EventKey><![CDATA[%s]]></EventKey>
                    </xml>";

    }





}
