# SINTA Citation Finder

Aplikasi Laravel sederhana untuk membantu menemukan **kandidat artikel dari jurnal terakreditasi SINTA** yang relevan dengan sebuah kalimat akademik. V1 ini **tidak menggunakan AI berbayar**. Pencarian dilakukan secara lokal dengan normalisasi teks, TF-IDF, cosine similarity, dan pembobotan field.

> **Penting:** repository ini tidak menyertakan database penuh SINTA dan tidak melakukan scraping otomatis. Pengguna mengimpor corpus artikel yang telah diverifikasi melalui CSV. Data seeder bawaan seluruhnya diberi label `[DEMO]` dan bukan referensi akademik nyata.

## Fitur V1

- UI Bahasa Indonesia.
- Input satu kalimat/pernyataan akademik.
- Filter SINTA 1–6 dan tahun minimum.
- Ranking relevansi artikel dengan TF-IDF + cosine similarity.
- Bobot pencarian: judul ×3, kata kunci ×2, abstrak ×1.
- Menampilkan penulis, tahun, judul, jurnal, level SINTA, URL/DOI, skor relevansi, istilah cocok, dan potongan abstrak yang paling relevan.
- Impor corpus melalui CSV tanpa package tambahan.
- Update otomatis jika judul yang sama di jurnal yang sama diimpor kembali.
- Template CSV siap unduh.
- Seeder demo untuk pengujian lokal.
- SQLite default; kompatibel dengan MySQL.
- Tidak membutuhkan Node/NPM karena antarmuka menggunakan Bootstrap CDN.

## Mengapa tidak scraping SINTA otomatis?

SINTA dipakai sebagai sumber verifikasi status jurnal, tetapi aplikasi portfolio sebaiknya tidak bergantung pada struktur HTML eksternal yang dapat berubah. Arsitektur corpus lokal membuat aplikasi reproducible, gratis, mudah di-clone, dan tidak mengharuskan pengguna melakukan scraping terhadap layanan pihak ketiga.

Untuk penggunaan nyata, isi CSV dengan metadata artikel dan abstrak yang secara sah dapat kamu gunakan, kemudian pastikan `sinta_level` jurnal masih sesuai dengan status yang diverifikasi.

## Instalasi

Persyaratan:

- PHP 8.2+
- Composer
- ekstensi PHP SQLite atau MySQL

```bash
unzip sinta-citation-finder-v1.zip
cd sinta-citation-finder
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Buka:

```text
http://127.0.0.1:8000
```

Seeder berisi enam artikel sintetis `[DEMO]` agar mesin pencari langsung dapat diuji.

Contoh pencarian demo:

```text
Kualitas sistem berpengaruh terhadap kepuasan pengguna.
```

atau:

```text
Transformasi digital dapat meningkatkan efisiensi pelayanan publik.
```

## Menggunakan MySQL

Ubah `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sinta_citation_finder
DB_USERNAME=root
DB_PASSWORD=
```

Lalu jalankan:

```bash
php artisan migrate --seed
```

## Format CSV

Kolom wajib:

```text
authors,title,journal,sinta_level,abstract
```

Kolom opsional:

```text
year,url,doi,keywords,subject_area,issn,eissn,journal_url
```

Contoh tersedia di `database/sample_corpus.csv` dan tombol **Unduh Template CSV** pada halaman Korpus.

## Cara kerja ranking

1. Query dinormalisasi menjadi token penting.
2. Stopword Indonesia dan Inggris dibuang.
3. Dokumen dibentuk dari `title`, `keywords`, dan `abstract`.
4. Title dimasukkan tiga kali dan keyword dua kali untuk memberi bobot lebih tinggi.
5. IDF dihitung terhadap corpus yang sedang difilter.
6. Query dan dokumen diubah menjadi vektor TF-IDF.
7. Cosine similarity dihitung dan dikonversi menjadi skor 0–100%.
8. Kalimat abstrak dengan jumlah istilah query paling banyak dipilih sebagai potongan teks relevan.

Implementasi utama ada pada:

```text
app/Services/CitationSearchService.php
```

## Batasan V1

- Tidak melakukan semantic embedding/LLM.
- Sinonim yang berbeda jauh secara leksikal dapat terlewat.
- Stemming Bahasa Indonesia belum digunakan.
- Status SINTA tidak diperbarui otomatis.
- Relevant excerpt berasal dari abstrak corpus, bukan otomatis dari PDF penuh.
- Skor relevansi bukan validasi bahwa artikel mendukung klaim pengguna.

## Etika penggunaan akademik

Aplikasi ini adalah **discovery tool**, bukan generator sumber. Selalu buka artikel asli dan verifikasi bahwa isi penelitian memang mendukung pernyataan yang akan ditulis. Jangan menjadikan skor relevansi atau potongan abstrak sebagai pengganti membaca sumber.

## Testing

Setelah dependency terpasang:

```bash
php artisan test
```

Tes meliputi halaman pencarian, ranking artikel yang cocok, dan filter level SINTA.

## Struktur penting

```text
app/Services/CitationSearchService.php
app/Services/CorpusImportService.php
app/Http/Controllers/SearchController.php
app/Http/Controllers/CorpusController.php
database/migrations/
database/seeders/DatabaseSeeder.php
resources/views/search.blade.php
resources/views/corpus/index.blade.php
```

## Lisensi

MIT.

## V1.1 — Importer / Crawler Corpus

V1.1 menambahkan pipeline corpus otomatis tanpa AI berbayar:

```text
SINTA journal directory
        ↓
