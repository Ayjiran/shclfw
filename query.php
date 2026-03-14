<?php
require_once 'db.php';

$order = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderNo = trim($_POST['order_no'] ?? '');
    
    if (empty($orderNo)) {
        $error = '请输入售后编号！';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ?");
        $stmt->execute([$orderNo]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            $error = '未找到该售后编号，请检查后重试！';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>售后服务 - 查询进度</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-search"></i> 查询进度</h1>
            <p>输入售后编号查询处理进度与结果</p>
        </div>
        
        <nav class="nav">
            <a href="index.php"><i class="fas fa-edit"></i> 提交申请</a>
            <a href="query.php" class="active"><i class="fas fa-search"></i> 查询进度</a>
        </nav>
        
        <div class="form-section">
            <div class="query-form">
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="text" name="order_no" class="form-control" placeholder="请输入售后编号 (如: AS2024031501A2B3)" value="<?php echo htmlspecialchars($_POST['order_no'] ?? ''); ?>" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> 查询
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($order): ?>
            <div class="query-result">
                <div class="result-header">
                    <div class="order-number">
                        <i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($order['order_no']); ?>
                    </div>
                    <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                        <i class="fas fa-<?php echo $order['status'] == 0 ? 'clock' : ($order['status'] == 1 ? 'spinner fa-spin' : ($order['status'] == 2 ? 'check-circle' : 'times-circle')); ?>"></i>
                        <?php echo getStatusText($order['status']); ?>
                    </span>
                </div>
                
                <div class="info-list">
                    <div class="info-item">
                        <div class="info-label">退款原因</div>
                        <div class="info-value">
                            <i class="fas fa-<?php 
                                echo $order['reason'] == '功能BUG' ? 'bug' : 
                                    ($order['reason'] == '充值未到账' ? 'credit-card' : 
                                    ($order['reason'] == '充值过多' ? 'coins' : 
                                    ($order['reason'] == '未达到预期' ? 'thumbs-down' : 
                                    ($order['reason'] == '账号注销' ? 'user-times' : 'question-circle')))); 
                            ?>" style="color: #e53935; margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($order['reason']); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['reason_detail'])): ?>
                    <div class="info-item">
                        <div class="info-label">问题详情</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($order['reason_detail'])); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <div class="info-label">详细描述</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($order['detail'])); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">付款订单号</div>
                        <div class="info-value" style="font-family: monospace; background: #f5f5f5; padding: 8px 12px; border-radius: 6px;">
                            <?php echo htmlspecialchars($order['payment_order_no']); ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">产品名称</div>
                        <div class="info-value">
                            <i class="fas fa-box" style="color: #e53935; margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($order['product_name']); ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">提交时间</div>
                        <div class="info-value">
                            <i class="far fa-calendar-alt" style="color: #e53935; margin-right: 5px;"></i>
                            <?php echo date('Y年m月d日 H:i:s', strtotime($order['create_time'])); ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">更新时间</div>
                        <div class="info-value">
                            <i class="far fa-clock" style="color: #e53935; margin-right: 5px;"></i>
                            <?php echo date('Y年m月d日 H:i:s', strtotime($order['update_time'])); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['result'])): ?>
                    <div class="info-item" style="background: #f8f9fa;">
                        <div class="info-label">处理结果</div>
                        <div class="info-value" style="color: #2e7d32; font-weight: 600;">
                            <i class="fas fa-check-circle" style="margin-right: 5px;"></i>
                            <?php echo nl2br(htmlspecialchars($order['result'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="notice" style="margin-top: 30px;">
                <div class="notice-title">
                    <i class="fas fa-info-circle"></i> 进度说明
                </div>
                <ul>
                    <li><strong>待处理</strong>：您的申请已收到，等待客服人员处理</li>
                    <li><strong>处理中</strong>：客服人员正在核实您的问题</li>
                    <li><strong>已完成</strong>：问题已解决，处理结果请查看上方</li>
                    <li><strong>已拒绝</strong>：申请不符合售后条件，已被拒绝</li>
                </ul>
            </div>
            <?php else: ?>
            <div class="notice" style="text-align: center; padding: 50px 25px;">
                <i class="fas fa-search" style="font-size: 4em; color: #e0e0e0; margin-bottom: 20px;"></i>
                <h3 style="color: #999; margin-bottom: 10px;">输入售后编号查询进度</h3>
                <p style="color: #bbb;">售后编号格式如：AS20240315XXXXXX</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p><?php echo getCopyright(); ?></p>
            <p style="margin-top: 8px; font-size: 0.85em;">
            </p>
        </div>
    </div>
</body>
</html>
