# PHP CDN Storage Gateway (Multi-Account Personal Google Drive Pool Edition)

**English** | [Tiếng Việt](README.vi.md)

A high-performance file storage and CDN delivery gateway built with **pure PHP**, transforming **Shared Hosting** into a distributed file cluster backed by a **Personal Google Drive Account Pool (15GB x N)** and **Cloudflare CDN**.

---

## ⚡ Core Features

1. **Content-Addressable Storage (CAS)**:
   - Automatically hashes file contents (SHA-1 / MD5) to form filenames: `{hash}.{ext}`.
   - **Zero Duplicate Storage (Deduplication)**: Uploading identical files returns the existing URL immediately without consuming additional storage.
   - **Automatic CDN Cache-Busting**: Any change to file content generates a new cryptographic hash and a new CDN link, completely eliminating stale edge cache issues without requiring manual purge.

2. **Personal Google Drive Multi-Account Pooling (15GB x N Pool)**:
   - Connect standard personal Google accounts (`@gmail.com`) or Google One subscriptions.
   - **Unlimited Aggregate Capacity**: 1 account = 15GB, 3 accounts = 45GB, 10 accounts = 150GB of free cloud storage.
   - **Intelligent Load Distribution**: Built-in Round-Robin hashing distributes files evenly across accounts in the pool.
   - Lightweight SQLite Metadata Store (`data/cdn_store.db`) tracks mappings between hashes, Google Drive file IDs, and account IDs.
   - Flexible `GDRIVE_KEEP_LOCAL_CACHE` toggle:
     - `true`: Keeps a local cache on hosting disk for instant origin responses.
     - `false`: Streams directly to Google Drive and cleans up local storage (0% hosting disk consumed).

3. **Auto-WebP Optimization Engine**:
   - Automatically converts uploaded images (JPEG, PNG, GIF, BMP) to `.webp` format.
   - Compresses file size by 60% – 85% while preserving visual clarity.
   - Drastically accelerates website page loads and conserves CDN bandwidth & Drive quota.

4. **OAuth 2.0 Scoped App & Identity Shield (Security)**:
   - **OAuth 2.0 Refresh Tokens**: Independent authorization tokens per account. No passwords ever stored or requested.
   - **Folder Scoping**: Uploads directly into your designated storage folder (e.g., `cdn-aigiup`), completely isolated from any personal emails, contacts, or documents.
   - **Zero URL Exposure**: Google Drive links and file IDs are completely hidden behind the CDN reverse proxy domain (`https://cdn.yourdomain.com/f/{hash}.{ext}`). Visitors cannot inspect or abuse origin storage.
   - **Protected Credentials & DB**: The `credentials/` and `data/` directories are strictly protected from web access via `.htaccess` rules and excluded from Git.

5. **Zero PHP Memory on Reads**:
   - Cached files are served statically directly by the web server (Apache / LiteSpeed / Nginx) with 0% PHP overhead.
   - Pre-configured with `Cache-Control: public, max-age=31536000, immutable` headers.
   - Full support for HTTP Range Requests (`Accept-Ranges: bytes`) for smooth video/audio streaming and scrubbing.

6. **2-Level Directory Sharding**:
   - Automatically partitions local files into a 2-level directory tree: `storage/ab/cd/{hash}.{ext}`.
   - Prevents filesystem inode bottlenecks and slowdowns on shared hosting when storing millions of files.

7. **Multi-Protocol Upload Support**:
   - Standard `Multipart Form-Data` (`$_FILES`).
   - `Direct Binary Stream` (`php://input` - streams in 64KB chunks, consuming minimal RAM even for files over 500MB).
   - `Chunked Upload API` (splits large files into chunks to bypass server timeouts).

8. **Server-Level Script Hardening**:
   - Authenticated via Bearer Token or `X-API-Key`.
   - Blocks and neutralizes executable scripts (`.php`, `.phtml`, `.phar`, `.sh`, `.py`, etc.).
   - The `storage/` directory enforces `php_flag engine off` and `RemoveHandler` to disarm malicious file execution.

---

## 📁 Directory Structure

