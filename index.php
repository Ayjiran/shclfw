<?php
require_once 'db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 先检查IP限制
    $ipCheck = checkIPLimit();
    if (!$ipCheck['allowed']) {
        $error = '您今日提交次数已达上限（' . $ipCheck['limit'] . '次），请明天再试！';
    } else {
        $reason = $_POST['reason'] ?? '';
        $reasonDetail = $_POST['reason_detail'] ?? '';
        $detail = $_POST['detail'] ?? '';
        $paymentOrderNo = $_POST['payment_order_no'] ?? '';
        $productName = $_POST['product_name'] ?? '';
        
        // 验证
        if (empty($reason)) {
            $error = '请选择售后原因！';
        } elseif (empty($detail)) {
            $error = '请输入详情信息！';
        } elseif (empty($productName)) {
            $error = '请输入需要售后的产品名！';
        } else {
        // 如果选择了其他问题但没有填写详情
        if ($reason === '其他问题' && empty($reasonDetail)) {
            $error = '请选择"其他问题"后填写具体问题描述！';
        } else {
            try {
                $orderNo = generateOrderNo();
                $stmt = $pdo->prepare("INSERT INTO orders (order_no, reason, reason_detail, detail, payment_order_no, product_name, status) VALUES (?, ?, ?, ?, ?, ?, 0)");
                $stmt->execute([$orderNo, $reason, $reasonDetail, $detail, $paymentOrderNo, $productName]);
                
                // 发送邮件通知管理员
                $orderData = [
                    'order_no' => $orderNo,
                    'reason' => $reason,
                    'reason_detail' => $reasonDetail,
                    'detail' => $detail,
                    'payment_order_no' => $paymentOrderNo,
                    'product_name' => $productName,
                    'create_time' => date('Y-m-d H:i:s')
                ];
                sendEmailNotification($orderData);
                
                // 记录IP提交次数
                recordIPSubmit();
                
                $message = $orderNo;
            } catch (PDOException $e) {
                $error = '提交失败，请稍后重试！';
            }
        }
    }
    }
}

