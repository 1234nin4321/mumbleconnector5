<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mumble Connection Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #10b981;
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
            max-width: 480px;
            padding: 20px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .logo {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 24px;
            background: rgba(79, 70, 229, 0.1);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 20px;
            margin-left: auto;
            margin-right: auto;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #fff;
        }

        p.subtitle {
            color: var(--text-muted);
            margin-bottom: 32px;
            font-size: 14px;
            line-height: 1.5;
        }

        .info-grid {
            text-align: left;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-item {
            margin-bottom: 16px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 6px;
            display: block;
            font-weight: 600;
        }

        .value {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 15px;
            background: rgba(15, 23, 42, 0.5);
            padding: 10px 12px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            word-break: break-all;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .copy-btn {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            padding: 4px 8px;
            transition: all 0.2s;
            border-radius: 4px;
        }

        .copy-btn:hover {
            background: rgba(79, 70, 229, 0.1);
            color: #fff;
        }

        .mumble-btn {
            display: inline-block;
            width: 100%;
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin-bottom: 16px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);
            border: none;
            cursor: pointer;
            box-sizing: border-box;
        }

        .mumble-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.5);
            filter: brightness(1.1);
        }

        .mumble-btn i {
            margin-right: 8px;
        }

        .expiry {
            font-size: 12px;
            color: var(--danger);
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            opacity: 0.9;
        }

        .footer-note {
            margin-top: 32px;
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo"><i class="fas fa-headset"></i></div>
            <h1>Mumble Access</h1>
            <p class="subtitle">You've been invited to join our Mumble server.<br>Please use the credentials below to connect.</p>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Server Address</span>
                    <div class="value">
                        <span id="addr">{{ $server_address }}</span>
                        <button class="copy-btn" onclick="copy('addr')" title="Copy"><i class="far fa-copy"></i></button>
                    </div>
                </div>
                <div class="info-item">
                    <span class="label">Port</span>
                    <div class="value">
                        <span id="port">{{ $server_port }}</span>
                        <button class="copy-btn" onclick="copy('port')" title="Copy"><i class="far fa-copy"></i></button>
                    </div>
                </div>
                <div class="info-item">
                    <span class="label">Username</span>
                    <div class="value">
                        <span id="user">{{ $link->mumble_username }}</span>
                        <button class="copy-btn" onclick="copy('user')" title="Copy"><i class="far fa-copy"></i></button>
                    </div>
                </div>
                <div class="info-item">
                    <span class="label">Password</span>
                    <div class="value">
                        <span id="pass">{{ $link->password }}</span>
                        <button class="copy-btn" onclick="copy('pass')" title="Copy"><i class="far fa-copy"></i></button>
                    </div>
                </div>
            </div>

            <a href="mumble://{{ $link->mumble_username }}:{{ $link->password }}@ {{ $server_address }}:{{ $server_port }}/?title={{ urlencode($link->display_name) }}" class="mumble-btn">
                <i class="fas fa-external-link-alt"></i> Open Mumble
            </a>

            <div class="expiry">
                <i class="fas fa-clock"></i> 
                <span>Expires {{ $link->expires_at->diffForHumans() }}</span>
            </div>

            <div class="footer-note">
                Need help? Contact the person who sent you this link.
            </div>
        </div>
    </div>

    <script>
        function copy(id) {
            const text = document.getElementById(id).innerText;
            const btn = document.querySelector(`button[onclick="copy('${id}')"] i`);
            
            navigator.clipboard.writeText(text).then(() => {
                const oldClass = btn.className;
                btn.className = 'fas fa-check';
                btn.style.color = '#10b981';
                
                setTimeout(() => {
                    btn.className = oldClass;
                    btn.style.color = '';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }
    </script>
</body>
</html>
