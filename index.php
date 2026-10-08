<?php
/**
 * PHP CDN Storage Gateway - Product Showcase & Developer Portal
 * High-performance Content-Addressable Storage (CAS) with Google Drive Pool
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP CDN Storage Gateway - Giải Pháp Lưu Trữ & Phân Phối File Hiệu Năng Cao</title>
    <meta name="description" content="Hệ thống CDN Storage phân tán, hỗ trợ Google Drive Pool 5TB x N, băm nội dung CAS chống trùng lặp dữ liệu, tích hợp dễ dàng với Node.js, C#, PHP, Next.js.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
        .glow-effect {
            background: radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.15) 0%, rgba(15, 23, 42, 0) 70%);
        }
        .code-tab.active {
            border-bottom: 2px solid #3b82f6;
            color: #60a5fa;
            background-color: rgba(59, 130, 246, 0.08);
        }
        .drop-zone { border: 2px dashed #334155; transition: all 0.2s ease; }
        .drop-zone.dragover { border-color: #3b82f6; background-color: rgba(59, 130, 246, 0.08); }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen selection:bg-blue-600 selection:text-white">

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-50 backdrop-blur-xl bg-slate-950/80 border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="#" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-cyan-400 flex items-center justify-center font-black text-xl shadow-lg shadow-blue-500/25 group-hover:scale-105 transition">
                    ⚡
                </div>
                <div>
                    <span class="text-lg font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">CDN Storage Gateway</span>
                    <span class="hidden sm:inline-block ml-2 px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">v1.2 Enterprise</span>
                </div>
            </a>
            
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                <a href="#features" class="hover:text-blue-400 transition">Tính năng</a>
                <a href="#architecture" class="hover:text-blue-400 transition">Kiến trúc</a>
                <a href="#integration" class="hover:text-blue-400 transition">Tích hợp Code</a>
                <a href="#console" class="hover:text-blue-400 transition">Live Console</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="health.php" target="_blank" class="text-xs px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 hover:border-slate-700 transition flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="hidden sm:inline">Hệ thống:</span> Online
                </a>
                <a href="#console" class="text-xs px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold shadow-lg shadow-blue-600/30 transition">
                    Upload Thử Nghiệm
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-20 pb-24 overflow-hidden glow-effect">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold uppercase tracking-wider mb-8">
                <span>🛡️ Content-Addressable Storage • Multi-Drive Pool</span>
            </div>

            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-[1.15]">
                Hạ Tầng CDN & File Storage <br>
                <span class="bg-gradient-to-r from-blue-400 via-indigo-300 to-cyan-400 bg-clip-text text-transparent">Tối Ưu Cho Ứng Dụng Hiện Đại</span>
            </h1>

            <p class="mt-6 text-base sm:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed">
                Biến hạ tầng máy chủ của bạn thành trạm phân phối file hiệu năng cao. Kết hợp cùng Google Drive Pool (5TB x N), chống trùng lặp dữ liệu và tương thích 100% với Node.js, C#, Next.js, PHP.
            </p>

            <div class="mt-10 flex flex-wrap justify-center items-center gap-4">
                <a href="#integration" class="px-6 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm shadow-xl shadow-blue-600/30 transition hover:-translate-y-0.5">
                    Xem Hướng Dẫn Tích Hợp (SDK)
                </a>
                <a href="#console" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 font-semibold text-sm transition hover:-translate-y-0.5">
                    Mở Bảng Điều Khiển Upload
                </a>
            </div>

            <!-- Highlights Metrics -->
            <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm text-left">
                    <div class="text-2xl font-black text-white">0% RAM</div>
                    <div class="text-xs text-slate-400 mt-1 font-medium">Zero-load static reads qua web server</div>
                </div>
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm text-left">
                    <div class="text-2xl font-black text-blue-400">5TB x N</div>
                    <div class="text-xs text-slate-400 mt-1 font-medium">Google Drive Multi-Account Pool</div>
                </div>
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm text-left">
                    <div class="text-2xl font-black text-indigo-400">CAS Hash</div>
                    <div class="text-xs text-slate-400 mt-1 font-medium">Chống trùng lặp & Auto Cache-Bust</div>
                </div>
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm text-left">
                    <div class="text-2xl font-black text-emerald-400">1 Năm</div>
                    <div class="text-xs text-slate-400 mt-1 font-medium">Edge Cache CDN Immutable</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 border-t border-slate-900 bg-slate-950/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-xs font-bold text-blue-400 uppercase tracking-widest">Tính Năng Vượt Trội</h2>
                <p class="mt-3 text-3xl font-extrabold text-white">Được Thiết Kế Cho Hiệu Năng & Độ Bền Vững</p>
                <p class="mt-3 text-slate-400 text-sm">Hạ tầng phân tán giải quyết triệt để vấn đề đầy ổ cứng máy chủ và nghẽn băng thông.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="p-8 rounded-3xl bg-slate-900/40 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-2xl mb-6 border border-blue-500/20">
                        🔄
                    </div>
                    <h3 class="text-lg font-bold text-white">Content-Addressable Storage (CAS)</h3>
                    <p class="mt-3 text-sm text-slate-400 leading-relaxed">
                        Tự động băm nội dung (SHA-1/MD5) thành định danh file duy nhất. Upload 1.000 lần cùng một ảnh thì chỉ tốn 1 bản ghi duy nhất, loại bỏ hoàn toàn lãng phí dung lượng.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="p-8 rounded-3xl bg-slate-900/40 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-2xl mb-6 border border-indigo-500/20">
                        ☁️
                    </div>
                    <h3 class="text-lg font-bold text-white">Google Drive Multi-Account Pool</h3>
                    <p class="mt-3 text-sm text-slate-400 leading-relaxed">
                        Kết nối không giới hạn tài khoản Google Drive 5TB qua Service Account độc lập. Phân bổ lưu trữ thông minh, che giấu ID file và link gốc đằng sau domain CDN.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="p-8 rounded-3xl bg-slate-900/40 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-2xl mb-6 border border-emerald-500/20">
                        ⚡
                    </div>
                    <h3 class="text-lg font-bold text-white">Binary Stream & Chunked Upload</h3>
                    <p class="mt-3 text-sm text-slate-400 leading-relaxed">
                        Hỗ trợ upload dòng nhị phân <code>php://input</code> chỉ tiêu tốn vài KB RAM cho file hàng trăm MB. Tự động chia nhỏ (chunked) giúp tải file dung lượng lớn không bao giờ bị timeout.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Architecture Section -->
    <section id="architecture" class="py-20 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-xs font-bold text-blue-400 uppercase tracking-widest">Mô Hình Triển Khai</h2>
                <p class="mt-3 text-3xl font-extrabold text-white">Kiến Trúc Microservice Độc Lập</p>
                <p class="mt-3 text-slate-400 text-sm">CDN Storage tách biệt hoàn toàn với Database nghiệp vụ của ứng dụng phía Vercel / Client.</p>
            </div>

            <div class="p-8 md:p-12 rounded-3xl bg-slate-900/50 border border-slate-800 max-w-5xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center md:text-left">
                    <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800">
                        <span class="text-xs font-bold text-blue-400 uppercase">Tầng 1: Ứng Dụng (Vercel)</span>
                        <h4 class="text-base font-bold text-white mt-1">E-commerce / CMS / App</h4>
                        <p class="text-xs text-slate-400 mt-2">
                            Lưu thông tin sản phẩm, danh mục, liên kết URL ảnh vào DB chính (Postgres / MySQL). Gọi API CDN ngầm từ Server-Side.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-blue-950/30 border border-blue-800/40">
                        <span class="text-xs font-bold text-cyan-400 uppercase">Tầng 2: CDN Gateway (PHP)</span>
                        <h4 class="text-base font-bold text-white mt-1">Storage & Proxy Gateway</h4>
                        <p class="text-xs text-slate-400 mt-2">
                            Băm CAS, kiểm tra trùng lặp, chia nhánh thư mục Sharding và đồng bộ sang Google Drive. Cache CDN tĩnh 1 năm.
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800">
                        <span class="text-xs font-bold text-emerald-400 uppercase">Tầng 3: Lưu Trữ Gốc</span>
                        <h4 class="text-base font-bold text-white mt-1">Google Drive Pool (5TB)</h4>
                        <p class="text-xs text-slate-400 mt-2">
                            Lưu trữ dữ liệu an toàn, dung lượng khổng lồ. Ẩn danh hoàn toàn, bot chỉ truy cập thư mục được chia sẻ.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Integration Code Examples Section -->
    <section id="integration" class="py-20 border-t border-slate-900 bg-slate-950/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <h2 class="text-xs font-bold text-blue-400 uppercase tracking-widest">Dành Cho Lập Trình Viên</h2>
                <p class="mt-3 text-3xl font-extrabold text-white">Hướng Dẫn Tích Hợp API Đa Nền Tảng</p>
                <p class="mt-3 text-slate-400 text-sm">Chỉ cần gửi một HTTP POST request kèm Authorization Bearer Token.</p>
            </div>

            <div class="max-w-4xl mx-auto rounded-3xl bg-slate-900/70 border border-slate-800 overflow-hidden shadow-2xl">
                <!-- Code Language Tabs -->
                <div class="flex flex-wrap border-b border-slate-800 bg-slate-950/60 px-4 pt-3 gap-2">
                    <button type="button" onclick="switchTab('nodejs')" class="code-tab active px-4 py-2.5 rounded-t-xl text-xs font-semibold text-slate-400 hover:text-white transition" data-tab="nodejs">
                        Node.js (Fetch / Axios)
                    </button>
                    <button type="button" onclick="switchTab('csharp')" class="code-tab px-4 py-2.5 rounded-t-xl text-xs font-semibold text-slate-400 hover:text-white transition" data-tab="csharp">
                        C# (.NET HttpClient)
                    </button>
                    <button type="button" onclick="switchTab('nextjs')" class="code-tab px-4 py-2.5 rounded-t-xl text-xs font-semibold text-slate-400 hover:text-white transition" data-tab="nextjs">
                        Next.js (App Router)
                    </button>
                    <button type="button" onclick="switchTab('php')" class="code-tab px-4 py-2.5 rounded-t-xl text-xs font-semibold text-slate-400 hover:text-white transition" data-tab="php">
                        PHP (SDK / cURL)
                    </button>
                    <button type="button" onclick="switchTab('curl')" class="code-tab px-4 py-2.5 rounded-t-xl text-xs font-semibold text-slate-400 hover:text-white transition" data-tab="curl">
                        cURL (CLI)
                    </button>
                </div>

                <!-- Code Panels -->
                <div class="p-6">
                    <!-- 1. Node.js -->
                    <div id="panel-nodejs" class="code-panel">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs text-slate-400">Node.js 18+ (Dùng Native Fetch & FormData) hoặc Axios</span>
                            <button onclick="copySnippet('code-nodejs')" class="text-xs px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition">Copy</button>
                        </div>
                        <pre id="code-nodejs" class="bg-slate-950 p-4 rounded-2xl text-xs text-emerald-400 overflow-x-auto border border-slate-800/80 leading-relaxed"><code>import fs from 'fs';

async function uploadToCdn(filePath) {
  const formData = new FormData();
  const fileBlob = new Blob([fs.readFileSync(filePath)]);
  formData.append('file', fileBlob, 'product-image.jpg');

  const res = await fetch('<?= htmlspecialchars($baseUrl) ?>/upload.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer YOUR_CDN_API_KEY', // Thay bằng API Key của bạn
    },
    body: formData,
  });

  const data = await res.json();
  if (data.success) {
    console.log('CDN URL:', data.url);
    console.log('CAS Hash:', data.hash);
    return data.url;
  } else {
    throw new Error(data.error || 'Upload failed');
  }
}</code></pre>
                    </div>

                    <!-- 2. C# (.NET) -->
                    <div id="panel-csharp" class="code-panel hidden">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs text-slate-400">C# (.NET 6 / 7 / 8 với HttpClient & MultipartFormDataContent)</span>
                            <button onclick="copySnippet('code-csharp')" class="text-xs px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition">Copy</button>
                        </div>
                        <pre id="code-csharp" class="bg-slate-950 p-4 rounded-2xl text-xs text-sky-400 overflow-x-auto border border-slate-800/80 leading-relaxed"><code>using System;
using System.IO;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text.Json;
using System.Threading.Tasks;

public class CdnUploader
{
    private static readonly HttpClient _httpClient = new HttpClient();

    public static async Task&lt;string&gt; UploadFileAsync(string filePath, string apiKey)
    {
        using var form = new MultipartFormDataContent();
        await using var fileStream = File.OpenRead(filePath);
        using var streamContent = new StreamContent(fileStream);

        streamContent.Headers.ContentType = new MediaTypeHeaderValue("image/jpeg");
        form.Add(streamContent, "file", Path.GetFileName(filePath));

        var request = new HttpRequestMessage(HttpMethod.Post, "<?= htmlspecialchars($baseUrl) ?>/upload.php")
        {
            Content = form
        };
        request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", apiKey);

        var response = await _httpClient.SendAsync(request);
        var jsonString = await response.Content.ReadAsStringAsync();

        using var doc = JsonDocument.Parse(jsonString);
        var root = doc.RootElement;

        if (root.GetProperty("success").GetBoolean())
        {
            return root.GetProperty("url").GetString();
        }

        throw new Exception(root.GetProperty("error").GetString());
    }
}</code></pre>
                    </div>

                    <!-- 3. Next.js -->
                    <div id="panel-nextjs" class="code-panel hidden">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs text-slate-400">Next.js App Router (app/api/upload/route.ts)</span>
                            <button onclick="copySnippet('code-nextjs')" class="text-xs px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition">Copy</button>
                        </div>
                        <pre id="code-nextjs" class="bg-slate-950 p-4 rounded-2xl text-xs text-violet-400 overflow-x-auto border border-slate-800/80 leading-relaxed"><code>import { NextResponse } from 'next/server';

export async function POST(req: Request) {
  const formData = await req.formData();
  const file = formData.get('file') as File;

  if (!file) {
    return NextResponse.json({ error: 'Chưa có file' }, { status: 400 });
  }

  const cdnBody = new FormData();
  cdnBody.append('file', file);

  const cdnRes = await fetch('<?= htmlspecialchars($baseUrl) ?>/upload.php', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${process.env.CDN_API_KEY}`,
    },
    body: cdnBody,
  });

  const data = await cdnRes.json();
  return NextResponse.json(data);
}</code></pre>
                    </div>

                    <!-- 4. PHP SDK -->
                    <div id="panel-php" class="code-panel hidden">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs text-slate-400">PHP SDK thuần (Dùng file CdnClient.php)</span>
                            <button onclick="copySnippet('code-php')" class="text-xs px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition">Copy</button>
                        </div>
                        <pre id="code-php" class="bg-slate-950 p-4 rounded-2xl text-xs text-amber-400 overflow-x-auto border border-slate-800/80 leading-relaxed"><code>require_once 'CdnClient.php';

$cdn = new CdnClient('<?= htmlspecialchars($baseUrl) ?>', 'YOUR_CDN_API_KEY');

// 1. Upload ảnh thông thường
$res = $cdn->uploadFile('/path/to/image.jpg');
echo "Link CDN: " . $res['url'];

// 2. Upload file dung lượng lớn dạng Chunk (mỗi phần 5MB)
$chunkRes = $cdn->uploadChunked('/path/to/video.mp4', 5 * 1024 * 1024);

// 3. Xóa file khỏi hệ thống
$cdn->deleteFile($res['hash']);</code></pre>
                    </div>

                    <!-- 5. cURL -->
                    <div id="panel-curl" class="code-panel hidden">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs text-slate-400">Command Line Interface (cURL)</span>
                            <button onclick="copySnippet('code-curl')" class="text-xs px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition">Copy</button>
                        </div>
                        <pre id="code-curl" class="bg-slate-950 p-4 rounded-2xl text-xs text-slate-300 overflow-x-auto border border-slate-800/80 leading-relaxed"><code># 1. Upload file dạng form-data
curl -X POST "<?= htmlspecialchars($baseUrl) ?>/upload.php" \
  -H "Authorization: Bearer YOUR_CDN_API_KEY" \
  -F "file=@photo.jpg"

# 2. Upload dạng Stream Binary trực tiếp (0% RAM overhead)
curl -X POST "<?= htmlspecialchars($baseUrl) ?>/upload.php" \
  -H "Authorization: Bearer YOUR_CDN_API_KEY" \
  -H "X-File-Name: photo.jpg" \
  --data-binary "@photo.jpg"</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Upload Console (Bảo mật: Không điền sẵn API Key) -->
    <section id="console" class="py-20 border-t border-slate-900">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <span class="text-xs font-bold text-blue-400 uppercase tracking-widest">Thử Nghiệm Trực Quan</span>
                <h2 class="mt-2 text-3xl font-extrabold text-white">Live Upload Console</h2>
                <p class="mt-2 text-xs text-slate-400">Nhập API Key quản trị của bạn để kiểm tra tính năng upload trực tiếp.</p>
            </div>

            <!-- API Key Input Box (Hoàn toàn bảo mật, KHÔNG hardcode giá trị) -->
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm mb-6">
                <div class="flex flex-col sm:flex-row gap-3 items-center">
                    <div class="flex-1 w-full">
                        <label for="apiKeyInput" class="block text-xs font-medium text-slate-400 mb-1.5 flex items-center justify-between">
                            <span>🔑 Khóa xác thực API (Bearer Token)</span>
                            <span class="text-[11px] text-slate-500">Được bảo vệ phía client</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="apiKeyInput" placeholder="Nhập API Key của bạn (được định nghĩa trong config.php)..." 
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-slate-200 placeholder:text-slate-600 focus:outline-none focus:border-blue-500 transition pr-10">
                            <button type="button" onclick="toggleKeyVisibility()" class="absolute right-3 top-2.5 text-xs text-slate-500 hover:text-slate-300">
                                👁️
                            </button>
                        </div>
                    </div>
                    <button type="button" onclick="testHealth()" class="w-full sm:w-auto mt-4 sm:mt-5 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs border border-slate-700 transition">
                        Kiểm Tra Kết Nối
                    </button>
                </div>
            </div>

            <!-- Drag & Drop Upload Zone -->
            <div id="dropZone" class="drop-zone rounded-3xl p-10 text-center cursor-pointer bg-slate-900/30 backdrop-blur-sm hover:border-slate-700 transition">
                <input type="file" id="fileInput" class="hidden" multiple>
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-blue-600/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-3xl shadow-inner">
                    📁
                </div>
                <h3 class="text-base font-bold text-white">Kéo thả file vào đây hoặc bấm để chọn</h3>
                <p class="text-xs text-slate-400 mt-1">Hỗ trợ hình ảnh, tài liệu, video, ZIP. Tự động băm SHA-1 chống trùng lặp dữ liệu.</p>
                <button type="button" onclick="document.getElementById('fileInput').click()" class="mt-5 px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-blue-600/30 transition">
                    Chọn File Upload
                </button>
            </div>

            <!-- Progress Bar -->
            <div id="progressContainer" class="hidden mt-4 bg-slate-900 border border-slate-800 rounded-2xl p-4">
                <div class="flex justify-between text-xs text-slate-400 mb-2">
                    <span id="progressFileName" class="truncate max-w-xs">Đang tải lên...</span>
                    <span id="progressPercent" class="font-mono">0%</span>
                </div>
                <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-800">
                    <div id="progressBar" class="bg-gradient-to-r from-blue-500 to-cyan-400 h-2 rounded-full transition-all duration-200" style="width: 0%"></div>
                </div>
            </div>

            <!-- Upload Results List -->
            <div id="resultsList" class="mt-6 space-y-3"></div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-slate-900 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                © <?= date('Y') ?> PHP CDN Storage Gateway. Kiến trúc lưu trữ phân tán hiệu năng cao.
            </div>
            <div class="flex items-center gap-6">
                <a href="README.vi.md" class="hover:text-slate-400 transition">Tài liệu tiếng Việt</a>
                <a href="README.md" class="hover:text-slate-400 transition">English Docs</a>
                <a href="health.php" target="_blank" class="hover:text-slate-400 transition">System Status</a>
            </div>
        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.code-tab').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.tab === tabName) btn.classList.add('active');
            });
            document.querySelectorAll('.code-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            const activePanel = document.getElementById('panel-' + tabName);
            if (activePanel) activePanel.classList.remove('hidden');
        }

        function copySnippet(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text);
            alert('Đã copy đoạn mã vào bộ nhớ tạm!');
        }

        // Toggle Key Visibility
        function toggleKeyVisibility() {
            const input = document.getElementById('apiKeyInput');
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        // Upload Logic
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const progressFileName = document.getElementById('progressFileName');
        const resultsList = document.getElementById('resultsList');
        const apiKeyInput = document.getElementById('apiKeyInput');

        // Load saved API key from localStorage if available
        const savedKey = localStorage.getItem('cdn_api_key_local');
        if (savedKey) {
            apiKeyInput.value = savedKey;
        }

        ['dragenter', 'dragover'].forEach(name => {
            dropZone.addEventListener(name, (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(name => {
            dropZone.addEventListener(name, (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
            });
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
                alert('Vui lòng nhập API Key để xác thực upload!');
                apiKeyInput.focus();
                return;
            }
            localStorage.setItem('cdn_api_key_local', key);

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
            item.className = 'bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-col gap-2 shadow-lg';

            const badge = res.deduplicated 
                ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">TRÙNG NỘI DUNG (Deduplicated)</span>'
                : '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">FILE MỚI</span>';

            const webpBadge = res.converted_to_webp 
                ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">✨ AUTO WEBP</span>' 
                : '';

            const isImg = res.mime_type && res.mime_type.startsWith('image/');
            const previewHtml = isImg 
                ? `<img src="${res.url}" alt="Preview" class="w-12 h-12 object-cover rounded-xl border border-slate-800">`
                : `<div class="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center text-lg">📄</div>`;

            item.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 overflow-hidden">
                        ${previewHtml}
                        <div class="overflow-hidden">
                            <span class="text-xs font-semibold text-white truncate block max-w-xs">${originalName}</span>
                            <div class="flex items-center gap-2 mt-0.5">
                                ${badge}
                                ${webpBadge}
                                <span class="text-[11px] text-slate-400">${(res.size / 1024).toFixed(1)} KB</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-2">
                    <input type="text" readonly value="${res.url}" class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-300 font-mono select-all">
                    <button onclick="navigator.clipboard.writeText('${res.url}'); this.innerText='Đã copy'; setTimeout(() => this.innerText='Copy', 2000)" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-medium transition">
                        Copy
                    </button>
                    <a href="${res.url}" target="_blank" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-medium transition">
                        Xem
                    </a>
                </div>
            `;
            resultsList.prepend(item);
        }

        function renderError(name, errText) {
            const item = document.createElement('div');
            item.className = 'bg-rose-950/40 border border-rose-800/50 rounded-2xl p-4 text-xs text-rose-300';
            item.innerHTML = `<strong>Thất bại (${name}):</strong> ${errText}`;
            resultsList.prepend(item);
        }

        async function testHealth() {
            const key = apiKeyInput.value.trim();
            try {
                const headers = key ? { 'Authorization': 'Bearer ' + key } : {};
                const res = await fetch('health.php', { headers });
                const data = await res.json();
                alert('Kết nối thành công!\nTrạng thái: ' + data.status + '\nỔ cứng trống: ' + data.storage.free_human);
            } catch (e) {
                alert('Không thể kết nối đến server: ' + e.message);
            }
        }
    </script>
</body>
</html>
