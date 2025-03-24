<?php
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateNickname($name) {
    return preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{2,20}$/u', $name);
}

function rateLimitCheck($ip) {
    $conn = DB::getInstance();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM messages 
                          WHERE ip = ? AND created_at > NOW() - INTERVAL 1 HOUR");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_row();
    return $result[0] < MAX_POST_PER_HOUR;
}
