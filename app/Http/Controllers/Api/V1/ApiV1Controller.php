<?php
namespace App\Http\Controllers\Api\V1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="易回课",
 *      description="易回课-首页接口文档"
 * )
 * @OA\Schemes(format="http")
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      in="header",
 *      name="Authorization",
 *      type="http",
 *      scheme="Bearer",
 *      bearerFormat="JWT",
 * ),
 */
class ApiV1Controller extends BaseController
{
    public $page_size = 5;// 分页
    public function __construct(Request $request) {
        $this->filterInput($request);// 过滤输入
    }
    /**
     * 过滤传参
     * @author Toby.Tu 2021-04-05
     */
    private function filterInput(Request $request) {
        $input = $request->all();
        if (!empty($input)) {
            // 过滤输入，防止sql注入和rss攻击
            $input = lib()->request_filter($input);
            // 过滤表情符
            $input = lib()->request_rmEmoji($input);
        }
        $input['now'] = phpdate()->time();
        $request->replace($input);
    }
}