// 获取原因提示文本
function getReasonTip($reason) {
    $tips = [
        '功能BUG' => '请详细描述遇到的BUG情况，包括操作步骤、错误现象等，我们会尽快修复。',
        '充值未到账' => '比如充值10元，最后什么都没有得到。请提供充值时间、支付方式等信息，方便我们核实。',
        '充值过多' => '比如充值本身只要10元，最终却支付了20元甚至更多。请说明实际应付金额和实际支付金额。',
        '未达到预期' => '比如买的时候我想着是如何的，购买以后没有达到相应的效果。请详细描述您的预期和实际差异。',
        '账号注销' => '请确认您了解账号注销后的后果，所有数据将被永久删除且无法恢复。',
        '其他问题' => '请在下方的输入框中详细描述您遇到的其他问题。'
    ];
    return $tips[$reason] ?? '';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>售后服务 - 提交申请</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-headset"></i> 售后服务</h1>
            <p>我们致力于为您提供最优质的售后支持</p>
        </div>
        
        <nav class="nav">
            <a href="index.php" class="active"><i class="fas fa-edit"></i> 提交申请</a>
            <a href="query.php"><i class="fas fa-search"></i> 查询进度</a>
        </nav>
        
        <div class="form-section">
            <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <strong>提交成功！</strong><br>
                您的售后编号是：<span style="font-size: 1.5em; color: #e53935; font-weight: bold;"><?php echo htmlspecialchars($message); ?></span><br>
                请妥善保存此编号，用于查询处理进度。
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="afterSalesForm">
                <div class="form-group">
                    <label><i class="fas fa-list-ul"></i> 选择售后原因 <span class="required">*</span></label>
                    <div class="reason-select-wrapper">
                        <select name="reason" id="reasonSelect" class="form-control reason-select" required>
                            <option value="">请选择售后原因...</option>
                            <option value="功能BUG" <?php echo ($_POST['reason'] ?? '') === '功能BUG' ? 'selected' : ''; ?>>
                                功能BUG
                            </option>
                            <option value="充值未到账" <?php echo ($_POST['reason'] ?? '') === '充值未到账' ? 'selected' : ''; ?>>
                                充值未到账
                            </option>
                            <option value="充值过多" <?php echo ($_POST['reason'] ?? '') === '充值过多' ? 'selected' : ''; ?>>
                                充值过多
                            </option>
                            <option value="未达到预期" <?php echo ($_POST['reason'] ?? '') === '未达到预期' ? 'selected' : ''; ?>>
                                未达到预期
                            </option>
                            <option value="账号注销" <?php echo ($_POST['reason'] ?? '') === '账号注销' ? 'selected' : ''; ?>>
                                账号注销
                            </option>
                            <option value="其他问题" <?php echo ($_POST['reason'] ?? '') === '其他问题' ? 'selected' : ''; ?>>
                                其他问题
                            </option>
                        </select>
                        
                    </div>
                    
                    <!-- 原因提示区域 -->
                    <div id="reasonTip" class="reason-tip">
                        <i class="fas fa-lightbulb"></i>
                        <span id="reasonTipText"></span>
                    </div>
                </div>
                
                <!-- 其他问题详情（默认隐藏） -->
                <div id="otherReasonBox" class="form-group" style="display: none;">
                    <label><i class="fas fa-comment-dots"></i> 请描述具体问题 <span class="required">*</span></label>
                    <textarea name="reason_detail" class="form-control" placeholder="请详细描述您遇到的问题..."><?php echo htmlspecialchars($_POST['reason_detail'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> 详情信息 <span class="required">*</span></label>
                    <textarea name="detail" class="form-control" placeholder="请详细描述您遇到的问题，包括时间、具体情况等，以便我们更快地处理..." required><?php echo htmlspecialchars($_POST['detail'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-box"></i> 需要售后的产品名 <span class="required">*</span></label>
                    <input type="text" name="product_name" class="form-control" placeholder="请输入需要售后的产品名称" value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-receipt"></i> 付款订单号</label>
                    <input type="text" name="payment_order_no" class="form-control" placeholder="选填，如有请填写您的付款订单号" value="<?php echo htmlspecialchars($_POST['payment_order_no'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> 确认提交
                </button>
            </form>
            
            <div class="notice">
                <div class="notice-title">
                    <i class="fas fa-exclamation-triangle"></i> 重要提示
                </div>
                <ul>
                    <li>售后问题仅提交处理，但具体规则要看您充值的项目与详细的产品！</li>
                    <li>请确保填写的信息真实准确，虚假信息可能导致申请被拒绝。</li>
                    <li>提交后请保存好售后编号，用于查询处理进度。</li>
                    <li>一般情况下，我们会在1-3个工作日内处理您的申请。</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p><?php echo getCopyright(); ?></p>
            <p style="margin-top: 8px; font-size: 0.85em;">
            </p>
        </div>
    </div>
    
    <script>
    // 原因提示文本
    const reasonTips = {
        '功能BUG': '请详细描述遇到的BUG情况，包括操作步骤、错误现象等，我们会尽快修复。',
        '充值未到账': '比如充值10元，最后什么都没有得到。请提供充值时间、支付方式等信息，方便我们核实。',
        '充值过多': '比如充值本身只要10元，最终却支付了20元甚至更多。请说明实际应付金额和实际支付金额。',
        '未达到预期': '比如买的时候我想着是如何的，购买以后没有达到相应的效果。请详细描述您的预期和实际差异。',
        '账号注销': '请确认您了解账号注销后的后果，所有数据将被永久删除且无法恢复。',
        '其他问题': '请在下方的"其他问题详情"中详细描述您遇到的具体问题。'
    };
    
    // 监听下拉框变化
    document.getElementById('reasonSelect').addEventListener('change', function() {
        const tipBox = document.getElementById('reasonTip');
        const tipText = document.getElementById('reasonTipText');
        const otherBox = document.getElementById('otherReasonBox');
        
        if (this.value) {
            // 显示提示
            tipText.textContent = reasonTips[this.value];
            tipBox.classList.add('show');
            
            // 处理"其他问题"的额外输入框
            if (this.value === '其他问题') {
                otherBox.style.display = 'block';
            } else {
                otherBox.style.display = 'none';
            }
        } else {
            tipBox.classList.remove('show');
            otherBox.style.display = 'none';
        }
    });
    
    // 页面加载时恢复选中状态
    window.addEventListener('load', function() {
        const select = document.getElementById('reasonSelect');
        if (select.value) {
            select.dispatchEvent(new Event('change'));
        }
    });
    </script>
</body>
</html>
