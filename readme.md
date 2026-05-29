# Web Reservasi - Hotel Khatulistiwa

Project ini merupakan sistem sederhana untuk membantu pengelolaan reservasi hotel, khususnya bagi resepsionis agar proses pemesanan kamar jadi lebih cepat dan terorganisir. Sistem ini juga sudah mendukung pemesanan beberapa tipe kamar dalam satu transaksi sekaligus, serta dapat menghitung total biaya secara otomatis.

## Anggota Kelompok 5
1. Imam Akbar Arbain      D1041241013
2. Rafli Gustiansyah      D1041241015
3. Vincent Rian Jonathan  D1041241075

## Fitur

Sistem ini dirancang untuk mempermudah proses check-in dengan beberapa fitur utama, yaitu:

* **Multi-Step Form**
  Proses input dibagi menjadi 3 tahap, mulai dari data pelanggan, pemilihan tipe kamar, hingga pemilihan nomor kamar, supaya lebih rapi dan mudah diikuti.

* **Dynamic Booking**
  Dalam satu reservasi, pengguna bisa memesan lebih dari satu tipe kamar, misalnya kombinasi kamar Standard dan Deluxe.

* **Perhitungan Harga Otomatis**
  Total biaya akan langsung dihitung berdasarkan harga per malam dari masing-masing tipe kamar dikalikan dengan lama menginap.

* **Visual Room Picker**
  Resepsionis bisa memilih kamar yang tersedia dengan cara klik langsung pada tampilan grid nomor kamar sesuai tipe yang dipilih.

## Struktur Tabel Utama

Sistem ini menggunakan database MySQL dengan beberapa tabel utama yang saling terhubung, yaitu:

* **pelanggan**
  Menyimpan data tamu seperti nama, email, dan nomor HP.

* **reservasi**
  Menyimpan data utama transaksi, seperti ID reservasi, tanggal check-in dan check-out, serta total biaya.

* **tipekamar**
  Berisi data jenis kamar beserta harga per malamnya.

* **kamar**
  Menyimpan data nomor kamar dan statusnya (tersedia atau terisi).

* **detail_reservasi**
  Digunakan untuk mencatat tipe kamar apa saja yang dipesan dalam satu reservasi.

* **detail_kamar**
  Menyimpan informasi nomor kamar spesifik yang ditempati oleh tamu.

## Cara Membuat & Menjalankan Aplikasi

1. **Menyiapkan Database**
   Siapkan database yang telah dibuat pada mini project II, dalam kasus ini kami menggunakan database hoteldb.

2. **Membuat Folder dan File**
   Folder yang dibuat beserta file didalamnya yaitu config(database.php), process(delete.php, insert.php, update.php), dan public(edit.php, hapus.php, index.php, tambah.php). Isi masing-masing file sesuai ketentuan yang ada di modul.

3. **Mengatur Koneksi Database**
   Buka file config/database.php, kemudian sesuaikan bagian host, dbname, username, dan password dengan konfigurasi server lokal kamu.

4. **Menjalankan Aplikasi**
   Pastikan Apache dan MySQL di Laragon sudah aktif.
   Buka folder di VSCode
   Aktifkan server di terminal VSCode dengan mengetik php -S localhost:8000".
   Setelah itu, buka browser dan akses:
   "http://localhost:8000/PROJECT_PHP/public/index.php"

## Kredensial Login
1. **Admin**
   * Username : admin1
   * Password : admin123
2. **Resepsionis**
   * Username : resepsionis1
   * Password : resepsionis123
