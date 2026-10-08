<?php
/**
 * PHP CDN Storage - Dashboard & Web Uploader
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDN Storage Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
        .drop-zone { border: 2px dashed #334155; transition: all 0.2s ease; }
        .drop-zone.dragover { border-color: #3b82f6; background-color: rgba(59, 130, 246, 0.05); }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-12">
        <!-- Header -->
        <header class="flex items-center justify-between pb-8 border-b border-slate-800">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold text-xl shadow-lg shadow-blue-500/20">
                    ⚡
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight">PHP CDN Storage</h1>
                    <p class="text-xs text-slate-400">Content-Addressable Storage (CAS) • Zero Memory Stream • CDN Edge Ready</p>
                </div>
            </div>
            <div>
                <a href="health.php" target="_blank" class="text-xs px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Hệ thống & Health
                </a>
            </div>
        </header>

        <!-- API Key Input Bar -->
        <div class="mt-8 bg-slate-900/60 p-4 rounded-xl border border-slate-800 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="flex-1 w-full">
                <label class="block text-xs font-medium text-slate-400 mb-1">API Key xác thực (Bearer / Header X-API-Key)</label>
                <input type="password" id="apiKeyInput" placeholder="Nhập API Key để upload..." value="cdn_secret_key_change_me_123456" 
                    class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-blue-500 transition">
            </div>
            <div class="flex items-center gap-2 self-end md:self-auto">
                <button type="button" onclick="testHealth()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs rounded-lg text-slate-200 transition font-medium">
                    Kiểm tra kết nối
                </button>
            </div>
        </div>

        <!-- Main Upload Zone -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-7">
                <div id="dropZone" class="drop-zone rounded-2xl p-10 text-center cursor-pointer bg-slate-900/40">
                    <input type="file" id="fileInput" class="hidden" multiple>
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-800/80 flex items-center justify-center text-3xl">
                        📁
                    </div>
                    <h3 class="text-base font-semibold text-slate-200">Kéo thả file vào đây hoặc bấm để chọn</h3>
                    <p class="text-xs text-slate-400 mt-1">Hỗ trợ hình ảnh, video, audio, tài liệu, zip (Tự động băm SHA-1 chống trùng lặp)</p>
                    <button type="button" onclick="document.getElementById('fileInput').click()" class="mt-5 px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-semibold shadow-lg shadow-blue-600/30 transition">
                        Chọn file upload
                    </button>
                </div>

                <!-- Upload Progress -->
                <div id="progressContainer" class="hidden mt-4 bg-slate-900 border border-slate-800 rounded-xl p-4">
                    <div class="flex justify-between text-xs text-slate-400 mb-2">
                        <span id="progressFileName">Đang upload...</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div id="progressBar" class="bg-blue-500 h-2 rounded-full transition-all duration-200" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Results List -->
                <div id="resultsList" class="mt-6 space-y-3"></div>
            </div>

            <!-- API Docs & Snippets -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
                    <h3 class="text-sm font-semibold text-slate-200 flex items-center gap-2 mb-3">
                        <span>🚀</span> Cú pháp cURL Upload
                    </h3>
                    <pre class="bg-slate-950 p-3 rounded-xl text-xs text-slate-300 overflow-x-auto border border-slate-800">curl -X POST "<?= htmlspecialchars($baseUrl) ?>/upload.php" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "file=@/path/to/image.jpg"</pre>
                </div>

                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
                    <h3 class="text-sm font-semibold text-slate-200 flex items-center gap-2 mb-3">
                        <span>⚡</span> Stream Trực tiếp (php://input)
                    </h3>
                    <p class="text-xs text-slate-400 mb-2">Gửi trực tiếp binary body, 0 tốn RAM máy chủ shared hosting:</p>
                    <pre class="bg-slate-950 p-3 rounded-xl text-xs text-slate-300 overflow-x-auto border border-slate-800">curl -X POST "<?= htmlspecialchars($baseUrl) ?>/upload.php" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "X-File-Name: video.mp4" \
  --data-binary "@/path/to/video.mp4"</pre>
                </div>

                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5">
                    <h3 class="text-sm font-semibold text-slate-200 flex items-center gap-2 mb-3">
                        <span>🛡️</span> Cấu hình CDN (Cloudflare)
                    </h3>
                    <ul class="text-xs text-slate-400 space-y-2 list-disc list-inside">
                        <li>Bật <strong>Cache Everything</strong> cho đường dẫn <code>/f/*</code> hoặc <code>/storage/*</code></li>
                        <li>Header <code>immutable</code> tự động báo CDN lưu 1 năm, không cần revalidate.</li>
                        <li>Khi file sửa đổi, file mới sinh hash mới -> tự động chống cache cũ mà không cần purge CDN!</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const progressFileName = document.getElementById('progressFileName');
        const resultsList = document.getElementById('resultsList');
        const apiKeyInput = document.getElementById('apiKeyInput');

        // Drag & drop handlers
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0) uploadFiles(files);
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) uploadFiles(fileInput.files);
        });

        async function uploadFiles(files) {
            const key = apiKeyInput.value.trim();
            if (!key) {
                alert('Vui lòng nhập API Key!');
                return;
            }

            for (let i = 0; i < files.length; i++) {
                await uploadSingleFile(files[i], key);
            }
        }

        function uploadSingleFile(file, key) {
            return new Promise((resolve) => {
                const xhr = new XMLHttpRequest();
                const formData = new FormData();
                formData.append('file', file);

                progressContainer.classList.remove('hidden');
                progressFileName.innerText = file.name;
                progressBar.style.width = '0%';
                progressPercent.innerText = '0%';

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = pct + '%';
                        progressPercent.innerText = pct + '%';
                    }
                });

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        progressContainer.classList.add('hidden');
                        try {
                            const res = JSON.parse(xhr.responseText);
                            renderResult(res, file.name);
                        } catch (err) {
                            renderError(file.name, xhr.responseText || 'Lỗi server');
                        }
                        resolve();
                    }
                };

                xhr.open('POST', 'upload.php', true);
                xhr.setRequestHeader('Authorization', 'Bearer ' + key);
                xhr.send(formData);
            });
        }

        function renderResult(res, originalName) {
            if (!res.success) {
                renderError(originalName, res.error || 'Upload thất bại');
                return;
            }

            const item = document.createElement('div');
            item.className = 'bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col gap-2';

            const badge = res.deduplicated 
                ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">TRÙNG NỘI DUNG (Deduplicated)</span>'
                : '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">FILE MỚI</span>';

            item.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 overflow-hidden">
                        <span class="text-sm font-medium text-slate-200 truncate">${originalName}</span>
                        ${badge}
                    </div>
                    <span class="text-xs text-slate-400">${(res.size / 1024).toFixed(1)} KB</span>
                </div>
                <div class="flex items-center gap-2 mt-1">
                    <input type="text" readonly value="${res.url}" class="flex-1 bg-slate-950 border border-slate-800 rounded px-2.5 py-1 text-xs text-slate-300 font-mono select-all">
                    <button onclick="navigator.clipboard.writeText('${res.url}'); this.innerText='Đã copy'; setTimeout(() => this.innerText='Copy', 2000)" class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-medium transition">
                        Copy
                    </button>
                    <a href="${res.url}" target="_blank" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded text-xs font-medium transition">
                        Mở
                    </a>
                </div>
            `;
            resultsList.prepend(item);
        }

        function renderError(name, errText) {
            const item = document.createElement('div');
            item.className = 'bg-rose-950/40 border border-rose-800/50 rounded-xl p-4 text-xs text-rose-300';
            item.innerHTML = `<strong>Thất bại (${name}):</strong> ${errText}`;
            resultsList.prepend(item);
        }

        async function testHealth() {
            const key = apiKeyInput.value.trim();
            try {
                const res = await fetch('health.php', {
                    headers: { 'Authorization': 'Bearer ' + key }
                });
                const data = await res.json();
                alert('Kết nối thành công!\nTrạng thái: ' + data.status + '\nỔ cứng trống: ' + data.storage.free_human);
            } catch (e) {
                alert('Không thể kết nối đến server: ' + e.message);
            }
        }
    </script>
</body>
</html>
