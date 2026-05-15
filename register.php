<?php
session_start();

// Direct DB connection - no includes needed
$pdo = new PDO("mysql:host=localhost;dbname=smart_city_v2;charset=utf8","root","");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$APP_URL = 'http://localhost/smart_city_complaints';

if (isset($_SESSION['role'])) {
    header("Location: $APP_URL/dashboard.php"); exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $cnic     = trim($_POST['cnic']     ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $email    = trim($_POST['email']    ?? '');
    $address  = trim($_POST['address']  ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    // Validation
    if (empty($name) || empty($cnic) || empty($email) || empty($password)) {
        $error = 'Name, CNIC, Email and Password are required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match. Please try again.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            // Check if email already exists
            $checkEmail = $pdo->prepare("SELECT citizen_id FROM Citizen WHERE email = ?");
            $checkEmail->execute([$email]);
            if ($checkEmail->fetch()) {
                $error = 'This email is already registered. Please use a different email or login.';
            } else {
                // Check if CNIC already exists
                $checkCnic = $pdo->prepare("SELECT citizen_id FROM Citizen WHERE cnic = ?");
                $checkCnic->execute([$cnic]);
                if ($checkCnic->fetch()) {
                    $error = 'This CNIC is already registered.';
                } else {
                    // Insert new citizen — plain text password
                    $stmt = $pdo->prepare("
                        INSERT INTO Citizen (name, cnic, phone, email, address, password, registered_date)
                        VALUES (?, ?, ?, ?, ?, ?, CURDATE())
                    ");
                    $stmt->execute([$name, $cnic, $phone, $email, $address, $password]);

                    // Auto login after registration
                    $newId = $pdo->lastInsertId();
                    $_SESSION['user_id']   = $newId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['role']      = 'citizen';

                    header("Location: $APP_URL/dashboard.php"); exit();
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Smart City Complaints</title>
    <link rel="stylesheet" href="<?= $APP_URL ?>/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background:#07090f; font-family:'Inter',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }

        .reg-wrapper {
            display:flex;
            width:100%;
            max-width:1000px;
            min-height:620px;
            border-radius:24px;
            overflow:hidden;
            box-shadow:0 32px 80px rgba(0,0,0,0.7);
            border:1px solid rgba(255,255,255,0.06);
        }

        /* LEFT PANEL */
        .reg-left {
            flex:1;
            background:#111520;
            padding:48px 52px;
            display:flex;
            flex-direction:column;
            justify-content:center;
        }
        .reg-logo { display:flex; align-items:center; gap:12px; margin-bottom:36px; }
        .reg-logo-icon { width:38px; height:38px; background:linear-gradient(135deg,#6366f1,#8b5cf6); border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:17px; color:white; box-shadow:0 4px 16px rgba(99,102,241,0.4); }
        .reg-logo-text { font-size:16px; font-weight:700; color:#f0f2ff; }
        .reg-logo-text span { color:#4b5563; font-weight:400; margin-left:4px; font-size:13px; }

        .reg-heading { font-size:26px; font-weight:800; color:#f0f2ff; letter-spacing:-0.7px; margin-bottom:6px; }
        .reg-sub { font-size:13.5px; color:#6b7280; margin-bottom:28px; }

        .error-box { background:rgba(239,68,68,0.07); border:1px solid rgba(239,68,68,0.2); color:#f87171; padding:12px 16px; border-radius:8px; font-size:13px; margin-bottom:18px; display:flex; align-items:flex-start; gap:8px; }

        .form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .f-group { margin-bottom:16px; }
        .f-label { display:block; font-size:10.5px; font-weight:700; color:#4b5563; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:7px; }
        .f-input { width:100%; padding:11px 15px; background:#0a0d18; border:1px solid rgba(255,255,255,0.08); border-radius:9px; font-family:'Inter',sans-serif; font-size:13.5px; color:#f0f2ff; outline:none; transition:all 0.18s; }
        .f-input::placeholder { color:#374151; }
        .f-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,0.08); background:#0e1220; }

        .submit-btn { width:100%; padding:13px; background:linear-gradient(135deg,#6366f1,#8b5cf6); border:none; border-radius:10px; color:white; font-family:'Inter',sans-serif; font-size:14px; font-weight:700; cursor:pointer; transition:all 0.18s; display:flex; align-items:center; justify-content:center; gap:8px; margin-top:8px; box-shadow:0 4px 20px rgba(99,102,241,0.3); }
        .submit-btn:hover { opacity:0.92; transform:translateY(-1px); }

        .reg-footer { margin-top:20px; text-align:center; font-size:13px; color:#4b5563; }
        .reg-footer a { color:#818cf8; text-decoration:none; font-weight:600; }

        /* RIGHT PANEL */
        .reg-right {
            width:360px;
            flex-shrink:0;
            background:linear-gradient(160deg,#1a1040 0%,#120d2e 40%,#0d0a24 100%);
            padding:48px 40px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            position:relative;
            overflow:hidden;
            border-left:1px solid rgba(99,102,241,0.15);
        }
        .reg-right::before { content:''; position:absolute; top:-100px; right:-100px; width:300px; height:300px; background:radial-gradient(circle,rgba(99,102,241,0.15),transparent 70%); pointer-events:none; }
        .reg-right::after { content:''; position:absolute; bottom:-60px; left:-60px; width:220px; height:220px; background:radial-gradient(circle,rgba(139,92,246,0.1),transparent 70%); pointer-events:none; }
        .right-content { position:relative; z-index:1; }
        .right-icon { width:64px; height:64px; background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(139,92,246,0.15)); border:1px solid rgba(99,102,241,0.25); border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:28px; color:#818cf8; margin-bottom:24px; }
        .right-title { font-size:22px; font-weight:800; color:#f0f2ff; letter-spacing:-0.6px; line-height:1.3; margin-bottom:10px; }
        .right-title span { background:linear-gradient(135deg,#818cf8,#c4b5fd); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .right-desc { font-size:13px; color:#4b5563; line-height:1.7; margin-bottom:28px; }

        .feature-list { display:flex; flex-direction:column; gap:12px; }
        .feature-item { display:flex; align-items:center; gap:12px; font-size:13px; color:#6b7280; }
        .feature-icon { width:32px; height:32px; background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.15); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:14px; color:#818cf8; flex-shrink:0; }

        .login-link { margin-top:32px; display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:rgba(99,102,241,0.06); border:1px solid rgba(99,102,241,0.2); border-radius:12px; color:#818cf8; text-decoration:none; font-size:13px; font-weight:700; transition:all 0.18s; text-transform:uppercase; letter-spacing:0.7px; }
        .login-link:hover { background:rgba(99,102,241,0.12); color:#a5b4fc; transform:translateX(3px); }

        @media(max-width:768px){ .reg-wrapper{flex-direction:column;max-width:480px;} .reg-right{width:100%;padding:32px;} .reg-left{padding:36px 28px;} .form-row-2{grid-template-columns:1fr;} }
    </style>
</head>
<body>

<div class="reg-wrapper">

    <!-- LEFT: FORM -->
    <div class="reg-left">
        <div class="reg-logo">
            <div class="reg-logo-icon"><i class="fas fa-city"></i></div>
            <div class="reg-logo-text">SmartCity <span>Complaints</span></div>
        </div>

        <h1 class="reg-heading">Create Account</h1>
        <p class="reg-sub">Register as a citizen to report and track civic issues</p>

        <?php if($error): ?>
        <div class="error-box"><i class="fas fa-exclamation-circle" style="margin-top:2px;flex-shrink:0;"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row-2">
                <div class="f-group">
                    <label class="f-label"><i class="fas fa-user"></i> Full Name *</label>
                    <input class="f-input" type="text" name="name"
                           value="<?= htmlspecialchars($_POST['name']??'') ?>"
                           placeholder="Ali Hassan" required>
                </div>
                <div class="f-group">
                    <label class="f-label"><i class="fas fa-id-card"></i> CNIC *</label>
                    <input class="f-input" type="text" name="cnic"
                           value="<?= htmlspecialchars($_POST['cnic']??'') ?>"
                           placeholder="42101-1234567-1" required>
                </div>
            </div>
            <div class="form-row-2">
                <div class="f-group">
                    <label class="f-label"><i class="fas fa-phone"></i> Phone</label>
                    <input class="f-input" type="text" name="phone"
                           value="<?= htmlspecialchars($_POST['phone']??'') ?>"
                           placeholder="0300-1234567">
                </div>
                <div class="f-group">
                    <label class="f-label"><i class="fas fa-envelope"></i> Email *</label>
                    <input class="f-input" type="email" name="email"
                           value="<?= htmlspecialchars($_POST['email']??'') ?>"
                           placeholder="you@gmail.com" required>
                </div>
            </div>
            <div class="f-group">
                <label class="f-label"><i class="fas fa-map-marker-alt"></i> Address</label>
                <input class="f-input" type="text" name="address"
                       value="<?= htmlspecialchars($_POST['address']??'') ?>"
                       placeholder="House 5, Block A, Gulshan, Karachi">
            </div>
            <div class="form-row-2">
                <div class="f-group">
                    <label class="f-label"><i class="fas fa-lock"></i> Password *</label>
                    <input class="f-input" type="password" name="password"
                           placeholder="Min 6 characters" required>
                </div>
                <div class="f-group">
                    <label class="f-label"><i class="fas fa-lock"></i> Confirm Password *</label>
                    <input class="f-input" type="password" name="confirm"
                           placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-user-plus"></i> Create My Account
            </button>
        </form>

        <div class="reg-footer">
            Already have an account? <a href="<?= $APP_URL ?>/login.php">Sign in here</a>
        </div>
    </div>

    <!-- RIGHT: INFO PANEL -->
    <div class="reg-right">
        <div class="right-content">
            <div class="right-icon"><i class="fas fa-user-plus"></i></div>
            <div class="right-title">Join the<br><span>Smart City</span><br>Platform</div>
            <p class="right-desc">Get access to civic complaint management and help improve your city.</p>

            <div class="feature-list">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-paper-plane"></i></div>
                    Submit complaints with photo evidence
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-bell"></i></div>
                    Get real-time status notifications
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-search"></i></div>
                    Track your complaint progress live
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-star"></i></div>
                    Rate and review resolved issues
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-gavel"></i></div>
                    File appeals if unsatisfied
                </div>
            </div>

            <a href="<?= $APP_URL ?>/login.php" class="login-link">
                <span>Already registered? Sign In</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>

</div>

</body>
</html>