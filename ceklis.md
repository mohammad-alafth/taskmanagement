# Ceklis Pengujian — Task Management

Dokumen ini mencatat seluruh hasil pengujian (test results) untuk aplikasi Task Management
sesuai `PRD_Task_Management_Execution_Ready.md`, `UI_UX_Design_Task_Management.md`,
serta spec DragDrop (`PRD_Task_Management_Execution_Ready_DragDrop.md` dan
`UI_UX_Design_Task_Management_DragDrop.md`).

## Informasi Lingkungan

| Item | Nilai |
|---|---|
| Framework | Laravel 10.50.3, PHP 8.2.23 |
| Database (dev) | MySQL `taskmanagement` (Laragon) |
| Database (test) | MySQL `taskmanagement_test` (otomatis, `phpunit.xml`) |
| Base URL live | `http://127.0.0.1:8000` |
| Tanggal pengujian | 30 September – 1 Oktober 2026 |
| Akun uji (password: `password`) | admin@example.com (Admin), manager@example.com (Manager), staff@example.com (Staff), checker@example.com (Checker) |

## Ringkasan Hasil

| Metode Pengujian | Jumlah | Lulus | Gagal |
|---|---|---|---|
| Pengujian otomatis (PHPUnit Feature/Unit) | **101 test** | **101** (425 assertions) | 0 |
| End-to-end live via HTTP (server berjalan) | **81 check** | **81** | 0 |
| **TOTAL** | **182** | **182** | **0** |

Cara menjalankan ulang:

```powershell
# Pengujian otomatis
& C:\laragon\bin\php\php-8.2.23-nts-Win32-vs16-x64\php.exe artisan test

# E2E live (server harus jalan: php artisan serve)
powershell -ExecutionPolicy Bypass -File $env:TEMP\opencode\e2e_live.ps1
```

---

## A. Pengujian Otomatis (PHPUnit) — 101 test, SEMUA LULUS

### A1. Autentikasi & Sesi — `AuthenticationTest` (8/8)

- [x] UAT-01: Login valid → redirect ke `/dashboard`, sesi terbentuk
- [x] UAT-02: Login invalid → error "Invalid credentials", tidak ada sesi
- [x] Akun non-aktif tidak bisa login
- [x] Validasi input login (email & password wajib)
- [x] Logout menghapus sesi
- [x] Guest diarahkan ke `/login` dari halaman terproteksi
- [x] User login diarahkan menjauh dari halaman `/login`
- [x] Password tersimpan ter-hash (bukan plaintext)

### A2. Manajemen Tugas — `TaskCrudTest` (13/13)

- [x] UAT-03: Create task → status default `WAITING`, assignee/checker/pemilik tersimpan
- [x] Create task membuat activity log `create` + notifikasi `TASK_ASSIGNED` ke assignee
- [x] Validasi create: judul < 3 karakter ditolak, input lama tetap tampil (old input)
- [x] Assignee non-aktif ditolak
- [x] Priority tidak valid & tanggal lewat ditolak
- [x] Staff tidak bisa create task (403)
- [x] Edit task tersimpan + activity log `update-title` / `update-priority`
- [x] Admin/Manager bisa reassign; perubahan assignee oleh staff diabaikan
- [x] Admin bisa delete task; staff ditolak (403)
- [x] Filter list task: status, search (judul/ID), priority
- [x] Staff hanya melihat task miliknya di list
- [x] Hak akses detail task: assignee/admin OK, pihak tak terkait 403
- [x] Create task dengan lampiran file

### A3. Workflow Status — `WorkflowTest` (12/12)

