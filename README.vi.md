# PHP CDN Storage Gateway (Multi-Account Personal Google Drive Pool Edition)

[English Version](README.md) | **Tiếng Việt**

Hệ thống lưu trữ và phân phối file CDN hiệu năng cao viết bằng **PHP thuần**, biến **Shared Hosting** thành cụm máy chủ file kết hợp cùng **Pool Tài Khoản Google Drive Cá Nhân (15GB x N tài khoản)** và **Cloudflare CDN**.

---

## ⚡ Các tính năng cốt lõi

1. **Content-Addressable Storage (CAS)**:
   - Tự động băm nội dung file (SHA-1 / MD5) để đặt tên file: `{hash}.{ext}`.
   - **Chống trùng lặp dữ liệu (Deduplication)**: Upload file cùng nội dung lập tức trả về URL cũ, không tốn thêm dung lượng.
   - **Tự động Cache-Busting cho CDN**: Bất cứ khi nào file có sửa đổi dù chỉ 1 byte, mã băm mới sẽ được tạo ra -> Link CDN mới -> Tránh 100% tình trạng CDN cache file cũ mà không cần purge cache.

2. **Hợp nhất nhiều tài khoản Google Drive cá nhân (Pool 15GB x N)**:
   - Sử dụng trực tiếp tài khoản Gmail thông thường (`@gmail.com`) hoặc Google One.
   - **Cộng dồn không giới hạn dung lượng**: 1 tài khoản = 15GB, 3 tài khoản = 45GB, 10 tài khoản = 150GB hoàn toàn miễn phí.
   - **Phân bổ tải thông minh**: Cơ chế băm vòng (Round-Robin) tự động phân bổ file đều giữa các tài khoản cá nhân trong pool.
   - SQLite Metadata Store (`data/cdn_store.db`) theo dõi ánh xạ hash, file_id, và tài khoản lưu trữ.
   - Tùy chọn `GDRIVE_KEEP_LOCAL_CACHE`:
     - `true`: Giữ cache local trên hosting để phản hồi tức thì cho lần đầu tải.
     - `false`: Đẩy thẳng lên Google Drive và xóa file local (tiết kiệm 100% dung lượng hosting).

3. **Tự động tối ưu hóa WebP (Auto-WebP Engine)**:
   - Tự động nén và chuyển đổi định dạng ảnh (JPEG, PNG, GIF, BMP) sang định dạng `.webp`.
   - Giảm dung lượng file từ 60% – 85% mà chất lượng hiển thị giữ nguyên vẹn.
   - Tăng tốc độ load web tối đa, tiết kiệm băng thông CDN và dung lượng Google Drive.

4. **Bảo mật OAuth 2.0 Scoped App & Che giấu danh tính (Security Shield)**:
   - **OAuth 2.0 Refresh Token**: Ứng dụng xác thực độc lập qua mã ủy quyền của Google, không bao giờ cần mật khẩu tài khoản Gmail.
   - **Folder Scoping**: Hệ thống upload thẳng vào thư mục bạn chỉ định (VD: `cdn-aigiup`), hoàn toàn không truy cập hay ảnh hưởng dữ liệu cá nhân khác.
   - **Che giấu danh tính (Zero URL Exposure)**: Link Google Drive và File ID được giấu kín hoàn toàn đằng sau domain CDN (`https://cdn.yourdomain.com/f/{hash}.{ext}`). Khách truy cập không biết file nằm trên Google Drive, ngăn chặn triệt để hành vi report abuse.
   - **Bảo mật file khóa**: Thư mục `credentials/` và `data/` được chặn truy cập web tuyệt đối bằng `.htaccess` và loại trừ khỏi Git repository.

5. **Tối đa hóa tốc độ tải (0% PHP Memory on Read)**:
   - Người dùng và CDN tải file trực tiếp qua Web Server (Apache / LiteSpeed / Nginx) theo file tĩnh.
   - Cấu hình sẵn header `Cache-Control: public, max-age=31536000, immutable`.
   - Hỗ trợ Range Requests (`Accept-Ranges: bytes`) cho video/audio streaming và tua nhanh.

6. **Directory Sharding (Chia nhánh thư mục thông minh)**:
   - Tự động chia thư mục 2 tầng theo hash: `storage/ab/cd/{hash}.{ext}`.
   - Tránh nghẽn inode filesystem trên Shared Hosting khi lưu trữ hàng trăm ngàn đến hàng triệu file.

7. **Upload đa phương thức**:
   - `Multipart Form-Data` (`$_FILES`)
   - `Binary Stream Direct` (`php://input` - stream từng block 64KB, RAM máy chủ chỉ tốn vài KB dù file nặng hàng trăm MB)
   - `Chunked Upload` (Chia nhỏ file upload từng phần, hỗ trợ file lớn trên hosting bị giới hạn timeout)

8. **Bảo mật máy chủ tuyệt đối**:
   - Xác thực qua Bearer Token / `X-API-Key`.
   - Chặn và trung hòa toàn bộ file thực thi (`.php`, `.phtml`, `.phar`, `.sh`, `.py`, v.v.).
   - Thư mục `storage/` khóa cứng `php_flag engine off` và `RemoveHandler`, ngăn chặn thực thi mã độc.

---

## 📁 Cấu trúc thư mục

