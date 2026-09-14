<?php
namespace Database\Seeders;
use App\Models\Journal;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $j1 = Journal::create(['name'=>'[DEMO] Jurnal Sistem Informasi Indonesia','sinta_level'=>2,'subject_area'=>'Engineering','website_url'=>'https://example.org/demo-journal-1']);
        $j2 = Journal::create(['name'=>'[DEMO] Jurnal Teknologi dan Pendidikan','sinta_level'=>3,'subject_area'=>'Education','website_url'=>'https://example.org/demo-journal-2']);
        $j3 = Journal::create(['name'=>'[DEMO] Jurnal Administrasi Digital','sinta_level'=>4,'subject_area'=>'Social','website_url'=>'https://example.org/demo-journal-3']);
        $j1->articles()->createMany([
            ['authors'=>'Andi Contoh; Bunga Demo','title'=>'[DEMO] Pengaruh Kualitas Sistem terhadap Kepuasan Pengguna Sistem Informasi','year'=>2025,'abstract'=>'Studi demonstrasi ini membahas hubungan kualitas sistem, kualitas informasi, dan kepuasan pengguna. Hasil simulasi menunjukkan bahwa kualitas sistem yang baik berkaitan dengan pengalaman pengguna dan tingkat kepuasan yang lebih tinggi. Data ini hanya untuk demonstrasi aplikasi dan bukan sumber akademik nyata.','keywords'=>'kualitas sistem; kepuasan pengguna; sistem informasi','url'=>'https://example.org/demo/article-1'],
            ['authors'=>'Citra Sampel','title'=>'[DEMO] Evaluasi Keberhasilan Sistem Informasi Menggunakan Pendekatan DeLone dan McLean','year'=>2024,'abstract'=>'Artikel demonstrasi mengevaluasi system quality, information quality, service quality, use, user satisfaction, dan net benefits. Kepuasan pengguna diposisikan sebagai salah satu indikator penting keberhasilan sistem informasi. Data ini sintetis dan hanya disediakan untuk pengujian mesin pencari.','keywords'=>'DeLone McLean; system quality; user satisfaction','url'=>'https://example.org/demo/article-2'],
        ]);
        $j2->articles()->createMany([
            ['authors'=>'Dedi Demo; Eka Contoh','title'=>'[DEMO] Pemanfaatan Media Digital dalam Pembelajaran','year'=>2023,'abstract'=>'Contoh artikel ini membahas pemanfaatan media digital untuk mendukung proses pembelajaran. Penggunaan teknologi dapat membantu akses materi, interaksi, dan fleksibilitas belajar ketika didukung desain pembelajaran yang sesuai. Semua isi merupakan data demonstrasi.','keywords'=>'media digital; pembelajaran; teknologi pendidikan','url'=>'https://example.org/demo/article-3'],
            ['authors'=>'Fani Sampel','title'=>'[DEMO] Penerimaan Teknologi Pembelajaran oleh Mahasiswa','year'=>2025,'abstract'=>'Data simulasi ini menggambarkan hubungan perceived usefulness, perceived ease of use, dan intention to use dalam konteks pembelajaran digital mahasiswa. Kemudahan penggunaan dan manfaat yang dirasakan menjadi konsep utama dalam contoh ini.','keywords'=>'TAM; perceived usefulness; perceived ease of use; mahasiswa','url'=>'https://example.org/demo/article-4'],
        ]);
        $j3->articles()->createMany([
            ['authors'=>'Gita Contoh','title'=>'[DEMO] Transformasi Digital dan Efisiensi Pelayanan Publik','year'=>2024,'abstract'=>'Artikel sintetis ini menggambarkan bahwa transformasi digital dapat mempercepat proses pelayanan publik dan mengurangi beberapa aktivitas manual, tetapi juga dapat menimbulkan hambatan interaksi baru bagi pengguna yang mengalami kesulitan teknologi. Isi ini hanya untuk demo aplikasi.','keywords'=>'transformasi digital; pelayanan publik; efisiensi; hambatan digital','url'=>'https://example.org/demo/article-5'],
            ['authors'=>'Hadi Demo','title'=>'[DEMO] Hambatan Pengguna dalam Layanan Publik Digital','year'=>2022,'abstract'=>'Contoh ini membahas hambatan akses, kebingungan antarmuka, autentikasi, dan kesalahan pengisian sebagai bentuk friksi dalam penggunaan layanan digital. Hambatan tersebut dapat meningkatkan waktu dan usaha pengguna. Data ini bukan publikasi nyata.','keywords'=>'layanan publik digital; digital friction; hambatan pengguna','url'=>'https://example.org/demo/article-6'],
        ]);
    }
}
