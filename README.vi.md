# PHP CDN Storage Gateway (Multi-Account Google Drive Pool Edition)

[English Version](README.md) | **Tiếng Việt**

Hệ thống lưu trữ và phân phối file CDN hiệu năng cao viết bằng **PHP thuần**, biến **Shared Hosting** thành cụm máy chủ file kết hợp cùng **Google Drive Pool (5TB x N)** và **Cloudflare CDN**.

---

## ⚡ Các tính năng cốt lõi

1. **Content-Addressable Storage (CAS)**:
   - Tự động băm nội dung file (SHA-1 / MD5) để đặt tên file: `{hash}.{ext}`.
   - **Chống trùng lặp dữ liệu (Deduplication)**: Upload file cùng nội dung lập tức trả về URL cũ, không tốn thêm dung lượng.
   - **Tự động Cache-Busting cho CDN**: Bất cứ khi nào file có sửa đổi dù chỉ 1 byte, mã băm mới sẽ được tạo ra -> Link CDN mới -> Tránh 100% tình trạng CDN cache file cũ mà không cần purge cache.

2. **Hỗ trợ Pool nhiều tài khoản Google Drive 5TB**:
   - Tích hợp không giới hạn số lượng tài khoản Google Drive thông qua Service Account.
   - Hỗ trợ lưu trữ phân tán, băm vòng tự động giữa các tài khoản trong pool.
   - SQLite Metadata Store (`data/cdn_store.db`) theo dõi ánh xạ hash, file_id, và tài khoản.
   - Tùy chọn `GDRIVE_KEEP_LOCAL_CACHE`:
     - `true`: Giữ cache local trên hosting để phản hồi tức thì cho lần đầu tải.
     - `false`: Đẩy thẳng lên Google Drive và xóa file local (tiết kiệm 100% dung lượng hosting).

3. **Cơ chế bảo vệ tài khoản Google chống hack và khóa (Security Shield)**:
   - **Cách ly tuyệt đối (Isolated Service Account)**: Không bao giờ sử dụng mật khẩu hay Master OAuth của tài khoản Google chính. Service Account là một bot riêng biệt.
   - **Giới hạn thư mục (Folder Scoping)**: Tài khoản chính chỉ cần tạo 1 thư mục riêng (VD: `CDN_Storage`) và chia sẻ quyền Editor cho email Service Account. Bot **chỉ có quyền trong đúng thư mục đó**, hoàn toàn KHÔNG THỂ truy cập Gmail, Photos, danh bạ hay bất kỳ file cá nhân nào khác trong tài khoản của bạn.
   - **Che giấu danh tính (Zero URL Exposure)**: Link Google Drive và File ID được giấu kín hoàn toàn đằng sau domain CDN (`https://cdn.yourdomain.com/f/{hash}.{ext}`). Khách truy cập không biết file nằm trên Google Drive, ngăn chặn triệt để hành vi report abuse hoặc bot cào trực tiếp.
   - **Bảo mật file khóa**: Thư mục `credentials/` và `data/` được chặn truy cập web tuyệt đối bằng `.htaccess` và loại trừ khỏi Git repository.

4. **Tối đa hóa tốc độ tải (0% PHP Memory on Read)**:
   - Người dùng và CDN tải file trực tiếp qua Web Server (Apache / LiteSpeed / Nginx) theo file tĩnh.
   - Cấu hình sẵn header `Cache-Control: public, max-age=31536000, immutable`.
   - Hỗ trợ Range Requests (`Accept-Ranges: bytes`) cho video/audio streaming và tua nhanh.

5. **Directory Sharding (Chia nhánh thư mục thông minh)**:
   - Tự động chia thư mục 2 tầng theo hash: `storage/ab/cd/{hash}.{ext}`.
   - Tránh nghẽn inode filesystem trên Shared Hosting khi lưu trữ hàng trăm ngàn đến hàng triệu file.

6. **Upload đa phương thức**:
   - `Multipart Form-Data` (`$_FILES`)
   - `Binary Stream Direct` (`php://input` - stream từng block 64KB, RAM máy chủ chỉ tốn vài KB dù file nặng hàng trăm MB)
   - `Chunked Upload` (Chia nhỏ file upload từng phần, hỗ trợ file lớn trên hosting bị giới hạn timeout)

