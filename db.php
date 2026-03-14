<?php
// 数据库配置文件
$host = 'localhost';
$dbname = 'shcl';
$username = 'shcl';
$password = 'shcl';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 创建数据库
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    
    // 创建售后订单表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_no` VARCHAR(20) NOT NULL UNIQUE,
        `reason` VARCHAR(50) NOT NULL,
        `reason_detail` TEXT,
        `detail` TEXT NOT NULL,
        `payment_order_no` VARCHAR(100) DEFAULT NULL,
        `product_name` VARCHAR(200) NOT NULL,
        `status` TINYINT DEFAULT 0 COMMENT '0=待处理,1=处理中,2=已完成,3=已拒绝',
        `result` TEXT,
        `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_order_no` (`order_no`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 创建管理员表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 插入默认管理员账号 (admin/admin123)
    $stmt = $pdo->prepare("SELECT id FROM admin WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->rowCount() == 0) {
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)")
            ->execute(['admin', $default_password]);
    }
    
    // 创建设置表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 创建IP提交记录表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ip_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ip_address` VARCHAR(45) NOT NULL,
        `submit_date` DATE NOT NULL,
        `submit_count` INT DEFAULT 1,
        `create_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `update_time` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `idx_ip_date` (`ip_address`, `submit_date`),
        INDEX `idx_date` (`submit_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 获取设置值
function getSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// 保存设置值
function saveSetting($key, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        return $stmt->execute([$key, $value]);
    } catch (PDOException $e) {
        return false;
    }
}

// 获取客户端真实IP
function getClientIP() {
    $ipHeaders = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
            return $ip;
        }
    }
    return '0.0.0.0';
}

// 检查IP提交次数限制
function checkIPLimit() {
    global $pdo;
    
    // 获取每日限制次数（默认5次）
    $dailyLimit = (int)getSetting('daily_submit_limit', 5);
    
    // 如果设置为0，表示不限制
    if ($dailyLimit <= 0) {
        return ['allowed' => true, 'remaining' => -1];
    }
    
    $ip = getClientIP();
    $today = date('Y-m-d');
    
    try {
        // 查询今日提交次数
        $stmt = $pdo->prepare("SELECT submit_count FROM ip_logs WHERE ip_address = ? AND submit_date = ?");
        $stmt->execute([$ip, $today]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $currentCount = $result ? (int)$result['submit_count'] : 0;
        $remaining = $dailyLimit - $currentCount;
        
        if ($currentCount >= $dailyLimit) {
            return ['allowed' => false, 'remaining' => 0, 'limit' => $dailyLimit];
        }
        
        return ['allowed' => true, 'remaining' => $remaining, 'limit' => $dailyLimit];
    } catch (PDOException $e) {
        return ['allowed' => true, 'remaining' => -1];
    }
}

// 记录IP提交
function recordIPSubmit() {
    global $pdo;
    
    $dailyLimit = (int)getSetting('daily_submit_limit', 5);
    if ($dailyLimit <= 0) {
        return true;
    }
    
    $ip = getClientIP();
    $today = date('Y-m-d');
    
    try {
        // 使用INSERT ... ON DUPLICATE KEY UPDATE
        $stmt = $pdo->prepare("INSERT INTO ip_logs (ip_address, submit_date, submit_count) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE submit_count = submit_count + 1");
        return $stmt->execute([$ip, $today]);
    } catch (PDOException $e) {
        return false;
    }
}

// 发送邮件通知
function sendEmailNotification($orderData) {
    // 获取邮件配置
    $senderEmail = getSetting('email_sender');
    $senderPass = getSetting('email_pass');
    $receiverEmail = getSetting('email_receiver');
    $smtpServer = getSetting('email_smtp');
    $smtpPort = getSetting('email_port');
    $apiKey = getSetting('email_apikey');
    $emailTitle = getSetting('email_title', '新售后申请通知');
    
    // 检查是否配置了邮件
    if (empty($senderEmail) || empty($receiverEmail) || empty($apiKey)) {
        return ['success' => false, 'message' => '邮件配置不完整'];
    }
    
    // 构建邮件内容
    $content = "有新的售后申请提交，请尽快处理。\n\n";
    $content .= "售后编号：{$orderData['order_no']}\n";
    $content .= "售后原因：{$orderData['reason']}\n";
    if (!empty($orderData['reason_detail'])) {
        $content .= "问题详情：{$orderData['reason_detail']}\n";
    }
    $content .= "详细描述：{$orderData['detail']}\n";
    $content .= "产品名称：{$orderData['product_name']}\n";
    if (!empty($orderData['payment_order_no'])) {
        $content .= "付款订单号：{$orderData['payment_order_no']}\n";
    }
    $content .= "提交时间：{$orderData['create_time']}\n";
    
    // 构建API URL
    $apiUrl = "https://api.ayjrw.cn/API/email.php";
    $params = [
        'name' => $senderEmail,
        'pass' => $senderPass,
        'mail' => $receiverEmail,
        'stmp' => $smtpServer,
        'ssl' => $smtpPort,
        'title' => $emailTitle,
        'content' => $content,
        'apikey' => $apiKey
    ];
    
    // 使用cURL发送请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response !== false) {
        return ['success' => true, 'message' => '邮件发送成功'];
    } else {
        return ['success' => false, 'message' => '邮件发送失败，HTTP代码：' . $httpCode];
    }
}

// 生成售后编号
function generateOrderNo() {
    return 'AS' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

// 获取状态文本
function getStatusText($status) {
    $statusMap = [
        0 => '待处理',
        1 => '处理中',
        2 => '已完成',
        3 => '已拒绝'
    ];
    return $statusMap[$status] ?? '未知';
}

// 获取状态样式类
function getStatusClass($status) {
    $classMap = [
        0 => 'status-pending',
        1 => 'status-processing',
        2 => 'status-completed',
        3 => 'status-rejected'
    ];
    return $classMap[$status] ?? 'status-pending';
}

// 系统信息配置
function getSystemInfo() {
    return [
        'company' => '长春幻隐网络科技有限公司',
        'website' => 'www.ayjrw.cn',
        'support_url' => 'ayjrw.cn/lx',
        'system_name' => '售后服务管理系统',
        'version' => '1.0.0'
    ];
}

// 获取版权信息
function getCopyright() {
    $info = getSystemInfo();
    return '&copy; ' . date('Y') . ' ' . $info['company'] . ' - All Rights Reserved';
}
?>
