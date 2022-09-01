<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Dingo\Api\Contract\Http\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Support\Facades\Auth;
use App\Logic\AuthLogic;
use App\Services\AuthService;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     * 要求附带mobile和password（数据来源users表）
     * @return void
     */
    public function __construct()
    {
        // 这里额外注意了：官方文档样例中只除外了『login』
        // 这样的结果是，token 只能在有效期以内进行刷新，过期无法刷新
        // 如果把 refresh 也放进去，token 即使过期但仍在刷新期以内也可刷新
        // 不过刷新一次作废
        $this->middleware('auth:api', [
            'except' => [
                'login',
                'refresh',
                'logout',
                'openidLogin',
            ]
        ]);
        // 另外关于上面的中间件，官方文档写的是『auth:api』
        // 但是我推荐用 『jwt.auth』，效果是一样的，但是有更加丰富的报错信息返回
    }

    /**
      * @OA\Post(
      *     path="/login",
      *     summary="微信授权登陆",
      *     operationId="postWeappLogin",
      *     tags={"Auth"},
      *     @OA\Parameter(
      *         name="code",in="query",required=true,description="小程序授权code",@OA\Schema(type="string")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="token", type="string", default="授权token"),
      *                     @OA\Property(property="openid", type="string", default="会员openid"),
      *                     @OA\Property(property="user_role", type="string", default="会员默认角色，1学生、2教师，0未选择"),
      *                     @OA\Property(property="expires_in", type="string", default="jwt授权密钥过期时间"),
      *                 ),
      *             ),
      *         ),
      *     ),
      *     @OA\Response(
      *         response=280,
      *         description="错误",
      *         @OA\JsonContent(ref="#/components/schemas/ApiErrorRespones"),
      *     )
      * )
      */
    public function login(Request $request)
    {
        echo 2;die;
        $rules = [
            'code' => ['required'],
        ];
        $input = $request->only('code');
        $validator = app('validator')->make($input, $rules);
        // 验证格式
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            throw new \Exception($errors, 220);//220 重试
        }
        $auth_logic = new AuthLogic();
        // 获取openid
        $openid = $auth_logic->getOpenIdByJsCode($input['code']);
        if (!$openid) {
            throw new \Exception('未获取到微信用户信息，请刷新页面重试。', 220);//220 重试
        }
        // 判断openid是否注册过
        $wechatuser = $auth_logic->checkWechatByOpenId($openid);
        if (!($wechatuser['id'] ?? '')) {
            $user = [
                'openid' => $openid,
            ];
            $wechatuser = $auth_logic->createUserByOpenId($user);
        }
        if ($token = AuthService::getToken($wechatuser)) {
            return response()->success(
                AuthService::respondWithToken($token, $wechatuser)
            );
        }
    }
    /**
      * @OA\Post(
      *     path="/openid",
      *     summary="openid直接获取tokne，测试时使用",
      *     operationId="openidLogin",
      *     tags={"Auth"},
      *     @OA\Parameter(
      *         name="openid",in="query",required=true,description="用户openid",@OA\Schema(type="string")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="token", type="string", default="授权token"),
      *                     @OA\Property(property="openid", type="string", default="会员openid"),
      *                     @OA\Property(property="user_role", type="string", default="会员默认角色，1学生、2教师，0未选择"),
      *                     @OA\Property(property="expires_in", type="string", default="jwt授权密钥过期时间"),
      *                 ),
      *             ),
      *         ),
      *     ),
      *     @OA\Response(
      *         response=280,
      *         description="错误",
      *         @OA\JsonContent(ref="#/components/schemas/ApiErrorRespones"),
      *     )
      * )
      */
    public function openidLogin(Request $request) {
        $rules = [
            'openid' => ['required'],
        ];
        $input = $request->only('openid');
        $validator = app('validator')->make($input, $rules);
        // 验证格式
        if ($validator->fails()) {
            throw new ResourceException('参数不正确.', $validator->errors());
        }
        $auth_logic = new AuthLogic();
        // 判断openid是否注册过
        $wechatuser = $auth_logic->checkWechatByOpenId($input['openid']);
        if (!($wechatuser['id'] ?? '')) {
            $user = [
                'openid' => $input['openid'],
            ];
            $wechatuser = $auth_logic->createUserByOpenId($user);
        }
        if ($token = AuthService::getToken($wechatuser)) {
            return response()->success(
                AuthService::respondWithToken($token, $wechatuser)
            );
        }
    }
    /**
     * Get the authenticated User
     * @return \Dingo\Api\Http\Response
     */
    public function me()
    {
        return response()->success(
                    AuthService::getAuthUser()
                );
    }

    /**
      * @OA\Post(
      *     path="/logout",
      *     summary="退出登陆",
      *     operationId="logout",
      *     tags={"Auth"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="string", default=""),
      *         ),
      *     ),
      *     @OA\Response(
      *         response=280,
      *         description="错误",
      *         @OA\JsonContent(ref="#/components/schemas/ApiErrorRespones"),
      *     )
      * )
      */
    public function logout()
    {
        AuthService::logout();

        return response()->success();
    }

    /**
      * @OA\Post(
      *     path="/refresh",
      *     summary="刷新token",
      *     operationId="refresh",
      *     tags={"Auth"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="token", type="string", default="刷新后的token"),
      *                     @OA\Property(property="openid", type="string", default="会员openid"),
      *                     @OA\Property(property="user_role", type="string", default="会员默认角色，1学生、2教师，0未选择"),
      *                     @OA\Property(property="expires_in", type="string", default="jwt授权密钥过期时间"),
      *                 ),
      *             ),
      *         ),
      *     ),
      *     @OA\Response(
      *         response=280,
      *         description="错误",
      *         @OA\JsonContent(ref="#/components/schemas/ApiErrorRespones"),
      *     )
      * )
      */
    public function refresh()
    {
        $token = AuthService::refresh();
        return response()->success(
                    AuthService::respondWithToken($token)
                );
    }
}
