<?php
session_start();
require_once '../db.php';

// 检查登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// 保存邮件设置
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_email_settings') {
    $emailSender = $_POST['email_sender'] ?? '';
    $emailPass = $_POST['email_pass'] ?? '';
    $emailReceiver = $_POST['email_receiver'] ?? '';
    $emailSmtp = $_POST['email_smtp'] ?? '';
    $emailPort = $_POST['email_port'] ?? '';
    $emailTitle = $_POST['email_title'] ?? '';
    $emailApikey = $_POST['email_apikey'] ?? '';
    
    saveSetting('email_sender', $emailSender);
    saveSetting('email_pass', $emailPass);
    saveSetting('email_receiver', $emailReceiver);
    saveSetting('email_smtp', $emailSmtp);
    saveSetting('email_port', $emailPort);
    saveSetting('email_title', $emailTitle);
    saveSetting('email_apikey', $emailApikey);
    
    $message = '邮件设置保存成功！';
}

// 保存提交限制设置
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_limit_settings') {
    $dailyLimit = $_POST['daily_submit_limit'] ?? '5';
    
    // 确保是数字且大于等于0
    $dailyLimit = intval($dailyLimit);
    if ($dailyLimit < 0) {
        $dailyLimit = 0;
    }
    
    saveSetting('daily_submit_limit', $dailyLimit);
    
    $message = '提交限制设置保存成功！';
}

// 修改密码
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = '请填写所有密码字段！';
    } elseif ($newPassword !== $confirmPassword) {
        $error = '两次输入的新密码不一致！';
    } elseif (strlen($newPassword) < 6) {
        $error = '新密码长度至少为6位！';
    } else {
        // 验证当前密码
        $stmt = $pdo->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($currentPassword, $admin['password'])) {
            $error = '当前密码错误！';
        } else {
            // 更新密码
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $_SESSION['admin_id']])) {
                $message = '密码修改成功！请使用新密码重新登录。';
            } else {
                $error = '密码修改失败，请重试！';
            }
        }
    }
}

