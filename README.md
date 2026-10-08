# PHP CDN Storage Gateway

Hệ thống lưu trữ và phân phối file CDN hiệu năng cao viết bằng **PHP thuần**, biến **Shared Hosting** thành cụm máy chủ file chịu tải lớn kết hợp cùng CDN (Cloudflare, BunnyCDN, v.v.).

---

## ⚡ Các tính năng cốt lõi

1. **Content-Addressable Storage (CAS)**:
   - Tự động băm nội dung file (SHA-1 / MD5) để đặt tên file: `{hash}.{ext}`.
   - **Chống trùng lặp dữ liệu (Deduplication)**: Upload file cùng nội dung lập tức trả về URL cũ, không tốn thêm dung lượng đĩa.
   - **Tự động Cache-Busting cho CDN**: Bất cứ khi nào file có sửa đổi dù chỉ 1 byte, tên file mới hoàn toàn sẽ được tạo ra -> Link CDN mới hoàn toàn -> Tránh 100% tình trạng CDN cache file cũ mà không cần thủ công purge cache!

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
├── .htaccess            # Rewrite URL sạch (/f/{hash}.ext), CORS & bảo mật file gốc
├── config.php           # Cấu hình API key, dung lượng, thuật toán băm
├── upload.php           # Endpoint upload (Multipart, Binary stream, Chunked)
├── delete.php           # Endpoint xóa file theo hash
├── health.php           # Kiểm tra dung lượng đĩa và môi trường
├── index.php            # Web Uploader UI & Dashboard test trực quan
├── CdnClient.php        # Client SDK PHP thuần để nhúng vào dự án khác
├── storage/             # Thư mục lưu trữ file tĩnh được shard
│   └── .htaccess        # Khóa thực thi PHP, header CDN immutable 1 năm
└── tmp/                 # Thư mục tạm cho chunk upload và stream
```

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
