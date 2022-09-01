<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\V1\ApiV1Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Logic\UserLogic;
use App\Logic\StudentLogic;
use App\Logic\TeacherLogic;
use App\Logic\BackclassLogic;
use App\Logic\AccountLogic;

/**
 * 学生端接口
 * @author Toby.Tu 2021-04-17
 */
class StudentController extends ApiV1Controller
{
//    private $logic;

    public function __construct() {
//        $this->logic = new StudentLogic();
    }

    /**
      * @OA\Get(
      *     path="/student/info",
      *     summary="获取学生信息",
      *     operationId="getStudentInfo",
      *     tags={"Student"},
      *     @OA\Parameter(
      *         name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string")
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="nickname", type="string", description="学生姓名"),
      *                     @OA\Property(property="avatar", type="string", description="学生头像"),
      *                     @OA\Property(property="sex", type="integer", description="性别"),
      *                     @OA\Property(property="age", type="integer", description="学生年龄"),
      *                     @OA\Property(property="course_name", type="string", description="乐器"),
      *                     @OA\Property(property="work_year", type="string", description="琴龄"),
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
    public function getStudentInfo(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $user_role = $auth_user['user_role'] ?? 0;// 用户角色
        if ( ! UserLogic::checkRole($user_role, 'student') ) {// 学生端统计
            throw new \Exception('非学生端角色');
        }
        $student = $this->logic->getStudentInfo($user_id);
        if (!$student) {
            throw new \Exception('未找到学生信息');
        }
        $studentAccount = new AccountLogic();
        $account = $studentAccount->getStudentAccount($user_id,$student->id);
        $rest = $this->logic->getStudentDetail($student);
        $rest["balance"] = $account->balance ?? 0;
        return response()->success($rest);
    }
    /**
      * @OA\Get(
      *     path="/student/backclass/report",
      *     summary="获取学生回课月度统计",
      *     operationId="getBackClassReport",
      *     tags={"Student"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="month", in="query", description="月份:2021-04-01", required=true,@OA\Schema(type="date") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="week", type="integer", default="当前第几周"),
      *                     @OA\Property(property="count", type="integer", default="周回课总数"),
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
    public function getBackClassReport(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $user_role = $auth_user['user_role'] ?? 0;// 用户角色
        if ( ! UserLogic::checkRole($user_role, 'student') ) {// 学生端统计
            throw new \Exception('非学生端角色');
        }
        $student = $this->logic->getStudentInfo($user_id);
        if (!$student) {
            throw new \Exception('未找到学生信息');
        }
        $input = $request->all();
        if ($input['month'] ?? '') {
            list($start_day, $end_day) = phpdate()->set($input['month'])->begin('month');
        } else {
            list($start_day, $end_day) = phpdate()->begin('month');
        }
        $reports = $this->logic->getClassReportByMonth($student['id'], $start_day, $end_day);
        return response()->success($reports);
    }
    /**
      * @OA\Get(
      *     path="/student/center",
      *     summary="获取学生个人中心数据",
      *     operationId="getStudentCenter",
      *     tags={"Student"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="student_id", type="integer", default="学生id"),
      *                     @OA\Property(property="nickname", type="string", default="学生姓名"),
      *                     @OA\Property(property="avatar", type="string", default="学生头像"),
      *                     @OA\Property(property="sex", type="integer", default="性别"),
      *                     @OA\Property(property="age", type="integer", default="学生年龄"),
      *                     @OA\Property(property="course_name", type="string", default="乐器"),
      *                     @OA\Property(property="work_year", type="string", default="琴龄"),
      *                     @OA\Property( property="bindteacher", type="object",
      *                         @OA\Property( property="teacher_id", type="integer", default="教师ID"),
      *                         @OA\Property( property="teacher_name", type="string", default="教师姓名"),
      *                         @OA\Property( property="course_name", type="string", default="课程")
      *                     ),
      *                     @OA\Property( property="classreport", type="object",
      *                         @OA\Property( property="total", type="integer", default="回课总数"),
      *                         @OA\Property( property="reply", type="string", default="回复数"),
      *                         @OA\Property( property="closed", type="string", default="闭课数"),
      *                         @OA\Property( property="noreply", type="string", default="未回复数"),
      *                     ),
      *                     @OA\Property( property="account", type="object",
      *                         @OA\Property( property="balance", type="integer", default="账户余额"),
      *                         @OA\Property( property="account_bn", type="string", default="账户编号"),
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
    public function getStudentCenter(Request $request) {
        echo base64_decode('e64b78fc3bc91bcbc7dc232ba8ec59e0');die;
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $student = $this->logic->getStudentInfo($user_id);
        if (!$student) {
            throw new \Exception('未找到学生信息');
        }
        $rest = $this->logic->getStudentDetail($student);
        $rest['bindteacher'] = [];
        // 获取学生绑定的教师和课程
        $student_teacher = $this->logic->getBindTeacher($student);
        if ($student_teacher ?? '') {
            foreach($student_teacher as $value) {
                $rest['bindteacher'][] = [
                    'teacher_id' => $value->teacher_id,
                    'teacher_name' => $value->teacher_name,
                    'course_name' => $value->course_name,
                ];
            }
        }
        // 获取学生回课统计
        $reports = $this->logic->getBackClassReport($student->id);
        if ($reports ?? '') {
            $rest['classreport'] = $reports;
        } else {
            $rest['classreport'] = [];
        }
        // 获取学生账户信息
        $rest['account'] = $this->logic->getAccountInfo($user_id, $student->id);
        return response()->success($rest);
    }

    /**
      * @OA\Post(
      *     path="/student/bindteacher",
      *     summary="学生扫码绑定教师",
      *     operationId="postBindTeacher",
      *     tags={"Student"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="teacher_id",in="query",description="教师ID",@OA\Schema(type="integer") ),
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
    public function postBindTeacher(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        // $user_role = $auth_user['user_role'] ?? 0;// 用户角色
        // if ( ! UserLogic::checkRole($user_role, 'student') ) {// 学生端统计
        //     throw new \Exception('非学生端角色');
        // }
        $teacher_id = $request->input('teacher_id', 0);// 教师ID
        if (!$teacher_id) {
            throw new \Exception('未传递教师ID');
        }
        $tracher_logic = new TeacherLogic();
        // 获取学生信息
        $student = $this->logic->getStudentInfo($user_id);
        if (!$student) {
            throw new \Exception('未找到学生信息');
        }
        // 获取老师信息
        $tracher = $tracher_logic->getTeacherById($teacher_id);
        if (!$tracher) {
            throw new \Exception('未找到教师信息');
        }
        // 验证学生与老师是否绑定过
        if ( $this->logic->isBindTeacher($student, $tracher) ) {
            return response()->success();
        }
        // 绑定教师
        $this->logic->bindTeacher($student, $tracher);
        return response()->success();
    }

    /**
      * @OA\Get(
      *     path="/student/accountlog",
      *     summary="获取学生账户变更记录",
      *     operationId="getStudentAccountLogs",
      *     tags={"Student"},
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
      *                             @OA\Property( property="teacher_name", type="string", default="课程所属教师")
      *                         ),
      *                     ),
      *                     @OA\Property( property="paginate", type="object",
      *                         @OA\Property( property="page_size", type="integer", default="每页条数"),
      *                         @OA\Property( property="total", type="integer", default="总条数"),
      *                         @OA\Property( property="is_end", type="integer", default="是否到底，1为到底"),
      *                     ),
      *                     @OA\Property( property="report", type="object",
      *                         @OA\Property( property="pay_total", type="integer", default="充值总额"),
      *                         @OA\Property( property="sales_total", type="integer", default="回课付费总额"),
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
    public function getStudentAccountLogs(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $user_role = $auth_user['user_role'] ?? 0;// 用户角色
        // $page_index = $request->input('page', 1);// 页数
        if ( ! UserLogic::checkRole($user_role, 'student') ) {// 学生端统计
            throw new \Exception('非学生端角色');
        }
        // 获取学生信息
        $student = $this->logic->getStudentInfo($user_id);
        if (!$student) {
            throw new \Exception('未找到学生信息');
        }
        $input = $request->all();
        if ($input['month'] ?? '') {
            list($start_day, $end_day) = phpdate()->set($input['month'])->begin('monthtime');
        } else {
            list($start_day, $end_day) = phpdate()->begin('monthtime');
        }
        // 获取账户变更记录
        $account_logic = new AccountLogic();
        $page_size = 10;
        $accountlogs = $account_logic->getLogsByStudentId($student->id, $page_size, [$start_day, $end_day]);
        $lists = [];$paginate = [];
        if ($accountlogs ?? '') {
            foreach($accountlogs as $log) {
                $lists[] = [
                    'id' => $log->id,
                    'name' => $log->name,
                    'course_name' => $log->course_name,
                    'course_icon' => $log->course_icon,
                    'teacher_name' => $log->teacher_name,
                    'type' => $log->type,
                    'first_balance' => $log->first_balance,
                    'change_fee' => $log->change_fee,
                    'created_at' => $log->create_time,
                ];
            }
            $paginate['page_size'] = $page_size;
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
        $rest['report'] = $account_logic->getReportByStudentId($student->id, [$start_day, $end_day]);
        return response()->success($rest);
    }

    /**
      * @OA\Get(
      *     path="/student/bindteacher",
      *     summary="获取学生绑定教师",
      *     operationId="getBindTeacher",
      *     tags={"Teacher"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property( property="teacher_id", type="integer", default="教师ID"),
      *             @OA\Property( property="teacher_name", type="string", default="教师姓名"),
      *             @OA\Property( property="course_name", type="string", default="课程")
      *         ),
      *     ),
      *     @OA\Response(
      *         response=280,
      *         description="错误信息",
      *         @OA\JsonContent(ref="#/components/schemas/ApiErrorRespones"),
      *     )
      * )
      */
    public function getBindTeacher(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $student = $this->logic->getStudentInfo($user_id);
        if (!$student) {
            throw new \Exception('未找到学生信息');
        }
        // 获取学生绑定的教师和课程
        $rest = [];
        $student_teacher = $this->logic->getBindTeacher($student);
        if ($student_teacher ?? '') {
            foreach($student_teacher as $value) {
                $course_fee = $value->teacher->course_fee ?? 0;
                if ( !$course_fee ) {
                    continue;
                }
                $rest['bindteacher'][] = [
                    'teacher_id' => $value->teacher_id,
                    'teacher_name' => $value->teacher_name,
                    'course_name' => $value->course_name,
                    'course_fee' => $course_fee,
                ];
            }
        }
        return response()->success($rest);
    }

