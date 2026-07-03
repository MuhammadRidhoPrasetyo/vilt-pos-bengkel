# Database Schema - Filament POS Bengkel

**Project:** Filament POS Bengkel  
**Generated:** 2026-03-01  
**Purpose:** Dokumentasi lengkap semua table dan field dalam database

---

## Daftar Isi

1. [Phase 0 - Tabel Sistem Tanpa Foreign Key](#phase-0---tabel-sistem-tanpa-foreign-key)
2. [Phase 1 - Master Data Inti](#phase-1---master-data-inti)
3. [Phase 2 - Authentication dan Authorization](#phase-2---authentication-dan-authorization)
4. [Phase 3 - Master Produk dan Gudang](#phase-3---master-produk-dan-gudang)
5. [Phase 4 - Harga, Stok, Diskon, dan Label Produk](#phase-4---harga-stok-diskon-dan-label-produk)
6. [Phase 5 - Pembelian dan Batch Inventory](#phase-5---pembelian-dan-batch-inventory)
7. [Phase 6 - Service Management](#phase-6---service-management)
8. [Phase 7 - Transactions](#phase-7---transactions)
9. [Phase 8 - Stock Operations, Cash Flow, dan Konfigurasi Cabang](#phase-8---stock-operations-cash-flow-dan-konfigurasi-cabang)
10. [Phase 9 - Alter Foreign Key Circular/Nullable Opsional](#phase-9---alter-foreign-key-circularnullable-opsional)
11. [Relasi Foreign Key Summary](#relasi-foreign-key-summary)
12. [Notes](#notes)

---

## Catatan Urutan Migrasi

Susunan tabel di bawah sudah diurutkan berdasarkan dependency foreign key agar migrasi bisa dijalankan dari atas ke bawah dengan risiko error yang lebih kecil.

Khusus relasi circular antara `service_orders.transaction_id` dan `transactions.service_order_id`, buat salah satu kolom sebagai nullable tanpa constraint terlebih dahulu, lalu tambahkan foreign key-nya di migration alter terpisah setelah kedua tabel tersedia.

Untuk konsep multi toko, data pembeli, supplier, reseller, dan customer distributor disimpan sebagai `partners`. Jika `partners.store_id` bernilai `null`, partner dianggap global dan bisa dipakai lintas toko. Jika berisi `store_id`, partner tersebut milik toko/cabang tertentu. Peran partner tidak disimpan sebagai satu enum tunggal, tetapi melalui `partner_roles` agar satu partner bisa memiliki banyak peran sekaligus.

---

## Phase 0 - Tabel Sistem Tanpa Foreign Key

Bisa dibuat paling awal karena tidak membutuhkan tabel domain lain.

---

### 📋 cache

Tabel penyimpanan cache.

| Field      | Type       | Attributes  | Keterangan                        |
| ---------- | ---------- | ----------- | --------------------------------- |
| key        | string     | PRIMARY KEY | Kunci cache                       |
| value      | mediumtext |             | Nilai cache                       |
| expiration | integer    |             | Waktu kadaluarsa (unix timestamp) |

---

### 📋 cache_locks

Tabel cache locks untuk concurrency control.

| Field      | Type    | Attributes  | Keterangan       |
| ---------- | ------- | ----------- | ---------------- |
| key        | string  | PRIMARY KEY | Kunci lock       |
| owner      | string  |             | Pemilik lock     |
| expiration | integer |             | Waktu kadaluarsa |

---

### 📋 jobs

Tabel antrian job background.

| Field        | Type                | Attributes                  | Keterangan                          |
| ------------ | ------------------- | --------------------------- | ----------------------------------- |
| id           | bigint              | PRIMARY KEY, AUTO INCREMENT |                                     |
| queue        | string              | INDEX                       | Nama queue                          |
| payload      | longtext            |                             | Data job                            |
| attempts     | unsignedTinyInteger |                             | Jumlah usaha eksekusi               |
| reserved_at  | unsignedInteger     | NULLABLE                    | Waktu job direserve                 |
| available_at | unsignedInteger     |                             | Waktu job tersedia untuk dijalankan |
| created_at   | unsignedInteger     |                             |                                     |

---

### 📋 job_batches

Tabel batch jobs.

| Field          | Type       | Attributes  | Keterangan             |
| -------------- | ---------- | ----------- | ---------------------- |
| id             | string     | PRIMARY KEY | ID batch               |
| name           | string     |             | Nama batch             |
| total_jobs     | integer    |             | Total job dalam batch  |
| pending_jobs   | integer    |             | Job yang masih pending |
| failed_jobs    | integer    |             | Job yang gagal         |
| failed_job_ids | longtext   |             | ID job yang gagal      |
| options        | mediumtext | NULLABLE    | Opsi batch (JSON)      |
| cancelled_at   | integer    | NULLABLE    | Waktu batch dibatalkan |
| created_at     | integer    |             |                        |
| finished_at    | integer    | NULLABLE    | Waktu batch selesai    |

---

### 📋 failed_jobs

Tabel job yang gagal.

| Field      | Type      | Attributes                  | Keterangan         |
| ---------- | --------- | --------------------------- | ------------------ |
| id         | bigint    | PRIMARY KEY, AUTO INCREMENT |                    |
| uuid       | string    | UNIQUE                      | UUID unik          |
| connection | text      |                             | Nama koneksi queue |
| queue      | text      |                             | Nama queue         |
| payload    | longtext  |                             | Data job           |
| exception  | longtext  |                             | Exception error    |
| failed_at  | timestamp | DEFAULT: CURRENT_TIMESTAMP  |                    |

---

### 📋 password_reset_tokens

Tabel token reset password.

| Field      | Type      | Attributes  | Keterangan            |
| ---------- | --------- | ----------- | --------------------- |
| email      | string    | PRIMARY KEY | Email pengguna        |
| token      | string    |             | Token reset           |
| created_at | timestamp | NULLABLE    | Waktu pembuatan token |

---

### 📋 media

Tabel media/file (spatie/laravel-medialibrary).

| Field                 | Type               | Attributes                  | Keterangan                      |
| --------------------- | ------------------ | --------------------------- | ------------------------------- |
| id                    | bigint             | PRIMARY KEY, AUTO INCREMENT |                                 |
| model_id              | uuid               |                             | ID model (morphable, UUID-safe) |
| model_type            | string             |                             | Tipe model (morphable)          |
| uuid                  | uuid               | UNIQUE, NULLABLE            | UUID media                      |
| collection_name       | string             |                             | Nama koleksi                    |
| name                  | string             |                             | Nama media                      |
| file_name             | string             |                             | Nama file                       |
| mime_type             | string             | NULLABLE                    | MIME type                       |
| disk                  | string             |                             | Disk storage                    |
| conversions_disk      | string             | NULLABLE                    | Disk konversi                   |
| size                  | unsignedBigInteger |                             | Ukuran file (bytes)             |
| manipulations         | json               |                             | Manipulasi (JSON)               |
| custom_properties     | json               |                             | Custom properties (JSON)        |
| generated_conversions | json               |                             | Generated conversions (JSON)    |
| responsive_images     | json               |                             | Responsive images (JSON)        |
| order_column          | unsignedInteger    | NULLABLE, INDEX             | Urutan                          |
| created_at            | timestamp          | NULLABLE                    |                                 |
| updated_at            | timestamp          | NULLABLE                    |                                 |

---

## Phase 1 - Master Data Inti

Tabel dasar yang menjadi referensi utama untuk user, partner bisnis, produk, transaksi, stok, dan kas.

---

### 📋 stores

Tabel toko/cabang.

| Field                 | Type                 | Attributes                                   | Keterangan             |
| --------------------- | -------------------- | -------------------------------------------- | ---------------------- |
| id                    | uuid                 | PRIMARY KEY                                  |                        |
| code                  | string               | UNIQUE                                       | Kode toko, misal: T01  |
| name                  | string               |                                              | Nama toko              |
| phone                 | string               | NULLABLE                                     | Nomor telepon          |
| email                 | string               | NULLABLE                                     | Email                  |
| address               | string               | NULLABLE                                     | Alamat                 |
| city                  | string               | NULLABLE                                     | Kota                   |
| province              | string               | NULLABLE                                     | Provinsi               |
| postal_code           | string               | NULLABLE                                     | Kode pos               |
| receipt_number_format | string               | DEFAULT: '{STORE_CODE}/{YYYY}/{MM}/{NUMBER}' | Format nomor struk     |
| receipt_sequence      | unsignedInteger      | DEFAULT: 0                                   | Counter nomor struk    |
| receipt_sequence_year | unsignedSmallInteger | NULLABLE                                     | Tahun terakhir counter |
| notes                 | text                 | NULLABLE                                     | Catatan                |
| created_at            | timestamp            |                                              |                        |
| updated_at            | timestamp            |                                              |                        |

---

### 📋 partners

Tabel pihak bisnis untuk pembeli, supplier, reseller, customer distributor, atau relasi toko lain. Partner dapat bersifat global atau spesifik per toko.

| Field          | Type      | Attributes                       | Keterangan                                      |
| -------------- | --------- | -------------------------------- | ----------------------------------------------- |
| id             | uuid      | PRIMARY KEY                      |                                                 |
| store_id       | uuid      | NULLABLE, FK (stores), INDEX     | Pemilik data partner; null berarti global       |
| linked_store_id | uuid      | NULLABLE, FK (stores), INDEX     | Toko internal yang direpresentasikan partner    |
| code           | string    | INDEX                            | Kode partner, misal: SUP001, CUS001, RSL001     |
| name           | string    |                                  | Nama pihak/partner                              |
| kind           | enum      | VALUES: 'person', 'organization' | Jenis partner                                   |
| contact_person | string    | NULLABLE                         | Nama PIC jika partner berupa organisasi         |
| phone          | string    | NULLABLE, INDEX                  | Nomor telepon                                   |
| email          | string    | NULLABLE, INDEX                  | Email                                           |
| address        | text      | NULLABLE                         | Alamat                                          |
| city           | string    | NULLABLE                         | Kota                                            |
| province       | string    | NULLABLE                         | Provinsi                                        |
| postal_code    | string    | NULLABLE                         | Kode pos                                        |
| npwp           | string    | NULLABLE                         | NPWP                                            |
| bank_name      | string    | NULLABLE                         | Nama bank                                       |
| bank_account   | string    | NULLABLE                         | Nomor rekening                                  |
| is_active      | boolean   | DEFAULT: true                    | Status aktif                                    |
| notes          | text      | NULLABLE                         | Catatan                                         |
| created_at     | timestamp |                                  |                                                 |
| updated_at     | timestamp |                                  |                                                 |

**Unique Index:** (store_id, code)

**Catatan:**

- Gunakan `store_id = null` untuk partner global yang dapat dipakai semua toko.
- Gunakan `store_id = stores.id` untuk partner yang hanya dimiliki toko tertentu.
- Gunakan `linked_store_id` jika partner merepresentasikan toko/cabang internal lain, misalnya saat toko distributor menjadi lawan transaksi toko retail.
- Pembeli retail, pembeli service, customer distributor, reseller, dan supplier tetap masuk ke tabel ini. Perbedaannya ditentukan oleh `partner_roles`.

---

### 📋 partner_roles

Tabel master peran partner.

| Field       | Type      | Attributes                  | Keterangan                                                    |
| ----------- | --------- | --------------------------- | ------------------------------------------------------------- |
| id          | bigint    | PRIMARY KEY, AUTO INCREMENT |                                                               |
| name        | string    | UNIQUE                      | Contoh: supplier, retail_customer, service_customer, reseller |
| description | string    | NULLABLE                    | Deskripsi peran                                               |
| created_at  | timestamp |                             |                                                               |
| updated_at  | timestamp |                             |                                                               |

**Default Roles:**

- `supplier`: pemasok barang/jasa untuk pembelian.
- `retail_customer`: pembeli POS retail.
- `service_customer`: pelanggan bengkel/service.
- `reseller`: pembeli yang menjual ulang.
- `distributor_customer`: customer khusus toko yang berperan sebagai distributor.
- `internal_store`: toko/cabang lain yang diperlakukan sebagai lawan transaksi.

---

### 📋 partner_role_partner

Tabel pivot relasi partner dengan banyak peran.

| Field           | Type      | Attributes                 | Keterangan |
| --------------- | --------- | -------------------------- | ---------- |
| partner_id      | uuid      | FK (partners, CASCADE)     | Partner    |
| partner_role_id | bigint    | FK (partner_roles, CASCADE) | Peran      |
| created_at      | timestamp | NULLABLE                   |            |

**Primary Key:** (partner_id, partner_role_id)

**Contoh Penggunaan:**

- Pembeli biasa: `partners` + role `retail_customer`.
- Pelanggan bengkel: `partners` + role `service_customer`.
- Supplier barang: `partners` + role `supplier`.
- Pembeli distributor/reseller: `partners` + role `reseller` atau `distributor_customer`.
- Toko internal sebagai lawan transaksi: `partners.linked_store_id` diisi dan diberi role `internal_store`.

---

### 📋 discount_types

Tabel tipe diskon.

| Field       | Type      | Attributes                  | Keterangan       |
| ----------- | --------- | --------------------------- | ---------------- |
| id          | bigint    | PRIMARY KEY, AUTO INCREMENT |                  |
| name        | string    |                             | Nama tipe diskon |
| description | string    | NULLABLE                    | Deskripsi        |
| created_at  | timestamp |                             |                  |
| updated_at  | timestamp |                             |                  |

---

### 📋 brands

Tabel merk/brand produk.

| Field      | Type      | Attributes                  | Keterangan |
| ---------- | --------- | --------------------------- | ---------- |
| id         | bigint    | PRIMARY KEY, AUTO INCREMENT |            |
| name       | string    |                             | Nama merk  |
| created_at | timestamp |                             |            |
| updated_at | timestamp |                             |            |

---

### 📋 units

Tabel satuan pengukuran.

| Field      | Type      | Attributes                  | Keterangan                      |
| ---------- | --------- | --------------------------- | ------------------------------- |
| id         | bigint    | PRIMARY KEY, AUTO INCREMENT |                                 |
| name       | string    |                             | Nama satuan (Pcs, Liter, Meter) |
| symbol     | string    | NULLABLE                    | Simbol (pcs, L, m)              |
| created_at | timestamp |                             |                                 |
| updated_at | timestamp |                             |                                 |

---

### 📋 attributes

Tabel master atribut variasi (Warna, Ukuran, dll).

| Field      | Type      | Attributes                  | Keterangan   |
| ---------- | --------- | --------------------------- | ------------ |
| id         | bigint    | PRIMARY KEY, AUTO INCREMENT |              |
| name       | string    |                             | Nama atribut |
| created_at | timestamp |                             |              |
| updated_at | timestamp |                             |              |

---

### 📋 payments

Tabel metode pembayaran.

| Field          | Type      | Attributes  | Keterangan                   |
| -------------- | --------- | ----------- | ---------------------------- |
| id             | uuid      | PRIMARY KEY |                              |
| name           | string    |             | Nama metode (QRIS, BCA, BRI) |
| type           | string    | NULLABLE    | Tipe                         |
| account_number | string    | NULLABLE    | Nomor rekening               |
| account_name   | string    | NULLABLE    | Nama rekening                |
| provider_code  | string    | NULLABLE    | Kode provider                |
| created_at     | timestamp |             |                              |
| updated_at     | timestamp |             |                              |

---

### 📋 cash_flow_categories

Tabel kategori alur kas (pemasukan/pengeluaran).

| Field       | Type      | Attributes                  | Keterangan                                      |
| ----------- | --------- | --------------------------- | ----------------------------------------------- |
| id          | bigint    | PRIMARY KEY, AUTO INCREMENT |                                                 |
| name        | string    |                             | Nama kategori (Gaji, Listrik, Operasional, dll) |
| type        | enum      | VALUES: 'income', 'expense' | Jenis kategori (pemasukan/pengeluaran)          |
| description | string    | NULLABLE                    | Deskripsi opsional                              |
| is_active   | boolean   | DEFAULT: true               | Status aktif                                    |
| is_system   | boolean   | DEFAULT: false              | Kategori bawaan sistem                          |
| created_at  | timestamp |                             |                                                 |
| updated_at  | timestamp |                             |                                                 |

---

## Phase 2 - Authentication dan Authorization

Buat setelah stores karena users memiliki store_id. Tabel pivot Spatie dibuat setelah roles dan permissions.

---

### 📋 users

Tabel pengguna sistem.

| Field             | Type      | Attributes                  | Keterangan                       |
| ----------------- | --------- | --------------------------- | -------------------------------- |
| id                | bigint    | PRIMARY KEY, AUTO INCREMENT |                                  |
| name              | string    |                             | Nama pengguna                    |
| email             | string    | UNIQUE                      | Email pengguna                   |
| email_verified_at | timestamp | NULLABLE                    | Waktu verifikasi email           |
| password          | string    |                             | Password terenkripsi             |
| remember_token    | string    | NULLABLE                    | Token untuk fitur "Remember Me"  |
| nik               | string    | UNIQUE, NULLABLE            | Nomor Induk Karyawan             |
| phone             | string    | NULLABLE                    | Nomor telepon                    |
| address           | string    | NULLABLE                    | Alamat                           |
| store_id          | uuid      | NULLABLE, FK (stores)       | Toko tempat kerja                |
| active            | boolean   | DEFAULT: true               | Status aktif pengguna            |
| top_navigation    | boolean   | DEFAULT: false              | Preferensi layout navigasi admin |
| created_at        | timestamp |                             |                                  |
| updated_at        | timestamp |                             |                                  |

---

### 📋 sessions

Tabel sesi pengguna.

| Field         | Type       | Attributes                  | Keterangan               |
| ------------- | ---------- | --------------------------- | ------------------------ |
| id            | string     | PRIMARY KEY                 | ID unik sesi             |
| user_id       | bigint     | NULLABLE, INDEX, FK (users) | Pengguna yang login      |
| ip_address    | string(45) | NULLABLE                    | Alamat IP                |
| user_agent    | text       | NULLABLE                    | User agent browser       |
| payload       | longtext   |                             | Data sesi                |
| last_activity | integer    | INDEX                       | Waktu aktivitas terakhir |

---

### 📋 permissions

Tabel permission/izin.

| Field      | Type      | Attributes                  | Keterangan                 |
| ---------- | --------- | --------------------------- | -------------------------- |
| id         | bigint    | PRIMARY KEY, AUTO INCREMENT |                            |
| name       | string    |                             | Nama permission            |
| guard_name | string    |                             | Guard name (web, api, dll) |
| created_at | timestamp |                             |                            |
| updated_at | timestamp |                             |                            |

**Unique Index:** (name, guard_name)

---

### 📋 roles

Tabel role/peran.

| Field            | Type               | Attributes                  | Keterangan               |
| ---------------- | ------------------ | --------------------------- | ------------------------ |
| id               | bigint             | PRIMARY KEY, AUTO INCREMENT |                          |
| team_foreign_key | unsignedBigInteger | NULLABLE, INDEX             | Tim (jika multi-tenancy) |
| name             | string             |                             | Nama role                |
| guard_name       | string             |                             | Guard name               |
| created_at       | timestamp          |                             |                          |
| updated_at       | timestamp          |                             |                          |

**Unique Index:** (team_foreign_key, name, guard_name) atau (name, guard_name)

---

### 📋 model_has_permissions

Tabel relasi model dengan permission (pivot).

| Field            | Type               | Attributes       | Keterangan             |
| ---------------- | ------------------ | ---------------- | ---------------------- |
| permission_id    | unsignedBigInteger | FK (permissions) |                        |
| model_type       | string             |                  | Model type (morphable) |
| model_morph_key  | unsignedBigInteger |                  | ID model (morphable)   |
| team_foreign_key | unsignedBigInteger | NULLABLE, INDEX  | Tim (opsional)         |

**Primary Key:** (team_foreign_key, permission_id, model_morph_key, model_type) atau (permission_id, model_morph_key, model_type)

---

### 📋 model_has_roles

Tabel relasi model dengan role (pivot).

| Field            | Type               | Attributes      | Keterangan             |
| ---------------- | ------------------ | --------------- | ---------------------- |
| role_id          | unsignedBigInteger | FK (roles)      |                        |
| model_type       | string             |                 | Model type (morphable) |
| model_morph_key  | unsignedBigInteger |                 | ID model (morphable)   |
| team_foreign_key | unsignedBigInteger | NULLABLE, INDEX | Tim (opsional)         |

**Primary Key:** (team_foreign_key, role_id, model_morph_key, model_type) atau (role_id, model_morph_key, model_type)

---

### 📋 role_has_permissions

Tabel relasi role dengan permission (pivot).

| Field         | Type               | Attributes       | Keterangan |
| ------------- | ------------------ | ---------------- | ---------- |
| permission_id | unsignedBigInteger | FK (permissions) |            |
| role_id       | unsignedBigInteger | FK (roles)       |            |

**Primary Key:** (permission_id, role_id)

---

## Phase 3 - Master Produk dan Gudang

Urutan ini memastikan parent product, opsi atribut, varian, gudang, dan lokasi sudah tersedia sebelum stok/harga.

---

### 📋 product_categories

Tabel kategori produk.

| Field        | Type      | Attributes                                    | Keterangan                     |
| ------------ | --------- | --------------------------------------------- | ------------------------------ |
| id           | bigint    | PRIMARY KEY, AUTO INCREMENT                   |                                |
| parent_id    | bigint    | NULLABLE, FK (product_categories), INDEX      | Kategori induk                 |
| name         | string    |                                               | Nama kategori                  |
| pricing_mode | enum      | VALUES: 'fixed', 'editable', DEFAULT: 'fixed' | Mode harga (tetap/bisa diubah) |
| item_type    | enum      | VALUES: 'part', 'labor', DEFAULT: 'part'      | Tipe item (barang/jasa)        |
| created_at   | timestamp |                                               |                                |
| updated_at   | timestamp |                                               |                                |

---

### 📋 attribute_options

Tabel pilihan dari masing-masing atribut (Merah, 14 inch, dll).

| Field        | Type      | Attributes                  | Keterangan     |
| ------------ | --------- | --------------------------- | -------------- |
| id           | bigint    | PRIMARY KEY, AUTO INCREMENT |                |
| attribute_id | bigint    | FK (attributes, CASCADE)    | Relasi atribut |
| value        | string    |                             | Nilai atribut  |
| created_at   | timestamp |                             |                |
| updated_at   | timestamp |                             |                |

---

### 📋 products

Tabel induk produk.

| Field               | Type      | Attributes                 | Keterangan              |
| ------------------- | --------- | -------------------------- | ----------------------- |
| id                  | uuid      | PRIMARY KEY                |                         |
| product_category_id | bigint    | FK (product_categories)    | Kategori                |
| brand_id            | bigint    | NULLABLE, FK (brands)      | Merk                    |
| unit_id             | bigint    | NULLABLE, FK (units)       | Satuan dasar            |
| name                | string    |                            | Nama induk produk       |
| type                | enum      | VALUES: 'goods', 'service' | Tipe (Barang/Jasa)      |
| has_variants        | boolean   | DEFAULT: false             | Apakah memiliki varian? |
| description         | text      | NULLABLE                   | Deskripsi               |
| created_at          | timestamp |                            |                         |
| updated_at          | timestamp |                            |                         |

---

### 📋 product_variants

Tabel SKU (Stock Keeping Unit) yang akan ditransaksikan.

| Field                  | Type          | Attributes             | Keterangan                  |
| ---------------------- | ------------- | ---------------------- | --------------------------- |
| id                     | uuid          | PRIMARY KEY            |                             |
| product_id             | uuid          | FK (products, CASCADE) | Induk produk                |
| sku                    | string        | UNIQUE, NULLABLE       | SKU (Barcode/Kode unik)     |
| barcode                | string        | UNIQUE, NULLABLE       | Barcode scanner             |
| name_suffix            | string        | NULLABLE               | Suffix (Misal: Merah - L)   |
| default_purchase_price | decimal(12,2) | DEFAULT: 0             | Harga beli dasar (opsional) |
| default_selling_price  | decimal(12,2) | DEFAULT: 0             | Harga jual dasar (opsional) |
| is_active              | boolean       | DEFAULT: true          | Status aktif                |
| created_at             | timestamp     |                        |                             |
| updated_at             | timestamp     |                        |                             |

---

### 📋 product_variant_attributes

Tabel pivot antara varian dan opsi atribut.

| Field               | Type   | Attributes                      | Keterangan |
| ------------------- | ------ | ------------------------------- | ---------- |
| product_variant_id  | uuid   | FK (product_variants, CASCADE)  | Varian     |
| attribute_option_id | bigint | FK (attribute_options, CASCADE) | Opsi       |

**Primary Key:** (product_variant_id, attribute_option_id)

---

### 📋 warehouses

Tabel Gudang dalam Toko.

| Field      | Type      | Attributes           | Keterangan   |
| ---------- | --------- | -------------------- | ------------ |
| id         | uuid      | PRIMARY KEY          |              |
| store_id   | uuid      | FK (stores, CASCADE) | Toko pemilik |
| name       | string    |                      | Nama gudang  |
| created_at | timestamp |                      |              |
| updated_at | timestamp |                      |              |

---

### 📋 warehouse_locations

Tabel Lokasi/Rak di dalam Gudang.

| Field        | Type      | Attributes               | Keterangan    |
| ------------ | --------- | ------------------------ | ------------- |
| id           | uuid      | PRIMARY KEY              |               |
| warehouse_id | uuid      | FK (warehouses, CASCADE) | Gudang        |
| name         | string    |                          | Nama (Blok A) |
| rack         | string    | NULLABLE                 | Rak           |
| shelf        | string    | NULLABLE                 | Baris/Tingkat |
| created_at   | timestamp |                          |               |
| updated_at   | timestamp |                          |               |

---

## Phase 4 - Harga, Stok, Diskon, dan Label Produk

Tabel ini bergantung pada product_variants, stores, warehouses, dan master diskon/brand/kategori.

---

### 📋 product_prices

Tabel harga produk per toko.

| Field              | Type          | Attributes                                     | Keterangan    |
| ------------------ | ------------- | ---------------------------------------------- | ------------- |
| id                 | uuid          | PRIMARY KEY                                    |               |
| product_variant_id | uuid          | FK (product_variants, CASCADE)                 | Varian Produk |
| store_id           | uuid          | FK (stores, CASCADE)                           | Toko          |
| price_type         | enum          | VALUES: 'toko', 'distributor', DEFAULT: 'toko' | Tipe harga    |
| purchase_price     | decimal(12,2) |                                                | Harga beli    |
| markup             | decimal(12,2) | DEFAULT: 0                                     | Markup        |
| markup_type        | enum          | VALUES: 'percent', 'amount', NULLABLE          | Tipe markup   |
| selling_price      | decimal(12,2) |                                                | Harga jual    |
| is_active          | boolean       | DEFAULT: false                                 | Status aktif  |
| created_at         | timestamp     |                                                |               |
| updated_at         | timestamp     |                                                |               |

---

### 📋 product_stocks

Tabel stok per varian produk di lokasi gudang. Jasa tidak masuk ke tabel ini.

| Field                 | Type      | Attributes                         | Keterangan           |
| --------------------- | --------- | ---------------------------------- | -------------------- |
| id                    | uuid      | PRIMARY KEY                        |                      |
| product_variant_id    | uuid      | FK (product_variants, CASCADE)     | Varian Produk        |
| warehouse_id          | uuid      | FK (warehouses, CASCADE)           | Gudang               |
| warehouse_location_id | uuid      | NULLABLE, FK (warehouse_locations) | Lokasi/Rak spesifik  |
| quantity              | integer   |                                    | Jumlah stok          |
| is_hidden             | boolean   | DEFAULT: false                     | Tersembunyi dari POS |
| minimum_stock         | integer   | DEFAULT: 0                         | Minimum stok alert   |
| created_at            | timestamp |                                    |                      |
| updated_at            | timestamp |                                    |                      |

---

### 📋 product_discounts

Tabel diskon produk.

| Field              | Type          | Attributes                     | Keterangan         |
| ------------------ | ------------- | ------------------------------ | ------------------ |
| id                 | uuid          | PRIMARY KEY                    |                    |
| product_variant_id | uuid          | FK (product_variants, CASCADE) | Varian Produk      |
| store_id           | uuid          | FK (stores)                    | Toko               |
| discount_type_id   | bigint        | FK (discount_types)            | Tipe diskon        |
| type               | enum          | VALUES: 'percent', 'amount'    | Jenis nilai diskon |
| value              | decimal(12,2) |                                | Nilai diskon       |
| created_at         | timestamp     |                                |                    |
| updated_at         | timestamp     |                                |                    |

---

### 📋 product_price_histories

Tabel riwayat perubahan harga produk.

| Field              | Type      | Attributes                     | Keterangan        |
| ------------------ | --------- | ------------------------------ | ----------------- |
| id                 | uuid      | PRIMARY KEY                    |                   |
| product_variant_id | uuid      | FK (product_variants, CASCADE) | Varian Produk     |
| store_id           | uuid      | FK (stores, CASCADE)           | Toko              |
| product_price_id   | uuid      | FK (product_prices, CASCADE)   | Harga produk      |
| date               | dateTime  |                                | Tanggal perubahan |
| created_at         | timestamp |                                |                   |
| updated_at         | timestamp |                                |                   |

---

### 📋 product_labels

Tabel template label produk.

| Field               | Type      | Attributes                     | Keterangan               |
| ------------------- | --------- | ------------------------------ | ------------------------ |
| id                  | uuid      | PRIMARY KEY                    |                          |
| product_variant_id  | uuid      | FK (product_variants, CASCADE) | Varian Produk            |
| product_category_id | bigint    | FK (product_categories)        | Kategori                 |
| brand_id            | bigint    | NULLABLE, FK (brands)          | Merk                     |
| label_sku           | boolean   | NULLABLE                       | Tampilkan SKU            |
| label_category      | boolean   | NULLABLE                       | Tampilkan kategori       |
| label_brand         | boolean   | NULLABLE                       | Tampilkan merk           |
| label_type          | boolean   | NULLABLE                       | Tampilkan tipe           |
| label_unit          | boolean   | NULLABLE                       | Tampilkan satuan         |
| label_size          | boolean   | NULLABLE                       | Tampilkan ukuran         |
| label_keyword       | boolean   | NULLABLE                       | Tampilkan keyword        |
| label_compatibility | boolean   | NULLABLE                       | Tampilkan kompatibilitas |
| label_description   | boolean   | NULLABLE                       | Tampilkan deskripsi      |
| separator           | string    | NULLABLE                       | Pemisah field            |
| created_at          | timestamp |                                |                          |
| updated_at          | timestamp |                                |                          |

---

## Phase 5 - Pembelian dan Batch Inventory

Purchase dibuat sebelum purchase_items dan inventory_batches karena keduanya bisa mereferensi pembelian.

---

### 📋 purchases

Tabel pembelian dari supplier.

| Field          | Type          | Attributes                            | Keterangan             |
| -------------- | ------------- | ------------------------------------- | ---------------------- |
| id             | uuid          | PRIMARY KEY                           |                        |
| store_id       | uuid          | FK (stores, CASCADE)                  | Toko                   |
| supplier_id    | uuid          | FK (partners, CASCADE)               | Supplier               |
| created_by     | bigint        | FK (users)                            | User pembuat           |
| received_by    | bigint        | NULLABLE, FK (users)                  | User penerima          |
| number         | string        | UNIQUE                                | Nomor pembelian        |
| invoice_number | string        | NULLABLE                              | Nomor invoice supplier |
| purchase_date  | date          |                                       | Tanggal pembelian      |
| discount_type  | enum          | VALUES: 'percent', 'amount', NULLABLE | Tipe diskon header     |
| discount_value | decimal(12,2) | NULLABLE                              | Nilai diskon header    |
| price          | decimal(15,2) | DEFAULT: 0                            | Total harga            |
| notes          | text          | NULLABLE                              | Catatan                |
| created_at     | timestamp     |                                       |                        |
| updated_at     | timestamp     |                                       |                        |

---

### 📋 purchase_items

Tabel item/detail pembelian.

| Field               | Type            | Attributes                                     | Keterangan          |
| ------------------- | --------------- | ---------------------------------------------- | ------------------- |
| id                  | uuid            | PRIMARY KEY                                    |                     |
| purchase_id         | uuid            | FK (purchases, CASCADE)                        | Pembelian           |
| product_variant_id  | uuid            | FK (product_variants)                          | Varian Produk       |
| price_type          | enum            | VALUES: 'toko', 'distributor', DEFAULT: 'toko' | Tipe harga beli     |
| quantity_ordered    | unsignedInteger |                                                | Jumlah pesan        |
| unit_purchase_price | decimal(12,2)   |                                                | Harga beli per unit |
| item_discount_type  | enum            | VALUES: 'percent', 'amount', NULLABLE          | Tipe diskon item    |
| item_discount_value | decimal(12,2)   | NULLABLE                                       | Nilai diskon item   |
| created_at          | timestamp       |                                                |                     |
| updated_at          | timestamp       |                                                |                     |

---

### 📋 inventory_batches

Tabel untuk mencatat Lot/Batch barang masuk (Mendukung HPP FIFO).

| Field              | Type          | Attributes                              | Keterangan                     |
| ------------------ | ------------- | --------------------------------------- | ------------------------------ |
| id                 | uuid          | PRIMARY KEY                             |                                |
| product_variant_id | uuid          | FK (product_variants, CASCADE)          | Varian Produk                  |
| warehouse_id       | uuid          | FK (warehouses, CASCADE)                | Gudang penerima                |
| purchase_item_id   | uuid          | NULLABLE, FK (purchase_items, SET NULL) | Referensi pembelian (jika ada) |
| initial_quantity   | integer       |                                         | Jumlah masuk awal              |
| current_quantity   | integer       |                                         | Sisa stok batch saat ini       |
| unit_cost          | decimal(12,2) |                                         | Harga modal aktual dari batch  |
| received_at        | timestamp     |                                         | Waktu penerimaan (untuk FIFO)  |
| created_at         | timestamp     |                                         |                                |
| updated_at         | timestamp     |                                         |                                |

---

### 📋 inventory_movements

Tabel Ledger/Kartu Stok untuk audit seluruh pergerakan barang.

| Field              | Type      | Attributes                       | Keterangan                   |
| ------------------ | --------- | -------------------------------- | ---------------------------- |
| id                 | uuid      | PRIMARY KEY                      |                              |
| warehouse_id       | uuid      | FK (warehouses, CASCADE)         | Gudang tempat mutasi         |
| product_variant_id | uuid      | FK (product_variants, CASCADE)   | Varian Produk                |
| inventory_batch_id | uuid      | NULLABLE, FK (inventory_batches) | Batch terkait                |
| reference_type     | string    |                                  | Morph: Transaksi/Mutasi/Beli |
| reference_id       | uuid      |                                  | Morph ID                     |
| type               | enum      | VALUES: 'in', 'out'              | Arah pergerakan              |
| quantity           | integer   |                                  | Jumlah pergerakan            |
| balance_after      | integer   |                                  | Sisa total stok setelahnya   |
| created_at         | timestamp |                                  | Waktu pergerakan             |
| updated_at         | timestamp |                                  |                              |

---

### 📋 product_movements (Digantikan oleh inventory_movements)

Tabel pergerakan stok produk.

| Field             | Type            | Attributes           | Keterangan              |
| ----------------- | --------------- | -------------------- | ----------------------- |
| id                | uuid            | PRIMARY KEY          |                         |
| product_id        | uuid            | FK (products)        | Produk                  |
| store_id          | uuid            | FK (stores)          | Toko                    |
| movement_type     | enum            | VALUES: 'in', 'out'  | Tipe (masuk/keluar)     |
| quantity          | unsignedInteger |                      | Jumlah (selalu positif) |
| movementable_type | string          | NULLABLE             | Tipe sumber (morphable) |
| movementable_id   | uuid            | NULLABLE             | ID sumber (morphable)   |
| occurred_at       | timestamp       |                      | Waktu kejadian          |
| created_by        | bigint          | NULLABLE, FK (users) | User pembuat            |
| note              | string          | NULLABLE             | Catatan                 |
| created_at        | timestamp       |                      |                         |
| updated_at        | timestamp       |                      |                         |

---

## Phase 6 - Service Management

Buat service_orders sebelum detail service. Untuk menghindari circular dependency, transaction_id pada service_orders sebaiknya nullable tanpa FK dulu, lalu FK ditambahkan pada Phase 9.

---

### 📋 customer_vehicles

Tabel kendaraan pelanggan.

| Field        | Type      | Attributes               | Keterangan                |
| ------------ | --------- | ------------------------ | ------------------------- |
| id           | uuid      | PRIMARY KEY              |                           |
| customer_id  | uuid      | NULLABLE, FK (partners) | Pelanggan                 |
| plate_number | string    |                          | Nomor polisi (KT 1234 AB) |
| brand        | string    | NULLABLE                 | Merk (Honda, Yamaha)      |
| model        | string    | NULLABLE                 | Model (Beat, Vario)       |
| year         | year      | NULLABLE                 | Tahun                     |
| color        | string    | NULLABLE                 | Warna                     |
| notes        | text      | NULLABLE                 | Catatan                   |
| created_at   | timestamp |                          |                           |
| updated_at   | timestamp |                          |                           |

**Unique Index:** (customer_id, plate_number)

---

### 📋 service_orders

Tabel order service (bengkel).

| Field             | Type          | Attributes                                                                                              | Keterangan               |
| ----------------- | ------------- | ------------------------------------------------------------------------------------------------------- | ------------------------ |
| id                | uuid          | PRIMARY KEY                                                                                             |                          |
| number            | string        | UNIQUE                                                                                                  | Nomor SO (SO-202511-001) |
| store_id          | uuid          | FK (stores)                                                                                             | Toko/bengkel             |
| customer_id       | uuid          | NULLABLE, FK (partners)                                                                                | Pelanggan                |
| status            | enum          | VALUES: 'checkin', 'in_progress', 'waiting_parts', 'ready', 'invoiced', 'cancelled', DEFAULT: 'checkin' | Status global            |
| checkin_at        | dateTime      |                                                                                                         | Waktu check-in           |
| completed_at      | dateTime      | NULLABLE                                                                                                | Waktu selesai            |
| general_complaint | text          | NULLABLE                                                                                                | Keluhan umum             |
| estimated_total   | decimal(15,2) | DEFAULT: 0                                                                                              | Estimasi total           |
| transaction_id    | uuid          | NULLABLE, FK (transactions)                                                                             | Transaksi POS            |
| created_at        | timestamp     |                                                                                                         |                          |
| updated_at        | timestamp     |                                                                                                         |                          |

---

### 📋 service_order_units

Tabel unit/kendaraan dalam service order.

| Field               | Type          | Attributes                                                                                                           | Keterangan          |
| ------------------- | ------------- | -------------------------------------------------------------------------------------------------------------------- | ------------------- |
| id                  | uuid          | PRIMARY KEY                                                                                                          |                     |
| service_order_id    | uuid          | FK (service_orders, CASCADE), INDEX                                                                                  | Service order       |
| customer_vehicle_id | uuid          | FK (customer_vehicles)                                                                                               | Kendaraan           |
| status              | enum          | VALUES: 'checkin', 'diagnosis', 'in_progress', 'waiting_parts', 'ready', 'invoiced', 'cancelled', DEFAULT: 'checkin' | Status unit         |
| checkin_at          | dateTime      |                                                                                                                      | Waktu check-in      |
| completed_at        | dateTime      | NULLABLE                                                                                                             | Waktu selesai       |
| plate_number        | string        |                                                                                                                      | Nomor polisi        |
| brand               | string        | NULLABLE                                                                                                             | Merk                |
| model               | string        | NULLABLE                                                                                                             | Model               |
| color               | string        | NULLABLE                                                                                                             | Warna               |
| complaint           | text          | NULLABLE                                                                                                             | Keluhan spesifik    |
| diagnosis           | text          | NULLABLE                                                                                                             | Diagnosis montir    |
| work_done           | text          | NULLABLE                                                                                                             | Ringkasan pekerjaan |
| estimated_total     | decimal(15,2) | DEFAULT: 0                                                                                                           | Estimasi total      |
| created_at          | timestamp     |                                                                                                                      |                     |
| updated_at          | timestamp     |                                                                                                                      |                     |

---

### 📋 service_order_unit_mechanics

Tabel montir yang menangani unit.

| Field                 | Type         | Attributes                                       | Keterangan      |
| --------------------- | ------------ | ------------------------------------------------ | --------------- |
| service_order_unit_id | uuid         | FK (service_order_units, CASCADE)                | Service unit    |
| mechanic_id           | bigint       | FK (users)                                       | Montir/user     |
| role                  | enum         | VALUES: 'leader', 'assistant', DEFAULT: 'leader' | Peran           |
| work_portion          | decimal(5,2) | NULLABLE                                         | Porsi pekerjaan |
| created_at            | timestamp    |                                                  |                 |
| updated_at            | timestamp    |                                                  |                 |

**Unique Index:** (service_order_unit_id, mechanic_id)

---

### 📋 service_order_items

Tabel item dalam service order unit.

| Field                 | Type            | Attributes                               | Keterangan                    |
| --------------------- | --------------- | ---------------------------------------- | ----------------------------- |
| id                    | uuid            | PRIMARY KEY                              |                               |
| service_order_unit_id | uuid            | FK (service_order_units, CASCADE), INDEX | Service unit                  |
| item_type             | enum            | VALUES: 'part', 'labor'                  | Tipe item (barang/jasa)       |
| product_variant_id    | uuid            | NULLABLE, FK (product_variants)          | Varian Produk (opsional)      |
| description           | string          | NULLABLE                                 | Deskripsi (untuk jasa custom) |
| quantity              | unsignedInteger | DEFAULT: 1                               | Jumlah                        |
| unit_price            | decimal(12,2)   | DEFAULT: 0                               | Harga per unit                |
| line_total            | decimal(15,2)   | DEFAULT: 0                               | Total line                    |
| created_at            | timestamp       |                                          |                               |
| updated_at            | timestamp       |                                          |                               |

---

### 📋 service_order_customers

Tabel data pelanggan dalam service order.

| Field            | Type      | Attributes                        | Keterangan    |
| ---------------- | --------- | --------------------------------- | ------------- |
| id               | uuid      | PRIMARY KEY                       |               |
| service_order_id | uuid      | FK (service_orders, CASCADE)      | Service order |
| customer_id      | uuid      | NULLABLE, FK (partners, CASCADE) | Pelanggan     |
| name             | string    |                                   | Nama          |
| phone            | string    | NULLABLE                          | Nomor telepon |
| address          | text      | NULLABLE                          | Alamat        |
| created_at       | timestamp |                                   |               |
| updated_at       | timestamp |                                   |               |

---

## Phase 7 - Transactions

Transactions dibuat setelah service_orders agar service_order_id bisa aman. Detail transaksi dan batch transaksi dibuat setelah transactions dan inventory_batches.

---

### 📋 transactions

Tabel transaksi (penjualan).

| Field                        | Type          | Attributes                                                             | Keterangan                         |
| ---------------------------- | ------------- | ---------------------------------------------------------------------- | ---------------------------------- |
| id                           | uuid          | PRIMARY KEY                                                            |                                    |
| number                       | string        | UNIQUE                                                                 | Nomor transaksi (POS-20250224-001) |
| store_id                     | uuid          | FK (stores), INDEX                                                     | Toko                               |
| user_id                      | bigint        | FK (users), INDEX                                                      | Kasir                              |
| customer_id                  | uuid          | NULLABLE, FK (partners)                                               | Pelanggan                          |
| payment_id                   | uuid          | NULLABLE, FK (payments)                                                | Metode pembayaran                  |
| transaction_date             | dateTime      | INDEX                                                                  | Tanggal transaksi                  |
| type                         | enum          | VALUES: 'retail', 'service', 'internal', 'warranty', DEFAULT: 'retail' | Tipe transaksi                     |
| service_order_id             | uuid          | NULLABLE, FK (service_orders)                                          | Service order (jika tipe service)  |
| subtotal                     | decimal(15,2) | DEFAULT: 0                                                             | Subtotal sebelum diskon item       |
| item_discount_total          | decimal(15,2) | DEFAULT: 0                                                             | Total diskon item                  |
| subtotal_after_item_discount | decimal(15,2) | DEFAULT: 0                                                             | Subtotal setelah diskon item       |
| universal_discount_mode      | enum          | VALUES: 'percent', 'amount', NULLABLE                                  | Mode diskon universal              |
| universal_discount_value     | decimal(12,2) | NULLABLE                                                               | Nilai diskon universal             |
| universal_discount_amount    | decimal(15,2) | DEFAULT: 0                                                             | Nominal diskon universal           |
| tax_total                    | decimal(15,2) | DEFAULT: 0                                                             | Total pajak                        |
| grand_total                  | decimal(15,2) | DEFAULT: 0                                                             | Total yang harus dibayar           |
| paid_amount                  | decimal(15,2) | DEFAULT: 0                                                             | Uang yang diterima                 |
| change_amount                | decimal(15,2) | DEFAULT: 0                                                             | Kembalian                          |
| payment_status               | enum          | VALUES: 'unpaid', 'partial', 'paid', 'refunded', DEFAULT: 'paid'       | Status pembayaran                  |
| total_cost                   | decimal(15,2) | DEFAULT: 0                                                             | Total biaya/modal                  |
| total_profit                 | decimal(15,2) | DEFAULT: 0                                                             | Total laba                         |
| status                       | enum          | VALUES: 'draft', 'completed', 'void', DEFAULT: 'completed'             | Status transaksi                   |
| note                         | text          | NULLABLE                                                               | Catatan                            |
| created_at                   | timestamp     |                                                                        |                                    |
| updated_at                   | timestamp     |                                                                        |                                    |

---

### 📋 transaction_items

Tabel item/detail transaksi.

| Field                | Type            | Attributes                            | Keterangan                        |
| -------------------- | --------------- | ------------------------------------- | --------------------------------- |
| id                   | uuid            | PRIMARY KEY                           |                                   |
| transaction_id       | uuid            | FK (transactions, CASCADE), INDEX     | Transaksi                         |
| product_variant_id   | uuid            | FK (product_variants)                 | Varian Produk                     |
| store_id             | uuid            | FK (stores)                           | Toko (redundan untuk laporan)     |
| product_stock_id     | uuid            | NULLABLE, FK (product_stocks)         | Stok produk (pemberi referensi)   |
| quantity             | unsignedInteger |                                       | Jumlah                            |
| unit_price           | decimal(12,2)   |                                       | Harga per unit                    |
| item_discount_mode   | enum            | VALUES: 'percent', 'amount', NULLABLE | Mode diskon item                  |
| item_discount_value  | decimal(12,2)   | NULLABLE                              | Nilai diskon item                 |
| item_discount_amount | decimal(15,2)   | DEFAULT: 0                            | Nominal diskon item               |
| final_unit_price     | decimal(12,2)   |                                       | Harga per unit setelah diskon     |
| line_subtotal        | decimal(15,2)   |                                       | Subtotal line (qty × unit_price)  |
| line_total           | decimal(15,2)   |                                       | Total line setelah diskon         |
| discount_type_id     | bigint          | NULLABLE, FK (discount_types)         | Tipe diskon yang dipakai          |
| unit_cost            | decimal(12,2)   | DEFAULT: 0                            | Harga modal per unit              |
| line_cost_total      | decimal(15,2)   | DEFAULT: 0                            | Total modal line                  |
| line_profit          | decimal(15,2)   | DEFAULT: 0                            | Laba line                         |
| price_edited         | boolean         | DEFAULT: false                        | Harga diubah manual               |
| pricing_mode         | string          | NULLABLE                              | Mode pricing ('fixed'/'editable') |
| created_at           | timestamp       |                                       |                                   |
| updated_at           | timestamp       |                                       |                                   |

---

### 📋 transaction_payment_attempts

Tabel percobaan pembayaran transaksi.

| Field          | Type          | Attributes                 | Keterangan          |
| -------------- | ------------- | -------------------------- | ------------------- |
| id             | uuid          | PRIMARY KEY                |                     |
| transaction_id | uuid          | FK (transactions, CASCADE) | Transaksi           |
| user_id        | bigint        | NULLABLE, FK (users)       | Pengguna            |
| payment_id     | uuid          | NULLABLE, FK (payments)    | Metode pembayaran   |
| amount         | decimal(15,2) | DEFAULT: 0                 | Nominal pembayaran  |
| amount_given   | decimal(15,2) | NULLABLE                   | Uang yang diberikan |
| change         | decimal(15,2) | NULLABLE                   | Kembalian           |
| paid_at        | timestamp     | NULLABLE                   | Waktu pembayaran    |
| metadata       | json          | NULLABLE                   | Data tambahan       |
| created_at     | timestamp     |                            |                     |
| updated_at     | timestamp     |                            |                     |

---

### 📋 transaction_item_batches

Tabel pivot untuk mencatat pemotongan HPP secara FIFO dari batch mana saja saat transaksi.

| Field               | Type          | Attributes                      | Keterangan                      |
| ------------------- | ------------- | ------------------------------- | ------------------------------- |
| id                  | uuid          | PRIMARY KEY                     |                                 |
| transaction_item_id | uuid          | FK (transaction_items, CASCADE) | Item Transaksi                  |
| inventory_batch_id  | uuid          | FK (inventory_batches, CASCADE) | Batch Inventori                 |
| quantity            | integer       |                                 | Jumlah yang dipotong dari batch |
| unit_cost           | decimal(12,2) |                                 | Harga modal per unit dari batch |
| created_at          | timestamp     |                                 |                                 |
| updated_at          | timestamp     |                                 |                                 |

---

## Phase 8 - Stock Operations, Cash Flow, dan Konfigurasi Cabang

Tabel operasional akhir karena bergantung pada kombinasi stores, users, products/product_variants, prices, dan kategori kas.

---

### 📋 stock_adjustments

Tabel penyesuaian stok.

| Field            | Type      | Attributes                 | Keterangan              |
| ---------------- | --------- | -------------------------- | ----------------------- |
| id               | uuid      | PRIMARY KEY                |                         |
| store_id         | uuid      | FK (stores, CASCADE)       | Toko                    |
| posted_by        | bigint    | NULLABLE, FK (users)       | User posting            |
| reference_number | string    | NULLABLE                   | Nomor referensi dokumen |
| occurred_at      | timestamp | DEFAULT: CURRENT_TIMESTAMP | Waktu kejadian          |
| note             | text      | NULLABLE                   | Catatan                 |
| created_at       | timestamp |                            |                         |
| updated_at       | timestamp |                            |                         |

---

### 📋 stock_adjustment_items

Tabel item penyesuaian stok.

| Field               | Type            | Attributes                      | Keterangan       |
| ------------------- | --------------- | ------------------------------- | ---------------- |
| id                  | uuid            | PRIMARY KEY                     |                  |
| stock_adjustment_id | uuid            | FK (stock_adjustments, CASCADE) | Penyesuaian stok |
| product_id          | uuid            | FK (products)                   | Produk           |
| adjustment_type     | enum            | VALUES: 'increase', 'decrease'  | Tipe penyesuaian |
| quantity            | unsignedInteger |                                 | Jumlah           |
| note                | string          | NULLABLE                        | Catatan          |
| created_at          | timestamp       |                                 |                  |
| updated_at          | timestamp       |                                 |                  |

---

### 📋 stock_transfers

Tabel transfer stok antar toko.

| Field            | Type      | Attributes                                               | Keterangan      |
| ---------------- | --------- | -------------------------------------------------------- | --------------- |
| id               | uuid      | PRIMARY KEY                                              |                 |
| from_store_id    | uuid      | FK (stores)                                              | Toko asal       |
| to_store_id      | uuid      | FK (stores)                                              | Toko tujuan     |
| status           | enum      | VALUES: 'draft', 'posted', 'cancelled', DEFAULT: 'draft' | Status          |
| reference_number | string    | NULLABLE                                                 | Nomor referensi |
| occurred_at      | timestamp | DEFAULT: CURRENT_TIMESTAMP                               | Waktu kejadian  |
| created_by       | bigint    | NULLABLE, FK (users)                                     | User pembuat    |
| posted_by        | bigint    | NULLABLE, FK (users)                                     | User posting    |
| posted_at        | timestamp | NULLABLE                                                 | Waktu posting   |
| note             | text      | NULLABLE                                                 | Catatan         |
| created_at       | timestamp |                                                          |                 |
| updated_at       | timestamp |                                                          |                 |

---

### 📋 stock_transfer_items

Tabel item transfer stok.

| Field              | Type            | Attributes                    | Keterangan              |
| ------------------ | --------------- | ----------------------------- | ----------------------- |
| id                 | uuid            | PRIMARY KEY                   |                         |
| stock_transfer_id  | uuid            | FK (stock_transfers, CASCADE) | Transfer stok           |
| product_variant_id | uuid            | FK (product_variants)         | Varian Produk           |
| quantity           | unsignedInteger |                               | Jumlah                  |
| product_price_id   | uuid            | NULLABLE, FK (product_prices) | Harga produk (opsional) |
| created_at         | timestamp       |                               |                         |
| updated_at         | timestamp       |                               |                         |

---

### 📋 cash_flows

Tabel catatan alur kas masuk dan keluar harian.

| Field          | Type          | Attributes                  | Keterangan                                                    |
| -------------- | ------------- | --------------------------- | ------------------------------------------------------------- |
| id             | uuid          | PRIMARY KEY                 |                                                               |
| store_id       | uuid          | FK (stores, CASCADE)        | Toko/cabang terkait                                           |
| user_id        | bigint        | FK (users)                  | User pencatat transaksi                                       |
| category_id    | bigint        | FK (cash_flow_categories)   | Kategori kas                                                  |
| amount         | decimal(15,2) | DEFAULT: 0                  | Nominal uang                                                  |
| date           | date          |                             | Tanggal kas masuk/keluar                                      |
| type           | enum          | VALUES: 'income', 'expense' | Jenis kategori (pemasukan/pengeluaran)                        |
| description    | text          | NULLABLE                    | Keterangan / rincian pengeluaran                              |
| reference_type | string        | NULLABLE                    | Morph type (integrasi otomatis, misal App\Models\Transaction) |
| reference_id   | uuid          | NULLABLE                    | Morph ID (ID dokumen terkait)                                 |
| created_at     | timestamp     |                             |                                                               |
| updated_at     | timestamp     |                             |                                                               |

---

### 📋 printers

Tabel konfigurasi printer toko.

| Field           | Type      | Attributes                            | Keterangan                   |
| --------------- | --------- | ------------------------------------- | ---------------------------- |
| id              | bigint    | PRIMARY KEY, AUTO INCREMENT           |                              |
| store_id        | uuid      | FK (stores)                           | Toko pemilik printer         |
| name            | string    |                                       | Nama printer                 |
| connection_type | enum      | VALUES: 'usb', 'network', 'bluetooth' | Jenis koneksi                |
| address         | string    |                                       | IP address atau nama printer |
| is_default      | boolean   | DEFAULT: false                        | Printer default untuk toko   |
| created_at      | timestamp |                                       |                              |
| updated_at      | timestamp |                                       |                              |

---

### 📋 document_sequences

Tabel sequence/counter nomor dokumen.

| Field      | Type      | Attributes                     | Keterangan                            |
| ---------- | --------- | ------------------------------ | ------------------------------------- |
| id         | uuid      | PRIMARY KEY                    |                                       |
| type       | string    |                                | Tipe dokumen (TRX, SRV, PUR, SERVICE) |
| store_id   | uuid      | NULLABLE, FK (stores, CASCADE) | Toko (jika multi-cabang)              |
| sequence   | integer   | DEFAULT: 0                     | Counter sequence                      |
| year       | integer   | NULLABLE                       | Tahun                                 |
| created_at | timestamp |                                |                                       |
| updated_at | timestamp |                                |                                       |

**Unique Index:** (type, store_id, year)

---

## Phase 9 - Alter Foreign Key Circular/Nullable Opsional

Tambahkan foreign key yang rawan circular setelah seluruh tabel tersedia, terutama service_orders.transaction_id -> transactions.id bila field tersebut ingin diberi constraint database.

---

Tidak ada tabel baru pada phase ini. Gunakan migration `Schema::table(...)` untuk menambahkan constraint FK yang sengaja ditunda.

---

## Relasi Foreign Key Summary

| From Table                   | FK Field              | References Table     | On Delete      |
| ---------------------------- | --------------------- | -------------------- | -------------- |
| partners                     | store_id              | stores               | -              |
| partners                     | linked_store_id       | stores               | -              |
| partner_role_partner         | partner_id            | partners             | CASCADE        |
| partner_role_partner         | partner_role_id       | partner_roles        | CASCADE        |
| users                        | store_id              | stores               | -              |
| sessions                     | user_id               | users                | -              |
| product_prices               | product_variant_id    | product_variants     | CASCADE        |
| product_prices               | store_id              | stores               | CASCADE        |
| product_stocks               | product_variant_id    | product_variants     | CASCADE        |
| product_stocks               | store_id              | stores               | -              |
| product_stocks               | product_price_id      | product_prices       | -              |
| product_discounts            | product_id            | products             | -              |
| product_discounts            | store_id              | stores               | -              |
| product_discounts            | discount_type_id      | discount_types       | -              |
| products                     | product_category_id   | product_categories   | -              |
| products                     | brand_id              | brands               | -              |
| products                     | unit_id               | units                | -              |
| product_categories           | parent_id             | product_categories   | SET NULL       |
| purchases                    | store_id              | stores               | CASCADE        |
| purchases                    | supplier_id           | partners            | CASCADE        |
| purchases                    | created_by            | users                | -              |
| purchases                    | received_by           | users                | -              |
| purchase_items               | purchase_id           | purchases            | CASCADE        |
| purchase_items               | product_id            | products             | -              |
| printers                     | store_id              | stores               | -              |
| transactions                 | store_id              | stores               | -              |
| transactions                 | user_id               | users                | -              |
| transactions                 | customer_id           | partners            | -              |
| transactions                 | payment_id            | payments             | -              |
| transactions                 | service_order_id      | service_orders       | NULL ON DELETE |
| transaction_items            | transaction_id        | transactions         | CASCADE        |
| transaction_items            | product_variant_id    | product_variants     | -              |
| transaction_items            | store_id              | stores               | -              |
| transaction_items            | product_stock_id      | product_stocks       | -              |
| transaction_items            | discount_type_id      | discount_types       | NULL ON DELETE |
| transaction_payment_attempts | transaction_id        | transactions         | CASCADE        |
| transaction_payment_attempts | user_id               | users                | NULL ON DELETE |
| transaction_payment_attempts | payment_id            | payments             | NULL ON DELETE |
| stock_adjustments            | store_id              | stores               | CASCADE        |
| stock_adjustments            | posted_by             | users                | -              |
| stock_adjustment_items       | stock_adjustment_id   | stock_adjustments    | CASCADE        |
| stock_adjustment_items       | product_id            | products             | -              |
| product_movements            | product_id            | products             | -              |
| product_movements            | store_id              | stores               | -              |
| product_movements            | created_by            | users                | -              |
| product_price_histories      | product_id            | products             | CASCADE        |
| product_price_histories      | store_id              | stores               | CASCADE        |
| product_price_histories      | product_price_id      | product_prices       | CASCADE        |
| product_labels               | product_id            | products             | CASCADE        |
| product_labels               | product_category_id   | product_categories   | -              |
| product_labels               | brand_id              | brands               | -              |
| service_orders               | store_id              | stores               | -              |
| service_orders               | customer_id           | partners            | NULL ON DELETE |
| service_orders               | transaction_id        | transactions         | NULL ON DELETE |
| customer_vehicles            | customer_id           | partners            | -              |
| service_order_units          | service_order_id      | service_orders       | CASCADE        |
| service_order_units          | customer_vehicle_id   | customer_vehicles    | -              |
| service_order_unit_mechanics | service_order_unit_id | service_order_units  | CASCADE        |
| service_order_unit_mechanics | mechanic_id           | users                | -              |
| service_order_items          | service_order_unit_id | service_order_units  | CASCADE        |
| service_order_items          | product_id            | products             | -              |
| service_order_customers      | service_order_id      | service_orders       | CASCADE        |
| service_order_customers      | customer_id           | partners            | CASCADE        |
| document_sequences           | store_id              | stores               | CASCADE        |
| stock_transfers              | from_store_id         | stores               | -              |
| stock_transfers              | to_store_id           | stores               | -              |
| stock_transfers              | created_by            | users                | -              |
| stock_transfers              | posted_by             | users                | -              |
| stock_transfer_items         | stock_transfer_id     | stock_transfers      | CASCADE        |
| stock_transfer_items         | product_id            | products             | -              |
| stock_transfer_items         | product_price_id      | product_prices       | -              |
| cash_flows                   | store_id              | stores               | CASCADE        |
| cash_flows                   | user_id               | users                | -              |
| cash_flows                   | category_id           | cash_flow_categories | -              |

---

## Notes

- **UUID Fields**: Beberapa tabel menggunakan UUID sebagai primary key (universally unique identifier)
- **Timestamps**: Hampir semua tabel memiliki `created_at` dan `updated_at` fields untuk audit trail
- **Soft Delete**: Tidak ada kolom `deleted_at` yang terlihat, jadi tidak ada soft delete
- **Morphable Relations**: Beberapa tabel menggunakan polymorphic relationships
- **Cascading Deletes**: Foreign keys tertentu dihapus secara cascade untuk memastikan referential integrity
- **Multi-Store**: Sistem mendukung multi-toko/cabang dengan field `store_id`
- **Partner System**: Pembeli, supplier, reseller, dan customer distributor disimpan di `partners`; perannya dikelola melalui `partner_roles`
- **Permission System**: Menggunakan package spatie/laravel-permission untuk authorization

---

**Last Updated:** 2026-03-01  
**Project:** Filament POS Bengkel
