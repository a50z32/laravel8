<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\V1\ApiV1Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Logic\UserLogic;
use App\Logic\StudentLogic;
use App\Logic\TeacherLogic;
use App\Logic\AccountLogic;
use App\Logic\TradeLogic;
use App\Logic\GoodLogic;
use App\Services\PayService;
use Carbon\Carbon;

/**
 * 支付订单
 * @author Toby.Tu 2021-07-24
 */
class TradeController extends ApiV1Controller
{
    private $logic;
    public function __construct() {
        $this->logic = new TradeLogic();
    }

    /**
      * @OA\Get(
      *     path="/trade/goodlist",
      *     summary="获取支付列表",
      *     operationId="getGoodList",
      *     tags={"Teade"},
      *     @OA\Parameter(
      *         name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer"),
      *             @OA\Property(property="msg", type="string"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="good_id", type="integer", default="支付id，支付时传递id过来即可"),
      *                     @OA\Property(property="good_name", type="string", default="支付title"),
      *                     @OA\Property(property="cost_price", type="string", default="支付原价(划线金额)"),
      *                     @OA\Property(property="price", type="string", default="支付展示金额"),
      *                     @OA\Property(property="desc", type="string", default="备注"),
      *                 ),
      *             ),
      *         ),
      *     ),
      *     @OA\Response(
      *         response=280,
      *         description="错误信息",
      *         @OA\JsonContent(ref="#/components/schemas/ApiErrorRespones"),
      *     )
      * )
      */
    public function getGoodList(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_role = $auth_user['user_role'] ?? 0;// 用户角色
        if ( UserLogic::checkRole($user_role, 'teacher') ) {
            throw new \Exception('教师目前没有支付场景');
        }
        $good_logic = new GoodLogic();
        $good_lists = $good_logic->getGoodList();
        if ( !$good_lists ) {
            throw new \Exception('暂时不可支付，请稍后再试。');
        }
        return response()->success($good_lists);
    }
    /**
      * @OA\Post(
      *     path="/trade/create",
      *     summary="创建支付单",
      *     operationId="postCreateTrade",
      *     tags={"Teade"},
      *     @OA\Parameter(
      *         name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="pay_fee",in="query",required=true,description="支付金额",@OA\Schema(type="string")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer"),
      *             @OA\Property(property="msg", type="string"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="appId", type="string", default="小程序appid"),
      *                     @OA\Property(property="timeStamp", type="integer", default="时间戳"),
      *                     @OA\Property(property="nonceStr", type="string", default="随机数"),
      *                     @OA\Property(property="package", type="string", default="预付款id"),
      *                     @OA\Property(property="signType", type="string", default="加密方式"),
      *                     @OA\Property(property="paySign", type="string", default="签名"),
      *                 ),
      *             ),
      *         ),
      *     ),
      *     @OA\Response(
      *         response=280,
      *         description="错误信息",
      *         @OA\JsonContent(ref="#/components/schemas/ApiErrorRespones"),
      *     )
      * )
      */
    public function postCreateTrade(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $user_role = $auth_user['user_role'] ?? 0;// 用户角色
        $input = $request->all();
        $validator = app('validator')->make($input, [
            'good_id' => 'required|numeric|min:0|not_in:0|max:10',
        ], [
            'good_id.required' => '请传递需要支付的金额id',
            'good_id.numeric' => '支付金额id必须是数字',
            'good_id.min' => '支付金额id必须是数字',
            'good_id.not_in' => '支付金额id必须是正整数',
            'good_id.max' => '支付金额id参数不正确',
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            throw new \Exception($error);
        }
        $student_logic = new StudentLogic();
        $account_logic = new AccountLogic();
        $good_logic = new GoodLogic();
        if ( UserLogic::checkRole($user_role, 'teacher') ) {// 学生端统计
            throw new \Exception('教师目前没有支付场景');
        }
        // 获取学生信息
        $student = $student_logic->getStudentInfo($user_id);
        if (!$student) {
            throw new \Exception('未找到学生信息');
        }
        $student_account = $account_logic->getStudentAccount($user_id, $student->id);
        if ( !$student_account ) {
            throw new \Exception('未找到学生账户信息。');
        }
        $good = $good_logic->getGoodById($input['good_id']);
        if ( !$good ) {
            throw new \Exception('未找到支付信息。');
        }
        if ( $good['status'] > 0 ) {
            throw new \Exception('未找到支付金额。');
        }
        // 加锁，防止重复提交
        $redis_key = 'trade:student:'. $student->id . ':user:'. $user_id;
        $pay_lock_time = redis()->lock($redis_key, 5);// 锁住10秒钟，10秒内不允许重复提交
        if ( !$pay_lock_time ) {
            throw new \Exception('您有未完成的支付单，5秒内无法再次支付。');
        }
        $params = [
            'pay_fee' => $good['pay_fee'],
            'openid'  => $auth_user['openid'],
            'trade_id' => $this->logic->getTradeId($user_id)
        ];
        list($code, $payjson) = PayService::doPay($params);
        if ($code > 0) {
            throw new \Exception('获取支付配置失败');
        }
        // $payjson['prepay_id'] = 'wx11161817647151452b637cf35aa0320000';
        $tradeData = [
            'trade_id' => $params['trade_id'],
            'user_id' => $user_id,
            'open_id' => $auth_user['openid'],
            'student_id' => $student->id,
            'account_id' => $student_account->id,
            'good_id' => $good['good_id'],
            'pay_fee' => $params['pay_fee'],
            'recharge_num' => $good['recharge_num'],
            'pay_time' => Carbon::now()->toDateTimeString(),
            'prepay_id' => $payjson['prepay_id'],
            'description' => '学生充值',
            'pay_type' => $payjson['pay_type'] ?? 1,// 1 微信支付
        ];
        $this->logic->createData($tradeData);
        unset($payjson['prepay_id']);
        unset($payjson['pay_type']);
        return response()->success($payjson);
    }
}