- [x] UAT-04: `WAITING → ON_PROCESS` (Start oleh assignee)
- [x] UAT-05: `ON_PROCESS → ON_CHECK` (Submit for Check oleh assignee)
- [x] UAT-06: `ON_CHECK → DONE` (Approve oleh checker) + `completed_at` terisi
- [x] UAT-07: Revision `ON_CHECK → ON_PROCESS` + alasan wajib diisi (tanpa alasan ditolak)
- [x] UAT-08: Transisi invalid ditolak, status tidak berubah (approve dari WAITING, submit dari WAITING, reopen bukan DONE)
- [x] UAT-13: Setiap perubahan status tercatat di activity log (`start`, `submit`, `approve`, dst.)
- [x] Notifikasi transisi ke penerima benar (submit→checker, approve→assignee)
- [x] Reopen `DONE → ON_PROCESS` + `completed_at` dihapus + activity + notifikasi `TASK_REOPENED`
- [x] Staff tidak bisa approve / request revision (403)
- [x] Checker tidak bisa submit for check (403)
- [x] Hanya Admin/Manager yang boleh reopen
- [x] Happy path penuh: start → submit → revision → start → submit → approve = DONE (+ komentar alasan revision)

### A4. Dokumen/Lampiran — `AttachmentTest` (7/7)

- [x] UAT-09: Upload PDF valid → tersimpan di disk `private`, activity `upload` tercatat, halaman detail menampilkan lampiran
- [x] UAT-10: Upload file `.php` ditolak (validasi `mimes`)
- [x] Upload file > 10 MB ditolak
- [x] UAT-11: Download oleh assignee → 200
- [x] UAT-11: Download oleh user tak terkait → 403
- [x] UAT-11: Download oleh guest → redirect `/login`
- [x] Delete lampiran: pihak tak terkait 403; admin berhasil + file hilang dari storage + activity tercatat

### A5. Komentar — `CommentTest` (4/4)

- [x] UAT-12: Komentar tersimpan dan tampil di halaman detail
- [x] Komentar wajib berisi teks
- [x] Staff tak terkait tidak bisa berkomentar (403)
- [x] Admin bisa menghapus komentar

### A6. Notifikasi — `NotificationTest` (6/6)

- [x] UAT-14: Halaman notifikasi hanya menampilkan notifikasi milik sendiri
- [x] Tandai satu notifikasi sudah dibaca (`PATCH /notifications/{id}/read`)
- [x] Tandai semua sudah dibaca (`PATCH /notifications/read-all`)
- [x] Tidak bisa menandai notifikasi orang lain (403)
- [x] Filter `unread` + empty state "You're all caught up"
- [x] Badge jumlah unread tampil di layout (sidebar & lonceng)

### A7. Dashboard — `DashboardTest` (7/7)

- [x] UAT-14: Assignee melihat task-nya di dashboard
- [x] UAT-15: KPI **Overdue** hanya menghitung task lewat tenggat & belum DONE (nilainya tepat)
- [x] Bagian "Due Soon" menampilkan task jatuh tempo ≤ 3 hari
- [x] Dashboard staff hanya berisi task miliknya (data berbasis peran)
- [x] Dashboard checker menampilkan antrean "Tasks Waiting for Your Review"
- [x] KPI jumlah task akurat sesuai DB
- [x] Feed activity di-scope per peran (tidak bocor ke task orang lain)

### A8. Admin & Peran — `AdminTest` (9/9)

- [x] UAT-16: Manager/Staff/Checker ditolak (403) di `/admin/users`, `/admin/users/create`, `/admin/roles`
- [x] Admin dapat membuka semua halaman admin
- [x] Admin membuat user (role + password hash + login dengan password baru)
- [x] Validasi create user (nama, email unik, password, role)
- [x] Admin update user (nama/email/role)
- [x] Toggle status: admin tidak bisa menonaktifkan akunnya sendiri
- [x] Reset password admin → password baru bisa dipakai login
- [x] User non-aktif tidak bisa login
- [x] Halaman Profile menampilkan data sendiri

### A9. UI Acceptance (UI doc §69) — `UiAcceptanceTest` (12/12)