7. **Bảo mật tuyệt đối**:
   - Xác thực qua Bearer Token / `X-API-Key`.
   - Chặn và trung hòa toàn bộ file thực thi (`.php`, `.phtml`, `.phar`, `.sh`, `.py`, v.v.).
   - Thư mục `storage/` khóa cứng `php_flag engine off` và `RemoveHandler`, ngăn chặn thực thi mã độc.

---

## 📁 Cấu trúc thư mục

```text
php-cdn-storage/
├── .htaccess               # Rewrite URL sạch (/f/{hash}.ext), chặn truy cập trái phép
├── config.php              # Cấu hình API key, cache, giới hạn upload
├── upload.php              # Endpoint upload (Multipart, Binary stream, Chunked)
├── delete.php              # Endpoint xóa file (xóa cả hosting & Google Drive)
├── stream.php              # Dynamic streaming proxy từ Google Drive + CDN Cache
├── health.php              # Kiểm tra trạng thái hệ thống, dung lượng, Google Drive pool
├── index.php               # Dashboard web kéo thả upload file trực quan
├── CdnClient.php           # Client SDK PHP thuần để nhúng vào ứng dụng khác
├── Database.php            # Tầng lưu trữ SQLite theo dõi ánh xạ file
├── GoogleDriveDriver.php   # Driver kết nối Google Drive API v3 qua RS256 JWT
├── GoogleDriveManager.php  # Quản lý pool nhiều tài khoản Google Drive
├── credentials/            # Chứa file Service Account JSON (Được bảo vệ 100%)
│   ├── .htaccess           # Chặn tải file credential từ web
│   └── account_sample.json # Mẫu cấu hình
├── data/                   # Database SQLite lưu mapping (Được bảo vệ 100%)
│   └── .htaccess           # Chặn tải DB từ web
├── storage/                # Thư mục cache file tĩnh trên hosting
│   └── .htaccess           # Khóa thực thi PHP, header CDN immutable 1 năm
└── tmp/                    # Thư mục tạm cho chunk upload và stream
```

---

## 🛡️ Hướng dẫn thiết lập Google Drive an toàn (1 hoặc nhiều tài khoản)

