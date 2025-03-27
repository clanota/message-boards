<?php error_reporting(0); ?>
<?php 
// 数据库配置 
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "database";
 
// 创建数据库连接 
$conn = new mysqli($servername, $username, $password, $dbname);
 
// 检查连接 
if ($conn->connect_error) {
    die('<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
        <meta name="renderer" content="webkit">
        <link rel="stylesheet" href="https://npm.elemecdn.com/mdui@1.0.2/dist/css/mdui.min.css">
        <title>Database Error!</title>
    </head>
    <body>
        <div class="mdui-container"><br>
            <div class="mdui-card">
                <div class="mdui-card-primary">
                    <div class="mdui-card-primary-title">
                        唔...数据库出问题了！
                    </div>
                    <div class="mdui-card-primary-subtitle">' . $conn->connect_error .'                    </div>
                </div>
            </div>
        </div>
        <br>
        <script src="https://npm.elemecdn.com/mdui@1.0.2/dist/js/mdui.min.js"></script>
    </body>
</html>');
}
 
// 处理表单提交 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nickname = htmlspecialchars($_POST['nickname']);
    $content = htmlspecialchars($_POST['content']);
    
    if (!empty($nickname) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO messages (nickname, content) VALUES (?, ?)");
        $stmt->bind_param("ss", $nickname, $content);
        $stmt->execute();
        $stmt->close();
    }
}
 
// 创建留言表（如果不存在）
$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(30) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
)");
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
        <meta name="renderer" content="webkit">
        <link rel="stylesheet" href="https://npm.elemecdn.com/mdui@1.0.2/dist/css/mdui.min.css">
        <title>留言板</title>
    </head>
    <body>
        <div class="mdui-container"><br>
            <div class="mdui-card">
                <div class="mdui-card-primary">
                    <div class="mdui-card-primary-title">
                        发表留言
                    </div>
                    <div class="mdui-card-primary-subtitle">
                        请勿提交违法违规内容
                    </div>
                </div>
                <div class="mdui-container">
                    <form method="post"><input class="mdui-textfield-input" type="text" name="nickname" placeholder="昵称" required> <br> <textarea class="mdui-textfield-input" name="content" placeholder="留言正文" required></textarea> <br> <input class="mdui-btn mdui-btn-block mdui-btn-raised" type="submit" value="提交留言">
                    </form> <br>
                </div>
            </div> <br>
            <div class="mdui-card">
                <div class="mdui-card-primary">
                    <div class="mdui-card-primary-title">
                        留言列表
                    </div>
                    <div class="mdui-card-primary-subtitle">
                        不友好的留言请联系xy@090508.xyz举报呐
                    </div>
                </div>
                <div class="mdui-card-content">
                    <?php 
    // 获取留言记录 
    $result = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<p>";
            echo "<strong>" . $row["nickname"] . "</strong> ";
            echo "创建于" . $row["created_at"] . "<br>";
            echo ($row["content"]);
            echo '</p><div class="mdui-divider"></div>';
        }
    } else {
        echo "这里什么都没有呐";
    }
    $conn->close();
    ?>
                </div>
            </div> <br>
        </div>
        <script src="https://npm.elemecdn.com/mdui@1.0.2/dist/js/mdui.min.js"></script>
    </body>
</html>