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
Route::any('/test','TestController@test');          //接入
Route::any('/token','TestController@getAccessToken');   //获取token



Route::post('/wx','TestController@CheckSignature');         //推送事件