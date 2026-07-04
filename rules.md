# Laravel Inertia Vue Project Rules

## Tech Stack

- Laravel (Latest Stable)
- Inertia.js
- Vue 3
- Tailwind CSS
- MySQL

---

# Architecture

Gunakan arsitektur berikut sebagai standar pada project ini.

```
Controller
    ↓
Service
    ↓
Repository
    ↓
Model
```

## Responsibilities

### Controller

Controller harus sesederhana mungkin.

- Tidak boleh berisi business logic.
- Tidak boleh berisi query database secara langsung.
- Hanya menerima Request.
- Memanggil Service.
- Mengembalikan Inertia Response atau Redirect.

---

### Service

Service bertanggung jawab terhadap business logic.

Contoh:

- proses create/update/delete
- manipulasi data
- transaksi database
- upload file
- validasi tambahan
- integrasi dengan service lain

Service tidak boleh mengembalikan Response HTTP.

---

### Repository

Repository hanya bertanggung jawab terhadap akses database.

Contoh:

- create
- update
- delete
- find
- paginate
- search
- custom query

Repository tidak boleh berisi business logic.

---

### Model

Model hanya berisi:

- relationship
- casts
- fillable
- accessor/mutator (jika diperlukan)
- scope sederhana

Hindari menaruh business logic yang kompleks di Model.

---

# Validation

Selalu gunakan Form Request untuk validasi apabila:

- Create
- Update
- Store
- Import

Jangan melakukan validasi langsung di Controller menggunakan `$request->validate()` untuk module yang menggunakan Service Repository Pattern.

---

# API Resource

Gunakan Laravel Resource untuk semua data yang dikirim ke frontend apabila:

- data cukup kompleks
- memiliki relasi
- digunakan pada table
- digunakan untuk API
- membutuhkan transformasi data

Jangan mengembalikan Model secara langsung apabila menggunakan Resource.

---

# Database Query

Jangan menulis query database di Controller.

Contoh yang tidak diperbolehkan:

```php
User::create(...);
User::find(...);
User::where(...);
```

Semua query harus melalui Repository.

---

# Transactions

Jika terdapat lebih dari satu proses penyimpanan data, gunakan:

```php
DB::transaction(...)
```

Transaksi berada di dalam Service.

---

# File Upload

Seluruh proses upload file dilakukan di Service.

Controller tidak boleh mengelola upload file.

---

# Inertia Response

Controller hanya mengembalikan:

```php
return Inertia::render(...);
```

atau

```php
return redirect()->route(...);
```

Data yang dikirim ke Inertia menggunakan Resource jika diperlukan.

---

# Code Style

- Gunakan Dependency Injection.
- Hindari static helper jika dapat menggunakan DI.
- Gunakan type hint.
- Gunakan return type.
- Ikuti standar PSR-12.
- Gunakan penamaan yang jelas dan konsisten.
- Hindari duplicate code (DRY).

---

# Folder Structure

```
app/
    Http/
        Controllers/
        Requests/
        Resources/

    Services/

    Repositories/

    Models/
```

---

# Exception Rule (Simple CRUD)

Tidak semua module wajib menggunakan Service Repository Pattern.

Apabila memenuhi seluruh kondisi berikut:

- hanya memiliki 2–5 field input
- hanya melakukan operasi CRUD sederhana
- tidak memiliki business logic
- tidak ada relasi yang kompleks
- tidak ada upload file
- tidak membutuhkan transaksi database

Maka diperbolehkan menggunakan struktur sederhana:

```
Controller
    ↓
Model
```

Pada kasus ini:

- Form Request bersifat opsional.
- Resource bersifat opsional.
- Repository tidak wajib.
- Service tidak wajib.

Namun Controller tetap harus dijaga tetap sederhana dan tidak berisi logika yang berlebihan.

---

# AI Instructions

Saat menghasilkan kode:

1. Selalu gunakan Service Repository Pattern sebagai default.
2. Selalu buat Form Request untuk Create dan Update.
3. Selalu gunakan Resource untuk response yang kompleks.
4. Jangan menaruh query database di Controller.
5. Jangan menaruh business logic di Repository.
6. Jangan menaruh business logic di Controller.
7. Jika module hanya CRUD sederhana dengan sedikit field dan tanpa business logic, AI boleh menyederhanakan implementasi tanpa membuat Service, Repository, Resource, atau Form Request.
8. Prioritaskan readability, maintainability, dan clean architecture dibanding membuat struktur yang terlalu kompleks.
9. Jika ragu, gunakan Service Repository Pattern sebagai pilihan utama.

---

# Frontend Page Rules

Semua tampilan Inertia Vue harus diletakkan di dalam folder:

```txt
resources/js/Pages
```

Gunakan struktur folder berdasarkan nama module.

Contoh:

```txt
resources/js/Pages/roles/index.vue
resources/js/Pages/roles/create.vue
resources/js/Pages/roles/edit.vue
resources/js/Pages/roles/show.vue
```

## Page Naming

Gunakan penamaan file lowercase.

Contoh yang benar:

```txt
roles/index.vue
users/create.vue
permissions/edit.vue
products/show.vue
```

---

# Create, Edit, and Show Page Rules

Gunakan halaman tersendiri untuk Create, Edit, dan Show apabila:

- jumlah input banyak
- form memiliki banyak section
- terdapat upload file
- terdapat relasi kompleks
- terdapat nested form
- membutuhkan preview data
- membutuhkan layout detail yang panjang

Contoh struktur:

```txt
resources/js/Pages/products/index.vue
resources/js/Pages/products/create.vue
resources/js/Pages/products/edit.vue
resources/js/Pages/products/show.vue
```

---

# Modal Form Rules

Gunakan modal untuk Create, Edit, atau Show apabila:

- jumlah input sedikit
- form sederhana
- tidak ada upload file
- tidak ada relasi kompleks
- tidak membutuhkan halaman detail khusus
- hanya CRUD ringan

Contoh:

```txt
resources/js/Pages/roles/index.vue
```

Pada kasus ini, modal Create/Edit/Show boleh berada langsung di dalam `index.vue` atau dipisahkan menjadi component kecil jika mulai panjang.

---

# AI Frontend Instructions

Saat membuat halaman Inertia Vue:

1. Selalu letakkan file halaman di `resources/js/Pages`.
2. Gunakan struktur folder per module, contoh `roles/index.vue`.
3. Untuk form dengan banyak input, buat halaman terpisah:
    - `create.vue`
    - `edit.vue`
    - `show.vue`
4. Untuk form dengan sedikit input, gunakan modal di halaman `index.vue`.
5. Jangan membuat halaman terpisah untuk CRUD sederhana jika modal sudah cukup.
6. Jika modal terlalu panjang atau sulit dibaca, pisahkan menjadi component.
7. Jika ragu, gunakan halaman terpisah untuk form kompleks dan modal untuk form sederhana.
