# 青龙面板脚本库（PHP 版）

一个青龙面板脚本共享站（脚本上传 / 审核 / 下载 / 开放接口），纯原生 PHP + SQLite，无需数据库服务，上传即可用。

## 功能

- 脚本列表 / 关键词搜索 / 分页（只展示「已上架」）
- 上传脚本：CSRF 防护 + SVG 算术验证码 + 扩展名/大小白名单校验
- 敏感词扫描：简介命中引流/代刷/博彩等词自动转「待人工审核」
- 详情页源码预览（前 200 行），下载计数、防直链（上传目录禁 Web 访问）
- JSON 开放接口 `api.php?action=list&page=1&size=10&q=关键词`
- 管理后台：按状态筛选 / 通过 / 拒绝 / 删除（同时删文件），记录上传者 IP

## 目录结构

```
qinglong-script-hub/
├── config.php     # 站点配置（站名/密码/限制/敏感词）
├── lib.php        # 公共函数 + 建库 + 页面布局
├── index.php      # 列表 + 搜索
├── upload.php     # 上传
├── detail.php     # 详情 + 源码预览
├── download.php   # 计数下载
├── api.php        # 只读 JSON 接口
├── captcha.php    # SVG 验证码
├── admin.php      # 管理后台
├── data/          # SQLite 库（自动创建）
└── uploads/       # 脚本文件（自动创建，含 .htaccess 拒绝直访）
```

## 部署

要求：PHP 7.4+（含 pdo_sqlite、session、mbstring），无需 Composer。

```bash
# 本地试跑
php -S 0.0.0.0:8080 -t qinglong-script-hub/
```

- **Apache**：`data/`、`uploads/` 内的 .htaccess 已自动生效。
- **Nginx**：在 server 块加：

```nginx
location ~* ^/(data|uploads)/ { deny all; }
```

## 上线前必改

1. `config.php` 里的 `admin_password`（默认 `changeme123`）。
2. 生产环境建议给管理路径再加一层 HTTP Basic Auth 或 IP 白名单。

## 免责声明

脚本均来自网友上传，平台不做安全审计，仅供学习交流；请自行检查代码安全性，24 小时内删除。
