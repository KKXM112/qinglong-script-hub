<?php
// config.php —— 站点配置
return [
    'site_name' => '青龙面板脚本库',
    'page_size' => 10,

    // 管理密码（正式部署务必修改！）
    'admin_password' => 'changeme123',

    // 上传限制
    'max_upload_bytes' => 512 * 1024,          // 单文件最大 512KB
    'allowed_ext'      => ['py', 'js', 'ts', 'sh'],

    // 敏感词：简介/文件名命中后状态强制为 pending（人工审核）
    'ban_words' => [
        'qq', '微信', 'vx', 'wx', 'v信', 'tg', '飞机', '频道',
        '引流', '代刷', '刷单', '兼职', '加群', '扫码', '二维码',
        '博彩', '彩票', '返利', '招代理', '招商',
    ],

    // 列表页每条简介最多显示的字符数
    'desc_preview_len' => 160,
];
