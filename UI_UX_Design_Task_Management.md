# UI/UX DESIGN SYSTEM --- TASK MANAGEMENT SYSTEM

**Versi:** 1.0\
**Basis:** PRD Task Management System --- Execution Ready\
**Tanggal desain:** 1 Oktober 2026\
**Target:** Web Application --- Desktop First, Responsive Mobile\
**Tema:** Elegant Professional / Modern Enterprise\
**Stack target:** Laravel + Blade/Livewire atau REST API + SPA

------------------------------------------------------------------------

## 1. Tujuan Desain

Desain harus membuat sistem task management terasa:

-   **Elegant** --- bersih, premium, tidak ramai.
-   **Professional** --- cocok untuk penggunaan internal
    perusahaan/organisasi.
-   **Clear** --- status pekerjaan dan tindakan berikutnya mudah
    dipahami.
-   **Efficient** --- pengguna dapat melihat dan mengubah task dengan
    sedikit klik.
-   **Trustworthy** --- activity log, checker, attachment, dan status
    terlihat jelas.
-   **Responsive** --- nyaman digunakan pada desktop, tablet, dan
    mobile.

PRD menetapkan workflow utama:

``` text
WAITING → ON PROCESS → ON CHECK → DONE
                         ↓
                     REVISION
                         ↓
                     ON PROCESS
```

Desain harus menjadikan workflow tersebut sebagai elemen visual utama di
Task Detail.

------------------------------------------------------------------------

# 2. Design Direction

## 2.1 Visual Style

Gunakan gaya **modern enterprise dashboard** dengan karakter:

-   background lembut dan terang;
-   card dengan border tipis;
-   radius medium;
-   shadow sangat halus;
-   typography modern;
-   whitespace cukup luas;
-   icon sederhana;
-   status menggunakan badge;
-   action utama menggunakan button yang kuat;
-   tidak menggunakan gradient berlebihan;
-   tidak menggunakan glassmorphism berat;
-   tidak menggunakan dekorasi yang mengganggu data.

### Prinsip

> Data first, action second, decoration last.

------------------------------------------------------------------------

# 3. Color System

Warna tidak boleh menjadi satu-satunya cara membedakan status. Setiap
status selalu menggunakan **label text + icon + warna** sesuai aturan UX
PRD.

## 3.1 Base Colors

  Token              Penggunaan             Nilai
  ------------------ ---------------------- -----------
  `bg-app`           Background aplikasi    `#F7F8FA`
  `bg-surface`       Card / panel           `#FFFFFF`
  `border`           Border default         `#E6E8EC`
  `text-primary`     Heading / text utama   `#171A1F`
  `text-secondary`   Supporting text        `#667085`
  `text-muted`       Metadata               `#98A2B3`
  `brand`            Primary action         `#1F4B99`
  `brand-hover`      Hover primary          `#173A78`
  `focus`            Focus ring             `#84A9E8`

## 3.2 Status Colors

  Status       Visual Direction
  ------------ ----------------------
  WAITING      Amber / warm neutral
  ON PROCESS   Blue
  ON CHECK     Purple
  DONE         Green
  REVISION     Orange / red-orange
  OVERDUE      Red

Contoh:

``` text
[ WAITING ]
[ ON PROCESS ]
[ ON CHECK ]
[ DONE ]
[ REVISION ]
[ OVERDUE ]
```

Badge harus selalu menampilkan nama status.

------------------------------------------------------------------------

# 4. Typography

Gunakan font sans-serif modern.

Rekomendasi:

``` text
Inter
```

Fallback:

``` text
system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif
```

## Typography Scale

  Element           Size   Weight
  --------------- ------ --------
  Page title        28px      700
  Section title     20px      650
  Card title        16px      600
  Body              14px      400
  Small             13px      400
  Caption           12px      400
  Button            14px      600
  Badge             12px      600

Mobile:

``` text
Page title: 22px
Section title: 18px
Body: 14px
Caption: 12px
```

------------------------------------------------------------------------

# 5. Spacing System

Gunakan spacing berbasis 4px.

``` text
4   = xs
8   = sm
12  = md
16  = lg
20  = xl
24  = 2xl
32  = 3xl
40  = 4xl
48  = 5xl
```

Default:

``` text
Card padding: 20–24px
Page padding desktop: 32px
Page padding mobile: 16px
Section gap: 24px
Form field gap: 16px
```

------------------------------------------------------------------------

# 6. Border Radius

Gunakan radius yang elegan dan tidak terlalu bulat.

``` text
Input: 8px
Button: 8px
Badge: 999px
Card: 12px
Modal: 16px
Avatar: 50%
```

------------------------------------------------------------------------

# 7. Shadow

Shadow harus subtle.

``` text
Card:
0 1px 3px rgba(...)

Dropdown:
0 8px 24px rgba(...)

Modal:
0 20px 50px rgba(...)
```

Jangan menggunakan shadow berat pada setiap komponen.

------------------------------------------------------------------------

# 8. Iconography

Gunakan satu icon library secara konsisten.

Rekomendasi:

``` text
Lucide Icons
```

