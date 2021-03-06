<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


//微信
          //接入
Route::any('/token','TestController@getAccessToken');   //获取token



Route::any('/test','TestController@test');         //推送事件

Route::post('/receiveMsg','TestController@receiveMsg');         //微信接收消息


//TEST 路由分组
Route::get('/guzzle1',"TestController@guzzle1");
Route::get('/guzzle2',"TestController@guzzle2");
Route::any('/cd',"TestController@cd");                  //菜单
Route::any('/typeContent',"TestController@typeContent");


Route::any('/eee',"TestController@eee");

Route::any('/login',"TestController@login");


Route::any('/detail',"TestController@detail");


Route::any('/detailww',"TestController@detailww");
Route::any('/addfav',"TestController@addfav");
Route::any('/cartadd',"TestController@cartadd")->middleware('check.token');
Route::any('/cartaa',"TestController@cartaa");

