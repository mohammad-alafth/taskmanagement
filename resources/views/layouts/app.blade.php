<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Task Management</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg: #F7F8FA;
            --color-surface: #FFFFFF;
            --color-border: #E6E8EC;
            --color-text: #171A1F;
            --color-text-secondary: #667085;
            --color-text-muted: #98A2B3;
            --color-brand: #1F4B99;
            --color-brand-hover: #173A78;
            --color-focus: #84A9E8;

            --color-waiting: #B7791F;
            --color-waiting-bg: #FDF6E3;
            --color-onprocess: #1F4B99;
            --color-onprocess-bg: #E8F0FB;
            --color-oncheck: #6B46C1;
            --color-oncheck-bg: #F1EBFD;
            --color-done: #2F855A;
            --color-done-bg: #E6F5EC;
            --color-overdue: #C53030;
            --color-overdue-bg: #FDECEC;
            --color-revision: #C05621;
            --color-revision-bg: #FDF0E7;

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-pill: 999px;

            --space-1: 4px; --space-2: 8px; --space-3: 12px; --space-4: 16px;
            --space-5: 20px; --space-6: 24px; --space-8: 32px; --space-10: 40px;

            --shadow-card: 0 1px 3px rgba(23, 26, 31, .06);
            --shadow-dropdown: 0 8px 24px rgba(23, 26, 31, .12);
            --shadow-modal: 0 20px 50px rgba(23, 26, 31, .25);

            --sidebar-width: 240px;
            --topbar-height: 64px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--color-bg);
            color: var(--color-text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a { color: var(--color-brand); text-decoration: none; }
        a:hover { color: var(--color-brand-hover); }

        :focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 2px;
            border-radius: 4px;
        }

        h1, h2, h3, h4 { margin: 0; color: var(--color-text); }
        .page-title { font-size: 28px; font-weight: 700; letter-spacing: -.02em; }
        .section-title { font-size: 20px; font-weight: 650; }
        .card-title { font-size: 16px; font-weight: 600; }
        .muted { color: var(--color-text-muted); }
        .text-secondary { color: var(--color-text-secondary); }
        .small { font-size: 13px; }
        .caption { font-size: 12px; color: var(--color-text-muted); }

        /* ---------- App shell ---------- */
        .app { display: flex; min-height: 100vh; }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-width);
            background: var(--color-surface);
            border-right: 1px solid var(--color-border);
            padding: var(--space-5) var(--space-4);
            display: flex;
            flex-direction: column;
            gap: var(--space-5);
            overflow-y: auto;
            z-index: 40;
        }

        .sidebar .brand {
            display: flex; align-items: center; gap: var(--space-2);
            font-size: 16px; font-weight: 700; color: var(--color-text);
        }
        .sidebar .brand .brand-mark {
            width: 32px; height: 32px; border-radius: var(--radius-sm);
            background: var(--color-brand); color: #fff;
            display: grid; place-items: center;
        }

        .nav-section-label {
            font-size: 11px; font-weight: 600; letter-spacing: .08em;
            text-transform: uppercase; color: var(--color-text-muted);
            padding: 0 var(--space-3); margin-bottom: var(--space-2);
        }

        .nav-list { list-style: none; margin: 0 0 var(--space-4); padding: 0; display: flex; flex-direction: column; gap: 2px; }
        .nav-link {
            display: flex; align-items: center; gap: var(--space-3);
            padding: 9px var(--space-3); border-radius: var(--radius-sm);
            color: var(--color-text-secondary); font-weight: 500; font-size: 14px;
        }
        .nav-link svg { width: 18px; height: 18px; }
        .nav-link:hover { background: var(--color-bg); color: var(--color-text); }
        .nav-link.active { background: #E8F0FB; color: var(--color-brand); font-weight: 600; }
        .nav-divider { border-top: 1px solid var(--color-border); margin: var(--space-2) 0; }

        .main { flex: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; min-width: 0; }

        .topbar {
            position: sticky; top: 0; z-index: 30;
            height: var(--topbar-height);
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(6px);
            border-bottom: 1px solid var(--color-border);
            display: flex; align-items: center; gap: var(--space-4);
            padding: 0 var(--space-8);
        }
        .topbar .page-context { font-weight: 600; font-size: 15px; }
        .topbar .spacer { flex: 1; }

        .topbar-search { position: relative; }
        .topbar-search input {
            width: 260px; height: 38px; padding: 0 var(--space-3) 0 36px;
            border: 1px solid var(--color-border); border-radius: var(--radius-sm);
            background: var(--color-bg); font-size: 14px; color: var(--color-text);
        }
        .topbar-search svg { position: absolute; left: 11px; top: 10px; width: 18px; height: 18px; color: var(--color-text-muted); }

        .icon-btn {
            position: relative; width: 38px; height: 38px; border-radius: var(--radius-sm);
            border: 1px solid var(--color-border); background: var(--color-surface);
            display: grid; place-items: center; cursor: pointer; color: var(--color-text-secondary);
        }
        .icon-btn:hover { background: var(--color-bg); color: var(--color-text); }
        .icon-btn svg { width: 18px; height: 18px; }
        .icon-btn .dot-badge {
            position: absolute; top: -6px; right: -6px; min-width: 18px; height: 18px;
            border-radius: var(--radius-pill); background: var(--color-overdue); color: #fff;
            font-size: 11px; font-weight: 600; display: grid; place-items: center; padding: 0 4px;
        }

        .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--color-brand); color: #fff; font-weight: 600; font-size: 14px;
            display: grid; place-items: center;
        }

        .dropdown { position: relative; }
        .dropdown-menu {
            position: absolute; right: 0; top: calc(100% + 8px);
            background: var(--color-surface); border: 1px solid var(--color-border);
            border-radius: var(--radius-md); box-shadow: var(--shadow-dropdown);
            min-width: 200px; padding: var(--space-2); display: none; z-index: 50;
        }
        .dropdown-menu.show { display: block; }
        .dropdown-menu a, .dropdown-menu button {
            display: flex; align-items: center; gap: var(--space-2); width: 100%;
            padding: 9px var(--space-3); border: 0; background: none; cursor: pointer;
            border-radius: var(--radius-sm); color: var(--color-text); font-size: 14px; text-align: left;
        }
        .dropdown-menu a:hover, .dropdown-menu button:hover { background: var(--color-bg); }
        .dropdown-header { padding: var(--space-2) var(--space-3); border-bottom: 1px solid var(--color-border); margin-bottom: var(--space-2); }

        .content { padding: var(--space-8); max-width: 1440px; width: 100%; margin: 0 auto; flex: 1; }

        /* ---------- Components ---------- */
        .card {
            background: var(--color-surface); border: 1px solid var(--color-border);
            border-radius: var(--radius-md); box-shadow: var(--shadow-card); padding: var(--space-6);
        }
        .card + .card { margin-top: var(--space-6); }
        .card-header-row { display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); margin-bottom: var(--space-4); }

        .page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-4); margin-bottom: var(--space-6); flex-wrap: wrap; }
        .page-header .subtitle { color: var(--color-text-secondary); margin-top: 4px; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: var(--space-2);
            height: 40px; padding: 0 var(--space-4); border-radius: var(--radius-sm);
            font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid transparent;
            transition: background .15s ease, border-color .15s ease, color .15s ease;
            background: var(--color-surface); color: var(--color-text); border-color: var(--color-border);
            text-decoration: none;
        }
        .btn svg { width: 16px; height: 16px; }
        .btn:hover { background: var(--color-bg); color: var(--color-text); }
        .btn-primary { background: var(--color-brand); border-color: var(--color-brand); color: #fff; }
        .btn-primary:hover { background: var(--color-brand-hover); border-color: var(--color-brand-hover); color: #fff; }
        .btn-outline { background: transparent; border-color: var(--color-border); color: var(--color-text-secondary); }
        .btn-success { background: var(--color-done); border-color: var(--color-done); color: #fff; }
        .btn-success:hover { background: #276749; color: #fff; }
        .btn-warning { background: var(--color-revision); border-color: var(--color-revision); color: #fff; }
        .btn-warning:hover { background: #9C4221; color: #fff; }
        .btn-danger { background: var(--color-overdue); border-color: var(--color-overdue); color: #fff; }
        .btn-danger:hover { background: #9B2C2C; color: #fff; }
        .btn-sm { height: 32px; padding: 0 var(--space-3); font-size: 13px; }
        .btn-block { width: 100%; }
        .btn[disabled], .btn.is-disabled { opacity: .55; cursor: not-allowed; pointer-events: none; }

        .badge-icon { width: 12px; height: 12px; }

        .status-badge, .priority-badge {
            display: inline-flex; align-items: center; gap: 5px;
            height: 24px; padding: 0 10px; border-radius: var(--radius-pill);
            font-size: 12px; font-weight: 600; letter-spacing: .02em; white-space: nowrap;
        }
        .status-badge .badge-icon, .priority-badge .badge-icon { width: 12px; height: 12px; }
        .badge-waiting { background: var(--color-waiting-bg); color: var(--color-waiting); }
        .badge-on-process { background: var(--color-onprocess-bg); color: var(--color-onprocess); }
        .badge-on-check { background: var(--color-oncheck-bg); color: var(--color-oncheck); }
        .badge-done { background: var(--color-done-bg); color: var(--color-done); }
        .badge-overdue { background: var(--color-overdue-bg); color: var(--color-overdue); }
        .badge-revision { background: var(--color-revision-bg); color: var(--color-revision); }
        .priority-low { background: var(--color-done-bg); color: var(--color-done); }
        .priority-medium { background: var(--color-onprocess-bg); color: var(--color-onprocess); }
        .priority-high { background: var(--color-revision-bg); color: var(--color-revision); }

        .grid { display: grid; gap: var(--space-6); }
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }

        .stat-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: var(--space-5); box-shadow: var(--shadow-card); }
        .stat-card .stat-label { display: flex; align-items: center; gap: var(--space-2); font-size: 13px; font-weight: 600; color: var(--color-text-secondary); }
        .stat-card .stat-value { font-size: 30px; font-weight: 700; margin-top: var(--space-2); letter-spacing: -.02em; }
        .stat-card .stat-meta { font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }
        .stat-accent { width: 8px; height: 8px; border-radius: 50%; }

        .table-responsive { overflow-x: auto; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .05em; color: var(--color-text-muted); padding: var(--space-3);
            border-bottom: 1px solid var(--color-border); white-space: nowrap;
        }
        table.data td { padding: var(--space-3); border-bottom: 1px solid var(--color-border); vertical-align: middle; }
        table.data tbody tr:hover { background: #FBFCFD; }
        table.data tbody tr:last-child td { border-bottom: 0; }

        .tabs { display: flex; gap: var(--space-1); border-bottom: 1px solid var(--color-border); overflow-x: auto; }
        .tab {
            padding: 10px var(--space-4); font-size: 14px; font-weight: 600; color: var(--color-text-secondary);
            border-bottom: 2px solid transparent; white-space: nowrap;
        }
        .tab:hover { color: var(--color-text); }
        .tab.active { color: var(--color-brand); border-bottom-color: var(--color-brand); }
        .tab .count { font-size: 12px; color: var(--color-text-muted); margin-left: 4px; }

        .filter-bar { display: flex; flex-wrap: wrap; gap: var(--space-3); align-items: center; margin-top: var(--space-4); }
        .filter-bar .search-field { position: relative; flex: 1; min-width: 220px; }
        .filter-bar .search-field input { padding-left: 36px; }
        .filter-bar .search-field svg { position: absolute; left: 11px; top: 11px; width: 16px; height: 16px; color: var(--color-text-muted); }

        .form-control, select.form-control, textarea.form-control {
            width: 100%; height: 40px; padding: 0 var(--space-3);
            border: 1px solid var(--color-border); border-radius: var(--radius-sm);
            background: var(--color-surface); color: var(--color-text); font-size: 14px;
            font-family: inherit;
        }
        textarea.form-control { height: auto; padding: 10px var(--space-3); }
        .form-control:focus { border-color: var(--color-focus); outline: none; box-shadow: 0 0 0 3px rgba(132,169,232,.25); }
        .form-control.is-invalid { border-color: var(--color-overdue); }
        .form-group { margin-bottom: var(--space-4); }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--color-text); }
        .form-label .req { color: var(--color-overdue); margin-left: 2px; }
        .form-helper { font-size: 12px; color: var(--color-text-muted); margin-top: 4px; }
        .form-error { font-size: 12px; color: var(--color-overdue); margin-top: 4px; }
        .form-section-title {
            font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
            color: var(--color-text-muted); padding-bottom: var(--space-2);
            border-bottom: 1px solid var(--color-border); margin: var(--space-6) 0 var(--space-4);
        }

        .alert { padding: var(--space-3) var(--space-4); border-radius: var(--radius-sm); font-size: 14px; margin-bottom: var(--space-4); border: 1px solid transparent; }
        .alert-success { background: var(--color-done-bg); color: var(--color-done); border-color: #C6EFD9; }
        .alert-danger { background: var(--color-overdue-bg); color: var(--color-overdue); border-color: #F8D4D4; }
        .alert-info { background: var(--color-onprocess-bg); color: var(--color-onprocess); border-color: #D4E3F7; }

        .empty-state { text-align: center; padding: var(--space-10) var(--space-6); color: var(--color-text-secondary); }
        .empty-state .empty-icon { width: 48px; height: 48px; margin: 0 auto var(--space-4); color: var(--color-text-muted); }
        .empty-state h4 { font-size: 16px; font-weight: 600; margin-bottom: var(--space-2); }
        .empty-state p { margin: 0 0 var(--space-4); color: var(--color-text-muted); }

        .pagination { display: flex; gap: 6px; margin-top: var(--space-5); flex-wrap: wrap; }
        .pagination a, .pagination span {
            min-width: 34px; height: 34px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid var(--color-border); border-radius: var(--radius-sm);
            background: var(--color-surface); color: var(--color-text-secondary); font-size: 13px;
        }
        .pagination .active span { background: var(--color-brand); border-color: var(--color-brand); color: #fff; }
        .pagination a:hover { background: var(--color-bg); color: var(--color-text); }

        /* ---------- Toast ---------- */
        .toast-stack { position: fixed; top: 76px; right: 24px; z-index: 100; display: flex; flex-direction: column; gap: 10px; max-width: 360px; }
        .toast {
            display: flex; align-items: flex-start; gap: var(--space-3);
            background: var(--color-surface); border: 1px solid var(--color-border);
            border-left: 4px solid var(--color-brand); border-radius: var(--radius-sm);
            box-shadow: var(--shadow-dropdown); padding: var(--space-3) var(--space-4); font-size: 14px;
            animation: toast-in .2s ease;
        }
        .toast.success { border-left-color: var(--color-done); }
        .toast.error { border-left-color: var(--color-overdue); }
        .toast .toast-close { margin-left: auto; border: 0; background: none; cursor: pointer; color: var(--color-text-muted); font-size: 16px; line-height: 1; }
        @keyframes toast-in { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }

        /* ---------- Workflow stepper ---------- */
        .workflow { display: flex; align-items: flex-start; gap: 0; margin: var(--space-5) 0; }
        .workflow .step { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; position: relative; text-align: center; }
        .workflow .step::before {
            content: ""; position: absolute; top: 13px; left: -50%; width: 100%; height: 2px; background: var(--color-border); z-index: 0;
        }
        .workflow .step:first-child::before { display: none; }
        .workflow .step.done::before, .workflow .step.current::before { background: var(--color-brand); }
        .step-dot {
            position: relative; z-index: 1; width: 26px; height: 26px; border-radius: 50%;
            background: var(--color-surface); border: 2px solid var(--color-border);
            display: grid; place-items: center; color: var(--color-text-muted); font-size: 12px;
        }
        .step.done .step-dot { background: var(--color-brand); border-color: var(--color-brand); color: #fff; }
        .step.current .step-dot { border-color: var(--color-brand); color: var(--color-brand); box-shadow: 0 0 0 4px rgba(31,75,153,.12); }
        .step-label { font-size: 12px; font-weight: 600; color: var(--color-text-muted); }
        .step.done .step-label, .step.current .step-label { color: var(--color-text); }

        .revision-note { display: flex; align-items: center; gap: var(--space-2); font-size: 13px; color: var(--color-revision); margin-top: var(--space-2); }

        /* ---------- Task detail ---------- */
        .detail-grid { display: grid; grid-template-columns: minmax(0, 7fr) minmax(0, 3fr); gap: var(--space-6); align-items: start; }
        .info-row { display: flex; justify-content: space-between; gap: var(--space-3); padding: 10px 0; border-bottom: 1px solid var(--color-border); font-size: 14px; }
        .info-row:last-child { border-bottom: 0; }
        .info-row .k { color: var(--color-text-secondary); }
        .info-row .v { font-weight: 600; text-align: right; }

        .comment-item { display: flex; gap: var(--space-3); padding: var(--space-4) 0; border-bottom: 1px solid var(--color-border); }
        .comment-item:last-child { border-bottom: 0; }
        .comment-avatar { width: 34px; height: 34px; border-radius: 50%; background: #EDEFF3; color: var(--color-text-secondary); display: grid; place-items: center; font-weight: 600; font-size: 13px; flex-shrink: 0; }

        .attachment-item {
            display: flex; align-items: center; gap: var(--space-3);
            border: 1px solid var(--color-border); border-radius: var(--radius-sm);
            padding: var(--space-3) var(--space-4); margin-bottom: var(--space-3); background: var(--color-surface);
        }
        .attachment-item .file-icon { width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--color-onprocess-bg); color: var(--color-brand); display: grid; place-items: center; }
        .attachment-item .file-meta { flex: 1; min-width: 0; }
        .attachment-item .file-name { font-weight: 600; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .timeline { list-style: none; margin: 0; padding: 0; }
        .timeline li { position: relative; padding: 0 0 var(--space-5) var(--space-6); border-left: 2px solid var(--color-border); }
        .timeline li:last-child { padding-bottom: 0; border-left-color: transparent; }
        .timeline li::before {
            content: ""; position: absolute; left: -6px; top: 3px; width: 10px; height: 10px;
            border-radius: 50%; background: var(--color-brand); border: 2px solid var(--color-surface);
        }
        .timeline .t-time { font-size: 12px; color: var(--color-text-muted); }
        .timeline .t-body { font-size: 14px; margin-top: 2px; }

        /* ---------- Upload ---------- */
        .upload-zone {
            border: 1.5px dashed var(--color-border); border-radius: var(--radius-md);
            padding: var(--space-6); text-align: center; color: var(--color-text-secondary);
            background: var(--color-bg); cursor: pointer;
        }
        .upload-zone:hover { border-color: var(--color-focus); }
        .upload-zone svg { width: 28px; height: 28px; color: var(--color-text-muted); margin-bottom: var(--space-2); }

        /* ---------- Modal ---------- */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(23,26,31,.45);
            display: none; align-items: center; justify-content: center; z-index: 90; padding: var(--space-4);
        }
        .modal-overlay.show { display: flex; }
        .modal {
            background: var(--color-surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-modal);
            width: 100%; max-width: 520px; padding: var(--space-6);
        }
        .modal h3 { font-size: 18px; font-weight: 650; margin-bottom: var(--space-2); }
        .modal-actions { display: flex; justify-content: flex-end; gap: var(--space-3); margin-top: var(--space-5); }

        /* ---------- Mobile nav ---------- */
        .mobile-bottom-nav { display: none; }

        /* ---------- Responsive ---------- */
        @media (max-width: 1023.98px) {
            .grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .detail-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 639.98px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding-bottom: 72px; }
            .content { padding: var(--space-4); }
            .topbar { padding: 0 var(--space-4); gap: var(--space-2); }
            .topbar-search { display: none; }
            .page-title { font-size: 22px; }
            .section-title { font-size: 18px; }
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
            .workflow { flex-direction: column; align-items: stretch; gap: var(--space-3); }
            .workflow .step { flex-direction: row; text-align: left; gap: var(--space-3); align-items: center; }
            .workflow .step::before { display: none; }

            .mobile-bottom-nav {
                display: flex; position: fixed; bottom: 0; left: 0; right: 0; z-index: 60;
                background: var(--color-surface); border-top: 1px solid var(--color-border);
                height: 64px; align-items: center; justify-content: space-around;
            }
            .mobile-bottom-nav a {
                display: flex; flex-direction: column; align-items: center; gap: 3px;
                font-size: 11px; color: var(--color-text-muted); font-weight: 600; position: relative;
            }
            .mobile-bottom-nav a svg { width: 20px; height: 20px; }
            .mobile-bottom-nav a.active { color: var(--color-brand); }
            .mobile-bottom-nav .fab {
                width: 44px; height: 44px; border-radius: 50%; background: var(--color-brand); color: #fff;
                display: grid; place-items: center;
            }
            .mobile-bottom-nav .fab svg { width: 22px; height: 22px; }
            .toast-stack { top: auto; bottom: 80px; right: 16px; left: 16px; max-width: none; }
            .mobile-cards { display: grid; gap: var(--space-4); }
            .desktop-only { display: none !important; }
        }

        @media (min-width: 640px) {
            .mobile-only { display: none !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
@auth
<div class="app">
    <!-- Sidebar -->
    <aside class="sidebar" aria-label="Main navigation">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark"><i data-lucide="layout-dashboard" style="width:18px;height:18px"></i></span>
            Task Management
        </a>

        <nav>
            <ul class="nav-list">
                <li>
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard"></i> Dashboard
                    </a>
                </li>
            </ul>

            <div class="nav-section-label">Work</div>
            <ul class="nav-list">
                <li>
                    <a href="{{ route('tasks.index') }}" class="nav-link {{ request()->routeIs('tasks.index') || request()->routeIs('tasks.show') ? 'active' : '' }}">
                        <i data-lucide="clipboard-list"></i> Tasks
                    </a>
                </li>
                @can('create', App\Models\Task::class)
                <li>
                    <a href="{{ route('tasks.create') }}" class="nav-link {{ request()->routeIs('tasks.create') ? 'active' : '' }}">
                        <i data-lucide="plus-circle"></i> Create Task
                    </a>
                </li>
                @endcan
            </ul>

            <div class="nav-section-label">System</div>
            <ul class="nav-list">
                <li>
                    <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                        <i data-lucide="bell"></i> Notifications
                        @php($unread = auth()->user()->notifications()->where('is_read', false)->count())
                        @if($unread)
                            <span class="dot-badge" style="position:static;margin-left:auto;background:var(--color-overdue);color:#fff;min-width:18px;height:18px;border-radius:999px;font-size:11px;display:grid;place-items:center;padding:0 5px;">{{ $unread }}</span>
                        @endif
                    </a>
                </li>
            </ul>

            <div class="nav-divider"></div>

            <div class="nav-section-label">Settings</div>
            <ul class="nav-list">
                <li>
                    <a href="{{ route('profile') }}" class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}">
                        <i data-lucide="user"></i> Profile
                    </a>
                </li>
                @can('viewAny', App\Models\User::class)
                <li>
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i data-lucide="users"></i> Users
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <i data-lucide="shield-check"></i> Roles &amp; Permissions
                    </a>
                </li>
                @endcan
            </ul>

            <div class="nav-divider"></div>
            <ul class="nav-list" style="margin-bottom:0">
                <li>
                    <a href="{{ route('logout') }}" class="nav-link">
                        <i data-lucide="log-out"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main -->
    <div class="main">
        <header class="topbar">
            <span class="page-context">@yield('title', 'Dashboard')</span>
            <div class="spacer"></div>

            <form class="topbar-search" action="{{ route('tasks.index') }}" method="GET" role="search">
                <i data-lucide="search"></i>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search tasks..." aria-label="Search tasks">
            </form>

            <a href="{{ route('notifications.index') }}" class="icon-btn" aria-label="Notifications">
                <i data-lucide="bell"></i>
                @php($unread = auth()->user()->notifications()->where('is_read', false)->count())
                @if($unread)
                    <span class="dot-badge">{{ $unread > 99 ? '99+' : $unread }}</span>
                @endif
            </a>

            <div class="dropdown">
                <button class="avatar" id="user-menu-btn" aria-haspopup="true" aria-expanded="false" aria-label="User menu">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </button>
                <div class="dropdown-menu" id="user-menu">
                    <div class="dropdown-header">
                        <div style="font-weight:600">{{ auth()->user()->name }}</div>
                        <div class="caption">{{ auth()->user()->role->name ?? 'No role' }}</div>
                    </div>
                    <a href="{{ route('profile') }}"><i data-lucide="user" style="width:16px;height:16px"></i> Profile</a>
                    <a href="{{ route('logout') }}"><i data-lucide="log-out" style="width:16px;height:16px"></i> Logout</a>
                </div>
            </div>
        </header>

        <main class="content">
            @if(session('success'))
                <div class="toast success" data-auto-dismiss>
                    <span>{{ session('success') }}</span>
                    <button class="toast-close" aria-label="Close">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="toast error" data-auto-dismiss>
                    <span>{{ session('error') }}</span>
                    <button class="toast-close" aria-label="Close">&times;</button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Mobile bottom navigation -->
    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i data-lucide="home"></i> Home
        </a>
        <a href="{{ route('tasks.index') }}" class="{{ request()->routeIs('tasks.*') ? 'active' : '' }}">
            <i data-lucide="clipboard-list"></i> Tasks
        </a>
        @can('create', App\Models\Task::class)
        <a href="{{ route('tasks.create') }}" class="fab" aria-label="Create task">
            <i data-lucide="plus"></i>
        </a>
        @endcan
        <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">
            <i data-lucide="bell"></i> Alerts
        </a>
        <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'active' : '' }}">
            <i data-lucide="user"></i> Profile
        </a>
    </nav>
</div>
@else
    @yield('content')
@endauth

<script src="https://unpkg.com/lucide@0.454.0/dist/umd/lucide.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) { lucide.createIcons(); }

        // User dropdown
        var userBtn = document.getElementById('user-menu-btn');
        var userMenu = document.getElementById('user-menu');
        if (userBtn && userMenu) {
            userBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                userMenu.classList.toggle('show');
                userBtn.setAttribute('aria-expanded', userMenu.classList.contains('show'));
            });
            document.addEventListener('click', function () {
                userMenu.classList.remove('show');
                userBtn.setAttribute('aria-expanded', 'false');
            });
        }

        // Toast auto dismiss (error stays longer)
        document.querySelectorAll('.toast[data-auto-dismiss]').forEach(function (toast) {
            var isError = toast.classList.contains('error');
            setTimeout(function () { toast.remove(); }, isError ? 8000 : 4000);
            var close = toast.querySelector('.toast-close');
            if (close) { close.addEventListener('click', function () { toast.remove(); }); }
        });

        // Modals
        document.querySelectorAll('[data-modal-open]').forEach(function (trigger) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                var modal = document.querySelector(trigger.getAttribute('data-modal-open'));
                if (modal) { modal.classList.add('show'); var f = modal.querySelector('textarea,input,button'); if (f) f.focus(); }
            });
        });
        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) overlay.classList.remove('show');
            });
            overlay.querySelectorAll('[data-modal-close]').forEach(function (btn) {
                btn.addEventListener('click', function () { overlay.classList.remove('show'); });
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.show').forEach(function (m) { m.classList.remove('show'); });
        });
    });
</script>
@stack('scripts')
</body>
</html>
