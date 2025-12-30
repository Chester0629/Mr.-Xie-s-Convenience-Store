<p align="center">
  <img src="https://img.icons8.com/color/96/shop--v1.png" alt="Mr. Xie's Convenience Store" width="100"/>
</p>

<h1 align="center">謝老闆便利店 Mr. Xie's Convenience Store</h1>

<p align="center">
  <strong>現代化全端電商平台</strong><br>
  Built with Laravel 11 + Vue 3 + Docker
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel"/>
  <img src="https://img.shields.io/badge/Vue.js-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white" alt="Vue.js"/>
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP"/>
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL"/>
  <img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=flat-square&logo=docker&logoColor=white" alt="Docker"/>
</p>

---

## 📋 目錄

- [功能特色](#-功能特色)
- [技術架構](#-技術架構)
- [快速開始](#-快速開始)
- [Docker 部署](#-docker-部署)
- [API 文檔](#-api-文檔)
- [專案結構](#-專案結構)
- [開發指南](#-開發指南)
- [授權條款](#-授權條款)

---

## ✨ 功能特色

### 🛒 購物系統
- 商品瀏覽、分類過濾
- **多規格商品支援 (顏色/尺寸)**
- 購物車與收藏夾
- **商品評論與評分系統**
- 全文搜尋 (Meilisearch)

### 💰 會員與錢包
- 會員等級制度 (一般/VIP/VVIP)
- 電子錢包儲值與消費
- **交易記錄追蹤**
- **信箱驗證與安全防護**

### 📦 訂單管理
- 完整訂單流程 (建立/支付/發貨/完成)
- 訂單狀態即時追蹤
- 退款與售後機制
- **物流單號管理**

### 🔐 管理後台
- **權限分級 (管理員/員工)**
- 商品與規格變體管理 (CRUD)
- **分類管理與產品轉移**
- 訂單與退款處理
- 會員與錢包餘額管理
- 優惠券系統
- 營收報表與庫存警示

---

## 🛠 技術架構

```
┌─────────────────────────────────────────────────────────────┐
│                        Frontend                             │
│  Vue 3 + Vue Router + Pinia + Axios + Chart.js             │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                         Nginx                               │
│              (Reverse Proxy + Static Files)                 │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Backend                          │
│  PHP 8.3 + Laravel 11 + Eloquent ORM + Sanctum Auth        │
└─────────────────────────────────────────────────────────────┘
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼
    ┌──────────┐       ┌──────────┐       ┌──────────────┐
    │  MySQL   │       │  Redis   │       │ Meilisearch  │
    │   8.0    │       │  Cache   │       │   Search     │
    └──────────┘       └──────────┘       └──────────────┘
```

---

## 🚀 快速開始

### 系統需求

- PHP >= 8.3
- Composer >= 2.0
- Node.js >= 18
- MySQL >= 8.0
- Redis (可選)
- Docker & Docker Compose (推薦)

### 本地安裝

```bash
# 1. 克隆專案
git clone https://github.com/Chester0629/Mr.-Xie-s-Convenience-Store.git
cd Mr.-Xie-s-Convenience-Store

# 2. 安裝 Backend 依賴
composer install
cp .env.example .env
php artisan key:generate

# 3. 設定資料庫
# 編輯 .env 設定資料庫連線
php artisan migrate --seed

# 4. 安裝 Frontend 依賴
cd xie_vue
npm install
npm run build  # 或 npm run serve 開發模式
cd ..

# 5. 啟動服務
php artisan serve
```

---

## 🐳 Docker 部署

詳細部署說明請參考 [deployment_guide.md](deployment_guide.md)

### 一鍵部署

```bash
# Windows
.\deploy.bat

# Linux/macOS
chmod +x deploy.sh
./deploy.sh
```

### 手動部署

```bash
# 開發環境
docker-compose up -d

# 生產環境
docker-compose -f docker-compose.prod.yml up -d --build

# 執行遷移
docker exec mr-xies-app php artisan migrate --force

# 優化快取
docker exec mr-xies-app php artisan config:cache
docker exec mr-xies-app php artisan route:cache
```

### 使用 Makefile

```bash
make up          # 啟動開發環境
make up-prod     # 啟動生產環境
make migrate     # 執行遷移
make fresh       # 重置資料庫 + 種子資料
make test        # 執行測試
make logs        # 查看日誌
make shell       # 進入容器 shell
```

### 服務端口

| 服務 | 開發環境 | 生產環境 |
|------|----------|----------|
| Frontend | http://localhost:8080 | http://localhost |
| Backend API | http://localhost:8000 | http://localhost/api |
| MySQL | localhost:3306 | - |
| Redis | localhost:6379 | - |
| Meilisearch | localhost:7700 | - |

---

## 📖 API 文檔

詳細 API 說明文件請參考 [API_REFERENCE.md](API_REFERENCE.md)

### 核心功能概覽

| 模組 | 主要功能 |
|------|----------|
| **認證** | 註冊、登入、信箱驗證、JWT Token 管理 |
| **商品** | 列表、搜尋、分類、詳情、評論 |
| **購物車** | 加入、更新、移除、清空 |
| **訂單** | 建立、支付、狀態更新、退款 |
| **會員** | 個人資料、地址管理、收藏夾 |
| **錢包** | 餘額查詢、儲值、交易記錄 |
| **後台** | 商品變體、用戶管理、營收報表、庫存管理 |

---

## 📁 專案結構

```
Mr.-Xie-s-Convenience-Store/
├── app/
│   ├── Http/Controllers/     # API 控制器
│   ├── Models/               # Eloquent 模型
│   └── Services/             # 業務邏輯服務
├── database/
│   ├── migrations/           # 資料庫遷移
│   └── seeders/              # 種子資料
├── docker/
│   ├── nginx/                # Nginx 配置
│   ├── php/                  # PHP-FPM 配置
│   └── mysql/                # MySQL 配置
├── tests/
│   ├── Feature/              # 功能測試
│   └── Unit/                 # 單元測試
├── xie_vue/                  # Vue 3 前端
│   ├── src/
│   │   ├── components/       # Vue 元件
│   │   ├── views/            # 頁面視圖
│   │   ├── stores/           # Pinia 狀態管理
│   │   └── router/           # Vue Router
│   └── public/
├── docker-compose.yml        # 開發環境配置
├── docker-compose.prod.yml   # 生產環境配置
├── Dockerfile                # PHP 應用鏡像
├── Makefile                  # 常用命令
├── deploy.sh                 # 部署腳本 (Linux)
└── deploy.bat                # 部署腳本 (Windows)
```

---

## 👨‍💻 開發指南

### 執行測試

```bash
# 執行所有測試
php artisan test

# 或使用 Docker
make test
```

### 程式碼風格

```bash
# PHP (Laravel Pint)
./vendor/bin/pint

# Vue.js (ESLint)
cd xie_vue && npm run lint
```

### 環境變數

複製 `docker/.env.docker` 到 `.env` 並設定以下關鍵變數：

```env
APP_KEY=              # 執行 php artisan key:generate
DB_PASSWORD=          # 資料庫密碼
MEILISEARCH_KEY=      # Meilisearch API 密鑰
```

---

## 📄 授權條款

本專案採用 [MIT License](LICENSE) 授權。

---

<p align="center">
  Made with ❤️ by Chester
</p>