    /**
      * @OA\Post(
      *     path="/student/update",
      *     summary="更新学生信息",
      *     operationId="postUpdateStudent",
      *     tags={"Student"},
      *     @OA\Parameter(name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string")),
      *     @OA\Parameter(
      *         name="student_name",in="query",required=true,description="学生姓名",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="course_id",in="query",required=true,description="课程id",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="desc",in="query",required=true,description="学生简介",@OA\Schema(type="integer")
      *     ),
      *     @OA\Parameter(
      *         name="avatar",in="query",description="头像，可不传",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="mobile",in="query",description="手机号，可不传",@OA\Schema(type="string")
      *     ),
      *     @OA\Parameter(
      *         name="start_time",in="query",description="开始学习时间，可不传",@OA\Schema(type="string")
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
    public function postUpdateStudent(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $student = $this->logic->getStudentInfo($user_id);
        if (!$student) {
            throw new \Exception('未找到学生信息');
        }
        $input = $request->all();
        // 首先学生信息
        $update = [];
        if ($input['student_name'] ?? '') {// 学生姓名
            $update['student_name'] = remove_emoji($input['student_name']);
        }
        if ($input['course_id'] ?? '') {// 课程ID
            $update['course_id'] = $input['course_id'];
        }
        if ($input['desc'] ?? '') {// 学生简介
            $update['desc'] = $input['desc'];
        }
        if ($input['avatar'] ?? '') {// 头像
            $update['avatar'] = $input['avatar'];
        }
        if ($input['mobile'] ?? '') {// 手机号
            $update['mobile'] = $input['mobile'];
        }
        if ($input['start_time'] ?? '') {// 开始学习时间
            $update['start_time'] = $input['start_time'];
        }
        if (empty($update)) {
            throw new \Exception('没有要修改的信息');
        }
        $this->logic->updateStudent($student->id, $update);
        return response()->success();
    }
}
