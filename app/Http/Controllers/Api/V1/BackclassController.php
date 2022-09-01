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
use App\Logic\AttachmentLogic;
use App\Services\OssService;

/**
 * 回课相关接口
 * @author Toby.Tu 2021-04-17
 */
class BackclassController extends ApiV1Controller
{
    private $logic;
    public function __construct() {
        $this->logic = new BackclassLogic();
    }
    /**
      * @OA\Get(
      *     path="/backclass/list",
      *     summary="获取回课记录(学生/教师)",
      *     operationId="getBackclassList",
      *     tags={"BackClass"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="page",in="query",description="当前页,默认为1",@OA\Schema(type="integer") ),
      *     @OA\Parameter( name="month", in="query", description="月份:2021-04-01，非必填",@OA\Schema(type="date") ),
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
    public function getBackclassList(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $user_role = $auth_user['user_role'] ?? 0;// 用户角色
        $input = $request->all();
        $start_day = '';$end_day = '';$days = [];
        if ($input['month'] ?? '') {
            list($start_day, $end_day) = phpdate()->set($input['month'])->begin('monthtime');
            $days = [$start_day, $end_day];
        }
        //  else {
        //     list($start_day, $end_day) = phpdate()->begin('monthtime');
        // }
        $student_logic = new StudentLogic();
        $attachment_logic = new AttachmentLogic();
        $filter = [];
        if ( UserLogic::checkRole($user_role, 'student') ) {// 学生端统计
            // 获取学生信息
            $student = $student_logic->getStudentInfo($user_id);
            if (!$student) {
                throw new \Exception('未找到学生信息');
            }
            $filter['student_id'] = $student->id;
        } else {
            // 获取教师信息
            $teacher_logic = new TeacherLogic();
            $teacher = $teacher_logic->getTeacherInfo($user_id);
            if (!$teacher) {
                throw new \Exception('未找到教师信息');
            }
            $filter['teacher_id'] = $teacher->id;// 查询对应教师的回课
            // 查询指定学生提交的回课
            if ($input['student_id'] ?? '') {
                $filter['student_id'] = $input['student_id'];
            }
        }
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
        $backclasses = $this->logic->getBackclassLists($filter, $this->page_size, $days);
        $lists = [];$paginate = [];
        if (!$backclasses->isEmpty()) {
            foreach($backclasses as $classes) {
                $list = [
                    'backclass_id' => $classes->id,
                    'backclass_name' => $classes->backclass_name,
                    'backclass_desc' => $classes->backclass_desc,
                    'course_name' => $classes->course_name,
                    'course_icon' => $classes->course_icon,
                    'teacher_name' => $classes->teacher_name,
                    'backclass_day' => $classes->backclass_date,
                    'is_reply' => $classes->is_reply,
                    'is_closed' => $classes->is_closed,
                ];
                if ($classes->reply ?? '') {
                    $list['reply'] = $this->logic->getClassReplys($classes->reply);
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
        $rest['report'] = $this->logic->getBackClassReport($filter, $days);
        return response()->success($rest);
    }

    /**
      * @OA\Get(
      *     path="/backclass/courselist",
      *     summary="获取课程列表",
      *     operationId="getCourseList",
      *     tags={"BackClass"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="id", type="string", default="课程id"),
      *                     @OA\Property(property="course_name", type="string", default="课程名称"),
      *                     @OA\Property(property="course_icon", type="string", default="课程icon")
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
    public function getCourseList(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        if (!$user_id) {
            throw new \Exception('参数不正确');
        }
        $course_opts = $this->logic->getCourseOpts();
        return response()->success($course_opts);
    }

    /**
      * @OA\Post(
      *     path="/backclass/upload",
      *     summary="上传素材资源",
      *     operationId="postUploadMedia",
      *     tags={"BackClass"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="code", type="integer", default="200"),
      *                     @OA\Property(property="msg", type="string", default="success"),
      *                     @OA\Property(property="data", type="string", default=""),
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
    public function postUploadMedia(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        if (!$user_id) {
            throw new \Exception('参数不正确');
        }
        return response()->success();
    }
    /**
      * @OA\Post(
      *     path="/backclass/qiniu_token",
      *     summary="获取七牛上传token",
      *     operationId="postQiniuToken",
      *     tags={"BackClass"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="type",in="query",description="类型，image或video",required=true,@OA\Schema(type="string") ),
      *     @OA\Parameter( name="file", in="query", description="文件名",required=true,@OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="token", type="string", default="七牛上传token"),
      *                     @OA\Property(property="domain", type="string", default="七牛资源域名"),
      *                     @OA\Property(property="region", type="string", default="存储区域"),
      *                     @OA\Property(property="key", type="string", default="文件存储位置")
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
    public function postQiniuToken(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $user_role = $auth_user['user_role'] ?? 0;// 用户权限
        if (!$user_id) {
            throw new \Exception('参数不正确');
        }
        $input = $request->all();
        $validator = app('validator')->make($input, [
            'type' => 'required|in:image,video',
            'file' => 'required',
        ], [
            'type.required' => '请传递资源类型',
            'type.in' => '资源上传暂时只支持图片和视频',
            'file.required' => '请传递文件名',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            throw new \Exception($errors);
        }
        $result = OssService::token($user_role, $input['type'], $input['file']);
        return response()->success($result);
    }
    /**
      * @OA\Post(
      *     path="/backclass/qiniu_uptoken",
      *     summary="根据七牛官方sdk获取uptoken",
      *     operationId="postQiniuUpToken",
      *     tags={"BackClass"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="type",in="query",description="类型，image或video",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="file", in="query", description="文件名",@OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="uptoken", type="string", default="七牛上传token"),
      *                     @OA\Property(property="domain", type="string", default="七牛资源域名"),
      *                     @OA\Property(property="region", type="string", default="存储区域"),
      *                     @OA\Property(property="key", type="string", default="文件存储位置")
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
    public function postQiniuUpToken(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $user_role = $auth_user['user_role'] ?? 0;// 用户权限
        if (!$user_id) {
            throw new \Exception('参数不正确');
        }
        $oss_type = $input['type'] ?? 'image';
        $filename = $input['file'] ?? '';
        $result = OssService::upToken($user_role, $oss_type, $filename);
        return response()->success($result);
    }

    /**
      * @OA\Post(
      *     path="/backclass/create",
      *     summary="新建回课",
      *     operationId="postCreateBackclass",
      *     tags={"BackClass"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="teacher_id",in="query",description="教师id",required=true,
      *         @OA\Schema(type="string") ),
      *     @OA\Parameter( name="backclass_name", in="query", description="回课名称",
      *         required=true,@OA\Schema(type="string") ),
      *     @OA\Parameter( name="backclass_desc", in="query", description="回课描述",
      *         @OA\Schema(type="string") ),
      *     @OA\Parameter( name="attach", in="query", description="回课附件",
      *         @OA\Schema(type="json") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="backclass_id", type="integer", default="回课id"),
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
    public function postCreateBackclass(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        // $user_role = $auth_user['user_role'] ?? 0;// 用户权限
        if (!$user_id) {
            throw new \Exception('参数不正确');
        }
        $input = $request->all();
        $validator = app('validator')->make($input, [
            'teacher_id' => 'required|min:0',
            'backclass_name' => 'required',
        ], [
            'teacher_id.required' => '请选择对应教师',
            'teacher_id.min' => '请选择对应教师',
            'backclass_name.required' => '请输入回课名称',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            throw new \Exception($errors);
        }
        $student_logic = new StudentLogic();
        $teacher_logic = new TeacherLogic();
        $account_logic = new AccountLogic();
        // 获取学生
        $student = $student_logic->getStudentInfo($user_id);
        if ( !$student ) {
            throw new \Exception('未找到学生信息。');
        }
        $student_account = $account_logic->getStudentAccount($user_id, $student->id);
        if ( !$student_account ) {
            throw new \Exception('未找到学生账户信息。');
        }
        $balance = $student_account['balance'] ?? 0;
        if ( !$balance || $balance < 1 ) {
            throw new \Exception('对不起，您账户余额不足，请充值。');
        }
        $student->account = $student_account;
        // 获取教师信息
        $teacher = $teacher_logic->getTeacherById($input['teacher_id']);
        if ( !$teacher ) {
            throw new \Exception('未找到教师信息');
        }
        $teacher_account = $account_logic->getTeacherAccount($teacher->user_id, $teacher->id);
        if ( !$teacher_account ) {
            throw new \Exception('未找到教师账户信息。');
        }
        $teacher->account = $teacher_account;
        $course_fee = $teacher['course_fee'] ?? 0;
        if ( !$course_fee || $course_fee < 1 ) {
            throw new \Exception('对不起，您选的教师未设置回课费用，请选择其他教师进行回课。');
        }
        // 判断学生账户余额，是否足够支付本次回课的余额
        if ( $balance < $course_fee ) {
            // 对不起，您账户以支付本次回课的费用，请先充值。
            throw new \Exception('余额不足');
        }
        // 组装回课记录
        $params = [
            'backclass_name' => $input['backclass_name'],
            'backclass_desc' => $input['backclass_desc'] ?? '',
            'backclass_day' => lib()->day(),
            'user_id' => $user_id,
            'student_id' => $student->id,
            'student_name' => $student->student_name,
            'teacher_id' => $input['teacher_id'],
            'teacher_name' => $teacher->teacher_name,
            'course_id' => $teacher->course_id,
            'course_fee' => $teacher->course_fee,
        ];
        // 组装资源
        $attachments = $input['attach'] ?? [];
        if ( empty($attachments) && empty($params['backclass_desc']) ) {
            throw new \Exception('回课文字描述和附件必传一个。');
        }
        if ( !empty($attachments) && count($attachments) > 9 ) {
            throw new \Exception('附件最多只允许上传9个。');
        }
        // 创建回课
        $backclass_id = $this->logic->createBackclass($student, $teacher, $params, $attachments);
        return response()->success(['backclass_id' => $backclass_id]);
    }

    /**
      * @OA\Post(
      *     path="/backclass/addreply",
      *     summary="新增回课评论",
      *     operationId="postAddClassReply",
      *     tags={"BackClass"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="backclass_id",in="query",description="回课id",required=true,
      *         @OA\Schema(type="string") ),
      *     @OA\Parameter( name="reply_content", in="query", description="评论内容",
      *         @OA\Schema(type="string") ),
      *     @OA\Parameter( name="attach", in="query", description="评论附件",
      *         @OA\Schema(type="json") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="reply_id", type="integer", default="评论id"),
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
    public function postAddClassReply(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        $user_role = $auth_user['user_role'] ?? 0;// 用户角色
        if (!$user_id) {
            throw new \Exception('参数不正确');
        }
        $input = $request->all();
        $validator = app('validator')->make($input, [
            'backclass_id' => 'required|min:0',
        ], [
            'backclass_id.required' => '回课id必填',
            'backclass_id.min' => '回课id格式不正确',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            throw new \Exception($errors);
        }
        $backclass = $this->logic->getBackClassById($input['backclass_id']);
        if ( !$backclass ) {
            throw new \Exception('未找到回课信息');
        }
        if ( $backclass->is_closed == 1 ) {
            throw new \Exception('回课已结束。');
        }
        $student_logic = new StudentLogic();
        $teacher_logic = new TeacherLogic();
        $params = [
            'backclass_id' => $input['backclass_id']
        ];
        if ( UserLogic::checkRole($user_role, 'teacher') ) {// 教师端，教师回复
            // 获取教师信息
            $teacher = $teacher_logic->getTeacherInfo($user_id);
            if ( !$teacher ) {
                throw new \Exception('未找到教师信息');
            }
            $params['teacher_id'] = $teacher->id;
            $params['teacher_name'] = $teacher->teacher_name;
        } else {// 学生端，学生回复
            // 获取学生
            $student = $student_logic->getStudentInfo($user_id);
            if ( !$student ) {
                throw new \Exception('未找到学生信息。');
            }
            $params['student_id'] = $student->id;
            $params['student_name'] = $student->student_name;
        }
        // 回课内容
        if ( !empty($input['reply_content']) ) {
            $params['reply_content'] = $input['reply_content'];
        }
        // 组装资源
        $attachments = $input['attach'] ?? [];
        if ( empty($attachments) && empty($params['reply_content']) ) {
            throw new \Exception('回复内容和附件必传一个。');
        }
        if ( !empty($attachments) && count($attachments) > 9 ) {
            throw new \Exception('附件最多只允许上传9个。');
        }
        if ( !empty($attachments) ) {
            $params['is_attachment'] = 1;
        }
        // 添加回复
        $reply_id = $this->logic->addBaclClassReply($backclass, $params, $attachments);
        return response()->success(['reply_id' => $reply_id]);
    }

    /**
      * @OA\Post(
      *     path="/backclass/closed",
      *     summary="结束回课",
      *     operationId="postCloseBackClass",
      *     tags={"BackClass"},
      *     @OA\Parameter( name="Authorization",in="header",description="JWT验证token",@OA\Schema(type="string") ),
      *     @OA\Parameter( name="backclass_id",in="query",description="回课id",required=true,
      *         @OA\Schema(type="string") ),
      *     @OA\Response(
      *         response=200,
      *         description="成功",
      *         @OA\JsonContent(type="object",
      *             @OA\Property(property="code", type="integer", default="200"),
      *             @OA\Property(property="msg", type="string", default="success"),
      *             @OA\Property(property="data", type="array",
      *                 @OA\Items(type="object",
      *                     @OA\Property(property="code", type="integer", default="200"),
      *                     @OA\Property(property="msg", type="string", default="success"),
      *                     @OA\Property(property="data", type="string", default=""),
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
    public function postCloseBackClass(Request $request) {
        $auth_user = $request->auth_user();// 获取授权信息
        $user_id = $auth_user['user_id'] ?? 0;// 用户ID
        if (!$user_id) {
            throw new \Exception('参数不正确');
        }
        $input = $request->all();
        $validator = app('validator')->make($input, [
            'backclass_id' => 'required|min:0',
        ], [
            'backclass_id.required' => '回课id必填',
            'backclass_id.min' => '回课id格式不正确',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            throw new \Exception($errors);
        }
        $backclass = $this->logic->getBackClassById($input['backclass_id']);
        if ( !$backclass ) {
            throw new \Exception('未找到回课信息');
        }
        if ( $backclass->is_closed == 1 ) {
            throw new \Exception('回课已结束。');
        }
        if ( $backclass->is_reply == 0 ) {
            throw new \Exception('回课还未被教师回复过。');
        }
        // 结束课程
        $this->logic->closeBaclClass($backclass);
        return response()->success();
    }
}