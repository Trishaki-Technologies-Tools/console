<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Console Dashboard - Trishaki</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000000;
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1000px;
            width: 100%;
            justify-items: center;
        }

        .card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 40px 30px;
            width: 280px;
            height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow:
                0 8px 32px rgba(255, 255, 255, 0.05),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.08), transparent);
            transition: left 0.6s;
        }

        .card:hover::before {
            left: 100%;
        }

        .card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow:
                0 25px 60px rgba(255, 255, 255, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2),
                0 0 40px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .card-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .card-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .card-icon::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, transparent 50%, rgba(255, 255, 255, 0.1) 100%);
            border-radius: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .card:hover .card-icon::after {
            opacity: 1;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            text-transform: capitalize;
            margin: 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            letter-spacing: 0.5px;
        }

        /* Individual card themes */
        .card-forms .card-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4), 0 0 20px rgba(79, 172, 254, 0.2);
        }

        .card-forms:hover .card-icon {
            box-shadow: 0 12px 35px rgba(79, 172, 254, 0.6), 0 0 30px rgba(79, 172, 254, 0.3);
        }

        .card-vaulto .card-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            box-shadow: 0 8px 25px rgba(67, 233, 123, 0.4), 0 0 20px rgba(67, 233, 123, 0.2);
        }

        .card-vaulto:hover .card-icon {
            box-shadow: 0 12px 35px rgba(67, 233, 123, 0.6), 0 0 30px rgba(67, 233, 123, 0.3);
        }

        .card-interns .card-icon {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            box-shadow: 0 8px 25px rgba(250, 112, 154, 0.4), 0 0 20px rgba(250, 112, 154, 0.2);
        }

        .card-interns:hover .card-icon {
            box-shadow: 0 12px 35px rgba(250, 112, 154, 0.6), 0 0 30px rgba(250, 112, 154, 0.3);
        }

        .card-projects .card-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4), 0 0 20px rgba(240, 147, 251, 0.2);
        }

        .card-projects:hover .card-icon {
            box-shadow: 0 12px 35px rgba(240, 147, 251, 0.6), 0 0 30px rgba(240, 147, 251, 0.3);
        }

        .card-accounts .card-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4), 0 0 20px rgba(102, 126, 234, 0.2);
        }

        .card-accounts:hover .card-icon {
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6), 0 0 30px rgba(102, 126, 234, 0.3);
        }

        .card-tools .card-icon {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            box-shadow: 0 8px 25px rgba(168, 237, 234, 0.4), 0 0 20px rgba(168, 237, 234, 0.2);
        }

        .card-tools:hover .card-icon {
            box-shadow: 0 12px 35px rgba(168, 237, 234, 0.6), 0 0 30px rgba(168, 237, 234, 0.3);
        }

        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        .logout-btn {
            position: fixed;
            top: 25px;
            right: 25px;
            background: rgba(255, 0, 0, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 0, 0, 0.3);
            color: #ff6b6b;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .logout-btn:hover {
            background: rgba(255, 0, 0, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div id="loading"
        style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 1.2rem; display: block;">
        Checking authentication...
    </div>

    <div id="dashboard" style="display: none;">
        <div class="logout-btn" onclick="logout()">
            <span>🚪</span> Logout
        </div>

        <div class="cards-container">
            <!-- Accounts Card -->
            <a href="Accounts/" class="card card-accounts" target="_blank">
                <div class="card-header">
                    <div class="card-icon">👤</div>
                    <div class="card-title">accounts</div>
                </div>
            </a>

            <!-- Forms Card -->
            <a href="https://forms.trishaki.com/admin" class="card card-forms" target="_blank">
                <div class="card-header">
                    <div class="card-icon">📝</div>
                    <div class="card-title">forms</div>
                </div>
            </a>

            <!-- Interns Card -->
            <a href="https://interns.trishaki.com/admin/" class="card card-interns" target="_blank">
                <div class="card-header">
                    <div class="card-icon">🎓</div>
                    <div class="card-title">interns</div>
                </div>
            </a>

            <!-- Projects Card -->
            <a href="/projects/" class="card card-projects" target="_blank">
                <div class="card-header">
                    <div class="card-icon">🚀</div>
                    <div class="card-title">projects</div>
                </div>
            </a>

            <!-- Tools Card -->
            <a href="/tools/" class="card card-tools" target="_blank">
                <div class="card-header">
                    <div class="card-icon">🛠️</div>
                    <div class="card-title">tools</div>
                </div>
            </a>

            <!-- Vaulto Card -->
            <a href="https://vaulto.trishaki.com" class="card card-vaulto" target="_blank">
                <div class="card-header">
                    <div class="card-icon">🔐</div>
                    <div class="card-title">vaulto</div>
                </div>
            </a>
        </div>
    </div>

    <script>
        // Check authentication on page load
        async function checkAuth() {
            try {
                const response = await fetch('check-session.php');
                const data = await response.json();

                if (data.authenticated) {
                    // User is authenticated, show dashboard
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('dashboard').style.display = 'block';
                } else {
                    // User is not authenticated, redirect to login
                    window.location.href = 'index.html';
                }
            } catch (error) {
                console.error('Auth check error:', error);
                // On error, redirect to login for security
                window.location.href = 'index.html';
            }
        }

        // Logout function
        async function logout() {
            try {
                await fetch('logout.php', { method: 'POST' });
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                window.location.href = 'index.html';
            }
        }

        // Check authentication when page loads
        checkAuth();
    </script>
</body>

</html>