<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function root()
    {
        header('content-type:application:json;charset=utf8');

        header('Access-Control-Allow-Origin:*');

        header('Access-Control-Allow-Methods:POST');

        header('Access-Control-Allow-Headers:x-requested-with,content-type');

        echo 222;die;
//        return view('welcome');
    }
    public function test()
    {
//        header('content-type:application:json;charset=utf8');
//
//        header('Access-Control-Allow-Origin:*');
//
////        header('Access-Control-Allow-Methods:POST');
//
//        header('Access-Control-Allow-Headers:x-requested-with,content-type');
       echo 111;die;
    }
}
