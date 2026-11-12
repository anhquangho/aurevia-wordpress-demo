<?php
/** Local-only, single-use bootstrap. No credentials are shipped with this project. */
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403); exit('This installer is available only on the local computer.');
}
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(?::[0-9]{1,5})?$/i', $host)) {
    http_response_code(400); exit('Open this installer with localhost or 127.0.0.1.');
}
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/setup.php')), '/.');
$url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $host . $base;
$configured = file_exists(__DIR__ . '/wp-config.php');
$locked = file_exists(__DIR__ . '/.aurevia-installed');
$success = false;
$error = '';
$token = $_COOKIE['av_setup_token'] ?? '';
if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $token = bin2hex(random_bytes(32));
    setcookie('av_setup_token', $token, ['expires'=>time()+3600,'path'=>($base ?: '') . '/','httponly'=>true,'samesite'=>'Strict','secure'=>str_starts_with($url, 'https:')]);
}
$defaults = ['db_host'=>'127.0.0.1','db_port'=>'3306','db_name'=>'aurevia_demo','db_user'=>'root','admin_user'=>'portfolio_admin','admin_email'=>'portfolio@example.com'];
$values = $defaults;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$configured && !$locked) {
    $configCreated = false;
    try {
        if (!isset($_POST['token']) || !is_string($_POST['token']) || !hash_equals($token, $_POST['token']) || empty($_COOKIE['av_setup_token'])) { throw new RuntimeException('Phiên cài đặt hết hạn. Tải lại trang và thử lại.'); }
        foreach ($defaults as $key=>$value) {
            if (!isset($_POST[$key]) || !is_string($_POST[$key])) { throw new RuntimeException('Dữ liệu cài đặt không hợp lệ.'); }
            $values[$key] = trim($_POST[$key]);
        }
        $dbPass = isset($_POST['db_pass']) && is_string($_POST['db_pass']) ? $_POST['db_pass'] : '';
        $adminPass = isset($_POST['admin_pass']) && is_string($_POST['admin_pass']) ? $_POST['admin_pass'] : '';
        if (!extension_loaded('mysqli') || !extension_loaded('mbstring') || !extension_loaded('json')) { throw new RuntimeException('Bật extensions mysqli, mbstring và json trong PHP của XAMPP.'); }
        if (version_compare(PHP_VERSION, '8.1', '<')) { throw new RuntimeException('Demo cần PHP 8.1 trở lên.'); }
        if (!file_exists(__DIR__ . '/wp-load.php')) { throw new RuntimeException('Thiếu WordPress core. Dùng bản aurevia-xampp.zip hoặc đọc README để tạo bộ đầy đủ.'); }
        if (!file_exists(__DIR__ . '/wp-content/themes/aurevia/functions.php') || !file_exists(__DIR__ . '/wp-content/mu-plugins/aurevia-demo.php')) { throw new RuntimeException('Thiếu theme hoặc demo plugin. Giải nén lại bộ đầy đủ.'); }
        if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $values['db_name'])) { throw new RuntimeException('Tên database chỉ được chứa chữ, số và dấu gạch dưới.'); }
        if (!in_array($values['db_host'], ['127.0.0.1','localhost'], true) || !ctype_digit($values['db_port']) || (int)$values['db_port'] < 1 || (int)$values['db_port'] > 65535) { throw new RuntimeException('Database phải chạy cục bộ, với port từ 1 đến 65535.'); }
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $values['admin_user']) || strlen(trim($adminPass)) < 12 || trim($adminPass) !== $adminPass || !filter_var($values['admin_email'], FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('Dùng username 3–50 ký tự, mật khẩu từ 12 ký tự (không có khoảng trắng đầu/cuối) và email hợp lệ.'); }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try { $db = new mysqli($values['db_host'], $values['db_user'], $dbPass, '', (int)$values['db_port']); }
        catch (mysqli_sql_exception $e) { throw new RuntimeException('Không kết nối được MySQL. Kiểm tra MySQL trong XAMPP, port và tài khoản database.'); }
        $db->set_charset('utf8mb4');
        $db->query('CREATE DATABASE IF NOT EXISTS `' . $values['db_name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $db->select_db($values['db_name']);
        if ($db->query('SHOW TABLES')->num_rows > 0) { throw new RuntimeException('Database đã có dữ liệu. Để bảo vệ dữ liệu cũ, hãy chọn tên database mới hoặc một database trống.'); }
        $db->close();
        $config = "<?php\n// Local demo configuration. Do not commit this file.\n";
        $settings = ['DB_NAME'=>$values['db_name'],'DB_USER'=>$values['db_user'],'DB_PASSWORD'=>$dbPass,'DB_HOST'=>$values['db_host'] . ':' . $values['db_port'],'DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>'','WP_HOME'=>$url,'WP_SITEURL'=>$url,'WP_ENVIRONMENT_TYPE'=>'local','DISALLOW_FILE_EDIT'=>true,'DISABLE_WP_CRON'=>true,'AUTOMATIC_UPDATER_DISABLED'=>true,'WP_DEBUG'=>false];
        foreach (['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $key) { $settings[$key] = bin2hex(random_bytes(48)); }
        foreach ($settings as $key=>$value) { $config .= 'define(' . var_export($key, true) . ', ' . var_export($value, true) . ");\n"; }
        $config .= "\$table_prefix = 'av_';\nif (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }\nrequire_once ABSPATH . 'wp-settings.php';\n";
        $file = @fopen(__DIR__ . '/wp-config.php', 'x');
        if (!$file) { throw new RuntimeException('Không tạo được wp-config.php. Kiểm tra quyền ghi hoặc file cấu hình đã tồn tại.'); }
        $configCreated = true;
        $written = fwrite($file, $config); fclose($file);
        if ($written !== strlen($config)) { unlink(__DIR__ . '/wp-config.php'); $configCreated = false; throw new RuntimeException('Không ghi đủ cấu hình. Kiểm tra dung lượng ổ đĩa rồi thử lại.'); }
        define('WP_INSTALLING', true);
        require_once __DIR__ . '/wp-load.php';
        add_filter('pre_wp_mail', '__return_true'); // No welcome email from this local installer.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $result = wp_install('Aurevia Life Sciences', $values['admin_user'], $values['admin_email'], false, '', $adminPass, 'en_US');
        if (empty($result['user_id']) || is_wp_error($result['user_id'])) { throw new RuntimeException('Không tạo được tài khoản WordPress.'); }
        // Only delete WordPress-generated starter posts in this fresh, empty database.
        foreach (get_posts(['post_type'=>['post','page'],'post_status'=>'any','numberposts'=>-1]) as $post) { wp_delete_post($post->ID, true); }
        switch_theme('aurevia');
        require_once __DIR__ . '/wp-content/themes/aurevia/functions.php';
        av_seed();
        update_option('blogdescription', 'Curiosity in our science. People in our purpose.');
        update_option('default_comment_status', 'closed');
        update_option('default_ping_status', 'closed');
        update_option('timezone_string', 'Asia/Bangkok');
        update_option('av_demo_version', '1.0.0');
        if (file_put_contents(__DIR__ . '/.aurevia-installed', 'Installed ' . gmdate('c') . "\n", LOCK_EX) === false) { throw new RuntimeException('Đã cài đặt nhưng không ghi được file khóa. wp-config.php vẫn ngăn cài lại.'); }
        $success = true;
        setcookie('av_setup_token', '', ['expires'=>time()-3600,'path'=>($base ?: '') . '/','httponly'=>true,'samesite'=>'Strict']);
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'Cài đặt chưa hoàn tất. Kiểm tra PHP/MySQL error log và quyền tạo database. Không có mật khẩu nào được hiển thị.';
        if ($configCreated) { $configured = true; $error .= ' Đã tạo cấu hình: không tự động cài lại. Xem mục Khôi phục trong README.'; }
    }
}
function av_setup_escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Aurevia · Local setup</title><style>
*{box-sizing:border-box}body{margin:0;background:#fff8e7;color:#231b2b;font:15px/1.7 'Segoe UI',Arial,sans-serif}main{max-width:760px;margin:55px auto;padding:40px;background:#fffdf8;border:1px solid #d7dfce;border-radius:10px}h1{font-size:38px;letter-spacing:-1.5px;margin:10px 0}h2{font-size:21px;margin:25px 0 15px}p{color:#5d6e67}.kicker{font-size:11px;letter-spacing:2px;color:#4c20d9}.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}label{display:block;font-size:12px;font-weight:600}input{display:block;width:100%;padding:12px;border:1px solid #bfceb5;border-radius:4px;font:14px 'Segoe UI';margin-top:6px;color:#231b2b;background:#fff}button,.button{display:inline-block;border:0;border-radius:4px;background:#231b2b;color:white;padding:14px 22px;font:14px 'Segoe UI';text-decoration:none;cursor:pointer;margin:12px 10px 0 0}.secondary{background:#fac95b;color:#231b2b}.notice{padding:16px;background:#eff5df;border:1px solid #b6c99c;border-radius:4px}.error{background:#fff0e8;border-color:#c09b82}.small{font-size:12px;color:#69785e}code{background:#edf1e6;padding:2px 5px;overflow-wrap:anywhere}:focus-visible{outline:3px solid #4c20d9;outline-offset:3px}@media(max-width:600px){main{margin:20px;padding:25px}.grid{grid-template-columns:1fr}h1{font-size:31px}}
</style></head><body><main><div class="kicker">✳ AUREVIA LIFE SCIENCES · LOCAL DEMO</div>
<?php if ($success): ?>
<h1>Demo đã sẵn sàng.</h1><p class="notice">Đã tạo 4 trang, kích hoạt theme và chuẩn bị hộp thư demo. Không cần import SQL.</p><p>Đăng nhập bằng username và mật khẩu anh vừa chọn. Để sửa nội dung, vào <strong>Pages → Edit → Aurevia — Page sections</strong>. Lời nhắn nằm ở <strong>Demo enquiries</strong>.</p><a class="button" href="<?php echo av_setup_escape($url); ?>/">Xem website ↗</a><a class="button secondary" href="<?php echo av_setup_escape($url); ?>/wp-admin/">Mở WordPress admin</a><p class="small">Bộ cài đã khóa. Có thể xóa setup.php sau khi cài xong.</p>
<?php elseif ($configured || $locked): ?>
<h1>Thư mục đã được cấu hình.</h1><?php if ($error): ?><p class="notice error"><?php echo av_setup_escape($error); ?></p><?php endif; ?><p>Bộ cài không ghi đè cấu hình hoặc dữ liệu đã có. Nếu lần cài trước chưa hoàn tất, xem mục <strong>Khôi phục</strong> trong README.</p><a class="button" href="<?php echo av_setup_escape($url); ?>/">Mở website ↗</a><a class="button secondary" href="<?php echo av_setup_escape($url); ?>/wp-admin/">WordPress admin</a>
<?php else: ?>
<h1>Từ giải nén đến website.</h1><p>Bật <strong>Apache + MySQL</strong> trong XAMPP, điền thông tin bên dưới và cài một lần. Giao diện, ảnh và dữ liệu mẫu đều có sẵn trong gói đầy đủ.</p>
<?php if ($error): ?><p class="notice error" role="alert"><?php echo av_setup_escape($error); ?></p><?php endif; ?>
<form method="post"><input type="hidden" name="token" value="<?php echo av_setup_escape($token); ?>"><h2>01 / Database cục bộ</h2><p class="small">XAMPP mới cài thường dùng root, mật khẩu trống và port 3306. Bộ cài chỉ sử dụng database trống.</p><div class="grid">
<?php foreach (['db_host'=>'Host','db_port'=>'MySQL port','db_name'=>'Tên database mới / trống','db_user'=>'Database user'] as $key=>$label): ?><label><?php echo av_setup_escape($label); ?><input name="<?php echo $key; ?>" value="<?php echo av_setup_escape($values[$key]); ?>" required></label><?php endforeach; ?><label>Database password (có thể trống)<input type="password" name="db_pass" autocomplete="off"></label></div>
<h2>02 / Tài khoản WordPress của anh</h2><div class="grid"><label>Admin username<input name="admin_user" value="<?php echo av_setup_escape($values['admin_user']); ?>" required pattern="[a-zA-Z0-9_.\-]{3,50}" autocomplete="username"></label><label>Admin email (không gửi mail)<input type="email" name="admin_email" value="<?php echo av_setup_escape($values['admin_email']); ?>" required autocomplete="email"></label><label>Admin password · tối thiểu 12 ký tự<input type="password" name="admin_pass" required minlength="12" autocomplete="new-password"></label></div><p class="small">Website sẽ dùng địa chỉ <code><?php echo av_setup_escape($url); ?></code>. Không có mật khẩu admin mặc định. Ghi nhớ mật khẩu anh tự chọn.</p><button type="submit">Tạo demo WordPress ↗</button></form><p class="small">Chỉ dành cho demo trên máy cá nhân. Aurevia là thương hiệu giả lập. Không có plugin trả phí hay dịch vụ email bên ngoài.</p>
<?php endif; ?></main></body></html>
