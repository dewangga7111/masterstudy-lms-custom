# WordPress Hooks System

WordPress pakai konsep **hooks** — cara untuk "nyangkutin" kode kamu ke titik-titik
tertentu di dalam proses WordPress, tanpa edit file core-nya.

Ada dua jenis: **Action** dan **Filter**.

---

## `add_action` — Jalankan kode di titik tertentu

```php
add_action( 'hook_name', 'fungsi_kamu', $priority, $args );
```

Action = **"lakukan sesuatu ketika X terjadi"**. Tidak mengembalikan nilai.

```php
// Jalankan fungsi saat WordPress selesai load
add_action( 'init', function () {
    // kode kamu dijalankan di sini
} );

// Tambah menu saat wp-admin membangun sidebar
add_action( 'admin_menu', 'daftarkan_menu_saya' );
```

---

## `add_filter` — Ubah nilai sebelum dipakai

```php
add_filter( 'hook_name', 'fungsi_kamu', $priority, $args );
```

Filter = **"ambil nilai X, ubah, kembalikan"**. Wajib return nilai.

```php
// Tambah field ke array yang sudah ada
add_filter( 'stm_wpcfto_fields', function ( array $fields ): array {
    $fields['key_baru'] = [ ... ];
    return $fields; // wajib dikembalikan
} );
```

> **Bedanya dengan action:** filter harus `return`, action tidak.

---

## `$priority` — Urutan eksekusi

Parameter ketiga, default `10`. Makin kecil = makin duluan dijalankan.

```php
add_action( 'init', 'fungsi_a', 5  ); // jalan duluan
add_action( 'init', 'fungsi_b', 10 ); // default
add_action( 'init', 'fungsi_c', 20 ); // jalan belakangan
```

Berguna ketika dua plugin/tema hook ke titik yang sama dan urutan penting.

---

## `$args` — Jumlah parameter yang diterima

Parameter keempat, default `1`. Isi sesuai berapa parameter yang dikirim hook-nya.

```php
// Hook ini mengirim 2 parameter: $post_id dan $post
add_action( 'save_post', function ( $post_id, $post ) {
    // ...
}, 10, 2 ); // beritahu WordPress bahwa fungsi ini menerima 2 param
```

---

## `do_action` dan `apply_filters` — Sisi pengirim

Plugin/tema bisa membuat hook sendiri. Inilah yang "dipasangi" oleh `add_action`/`add_filter`.

```php
// Plugin utama membuat titik action
do_action( 'masterstudy_lms_after_enroll', $user_id, $course_id );

// Plugin lain nyangkut ke situ
add_action( 'masterstudy_lms_after_enroll', function ( $user_id, $course_id ) {
    // jalankan sesuatu setelah user enroll
}, 10, 2 );
```

```php
// Plugin utama membuat titik filter
$fields = apply_filters( 'masterstudy_lms_course_custom_fields', [] );

// Plugin lain menambahkan field ke array itu
add_filter( 'masterstudy_lms_course_custom_fields', function ( $fields ) {
    $fields[] = [ 'type' => 'text', 'name' => 'career_path' ];
    return $fields;
} );
```

---

## `register_activation_hook` — Khusus saat plugin diaktifkan

```php
register_activation_hook( __FILE__, 'fungsi_saat_aktif' );

function fungsi_saat_aktif() {
    // Buat tabel, set default options, dll
    // Hanya jalan SEKALI saat admin klik "Activate"
}
```

| Hook | Kapan jalan |
|---|---|
| `register_activation_hook` | Saat plugin diaktifkan |
| `register_deactivation_hook` | Saat plugin dinonaktifkan |
| `register_uninstall_hook` | Saat plugin dihapus |

---

## Ringkasan

| Fungsi | Kapan dipakai |
|---|---|
| `add_action` | Jalankan kode di titik tertentu, tidak perlu return |
| `add_filter` | Ubah nilai, wajib return |
| `do_action` | Membuat titik action (biasanya di plugin/core) |
| `apply_filters` | Membuat titik filter (biasanya di plugin/core) |
| `register_activation_hook` | Jalankan sekali saat plugin diaktifkan |
