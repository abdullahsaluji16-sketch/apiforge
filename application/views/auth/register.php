<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>APIForge — Register</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="auth-page">
<div class="auth-container">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-icon-lg">⚡</div>
      <h1>APIForge</h1>
      <p>Create your account</p>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert alert-error"><?= $this->session->flashdata('error') ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= site_url('register/post') ?>">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" placeholder="Ali Khan" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="ali@example.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Min 8 characters" required>
      </div>
      <button type="submit" class="btn-primary full">Create Account</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="<?= site_url('login') ?>">Login here</a>
    </div>
  </div>
</div>
</body>
</html>
