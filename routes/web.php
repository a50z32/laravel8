<?php

use Illuminate\Support\Facades\Route;

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
Route::group( [ 'namespace' => 'App\Http\Controllers' , 'middleware' => ['access'] ] , function() {
    Route::post('login', 'AuthController@login');// 微信授权登陆
    Route::post('refresh', 'AuthController@refresh');// 刷新token
    Route::post('logout', 'AuthController@logout');// 退出登陆
    Route::post('openid', 'AuthController@openidLogin');// openid直接获取token，测试时使用
});
Route::get('home', 'HomeController@root')->name('root');
Route::post('home', 'HomeController@test')->name('test');
// 接口绑定
$api = app('Dingo\Api\Routing\Router');
$api->version('v1', function($api) {
    // throttle:api 限流中间件
    $api->group(['namespace'=>'App\Http\Controllers\Api\V1', 'middleware'=>['access','api.check']], function($api){
        // 学生端接口
        $api->group(['prefix' => 'student'], function($api){
//            $api->get('info', [ 'name'=>'获取学生端信息','as' => 'api.student.info',
//                'uses'=>'StudentController@getStudentInfo']);
//            $api->get('backclass/report', [ 'name'=>'获取学生端回课统计','as' => 'api.student.backclass.report',
//                'uses'=>'StudentController@getBackClassReport']);
            $api->get('center', [ 'name'=>'获取学生个人中心数据','as' => 'api.student.center',
                'uses'=>'StudentController@getStudentCenter']);
//            $api->get('bindteacher', [ 'name'=>'获取学生绑定的教师列表','as' => 'api.student.bindteacherlist',
//                'uses'=>'StudentController@getBindTeacher']);
//            $api->get('accountlog', [ 'name'=>'获取学生账户变更记录','as' => 'api.student.accountlog',
//                'uses'=>'StudentController@getStudentAccountLogs']);
//            $api->post('update', [ 'name'=>'更新学生信息','as' => 'api.student.update',
//                'uses'=>'StudentController@postUpdateStudent']);
        });
//        $api->group(['prefix' => 'student', 'middleware'=>['api.login'] ], function($api){
//            $api->post('bindteacher', [ 'name'=>'学生绑定教师','as' => 'api.student.bindteacher',
//                'uses'=>'StudentController@postBindTeacher']);
//        });
    });
});
