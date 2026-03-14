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

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderId = intval($_POST['order_id'] ?? 0);
    
    if ($action === 'update_status') {
        $status = intval($_POST['status'] ?? 0);
        $result = $_POST['result'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, result = ? WHERE id = ?");
        if ($stmt->execute([$status, $result, $orderId])) {
            $message = '状态更新成功！';
        } else {
            $error = '更新失败，请重试！';
        }
    }
}

// 获取筛选参数
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

// 构建查询
$where = [];
$params = [];

if ($statusFilter !== '' && $statusFilter !== 'all') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(order_no LIKE ? OR product_name LIKE ? OR payment_order_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// 获取总数
$countSql = "SELECT COUNT(*) FROM orders $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// 获取订单列表
$offset = ($page - 1) * $perPage;
$sql = "SELECT * FROM orders $whereSql ORDER BY create_time DESC LIMIT $offset, $perPage";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取当前查看的订单详情
$viewOrder = null;
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $viewOrder = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单管理 - 售后管理系统</title>
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
                <li><a href="orders.php" class="active"><i class="fas fa-clipboard-list"></i> 订单管理</a></li>
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
                <h1><i class="fas fa-clipboard-list"></i> 订单管理</h1>
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
            
            <!-- 筛选栏 -->
            <div class="data-table" style="margin-bottom: 25px;">
                <div class="table-header">
                    <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                        <select name="status" class="form-control" style="width: auto;">
                            <option value="all">全部状态</option>
                            <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>待处理</option>
                            <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>处理中</option>
                            <option value="2" <?php echo $statusFilter === '2' ? 'selected' : ''; ?>>已完成</option>
                            <option value="3" <?php echo $statusFilter === '3' ? 'selected' : ''; ?>>已拒绝</option>
                        </select>
                        <input type="text" name="search" class="form-control" placeholder="搜索售后编号/产品名/订单号" value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 250px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> 筛选
                        </button>
                        <a href="orders.php" class="btn btn-secondary">重置</a>
                    </form>
                </div>
            </div>
            
            <!-- 订单列表 -->
            <div class="data-table">
                <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>暂无订单</h3>
                    <p>没有找到符合条件的售后申请</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>售后编号</th>
                            <th>退款原因</th>
                            <th>产品名称</th>
                            <th>付款订单号</th>
                            <th>状态</th>
                            <th>提交时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><span class="order-no"><?php echo htmlspecialchars($order['order_no']); ?></span></td>
                            <td><?php echo htmlspecialchars($order['reason']); ?></td>
                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                            <td style="font-family: monospace; font-size: 0.9em;"><?php echo htmlspecialchars($order['payment_order_no']); ?></td>
                            <td>
                                <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                                    <?php echo getStatusText($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['create_time'])); ?></td>
                            <td>
                                <div class="action-btns">
                                    <button type="button" class="btn-sm btn-view" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-eye"></i> 查看
                                    </button>
                                    <?php if ($order['status'] < 2): ?>
                                    <button type="button" class="btn-sm btn-process" onclick="processOrder(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-edit"></i> 处理
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                    <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page+1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- 查看订单模态框 -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> 订单详情</h3>
                <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- 动态加载内容 -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('viewModal')">关闭</button>
            </div>
        </div>
    </div>
    
    <!-- 处理订单模态框 -->
    <div id="processModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> 处理订单</h3>
                <button class="modal-close" onclick="closeModal('processModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body" id="processModalBody">
                    <!-- 动态加载内容 -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('processModal')">取消</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    const orders = <?php echo json_encode($orders); ?>;
    
    function viewOrder(id) {
        const order = orders.find(o => o.id == id);
        if (!order) return;
        
        const statusClass = {
            0: 'status-pending',
            1: 'status-processing',
            2: 'status-completed',
            3: 'status-rejected'
        }[order.status];
        
        const statusText = {
            0: '待处理',
            1: '处理中',
            2: '已完成',
            3: '已拒绝'
        }[order.status];
        
        document.getElementById('viewModalBody').innerHTML = `
            <div class="detail-item">
                <div class="detail-label">售后编号</div>
                <div class="detail-value"><span class="order-no">${order.order_no}</span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">退款原因</div>
                <div class="detail-value">${order.reason}</div>
            </div>
            ${order.reason_detail ? `
            <div class="detail-item">
                <div class="detail-label">问题详情</div>
                <div class="detail-value">${order.reason_detail.replace(/\n/g, '<br>')}</div>
            </div>
            ` : ''}
            <div class="detail-item">
                <div class="detail-label">详细描述</div>
                <div class="detail-value">${order.detail.replace(/\n/g, '<br>')}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">付款订单号</div>
                <div class="detail-value" style="font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 6px;">${order.payment_order_no}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">产品名称</div>
                <div class="detail-value">${order.product_name}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">当前状态</div>
                <div class="detail-value"><span class="status-badge ${statusClass}">${statusText}</span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">提交时间</div>
                <div class="detail-value">${order.create_time}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">更新时间</div>
                <div class="detail-value">${order.update_time}</div>
            </div>
            ${order.result ? `
            <div class="detail-item" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
                <div class="detail-label">处理结果</div>
                <div class="detail-value" style="color: #2e7d32; font-weight: 600;">${order.result.replace(/\n/g, '<br>')}</div>
            </div>
            ` : ''}
        `;
        
        document.getElementById('viewModal').classList.add('show');
    }
    
    function processOrder(id) {
        const order = orders.find(o => o.id == id);
        if (!order) return;
        
        const statusOptions = [
            { value: 0, text: '待处理' },
            { value: 1, text: '处理中' },
            { value: 2, text: '已完成' },
            { value: 3, text: '已拒绝' }
        ];
        
        let statusSelect = statusOptions.map(opt => 
            `<option value="${opt.value}" ${order.status == opt.value ? 'selected' : ''}>${opt.text}</option>`
        ).join('');
        
        document.getElementById('processModalBody').innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="${order.id}">
            <div class="form-group">
                <label>售后编号</label>
                <input type="text" class="form-control" value="${order.order_no}" readonly>
            </div>
            <div class="form-group">
                <label>产品名称</label>
                <input type="text" class="form-control" value="${order.product_name}" readonly>
            </div>
            <div class="form-group">
                <label>当前状态</label>
                <select name="status" class="form-control">
                    ${statusSelect}
                </select>
            </div>
            <div class="form-group">
                <label>处理结果 / 备注</label>
                <textarea name="result" class="form-control" placeholder="请输入处理结果或备注信息...">${order.result || ''}</textarea>
            </div>
        `;
        
        document.getElementById('processModal').classList.add('show');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('show');
    }
    
    // 点击模态框外部关闭
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });
    </script>
</body>
</html>
