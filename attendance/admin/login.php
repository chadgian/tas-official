<!DOCTYPE html>
<html lang="en">
<head>
  <?php $assetBase = rtrim(dirname($_SERVER['SCRIPT_NAME'], 3), '/\\'); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Self Attendance Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="../../src/img/csc-logo.png" type="image/png">
  <link rel="apple-touch-icon" href="../../src/img/csc-logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?php echo $assetBase; ?>/css/app.css">
</head>
<body class="app-auth-page">
  <header class="app-topbar sticky-top">
    <div class="container-fluid py-3 px-3 px-lg-4 d-flex align-items-center justify-content-between">
      <div class="brand-lockup">
        <img src="../../src/img/csc-logo.png" alt="CSC Logo" width="48" height="48" class="rounded-3">
        <div>
          <div class="app-kicker text-white-50">Training Attendance System</div>
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="../login.php" class="btn btn-light btn-sm rounded-pill px-3 fw-semibold" title="Participant login">
          <i class="fas fa-user me-2"></i>Participant
        </a>
      </div>
    </div>
  </header>

  <main class="container-fluid py-4 py-lg-5">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-8">
        <div class="app-panel p-3 p-lg-4 overflow-hidden">
          <div class="row g-0 align-items-stretch app-auth-split">
            <div class="col-lg-5">
              <div class="app-auth-sidecard h-100 text-white">
                <div class="app-auth-badge mb-4">HR</div>
                <div class="app-kicker text-white-50 mb-2">Admin access</div>
                <h1 class="display-6 fw-bold mb-3">Manage attendance</h1>
                <p class="mb-4 opacity-75">Configure schedules, passwords, and attendance windows from one secure place.</p>
                <div class="app-mini-card app-auth-sidecard-stats mb-4">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="app-auth-badge" style="width: 2.7rem; height: 2.7rem; background: rgba(255,255,255,.14); box-shadow: none;">
                      <i class="fas fa-lock"></i>
                    </div>
                    <div>
                      <div class="fw-semibold">Restricted portal</div>
                      <small class="opacity-75">For authorized attendance admins only</small>
                    </div>
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill text-bg-light text-dark">Session manager</span>
                    <span class="badge rounded-pill text-bg-light text-dark">Secure access</span>
                  </div>
                </div>
                <div class="small opacity-75">
                  <div class="d-flex align-items-center gap-2 mb-2"><i class="fas fa-calendar-check"></i><span>Set daily session windows</span></div>
                  <div class="d-flex align-items-center gap-2 mb-2"><i class="fas fa-key"></i><span>Generate attendance passwords</span></div>
                  <div class="d-flex align-items-center gap-2"><i class="fas fa-qrcode"></i><span>Control self-attendance scanning</span></div>
                </div>
              </div>
            </div>
            <div class="col-lg-7">
              <div class="p-4 p-lg-5">
                <div class="mb-4 pb-3 border-bottom">
                  <div class="app-kicker mb-2">Admin access</div>
                  <h2 class="app-section-title h3 mb-2">Session manager</h2>
                  <p class="app-muted mb-0">Enter the admin password to open the self-attendance dashboard.</p>
                </div>
                <?php if (isset($_GET['err'])): ?>
                  <div class="alert alert-danger py-2">
                    <i class="fas fa-circle-exclamation me-2"></i>Invalid password
                  </div>
                <?php endif; ?>
                <form action="loginProcess.php" method="GET" class="d-grid gap-3">
                  <input type="hidden" name="username" id="username" value="admin">
                  <div>
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="password-field">
                      <input type="password" name="password" id="password" class="form-control" placeholder="Enter admin password" required>
                      <button class="btn btn-outline-secondary toggle-password" type="button" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                    <small class="text-muted">Use the same password you assigned for the admin portal.</small>
                  </div>
                  <button type="submit" class="btn app-btn-primary w-100 py-2">
                    <i class="fas fa-right-to-bracket me-2"></i>Enter admin portal
                  </button>
                </form>
                <div class="d-flex justify-content-between flex-wrap gap-2 mt-4 pt-3 border-top small">
                  <span class="app-muted">Need the participant login instead?</span>
                  <a href="../login.php" class="text-decoration-none">Go to main login</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelectorAll('.toggle-password').forEach(button => {
      button.addEventListener('click', function () {
        const input = this.parentElement.querySelector('input');
        const icon = this.querySelector('i');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
      });
    });
  </script>
</body>
</html>