- [x] Halaman login: render desain system (Inter, form email/password)
- [x] Layout: sidebar navigasi, pencarian topbar, lonceng notifikasi, menu avatar, nav mobile bawah
- [x] List task: tab status berhitung, filter bar (search/priority/assignee/checker + due date/created date), tampilan **Board (Kanban) default** dengan toggle Tabel, badge
- [x] Empty state "No tasks found" saat filter tidak cocok
- [x] Detail task: workflow stepper, bagian Description/Attachments/Comments/Activity/Task Information, modal upload
- [x] Aksi kontekstual per status: Start (WAITING), Submit for Check (ON_PROCESS), Approve + Request Revision (ON_CHECK, oleh checker), Reopen (DONE, oleh admin)
- [x] Form create: section Task Information/Assignment/Task Settings + tanda required `*`
- [x] Form edit: data task ter-prefill
- [x] Dashboard: KPI + Recent Tasks, Due Soon, Recent Activity, Tasks by Assignee
- [x] Badge status & priority selalu punya label teks (bukan hanya warna)
- [x] Halaman error ramah: 403 "Access denied" & 404
- [x] CSRF middleware (`VerifyCsrfToken`) terdaftar pada grup `web`

### A10. Kanban Board & DragDrop — `KanbanBoardTest` (21/21)

- [x] `/tasks` default menampilkan papan Kanban 4 kolom (WAITING/ON_PROCESS/ON_CHECK/DONE) + tab + toggle Board/Tabel
- [x] `?view=table` tetap tersedia sebagai tampilan sekunder (tabel `Task ID`, tanpa markup papan)
- [x] Empty state "No tasks found" saat tidak ada task cocok
- [x] Tab status menyembunyikan kolom lain di papan
- [x] Filter baru `due_date` dan `created_date` menyaring papan
- [x] Card memuat `data-valid-targets` + menu **Change Status** bila user punya transisi valid; card jadi statis (`is-static`, `draggable=false`) bila tidak
- [x] Hint "Drop a valid task here" hanya untuk kolom kosong yang bisa di-drop (`data-can-drop`)
- [x] Endpoint `GET /tasks/board/column/{status}`: kartu dirender, `has_more`/`total`/`page` benar (16 task → hal. 2 halaman), scope per user, status invalid → 404
- [x] **UAT-17**: `PATCH /tasks/{id}/status` start `WAITING → ON_PROCESS` + activity `start` (label "via kanban drag")
- [x] **UAT-18**: submit `ON_PROCESS → ON_CHECK` + notifikasi `TASK_SUBMITTED` ke checker
- [x] **UAT-19**: approve `ON_CHECK → DONE` + `completed_at` + notifikasi `TASK_APPROVED`
- [x] **UAT-20**: revision tanpa alasan → **422 `errors.comment`**; dengan alasan → komentar tersimpan + notifikasi `TASK_REVISION`
- [x] **UAT-21**: transisi invalid → **422** `message/current_status/requested_status` (termasuk `WAITING→DONE` dan `ON_PROCESS→DONE` yang ditolak)
- [x] **UAT-22**: aktor tak berwenang → **403** (assignee approve/revision, checker submit, non-admin reopen)
- [x] **UAT-23**: `source` (`kanban_drag`) hanya metadata audit — tidak pernah dipakai otorisasi
- [x] **UAT-24**: drop ke kolom sama → 200 `changed:false` tanpa activity; request non-JSON fallback redirect; rangkaian penuh mencatat activity + notifikasi yang benar

### A11. Lainnya

- [x] `ExampleTest` (2): root `/` merespons redirect; unit test dasar

---

## B. End-to-End Live (HTTP ke server berjalan) — 81 check, SEMUA LULUS

### B1. Smoke halaman per peran

- [x] Admin: 8 halaman OK (dashboard, tasks, create, notifications, profile, admin/users, admin/users/create, admin/roles) — tanpa error marker
- [x] Manager: 5 halaman OK (termasuk `/tasks/create`)
- [x] Staff: 4 halaman OK
- [x] Checker: 4 halaman OK
- [x] 403 ramah (berisi "Access denied"): staff & checker di `/tasks/create`, `/admin/users`, `/admin/users/create`, `/admin/roles`

### B2. Isolasi data (scoping)