Icon utama:

  Fungsi         Icon
  -------------- -------------------
  Dashboard      LayoutDashboard
  Tasks          ClipboardList
  Create         Plus
  Search         Search
  Filter         SlidersHorizontal
  Notification   Bell
  User           User
  Settings       Settings
  Attachment     Paperclip
  Comment        MessageCircle
  Activity       History
  Check          CheckCircle
  Revision       RotateCcw
  Delete         Trash2
  Edit           Pencil
  Download       Download
  Calendar       Calendar
  Priority       Flag

------------------------------------------------------------------------

# 9. Global Application Layout

Desktop menggunakan:

``` text
┌─────────────────────────────────────────────────────────────┐
│ Sidebar │ Topbar                                  🔔 Avatar │
│         ├───────────────────────────────────────────────────┤
│         │                                                   │
│ Logo    │                 MAIN CONTENT                      │
│         │                                                   │
│ Dashboard│                                                  │
│ Tasks    │                                                  │
│ Create   │                                                  │
│          │                                                  │
│ -------- │                                                  │
│ Settings │                                                  │
│ Profile  │                                                  │
└─────────────────────────────────────────────────────────────┘
```

## 9.1 Sidebar

Lebar:

``` text
240px
```

Karakter:

-   putih;
-   border-right;
-   logo di atas;
-   navigation compact;
-   active item menggunakan background lembut;
-   icon + label;
-   section divider.

Navigation:

``` text
Dashboard

WORK
Tasks
Create Task

SYSTEM
Notifications

SETTINGS
Profile
Users
Roles & Permissions
```

`Users` dan `Roles & Permissions` hanya muncul sesuai permission.

------------------------------------------------------------------------

# 10. Topbar

Topbar:

``` text
┌─────────────────────────────────────────────────────────────┐
│ Page Context                              Search 🔍 Bell 👤 │
└─────────────────────────────────────────────────────────────┘
```

Isi:

-   breadcrumb / page title;
-   global search;
-   notification icon;
-   user avatar;
-   user menu.

User menu:

``` text
Profile
Account Settings
Logout
```

------------------------------------------------------------------------

# 11. Mobile Navigation

Pada mobile sidebar berubah menjadi:

``` text
┌─────────────────────────────────────┐
│ Logo / Page Title             ☰    │
├─────────────────────────────────────┤
│                                     │
│           CONTENT                   │
│                                     │
├─────────────────────────────────────┤
│ Home │ Tasks │ + │ Bell │ Profile  │
└─────────────────────────────────────┘
```

Bottom navigation maksimal 5 item.

Primary Create Task dapat menggunakan tombol `+` di tengah.

------------------------------------------------------------------------

# 12. Dashboard

Dashboard harus role-based sesuai PRD.

## 12.1 Header

``` text
Dashboard
Good morning, [User Name]

[ Search tasks... ]       [ + Create Task ]
```

Mobile:

``` text
Dashboard
Good morning, [User]

[ Search... ]

[ + Create Task ]
```

------------------------------------------------------------------------

# 13. Dashboard Cards

Desktop:

``` text
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ WAITING      │ │ ON PROCESS   │ │ ON CHECK     │ │ DONE         │
│ 12           │ │ 8            │ │ 5            │ │ 34           │
│ 4 new        │ │ 2 due soon   │ │ 3 review     │ │ +8 this week │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
```

Tambahan:

``` text
┌──────────────────┐ ┌──────────────────┐
│ OVERDUE          │ │ DUE SOON         │
│ 3                │ │ 7                │
└──────────────────┘ └──────────────────┘
```

Card tidak perlu terlalu besar.

------------------------------------------------------------------------

# 14. Dashboard --- Admin / Manager

Content order:

1.  KPI cards
2.  Task status overview
3.  Recent tasks
4.  Tasks by assignee
5.  Recent activities

Layout:

``` text
┌──────────────────────────────────────────────┐
│ Status Overview                              │
│                                              │
│ Waiting   Process   Check   Done             │
└──────────────────────────────────────────────┘

┌────────────────────────────┐ ┌───────────────┐
│ Recent Tasks               │ │ Due Soon      │
│                            │ │               │
│ Task #0001                 │ │ Task #0004    │
│ Task #0002                 │ │ Task #0008    │
└────────────────────────────┘ └───────────────┘

┌──────────────────────────────────────────────┐
│ Recent Activity                              │
└──────────────────────────────────────────────┘
```

------------------------------------------------------------------------

# 15. Dashboard --- Staff

Staff tidak membutuhkan monitoring global.

Tampilkan:

``` text
My Waiting
My On Process
My On Check
My Done
My Overdue
My Due Soon
```

Kemudian:

``` text
Recent Assigned Tasks
```

Prioritas utama:

> Task yang harus dikerjakan sekarang.

------------------------------------------------------------------------

# 16. Dashboard --- Checker

Checker membutuhkan fokus review.

Tampilkan:

``` text
Waiting for Review
Returned for Revision
Recently Approved
```

Hero section:

``` text
Tasks Waiting for Your Review
```

Task ON_CHECK harus mudah ditemukan.

------------------------------------------------------------------------