```text
php-cdn-storage/
├── .htaccess               # Clean URL rewriting (/f/{hash}.ext) & security guards
├── config.php              # Global configuration (API keys, cache rules, upload limits, Auto-WebP)
├── upload.php              # Upload endpoint (Multipart, Binary Stream, Chunked, Auto-WebP)
├── delete.php              # Delete endpoint (removes both from hosting and Google Drive)
├── stream.php              # Dynamic streaming proxy from Google Drive + disk cache
├── health.php              # Health monitoring, disk space, and pool diagnostic endpoint
├── index.php               # Product landing page & interactive testing console
├── oauth_setup.php         # 1-Click OAuth2 authorization wizard & pool status manager
├── CdnClient.php           # Pure PHP Client SDK for embedding into other projects
├── Database.php            # SQLite metadata database layer
├── GoogleDriveDriver.php   # Pure PHP Google Drive v3 client via OAuth 2.0 Refresh Tokens
├── GoogleDriveManager.php  # Multi-account Google Drive pool manager
├── credentials/            # Account JSON configurations (strictly protected)
│   ├── .htaccess           # Forbids web access to JSON files
│   └── account_sample.json # Sample configuration schema
├── data/                   # SQLite database directory (strictly protected)
│   └── .htaccess           # Forbids web access to database files
├── storage/                # Local cache directory with immutable headers
│   └── .htaccess           # Disables PHP execution and sets 1-year cache headers
└── tmp/                    # Temporary scratch storage for chunks, tokens, and streams
```

---

## 🛡️ Personal Google Drive Setup (15GB x N Pool)

*(Automated setup via `oauth_setup.php` wizard in 2 minutes - **No Service Account required**)*

### Step 1: Create OAuth Client ID on Google Cloud Console

1. Open [Google Cloud Console ➔ Credentials](https://console.cloud.google.com/apis/credentials) (Select your project, e.g. `aigiup-cdn`).
2. **Configure OAuth consent screen**:
   - Go to [OAuth consent screen](https://console.cloud.google.com/apis/credentials/consent).
   - Select User Type: **External** ➔ Click **Create**.
   - Fill in App name (e.g., `CDN Storage`) and your email address.
   - Click **Save and Continue** until the **Test users** step:
     > **⚠️ CRITICAL STEP (Prevents 403 access_denied):**  
     > Click **ADD USERS** ➔ Enter your own Gmail address (e.g. `hducit1@gmail.com`) ➔ Click **Save**.  
     > *(If not added, Google will block sign-in with "Application is in testing mode / access_denied")*.
3. **Create Credentials**:
   - Go to **Credentials** tab ➔ Click **Create Credentials** ➔ Select **OAuth client ID**.
   - Application type: Select **Web application**.
   - Under **Authorized redirect URIs**: Click **Add URI** and paste:
     ```text
     https://your-cdn-domain.com/oauth_setup.php
     ```
     *(Example: `https://cdn.aigiup.com/oauth_setup.php`)*.
   - Click **Create** ➔ Copy the **Client ID** and **Client Secret**.

---

### Step 2: 1-Click Authorization via `oauth_setup.php`

1. Open your browser and visit: `https://your-cdn-domain.com/oauth_setup.php`.
2. Paste your **Client ID**, **Client Secret**, and **Folder ID** (e.g. `15n4k5hfyjhjymkHbNizRh179UzeGYEQH`).
3. Click **"Authorize with Google"** ➔ Sign in with your personal Google account ➔ Grant Drive permissions.
4. ➔ The system automatically saves `credentials/account_{email}.json` and activates the account in the pool!

---

### Step 3: Pool Multiple Personal Accounts (Multi-Account Scaling)

To scale storage from 15GB to 30GB, 45GB, 75GB+:
1. Repeat Step 1.2: Add your secondary Gmail addresses to **Test users** in the Google Cloud Console.
2. Re-visit `https://your-cdn-domain.com/oauth_setup.php`.
3. Click **Authorize with Google** and sign in with your 2nd (or 3rd, 4th...) Gmail account.
4. ➔ The system automatically saves `credentials/account_gmail2.json`, `credentials/account_gmail3.json`, etc.
5. All connected accounts are aggregated into a single unified storage pool with automatic Round-Robin distribution!


---

## 🚀 Shared Hosting Deployment Guide

### Option A: Standard Security Structure with `public_html` (Recommended)
If your hosting allows files outside `public_html`, structure your deployment like this:
```text
/home/username/
├── credentials/              # Private Google account JSON configurations
├── data/                     # SQLite database (cdn_store.db)
├── tmp/                      # Scratch storage
├── config.php
├── Database.php
├── GoogleDriveDriver.php
├── GoogleDriveManager.php
└── public_html/              # Web-accessible root
    ├── .htaccess             # Rewrites and edge cache headers
    ├── index.php             # Upload interface
    ├── upload.php            # Upload endpoint
    ├── delete.php            # Delete endpoint
    ├── stream.php            # Streaming proxy
    ├── health.php            # Health probe
    └── storage/              # Static file cache
```
*Note:* Adjust the config require path in files inside `public_html/` to `require_once __DIR__ . '/../config.php';`.

### Option B: Direct Root Deployment into `public_html`
If your hosting restricts file management strictly to `public_html`:
- Upload the entire repository into `public_html`.
- The included `.htaccess` files inside `credentials/` and `data/` enforce `Require all denied` / `Deny from all` to completely block direct browser access.
- Ensure write permissions (`chmod 755`) for `storage`, `tmp`, and `data`.

---

## 🌐 Consuming from External Apps (e.g. Next.js on Vercel)

When integrating this CDN with an E-commerce store, CMS, or Web App running on Vercel:

### 💡 Microservice Architecture: Should the CDN store business data?
- **CDN Gateway**: **Stores only technical metadata** (`hash`, `gdrive_file_id`, `account_id`, `mime_type`, `size` inside `data/cdn_store.db`). It functions as a stateless, dedicated media provider (identical to AWS S3 or Cloudinary).
- **Vercel Consumer App**: **Stores business relations** in your primary database (e.g., PostgreSQL, Supabase, MongoDB, MySQL):
  - Example `products` table: `image_url` = `https://cdn.yourdomain.com/f/{hash}.webp`.
  - The Vercel app calls the upload endpoint via a secure Server Action or API Route (`/api/upload`) using the secret `CDN_API_KEY`.

### Integration Example with Next.js (App Router):

```typescript
// app/api/upload/route.ts
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

### 1. Node.js Integration (Express / Axios / Native Fetch)

```javascript
// Native fetch in Node.js 18+
import fs from 'fs';

async function uploadFileNode(filePath) {
  const formData = new FormData();
  const fileBlob = new Blob([fs.readFileSync(filePath)]);
  formData.append('file', fileBlob, 'photo.jpg');

  const res = await fetch('https://cdn.yourdomain.com/upload.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer YOUR_API_KEY',
    },
    body: formData,
  });

  const data = await res.json();
  if (data.success) {
    console.log('CDN URL:', data.url);
    return data.url;
  }
  throw new Error(data.error);
}
```

---

### 2. C# (.NET 6 / 7 / 8) Integration

```csharp
using System;
using System.IO;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text.Json;
using System.Threading.Tasks;

