<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\V1\ApiV1Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Logic\UserLogic;
use App\Logic\StudentLogic;
use App\Logic\TeacherLogic;
use App\Logic\AccountLogic;
use App\Logic\BackclassLogic;
use App\Logic\AttachmentLogic;

/**
 * 教师端接口
 * @author Toby.Tu 2021-04-27
 */
class TeacherController extends ApiV1Controller
{
    private $logic;
    public function __construct() {
        $this->logic = new TeacherLogic();
    }
    
    /**
      * @OA\Get(
      *     path="/teacher/info",
      *     summary="获取教师信息",
      *     operationId="getTeacherInfo",
      *     tags={"Teacher"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="teacher_id",in="query",description="教师ID，非必填",@OA\Schema(type="integer") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="teacher_name", type="string", default="教师姓名"),
      *                     @OA\Property(property="avatar", type="string", default="教师头像"),
      *                     @OA\Property(property="course_name", type="string", default="乐器"),
      *                     @OA\Property(property="work_year", type="string", default="琴龄"),
      *                     @OA\Property(property="desc", type="string", default="教师简介"),
      *                     @OA\Property(property="twocode", type="string", default="教师二维码"),
      *                     @OA\Property(property="attachment", type="array",
      *                         @OA\Items(type="object",
      *                             @OA\Property( property="id", type="integer", default="教师资质ID"),
      *                             @OA\Property( property="attachment_url", type="string", default="资质图片地址"),
      *                         )
      *                     ),
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
    public function getTeacherInfo(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $teacher_id = $request->input('teacher_id', 0);
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        if ($teacher_id > 0) {
            $teacher = $this->logic->getTeacherById($teacher_id);
        } else {
            $teacher = $this->logic->getTeacherInfo($user_id);
        }
        if (!$teacher) {
            throw new \Exception('未找到教师');
        }
        $rest = $this->logic->getTeacherDetail($teacher, true);
        return response()->success($rest);
    }

    /**
      * @OA\Get(
      *     path="/teacher/list",
      *     summary="获取教师列表",
      *     operationId="getTeacherList",
      *     tags={"Teacher"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="teacher_id",in="query",description="教师ID，非必填",@OA\Schema(type="integer") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="teacher_id", type="string", default="教师ID"),
      *                     @OA\Property(property="teacher_name", type="string", default="教师姓名"),
      *                     @OA\Property(property="avatar", type="string", default="教师头像"),
      *                     @OA\Property(property="course_name", type="string", default="课程"),
      *                     @OA\Property(property="course_icon", type="string", default="图标"),
      *                     @OA\Property(property="work_year", type="string", default="琴龄"),
      *                     @OA\Property(property="course_fee", type="string", default="收费"),
      *                     @OA\Property( property="paginate", type="object",
      *                         @OA\Property( property="page_size", type="integer", default="每页条数"),
      *                         @OA\Property( property="total", type="integer", default="总条数"),
      *                         @OA\Property( property="is_end", type="integer", default="是否到底，1为到底"),
      *                     ),
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
    public function getTeacherList(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $page_size = 100;
        $filter = [
            ['course_fee', '>', 0],
            ['course_id', '>', 0]
        ];
        $teacherlists = $this->logic->getTeacherList($filter, $page_size);
        if (!$teacherlists || !$user_id) {
            throw new \Exception('未找到教师');
        }
        $lists = [];$paginate = [];
        if ($teacherlists ?? '') {
            foreach($teacherlists as $data) {
                $lists[] = [
                    'teacher_id' => $data->id,
                    'teacher_name' => $data->teacher_name,
                    'teacher_bn' => $data->teacher_bn,
                    'mobile' => $data->mobile,
                    'avatar' => $data->avatar,
                    'course_name' => $data->course_name,
                    'course_icon' => $data->course_icon,
                    'course_fee ' => $data->course_fee,
                    'work_year ' => $data->work_year,
                ];
            }
            $paginate['page_size'] = $this->page_size;
            $paginate['total'] = $teacherlists->total();
            if (count($lists) < $paginate['page_size']) {
                $paginate['is_end'] = 1;
            } else {
                $paginate['is_end'] = 0;
            }
        }
        $rest = [];
        $rest['list'] = $lists;
        $rest['paginate'] = $paginate;
        return response()->success($rest);
    }
    /**
      * @OA\Get(
      *     path="/teacher/center",
      *     summary="获取教师个人中心数据",
      *     operationId="getTeacherCenter",
      *     tags={"Teacher"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="teacher_id", type="integer", default="教师id"),
      *                     @OA\Property(property="teacher_name", type="string", default="教师姓名"),
      *                     @OA\Property(property="avatar", type="string", default="教师头像"),
      *                     @OA\Property(property="course_name", type="string", default="课程"),
      *                     @OA\Property(property="course_icon", type="string", default="图标"),
      *                     @OA\Property(property="work_year", type="string", default="琴龄"),
      *                     @OA\Property(property="course_fee", type="string", default="收费"),
      *                     @OA\Property( property="student", type="object",
      *                         @OA\Property( property="total", type="integer", default="总绑定学生数"),
      *                         @OA\Property( property="list", type="array", 
      *                             @OA\Items(type="object",
      *                                 @OA\Property( property="student_id", 
      *                                     type="integer", default="学生id"),
      *                                 @OA\Property( property="student_name",
      *                                     type="integer", default="学生姓名"),
      *                                 @OA\Property( property="course_name", 
      *                                     type="integer", default="课程姓名"),
      *                                 @OA\Property( property="avatar", 
      *                                     type="integer", default="学生头像"),
      *                                 @OA\Property( property="score", 
      *                                     type="integer", default="评分，还不知道规则，固定5分")
      *                             ),
      *                         ),
      *                     ),
      *                     @OA\Property( property="account", type="object",
      *                         @OA\Property( property="balance", type="integer", default="账户余额"),
      *                         @OA\Property( property="account_bn", type="string", default="账户编号"),
      *                         @OA\Property( property="month_total", type="integer", default="本月收入"),
      *                     ),
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
    public function getTeacherCenter(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $teacher = $this->logic->getTeacherInfo($user_id);
        if (!$teacher) {
            throw new \Exception('未找到教师信息');
        }
        $rest = $this->logic->getTeacherDetail($teacher);
        // 获取学生账户信息
        $rest['account'] = $this->logic->getAccountInfo($user_id, $teacher->id);
        $accountreport = $this->logic->getAccountByMonth($teacher->id);
        if ($accountreport['pay_total'] ?? '') {
            $rest['account']['month_total'] = $accountreport['pay_total'];
        } else {
            $rest['account']['month_total'] = 0;
        }
        // 获取教师的关联学生
        $bindStudent = $this->logic->getBindStudent($teacher);
        if (!empty($bindStudent)) {
            $rest['student']['total'] = count($bindStudent);
            $rest['student']['list'] = $bindStudent;
        } else {
            $rest['student'] = [];
        }
        return response()->success($rest);
    }
    /**
      * @OA\Post(
      *     path="/teacher/update",
      *     summary="更新教师信息",
      *     operationId="postUpdateTeacher",
      *     tags={"Teacher"},
      *     @OA\Parameter(name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string")),
      *     @OA\Parameter(
      *         name="teacher_name",in="query",required=true,description="教师昵称",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="course_id",in="query",required=true,description="课程id",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="desc",in="query",required=true,description="教师备注",@OA\Schema(type="integer")
      *     ),
      *     @OA\Parameter(
      *         name="avatar",in="query",description="头像，可不传",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="mobile",in="query",description="手机号，可不传",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="start_time",in="query",description="从业时间，可不传",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="course_fee",in="query",description="回课费用",@OA\Schema(type="integer")
      *     ),
      *     @OA\Parameter( 
      *         name="attach", in="query", description="教师资质，可不传",@OA\Schema(type="json") ),
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
    public function postUpdateTeacher(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $teacher = $this->logic->getTeacherInfo($user_id);
        if (!$teacher) {
            throw new \Exception('未找到教师信息');
        }
        $input = $request->all();
        // $validator = app('validator')->make($input, [
        //     'teacher_name' => 'required|string|max:255',// 教师姓名
        //     'course_id' => 'required',// 教师所教课程
        //     'desc' => 'required',// 教师备注
        // ]);
        // if ($validator->fails()) {
        //     throw new ResourceException('参数不正确.', $validator->errors());
        // }
        // 首先教师信息
        $update = [];
        if ($input['teacher_name'] ?? '') {// 教师姓名
            $update['teacher_name'] = remove_emoji($input['teacher_name']);
        }
        if ($input['course_id'] ?? '') {// 课程ID
            $update['course_id'] = $input['course_id'];
        }
        if ($input['desc'] ?? '') {// 教师简介
            $update['desc'] = $input['desc'];
        }
        if ($input['avatar'] ?? '') {// 头像
            $update['avatar'] = $input['avatar'];
        }
        if ($input['mobile'] ?? '') {// 手机号
            $update['mobile'] = $input['mobile'];
        }
        if ($input['working_time'] ?? '') {// 从业时间
            $update['start_time'] = $input['working_time'];
        }
        if ($input['course_fee'] ?? '') {// 回课费用（虚拟币）
            $update['course_fee'] = $input['course_fee'];
        }
        $this->logic->updateTeacher($teacher->id, $update);
        // 如果传递了资质连接，则保存资质
        if ($input['attach'] ?? '') {
            $this->logic->delAttachByTeacherId($teacher->id);
            $this->logic->uploadTeacherAttachments($teacher->id, $input['attach']);
        }
        return response()->success();
    }

    /**
      * @OA\Get(
      *     path="/teacher/accountlog",
      *     summary="获取教师变更记录",
      *     operationId="getTeacherAccountLogs",
      *     tags={"Teacher"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="page",in="query",description="当前页,默认为1",@OA\Schema(type="integer") ),
      *     @OA\Parameter( name="month", in="query", description="月份:2021-04-01", 
      *                         required=true,@OA\Schema(type="date") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property( property="list", type="array",
      *                         @OA\Items(type="object",
      *                             @OA\Property( property="name", type="string", default="消费备注"),
      *                             @OA\Property( property="course_name", type="string", default="课程名称"),
      *                             @OA\Property( property="course_icon", type="string", default="课程标识"),
      *                             @OA\Property( property="change_fee", type="integer", default="变更金额"),
      *                             @OA\Property( property="created_at", type="string", default="时间"),
      *                             @OA\Property( property="student_name", type="string", default="回课所属学生")
      *                         ),
      *                     ),
      *                     @OA\Property( property="paginate", type="object",
      *                         @OA\Property( property="page_size", type="integer", default="每页条数"),
      *                         @OA\Property( property="total", type="integer", default="总条数"),
      *                         @OA\Property( property="is_end", type="integer", default="是否到底，1为到底"),
      *                     ),
      *                     @OA\Property( property="report", type="object",
      *                         @OA\Property( property="pay_total", type="integer", default="收入总额"),
      *                         @OA\Property( property="sales_total", type="integer", default="提现总额"),
      *                     ),
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
    public function getTeacherAccountLogs(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        // $page_index = $request->input('page', 1);// 页数
        // 获取教师信息
        $teacher = $this->logic->getTeacherInfo($user_id);
        if (!$teacher) {
            throw new \Exception('未找到教师信息');
        }
        $input = $request->all();
        if ($input['month'] ?? '') {
            list($start_day, $end_day) = phpdate()->set($input['month'])->begin('monthtime');
        } else {
            list($start_day, $end_day) = phpdate()->begin('monthtime');
        }
        // 获取账户变更记录
        $account_logic = new AccountLogic();
        $accountlogs = $account_logic->getLogsByTeacherId($teacher->id, $this->page_size, [$start_day, $end_day]);
        $lists = [];$paginate = [];
        if ($accountlogs ?? '') {
            foreach($accountlogs as $log) {
                $lists[] = [
                    'id' => $log->id,
                    'name' => $log->name,
                    'course_name' => $log->course_name,
                    'course_icon' => $log->course_icon,
                    'student_name' => $log->student_name,
                    'type' => $log->type,
                    'first_balance' => $log->first_balance,
                    'change_fee' => $log->change_fee,
                    'created_at' => $log->create_time,
                ];
            }
            $paginate['page_size'] = $this->page_size;
            $paginate['total'] = $accountlogs->total();
            if (count($lists) < $paginate['page_size']) {
                $paginate['is_end'] = 1;
            } else {
                $paginate['is_end'] = 0;
            }
        }
        $rest = [];
        $rest['list'] = $lists;
        $rest['paginate'] = $paginate;
        $filter = [
            'teacher_id' => $teacher->id,
            'user_role' => 2
        ];
        $rest['report'] = $account_logic->getTotalByDays($filter, [$start_day, $end_day]);
        return response()->success($rest);
    }

    /**
      * @OA\Get(
      *     path="/teacher/report",
      *     summary="获取教师账户回课统计",
      *     operationId="getTeacherReport",
      *     tags={"Teacher"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="month", in="query", description="月份:2021-04-01", 
      *                     required=true,@OA\Schema(type="date") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="week", type="array", 
      *                         @OA\Items(type="object",
      *                             @OA\Property(property="week", type="string", default="星期，0是周日"),
      *                             @OA\Property(property="total", type="string", default="总课程计"),
      *                             @OA\Property(property="reply", type="string", default="回总复数"),
      *                             @OA\Property(property="notreply", type="string", default="未回复数"),
      *                             @OA\Property(property="closed", type="string", default="总闭课数"),
      *                         ),
      *                     ),
      *                     @OA\Property(property="account", type="object", 
      *                         @OA\Property(property="total_count", type="string", default="总额统计"),
      *                         @OA\Property(property="month_count", type="string", default="本月统计"),
      *                         @OA\Property(property="week_count", type="string", default="本周统计"),
      *                         @OA\Property(property="balance", type="string", default="账户余额"),
      *                         @OA\Property(property="course_fee", type="string", default="课程金额"),
      *                     ),
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
    public function getTeacherReport(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $user_role = $auth_user['user_role'] ?? 0;// 用户角色
        if ( ! UserLogic::checkRole($user_role, 'teacher') ) {// 学生端统计
            throw new \Exception('非学生端角色');
        }
        // 获取教师信息
        $teacher = $this->logic->getTeacherInfo($user_id);
        if (!$teacher) {
            throw new \Exception('未找到教师信息');
        }
        $input = $request->all();
        if ($input['month'] ?? '') {
            list($start_day, $end_day) = phpdate()->set($input['month'])->begin('month');
        } else {
            list($start_day, $end_day) = phpdate()->begin('month');
        }
        $rest = [];
        $rest['week'] = $this->logic->getClassReportByWeek($teacher->id, [$start_day, $end_day]);
        $filter = [
            'teacher_id' => $teacher->id,
            'user_role' => 2
        ];
        $account_logic = new AccountLogic();
        // 获取总统计
        $total_count = $account_logic->getTotalByDays($filter);
        // 获取本月统计
        $time = time();
        list($start_day, $end_day) = phpdate()->set($time)->begin('monthtime');
        $month_count = $account_logic->getTotalByDays($filter, [$start_day, $end_day]);
        // 获取本周统计
        $time = time();
        list($start_day, $end_day) = phpdate()->set($time)->getWeeks(true);
        $week_count = $account_logic->getTotalByDays($filter, [$start_day, $end_day]);
        $rest['account']['total_count'] = $total_count['pay_total'] ?? 0;
        $rest['account']['month_count'] = $month_count['pay_total'] ?? 0;
        $rest['account']['week_count'] = $week_count['pay_total'] ?? 0;
        $rest['account']['balance'] = 0;// 账户余额
        $rest['account']['course_fee'] = 0;// 课程金额
        // 获取账户详情
        $account = $account_logic->getTeacherAccount($user_id, $teacher->id);
        if ( $account->balance ?? '' ) {
            $rest['account']['balance'] = $account->balance;
        }
        if ( $teacher->course_fee ?? 0 ) {
            $rest['account']['course_fee'] = $teacher->course_fee;
        }
        return response()->success($rest);
    }

    /**
      * @OA\Post(
      *     path="/teacher/coursefee",
      *     summary="更新教师回课金额",
      *     operationId="postTeacherCourseFee",
      *     tags={"Teacher"},
      *     @OA\Parameter(name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string")),
      *     @OA\Parameter(
      *         name="course_fee",in="query",required=true,description="回课费用",@OA\Schema(type="integer")
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
    public function postTeacherCourseFee(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $teacher = $this->logic->getTeacherInfo($user_id);
        if (!$teacher) {
            throw new \Exception('未找到教师信息');
        }
        $input = $request->all();
        $validator = app('validator')->make($input, [
            'course_fee' => 'required|integer|min:1|max:200',// 回课费用
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数不正确.', $validator->errors());
        }
        // 首先教师信息
        $update = [
            'course_fee'  =>  $input['course_fee'],// 回课费用
        ];
        $this->logic->updateTeacher($teacher->id, $update);
        return response()->success();
    }

    /**
      * @OA\Get(
      *     path="/teacher/student/detail",
      *     summary="教师绑定学生详情",
      *     operationId="getStudentDetail",
      *     tags={"Teacher"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="student_id", in="query", description="学生id", 
      *                     required=true,@OA\Schema(type="integer") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="student", type="object", 
      *                         @OA\Property(property="student_name", type="string", default="学生姓名"),
      *                         @OA\Property(property="avatar", type="string", default="学生头像"),
      *                         @OA\Property(property="score", type="string", default="学生分数"),
      *                     ),
      *                     @OA\Property(property="baclclass", type="object", 
      *                         @OA\Property(property="total_count", type="string", default="总回课数"),
      *                         @OA\Property(property="month_count", type="string", default="本月回课"),
      *                         @OA\Property(property="week_count", type="string", default="本周回课"),
      *                     ),
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
    public function getStudentDetail(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $student_id = $request->input('student_id', 0);// 学生id
        if (!$user_id || !$student_id) {
            throw new \Exception('参数不正确');
        }
        // 获取教师信息
        $teacher = $this->logic->getTeacherInfo($user_id);
        if (!$teacher) {
            throw new \Exception('未找到教师信息');
        }
        // 获取学生信息
        $student_logic = new StudentLogic();
        $student = $student_logic->getStudentById($student_id);
        if (!$student) {
            throw new \Exception('未找到学生信息');
        }
        $rest = [];
        $rest['student']['student_name'] = $student->student_name;
        $rest['student']['avatar'] = $student->avatar;
        $rest['student']['score'] = $student->score;
        // 获取学生回课统计
        $backclass_logic = new BackclassLogic();
        // 获取总统计
        $filter['student_id'] = $student->id;
        $total_count = $backclass_logic->getBackClassReport($filter);
        // 获取本月统计
        $time = time();
        list($start_day, $end_day) = phpdate()->set($time)->begin('monthtime');
        $month_count = $backclass_logic->getBackClassReport($filter, [$start_day, $end_day]);
        // 获取本周统计
        $time = time();
        list($start_day, $end_day) = phpdate()->set($time)->getWeeks(true);
        $week_count = $backclass_logic->getBackClassReport($filter, [$start_day, $end_day]);
        $rest['baclclass']['total_count'] = $total_count['total'] ?? 0;
        $rest['baclclass']['month_count'] = $month_count['total'] ?? 0;
        $rest['baclclass']['week_count'] = $week_count['total'] ?? 0;
        // // 获取学生发送给教师，未处理的回课信息
        // $rest['now_classes'] = $this->logic->getNewStudentClasses($teacher->id, $student->id);
        // // 获取学生发送给教师，已处理的回课信息，带分页
        // $rest['reply_classes'] = $this->logic->getReplyStudentClasses($teacher->id, $student->id);
        return response()->success($rest);
    }

    /**
      * @OA\Get(
      *     path="/teacher/backclass/list",
      *     summary="获取教师关联的回课列表",
      *     operationId="getReacherBackclass",
      *     tags={"Teacher"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="page",in="query",description="当前页,默认为1",@OA\Schema(type="integer") ),
      *     @OA\Parameter( name="month", in="query", description="月份:2021-04-01，非必填",@OA\Schema(type="date") ),
      *     @OA\Parameter( name="week", in="query", description="第几周，1～5周，非必填",@OA\Schema(type="date") ),
      *     @OA\Parameter( name="is_reply", in="query", description="是否回复过，0 未回复，1 已回复，2或不传为全部",@OA\Schema(type="integer") ),
      *     @OA\Parameter( name="is_closed", in="query", description="是否结课，0 未结课，1 已结课，2或不传为全部",@OA\Schema(type="integer") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property( property="list", type="array",
      *                         @OA\Items(type="object",
      *                             @OA\Property( property="backclass_id", type="string", default="回课ID"),
      *                             @OA\Property( property="backclass_name", type="string", default="回课名称"),
      *                             @OA\Property( property="backclass_desc", type="string", default="回课备注"),
      *                             @OA\Property( property="student_id", type="string", default="学生ID"),
      *                             @OA\Property( property="student_name", type="string", default="学生姓名"),
      *                             @OA\Property( property="student_avatar", type="string", default="学生头像"),
      *                             @OA\Property( property="course_name", type="string", default="课程名称"),
      *                             @OA\Property( property="course_icon", type="string", default="课程标识"),
      *                             @OA\Property( property="backclass_day", type="string", default="时间"),
      *                             @OA\Property( property="is_reply", type="integer", default="是否回复过"),
      *                             @OA\Property( property="is_closed", type="integer", default="是否结课"),
      *                             @OA\Property( property="attachment", type="array",
      *                                 @OA\Items(type="object",
      *                                     @OA\Property( property="attachment_url", 
      *                                         type="string", default="资源路径"),
      *                                 ),
      *                             ),
      *                             @OA\Property( property="reply", type="array",
      *                                 @OA\Items(type="object",
      *                                     @OA\Property( property="reply_id", 
      *                                         type="integer", default="评论id"),
      *                                     @OA\Property( property="student_id", 
      *                                         type="integer", default="学生id"),
      *                                     @OA\Property( property="student_name", 
      *                                         type="integer", default="学生姓名"),
      *                                     @OA\Property( property="teacher_id", 
      *                                         type="integer", default="教师id"),
      *                                     @OA\Property( property="teacher_name", 
      *                                         type="integer", default="教师姓名"),
      *                                     @OA\Property( property="reply_content", 
      *                                         type="integer", default="评论内容"),
      *                                     @OA\Property( property="reply_time", 
      *                                         type="integer", default="评论时间"),
      *                                     @OA\Property( property="attachment", type="array",
      *                                         @OA\Items(type="object",
      *                                             @OA\Property( property="attachment_url", 
      *                                                 type="string", default="资源路径"),
      *                                             @OA\Property( property="type", 
      *                                                 type="integer", default="图片或视频"),
      *                                         ),
      *                                     ),
      *                                 ),
      *                             ),
      *                         ),
      *                     ),
      *                     @OA\Property( property="paginate", type="object",
      *                         @OA\Property( property="page_size", type="integer", default="每页条数"),
      *                         @OA\Property( property="total", type="integer", default="总条数"),
      *                         @OA\Property( property="is_end", type="integer", default="是否到底，1为到底"),
      *                     ),
      *                     @OA\Property( property="report", type="object",
      *                         @OA\Property( property="total", type="integer", default="回课总数"),
      *                         @OA\Property( property="reply", type="string", default="回复数"),
      *                         @OA\Property( property="closed", type="string", default="闭课数"),
      *                         @OA\Property( property="noreply", type="string", default="未回复数"),
      *                     ),
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
    public function getTeacherBackclass(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $input = $request->all();
        $start_day = '';$end_day = '';$days = [];
        if ($input['month'] ?? '') {
            $week = $input['week'] ?? 1;
            if ($week > 5) {
                $week = 5;
            }
            list($start_day, $end_day) = phpdate()->getMonthWeek($input['month'], $week, true);
            $days = [$start_day, $end_day];
        }
        $attachment_logic = new AttachmentLogic();
        $filter = [];
        // 获取教师信息
        $teacher_logic = new TeacherLogic();
        $teacher = $teacher_logic->getTeacherInfo($user_id);
        if (!$teacher) {
            throw new \Exception('未找到学生信息');
        }
        $filter['teacher_id'] = $teacher->id;// 查询对应教师的回课
        // 查询指定学生提交的回课
        if ($input['student_id'] ?? '') {
            $filter['student_id'] = $input['student_id'];
        }
        // 查询条件
        if ( isset($input['is_closed']) && $input['is_closed'] < 2) {
            $filter['is_closed'] = $input['is_closed'];
        } else if ( isset($input['is_reply']) && $input['is_reply'] < 2) {
            $filter['is_reply'] = $input['is_reply'];
        }
        // 获取账户变更记录
        $backclass_logic = new BackclassLogic();
        $backclasses = $backclass_logic->getBackclassLists($filter, $this->page_size, $days);
        $lists = [];$paginate = [];
        if ( $backclasses ?? '' ) {
            foreach($backclasses as $classes) {
                $list = [
                    'backclass_id' => $classes->id,
                    'backclass_name' => $classes->backclass_name,
                    'backclass_desc' => $classes->backclass_desc,
                    'student_name' => $classes->student_name,
                    'student_id' => $classes->student_id,
                    'student_avatar' => $classes->student_avatar,
                    'course_name' => $classes->course_name,
                    'course_icon' => $classes->course_icon,
                    'teacher_name' => $classes->teacher_name,
                    'backclass_day' => $classes->backclass_date,
                    'is_reply' => $classes->is_reply,
                    'is_closed' => $classes->is_closed,
                ];
                if ($classes->reply ?? '') {
                    $list['reply'] = $backclass_logic->getClassReplys($classes->reply);
                } else {
                    $list['reply'] = [];
                }
                $list['attachment'] = $attachment_logic->getBackclassAttchments( $classes->id );
                $lists[] = $list;
            }
            $paginate['page_size'] = $this->page_size;
            $paginate['total'] = $backclasses->total();
            if (count($lists) < $paginate['page_size']) {
                $paginate['is_end'] = 1;
            } else {
                $paginate['is_end'] = 0;
            }
        }
        $rest = [];
        $rest['list'] = $lists;
        $rest['paginate'] = $paginate;
        $rest['report'] = $backclass_logic->getBackClassReport($filter, $days);
        return response()->success($rest);
    }

    /**
      * @OA\Get(
      *     path="/teacher/backclass/untreated",
      *     summary="获取教师关联的回课列表",
      *     operationId="getStudentUntreatedClasses",
      *     tags={"Teacher"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property( property="list", type="array",
                                @OA\Items(type="object",
      *                             @OA\Property( property="student_id", type="string", default="学生ID"),
      *                             @OA\Property( property="student_name", type="string", default="学生姓名"),
      *                             @OA\Property( property="student_avatar", type="string", default="学生头像"),
      *                             @OA\Property( property="backclass_count", 
      *                                 type="string", default="未处理回课总数"),
      *                             @OA\Property( property="backclass_time", type="string", default="最新回课时间"),
      *                         ),
      *                     ),
      *                 ),
      *                 @OA\Property( property="noreply", type="integer", default="未处理总数"),
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
    public function getStudentUntreatedClasses(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        // 获取教师信息
        $teacher_logic = new TeacherLogic();
        $teacher = $teacher_logic->getTeacherInfo($user_id);
        if ( !$teacher ) {
            throw new \Exception('未找到教师信息');
        }
        // 查询条件
        $filter = [
            'teacher_id' => $teacher->id,// 查询对应教师的回课
            'is_reply' => 0,// 未回复过
        ];
        // 获取账户变更记录
        $backclass_logic = new BackclassLogic();
        $backclasses = $backclass_logic->getBackclassByStudent($filter);
        $lists = [];$noreply = 0;
        $day = lib()->now();
        if ( $backclasses ) {
            foreach($backclasses as $classes) {
                $list = [
                    'student_id' => $classes->student_id,
                    'student_name' => $classes->student->student_name ?? $classes->student_name,
                    'student_avatar' => $classes->student->avatar ?? "",
                    'backclass_count' => $classes->cnt ?? 0,
                    'backclass_time' => $classes->time ?? $day,
                ];
                $lists[] = $list;
                $noreply += $list['backclass_count'];
            }
        }
        $rest['list'] = $lists;
        $rest['noreply'] = $noreply;
        return response()->success($rest);
    }
}