# 17. Task List

## 17.1 Page Header

``` text
Tasks

Manage and monitor all tasks.

[ + Create Task ]
```

## 17.2 Status Tabs

PRD merekomendasikan status sebagai filter/tab:

``` text
All | Waiting | On Process | On Check | Done
```

Tambahkan counter:

``` text
All (59)
Waiting (12)
On Process (8)
On Check (5)
Done (34)
```

------------------------------------------------------------------------

# 18. Filter Bar

Desktop:

``` text
┌─────────────────────────────────────────────────────────────┐
│ 🔍 Search title / Task ID                                   │
│                                                             │
│ Status ▾  Priority ▾  Assignee ▾  Checker ▾  Due Date ▾   │
└─────────────────────────────────────────────────────────────┘
```

Filter dapat dibuka dalam drawer pada mobile.

------------------------------------------------------------------------

# 19. Task Table

Kolom:

``` text
Task ID
Title
Assignee
Checker
Priority
Status
Due Date
Updated
Action
```

Contoh:

``` text
┌────────┬──────────────────────┬────────┬────────┬──────────┐
│ #0001  │ Monthly Report       │ Andi   │ Budi   │ HIGH     │
│        │ Financial Report     │        │        │          │
├────────┼──────────────────────┼────────┼────────┼──────────┤
│ #0002  │ Update Website       │ Rina   │ Budi   │ MEDIUM   │
└────────┴──────────────────────┴────────┴────────┴──────────┘
```

Action:

``` text
View
Edit
Delete
```

Delete menggunakan confirmation dialog.

------------------------------------------------------------------------

# 20. Mobile Task List

Jangan memaksakan desktop table di mobile.

Gunakan card:

``` text
┌─────────────────────────────────┐
│ #0001                    HIGH   │
│                                 │
│ Monthly Financial Report        │
│                                 │
│ 👤 Andi       ✓ Budi            │
│                                 │
│ [ ON PROCESS ]                  │
│                                 │
│ Due 02 Oct 2026                 │
│                                 │
│                         →       │
└─────────────────────────────────┘
```

Card dapat ditekan untuk membuka detail.

------------------------------------------------------------------------

# 21. Create Task

Gunakan form berbasis section.

``` text
Create Task

Task Information
─────────────────────────────────

Title *
[____________________________]

Description *
[                            ]
[                            ]

Assignment
─────────────────────────────────

Assignee *
[ Select assignee ▾ ]

Checker
[ Select checker ▾ ]

Task Settings
─────────────────────────────────

Priority *
[ High ▾ ]

Due Date
[ Calendar ]

Attachment
[ Drag & drop files ]

                    [Cancel] [Create Task]
```

------------------------------------------------------------------------

# 22. Create Task --- UX Rules

-   Required field menggunakan `*`.
-   Validation muncul dekat field.
-   Input tidak hilang ketika validation gagal.
-   Assignee harus aktif.
-   Checker hanya menampilkan user yang relevan.
-   Attachment menampilkan ukuran dan progress.
-   Submit button menampilkan loading state.
-   Setelah sukses tampilkan success notification.
-   Task dibuat dengan status `WAITING`.

------------------------------------------------------------------------

# 23. Task Detail --- Hero Section

Task Detail merupakan halaman paling penting.

Desktop:

``` text
← Back to Tasks

TASK #0001

Create Monthly Financial Report

[ HIGH ] [ ON PROCESS ]

Assignee: Andi
Checker: Budi
Due: 02 Oct 2026

                         [ Upload ]
                         [ Submit for Check ]
```

------------------------------------------------------------------------

# 24. Workflow Progress

Workflow harus selalu terlihat.

``` text
● WAITING
     │
     ✓
● ON PROCESS
     │
     ↓
● ON CHECK
     │
     ↓
● DONE
```

Pada desktop gunakan horizontal stepper:

``` text
[✓ WAITING] ─── [● ON PROCESS] ─── [ ON CHECK ] ─── [ DONE ]
```

Revision:

``` text
ON CHECK
   ↓
REVISION
   ↓
ON PROCESS
```

Revision bukan status utama baru jika implementasi menggunakan status
`ON_PROCESS` + revision activity/comment. Jika implementasi memakai
state terpisah, visual harus tetap menjelaskan bahwa task dikembalikan
ke proses.

------------------------------------------------------------------------

# 25. Task Detail --- Information Layout

Desktop:

``` text
┌──────────────────────────────────────┐
│ Description                          │
│                                      │
│ Full task description...             │
└──────────────────────────────────────┘

┌───────────────────────────┐
│ Task Information          │
│ Creator      Admin        │
│ Assignee     Andi         │
│ Checker      Budi         │
│ Priority     HIGH         │
│ Due Date     02 Oct       │
│ Created      30 Sep       │
└───────────────────────────┘
```

Gunakan 2-column layout:

``` text
Main content: 70%
Sidebar: 30%
```

Mobile:

``` text
Description
↓
Task Information
↓
Attachments
↓
Comments
↓
Activity
```

------------------------------------------------------------------------

# 26. Contextual Action System

Action button harus mengikuti status.

## WAITING