public class CdnService
{
    private static readonly HttpClient _httpClient = new HttpClient();

    public static async Task<string> UploadFileAsync(string filePath, string apiKey)
    {
        using var form = new MultipartFormDataContent();
        await using var fileStream = File.OpenRead(filePath);
        using var streamContent = new StreamContent(fileStream);

        streamContent.Headers.ContentType = new MediaTypeHeaderValue("image/jpeg");
        form.Add(streamContent, "file", Path.GetFileName(filePath));

        var request = new HttpRequestMessage(HttpMethod.Post, "https://cdn.yourdomain.com/upload.php")
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
}
```

---

### 3. Pure PHP SDK Integration (`CdnClient.php`)
```php
require_once 'CdnClient.php';

$cdn = new CdnClient('https://cdn.yourdomain.com', 'YOUR_API_KEY');

// Standard upload
$res = $cdn->uploadFile('/tmp/photo.jpg');
echo "CDN Link: " . $res['url'];

// Chunked upload for huge files (5MB per chunk)
$res = $cdn->uploadChunked('/path/to/big-video.mp4', 5 * 1024 * 1024);

// Delete file
$cdn->deleteFile($res['hash']);
```

---

### 4. Direct cURL Examples (Multipart & Binary Stream)
```bash
# Multipart
curl -X POST "https://cdn.yourdomain.com/upload.php" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "file=@/path/to/image.jpg"

# Binary stream direct (0% RAM)
curl -X POST "https://cdn.yourdomain.com/upload.php" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "X-File-Name: document.pdf" \
  --data-binary "@/path/to/document.pdf"
```


---

## 📜 License
MIT License. Open-source and free to deploy anywhere.
