<?php
/**
 * Google Drive Personal Account OAuth2 Setup Wizard
 * Automatically authorizes personal Google Accounts & generates credentials/oauth_personal.json
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

$baseUrl = getBaseUrl();
$redirectUri = $baseUrl . '/oauth_setup.php';

// Session for CSRF & temporary client credentials
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$statusMessage = '';
$isSuccess = false;
$folderIdDefault = defined('GDRIVE_DEFAULT_FOLDER_ID') ? GDRIVE_DEFAULT_FOLDER_ID : '15n4k5hfyjhjymkHbNizRh179UzeGYEQH';

// -------------------------------------------------------------
// STEP 2: Handle OAuth2 Redirect Callback from Google (with ?code=...)
// -------------------------------------------------------------
if (isset($_GET['code'])) {
    $code = trim((string)$_GET['code']);
    $clientId = $_SESSION['oauth_client_id'] ?? '';
    $clientSecret = $_SESSION['oauth_client_secret'] ?? '';
    $targetFolderId = $_SESSION['oauth_folder_id'] ?? $folderIdDefault;

    if (empty($clientId) || empty($clientSecret)) {
        $statusMessage = 'Lỗi phiên làm việc: Thiếu Client ID hoặc Client Secret. Vui lòng thử lại từ bước 1.';
    } else {
        // Exchange authorization code for tokens
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'grant_type'    => 'authorization_code'
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT        => 30
        ]);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $tokenData = json_decode((string)$res, true);

        if ($httpCode === 200 && !empty($tokenData['refresh_token'])) {
            // Save to credentials/oauth_personal.json
            $credDir = __DIR__ . '/credentials';
            if (!is_dir($credDir)) {
                @mkdir($credDir, 0755, true);
            }

            $userEmail = 'personal_google_account';
            // Try to extract user info from id_token if available
            if (!empty($tokenData['id_token'])) {
                $parts = explode('.', $tokenData['id_token']);
                if (isset($parts[1])) {
                    $payload = json_decode((string)base64_decode(strtr($parts[1], '-_', '+/')), true);
                    if (!empty($payload['email'])) {
                        $userEmail = $payload['email'];
                    }
                }
            }

            $safeEmail = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $userEmail);
            $credentialPayload = [
                'type'          => 'authorized_user',
                'user_email'    => $userEmail,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $tokenData['refresh_token'],
                'folder_id'     => $targetFolderId,
                'created_at'    => time()
            ];

            // Save as unique account file in pool
            $targetJsonPath = $credDir . '/account_' . $safeEmail . '.json';
            file_put_contents($targetJsonPath, json_encode($credentialPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $isSuccess = true;
            $statusMessage = "Đã ủy quyền thành công tài khoản [{$userEmail}] vào Pool lưu trữ! File cấu hình đã được tạo tự động tại credentials/account_{$safeEmail}.json.";
            
            // Clear temporary session
            unset($_SESSION['oauth_client_id'], $_SESSION['oauth_client_secret'], $_SESSION['oauth_folder_id']);
        } else {
            $err = $tokenData['error_description'] ?? $tokenData['error'] ?? 'Không lấy được refresh_token từ Google.';

            $statusMessage = "Lỗi xác thực từ Google: {$err}. Lưu ý: Cần chọn 'Cho phép' tất cả các quyền.";
        }
    }
}

// -------------------------------------------------------------
// STEP 1: Handle User Form Submission (Redirect to Google Auth)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id'], $_POST['client_secret'])) {
    $clientId = trim((string)$_POST['client_id']);
    $clientSecret = trim((string)$_POST['client_secret']);
    $folderId = trim((string)($_POST['folder_id'] ?? $folderIdDefault));

    if (empty($clientId) || empty($clientSecret)) {
        $statusMessage = 'Vui lòng điền đầy đủ Client ID và Client Secret!';
    } else {
        $_SESSION['oauth_client_id'] = $clientId;
        $_SESSION['oauth_client_secret'] = $clientSecret;
        $_SESSION['oauth_folder_id'] = $folderId;

        // Redirect to Google Consent Screen
        $scope = urlencode('https://www.googleapis.com/auth/drive');
        $authUrl = "https://accounts.google.com/o/oauth2/v2/auth"
            . "?client_id=" . urlencode($clientId)
            . "&redirect_uri=" . urlencode($redirectUri)
            . "&response_type=code"
            . "&scope=" . $scope
            . "&access_type=offline"
            . "&prompt=consent";

        header("Location: {$authUrl}");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu Hình OAuth2 Google Drive Cá Nhân (15GB/Google One)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen py-12 px-4 selection:bg-blue-600 selection:text-white">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold mb-3">
                <span>🔑 Giải Pháp Cho Tài Khoản Google Cá Nhân</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Cấu Hình Google Drive OAuth 2.0</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-2">
                Kết nối trực tiếp tài khoản cá nhân của bạn (15GB/Google One) để upload thẳng vào thư mục <code class="text-blue-400 font-mono">cdn-aigiup</code> mà không bao giờ bị lỗi 403 quota!
            </p>
        </div>

        <?php if (!empty($statusMessage)): ?>
            <div class="mb-6 p-4 rounded-2xl border text-sm <?= $isSuccess ? 'bg-emerald-950/40 border-emerald-800 text-emerald-300' : 'bg-rose-950/40 border-rose-800 text-rose-300' ?>">
                <div class="flex items-start gap-3">
                    <span class="text-lg"><?= $isSuccess ? '✅' : '❌' ?></span>
                    <div>
                        <div class="font-semibold"><?= $isSuccess ? 'Thành công!' : 'Thông báo' ?></div>
                        <div class="mt-1 text-xs opacity-90"><?= htmlspecialchars($statusMessage) ?></div>
                        <?php if ($isSuccess): ?>
                            <div class="mt-4">
                                <a href="index.php" class="inline-block px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold transition">
                                    Quay Lại Trang Chủ & Thử Upload Ảnh Ngay
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php 
            require_once __DIR__ . '/GoogleDriveManager.php';
            $connectedAccounts = GoogleDriveManager::getAccountsList();
        ?>

        <!-- Active Accounts Pool Section -->
        <div class="mb-6 bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <div class="font-bold text-white text-sm flex items-center gap-2">
                    <span>📦</span> Danh Sách Tài Khoản Trong Pool
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= count($connectedAccounts) > 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' ?>">
                    <?= count($connectedAccounts) ?> Tài khoản (≈ <?= count($connectedAccounts) * 15 ?> GB)
                </span>
            </div>

            <?php if (empty($connectedAccounts)): ?>
                <p class="text-xs text-slate-400 italic">Chưa có tài khoản nào được kết nối. Hãy kết nối tài khoản đầu tiên ở form bên dưới!</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($connectedAccounts as $acc): ?>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800/80 text-xs">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="font-medium text-white"><?= htmlspecialchars($acc['client_email']) ?></span>
                            </div>
                            <div class="text-slate-400 font-mono text-[11px]">
                                Folder: <span class="text-blue-400"><?= htmlspecialchars($acc['folder_id']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-[11px] text-slate-500 mt-3">
                    💡 <strong>Mẹo mở rộng:</strong> Bạn có thể dùng form bên dưới để kết nối thêm tài khoản Google thứ 2, thứ 3... Hệ thống sẽ tự động băm vòng và hợp nhất dung lượng lưu trữ!
                </p>
            <?php endif; ?>
        </div>

        <?php if (!$isSuccess): ?>
            <!-- Guide Box -->
            <div class="mb-6 bg-slate-900/60 border border-slate-800 rounded-2xl p-5 text-xs text-slate-300 space-y-3">
                <div class="font-bold text-white text-sm flex items-center gap-2">
                    <span>📋</span> Các Bước Thiết Lập Google Cloud Cho Tài Khoản Cá Nhân
                </div>

                <div class="p-3 rounded-xl bg-amber-950/40 border border-amber-800/60 text-amber-200 text-xs">
                    <strong class="text-amber-100">⚠️ BƯỚC QUAN TRỌNG (Tránh lỗi 403 access_denied):</strong><br>
                    Vì ứng dụng ở chế độ Testing, Google yêu cầu thêm email vào danh sách thử nghiệm:
                    <ol class="list-decimal list-inside mt-1.5 space-y-1 text-[11px] text-amber-200/90">
                        <li>Vào <a href="https://console.cloud.google.com/apis/credentials/consent" target="_blank" class="underline font-semibold text-white">OAuth consent screen</a>.</li>
                        <li>Cuộn xuống mục <strong>Test users</strong> (Người dùng thử nghiệm).</li>
                        <li>Bấm <strong>+ ADD USERS</strong> ➔ Nhập email cá nhân bạn sẽ đăng nhập (VD: <code class="bg-black/40 px-1 rounded">hducit1@gmail.com</code>) ➔ Bấm <strong>Save</strong>.</li>
                    </ol>
                </div>

                <div class="font-semibold text-slate-200 mt-2">Lấy Client ID & Client Secret:</div>
                <ol class="list-decimal list-inside space-y-2 text-slate-400 leading-relaxed">
                    <li>Vào <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-400 underline">Google Cloud Console ➔ Credentials</a>.</li>
                    <li>Bấm <strong>Create Credentials</strong> ➔ Chọn <strong>OAuth client ID</strong>.
                        <div class="pl-5 text-slate-400 text-[11px] mt-0.5">
                            • Application type: Chọn <strong>Web application</strong>.<br>
                            • Authorized redirect URIs: Bấm <strong>Add URI</strong> và dán chính xác:<br>
                            <input type="text" readonly value="<?= htmlspecialchars($redirectUri) ?>" class="mt-1 w-full bg-slate-950 border border-slate-700 rounded px-2 py-1 font-mono text-cyan-400 select-all">
                        </div>
                    </li>
                    <li>Bấm <strong>Create</strong> ➔ Copy <strong>Client ID</strong> và <strong>Client Secret</strong> dán vào form bên dưới.</li>
                </ol>
            </div>

            <!-- Setup Form -->
            <form method="POST" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-5 shadow-2xl">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Client ID</label>
                    <input type="text" name="client_id" required placeholder="xxx.apps.googleusercontent.com" 
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500 font-mono transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Client Secret</label>
                    <input type="password" name="client_secret" required placeholder="GOCSPX-xxxxxxxxxxxxxx" 
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500 font-mono transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5 flex items-center justify-between">
                        <span>Folder ID Thư Mục Trên Google Drive Của Bạn</span>
                        <span class="text-[11px] text-slate-500">Mặc định: cdn-aigiup</span>
                    </label>
                    <input type="text" name="folder_id" value="<?= htmlspecialchars($folderIdDefault) ?>" 
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-300 focus:outline-none focus:border-blue-500 font-mono transition">
                </div>

                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-blue-600/30 transition flex items-center justify-center gap-2">
                    <span>Đăng Nhập Ủy Quyền Với Google</span>
                    <span>➔</span>
                </button>
            </form>
        <?php endif; ?>

        <div class="text-center mt-8 text-xs text-slate-500">
            <a href="index.php" class="hover:text-slate-400">← Về Trang Chủ CDN</a> • 
            <a href="health.php" target="_blank" class="hover:text-slate-400">Kiểm tra Health</a>
        </div>
    </div>
</body>
</html>