``` text
[ Start Task ]
```

Jika user berwenang:

``` text
[ Edit ]
[ Start Task ]
```

## ON PROCESS

``` text
[ Upload ]
[ Submit for Check ]
```

## ON CHECK

Checker:

``` text
[ Approve ]
[ Request Revision ]
```

Assignee:

``` text
Waiting for review
```

## DONE

Manager/Admin:

``` text
[ Reopen ]
```

Staff:

``` text
Task completed
```

------------------------------------------------------------------------

# 27. Primary / Secondary / Destructive Actions

Primary:

``` text
Create Task
Start Task
Submit for Check
Approve
```

Secondary:

``` text
Edit
Upload
Download
Cancel
```

Destructive:

``` text
Delete
```

Revision:

``` text
Request Revision
```

Destructive action wajib confirmation.

------------------------------------------------------------------------

# 28. Attachment Section

``` text
Attachments                         [ + Upload ]

┌─────────────────────────────────────────────┐
│ 📄 Monthly_Report.pdf                       │
│ PDF · 2.4 MB · uploaded by Andi             │
│                                             │
│                              [Download]      │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ 📊 Financial_Data.xlsx                      │
│ XLSX · 1.2 MB · uploaded by Andi            │
│                                             │
│                              [Download]      │
└─────────────────────────────────────────────┘
```

Upload area:

``` text
┌─────────────────────────────────────────────┐
│                                             │
│       📎 Drop files here                    │
│       or click to browse                    │
│                                             │
│       Maximum 10 MB / file                  │
│                                             │
└─────────────────────────────────────────────┘
```

------------------------------------------------------------------------

# 29. Upload Progress

Saat upload:

``` text
Monthly_Report.pdf

Uploading...

██████████████████░░ 82%

8.2 MB / 10 MB

[Cancel]
```

Error:

``` text
Upload failed
File exceeds maximum size.

[Try Again]
```

------------------------------------------------------------------------

# 30. Comments

Comment section:

``` text
Comments

┌───────────────────────────────────────────┐
│ 👤 Budi                                   │
│ Please revise page 3.                     │
│ 30 Sep 2026 · 19:10                      │
└───────────────────────────────────────────┘

┌───────────────────────────────────────────┐
│ Write a comment...                        │
│                                           │
│                               [Comment]    │
└───────────────────────────────────────────┘
```

Untuk Request Revision:

``` text
Request Revision

Reason *
[ Please revise page 3 and update totals. ]

[Cancel] [Request Revision]
```

Reason wajib.

------------------------------------------------------------------------

# 31. Activity Timeline

Gunakan vertical timeline.

``` text
Activity

● 19:10
│ Budi requested revision
│ "Please revise page 3."
│
● 19:00
│ Andi submitted task for checking
│
● 18:45
│ Andi uploaded Monthly_Report.pdf
│
● 18:30
│ Andi changed status
│ WAITING → ON PROCESS
```

Activity bersifat read-only.

------------------------------------------------------------------------

# 32. Notifications

Notification dropdown:

``` text
Notifications

● Andi assigned you a task
  Monthly Financial Report
  5 min ago

● Task requires your review
  Website Update
  20 min ago

○ Task approved
  Data Report
  1 hour ago

[View all notifications]
```

Unread menggunakan dot/badge.

------------------------------------------------------------------------

# 33. Notification Page

Layout:

``` text
Notifications                         [Mark all as read]

TODAY

● Task assigned
  Monthly Financial Report
  5 minutes ago

● Review requested
  Website Update
  20 minutes ago

EARLIER

○ Task approved
  Data Report
  Yesterday
```

Filter:

``` text
All | Unread | Task | Review
```

------------------------------------------------------------------------

# 34. User Management

Admin page:

``` text
Users

[ Search users... ] [ Status ▾ ] [ Role ▾ ] [ + Create User ]

┌───────────────────────────────────────────────────────────┐
│ User      Email           Role       Status      Action   │
├───────────────────────────────────────────────────────────┤
│ Andi      andi@...        STAFF      Active      ⋮        │
│ Budi      budi@...        CHECKER    Active      ⋮        │
└───────────────────────────────────────────────────────────┘
```

Actions:

``` text
View
Edit
Activate / Deactivate
Reset Password
```

------------------------------------------------------------------------

# 35. Role & Permission

Admin-only.

Layout:

``` text
Roles & Permissions

┌────────────────────┬────────────────────────────────────┐
│ Roles              │ Permissions                        │
│                    │                                    │
│ ADMIN              │ Task                               │
│ MANAGER            │ ☑ Create                           │
│ STAFF              │ ☑ View                             │
│ CHECKER            │ ☑ Edit                             │
│                    │ ☐ Delete                           │
│                    │                                    │
│                    │ Workflow                           │
│                    │ ☑ Start                            │
│                    │ ☑ Submit                           │
│                    │ ☑ Approve                          │
│                    │ ☑ Revision                         │
└────────────────────┴────────────────────────────────────┘
```

Permission backend tetap menjadi sumber kebenaran. UI hanya
merepresentasikan permission yang sudah diberikan.

------------------------------------------------------------------------

