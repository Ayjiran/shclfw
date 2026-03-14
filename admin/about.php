<?php
session_start();
require_once '../db.php';

// 检查登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$systemInfo = getSystemInfo();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>关于系统 - 售后管理系统</title>
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
                <li><a href="settings.php"><i class="fas fa-cog"></i> 系统设置</a></li>
                <li><a href="about.php" class="active"><i class="fas fa-info-circle"></i> 关于系统</a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
            </div>
        </aside>
        
        <!-- 主内容 -->
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-info-circle"></i> 关于系统</h1>
            </div>
            
            <!-- 系统信息卡片 -->
            <div class="data-table" style="margin-bottom: 30px;">
                <div class="table-header">
                    <h3><i class="fas fa-info-circle"></i> 系统信息</h3>
                </div>
                <div style="padding: 30px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
                        <div style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 25px; border-radius: 15px; text-align: center;">
                            <div style="font-size: 3em; margin-bottom: 15px;"><i class="fas fa-building" style="color: #667eea;"></i></div>
                            <div style="color: #666; font-size: 0.9em; margin-bottom: 8px;">开发公司</div>
                            <div style="font-size: 1.3em; font-weight: 700; color: #333;"><?php echo $systemInfo['company']; ?></div>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 15px; text-align: center; color: white;">
                            <div style="font-size: 3em; margin-bottom: 15px;"><i class="fas fa-globe"></i></div>
                            <div style="opacity: 0.9; font-size: 0.9em; margin-bottom: 8px;">官方网站</div>
                            <a href="https://<?php echo $systemInfo['website']; ?>" target="_blank" style="font-size: 1.3em; font-weight: 700; color: white; text-decoration: none;">
                                <?php echo $systemInfo['website']; ?>
                            </a>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, #e53935 0%, #c62828 100%); padding: 25px; border-radius: 15px; text-align: center; color: white;">
                            <div style="font-size: 3em; margin-bottom: 15px;"><i class="fas fa-headset"></i></div>
                            <div style="opacity: 0.9; font-size: 0.9em; margin-bottom: 8px;">联系客服</div>
                            <a href="https://<?php echo $systemInfo['support_url']; ?>" target="_blank" style="font-size: 1.1em; font-weight: 600; color: white; text-decoration: none;">
                                <?php echo $systemInfo['support_url']; ?> →
                            </a>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 25px; border-radius: 15px; text-align: center; color: white;">
                            <div style="font-size: 3em; margin-bottom: 15px;"><i class="fas fa-box-open"></i></div>
                            <div style="opacity: 0.9; font-size: 0.9em; margin-bottom: 8px;">系统版本</div>
                            <div style="font-size: 1.3em; font-weight: 700;">V<?php echo $systemInfo['version']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 版权声明 -->
            <div class="data-table" style="margin-bottom: 30px;">
                <div class="table-header">
                    <h3><i class="fas fa-copyright"></i> 版权声明</h3>
                </div>
                <div style="padding: 30px; background: #f8f9fa; border-radius: 0 0 16px 16px;">
                    <div style="text-align: center; color: #666; line-height: 2;">
                        <p style="font-size: 1.1em; margin-bottom: 15px;">
                            <strong><?php echo $systemInfo['system_name']; ?></strong>
                        </p>
                        <p>本系统由 <strong><?php echo $systemInfo['company']; ?></strong> 开发并拥有全部知识产权</p>
                        <p>未经授权，禁止复制、修改、传播本系统的全部或部分内容</p>
                        <p style="margin-top: 20px; color: #999;">
                            <?php echo getCopyright(); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- 技术支持 -->
            <div class="data-table">
                <div class="table-header">
                    <h3><i class="fas fa-tools"></i> 技术支持</h3>
                </div>
                <div style="padding: 30px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div style="background: white; border: 2px solid #e0e0e0; border-radius: 12px; padding: 25px; text-align: center; transition: all 0.3s;">
                            <div style="font-size: 2.5em; margin-bottom: 15px;"><i class="fas fa-book" style="color: #667eea;"></i></div>
                            <h4 style="margin-bottom: 10px; color: #333;">使用文档</h4>
                            <p style="color: #999; font-size: 0.9em; margin-bottom: 15px;">查看系统使用说明和操作指南</p>
                            <a href="../docs/操作文档.md" target="_blank" class="btn btn-primary btn-sm">查看文档</a>
                        </div>
                        
                        <div style="background: white; border: 2px solid #e0e0e0; border-radius: 12px; padding: 25px; text-align: center; transition: all 0.3s;">
                            <div style="font-size: 2.5em; margin-bottom: 15px;"><i class="fas fa-comments" style="color: #e53935;"></i></div>
                            <h4 style="margin-bottom: 10px; color: #333;">在线客服</h4>
                            <p style="color: #999; font-size: 0.9em; margin-bottom: 15px;">遇到问题？联系我们的客服团队</p>
                            <a href="https://<?php echo $systemInfo['support_url']; ?>" target="_blank" class="btn btn-primary btn-sm">联系客服</a>
                        </div>
                        
                        <div style="background: white; border: 2px solid #e0e0e0; border-radius: 12px; padding: 25px; text-align: center; transition: all 0.3s;">
                            <div style="font-size: 2.5em; margin-bottom: 15px;"><i class="fas fa-globe" style="color: #11998e;"></i></div>
                            <h4 style="margin-bottom: 10px; color: #333;">访问官网</h4>
                            <p style="color: #999; font-size: 0.9em; margin-bottom: 15px;">了解更多产品和服务信息</p>
                            <a href="https://<?php echo $systemInfo['website']; ?>" target="_blank" class="btn btn-primary btn-sm">访问官网</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