### Bước 1: Tạo Google Service Account
1. Truy cập [Google Cloud Console](https://console.cloud.google.com/) và tạo một Project mới (VD: `cdn-storage-pool`).
   > **Mẹo quan trọng:** Nếu dùng email công ty / trường học bị báo lỗi chính sách `iam.disableServiceAccountKeyCreation`, hãy dùng **tài khoản Gmail cá nhân (`@gmail.com`)** để tạo Project. Tài khoản cá nhân không bao giờ bị chặn tạo Key!
2. Vào **APIs & Services** → **Library** → Tìm và Bật **Google Drive API**.
3. Vào **IAM & Admin** → **Service Accounts** → Bấm **Create Service Account**:
   - Điền tên (VD: `cdn-bot-1`) → Bấm **Done**.
4. Bấm vào Service Account vừa tạo → Chọn tab **Keys** → **Add Key** → **Create new key** → Chọn **JSON** và tải file về máy.

### Bước 2: Chia sẻ thư mục từ Google Drive 5TB của bạn
1. Đăng nhập vào Google Drive chứa dung lượng của bạn.
2. Tạo 1 thư mục mới, ví dụ: `CDN_FILES`.
3. Nhấp chuột phải vào thư mục `CDN_FILES` → Chọn **Chia sẻ (Share)**.
4. Dán địa chỉ email của Service Account (có đuôi `@...iam.gserviceaccount.com` trong file JSON) vào ô chia sẻ, đặt quyền là **Người chỉnh sửa (Editor)**.
5. Sao chép **Folder ID** từ thanh địa chỉ trình duyệt:
   ```text
   https://drive.google.com/drive/folders/1AbC2xYz3_D4eF5gHiJkLmnOpQrStUvwX
                                          └───────────┬───────────┘
                                                Đây là Folder ID
   ```

### Bước 3: Đưa khóa vào dự án
1. Đổi tên file JSON vừa tải về thành `credentials/account_1.json` (Lưu ý: Không đặt tên là `account_sample.json`).
2. Mở file `credentials/account_1.json` và bổ sung thêm trường `"folder_id"` ở dòng cuối:
   ```json
   {
     "type": "service_account",
     "project_id": "...",
     "private_key_id": "...",
     "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
     "client_email": "sa-bot@project.iam.gserviceaccount.com",
     "client_id": "...",
     "folder_id": "1AbC2xYz3_D4eF5gHiJkLmnOpQrStUvwX"
   }
   ```
3. **Thêm tài khoản 5TB thứ 2, thứ 3...**: Chỉ cần tạo file `credentials/account_2.json`, `credentials/account_3.json`. Hệ thống sẽ tự động nhận diện và chia tải vào pool!

---

## 🚀 Hướng dẫn triển khai lên Web Hosting

### Lựa chọn 1: Triển khai chuẩn bảo mật với thư mục `public_html` (Khuyên dùng)
Nếu hosting của bạn cho phép lưu file ngoài `public_html`, hãy đặt code theo cấu trúc:
```text
/home/username/
├── credentials/              # Chứa các file account_*.json bí mật
├── data/                     # Chứa cdn_store.db
├── tmp/                      # Bộ nhớ đệm tạm
├── config.php
├── Database.php
├── GoogleDriveDriver.php
├── GoogleDriveManager.php
└── public_html/              # Thư mục Webroot công khai
    ├── .htaccess             # File cấu hình rewrite và CDN headers
    ├── index.php             # Giao diện web upload
    ├── upload.php            # Endpoint API upload
    ├── delete.php            # Endpoint API delete
    ├── stream.php            # Dynamic streaming
    ├── health.php            # Kiểm tra trạng thái
    └── storage/              # Cache file tĩnh
```
*Ghi chú:* Sửa dòng require trong `public_html/upload.php` thành `require_once __DIR__ . '/../config.php';`.

### Lựa chọn 2: Triển khai trực tiếp toàn bộ vào `public_html`
Nếu hosting chỉ cho upload vào duy nhất `public_html`:
- Upload toàn bộ source code vào `public_html`.
- Các file `.htaccess` trong thư mục `credentials/` và `data/` đã được cấu hình sẵn lệnh `Require all denied` và `Deny from all` để chặn tuyệt đối mọi truy cập từ web.
- Cấp quyền ghi (chmod `755`) cho các thư mục: `storage`, `tmp`, `data`.

---

## 🌐 Tích hợp với Ứng dụng Vercel (Next.js / Node.js)

Khi triển khai ứng dụng thương mại điện tử, web bán hàng hoặc portfolio trên Vercel:

### 💡 Kiến trúc Microservice: Có nên lưu Database ở CDN không?
- **Phía CDN Gateway**: **CHỈ LƯU METADATA KỸ THUẬT** (`hash`, `gdrive_file_id`, `account_id`, `mime_type`, `size` trong SQLite `cdn_store.db`). CDN hoạt động hoàn toàn như một Media Provider độc lập (tương tự Cloudinary, AWS S3).
- **Phía Vercel App**: **LƯU TOÀN BỘ THÔNG TIN NGHIỆP VỤ** trong Database chính của bạn (PostgreSQL, Supabase, MySQL...):
  - Ví dụ bảng `products`: `thumbnail_url` = `https://cdn.yourdomain.com/f/{hash}.webp`.
  - Phía Vercel gọi API upload ngầm từ Server Route (`/api/upload`) bằng secret `CDN_API_KEY` (không lộ ra trình duyệt client).

### Ví dụ code upload từ Next.js / Vercel:

```typescript
// app/api/upload/route.ts (Next.js App Router)
import { NextResponse } from 'next/server';

export async function POST(req: Request) {
  const formData = await req.formData();
  const file = formData.get('file') as File;

  if (!file) {
    return NextResponse.json({ error: 'No file provided' }, { status: 400 });
  }

  const cdnFormData = new FormData();
  cdnFormData.append('file', file);

  const cdnResponse = await fetch('https://cdn.yourdomain.com/upload.php', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${process.env.CDN_API_KEY}`,
    },
    body: cdnFormData,
  });

  const result = await cdnResponse.json();
  // result = { success: true, url: "https://cdn.yourdomain.com/f/...", hash: "..." }
  return NextResponse.json(result);
}
```

---

### 1. Tích hợp với Node.js (Express / Axios / Native Fetch)

```javascript
// Cài đặt thư viện nếu cần: npm install axios form-data
// Cách 1: Sử dụng Native fetch (Node.js 18+)
import fs from 'fs';