# 36. Profile

``` text
Profile

┌──────────────────────────────────────┐
│             Avatar                   │
│          Mohammad User               │
│             STAFF                    │
└──────────────────────────────────────┘

Personal Information
Name
Email
Role
Status

[Edit Profile]
```

Password/security dapat ditempatkan pada section terpisah.

------------------------------------------------------------------------

# 37. Modal Design

Modal:

``` text
┌─────────────────────────────────────┐
│ Request Revision                 ×  │
├─────────────────────────────────────┤
│                                     │
│ Reason *                            │
│                                     │
│ [                                 ] │
│ [                                 ] │
│                                     │
├─────────────────────────────────────┤
│                    [Cancel] [Send] │
└─────────────────────────────────────┘
```

Rules:

-   width desktop sekitar 480--560px;
-   mobile hampir full width;
-   overlay gelap transparan;
-   focus trap;
-   Escape untuk close jika aman;
-   destructive modal tidak boleh tertutup accidental jika data sudah
    diketik tanpa konfirmasi.

------------------------------------------------------------------------

# 38. Confirmation Dialog

Untuk Delete:

``` text
Delete Task?

Are you sure you want to delete
"Monthly Financial Report"?

This action cannot be undone.

[Cancel] [Delete Task]
```

Jangan menggunakan confirmation untuk setiap aksi kecil.

------------------------------------------------------------------------

# 39. Empty States

## No Tasks

``` text
No tasks found

There are no tasks matching your current filters.

[Clear Filters]
```

## No Assigned Tasks

``` text
You're all caught up

You currently have no assigned tasks.
```

## No Notifications

``` text
You're all caught up

No new notifications.
```

Empty state harus menjelaskan kondisi dan langkah berikutnya.

------------------------------------------------------------------------

# 40. Loading States

Gunakan skeleton, bukan spinner besar untuk page utama.

Task list:

``` text
████████████████
██████████
████████████████████

████████████████
██████████
████████████████████
```

Button:

``` text
[ ⟳ Creating... ]
```

------------------------------------------------------------------------

# 41. Error States

Error harus actionable.

Contoh:

``` text
Unable to load tasks

We couldn't retrieve your tasks.
Please check your connection and try again.

[Try Again]
```

Validation:

``` text
Title
[ Monthly Report              ]
Title must be at least 3 characters.
```

Permission:

``` text
Access denied

You don't have permission to perform this action.
```

------------------------------------------------------------------------

# 42. Overdue UI

Overdue harus mudah terlihat tetapi tidak membuat seluruh interface
terlihat merah.

Contoh:

``` text
[ OVERDUE ]

Due 28 Sep 2026
3 days overdue
```

Gunakan red hanya pada bagian yang membutuhkan perhatian.

------------------------------------------------------------------------

# 43. Due Soon UI

``` text
[ DUE SOON ]

Due tomorrow
```

Tampilkan pada task card dan dashboard.

------------------------------------------------------------------------

# 44. Priority UI

Priority:

``` text
LOW
MEDIUM
HIGH
```

Gunakan icon + text.

Contoh:

``` text
⚑ LOW
⚑ MEDIUM
⚑ HIGH
```

Jangan hanya mengandalkan warna.

------------------------------------------------------------------------

# 45. Search UX

Global search dapat mencari:

``` text
Task title
Task ID
Assignee
Checker
```

Search result:

``` text
Search results for "financial"

Tasks
#0001 Monthly Financial Report
#0012 Financial Data Review
```

Debounce search sekitar 250--400ms untuk live search.

------------------------------------------------------------------------

# 46. Responsive Breakpoints

Rekomendasi:

``` text
Mobile:  < 640px
Tablet:  640–1023px
Desktop: >= 1024px
Large:   >= 1280px
```

Desktop:

-   sidebar;
-   table;
-   two-column detail.

Tablet:

-   compact sidebar;
-   table dapat horizontal scroll atau berubah menjadi card.

Mobile:

-   bottom navigation;
-   card list;
-   stacked forms;
-   filter drawer;
-   sticky contextual action.

------------------------------------------------------------------------

# 47. Mobile Task Detail

Mobile layout:

``` text
← Tasks

#0001
Monthly Financial Report

[ ON PROCESS ]

Assignee
Andi

Checker
Budi

Due
02 Oct 2026

────────────────────

Workflow

✓ Waiting
✓ On Process
○ On Check
○ Done

────────────────────

Description

...

────────────────────

Attachments

...

────────────────────

Comments

...

────────────────────

Activity

...
```

Contextual action dapat dibuat sticky di bawah:

``` text
┌─────────────────────────────────────┐
│ [ Upload ] [ Submit for Check ]    │
└─────────────────────────────────────┘
```

Pastikan tidak menutup konten penting.

------------------------------------------------------------------------

# 48. Accessibility

Minimal:

-   semantic HTML;
-   keyboard navigation;
-   visible focus state;
-   sufficient contrast;
-   label form jelas;
-   icon button memiliki `aria-label`;
-   modal memiliki accessible title;
-   status tidak hanya dibedakan dengan warna;
-   error dikaitkan dengan input;
-   table memiliki header yang benar.

