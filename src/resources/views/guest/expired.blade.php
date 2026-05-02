<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expired</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --danger: #ef4444;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 48px 32px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .icon {
            font-size: 64px;
            color: var(--danger);
            margin-bottom: 24px;
            opacity: 0.8;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon"><i class="fas fa-hourglass-end"></i></div>
            <h1>Link Expired</h1>
            <p>This temporary connection link has expired and is no longer valid. Please contact an administrator if you need a new one.</p>
        </div>
    </div>
</body>
</html>
