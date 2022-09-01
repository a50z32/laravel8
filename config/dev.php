<?php

return [
    'is_exception'      =>      env('is_exception', 1),// 是否监控错误日志
    'is_check_token'    =>      env('is_check_token', 1),// 是否验证token
    'is_check_sign'     =>      env('is_check_sign', 1),// 是否开启签名判断
    'is_check_time'     =>      env('is_check_time', 1),// 是否开启签名过期判断
    'is_check_role'     =>      env('is_check_role', 1),// 是否验证角色
];
