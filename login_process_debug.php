<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function debug_line($label, $value) {
    echo "<p><strong>" . h($label) . ":</strong> " . h($value) . "</p>";
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Login Debug</title>";
echo "<style>
body{font-family:Arial,sans-serif;background:#111827;color:#f9fafb;padding:24px;line-height:1.6}
.card{background:#1f2937;border:1px solid #374151;border-radius:12px;padding:20px;max-width:900px}
.ok{color:#86efac}.bad{color:#fca5a5}.warn{color:#fde68a}
code,pre{background:#0f172a;padding:2px 6px;border-radius:6px}
pre{padding:16px;overflow:auto}
</style></head><body><div class='card'>";
echo "<h2>Admin Login Debug</h2>";

debug_line('Request method', $_SERVER['REQUEST_METHOD'] ?? 'unknown');
debug_line('PHP version', PHP_VERSION);
debug_line('Server software', $_SERVER['SERVER_SOFTWARE'] ?? 'unknown');
debug_line('Current file', __FILE__);

$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
debug_line('config.php exists', file_exists($configPath) ? 'yes' : 'no');

if (!file_exists($configPath)) {
    echo "<p class='bad'><strong>Fatal:</strong> config.php not found.</p></div></body></html>";
    exit;
}

include $configPath;

if (!isset($conn) || !($conn instanceof mysqli)) {
    echo "<p class='bad'><strong>Fatal:</strong> \$conn was not created by config.php.</p></div></body></html>";
    exit;
}

if ($conn->connect_error) {
    echo "<p class='bad'><strong>Database connection failed:</strong> " . h($conn->connect_error) . "</p></div></body></html>";
    exit;
}

echo "<p class='ok'><strong>Database connection:</strong> OK</p>";
debug_line('Database name', $dbname ?? 'not set in config');

$tableCheck = $conn->query("SHOW TABLES LIKE 'admin'");
if (!$tableCheck) {
    echo "<p class='bad'><strong>Table check failed:</strong> " . h($conn->error) . "</p></div></body></html>";
    exit;
}

if ($tableCheck->num_rows === 0) {
    echo "<p class='bad'><strong>Fatal:</strong> `admin` table does not exist.</p></div></body></html>";
    exit;
}

echo "<p class='ok'><strong>admin table:</strong> found</p>";

$columns = [];
$columnResult = $conn->query("SHOW COLUMNS FROM admin");
if ($columnResult) {
    while ($row = $columnResult->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}
debug_line('admin table columns', implode(', ', $columns));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<p class='warn'><strong>Info:</strong> Open this file through the debug login form and submit credentials.</p>";
    echo "</div></body></html>";
    exit;
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

debug_line('Posted username', $username === '' ? '(empty)' : $username);
debug_line('Password length', strlen($password));

if ($username === '' || $password === '') {
    echo "<p class='bad'><strong>Fatal:</strong> Username or password is empty.</p></div></body></html>";
    exit;
}

$sql = "SELECT * FROM admin WHERE admin_name = ?";
debug_line('SQL', $sql);

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "<p class='bad'><strong>Prepare failed:</strong> " . h($conn->error) . "</p></div></body></html>";
    exit;
}

echo "<p class='ok'><strong>Prepare:</strong> OK</p>";

if (!$stmt->bind_param("s", $username)) {
    echo "<p class='bad'><strong>bind_param failed:</strong> " . h($stmt->error) . "</p></div></body></html>";
    exit;
}

echo "<p class='ok'><strong>bind_param:</strong> OK</p>";

if (!$stmt->execute()) {
    echo "<p class='bad'><strong>Execute failed:</strong> " . h($stmt->error) . "</p></div></body></html>";
    exit;
}

echo "<p class='ok'><strong>Execute:</strong> OK</p>";

if (!$stmt->store_result()) {
    echo "<p class='bad'><strong>store_result failed:</strong> " . h($stmt->error) . "</p></div></body></html>";
    exit;
}

debug_line('Matching rows', $stmt->num_rows);

if ($stmt->num_rows < 1) {
    echo "<p class='warn'><strong>Result:</strong> Username not found in `admin` table.</p></div></body></html>";
    exit;
}

$meta = $stmt->result_metadata();
if (!$meta) {
    echo "<p class='bad'><strong>result_metadata failed:</strong> " . h($stmt->error) . "</p></div></body></html>";
    exit;
}

$row = [];
$bindRefs = [];
while ($field = $meta->fetch_field()) {
    $row[$field->name] = null;
    $bindRefs[] = &$row[$field->name];
}

if (!call_user_func_array([$stmt, 'bind_result'], $bindRefs)) {
    echo "<p class='bad'><strong>bind_result failed:</strong> " . h($stmt->error) . "</p></div></body></html>";
    exit;
}

if (!$stmt->fetch()) {
    echo "<p class='bad'><strong>fetch failed:</strong> Could not fetch the admin row.</p></div></body></html>";
    exit;
}

echo "<p class='ok'><strong>Fetch:</strong> OK</p>";

$hashedPassword = $row['admin_password'] ?? '';
debug_line('Fetched admin_name', $row['admin_name'] ?? '(missing)');
debug_line('Fetched admin_type', $row['admin_type'] ?? '(missing)');
debug_line('Password hash length', strlen((string) $hashedPassword));
debug_line('Looks like password_hash', preg_match('/^\$2y\$/', (string) $hashedPassword) ? 'yes' : 'no');

$passwordVerified = $hashedPassword !== '' ? password_verify($password, $hashedPassword) : false;
debug_line('password_verify result', $passwordVerified ? 'true' : 'false');

if (!$passwordVerified && $hashedPassword === $password) {
    echo "<p class='warn'><strong>Important:</strong> The live admin password appears to be stored in plain text, not as a PHP hash.</p>";
}

echo "<h3>Fetched Row</h3><pre>" . h(print_r($row, true)) . "</pre>";

$stmt->close();
$conn->close();

echo "</div></body></html>";
?>
