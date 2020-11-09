<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
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

    //微信接入的接口
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
            $obj = file_put_contents('wx_event.log',$xml_str);
            echo "";
            die;
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
            echo "无缓存";
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC')."";
            $respone = file_get_contents($url);
            echo $respone;
            $data = json_decode($respone,true); //存到redis要转成字符串
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

        echo $xml_obj;
    }

    //回复消息
    private function responseText($xml_obj,$content){
        $toUserName=$xml_obj->FromUserName;
        $fromUserName=$xml_obj->ToUserName;
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



}
