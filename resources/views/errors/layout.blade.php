<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $code ?? 500 }} · Hotel Management System</title>
        <style>
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family:
                    ui-sans-serif, system-ui, -apple-system, "Segoe UI",
                    Roboto, Helvetica, Arial, sans-serif;
                background: linear-gradient(
                    135deg,
                    #eef2ff 0%,
                    #f8fafc 50%,
                    #e0e7ff 100%
                );
                color: #111827;
                text-align: center;
                padding: 24px;
            }
            .card {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                padding: 48px 40px;
                max-width: 460px;
                width: 100%;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            }
            .code {
                font-size: 64px;
                font-weight: 800;
                line-height: 1;
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            h1 {
                font-size: 20px;
                margin: 16px 0 8px;
            }
            p {
                font-size: 14px;
                color: #6b7280;
                margin: 0 0 24px;
                line-height: 1.6;
            }
            a {
                display: inline-block;
                background: #4f46e5;
                color: #fff;
                text-decoration: none;
                font-size: 14px;
                font-weight: 600;
                padding: 10px 22px;
                border-radius: 10px;
                transition: background 0.15s ease;
            }
            a:hover {
                background: #4338ca;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="code">{{ $code ?? 500 }}</div>
            <h1>{{ $title ?? 'Something went wrong' }}</h1>
            <p>{{ $message ?? 'Please try again or contact an administrator if the problem persists.' }}</p>
            <a href="{{ url('/') }}">Back to dashboard</a>
        </div>
    </body>
</html>
