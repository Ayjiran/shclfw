<?php
session_start();
require_once '../db.php';

// 检查登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取统计数据
$stats = [
    'pending' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 0")->fetchColumn(),
    'processing' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 1")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN (2, 3)")->fetchColumn(),
    'total' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn()
];

// 获取最近订单
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY create_time DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台首页 - 售后管理系统</title>
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
                <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> 概览</a></li>
                <li><a href="orders.php"><i class="fas fa-clipboard-list"></i> 订单管理</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> 系统设置</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> 关于系统</a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
            </div>
        </aside>
        
        <!-- 主内容 -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-tachometer-alt"></i> 概览</h1>
            </div>
            
            <!-- 统计卡片 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>待处理</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon processing">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['processing']; ?></h3>
                        <p>处理中</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['completed']; ?></h3>
                        <p>已处理</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-list-ol"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>总订单</p>
                    </div>
                </div>
            </div>
            
            <!-- 最近订单 -->
            <div class="data-table">
                <div class="table-header">
                    <h3><i class="fas fa-history"></i> 最近提交的售后申请</h3>
                    <a href="orders.php" class="btn btn-primary btn-sm">查看全部</a>
                </div>
                
                <?php if (empty($recentOrders)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>暂无订单</h3>
                    <p>还没有收到任何售后申请</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>售后编号</th>
                            <th>退款原因</th>
                            <th>产品名称</th>
                            <th>状态</th>
                            <th>提交时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><span class="order-no"><?php echo htmlspecialchars($order['order_no']); ?></span></td>
                            <td><?php echo htmlspecialchars($order['reason']); ?></td>
                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                            <td>
                                <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                                    <?php echo getStatusText($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('m-d H:i', strtotime($order['create_time'])); ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="btn-sm btn-view">
                                        <i class="fas fa-eye"></i> 查看
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
