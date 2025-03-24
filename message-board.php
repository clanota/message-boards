<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();
header('Content-Type: text/html; charset=utf-8');

try {
    $conn = DB::getInstance();
    
    // 自动创建表结构
    $conn->query("CREATE TABLE IF NOT EXISTS messages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nickname VARCHAR(30) NOT NULL,
        content TEXT NOT NULL,
        ip VARCHAR(45) NOT NULL,
        user_agent VARCHAR(255),
        is_approved TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_approved (is_approved)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 处理表单提交
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        
        if (!rateLimitCheck($ip)) {
            throw new Exception("提交过于频繁，请稍后再试");
        }

        $nickname = sanitizeInput($_POST['nickname'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        
        if (!validateNickname($nickname)) {
            throw new Exception("昵称格式无效（2-20位中英文/数字/下划线）");
        }
        
        if (empty($content) || mb_strlen($content) > 1000) {
            throw new Exception("内容不能为空且不超过1000字");
        }

        $stmt = $conn->prepare("INSERT INTO messages 
                              (nickname, content, ip, user_agent, is_approved)
                              VALUES (?, ?, ?, ?, ?)");
        $approved = $_SESSION['is_admin'] ?? 0;
        $stmt->bind_param("ssssi", $nickname, $content, $ip, $user_agent, $approved);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['flash'] = "留言提交成功".($approved ? "" : "，等待审核");
        }
        header("Location: ".$_SERVER['REQUEST_URI']);
        exit;
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}

// 分页处理
$per_page = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$stmt = $conn->prepare("SELECT SQL_CALC_FOUND_ROWS * 
                       FROM messages 
                       WHERE is_approved = 1 
                       ORDER BY created_at DESC 
                       LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$total = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_pages = ceil($total / $per_page);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>安全留言板</title>
    <link rel="stylesheet" href="https://npm.elemecdn.com/mdui@1.0.2/dist/css/mdui.min.css">
</head>
<body class="mdui-theme-primary-indigo mdui-theme-accent-pink">
<div class="mdui-container mdui-typo">
    <?php if(isset($_SESSION['error'])): ?>
        <div class="mdui-alert mdui-alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']) ?>
    <?php endif; ?>
    
    <form method="post" onsubmit="return submitForm(this)">
        <div class="mdui-textfield">
            <input class="mdui-textfield-input" 
                   type="text" 
                   name="nickname" 
                   placeholder="昵称 (2-20位)"
                   required>
        </div>
        
        <div class="mdui-textfield">
            <textarea class="mdui-textfield-input" 
                      name="content" 
                      rows="4"
                      placeholder="留言内容 (最多1000字)"
                      required></textarea>
        </div>
        
        <button type="submit" class="mdui-btn mdui-btn-block mdui-ripple mdui-color-theme">
            提交留言
        </button>
    </form>

    <div class="mdui-panel" mdui-panel>
        <?php while($row = $result->fetch_assoc()): ?>
        <div class="mdui-panel-item">
            <div class="mdui-panel-item-header">
                <div class="mdui-panel-item-title"><?= $row['nickname'] ?></div>
                <div class="mdui-panel-item-summary">
                    <?= date('Y-m-d H:i', strtotime($row['created_at'])) ?>
                    <?php if($_SESSION['is_admin'] ?? false): ?>
                        <span class="mdui-text-color-theme">[IP: <?= $row['ip'] ?>]</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mdui-panel-item-body">
                <p><?= nl2br($row['content']) ?></p>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <div class="mdui-pagination">
        <?php if($page > 1): ?>
            <a href="?page=<?= $page-1 ?>" class="mdui-btn mdui-ripple">上一页</a>
        <?php endif; ?>
        
        <span class="mdui-pagination-current">第 <?= $page ?> 页 / 共 <?= $total_pages ?> 页</span>
        
        <?php if($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?>" class="mdui-btn mdui-ripple">下一页</a>
        <?php endif; ?>
    </div>
</div>

<script src="https://npm.elemecdn.com/mdui@1.0.2/dist/js/mdui.min.js"></script>
<script>
function submitForm(form) {
    const formData = new FormData(form);
    
    fetch('', {
        method: 'POST',
        body: formData
    }).then(response => {
        if(response.redirected) {
            location.reload();
        }
    }).catch(error => {
        mdui.alert('提交失败: ' + error.message);
    });
    
    return false;
}
</script>
</body>
</html>
