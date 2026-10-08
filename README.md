# PHP CDN Storage Gateway (Multi-Account Google Drive Pool Edition)

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
   - **Giới hạn thư mục (Folder Scoping)**: Tài khoản chính chỉ cần tạo 1 thư mục riêng (VD: `CDN_Storage`) và chia sẻ quyền Editor cho email Service Account. Bot **chỉ có quyền trong đúng thư mục đó**, hoàn toàn KHÔNG THỂ truy cập Gmail, Photos, danh bạ hay bất kỳ file cá nhân nào khác trong tài khoản 5TB của bạn.
   - **Che giấu danh tính (Zero URL Exposure)**: Link Google Drive và File ID được giấu kín hoàn toàn đằng sau domain CDN (`https://cdn.yourdomain.com/f/{hash}.{ext}`). Khách truy cập không biết file nằm trên Google Drive, ngăn chặn triệt để hành vi report abuse hoặc bot cào trực tiếp.
   - **Bảo mật file khóa**: Thư mục `credentials/` và `data/` được chặn truy cập web tuyệt đối bằng `.htaccess` và loại trừ khỏi Git repository.

2. **Tối đa hóa tốc độ tải (0% PHP Memory on Read)**:
   - Người dùng và CDN tải file trực tiếp qua Web Server (Apache / LiteSpeed / Nginx) theo file tĩnh.
   - Cấu hình sẵn header `Cache-Control: public, max-age=31536000, immutable`.
   - Hỗ trợ Range Requests (`Accept-Ranges: bytes`) cho video/audio streaming và tua nhanh.

3. **Directory Sharding (Chia nhánh thư mục thông minh)**:
   - Tự động chia thư mục 2 tầng theo hash: `storage/ab/cd/{hash}.{ext}`.
   - Tránh nghẽn inode filesystem trên Shared Hosting khi lưu trữ hàng trăm ngàn đến hàng triệu file.

4. **Upload đa phương thức**:
   - `Multipart Form-Data` (`$_FILES`)
   - `Binary Stream Direct` (`php://input` - stream từng block 64KB, RAM máy chủ chỉ tốn vài KB dù file nặng hàng trăm MB)
   - `Chunked Upload` (Chia nhỏ file upload từng phần, hỗ trợ file lớn trên hosting bị giới hạn timeout)

5. **Bảo mật tuyệt đối**:
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
2. Vào **APIs & Services** → **Enable APIs and Services** → Tìm và Bật **Google Drive API**.
3. Vào **IAM & Admin** → **Service Accounts** → Bấm **Create Service Account**:
   - Điền tên (VD: `cdn-bot-1`) → Bấm **Done**.
4. Bấm vào Service Account vừa tạo → Chọn tab **Keys** → **Add Key** → **Create new key** → Chọn **JSON** và tải file về máy.

### Bước 2: Chia sẻ thư mục từ Google Drive 5TB của bạn
1. Đăng nhập vào Google Drive 5TB cá nhân của bạn.
2. Tạo 1 thư mục mới, ví dụ: `CDN_FILES`.
3. Nhấp chuột phải vào thư mục `CDN_FILES` → Chọn **Chia sẻ (Share)**.
4. Dán địa chỉ email của Service Account (có đuôi `@...iam.gserviceaccount.com` trong file JSON) vào ô chia sẻ, đặt quyền là **Người chỉnh sửa (Editor)**.
5. Sao chép **Folder ID** từ thanh địa chỉ trình duyệt:
   `https://drive.google.com/drive/folders/PASTE_YOUR_FOLDER_ID_HERE`

### Bước 3: Đưa khóa vào dự án
1. Đổi tên file JSON vừa tải về thành `credentials/account_1.json`.
2. Mở file `credentials/account_1.json` và bổ sung thêm trường `"folder_id"`:
   ```json
   {
     "type": "service_account",
     "project_id": "...",
     "private_key": "...",
     "client_email": "...",
     "folder_id": "1a2b3c4d5e6f7g8h9_ID_THƯ_MỤC_VỪA_TẠO"
   }
   ```
3. **Thêm tài khoản 5TB thứ 2, thứ 3...**: Chỉ cần tạo file `credentials/account_2.json`, `credentials/account_3.json`. Hệ thống sẽ tự động nhận diện và chia tải vào pool!

---

## 🚀 Hướng dẫn triển khai lên Shared Hosting

### Bước 1: Upload code
- Upload toàn bộ nội dung thư mục lên `public_html` (hoặc thư mục subdomain như `cdn.yourdomain.com`).
- Đảm bảo quyền ghi (chmod `755`) cho 2 thư mục `storage` và `tmp`.

### Bước 2: Cấu hình API Key
Mở file `config.php`:
```php
define('CDN_API_KEY', 'thay_doi_chuoi_bi_mat_o_day_123456');
```
*(Hoặc thiết lập biến môi trường `CDN_API_KEY` nếu hosting hỗ trợ)*.

### Bước 3: Cấu hình CDN (Cloudflare)
1. Thêm bản ghi DNS (VD: `cdn.yourdomain.com`) trỏ về IP của Shared Hosting và **Bật Proxy (Đám mây cam)**.
2. Tại Cloudflare Dashboard: **Caching** → **Cache Rules** (hoặc Page Rules):
   - **URL Path** matches: `/f/*` hoặc `/storage/*`
   - **Eligible for cache**: Cache Everything
   - **Edge TTL**: Respect origin headers (hoặc 1 year)
   - **Browser TTL**: Respect origin headers

---

## 💻 Hướng dẫn gọi API

### 1. Upload qua cURL (Multipart)
```bash
curl -X POST "https://cdn.yourdomain.com/upload.php" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "file=@/path/to/image.jpg"
```

**JSON trả về:**
```json
{
  "success": true,
  "status": "uploaded",
  "deduplicated": false,
  "file_name": "8c194b42c9d7bd126c01b9a3e2af4e254d28e0da.jpg",
  "hash": "8c194b42c9d7bd126c01b9a3e2af4e254d28e0da",
  "url": "https://cdn.yourdomain.com/f/8c194b42c9d7bd126c01b9a3e2af4e254d28e0da.jpg",
  "size": 1048576,
  "mime_type": "image/jpeg"
}
```

### 2. Stream Binary trực tiếp (Siêu nhanh, 0 tốn RAM)
```bash
curl -X POST "https://cdn.yourdomain.com/upload.php" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "X-File-Name: document.pdf" \
  --data-binary "@/path/to/document.pdf"
```

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