------------------------------------------------------------------------

# 49. Interaction Rules

## Hover

Desktop:

-   button sedikit berubah;
-   table row memiliki subtle background;
-   card dapat memiliki border emphasis.

## Focus

``` text
2px focus ring
```

## Active

Button menunjukkan state active secara jelas.

## Disabled

``` text
opacity: 0.5–0.6
cursor: not-allowed
```

Jangan menyembunyikan alasan mengapa action disabled.

------------------------------------------------------------------------

# 50. Toast Notification

Success:

``` text
✓ Task created successfully
```

Info:

``` text
Task submitted for review
```

Warning:

``` text
This task is due tomorrow
```

Error:

``` text
Unable to upload file
```

Toast:

-   muncul di kanan atas desktop;
-   bawah atas navigation pada mobile;
-   auto dismiss;
-   error tidak auto dismiss terlalu cepat.

------------------------------------------------------------------------

# 51. Task Status Transition UI

Saat user menekan action status:

``` text
Current Status
ON PROCESS

Action
Submit for Check

Next Status
ON CHECK

[Cancel] [Submit for Check]
```

Setelah sukses:

``` text
✓ Task submitted for checking
```

Activity otomatis dibuat oleh backend.

------------------------------------------------------------------------

# 52. Revision UX

Saat checker meminta revision:

``` text
Request Revision

Task:
Monthly Financial Report

Reason *
[ Please revise page 3. ]

This task will return to ON PROCESS.

[Cancel] [Request Revision]
```

Setelah berhasil:

``` text
ON CHECK → ON PROCESS

Revision requested by Budi
```

Reason masuk ke comment dan activity.

------------------------------------------------------------------------

# 53. Approve UX

``` text
Approve Task?

You're about to mark this task as DONE.

Task:
Monthly Financial Report

[Cancel] [Approve Task]
```

Setelah approve:

``` text
✓ Task approved
Task is now DONE.
```

`completed_at` diisi oleh backend.

------------------------------------------------------------------------

# 54. Reopen UX

Untuk Manager/Admin:

``` text
Reopen Task?

This task is currently DONE.

Reopening will move it back to ON PROCESS.

[Cancel] [Reopen Task]
```

Activity:

``` text
Admin reopened task
DONE → ON PROCESS
```

------------------------------------------------------------------------

# 55. Navigation Information Architecture

Gunakan struktur:

``` text
TASK MANAGEMENT

Dashboard

WORK
Tasks
Create Task

COMMUNICATION
Notifications

ACCOUNT
Profile

ADMIN
Users
Roles & Permissions
```

Status tidak dibuat sebagai menu terpisah karena PRD merekomendasikan
tab/filter.

------------------------------------------------------------------------

# 56. Design Component Inventory

Komponen utama:

``` text
AppShell
Sidebar
MobileBottomNav
Topbar
Breadcrumb
PageHeader

DashboardCard
StatusOverview
TaskTable
TaskCard
TaskFilter
StatusTabs
PriorityBadge
StatusBadge

TaskForm
TaskInformation
TaskWorkflow
TaskActions

AttachmentList
FileUploader
CommentList
CommentForm
ActivityTimeline

NotificationDropdown
NotificationList

UserTable
RolePermissionMatrix

Modal
ConfirmDialog
Toast
Dropdown
Tooltip
Pagination

EmptyState
LoadingSkeleton
ErrorState
```

------------------------------------------------------------------------

# 57. Component State Matrix

Setiap komponen interaktif minimal memiliki:

``` text
Default
Hover
Focus
Active
Disabled
Loading
Error
Empty
Success
```

Contoh button:

``` text
Default:  Create Task
Hover:    Create Task
Focus:    Create Task + focus ring
Loading:  ⟳ Creating...
Disabled: Create Task
Success:  ✓ Created
```

------------------------------------------------------------------------

# 58. Task Card Design

``` text
┌─────────────────────────────────────────┐
│ #0001                         [ HIGH ]  │
│                                         │
│ Create Monthly Financial Report         │
│ Prepare monthly financial report...      │
│                                         │
│ 👤 Andi       🔎 Budi                   │
│                                         │
│ [ ON PROCESS ]                          │
│                                         │
│ 📅 Due 02 Oct 2026             →       │
└─────────────────────────────────────────┘
```

Card tidak boleh terlalu penuh.

------------------------------------------------------------------------

# 59. Task Detail Desktop Composition

``` text
┌─────────────────────────────────────────────────────────────┐
│ ← Tasks                                                     │
│                                                             │
│ #0001                                                       │
│ Create Monthly Financial Report                             │
│ [ON PROCESS] [HIGH]                                         │
│                                                             │
│ [Upload] [Submit for Check]                                │
├───────────────────────────────────────┬─────────────────────┤
│ Workflow                              │ Task Information    │
│                                       │                     │
│ WAITING → ON PROCESS → ON CHECK       │ Creator             │
│                                       │ Assignee            │
│                                       │ Checker             │
│ Description                           │ Priority            │
│                                       │ Due Date            │
│ ...                                   │                     │
│                                       │                     │
│ Attachments                           │                     │
│ ...                                   │                     │
│                                       │                     │
│ Comments                              │                     │
│ ...                                   │                     │
│                                       │                     │
│ Activity                              │                     │
│ ...                                   │                     │
└───────────────────────────────────────┴─────────────────────┘
```

