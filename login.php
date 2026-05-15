<?php
session_start();

// ── Redirect if already logged in ──
if (isset($_SESSION['role'])) {
    header('Location: dashboard.php');
    exit();
}

// ── DB CONNECTION ──
try {
    $pdo = new PDO("mysql:host=localhost;dbname=smart_city_v2;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error   = '';
$APP_URL = 'http://localhost/smart_city_complaints';

// ── FORM PROCESSING ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = trim($_POST['role']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $dept_id  = trim($_POST['dept_id']  ?? '');

    // ── ADMIN ──
    if ($role === 'admin') {
        if ($email === 'admin@smartcity.pk' && $password === 'admin123') {
        $_SESSION['user_id'] = 'admin';
        $_SESSION['user_name'] = 'Administrator';
            $_SESSION['role']      = 'admin';
            header("Location: $APP_URL/dashboard.php");
            exit();
        }
        $error = 'Invalid admin credentials.';

    // ── CITIZEN ──
    } elseif ($role === 'citizen') {
        if (empty($email) || empty($password)) {
            $error = 'Please enter email and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM Citizen WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Support both plain text AND hashed passwords
                $ok = false;
                if (str_starts_with($user['password'], '$2y$')) {
                    $ok = password_verify($password, $user['password']);
                } else {
                    $ok = ($password === $user['password']);
                }

                if ($ok) {
                    $_SESSION['user_id']   = $user['citizen_id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['role']      = 'citizen';
                    header("Location: $APP_URL/dashboard.php");
                    exit();
                }
            }
            $error = 'Invalid citizen email or password.';
        }

    // ── OFFICER ──
    } elseif ($role === 'officer') {
        if (empty($email) || empty($password) || empty($dept_id)) {
            $error = 'Please fill all fields.';
        } else {
           $stmt = $pdo->prepare("SELECT * FROM Officer WHERE (email = ? OR contact = ?) AND department_id = ?");
$stmt->execute([$email, $email, $dept_id]);
            $user = $stmt->fetch();

            if ($user) {
                // Support both plain text AND hashed passwords
                $ok = false;
                if (str_starts_with($user['password'], '$2y$')) {
                    $ok = password_verify($password, $user['password']);
                } else {
                    $ok = ($password === $user['password']);
                }

                if ($ok) {
                    $_SESSION['user_id']   = $user['officer_id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['role']      = 'officer';
                    $_SESSION['dept_id']   = $user['department_id'];
                    header("Location: $APP_URL/dashboard.php");
                    exit();
                }
            }
            $error = 'Invalid officer credentials or wrong department.';
        }

    } else {
        $error = 'Please select a role to continue.';
    }
}

$departments = $pdo->query("SELECT * FROM Department ORDER BY department_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — Smart City</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

:root {
    --bg:         #06070d;
    --surface:    #0a0b14;
    --card:       #0e1018;
    --hover:      #13151f;
    --elevated:   #181b28;
    --gold:       #c9a84c;
    --gold-l:     #e2c47a;
    --gold-xl:    #f0d080;
    --gold-dim:   rgba(201,168,76,0.12);
    --gold-trace: rgba(201,168,76,0.05);
    --gold-glow:  rgba(201,168,76,0.22);
    --border:     rgba(201,168,76,0.10);
    --border2:    rgba(201,168,76,0.22);
    --t1: #f5f0e8;
    --t2: rgba(245,240,232,0.60);
    --t3: rgba(245,240,232,0.34);
    --t4: rgba(245,240,232,0.16);
    --font-d: 'Cormorant Garamond', Georgia, serif;
    --font-b: 'DM Sans', sans-serif;
    --font-m: 'JetBrains Mono', monospace;
    --ease: cubic-bezier(0.16,1,0.3,1);
}

html { font-size: 16px; }
body {
    font-family: var(--font-b);
    background: var(--bg);
    color: var(--t1);
    width: 100vw; height: 100vh;
    overflow: hidden;
    -webkit-font-smoothing: antialiased;
}

.bg-wrap { position:fixed; inset:0; z-index:0; pointer-events:none; overflow:hidden; }
.orb { position:absolute; border-radius:50%; filter:blur(110px); opacity:0.45; }
.orb-a { width:60vw; height:60vh; top:-15%; left:-15%; background: radial-gradient(ellipse, rgba(201,168,76,0.13) 0%, transparent 70%); animation: fa 26s ease-in-out infinite alternate; }
.orb-b { width:40vw; height:50vh; bottom:-10%; right:15%; background: radial-gradient(ellipse, rgba(160,120,48,0.08) 0%, transparent 70%); animation: fb 20s ease-in-out infinite alternate; }
.orb-c { width:30vw; height:30vh; top:45%; left:35%; background: radial-gradient(ellipse, rgba(240,208,128,0.05) 0%, transparent 70%); animation: fc 30s ease-in-out infinite alternate; }
@keyframes fa { 0%{transform:translate(0,0)} 100%{transform:translate(7%,9%)} }
@keyframes fb { 0%{transform:translate(0,0)} 100%{transform:translate(-6%,-7%)} }
@keyframes fc { 0%{transform:translate(0,0)} 100%{transform:translate(4%,-5%)} }

.bg-grid { position:fixed; inset:0; z-index:0; pointer-events:none; background-image: radial-gradient(rgba(201,168,76,0.025) 1px, transparent 1px); background-size: 28px 28px; }

.page { position:relative; z-index:1; display:flex; width:100vw; height:100vh; }

/* LEFT */
.left { flex:1; display:flex; flex-direction:column; padding:38px 56px 38px 64px; border-right:1px solid var(--border); overflow:hidden; justify-content:space-between; }

.brand { display:flex; align-items:center; gap:13px; flex-shrink:0; animation: up .5s var(--ease) both; }
.brand-icon { width:40px; height:40px; background: linear-gradient(135deg, #8b6820, var(--gold), var(--gold-l)); border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#06070d; box-shadow: 0 0 20px rgba(201,168,76,0.35); flex-shrink:0; }
.brand-text .bn { font-family:var(--font-d); font-size:20px; font-weight:700; color:var(--t1); }
.brand-text .bt { font-size:11px; color:var(--t4); text-transform:uppercase; letter-spacing:0.12em; font-weight:500; margin-top:1px; display:block; }

.hero { flex:1; display:flex; flex-direction:column; justify-content:center; gap:26px; padding:26px 0; animation: up .6s .06s var(--ease) both; }
.hero-tag { display:inline-flex; align-items:center; gap:8px; padding:6px 16px; background:var(--gold-trace); border:1px solid rgba(201,168,76,0.20); border-radius:100px; font-size:13px; font-weight:600; color:var(--gold-l); width:fit-content; }
.hero-h { font-family:var(--font-d); font-size:clamp(52px,5.6vw,82px); font-weight:700; line-height:1.0; letter-spacing:-1px; color:var(--t1); }
.hero-h .aw { background:linear-gradient(135deg,var(--gold) 0%,var(--gold-l) 50%,var(--gold-xl) 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.hero-p { font-size:16.5px; color:var(--t3); line-height:1.75; max-width:430px; }

.feats { display:flex; flex-direction:column; gap:15px; }
.feat { display:flex; align-items:center; gap:14px; animation: up .4s var(--ease) both; }
.feat:nth-child(1){animation-delay:.18s} .feat:nth-child(2){animation-delay:.24s} .feat:nth-child(3){animation-delay:.30s} .feat:nth-child(4){animation-delay:.36s}
.feat-ic { width:36px; height:36px; flex-shrink:0; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:14px; background:var(--gold-trace); border:1px solid rgba(201,168,76,0.18); color:var(--gold); }
.feat-t strong { display:block; font-size:15px; font-weight:600; color:var(--t1); }
.feat-t span { font-size:13px; color:var(--t3); }

.stats { display:flex; background:var(--card); border:1px solid var(--border); border-radius:13px; overflow:hidden; width:fit-content; }
.stat { padding:15px 28px; border-right:1px solid var(--border); text-align:center; }
.stat:last-child { border-right:none; }
.stat-n { font-family:var(--font-d); font-size:30px; font-weight:700; color:var(--gold-l); line-height:1; margin-bottom:3px; }
.stat-l { font-size:11px; font-weight:600; color:var(--t4); text-transform:uppercase; letter-spacing:.09em; }

.reg-cta { display:inline-flex; align-items:center; gap:11px; padding:13px 20px; background:var(--gold-trace); border:1px solid rgba(201,168,76,0.28); border-radius:11px; color:var(--gold-l); text-decoration:none; font-size:15px; font-weight:600; width:fit-content; transition:all .2s var(--ease); }
.reg-cta:hover { background:var(--gold-dim); border-color:rgba(201,168,76,0.50); color:#fff; transform:translateX(4px); }

.creds-row { flex-shrink:0; animation: up .4s .45s var(--ease) both; }
.creds-lbl { font-family:var(--font-m); font-size:10.5px; color:var(--t4); letter-spacing:.13em; text-transform:uppercase; margin-bottom:10px; }
.creds-pills { display:flex; gap:8px; flex-wrap:wrap; }
.cpill { display:flex; align-items:center; gap:8px; padding:7px 15px; background:var(--card); border:1px solid var(--border); border-radius:8px; cursor:pointer; font-family:var(--font-m); font-size:12px; color:var(--t3); transition:all .18s var(--ease); }
.cpill:hover { border-color:var(--border2); color:var(--gold-l); background:var(--hover); }
.cpill-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

/* RIGHT */
.right { width:510px; flex-shrink:0; background:var(--surface); border-left:1px solid var(--border); display:flex; flex-direction:column; justify-content:center; padding:52px 48px; overflow-y:auto; position:relative; }
.right::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,transparent 0%,var(--gold) 30%,var(--gold-xl) 50%,var(--gold) 70%,transparent 100%); opacity:0.75; }

.fhd { margin-bottom:28px; animation: up .5s .08s var(--ease) both; }
.fhd-tag { display:inline-flex; align-items:center; gap:7px; font-family:var(--font-m); font-size:11.5px; font-weight:500; color:var(--gold); letter-spacing:.12em; text-transform:uppercase; margin-bottom:11px; }
.fhd-title { font-family:var(--font-d); font-size:36px; font-weight:700; color:var(--t1); letter-spacing:0.3px; line-height:1.1; margin-bottom:8px; }
.fhd-sub { font-size:15.5px; color:var(--t3); line-height:1.5; }

/* ERROR BOX */
.err { display:flex; align-items:center; gap:11px; padding:14px 18px; border-radius:11px; margin-bottom:24px; background:rgba(239,68,68,0.07); border:1px solid rgba(239,68,68,0.22); color:#fca5a5; font-size:15px; animation: shake .45s var(--ease); }
@keyframes shake { 0%,100%{transform:translateX(0)} 18%{transform:translateX(-6px)} 36%{transform:translateX(6px)} 54%{transform:translateX(-4px)} 72%{transform:translateX(4px)} }

.pdots { display:flex; align-items:center; gap:6px; margin-bottom:24px; }
.pd { width:7px; height:7px; border-radius:4px; background:var(--border2); transition:all .3s var(--ease); }
.pd.on { width:28px; background:linear-gradient(90deg,#8b6820,var(--gold)); box-shadow:0 0 10px var(--gold-glow); }

.step { display:none; animation: sin .28s var(--ease) both; }
.step.on { display:block; }
@keyframes sin { from{opacity:0;transform:translateX(14px)} to{opacity:1;transform:translateX(0)} }

.slbl { font-family:var(--font-b); font-size:12px; font-weight:700; color:var(--t4); text-transform:uppercase; letter-spacing:.11em; margin-bottom:16px; display:block; }

.rcards { display:flex; flex-direction:column; gap:10px; margin-bottom:24px; }
.rcard { display:flex; align-items:center; gap:15px; padding:17px 20px; background:var(--card); border:1px solid var(--border); border-radius:13px; cursor:pointer; width:100%; text-align:left; font-family:var(--font-b); transition:all .2s var(--ease); position:relative; overflow:hidden; }
.rcard::after { content:''; position:absolute; left:0; top:0; bottom:0; width:2px; opacity:0; transition:opacity .2s; border-radius:2px 0 0 2px; background:linear-gradient(180deg,var(--gold),var(--gold-l)); }
.rcard:hover { background:var(--hover); border-color:var(--border2); transform:translateX(4px); }
.rcard.sel { background:var(--gold-trace); border-color:rgba(201,168,76,0.30); }
.rcard.sel::after { opacity:1; }
.rc-ic { width:46px; height:46px; flex-shrink:0; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:19px; background:var(--gold-trace); border:1px solid rgba(201,168,76,0.18); color:var(--gold); }
.rc-info { flex:1; }
.rc-name { font-family:var(--font-d); font-size:18px; font-weight:700; color:var(--t1); margin-bottom:2px; }
.rc-desc { font-size:13.5px; color:var(--t3); }
.rc-arr { font-size:14px; color:var(--t4); transition:all .2s var(--ease); }
.rcard:hover .rc-arr, .rcard.sel .rc-arr { color:var(--gold-l); transform:translateX(3px); }

.dgrid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:22px; }
.dcard { display:flex; flex-direction:column; align-items:center; gap:9px; padding:20px 12px; background:var(--card); border:1px solid var(--border); border-radius:12px; cursor:pointer; font-size:14px; font-weight:600; color:var(--t3); text-align:center; transition:all .2s var(--ease); position:relative; overflow:hidden; }
.dcard i { font-size:24px; color:var(--t4); transition:all .2s; }
.dcard:hover { background:var(--hover); border-color:var(--border2); color:var(--gold-l); }
.dcard:hover i { color:var(--gold); }
.dcard.sel { background:var(--gold-trace); border-color:rgba(201,168,76,0.35); color:var(--gold-l); }
.dcard.sel i { color:var(--gold); }
.dcard.sel::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,var(--gold),var(--gold-l)); }

.bk { display:inline-flex; align-items:center; gap:8px; background:none; border:none; cursor:pointer; font-family:var(--font-b); font-size:14px; font-weight:500; color:var(--t3); padding:0; margin-bottom:18px; transition:color .15s; }
.bk:hover { color:var(--gold-l); }

.cbadge { display:inline-flex; align-items:center; gap:9px; padding:6px 16px; background:var(--card); border:1px solid var(--border2); border-radius:100px; font-size:14px; font-weight:600; color:var(--t2); margin-bottom:20px; }
.cbadge i { font-size:13px; color:var(--gold); }

.fg { margin-bottom:20px; }
.fl { display:block; font-size:12.5px; font-weight:700; color:var(--t4); text-transform:uppercase; letter-spacing:.09em; margin-bottom:10px; }
.fw { position:relative; }
.fw .fi-ic { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--t4); font-size:16px; pointer-events:none; transition:color .2s; }
.fw:focus-within .fi-ic { color:var(--gold); }
.fi { width:100%; padding:15px 16px 15px 48px; background:var(--card); border:1px solid var(--border2); border-radius:11px; font-family:var(--font-b); font-size:16px; color:var(--t1); outline:none; transition:all .2s var(--ease); }
.fi::placeholder { color:var(--t4); font-size:15px; }
.fi:focus { border-color:var(--gold); background:var(--elevated); box-shadow:0 0 0 3px rgba(201,168,76,0.12); }
.pw-btn { position:absolute; right:14px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--t4); font-size:15px; padding:5px; transition:color .15s; }
.pw-btn:hover { color:var(--gold-l); }

.sbtn { width:100%; padding:16px; background:linear-gradient(135deg,#8b6820 0%,var(--gold) 40%,var(--gold-l) 100%); border:none; border-radius:12px; font-family:var(--font-d); font-size:19px; font-weight:700; color:#06070d; cursor:pointer; letter-spacing:0.3px; display:flex; align-items:center; justify-content:center; gap:10px; margin-top:7px; box-shadow:0 0 32px rgba(201,168,76,0.28); transition:all .22s var(--ease); }
.sbtn:hover { box-shadow:0 0 50px rgba(201,168,76,0.45),0 8px 24px rgba(0,0,0,0.5); transform:translateY(-2px); filter:brightness(1.07); }
.sbtn:active { transform:scale(0.98); }

.or { display:flex; align-items:center; gap:13px; margin:20px 0; color:var(--t4); font-size:12.5px; text-transform:uppercase; letter-spacing:.1em; }
.or::before,.or::after { content:''; flex:1; height:1px; background:var(--border); }

.ffoot { text-align:center; font-size:15px; color:var(--t3); }
.ffoot a { color:var(--gold-l); text-decoration:none; font-weight:600; }
.ffoot a:hover { color:var(--gold-xl); }

@keyframes up { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

@media(max-width:860px) {
    body { overflow-y:auto; height:auto; }
    .page { flex-direction:column; height:auto; min-height:100vh; }
    .left { border-right:none; border-bottom:1px solid var(--border); padding:34px 28px; }
    .feats,.stats { display:none; }
    .right { width:100%; padding:38px 28px; }
}
</style>
</head>
<body>

<div class="bg-wrap">
    <div class="orb orb-a"></div>
    <div class="orb orb-b"></div>
    <div class="orb orb-c"></div>
</div>
<div class="bg-grid"></div>

<div class="page">

    <!-- ════ LEFT ════ -->
    <div class="left">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-city"></i></div>
            <div class="brand-text">
                <span class="bn">SmartCity</span>
                <span class="bt">Complaint Portal</span>
            </div>
        </div>

        <div class="hero">
            <div class="hero-tag"><i class="fas fa-map-marker-alt"></i> Karachi · Pakistan</div>
            <h1 class="hero-h">Report.<br>Track.<br><span class="aw">Resolve.</span></h1>
            <p class="hero-p">A royal-grade civic complaint management platform connecting citizens with government departments for faster, transparent issue resolution.</p>

            <div class="feats">
                <div class="feat"><div class="feat-ic"><i class="fas fa-bolt"></i></div><div class="feat-t"><strong>Real-time Tracking</strong><span>Monitor your complaint status live, anytime</span></div></div>
                <div class="feat"><div class="feat-ic"><i class="fas fa-shield-alt"></i></div><div class="feat-t"><strong>Direct to Department</strong><span>Routed instantly to the right government team</span></div></div>
                <div class="feat"><div class="feat-ic"><i class="fas fa-star"></i></div><div class="feat-t"><strong>Rate &amp; Review</strong><span>Hold officers accountable with your feedback</span></div></div>
                <div class="feat"><div class="feat-ic"><i class="fas fa-chart-bar"></i></div><div class="feat-t"><strong>Full Transparency</strong><span>Complete audit trail from submission to resolution</span></div></div>
            </div>

            <div class="stats">
                <div class="stat"><div class="stat-n">4</div><div class="stat-l">Departments</div></div>
                <div class="stat"><div class="stat-n">24/7</div><div class="stat-l">Available</div></div>
                <div class="stat"><div class="stat-n">100%</div><div class="stat-l">Transparent</div></div>
            </div>

            <a href="<?= $APP_URL ?>/register.php" class="reg-cta">
                <i class="fas fa-user-plus"></i>
                Register as Citizen — It's Free
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="creds-row">
            <div class="creds-lbl">Demo Credentials — click to auto-fill</div>
            <div class="creds-pills">
                <button class="cpill" onclick="autofill('admin','admin@smartcity.pk','admin123','')">
                    <div class="cpill-dot" style="background:var(--gold);box-shadow:0 0 6px rgba(201,168,76,0.6);"></div>
                    Admin
                </button>
                <button class="cpill" onclick="autofill('citizen','ali@gmail.com','password123','')">
                    <div class="cpill-dot" style="background:#38bdf8;box-shadow:0 0 6px rgba(56,189,248,0.6);"></div>
                    Citizen
                </button>
                <button class="cpill" onclick="autofill('officer','0311-1111111','password123','1')">
                    <div class="cpill-dot" style="background:#22c55e;box-shadow:0 0 6px rgba(34,197,94,0.6);"></div>
                    Officer
                </button>
            </div>
        </div>
    </div>

    <!-- ════ RIGHT ════ -->
    <div class="right">

        <div class="fhd">
            <div class="fhd-tag"><i class="fas fa-crown"></i> Secure Royal Access</div>
            <h2 class="fhd-title">Welcome Back</h2>
            <p class="fhd-sub">Sign in to your account to continue</p>
        </div>

        <?php if ($error): ?>
        <div class="err">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="pdots">
            <div class="pd on" id="d1"></div>
            <div class="pd"    id="d2"></div>
            <div class="pd"    id="d3"></div>
        </div>

        <form method="POST" id="lf">
            <input type="hidden" name="role"    id="rI" value="<?= htmlspecialchars($_POST['role']    ?? '') ?>">
            <input type="hidden" name="dept_id" id="dI" value="<?= htmlspecialchars($_POST['dept_id'] ?? '') ?>">

            <!-- STEP 1: Role -->
            <div class="step on" id="s1">
                <span class="slbl">Step 1 of 3 — Who are you?</span>
                <div class="rcards">
                    <button type="button" class="rcard" onclick="pickRole('citizen',this)">
                        <div class="rc-ic"><i class="fas fa-user"></i></div>
                        <div class="rc-info"><div class="rc-name">Citizen</div><div class="rc-desc">Report &amp; track complaints</div></div>
                        <i class="fas fa-chevron-right rc-arr"></i>
                    </button>
                    <button type="button" class="rcard" onclick="pickRole('officer',this)">
                        <div class="rc-ic"><i class="fas fa-shield-alt"></i></div>
                        <div class="rc-info"><div class="rc-name">Government Officer</div><div class="rc-desc">Manage &amp; resolve complaints</div></div>
                        <i class="fas fa-chevron-right rc-arr"></i>
                    </button>
                    <button type="button" class="rcard" onclick="pickRole('admin',this)">
                        <div class="rc-ic"><i class="fas fa-crown"></i></div>
                        <div class="rc-info"><div class="rc-name">Administrator</div><div class="rc-desc">Full system oversight &amp; control</div></div>
                        <i class="fas fa-chevron-right rc-arr"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 2: Department (Officer only) -->
            <div class="step" id="s2">
                <button type="button" class="bk" onclick="goto('s1',1)">
                    <i class="fas fa-arrow-left"></i> Back to roles
                </button>
                <span class="slbl">Step 2 of 3 — Select your department</span>
                <div class="dgrid">
                    <?php
                    $icons = [1=>'fas fa-road', 2=>'fas fa-tint', 3=>'fas fa-bolt', 4=>'fas fa-trash-alt'];
                    foreach ($departments as $d):
                        $ic = $icons[$d['department_id']] ?? 'fas fa-building';
                    ?>
                    <button type="button" class="dcard"
                        onclick="pickDept(<?= $d['department_id'] ?>,'<?= htmlspecialchars($d['name']) ?>',this)">
                        <i class="<?= $ic ?>"></i>
                        <?= htmlspecialchars($d['name']) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 3: Credentials -->
            <div class="step" id="s3">
                <button type="button" class="bk" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>

                <div id="cbadge" class="cbadge"></div>

                <div class="fg">
                    <label class="fl" id="eLabel">Email Address</label>
                    <div class="fw">
                        <i class="fas fa-envelope fi-ic" id="eIcon"></i>
                        <input class="fi" type="text" name="email" id="eInput"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               placeholder="your@email.com">
                    </div>
                </div>

                <div class="fg">
                    <label class="fl">Password</label>
                    <div class="fw">
                        <i class="fas fa-lock fi-ic"></i>
                        <input class="fi" type="password" name="password" id="pwI"
                               placeholder="••••••••">
                        <button type="button" class="pw-btn" onclick="togPw(this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="sbtn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In to Portal
                </button>
            </div>
        </form>

        <div class="or">or</div>
        <div class="ffoot">
            New to SmartCity? <a href="<?= $APP_URL ?>/register.php">Create a citizen account</a>
        </div>
    </div>
</div>

<script>
const rI = document.getElementById('rI');
const dI = document.getElementById('dI');
const cb = document.getElementById('cbadge');

function setDots(n) {
    [1,2,3].forEach(i => document.getElementById('d'+i).classList.toggle('on', i===n));
}

function goto(id, dot) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('on'));
    document.getElementById(id).classList.add('on');
    if (dot) setDots(dot);
}

function pickRole(role, btn) {
    rI.value = role;
    document.querySelectorAll('.rcard').forEach(b => b.classList.remove('sel'));
    btn.classList.add('sel');

    if (role === 'officer') {
        goto('s2', 2);
        return;
    }

    const cfg = {
        citizen: { i:'fas fa-user',   label:'Citizen',       ph:'ali@gmail.com',      el:'Email Address', eic:'fas fa-envelope' },
        admin:   { i:'fas fa-crown',  label:'Administrator', ph:'admin@smartcity.pk', el:'Admin Email',   eic:'fas fa-envelope' },
    };
    const c = cfg[role];
    cb.innerHTML = `<i class="${c.i}"></i> ${c.label}`;
    document.getElementById('eInput').placeholder = c.ph;
    document.getElementById('eLabel').textContent  = c.el;
    document.getElementById('eIcon').className     = c.eic + ' fi-ic';
    goto('s3', 3);
}

function pickDept(id, name, btn) {
    dI.value = id;
    document.querySelectorAll('.dcard').forEach(b => b.classList.remove('sel'));
    btn.classList.add('sel');
    cb.innerHTML = `<i class="fas fa-shield-alt"></i> Officer — ${name}`;
    document.getElementById('eLabel').textContent  = 'Contact Number';
    document.getElementById('eInput').placeholder  = '0311-1111111';
    document.getElementById('eIcon').className     = 'fas fa-phone fi-ic';
    goto('s3', 3);
}

function goBack() {
    goto(rI.value === 'officer' ? 's2' : 's1', rI.value === 'officer' ? 2 : 1);
}

function togPw(btn) {
    const pw = document.getElementById('pwI');
    const ic = btn.querySelector('i');
    pw.type = pw.type === 'password' ? 'text' : 'password';
    ic.className = pw.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

// Auto-fill from demo pill buttons
function autofill(role, email, pw, deptId) {
    rI.value = role;

    // Simulate role click
    const roleMap = { citizen: 0, officer: 1, admin: 2 };
    const cards = document.querySelectorAll('.rcard');
    if (cards[roleMap[role]]) pickRole(role, cards[roleMap[role]]);

    // If officer, also pick dept
    if (role === 'officer' && deptId) {
        const dCards = document.querySelectorAll('.dcard');
        dCards.forEach(dc => {
            if (dc.getAttribute('onclick') && dc.getAttribute('onclick').includes('('+deptId+',')) {
                pickDept(deptId, 'Roads & Infrastructure', dc);
            }
        });
    }

    // Fill credentials
    setTimeout(() => {
        document.getElementById('eInput').value = email;
        document.getElementById('pwI').value    = pw;
        // Flash effect
        ['eInput','pwI'].forEach(id => {
            const el = document.getElementById(id);
            el.style.borderColor = 'var(--gold)';
            el.style.boxShadow   = '0 0 0 3px rgba(201,168,76,0.18)';
            setTimeout(() => { el.style.borderColor=''; el.style.boxShadow=''; }, 1400);
        });
        toast('Credentials filled ✓');
    }, role === 'officer' ? 300 : 100);
}

function toast(msg) {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(8px);
        background:var(--elevated);border:1px solid var(--border2);border-left:3px solid var(--gold);
        color:var(--t1);padding:12px 22px;border-radius:10px;font-family:var(--font-b);
        font-size:14px;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,0.6);
        z-index:9999;pointer-events:none;opacity:0;transition:all .3s cubic-bezier(0.16,1,0.3,1);`;
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateX(-50%) translateY(0)'; });
    setTimeout(() => { t.style.opacity='0'; setTimeout(() => t.remove(), 350); }, 2000);
}

// Restore step on PHP error redirect
window.addEventListener('DOMContentLoaded', () => {
    const role = rI.value;
    if (!role) { setDots(1); return; }
    const cards = document.querySelectorAll('.rcard');
    const roleMap = { citizen:0, officer:1, admin:2 };
    if (cards[roleMap[role]]) cards[roleMap[role]].classList.add('sel');

    if (role === 'officer') {
        const deptId = dI.value;
        cb.innerHTML = '<i class="fas fa-shield-alt"></i> Government Officer';
        if (deptId) {
            document.querySelectorAll('.dcard').forEach(dc => {
                if (dc.getAttribute('onclick')?.includes('('+deptId+',')) dc.classList.add('sel');
            });
            goto('s3', 3);
        } else {
            goto('s2', 2);
        }
    } else {
        const m = { citizen:{i:'fas fa-user',label:'Citizen'}, admin:{i:'fas fa-crown',label:'Administrator'} };
        const c = m[role] || m.citizen;
        cb.innerHTML = `<i class="${c.i}"></i> ${c.label}`;
        goto('s3', 3);
    }
});
</script>
</body>
</html>