<?php
require_once __DIR__ . '/lib/Auth.php';

use Analytics\Auth;

$auth = new Auth(__DIR__ . '/config/users.json');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($auth->login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid credentials.';
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="public/image/icon.webp" type="image/x-icon">
    <title>Analytics Login</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              light1: '#E8E8E8',
              light2: '#F4F4F4',
              dark1: '#010A1A',
              dark2: '#1A2331',
              dark3: '#283243',
            }
          }
        }
      }
    </script>
    <style>
      html[data-theme='dark'] {
        --bg-main: #010A1A;
        --bg-card: #1A2331;
        --text-main: #fff;
        --border-main: #283243;
      }
      html[data-theme='light'] {
        --bg-main: #E8E8E8;
        --bg-card: #F4F4F4;
        --text-main: #1A2331;
        --border-main: #E8E8E8;
      }
    </style>
</head>
<body class="relative h-screen w-full bg-slate-950 text-[var(--text-main)] transition-colors duration-300">
  <div class="absolute inset-0 bg-[radial-gradient(circle_500px_at_50%_200px,#3e3e3e,transparent)] -z-10"></div>
  <div class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-[var(--bg-card)] rounded-xl shadow-lg p-8 flex flex-col gap-4">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-2xl font-bold">Admin Login</h2>
      </div>
      <?php if ($error): ?><p class="text-red-500 text-sm font-medium mb-2"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <form method="post" class="flex flex-col gap-3">
        <input type="username" name="username" placeholder="Username" required class="rounded border border-[var(--border-main)] bg-transparent px-3 py-2" />
        <input type="password" name="password" placeholder="Password" required class="rounded border border-[var(--border-main)] bg-transparent px-3 py-2" />
        <button type="submit" class="mt-2 px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Login</button>
      </form>
    </div>
  </div>
  <script>
    // Theme toggle logic
    const themeToggle = document.getElementById('themeToggle');
    function setTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('theme', theme);
    }
    function getTheme() {
      return localStorage.getItem('theme') || 'dark';
    }
    setTheme(getTheme());
    themeToggle.addEventListener('click', () => {
      const current = getTheme();
      setTheme(current === 'dark' ? 'light' : 'dark');
    });
  </script>
</body>
</html>
