<?php
namespace App\Http\Controllers\Api\V1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Http\Controllers\Api\V1\ApiV1Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Response;

class HomeController extends ApiV1Controller
{
    public function getIndex(UserRequest $request) {
        // echo '<pre>';
        // print_r($this->auth_user);
        // echo '</pre>';
        // echo '<pre>';
        // print_r($this->input);
        // echo '</pre>';
        // throw new ResourceException('参数不正确');
        return response()->success(['id'=> 1]);
    }
}
