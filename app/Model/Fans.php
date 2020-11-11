<?php


namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Fans extends Model
{
    protected $table = 'fans';//表名
    protected $primaryKey = "id";//主键
    public $timestamps = false;//没有create_at 和update_at 这个字段
    protected $fillable = ["nickname", "sex", "country", "province", "city", "headimgurl", "subscribe_time", "openid"];
}