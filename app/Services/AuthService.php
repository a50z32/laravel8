<?php
namespace App\Services;
use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;
/**
 * 验证服务层
 * @author Toby.Tu 2021-04-24
 */
class AuthService extends BaseService {
    // 查看 config/auth.php 的 guards 设置项，我 auth 中配置的 driver->jwt 为 api
    protected static $guard = 'api';

    public static $auth_user = [
        'user_id' => 0,
        'unionid' => '',
        'openid' => '',
        'mobile' => '',
        'nickname' => '',
        'sex' => 0,
        'birthday' => '',
        'user_role' => 0,
    ];

    /**
     * Get the authenticated User
     * @return \Dingo\Api\Http\Response
     */
    public static function user()
    {
        return self::guard()->user();
    }

    /**
     * Log the user out (Invalidate the token)
     * @return JsonResponse
     */
    public static function logout() {
        self::guard()->logout();
        return true;
    }

    /**
     * Refresh a token.
     * @return JsonResponse
     */
    public static function refresh() {
        return self::guard()->refresh();
    }
    /**
     * 验证token
     * @author Toby.Tu 2021-04-24
     */
    public static function checkToken($auth) {
        if ( $auth->parseToken() ) {
            return true;
        }
        return false;
    }
    /**
     * 获取userid
     * @author Toby.Tu 2021-04-24
     */
    public static function getUserId($auth) {
        $jwt_token = $auth->manager()->getPayloadFactory()
                            ->buildClaimsCollection()->toPlainArray();
        $user_id = $jwt_token['sub'] ?? 0;
        return $user_id;
    }
    /**
     * 使用一次性登录以保证此次请求的成功
     * @author Toby.Tu 2021-04-24
     */
    public static function onceUsingId($auth) {
        $user_id = self::getUserId($auth);
        self::guard()->onceUsingId($user_id);
        return true;
    }
    /**
     * 获取授权用户信息
     * @author Toby.Tu 2021-04-24
     */
    public static function getAuthUser($user=[]) {
        if (!$user) $user = self::user();
        if ($user ?? '') {
            self::$auth_user = [
                'user_id'   =>  $user->id,
                'unionid'   =>  $user->unionid,
                'openid'    =>  $user->openid,
                'mobile'    =>  $user->mobile,
                'nickname'  =>  $user->nickname,
                'sex'       =>  $user->sex,
                'birthday'  =>  $user->birthday,
                'user_role' =>  $user->user_role,
            ];
        }
        return self::$auth_user;
    }
    /**
     * 生成token
     * @author Toby.Tu 2021-04-24
     */
    public static function getToken($user=[]) {
        return self::guard()->fromUser($user);
    }
    /**
     * Get the token array structure.
     * @param string $token
     * @return JsonResponse
     */
    public static function respondWithToken($token, $user=[]) {
        return [
            'token' => $token,
            'user_role' => $user->user_role ?? '',
            'openid' => $user->openid ?? '',
            'expires_in' => self::guard()->factory()->getTTL() * 60,
        ];
    }
    /**
     * Get the guard to be used during authentication.
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public static function guard() {
        return Auth::guard(self::$guard);
    }

    ///////////////  判断 ↓↓   ///////////////
    /**
     * 是否验证sign
     * @author Toby.Tu 2021-04-24
     */
    public static function isCheckSign() {
        $is_check_sign = config('dev.is_check_sign');
        if ($is_check_sign != 1) {// 未开启签名判断
            return false;
        }
        return true;
    }
    /**
     * 是否验证签名过期
     * @author Toby.Tu 2021-04-24
     */
    public static function isCheckTime() {
        $is_check_time = config('dev.is_check_time');
        if ($is_check_time != 1) {// 未开启签名判断
            return false;
        }
        return true;
    }
    /**
     * 是否验证token
     * @author Toby.Tu 2021-04-24
     */
    public static function isCheckToken() {
        $is_check_token = config('dev.is_check_token');
        if ($is_check_token != 1) {// 未开启签名判断
            return false;
        }
        return true;
    }
    /**
     * 是否格式化返回信息
     * @author Toby.Tu 2021-04-24
     */
    public static function isException() {
        $is_exception = config('dev.is_exception');
        if ($is_exception != 1) {// 未开启错误收集
            return false;
        }
        return true;
    }
    /**
     * 是否验证角色
     * @author Toby.Tu 2021-04-24
     */
    public static function isCheckRole() {
        $is_check_role = config('dev.is_check_role');
        if ($is_check_role != 1) {// 未开启错误收集
            return false;
        }
        return true;
    }
    /**
     * 是否需要重新登陆微信
     * @author Toby.Tu 2021-06-06
     */
    public static function isWxLogin($request) {
        $user = $request->user();
        if ( !$user || !$user['id'] || !$user['nickname'] ) {
            return false;
        }
        return true;
    }
}
