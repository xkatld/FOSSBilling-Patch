# FOSSBilling-Patch

FOSSBilling 功能扩展补丁包，免费插件请勿转卖，二开请署名原作者。

## 项目信息

- **作者**: xkatld
- **项目地址**: https://github.com/xkatld/FOSSBilling-Patch
- **版本**: v1.0.0
- **适用版本**: FOSSBilling 0.7.x

---

## 补丁包包含插件

### 1. MailFlow邮箱API by xkatld

通过 MailFlow API 发送邮件的第三方邮件服务集成。

**修改文件**:
- `library/FOSSBilling/Mail.php`
- `modules/Email/html_admin/mod_email_settings.html.twig`

**配置项**:
- MailFlow API 地址
- MailFlow API 密钥
- 超时时间

---

### 2. 易支付-支付宝 by xkatld

通过易支付平台完成支付宝收款的支付网关插件。

**新增文件**:
- `library/Payment/Adapter/Epay.php`

**修改文件**:
- `modules/Invoice/html_admin/mod_invoice_gateway.html.twig`

**配置项**:
- 易支付网关地址
- 商户ID
- 商户密钥

---

### 3. 汉化邮箱插件 by xkatld

将 FOSSBilling 的所有邮件模板汉化为中文。

**新增文件**: 共 62 个邮件模板
- `modules/Client/html_email/` - 客户相关邮件 (4个)
- `modules/Invoice/html_email/` - 账单相关邮件 (4个)
- `modules/Support/html_email/` - 工单相关邮件 (8个)
- `modules/Staff/html_email/` - 员工通知邮件 (10个)
- `modules/Servicehosting/html_email/` - 主机服务邮件 (5个)
- `modules/Servicedomain/html_email/` - 域名服务邮件 (4个)
- `modules/Servicelicense/html_email/` - 授权服务邮件 (5个)
- `modules/Servicecustom/html_email/` - 自定义服务邮件 (5个)
- `modules/Serviceapikey/html_email/` - API密钥服务邮件 (5个)
- `modules/Servicemembership/html_email/` - 会员服务邮件 (5个)
- `modules/Servicedownloadable/html_email/` - 下载服务邮件 (1个)
- `modules/Servicelxdapi/html_email/` - LXDAPI服务邮件 (4个)
- `modules/Email/html_email/` - 邮件测试 (1个)

**汉化内容**:
- 邮件主题
- 邮件正文
- 按钮/链接文字
- 提示信息

---

### 4. FOSSBilling-LXDAPI对接插件 by xkatld

LXDAPI 容器管理插件，用于对接 LXD 容器服务。

**版本**: v2.0.2  
**项目地址**: https://github.com/xkatld/lxdapi-web-server

**新增文件**:
- `modules/Servicelxdapi/manifest.json` - 插件清单
- `modules/Servicelxdapi/Service.php` - 核心服务类
- `modules/Servicelxdapi/Api/Admin.php` - 管理员API
- `modules/Servicelxdapi/Api/Client.php` - 客户端API
- `modules/Servicelxdapi/Controller/Admin.php` - 管理员控制器
- `modules/Servicelxdapi/Controller/Client.php` - 客户端控制器
- `modules/Servicelxdapi/html_admin/` - 管理后台界面 (5个)
- `modules/Servicelxdapi/html_client/` - 客户端界面 (1个)
- `modules/Servicelxdapi/html_email/` - 邮件模板 (4个)

**主要功能**:
- 服务器组管理（支持最少负载、轮询、随机分配策略）
- 服务器管理（主机名、端口、API密钥、SSL验证、容器上限）
- 容器生命周期管理（创建、启动、停止、重启、删除、重装）
- 资源配置（CPU、内存、硬盘、带宽、流量限制）
- 密码重置和流量重置
- Web控制台访问
- 系统镜像模板管理

**数据库表**:
- `service_lxdapi` - 服务实例表
- `service_lxdapi_server` - 服务器表
- `service_lxdapi_server_group` - 服务器组表

**产品配置项**:
- CPU 核心数
- 内存大小 (MB)
- 硬盘大小 (MB)
- 系统镜像
- 入站带宽 (Mbit)
- 出站带宽 (Mbit)
- 月流量限制 (GB)
- IPv4/IPv6 地址数量

---

## 安装说明

```bash
# 将补丁文件复制到 FOSSBilling 安装目录
cp -r library/ /your/fossbilling/
cp -r modules/ /your/fossbilling/

# 进入 FOSSBilling 管理后台
# 访问 Extensions → Core 启用 Servicelxdapi 模块（如需使用 LXDAPI 插件）
```

---

## 使用配置

### MailFlow邮箱API
1. 管理后台 → **Settings** → **Email** → **Email settings**
2. 选择 **MailFlow**
3. 填写 API 地址、API 密钥、超时时间
4. 保存配置

### 易支付-支付宝
1. 管理后台 → **Configuration** → **Payment gateways**
2. 点击 **New payment gateway**，选择 **Epay**
3. 填写易支付网关地址、商户ID、商户密钥
4. 启用网关

### LXDAPI容器管理
1. 管理后台 → **Extensions** → **Core**，启用 **Servicelxdapi** 模块
2. 进入 **Servicelxdapi** → **Servers** 添加服务器
3. 创建服务器组并分配服务器
4. 创建产品，选择 **Servicelxdapi** 类型
5. 配置产品参数（CPU、内存、硬盘等）
