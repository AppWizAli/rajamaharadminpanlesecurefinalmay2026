<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Login Debug</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: #111827;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 24px;
            box-sizing: border-box;
        }
        h2 {
            margin-top: 0;
        }
        p {
            color: #cbd5e1;
        }
        label {
            display: block;
            margin: 14px 0 6px;
            font-weight: 700;
        }
        input {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #475569;
            background: #0f172a;
            color: #f8fafc;
            box-sizing: border-box;
        }
        button {
            margin-top: 18px;
            width: 100%;
            padding: 12px;
            border: 0;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }
        .note {
            margin-top: 16px;
            font-size: 13px;
            color: #fbbf24;
        }
        code {
            background: #1e293b;
            padding: 2px 6px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Admin Login Debug</h2>
        <p>This page submits to <code>login_process_debug.php</code> so the server can show the real PHP or database error instead of only HTTP 500.</p>

        <form action="login_process_debug.php" method="post">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <button type="submit">Run Debug Login</button>
        </form>

        <p class="note">Use this only temporarily on the live server. After we identify the issue, remove these debug files.</p>
    </div>
</body>
</html>