- [x] Staff melihat task miliknya di list
- [x] Staff TIDAK melihat task tak terkait ("E2E Secret Task")
- [x] Detail task tak terkait → 403 untuk staff

### B3. Workflow live (skenario penuh)

- [x] WAITING: tombol Start tampil untuk assignee; badge WAITING tampil
- [x] UAT-04: Start → badge ON PROCESS
- [x] Tombol Submit for Check tampil di ON PROCESS
- [x] UAT-05: Submit → badge ON CHECK
- [x] Checker melihat tombol Approve + Request Revision di ON CHECK
- [x] UAT-16: Staff coba approve → 403
- [x] UAT-07: Revision tanpa alasan → ditolak, status tetap ON CHECK
- [x] UAT-07: Revision dengan alasan → badge ON PROCESS + alasan tampil sebagai komentar
- [x] Submit ulang → ON CHECK; Approve → badge DONE
- [x] Admin melihat tombol Reopen di DONE; Reopen → badge ON PROCESS
- [x] UAT-08: Reopen kedua (status bukan DONE) → ditolak, status tidak berubah
- [x] Verifikasi DB: `final_status=ON_PROCESS`, activity `create,start,submit,request-revision,submit,approve,reopen,upload,comment` (9 entri), komentar revision & staff tersimpan (2), notifikasi `TASK_SUBMITTED`/`TASK_APPROVED`/`TASK_REOPENED` terkirim, semua notifikasi staff sudah dibaca

### B4. Dokumen (live)

- [x] UAT-09: Upload PDF valid → 302 sukses
- [x] UAT-10: Upload `.php` → ditolak (DB lampiran task utama tetap 1)
- [x] UAT-11: Download oleh assignee → 200
- [x] UAT-11: Download oleh Manager (peran boleh lihat semua task) → 200
- [x] UAT-11: Download oleh staff tak terkait → 403
- [x] UAT-11: Download oleh guest → redirect (302)

### B5. Komentar, notifikasi, filter (live)

- [x] UAT-12: Komentar tampil di halaman detail
- [x] UAT-14: Assignee menerima notifikasi tugas
- [x] Badge unread tampil di layout
- [x] Tandai semua dibaca → filter unread menampilkan empty state
- [x] Filter search `?search=E2E` menampilkan task yang benar
- [x] Filter `?status=ON_PROCESS` → 200 tanpa error
- [x] Filter `?priority=HIGH` → 200 tanpa error

### B6. Keamanan (live)

- [x] POST tanpa token CSRF → **419**
- [x] UAT-02: Login salah → pesan "Invalid credentials"
- [x] Logout → sesi invalid (akses berikutnya redirect)
- [x] Guest membuka `/dashboard` → redirect 302 ke `/login`

### B7. Kanban & status endpoint live (DragDrop UAT-17..24)

- [x] UAT-17: `/tasks` render papan Kanban (4 kolom + tab + toggle), card tampil
- [x] UAT-17: card staff `draggable=true` + target valid `["ON_PROCESS"]`; card checker statis (`draggable=false`)
- [x] UAT-18: `?view=table` → tabel sekunder, tanpa markup papan
- [x] UAT-19: `GET /tasks/board/column/WAITING` → JSON `html`/`has_more` berisi kartu
- [x] UAT-20: `PATCH .../status` start → 200 `changed:true` + badge ON PROCESS
- [x] UAT-21: submit → 200 `ON_CHECK`
- [x] UAT-22: revision tanpa alasan → 422 (status tidak berubah); dengan alasan → 200 + alasan tampil sebagai komentar
- [x] UAT-23: approve → 200 `DONE` + badge DONE
- [x] Tab status `?status=DONE` menyaring kolom papan
- [x] UAT-24: drop kolom sama → 200 `changed:false`; staff reopen → **403** (dengan `source=kanban_drag`); `DONE→WAITING` → **422**; admin reopen → 200; `ON_PROCESS→DONE` → **422**
- [x] Filter `?due_date=` dan `?created_date=` menampilkan task yang benar

