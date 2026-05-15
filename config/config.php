<?php
ob_start();

require_once __DIR__ . '/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

define('APP_URL','http://localhost/smart_city_complaints');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// FIX: !empty(0) = FALSE hota hai PHP mein — isliye admin ka user_id=0 session fail karta tha
// Ab isset() use karte hain jo 0 ke liye bhi TRUE deta hai
function isLoggedIn(){ return isset($_SESSION['user_id'], $_SESSION['role']); }
function isCitizen(){ return ($_SESSION['role'] ?? '') === 'citizen'; }
function isOfficer(){ return ($_SESSION['role'] ?? '') === 'officer'; }
function isAdmin()  { return ($_SESSION['role'] ?? '') === 'admin'; }
function getUserId(){ return $_SESSION['user_id'] ?? null; }
function getUserName(){ return $_SESSION['user_name'] ?? 'Guest'; }

function redirect($url){
    ob_end_clean();
    header("Location: $url");
    exit();
}
function requireLogin(){ if (!isLoggedIn()) redirect(APP_URL.'/login.php'); }
function requireCitizen(){ requireLogin(); if(!isCitizen()) redirect(APP_URL.'/dashboard.php'); }
function requireOfficer(){ requireLogin(); if(!isOfficer()) redirect(APP_URL.'/dashboard.php'); }
function requireAdmin()  { requireLogin(); if(!isAdmin())   redirect(APP_URL.'/dashboard.php'); }

function setFlash($type,$msg){ $_SESSION['flash']=['type'=>$type,'message'=>$msg]; }
function showFlash(){
    if(!isset($_SESSION['flash'])) return;
    $f=$_SESSION['flash'];
    $icon=['success'=>'fa-check-circle','error'=>'fa-exclamation-circle','warning'=>'fa-exclamation-triangle','info'=>'fa-info-circle'][$f['type']]??'fa-info-circle';
    echo "<div class='flash {$f['type']}'><i class='fas $icon'></i>".htmlspecialchars($f['message'])."</div>";
    unset($_SESSION['flash']);
}
function clean($s){ return htmlspecialchars(strip_tags(trim($s))); }

function statusBadge($status){
    $map=[
        'submitted'  =>['rgba(99,102,241,0.1)','#818cf8','rgba(99,102,241,0.25)','📋 Submitted'],
        'in_progress'=>['rgba(245,158,11,0.1)','#fbbf24','rgba(245,158,11,0.25)','🔧 In Progress'],
        'resolved'   =>['rgba(16,185,129,0.1)','#34d399','rgba(16,185,129,0.25)','✅ Resolved'],
        'escalated'  =>['rgba(239,68,68,0.1)', '#f87171','rgba(239,68,68,0.25)', '🚨 Escalated'],
    ];
    [$bg,$color,$border,$label]=$map[$status]??['rgba(100,100,100,0.1)','#888','rgba(100,100,100,0.2)',ucfirst($status)];
    return "<span class='badge' style='background:$bg;color:$color;border-color:$border'>$label</span>";
}