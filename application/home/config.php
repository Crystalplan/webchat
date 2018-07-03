<?php
//配置文件
return [
    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------
    // 默认控制器名
    'default_controller' => 'login',
    // 默认操作名
    'default_action' => 'index',
    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template' => [
        // 模板引擎类型 支持 php think 支持扩展
        'type' => 'Think',
        // 模板路径
        'view_path' => 'templates/home/',
        // 模板后缀
        'view_suffix' => 'html',
        // 模板文件名分隔符
        'view_depr' => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin' => '{',
        // 模板引擎普通标签结束标记
        'tpl_end' => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end' => '}',
    ],

    // 视图输出字符串内容替换
    'view_replace_str' => [
        '__ROOT__' => str_replace('/index.php', '', \think\Request::instance()->root()),
        '__APP__' => \think\Request::instance()->root(),
        '__STATIC__' => str_replace('/index.php', '', \think\Request::instance()->root()) . '/static',
        '__VIEWPATH__' => str_replace('/index.php', '', \think\Request::instance()->root()) . '/templates/home',
    ],
];