async function uploadFileNode(filePath) {
  const formData = new FormData();
  const fileBlob = new Blob([fs.readFileSync(filePath)]);
  formData.append('file', fileBlob, 'avatar.png');

  const res = await fetch('https://cdn.yourdomain.com/upload.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer YOUR_API_KEY',
    },
    body: formData,
  });

  const data = await res.json();
  if (data.success) {
    console.log('Upload thành công! URL:', data.url);
    return data.url;
  }
  throw new Error(data.error);
}

// Cách 2: Sử dụng Axios + form-data
import axios from 'axios';
import FormData from 'form-data';

async function uploadFileAxios(filePath) {
  const form = new FormData();
  form.append('file', fs.createReadStream(filePath));

  const response = await axios.post('https://cdn.yourdomain.com/upload.php', form, {
    headers: {
      ...form.getHeaders(),
      'Authorization': 'Bearer YOUR_API_KEY',
    },
  });

  return response.data;
}
```

---

### 2. Tích hợp với C# (.NET 6 / 7 / 8)

```csharp
using System;
using System.IO;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text.Json;
using System.Threading.Tasks;

public class CdnService
{
    private readonly HttpClient _httpClient;
    private readonly string _apiKey = "YOUR_API_KEY";
    private readonly string _uploadEndpoint = "https://cdn.yourdomain.com/upload.php";

    public CdnService(HttpClient httpClient)
    {
        _httpClient = httpClient;
    }

    public async Task<string> UploadFileAsync(string localFilePath)
    {
        using var form = new MultipartFormDataContent();
        await using var fileStream = File.OpenRead(localFilePath);
        using var streamContent = new StreamContent(fileStream);

        // Thiết lập header content type phù hợp (hoặc application/octet-stream)
        streamContent.Headers.ContentType = new MediaTypeHeaderValue("image/jpeg");
        form.Add(streamContent, "file", Path.GetFileName(localFilePath));

        using var request = new HttpRequestMessage(HttpMethod.Post, _uploadEndpoint)
        {
            Content = form
        };
        request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", _apiKey);

        var response = await _httpClient.SendAsync(request);
        response.EnsureSuccessStatusCode();

        var json = await response.Content.ReadAsStringAsync();
        using var doc = JsonDocument.Parse(json);
        var root = doc.RootElement;

        if (root.GetProperty("success").GetBoolean())
        {
            return root.GetProperty("url").GetString();
        }

        throw new Exception(root.GetProperty("error").GetString());
    }
}
```

---

### 3. Tích hợp bằng PHP (`CdnClient.php`)
```php
require_once 'CdnClient.php';

$cdn = new CdnClient('https://cdn.yourdomain.com', 'YOUR_API_KEY');

// Upload file thường
$res = $cdn->uploadFile('/tmp/photo.jpg');
echo "CDN Link: " . $res['url'];

// Upload file dung lượng lớn dạng chunked (chia nhỏ mỗi chunk 5MB)
$res = $cdn->uploadChunked('/path/to/big-video.mp4', 5 * 1024 * 1024);

// Xóa file
$cdn->deleteFile($res['hash']);
```

---

### 4. Upload qua cURL (Multipart & Binary Stream)
```bash
# Multipart thông thường
curl -X POST "https://cdn.yourdomain.com/upload.php" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "file=@/path/to/image.jpg"

# Binary stream trực tiếp (0% RAM overhead)
curl -X POST "https://cdn.yourdomain.com/upload.php" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "X-File-Name: document.pdf" \
  --data-binary "@/path/to/document.pdf"
```

