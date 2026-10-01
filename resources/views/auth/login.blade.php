<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Task Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg: #F7F8FA; --color-surface: #FFFFFF; --color-border: #E6E8EC;
            --color-text: #171A1F; --color-text-secondary: #667085; --color-text-muted: #98A2B3;
            --color-brand: #1F4B99; --color-brand-hover: #173A78; --color-focus: #84A9E8;
            --color-done: #2F855A; --color-done-bg: #E6F5EC;
            --color-overdue: #C53030; --color-overdue-bg: #FDECEC;
            --radius-sm: 8px; --radius-md: 12px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; background: var(--color-bg);
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
            display: flex; align-items: center; justify-content: center; padding: 24px;
            color: var(--color-text);
        }
        .login-card {
            width: 100%; max-width: 400px; background: var(--color-surface);
            border: 1px solid var(--color-border); border-radius: var(--radius-md);
            box-shadow: 0 1px 3px rgba(23,26,31,.06); padding: 40px 32px;
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 16px; margin-bottom: 28px; }
        .brand-mark {
            width: 34px; height: 34px; border-radius: var(--radius-sm); background: var(--color-brand);
            color: #fff; display: grid; place-items: center; font-size: 16px;
        }
        h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; }
        .subtitle { color: var(--color-text-secondary); font-size: 14px; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        input {
            width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--color-border);
            border-radius: var(--radius-sm); font-size: 14px; font-family: inherit; background: #fff;
        }
        input:focus { outline: none; border-color: var(--color-focus); box-shadow: 0 0 0 3px rgba(132,169,232,.25); }
        button {
            width: 100%; height: 44px; border: 0; border-radius: var(--radius-sm);
            background: var(--color-brand); color: #fff; font-size: 14px; font-weight: 600;
            font-family: inherit; cursor: pointer; margin-top: 8px;
        }
        button:hover { background: var(--color-brand-hover); }
        .alert { padding: 10px 14px; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 16px; }
        .alert-danger { background: var(--color-overdue-bg); color: var(--color-overdue); border: 1px solid #F8D4D4; }
        .alert-success { background: var(--color-done-bg); color: var(--color-done); border: 1px solid #C6EFD9; }
        :focus-visible { outline: 2px solid var(--color-focus); outline-offset: 2px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand"><span class="brand-mark">✓</span> Task Management</div>
        <h1>Welcome back</h1>
        <p class="subtitle">Sign in to continue to your workspace.</p>

        @if(session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.authenticate') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email <span style="color:var(--color-overdue)">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@company.com">
            </div>
            <div class="form-group">
                <label for="password">Password <span style="color:var(--color-overdue)">*</span></label>
                <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="••••••••">
            </div>
            <button type="submit">Sign in</button>
        </form>
    </div>
</body>
</html>