------------------------------------------------------------------------

# 60. Dashboard Visual Hierarchy

Urutan perhatian pengguna:

``` text
1. Apa yang harus saya kerjakan?
2. Apa yang membutuhkan perhatian?
3. Task mana yang overdue?
4. Apa perubahan terbaru?
5. Statistik tambahan
```

Untuk Staff:

``` text
My Tasks > Due Soon > Overdue > Activity
```

Untuk Checker:

``` text
Waiting for Review > Revision > Recently Approved
```

Untuk Manager/Admin:

``` text
Overall Status > Overdue > Assignee > Activity
```

------------------------------------------------------------------------

# 61. Design Rules Berdasarkan Role

## ADMIN

UI:

-   global dashboard;
-   all tasks;
-   user management;
-   role management;
-   full actions sesuai permission.

## MANAGER

UI:

-   monitoring;
-   create task;
-   assignment;
-   checking;
-   reopen;
-   dashboard organisasi.

## STAFF

UI:

-   personal tasks;
-   start;
-   upload;
-   submit;
-   comment;
-   activity task terkait.

## CHECKER

UI:

-   review queue;
-   task detail;
-   approve;
-   request revision;
-   comment.

------------------------------------------------------------------------

# 62. Security UX

UI tidak boleh memberi kesan user dapat melakukan action yang sebenarnya
tidak diizinkan.

Namun authorization tetap harus dilakukan backend sesuai PRD.

Contoh:

Jika STAFF membuka task yang bukan miliknya:

``` text
Access Restricted

You don't have permission to access this task.
```

File:

``` text
Download
```

harus hanya tersedia jika user memiliki permission.

------------------------------------------------------------------------

# 63. Form UX

Form:

-   label di atas input;
-   helper text di bawah input;
-   validation error dekat field;
-   required indicator;
-   sensible default;
-   autocomplete untuk assignee/checker;
-   date picker untuk due date;
-   unsaved-change warning untuk form panjang.

------------------------------------------------------------------------

# 64. Desktop Grid

Container:

``` text
max-width: 1440px
margin: auto
padding: 32px
```

Dashboard grid:

``` text
4 columns desktop
2 columns tablet
1 column mobile
```

Task detail:

``` text
8 columns main
4 columns sidebar
```

------------------------------------------------------------------------

# 65. Mobile Grid

Mobile:

``` text
1 column
16px page padding
16px card gap
```

Form:

``` text
1 field per row
```

Buttons:

``` text
Primary action full width jika berada di modal/form mobile.
```

------------------------------------------------------------------------

# 66. Design Tokens

Contoh CSS variables:

``` css
:root {
    --color-bg: #F7F8FA;
    --color-surface: #FFFFFF;
    --color-border: #E6E8EC;

    --color-text: #171A1F;
    --color-text-secondary: #667085;
    --color-text-muted: #98A2B3;

    --color-brand: #1F4B99;
    --color-brand-hover: #173A78;

    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;

    --space-1: 4px;
    --space-2: 8px;
    --space-3: 12px;
    --space-4: 16px;
    --space-5: 20px;
    --space-6: 24px;
    --space-8: 32px;
}
```

Status colors sebaiknya didefinisikan sebagai token terpisah dan
digunakan konsisten.

------------------------------------------------------------------------

# 67. Suggested Laravel Blade Structure

``` text
resources/views/
├── layouts/
│   ├── app.blade.php
│   └── guest.blade.php
│
├── components/
│   ├── sidebar.blade.php
│   ├── mobile-nav.blade.php
│   ├── topbar.blade.php
│   ├── status-badge.blade.php
│   ├── priority-badge.blade.php
│   ├── task-card.blade.php
│   ├── task-table.blade.php
│   ├── task-filter.blade.php
│   ├── task-timeline.blade.php
│   ├── attachment-list.blade.php
│   ├── comment-list.blade.php
│   ├── notification-dropdown.blade.php
│   └── dashboard-card.blade.php
│
├── dashboard/
│   └── index.blade.php
│
├── tasks/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
│
├── notifications/
│   └── index.blade.php
│
├── profile/
│   └── index.blade.php
│
└── admin/
    ├── users/
    └── roles/
```

------------------------------------------------------------------------

# 68. Recommended Frontend Interaction

Jika menggunakan Blade/Livewire:

``` text
Blade
  ↓
Livewire Components
  ↓
Reusable UI Components
```

Gunakan Alpine.js untuk:

-   dropdown;
-   modal;
-   confirmation;
-   tabs;
-   mobile navigation;
-   file upload interaction;
-   lightweight UI state.

Jika menggunakan SPA:

``` text
API
 ↓
React/Vue/other SPA
 ↓
Design System
```

Visual specification tetap sama.

------------------------------------------------------------------------

# 69. UI Acceptance Checklist

## Global

