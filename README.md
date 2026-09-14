# SINTA Citation Finder

SINTA Citation Finder adalah aplikasi berbasis Laravel untuk membantu mencari artikel dari jurnal terakreditasi SINTA yang relevan dengan suatu kalimat atau pernyataan akademik.

Pengguna cukup memasukkan sebuah kalimat, kemudian sistem akan mencari artikel yang paling relevan berdasarkan judul, kata kunci, dan abstrak yang tersedia pada corpus lokal.

Hasil pencarian menampilkan informasi seperti:

* Nama penulis
* Tahun publikasi
* Judul artikel
* Nama jurnal
* Level SINTA
* DOI atau URL artikel
* Skor relevansi
* Potongan abstrak yang relevan
* Istilah atau konsep yang cocok

Aplikasi ini menggunakan pendekatan lexical ranking tanpa layanan AI berbayar.

## Teknologi

* Laravel
* Blade
* Bootstrap
* SQLite atau MySQL
* Crossref API
* TF-IDF / lexical similarity

## Instalasi

Clone repository:

```bash
git clone https://github.com/surodipoikromo/sinta-citation-finder.git
cd sinta-citation-finder
```

Install dependency:

```bash
composer install
```

Buat file konfigurasi:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Jika menggunakan SQLite, buat file database:

```bash
touch database/database.sqlite
```

Kemudian jalankan migration:

```bash
php artisan migrate
```

Jalankan aplikasi:

```bash
php artisan serve
```

Buka:

```text
http://127.0.0.1:8000
```

## Menambahkan Corpus

Corpus artikel dapat ditambahkan secara otomatis melalui command Laravel.

Untuk mengambil daftar jurnal SINTA dan artikel terkait:

```bash
php artisan corpus:bootstrap --pages=3 --journals=20 --per-journal=100
```

Contoh untuk corpus yang lebih besar:

```bash
php artisan corpus:bootstrap --pages=10 --journals=100 --per-journal=200
```

Corpus juga dapat diperluas tanpa menghapus data yang sudah ada:

```bash
php artisan corpus:sync --journals=100 --per-journal=200
```

Jika hanya ingin memperbarui daftar jurnal SINTA:

```bash
php artisan sinta:discover --pages=10
```

Kemudian sinkronkan artikelnya:

```bash
php artisan corpus:sync --journals=100 --per-journal=200
```

Untuk membatasi berdasarkan level SINTA:

```bash
php artisan sinta:discover --pages=10 --level=2
php artisan corpus:sync --level=2 --journals=100 --per-journal=200
```

Data artikel diperoleh dari metadata publik yang tersedia melalui Crossref dan sumber jurnal terkait. Tidak semua artikel memiliki abstrak lengkap, sehingga kualitas hasil pencarian dapat dipengaruhi oleh kelengkapan metadata pada corpus.

Semakin besar dan beragam corpus, semakin baik peluang sistem menemukan artikel yang relevan.

## Catatan

SINTA Citation Finder digunakan sebagai alat bantu pencarian referensi. Hasil pencarian dan skor relevansi bukan pengganti proses membaca dan memverifikasi artikel asli sebelum digunakan sebagai sitasi akademik.