---

## C. Bug yang Ditemukan & Diperbaiki Selama Pengujian

| # | Bug | Dampak | Perbaikan |
|---|------|--------|-----------|
| 1 | Urutan migrasi: `users` FK ke `roles` sebelum tabel `roles` dibuat | Migrasi DB gagal dari nol | Migrasi `roles` diganti menjadi `2014_10_12_000000_create_roles_table.php` |
| 2 | `RoleSeeder` tidak idempotent | Gagal duplikat saat dijalankan berulang | `Role::firstOrCreate` |
| 3 | Login tanpa validasi `required` | Pesan error tidak jelas | `AuthController::authenticate` validasi email/password |
| 4 | **`submitForCheck` mentransisi ke `ON_PROCESS` (bukan `ON_CHECK`)** | Submit for check selalu gagal (500) — alur utama PRD rusak | Diperbaiki ke `ON_CHECK` |
| 5 | Blok "alasan revision disimpan sebagai komentar" salah tempat di `submitForCheck` (variabel `$comment` tak terdefinisi) | 500 saat submit; alasan revision tidak masuk komentar | Dipindah ke `requestRevision` (PRD §12) |
| 6 | Reopen diizinkan dari status selain `DONE` | Transisi tidak sah (PRD §5.2) | Guard `status !== 'DONE'` di `TaskController::reopen` |
| 7 | Validasi upload `max:10240KB` memicu `NumberFormatException` | Semua upload file 500 | `max:10240` (satuan KB bawaan Laravel) |
| 8 | Halaman detail task 500 setelah ada lampiran: eager-load & view memakai relasi `user` yang tidak ada di `TaskAttachment` | Task dengan lampiran tidak bisa dibuka | Diganti ke relasi `uploader` (`TaskController:169`, `show.blade.php:146`) |
| 9 | Halaman error 403/404 sebelumnya tidak ada/tidak ramah | User melihat error mentah | Ditambahkan `errors/403.blade.php` & `errors/404.blade.php` (terverifikasi live) |
| 10 | Peta transisi `TaskStatusService` mengizinkan `ON_PROCESS → DONE` (bug dokumentasi PRD §5.2) | Task bisa di-skip tanpa pemeriksaan checker | Dihapus dari `TRANSITIONS`; ditambah `ACTION_FOR` + `allowedTargets()`; diverifikasi `KanbanBoardTest` + E2E UAT-24 |
| 11 | Inline `@php($meta = ...)` di satu view Blade dipasangkan dengan `@endphp` blok lain oleh `storePhpBlocks`, sehingga seluruh bagian papan tidak terkompilasi | `GET /tasks` 500 (syntax error di view hasil compile) | Diganti blok `@php ... @endphp` berpasangan; kanban JS juga dibungkus `@if($view === 'board')` agar tampilan Tabel bersih |

---

## D. Catatan

- Pengujian PHPUnit berjalan pada database terpisah `taskmanagement_test` (refresh otomatis tiap run) — database dev tidak disentuh.
- Skrip E2E live tersedia di `$env:TEMP\opencode\e2e_live.ps1` beserta helper PHP di `$env:TEMP\opencode\tmtest\`.
- Login dibatasi `throttle:5,1`; beri jeda ≥ 60 detik bila skrip E2E dijalankan beruntun.
- Drag & drop hanyalah lapisan interaksi: setiap drop memanggil endpoint bersama `PATCH /tasks/{task}/status` yang menjalankan validasi transisi, policy, activity log, dan notifikasi yang sama dengan tombol aksi (DragDrop PRD §7.3); `source` hanya metadata audit.
- Setelah drop berhasil, papan melakukan **auto refresh** kolom asal & tujuan lewat `GET /tasks/board/column/{status}` (mencakup semua halaman yang sudah dimuat + tombol Load more), sehingga `valid-targets`, badge, urutan kartu, hitungan, dan target drag selalu sinkron dengan server (peran `TaskBoard … refresh`, PRD §21).
