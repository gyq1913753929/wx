<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Model\Fans;
class TestController extends Controller
{

    public function test()
    {
        //调用
        $result = $this->CheckSignature();
        $echostr = request()->get("echostr","");
        if($result){

        }else{

        }
    }

    //处理推送事件
    public function CheckSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = config('weixin.Token');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){        //验证通过
            //1接收数据
            $xml_str = file_get_contents("php://input");
            //记录日志
            $obj = file_put_contents('wx_event.log',$xml_str);
            //回复
            switch($obj->MsgType){
                case "even":
                    //关注事件
                    if($obj->Event=="subscribe"){
                        $openid = $obj->FromUserName;
                        //获取token
                        $access_token=$this->getAccessToken();
                        //获取用户信息
                        $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN";
                        echo $url;
                        //转字符串
                        $fans=json_decode($url,true);
                        $fansModel = Fans::where('openid',$openid)->first();



                    }

            }
        }else{
           echo "";
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
            echo "无缓存";echo '<br>';
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC')."";

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

        echo "access_token:".$token;

    }

    //接收平台消息
    public function receiveMsg()
    {

        $data = file_get_contents("php://input");
        $xml_obj = simplexml_load_string($data);
        //echo '<pre>';print_r($xml_obj);echo '</pre>';

        echo $xml_obj->ToUserName;
    }

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


    public function cd()
    {
        $access_token = $this->getAccessToken();
        $url ='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        $menu = '{
            "button":[
            {
                 "type":"click",
                 "name":"今日歌曲",
                 "key":"V1001_TODAY_MUSIC"
             },
             {
                  "name":"菜单",
                  "sub_button":[
                  {
                      "type":"view",
                      "name":"搜索",
                      "url":"http://www.baidu.com/"
                   },
                   {
                      "type":"click",
                      "name":"赞一下我们",
                      "key":"V1001_GOOD"
                   }]
              }]
        }';

        $client = new Client();

        $response = $client->request('POST',$url,[
            'verify' =>false,
            'body'=>json_encode($menu)
        ]);


    }



    public function curl($url,$menu){
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



}