name + SINTA level + ISSN
        ↓
Crossref REST API (/journals/{issn}/works)
        ↓
article metadata + abstract (jika tersedia)
        ↓
local Laravel corpus
        ↓
TF-IDF + cosine similarity search
```

### Bootstrap corpus otomatis

Setelah migration:

```bash
php artisan corpus:bootstrap --pages=3 --journals=20 --per-journal=100
```

Command tersebut setara dengan:

```bash
php artisan sinta:discover --pages=3
php artisan corpus:sync --journals=20 --per-journal=100
```

Contoh hanya SINTA 2 dan artikel 2022 ke atas:

```bash
php artisan sinta:discover --pages=10 --level=2
php artisan corpus:sync --level=2 --journals=50 --per-journal=200 --from-year=2022
```

### Konfigurasi yang direkomendasikan

Isi email pada `.env` agar request Crossref masuk *polite pool*:

```env
CROSSREF_MAILTO=nama@example.com
```

Jeda default crawler SINTA adalah 1200 ms per halaman dan Crossref 250 ms per page. Nilainya dapat diubah melalui `.env`.

### Catatan sumber data

- SINTA dipakai untuk menemukan jurnal, ISSN, dan level akreditasi.
- Crossref dipakai untuk mengambil metadata artikel berdasarkan ISSN.
- Tidak semua record Crossref mempunyai abstrak. Record tanpa abstrak tetap dapat ditemukan dari judul/subject, tetapi tidak menghasilkan potongan abstrak yang kaya.
- Struktur HTML SINTA dapat berubah. `SintaJournalCrawler` sengaja dibuat terpisah dari mesin pencari agar parser dapat diperbarui tanpa mengubah search engine.
- CSV importer tetap tersedia sebagai fallback dan untuk corpus terkurasi.
- Sebelum mengutip, selalu buka artikel asli dan verifikasi isi, penulis, tahun, DOI, serta status SINTA jurnal.

### Artisan commands

| Command | Fungsi |
|---|---|
| `sinta:discover` | Crawl daftar jurnal SINTA publik |
| `corpus:sync` | Sinkronkan artikel via Crossref berdasarkan ISSN |
| `corpus:bootstrap` | Menjalankan discovery + sync sekaligus |


## Ranking pencarian v1.2

Mesin pencarian tidak lagi hanya menghitung kemunculan kata tunggal. Query dipecah menjadi istilah dan frasa konsep. Contoh `Kualitas sistem berpengaruh terhadap kepuasan pengguna` diperlakukan sebagai konsep `kualitas sistem` dan `kepuasan pengguna`; kata relasional seperti `berpengaruh` diberi prioritas rendah/dibuang. Ranking menggabungkan kecocokan istilah berbobot IDF, phrase/ngram matching, cakupan konsep, serta bobot field (judul > kata kunci > abstrak). Pendekatan ini tetap sepenuhnya lokal dan tidak menggunakan AI/API berbayar.
