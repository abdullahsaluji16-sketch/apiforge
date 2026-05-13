<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>APIForge — Login</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  .divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 20px 0;
    color: #888;
    font-size: 13px;
  }
  .divider::before,
  .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.1);
  }

  .btn-google {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 11px 20px;
    background: #fff;
    color: #3c4043;
    border: 1px solid #dadce0;
    border-radius: 8px;
    font-family: 'Sora', sans-serif;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s, box-shadow 0.2s;
    margin-bottom: 4px;
  }
  .btn-google:hover {
    background: #f8f9fa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    text-decoration: none;
    color: #3c4043;
  }
  .btn-google svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
  }
</style>
</head>
<body class="auth-page">
<div class="auth-container">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-icon-lg">⚡</div>
      <h1>APIForge</h1>
      <p>REST Client Panel</p>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert alert-error"><?= $this->session->flashdata('error') ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('success')): ?>
      <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
    <?php endif; ?>

    <!-- Google Login Button -->
    <a href="<?= site_url('auth/google_login') ?>" class="btn-google">
      <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        <path fill="none" d="M0 0h48v48H0z"/>
      </svg>
      Continue with Google
    </a>

    <div class="divider">or login with email</div>

    <!-- Email Login Form -->
    <form method="POST" action="<?= site_url('login/post') ?>">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="admin@apiforge.com" required autocomplete="off">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-primary full">Login to APIForge</button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="<?= site_url('register') ?>">Register here</a>
    </div>
  </div>
</div>
</body>
</html>
