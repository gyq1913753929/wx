<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
class TestController extends Controller
{
    public function test()
    {
        echo __METHOD__;
        $aa = DB::table('user')->limit(4)->get();
        dd($aa);
    }
}
