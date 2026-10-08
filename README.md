# PHP CDN Storage Gateway (Multi-Account Google Drive Pool Edition)

**English** | [Tiếng Việt](README.vi.md)

A high-performance file storage and CDN delivery gateway built with **pure PHP**, transforming **Shared Hosting** into a distributed file cluster backed by a **Google Drive Pool (5TB x N)** and **Cloudflare CDN**.

---

## ⚡ Core Features

1. **Content-Addressable Storage (CAS)**:
   - Automatically hashes file contents (SHA-1 / MD5) to form filenames: `{hash}.{ext}`.
   - **Zero Duplicate Storage (Deduplication)**: Uploading identical files returns the existing URL immediately without consuming additional storage.
   - **Automatic CDN Cache-Busting**: Any change to file content generates a new cryptographic hash and a new CDN link, completely eliminating stale edge cache issues without requiring manual purge.

2. **Unlimited Google Drive 5TB Account Pooling**:
   - Seamlessly connect unlimited Google Drive accounts via Google Service Accounts.
   - Intelligent multi-account distribution with round-robin hashing.
   - Lightweight SQLite Metadata Store (`data/cdn_store.db`) tracks mappings between hashes, Google Drive file IDs, and account IDs.
   - Flexible `GDRIVE_KEEP_LOCAL_CACHE` toggle:
     - `true`: Keeps a local cache on hosting disk for instant origin responses.
     - `false`: Streams directly to Google Drive and cleans up local storage (0% hosting disk consumed).

3. **Enterprise Security Shield**:
   - **Isolated Service Accounts**: Never uses master account credentials or Master OAuth refresh tokens.
   - **Scoped Folder Permissions**: The service account bot only has access to the specific designated folder (e.g., `CDN_FILES`) shared with it. It has **zero access** to Gmail, Google Photos, contacts, or personal files.
   - **Zero URL Exposure**: Google Drive links and file IDs are completely hidden behind the CDN reverse proxy domain (`https://cdn.yourdomain.com/f/{hash}.{ext}`). Visitors cannot inspect or abuse origin storage.
   - **Protected Credentials & DB**: The `credentials/` and `data/` directories are strictly protected from web access via `.htaccess` rules and excluded from Git.

4. **Zero PHP Memory on Reads**:
   - Cached files are served statically directly by the web server (Apache / LiteSpeed / Nginx) with 0% PHP overhead.
   - Pre-configured with `Cache-Control: public, max-age=31536000, immutable` headers.
   - Full support for HTTP Range Requests (`Accept-Ranges: bytes`) for smooth video/audio streaming and scrubbing.

5. **2-Level Directory Sharding**:
   - Automatically partitions local files into a 2-level directory tree: `storage/ab/cd/{hash}.{ext}`.
   - Prevents filesystem inode bottlenecks and slowdowns on shared hosting when storing millions of files.

6. **Multi-Protocol Upload Support**:
   - Standard `Multipart Form-Data` (`$_FILES`).
   - `Direct Binary Stream` (`php://input` - streams in 64KB chunks, consuming minimal RAM even for files over 500MB).
   - `Chunked Upload API` (splits large files into chunks to bypass server timeouts).

7. **Server-Level Script Hardening**:
   - Authenticated via Bearer Token or `X-API-Key`.
   - Blocks and neutralizes executable scripts (`.php`, `.phtml`, `.phar`, `.sh`, `.py`, etc.).
   - The `storage/` directory enforces `php_flag engine off` and `RemoveHandler` to disarm malicious file execution.

---

## 📁 Directory Structure

```text
php-cdn-storage/
├── .htaccess               # Clean URL rewriting (/f/{hash}.ext) & security guards
├── config.php              # Global configuration (API keys, cache rules, upload limits)
├── upload.php              # Upload endpoint (Multipart, Binary Stream, Chunked)
├── delete.php              # Delete endpoint (removes both from hosting and Google Drive)
├── stream.php              # Dynamic streaming proxy from Google Drive + disk cache
├── health.php              # Health monitoring, disk space, and pool diagnostic endpoint
├── index.php               # Clean web drag-and-drop upload dashboard
├── CdnClient.php           # Pure PHP Client SDK for embedding into other projects
├── Database.php            # SQLite metadata database layer
├── GoogleDriveDriver.php   # Pure PHP Google Drive v3 client with RS256 JWT auth
├── GoogleDriveManager.php  # Multi-account Google Drive pool manager
├── credentials/            # Google Service Account JSON keys (strictly protected)
│   ├── .htaccess           # Forbids web access to JSON files
│   └── account_sample.json # Sample configuration schema
├── data/                   # SQLite database directory (strictly protected)
│   └── .htaccess           # Forbids web access to database files
├── storage/                # Local cache directory with immutable headers
│   └── .htaccess           # Disables PHP execution and sets 1-year cache headers
└── tmp/                    # Temporary scratch storage for chunks and streams
```

---

## 🛡️ Google Drive Service Account Setup Guide

### Step 1: Create a Google Service Account
1. Open [Google Cloud Console](https://console.cloud.google.com/) and create a new project (e.g., `cdn-storage-pool`).
   > **Note on Organization Policies**: If using a corporate or school Google Workspace account that triggers `iam.disableServiceAccountKeyCreation`, create the project using a **personal standard `@gmail.com` account** instead. Personal accounts never restrict service account key creation!
2. Navigate to **APIs & Services** → **Library** → Search for and enable **Google Drive API**.
3. Go to **IAM & Admin** → **Service Accounts** → Click **Create Service Account**:
   - Enter a name (e.g., `cdn-bot-1`) → Click **Done**.
4. Click on the newly created Service Account → Open the **Keys** tab → **Add Key** → **Create new key** → Choose **JSON** and download the key file.

### Step 2: Share your Google Drive Folder
1. Open your 5TB Google Drive account.
2. Create a dedicated folder (e.g., `CDN_FILES`).
3. Right-click `CDN_FILES` → Select **Share**.
4. Paste the Service Account email (`...@...iam.gserviceaccount.com` from the JSON file) and grant **Editor** permissions.
5. Copy the **Folder ID** from your browser address bar:
   ```text
   https://drive.google.com/drive/folders/1AbC2xYz3_D4eF5gHiJkLmnOpQrStUvwX
                                          └───────────┬───────────┘
                                                This is Folder ID
   ```

### Step 3: Register the Key into the Gateway
1. Rename your downloaded JSON file to `credentials/account_1.json` (do not name it `account_sample.json`).
2. Open `credentials/account_1.json` and append the `"folder_id"` key:
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
3. **Scaling with more accounts**: To add 2nd or 3rd Google Drive accounts to the pool, simply create `credentials/account_2.json`, `credentials/account_3.json`, etc. The system automatically loads all accounts and balances uploads across them.

---

## 🚀 Shared Hosting Deployment Guide

### Option A: Standard Security Structure with `public_html` (Recommended)
If your hosting allows files outside `public_html`, structure your deployment like this:
```text
/home/username/
├── credentials/              # Private service account keys
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
