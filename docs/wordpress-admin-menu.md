# WordPress Admin Menu

## 1. Hook ke `admin_menu`

```php
add_action( 'admin_menu', 'nama_fungsi_kamu' );
```

`admin_menu` adalah event yang WordPress trigger saat membangun menu di wp-admin.
Semua registrasi menu harus ada di dalam hook ini.

---

## 2. `add_menu_page()` — Top-level menu

```php
add_menu_page(
    'Page Title',      // Judul di <title> browser
    'Menu Label',      // Teks yang tampil di sidebar
    'manage_options',  // Capability: siapa yang bisa lihat
    'menu-slug',       // Slug unik (jadi URL: admin.php?page=menu-slug)
    'callback_fn',     // Fungsi yang dijalankan saat halaman dibuka
    'dashicons-star',  // Icon (dari dashicons atau URL gambar)
    30                 // Posisi di sidebar (20=Pages, 25=Comments, 80=Settings)
);
```

---

## 3. `add_submenu_page()` — Submenu

```php
add_submenu_page(
    'parent-slug',     // Slug menu induk (harus sama dengan menu_page di atas)
    'Page Title',
    'Submenu Label',
    'manage_options',
    'submenu-slug',    // Slug unik untuk submenu ini
    'callback_fn'
);
```

> **Catatan:** Submenu pertama biasanya duplikat parent dengan slug yang sama —
> ini yang membuat label parent dan submenu pertama bisa berbeda teksnya.

---

## 4. Callback function — Render halaman

```php
function callback_fn() {
    ?>
    <div class="wrap">
        <h1>Judul Halaman</h1>
        <!-- konten bebas di sini -->
    </div>
    <?php
}
```

`class="wrap"` wajib ada — tanpa ini layout WordPress tidak akan terapply dengan benar.

---

## 5. Capability — Siapa yang bisa akses

| Capability       | Artinya                  |
|------------------|--------------------------|
| `manage_options` | Administrator saja       |
| `edit_posts`     | Editor ke atas           |
| `read`           | Semua user yang login    |

---

## 6. Dashicons — Icon menu

Icon bawaan WordPress. Format: `dashicons-nama`.

Katalog lengkap: https://developer.wordpress.org/resource/dashicons/

---

## Alur lengkap

```
add_action('admin_menu', fn)
  └─ add_menu_page(...)        → buat menu utama "Careers"
  └─ add_submenu_page(...)     → submenu "All Careers"  (slug sama dg parent)
  └─ add_submenu_page(...)     → submenu "Add Career"   (slug beda)
  └─ add_submenu_page(...)     → submenu "Categories"   (slug beda)
```

Setiap submenu slug yang berbeda = halaman terpisah dengan callback-nya sendiri.
URL-nya jadi `admin.php?page={slug}`.

---

## Contoh implementasi

```php
add_action( 'admin_menu', function () {

    add_menu_page(
        'Careers',
        'Careers',
        'manage_options',
        'my-careers',
        'page_careers_list',
        'dashicons-welcome-learn-more',
        30
    );

    add_submenu_page( 'my-careers', 'All Careers',       'All Careers',       'manage_options', 'my-careers',         'page_careers_list' );
    add_submenu_page( 'my-careers', 'Add Career',        'Add Career',        'manage_options', 'my-careers-add',     'page_careers_form' );
    add_submenu_page( 'my-careers', 'Career Categories', 'Career Categories', 'manage_options', 'my-careers-cat',     'page_careers_cat'  );

} );

function page_careers_list() {
    echo '<div class="wrap"><h1>All Careers</h1></div>';
}

function page_careers_form() {
    echo '<div class="wrap"><h1>Add Career</h1></div>';
}

function page_careers_cat() {
    echo '<div class="wrap"><h1>Career Categories</h1></div>';
}
```
