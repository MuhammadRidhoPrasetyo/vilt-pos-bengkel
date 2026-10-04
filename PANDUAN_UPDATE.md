# Panduan Update Aplikasi POS Bengkel (Setelah `git pull`)

Dokumen ini menjelaskan langkah-langkah yang perlu dilakukan pada komputer/laptop lain setelah Anda melakukan pembaruan kode (`git pull`), khususnya dengan adanya penambahan fitur real-time **Laravel Reverb**.

---

## 📌 Dua Skenario Penggunaan PC

Sebelum memulai, pastikan tipe penggunaan PC tersebut:

1. **Skenario A: PC bertindak sebagai Server Lokal / Development (Menjalankan Docker & `start.bat`)**
   *(Lanjutkan membaca bagian Langkah di bawah)*
2. **Skenario B: PC hanya sebagai Client (Kasir / Mekanik / TV Display yang membuka Browser)**
   * **Tidak perlu melakukan update apa pun di PC tersebut.**
   * Cukup buka browser dan akses link server lokal (`http://ip-server:8000`) atau Cloudflare Tunnel (`https://xxxx.trycloudflare.com`).

---

## 🚀 Langkah Update untuk PC Server (Docker / `start.bat`)

File [start.bat](file:///home/mhmmdrdhprstyo/Projects/vilt-pos/start.bat) telah diperbarui dengan fitur **Auto-Detect & Auto-Config**. Sebagian besar proses akan berjalan otomatis.

### Langkah 1: Tarik Pembaruan Kode
Buka Git Bash / Terminal / Command Prompt pada folder project, lalu jalankan:
```bash
git pull origin main
```

---

### Langkah 2: Matikan Container Lama (Jika Sedang Berjalan)
Jika Docker container sebelumnya masih menyala, matikan terlebih dahulu agar service baru `reverb` dapat didaftarkan:
```bash
docker compose down
```
*(Atau pilih opsi menu `[3] Hentikan Server` pada jendela `start.bat` lama).*

---

### Langkah 3: Jalankan `start.bat`
Cukup klik dua kali file **`start.bat`** seperti biasa.

`start.bat` akan secara otomatis mendeteksi dan mengeksekusi hal berikut:
1. **Auto-Config `.env`**: Mendeteksi jika variabel `REVERB_*` belum ada di `.env` lokal Anda, lalu otomatis menambahkannya tanpa merusak konfigurasi database lama Anda.
2. **Menyalakan Service Baru**: Menyalakan container `app`, `nginx`, `queue`, `tunnel`, serta container baru **`reverb`**.
3. **Auto-Install Composer**: Mendeteksi paket `laravel/reverb` dan otomatis menjalankan `composer install` di dalam container.
4. **Auto-Migrate Database**: Memastikan struktur database terbaru terpasang (`php artisan migrate`).
5. **Auto-Build Frontend**: Mendeteksi dependensi baru (`laravel-echo`, `pusher-js`) dan otomatis menjalankan `npm install` serta `npm run build` di dalam container Node.js.
6. **Membuka Tunnel & Local URL**: Menyiapkan URL `http://localhost:8000` dan URL publik Cloudflare Tunnel.

---

## 🛠️ Langkah Manual (Jika Ingin Dijalankan Manual Tanpa `start.bat`)

Jika di PC tersebut Anda terbiasa menggunakan command line manual:

```bash
# 1. Update kode
git pull origin main

# 2. Sinkronkan variabel Reverb ke .env
# Tambahkan variabel berikut ke file .env jika belum ada:
# BROADCAST_CONNECTION=reverb
# REVERB_APP_ID=751073
# REVERB_APP_KEY=a2kz89zdynwffpznlejr
# REVERB_APP_SECRET=4ozv52uqxhod7tykme8y
# REVERB_HOST="localhost"
# REVERB_PORT=8080
# REVERB_SCHEME=http
# REVERB_INTERNAL_HOST="reverb"
# REVERB_INTERNAL_PORT=8080
# VITE_REVERB_APP_KEY="a2kz89zdynwffpznlejr"
# VITE_REVERB_HOST="localhost"
# VITE_REVERB_PORT="8080"
# VITE_REVERB_SCHEME="http"

# 3. Jalankan container dengan rebuild
docker compose down
docker compose up -d --build

# 4. Install dependensi backend di dalam container
docker compose exec app composer install --optimize-autoloader

# 5. Jalankan migrasi database
docker compose exec app php artisan migrate --force

# 6. Install dependensi frontend dan kompilasi aset
docker compose run --rm node npm install
docker compose run --rm node npm run build
```

---

## 🔍 Cara Memastikan Fitur Real-Time Berjalan
1. Buka halaman **POS Kasir** (`/transactions/create`) di satu browser.
2. Buka halaman **SPK Servis** (`/services/create` atau `/services`) di browser/tab lain.
3. Saat SPK baru dibuat atau diubah menjadi status **Ready**:
   * Di layar kasir akan terdengar bunyi **bel lonceng "ting"**.
   * Muncul **toast notifikasi** hijau di pojok kanan bawah kasir.
   * Daftar SPK siap ditagih di kasir langsung bertambah tanpa perlu menekan F5/Refresh.
4. Saat kasir menyelesaikan transaksi pembayaran barang:
   * Stok barang pada form servis otomatis berkurang dan ter-update detik itu juga.
