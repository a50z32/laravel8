<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\V1\ApiV1Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Logic\UserLogic;
use App\Logic\StudentLogic;
use App\Logic\TeacherLogic;
use App\Services\WeixinService;

/**
 * 用户接口
 * @author Toby.Tu 2021-04-17
 */
class UserController extends ApiV1Controller
{
    /**
      * @OA\Post(
      *     path="/user/update",
      *     summary="更新会员信息",
      *     operationId="postUpdateUser",
      *     tags={"Auth"},
      *     @OA\Parameter(
      *         name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="nickname",in="query",required=true,description="用户昵称",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="avatar",in="query",required=true,description="用户头像",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="sex",in="query",required=true,description="性别，1男2女",@OA\Schema(type="integer")
      *     ),
      *     @OA\Parameter(
      *         name="country",in="query",description="国家",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="province",in="query",description="省市",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="city",in="query",description="区县",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="user_role",in="query",description="用户角色,1 学生、2 教师",@OA\Schema(type="integer")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer"),
      *             @OA\Property(property="msg", type="string"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="id", type="integer", description="会员id"),
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
    public function postUpdateUser(Request $request) {
        // $auth_user = $request->auth_user();// 获取授权信息
        $auth_user = $request->user();
        $user_id = $auth_user['id'] ?? 0;
        $input = $request->all();
        $validator = app('validator')->make($input, [
            'nickname' => 'required|string|max:255',
            'avatar' => 'required|string',
            // 'birthday' => 'required',
            'user_role' => 'integer|in:1,2',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数不正确.', $validator->errors());
        }
        $input['nickname'] = remove_emoji($input['nickname']);// 去除emoji表情
        // 首先更新用户信息
        $update = [
            'nickname'  =>  $input['nickname'],
            'avatar'    =>  $input['avatar'],
            'sex'       =>  $input['sex'] ?? 0,// 1 男 2 女
            'country'  =>  $input['country'] ?? '',
            'province'  =>  $input['province'] ?? '',
            'city'  =>  $input['city'] ?? '',
        ];
        if ($input['user_role'] ?? '') {
            $update['user_role'] = $input['user_role'];
        }
        $user_logic = new UserLogic();
        $student_logic = new StudentLogic();
        $teacher_logic = new TeacherLogic();
        $user_logic->updateWechatUser($user_id,$update);
        // 初始化学生或教师
        if ($update['user_role'] ?? '') {
            if ($update['user_role'] == 1) {
                $student_logic->createStudent($user_id);// 初始化学生信息    
            } else if ($update['user_role'] == 2) {
                $teacher_logic->createTeacher($user_id);// 初始化老师信息    
            }
        }
        return response()->success(['id'=> $user_id]);
    }
    /**
      * @OA\Post(
      *     path="/user/role",
      *     summary="更新用户角色信息",
      *     operationId="postChangeUserRole",
      *     tags={"Auth"},
      *     @OA\Parameter(
      *         name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="user_role",in="query",required=true,description="用户角色",@OA\Schema(type="integer")
      *     ),
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
      *         description="错误信息",
      *         @OA\JsonContent(ref="#/components/schemas/ApiErrorRespones"),
      *     )
      * )
      */
    public function postChangeUserRole(Request $request) {
        // $auth_user = $request->auth_user();// 获取授权信息
        $auth_user = $request->user();
        $user_id = $auth_user['id'] ?? 0;
        $input = $request->all();
        $validator = app('validator')->make($input, [
            'user_role' => 'required|integer|min:1|max:2',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数不正确.', $validator->errors());
        }
        $user_logic = new UserLogic();
        $user_logic->changeUserRole($user_id, $input['user_role']);
        // 初始化学生或教师
        $student_logic = new StudentLogic();
        $teacher_logic = new TeacherLogic();
        if ($input['user_role'] == 1) {
            $student_logic->createStudent($user_id);// 初始化学生信息    
        } else if ($input['user_role'] == 2) {
            $teacher_logic->createTeacher($user_id);// 初始化老师信息    
        }
        return response()->success();
    }
    /**
      * @OA\Post(
      *     path="/user/mobile",
      *     summary="更新会员手机号",
      *     operationId="postUpdateMobile",
      *     tags={"Auth"},
      *     @OA\Parameter(
      *         name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="mobile",in="query",required=true,description="用户昵称",@OA\Schema(type="string")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer"),
      *             @OA\Property(property="msg", type="string"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="id", type="integer", description="会员id"),
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
    public function postUpdateMobile(Request $request) {
        // $auth_user = $request->auth_user();// 获取授权信息
        $auth_user = $request->user();
        $user_id = $auth_user['id'] ?? 0;
        $input = $request->all();
        $validator = app('validator')->make($input, [
            'mobile' => 'required|string',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数不正确.', $validator->errors());
        }
        // 首先更新用户信息
        $update = [
            'mobile'  =>  $input['mobile']
        ];
        $user_logic = new UserLogic();
        $user_logic->updateWechatUser($user_id, $update);
        return response()->success(['id'=> $user_id]);
    }

    /**
      * @OA\Post(
      *     path="/user/sign",
      *     summary="用户签到",
      *     operationId="postUserSign",
      *     tags={"Auth"},
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
      *                     @OA\Property(property="sign_id", type="integer", description="签到id"),
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
    public function postUserSign(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        // $auth_user = $request->user();
        $user_id = $auth_user['user_id'] ?? 0;
        $user_role = $auth_user['user_role'] ?? 0;// 用户角色
        if ( !$user_id ) {
            throw new \Exception('参数不正确.');
        }
        $params = [
            'user_id' => $user_id,
        ];
        if ( UserLogic::checkRole($user_role, 'student') ) {// 学生端
            $student_logic = new StudentLogic();
            $student = $student_logic->getStudentInfo($user_id);
            if (!$student) {
                throw new \Exception('未找到学生信息');
            }
            $params['student_id'] = $student->id;
        } else {
            $teacher_logic = new TeacherLogic();
            $teacher = $teacher_logic->getTeacherInfo($user_id);
            if (!$teacher) {
                throw new \Exception('未找到学生信息');
            }
            $params['teacher_id'] = $teacher->id;
        }
        $user_logic = new UserLogic();
        $today = lib()->day();
        $params['sign_day'] = $today;
        // 判断今天是否签到过
        $sign = $user_logic->getUserSignByDay($params);
        if ($sign ?? '') {
            throw new \Exception('今天已经签到过');
        }
        // 获取昨天的签到记录
        $params['sign_day'] = phpdate()->day(-1)->get();
        $sign = $user_logic->getUserSignByDay($params);
        if ($sign ?? '') {
            $params['sign_count'] = $sign['sign_count'] + 1;
        } else {
            $params['sign_count'] = 1;
        }
        $params['sign_day'] = $today;
        $sign_id = $user_logic->addUserSign($params);
        return response()->success(['sign_id'=> $sign_id]);
    }
}