```text
php-cdn-storage/
├── .htaccess               # Rewrite URL sạch (/f/{hash}.ext), chặn truy cập trái phép
├── config.php              # Cấu hình API key, cache, giới hạn upload, Auto-WebP
├── upload.php              # Endpoint upload (Multipart, Binary stream, Chunked, Auto-WebP)
├── delete.php              # Endpoint xóa file (xóa cả hosting & Google Drive)
├── stream.php              # Dynamic streaming proxy từ Google Drive + CDN Cache
├── health.php              # Kiểm tra trạng thái hệ thống, dung lượng, Google Drive pool
├── index.php               # Landing page giới thiệu API, tích hợp console test upload
├── oauth_setup.php         # Wizard kích hoạt OAuth2 1-click & quản lý danh sách tài khoản
├── CdnClient.php           # Client SDK PHP thuần để nhúng vào ứng dụng khác
├── Database.php            # Tầng lưu trữ SQLite theo dõi ánh xạ file
├── GoogleDriveDriver.php   # Driver kết nối Google Drive API v3 qua OAuth2 Refresh Token
├── GoogleDriveManager.php  # Quản lý pool nhiều tài khoản Google Drive cá nhân
├── credentials/            # Chứa file cấu hình tài khoản (Được bảo vệ 100%)
│   ├── .htaccess           # Chặn tải file credential từ web
│   └── account_sample.json # Mẫu cấu hình tài khoản
├── data/                   # Database SQLite lưu mapping (Được bảo vệ 100%)
│   └── .htaccess           # Chặn tải DB từ web
├── storage/                # Thư mục cache file tĩnh trên hosting
│   └── .htaccess           # Khóa thực thi PHP, header CDN immutable 1 năm
└── tmp/                    # Thư mục tạm cho chunk upload, token cache và stream
```

---

## 🛡️ Hướng dẫn thiết lập Google Drive Cá Nhân (15GB x N Pool)

*(Thiết lập tự động qua giao diện `oauth_setup.php` chỉ trong 2 phút - **Không cần Service Account**)*

### Bước 1: Tạo OAuth Client ID trên Google Cloud Console

1. Truy cập [Google Cloud Console ➔ Credentials](https://console.cloud.google.com/apis/credentials) (Project của bạn, ví dụ `aigiup-cdn`).
2. **Cấu hình OAuth consent screen**:
   - Vào [OAuth consent screen](https://console.cloud.google.com/apis/credentials/consent).
   - Chọn User Type: **External** (Bên ngoài) ➔ Bấm **Create**.
   - Điền **App name** (VD: `CDN Storage`) và email liên hệ của bạn.
   - Bấm **Save and Continue** đến bước **Test users** (Người dùng thử nghiệm):
     > **⚠️ BƯỚC QUAN TRỌNG NHẤT (Tránh lỗi 403 access_denied):**  
     > Bấm **ADD USERS** ➔ Nhập chính địa chỉ Gmail cá nhân của bạn (ví dụ `hducit1@gmail.com`) ➔ Bấm **Save**.  
     > *(Nếu không thêm, Google sẽ chặn đăng nhập với thông báo "Ứng dụng đang trong giai đoạn kiểm thử / access_denied")*.
3. **Tạo thông tin xác thực (Credentials)**:
   - Quay lại tab **Credentials** ➔ Bấm **Create Credentials** ➔ Chọn **OAuth client ID**.
   - Application type: Chọn **Web application**.
   - Mục **Authorized redirect URIs**: Bấm **Add URI** và dán chính xác:
     ```text
     https://your-cdn-domain.com/oauth_setup.php
     ```
     *(Ví dụ: `https://cdn.aigiup.com/oauth_setup.php`)*.
   - Bấm **Create** ➔ Copy **Client ID** và **Client Secret**.

---

### Bước 2: Kích hoạt tự động qua trang `oauth_setup.php`

1. Mở trình duyệt truy cập: `https://your-cdn-domain.com/oauth_setup.php`.
2. Nhập **Client ID**, **Client Secret** và **Folder ID** (thư mục trên Drive của bạn, ví dụ `15n4k5hfyjhjymkHbNizRh179UzeGYEQH`).
3. Bấm **"Đăng Nhập Ủy Quyền Với Google"** ➔ Chọn tài khoản Gmail của bạn ➔ Bấm **Tiếp tục / Cho phép**.
4. ➔ Hệ thống tự động tạo file cấu hình `credentials/account_{email}.json` và kích hoạt tài khoản vào Pool lưu trữ!

---

### Bước 3: Hợp nhất nhiều tài khoản để tăng dung lượng (Multi-Account Pooling)

Để mở rộng từ 15GB lên 30GB, 45GB, 75GB+:
1. Lặp lại bước 1.2: Thêm các Gmail phụ của bạn vào danh sách **Test users** trên Google Cloud Console.
2. Mở lại trang `https://your-cdn-domain.com/oauth_setup.php`.
3. Bấm **Đăng nhập ủy quyền với Google** và chọn tài khoản Gmail thứ 2 (hoặc 3, 4...).
4. ➔ Hệ thống sẽ tự động lưu thêm `credentials/account_gmail2.json`, `credentials/account_gmail3.json`...
5. Toàn bộ tài khoản này sẽ được hệ thống tự động băm vòng phân bổ tải (Round-Robin), tạo nên kho lưu trữ đám mây hợp nhất khổng lồ!


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

