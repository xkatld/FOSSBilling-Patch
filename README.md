# FOSSBilling-Patch

FOSSBilling 功能扩展补丁包 , 免费插件请勿转卖 , 二开请署名原作者。

## 信息

- **作者**: xkatld
- **项目地址**: https://github.com/xkatld/FOSSBilling-Patch
- **版本**: v1.0.0
- **适用版本**: FOSSBilling 0.6.x

## 包含功能

### 1. MailFlow邮箱API by xkatld

通过 MailFlow API 发送邮件。

**修改文件**:
- `library/FOSSBilling/Mail.php`
- `modules/Email/html_admin/mod_email_settings.html.twig`

**配置项**:
- MailFlow API 地址
- MailFlow API 密钥
- 超时时间

### 2. 易支付-支付宝 by xkatld

通过易支付平台完成支付宝收款。

**新增文件**:
- `library/Payment/Adapter/Epay.php`

**配置项**:
- 易支付网关地址
- 商户ID
- 商户密钥

## 安装

```bash
cp -r library/ /your/fossbilling/
cp -r modules/ /your/fossbilling/
```

## 配置

### MailFlow邮箱API
1. 管理后台 → Settings → Email → Email settings
2. 选择 MailFlow
3. 填写配置并保存

### 易支付-支付宝
1. 管理后台 → Configuration → Payment gateways
2. 新建支付网关，选择 Epay
3. 填写配置并启用