// 获取当前管理员信息
$stmt = $pdo->prepare("SELECT username, create_time FROM admin WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$adminInfo = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - 售后管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-headset"></i> 售后管理</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> 概览</a></li>
                <li><a href="orders.php"><i class="fas fa-clipboard-list"></i> 订单管理</a></li>
                <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> 系统设置</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> 关于系统</a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
            </div>
        </aside>
        
        <!-- 主内容 -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> 系统设置</h1>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <!-- 邮件设置 -->
            <div class="settings-form" style="margin-bottom: 30px;">
                <h3><i class="fas fa-envelope"></i> 邮件通知设置</h3>
                <p style="color: #666; margin-bottom: 20px; font-size: 0.9em;">当用户提交售后申请时，系统会自动发送邮件通知管理员</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="save_email_settings">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label><i class="fas fa-envelope" style="margin-right: 5px;"></i>发信人邮箱 (name)</label>
                            <input type="email" name="email_sender" class="form-control" placeholder="例如：admin@example.com" value="<?php echo htmlspecialchars(getSetting('email_sender')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-key" style="margin-right: 5px;"></i>授权码 (pass)</label>
                            <input type="password" name="email_pass" class="form-control" placeholder="邮箱授权码" value="<?php echo htmlspecialchars(getSetting('email_pass')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-inbox" style="margin-right: 5px;"></i>收件人邮箱 (mail)</label>
                            <input type="email" name="email_receiver" class="form-control" placeholder="例如：admin@example.com" value="<?php echo htmlspecialchars(getSetting('email_receiver')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-server" style="margin-right: 5px;"></i>SMTP地址 (stmp)</label>
                            <input type="text" name="email_smtp" class="form-control" placeholder="例如：smtp.qq.com" value="<?php echo htmlspecialchars(getSetting('email_smtp')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-plug" style="margin-right: 5px;"></i>端口 (ssl)</label>
                            <input type="text" name="email_port" class="form-control" placeholder="例如：465" value="<?php echo htmlspecialchars(getSetting('email_port')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-heading" style="margin-right: 5px;"></i>邮件标题 (title)</label>
                            <input type="text" name="email_title" class="form-control" placeholder="例如：新售后申请通知" value="<?php echo htmlspecialchars(getSetting('email_title', '新售后申请通知')); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <label><i class="fas fa-lock" style="margin-right: 5px;"></i>API密钥 (apikey)</label>
                        <input type="text" name="email_apikey" class="form-control" placeholder="请输入API密钥" value="<?php echo htmlspecialchars(getSetting('email_apikey')); ?>">
                    </div>
                    
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-top: 20px; margin-bottom: 20px;">
                        <p style="color: #856404; font-size: 0.9em; margin: 0;">
                            <i class="fas fa-info-circle" style="margin-right: 5px;"></i>
                            <strong>API接口地址：</strong>https://api.ayjrw.cn/API/email.php<br>
                            <small>此功能用于在用户提交售后申请时自动通知管理员邮箱</small>
                        </p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存邮件设置
                    </button>
                </form>
            </div>
            
            <!-- 提交限制设置 -->
            <div class="settings-form" style="margin-bottom: 30px;">
                <h3><i class="fas fa-shield-alt"></i> 提交限制设置</h3>
                <p style="color: #666; margin-bottom: 20px; font-size: 0.9em;">限制每个IP地址每天可以提交的售后申请次数</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="save_limit_settings">
                    
                    <div class="form-group">
                        <label><i class="fas fa-clock" style="margin-right: 5px;"></i>每日提交次数限制</label>
                        <input type="number" name="daily_submit_limit" class="form-control" placeholder="例如：5" min="0" value="<?php echo htmlspecialchars(getSetting('daily_submit_limit', 5)); ?>" style="max-width: 200px;">
                        <small style="color: #999; display: block; margin-top: 8px;">
                            <i class="fas fa-info-circle" style="margin-right: 3px;"></i>
                            每个IP每天最多可提交的次数，设置为 0 表示不限制
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存限制设置
                    </button>
                </form>
            </div>
            
            <!-- 密码修改 -->
            <div class="settings-form">
                <h3><i class="fas fa-user-lock"></i> 修改密码</h3>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 25px;">
                    <p style="margin-bottom: 8px;"><strong>当前用户：</strong><?php echo htmlspecialchars($adminInfo['username']); ?></p>
                    <p style="color: #999; font-size: 0.9em;"><strong>账号创建时间：</strong><?php echo date('Y年m月d日 H:i:s', strtotime($adminInfo['create_time'])); ?></p>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label>当前密码</label>
                        <input type="password" name="current_password" class="form-control" placeholder="请输入当前密码" required>
                    </div>
                    
                    <div class="form-group">
                        <label>新密码</label>
                        <input type="password" name="new_password" class="form-control" placeholder="请输入新密码（至少6位）" required>
                    </div>
                    
                    <div class="form-group">
                        <label>确认新密码</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="请再次输入新密码" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 保存修改
                    </button>
                </form>
            </div>
            
            <div style="margin-top: 30px; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                <h3 style="margin-bottom: 20px; color: #333;"><i class="fas fa-info-circle" style="color: #e53935; margin-right: 10px;"></i>系统信息</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                        <p style="color: #999; font-size: 0.9em; margin-bottom: 5px;">PHP版本</p>
                        <p style="font-size: 1.2em; font-weight: 600;"><?php echo phpversion(); ?></p>
                    </div>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                        <p style="color: #999; font-size: 0.9em; margin-bottom: 5px;">数据库</p>
                        <p style="font-size: 1.2em; font-weight: 600;">MySQL</p>
                    </div>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                        <p style="color: #999; font-size: 0.9em; margin-bottom: 5px;">当前时间</p>
                        <p style="font-size: 1.2em; font-weight: 600;"><?php echo date('Y-m-d H:i:s'); ?></p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
