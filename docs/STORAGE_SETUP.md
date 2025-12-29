# 圖片儲存設定指南 (Laravel Cloud)

## 問題說明

Laravel Cloud 使用 **ephemeral（臨時性）檔案系統**。這意味著：
- 每次部署時，容器會被重建
- 本機儲存的檔案（如上傳的圖片）會被刪除
- 只有資料庫資料會被保留

## 解決方案：使用 AWS S3 或相容的物件儲存

### 步驟 1：安裝 S3 驅動

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

### 步驟 2：在 Laravel Cloud 設定環境變數

在 Laravel Cloud 控制台中設定以下環境變數：

| 變數名稱 | 說明 | 範例值 |
|---------|------|--------|
| `FILESYSTEM_DISK` | 預設檔案系統 | `s3` |
| `AWS_ACCESS_KEY_ID` | AWS Access Key | `AKIA...` |
| `AWS_SECRET_ACCESS_KEY` | AWS Secret Key | `xxx...` |
| `AWS_DEFAULT_REGION` | S3 區域 | `ap-northeast-1` |
| `AWS_BUCKET` | S3 Bucket 名稱 | `mr-xies-uploads` |
| `AWS_URL` | (選填) 自訂 URL | `https://cdn.example.com` |

### 步驟 3：S3 Bucket 設定

1. **建立 S3 Bucket**
   - 前往 AWS Console → S3
   - 建立新的 bucket
   - 選擇適當的區域（建議選擇離用戶近的區域）

2. **設定 Bucket 公開存取（如需公開圖片）**
   ```json
   {
     "Version": "2012-10-17",
     "Statement": [
       {
         "Sid": "PublicReadGetObject",
         "Effect": "Allow",
         "Principal": "*",
         "Action": "s3:GetObject",
         "Resource": "arn:aws:s3:::YOUR-BUCKET-NAME/*"
       }
     ]
   }
   ```

3. **CORS 設定（如果前端直接存取）**
   ```json
   [
     {
       "AllowedHeaders": ["*"],
       "AllowedMethods": ["GET", "HEAD"],
       "AllowedOrigins": ["*"],
       "ExposeHeaders": []
     }
   ]
   ```

### 步驟 4：更新前端圖片 URL 處理

如果使用 S3，圖片 URL 會變成完整的 S3 URL。確保前端可以處理：

```javascript
// 在 Vue 組件中
const getImageUrl = (imagePath) => {
  if (!imagePath) return '/placeholder.jpg';
  
  // 如果已經是完整 URL，直接返回
  if (imagePath.startsWith('http')) {
    return imagePath;
  }
  
  // 本機開發環境
  return `/storage/${imagePath}`;
};
```

## 替代方案：DigitalOcean Spaces

如果不想用 AWS，可以使用 DigitalOcean Spaces（S3 相容）：

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-spaces-key
AWS_SECRET_ACCESS_KEY=your-spaces-secret
AWS_DEFAULT_REGION=sgp1
AWS_BUCKET=your-space-name
AWS_ENDPOINT=https://sgp1.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## 替代方案：Cloudflare R2

Cloudflare R2 也是 S3 相容且更便宜：

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-r2-key
AWS_SECRET_ACCESS_KEY=your-r2-secret
AWS_DEFAULT_REGION=auto
AWS_BUCKET=your-bucket-name
AWS_ENDPOINT=https://xxx.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

## 驗證設定

部署後執行：

```bash
php artisan tinker
>>> Storage::disk('s3')->put('test.txt', 'Hello World!');
>>> Storage::disk('s3')->url('test.txt');
```

如果返回正確的 S3 URL，設定成功！