-   [ ] Elegant professional visual.
-   [ ] Responsive desktop/tablet/mobile.
-   [ ] Consistent typography.
-   [ ] Consistent spacing.
-   [ ] Consistent iconography.
-   [ ] Status tidak hanya menggunakan warna.
-   [ ] Loading state tersedia.
-   [ ] Empty state tersedia.
-   [ ] Error state tersedia.

## Dashboard

-   [ ] Role-based content.
-   [ ] Status counters.
-   [ ] Overdue.
-   [ ] Due soon.
-   [ ] Recent tasks.
-   [ ] Recent activity.

## Task List

-   [ ] Search.
-   [ ] Status filter.
-   [ ] Priority filter.
-   [ ] Assignee filter.
-   [ ] Checker filter.
-   [ ] Due date filter.
-   [ ] Pagination.
-   [ ] Mobile cards.

## Task Detail

-   [ ] Status selalu terlihat.
-   [ ] Workflow visible.
-   [ ] Contextual action.
-   [ ] Description.
-   [ ] Task information.
-   [ ] Attachments.
-   [ ] Comments.
-   [ ] Activity timeline.

## Workflow

-   [ ] WAITING.
-   [ ] ON PROCESS.
-   [ ] ON CHECK.
-   [ ] DONE.
-   [ ] Revision flow.
-   [ ] Confirmation untuk important actions.

## Admin

-   [ ] User management.
-   [ ] Role management.
-   [ ] Permission UI.
-   [ ] Search/filter.
-   [ ] Activate/deactivate.

------------------------------------------------------------------------

# 70. Final Visual Concept

Produk harus terasa seperti:

``` text
Modern Enterprise
        +
Clean Productivity App
        +
Professional Internal System
```

Bukan:

``` text
Gaming Dashboard
Heavy Glassmorphism
Over-decorated SaaS
Colorful Consumer App
```

Prioritas visual:

``` text
CLARITY
  ↓
WORKFLOW
  ↓
ACTION
  ↓
DATA
  ↓
DETAIL
```

------------------------------------------------------------------------

# 71. Final Screen Map

``` text
LOGIN
  │
  ▼
DASHBOARD
  │
  ├───────────────┐
  ▼               ▼
TASK LIST      NOTIFICATIONS
  │
  ├── All
  ├── Waiting
  ├── On Process
  ├── On Check
  └── Done
  │
  ▼
TASK DETAIL
  │
  ├── Workflow
  ├── Information
  ├── Attachments
  ├── Comments
  └── Activity
  │
  ▼
STATUS ACTION
  │
  ├── Start
  ├── Submit for Check
  ├── Approve
  ├── Request Revision
  └── Reopen
```

Admin:

``` text
SETTINGS
  ├── Profile
  ├── Users
  └── Roles & Permissions
```

------------------------------------------------------------------------

# 72. Design-to-Development Handoff

Developer harus menggunakan dokumen ini bersama PRD sebagai acuan.

PRD mengatur:

-   business rules;
-   role;
-   permission;
-   workflow;
-   database;
-   API;
-   validation;
-   security;
-   acceptance criteria.

Dokumen desain ini mengatur:

-   visual language;
-   page composition;
-   component hierarchy;
-   responsive behavior;
-   interaction states;
-   dashboard presentation;
-   task workflow presentation;
-   form UX;
-   empty/loading/error states.

Keduanya harus diterapkan bersama.

------------------------------------------------------------------------

# 73. Important Implementation Note

Jangan membuat UI hanya berdasarkan screenshot atau visual.

Implementasi harus tetap mengikuti business rule PRD, terutama:

``` text
WAITING
  ↓
ON_PROCESS
  ↓
ON_CHECK
  ├── APPROVE → DONE
  └── REVISION → ON_PROCESS
```

Authorization harus tetap dilakukan di backend.

UI hanya menampilkan action yang relevan sebagai pengalaman pengguna,
bukan sebagai mekanisme keamanan.

------------------------------------------------------------------------

# 74. Recommended First UI Build Order

``` text
1. App Shell
2. Sidebar + Topbar
3. Mobile Navigation
4. Design Tokens
5. Dashboard
6. Task List
7. Task Card
8. Create Task
9. Task Detail
10. Workflow Stepper
11. Attachment UI
12. Comment UI
13. Activity Timeline
14. Notification UI
15. User Management
16. Role & Permission
17. Responsive polish
18. Loading / Empty / Error states
19. Accessibility review
20. Final UI QA
```

------------------------------------------------------------------------

# 75. Final Design Principle

> **Satu layar harus menjawab tiga pertanyaan pengguna:**
>
> 1.  **Apa yang sedang terjadi?**
> 2.  **Apa yang harus saya lakukan?**
> 3.  **Apa yang terjadi setelah saya melakukan action tersebut?**

Untuk Task Management System, jawaban tersebut harus selalu terlihat
melalui:

``` text
STATUS
  +
CONTEXT
  +
ACTION
  +
HISTORY
```

Dengan prinsip tersebut, interface tetap elegant, proper, mudah
dipahami, dan konsisten dengan workflow yang ditetapkan pada PRD.
