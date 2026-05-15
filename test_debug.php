<?php
// ══════════════════════════════════════════════
//  DEBUG FILE - Apne project root mein rakho
//  localhost/smart_city_complaints/test_debug.php
//  Kaam hone ke baad DELETE kar dena!
// ══════════════════════════════════════════════

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Step 1: PHP Working</h2><p style='color:green'>✅ PHP OK</p>";

// Step 2: Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=smart_city_v2;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2>Step 2: Database</h2><p style='color:green'>✅ Database connected</p>";
} catch (Exception $e) {
    echo "<h2>Step 2: Database</h2><p style='color:red'>❌ DB Error: " . $e->getMessage() . "</p>";
    die();
}

// Step 3: Session check
session_start();
echo "<h2>Step 3: Session</h2>";
echo "<pre style='background:#f5f5f5;padding:10px'>";
print_r($_SESSION);
echo "</pre>";

// Step 4: POST check
echo "<h2>Step 4: POST Data (submit form neeche)</h2>";
if (!empty($_POST)) {
    echo "<pre style='background:#f5f5f5;padding:10px'>";
    print_r($_POST);
    echo "</pre>";

    // Step 5: Direct insert test
    echo "<h2>Step 5: Direct DB Insert Test</h2>";

    if (isset($_POST['test_appeal'])) {
        $complaint_id = (int)$_POST['complaint_id'];
        $reason = $_POST['reason'];
        try {
            $pdo->prepare("INSERT INTO appeal (reason, status, filed_date, complaint_id) VALUES (?, 'pending', CURDATE(), ?)")
                ->execute([$reason, $complaint_id]);
            echo "<p style='color:green'>✅ Appeal inserted! ID: " . $pdo->lastInsertId() . "</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Appeal insert failed: " . $e->getMessage() . "</p>";
        }
    }

    if (isset($_POST['test_fine'])) {
        $complaint_id = (int)$_POST['complaint_id'];
        $amount = (float)$_POST['amount'];
        $reason = $_POST['reason'];
        try {
            $pdo->prepare("INSERT INTO fine (amount, reason, issued_date, paid_status, complaint_id) VALUES (?, ?, CURDATE(), 'unpaid', ?)")
                ->execute([$amount, $reason, $complaint_id]);
            echo "<p style='color:green'>✅ Fine inserted! ID: " . $pdo->lastInsertId() . "</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Fine insert failed: " . $e->getMessage() . "</p>";
        }
    }

    if (isset($_POST['test_survey'])) {
        $citizen_id = (int)$_POST['citizen_id'];
        $question = $_POST['question'];
        $response = $_POST['response'];
        try {
            $pdo->prepare("INSERT INTO survey (question, response, response_date, citizen_id) VALUES (?, ?, CURDATE(), ?)")
                ->execute([$question, $response, $citizen_id]);
            echo "<p style='color:green'>✅ Survey inserted! ID: " . $pdo->lastInsertId() . "</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Survey insert failed: " . $e->getMessage() . "</p>";
        }
    }

    if (isset($_POST['test_announcement'])) {
        $title   = $_POST['title'];
        $content = $_POST['content'];
        $dept_id = (int)$_POST['dept_id'];
        $muni_id = (int)$_POST['muni_id'];
        try {
            $pdo->prepare("INSERT INTO announcement (title, content, published_date, department_id, municipality_id) VALUES (?, ?, CURDATE(), ?, ?)")
                ->execute([$title, $content, $dept_id, $muni_id]);
            echo "<p style='color:green'>✅ Announcement inserted! ID: " . $pdo->lastInsertId() . "</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Announcement insert failed: " . $e->getMessage() . "</p>";
        }
    }
}

// Fetch existing IDs for reference
$complaints = $pdo->query("SELECT complaint_id, status FROM complaint LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$citizens   = $pdo->query("SELECT citizen_id, name FROM citizen LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$depts      = $pdo->query("SELECT department_id, name FROM department LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$munis      = $pdo->query("SELECT municipality_id, name FROM municipality LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Available IDs in DB:</h2>";
echo "<b>Complaints:</b> <pre>" . print_r($complaints, true) . "</pre>";
echo "<b>Citizens:</b> <pre>"   . print_r($citizens, true)   . "</pre>";
echo "<b>Departments:</b> <pre>". print_r($depts, true)       . "</pre>";
echo "<b>Municipalities:</b> <pre>". print_r($munis, true)    . "</pre>";
?>

<hr>
<h2>Test Forms — Inhe submit karo aur dekho kya hota hai</h2>

<h3>Test 1: Appeal Insert</h3>
<form method="POST" style="background:#fff3cd;padding:15px;margin-bottom:20px;">
    Complaint ID: <input type="number" name="complaint_id" value="<?= $complaints[0]['complaint_id'] ?? 1 ?>"><br><br>
    Reason: <input type="text" name="reason" value="Test appeal reason for debugging"><br><br>
    <button type="submit" name="test_appeal" style="background:blue;color:white;padding:8px 16px;">Submit Appeal Test</button>
</form>

<h3>Test 2: Fine Insert</h3>
<form method="POST" style="background:#d4edda;padding:15px;margin-bottom:20px;">
    Complaint ID: <input type="number" name="complaint_id" value="<?= $complaints[0]['complaint_id'] ?? 1 ?>"><br><br>
    Amount: <input type="number" name="amount" value="5000"><br><br>
    Reason: <input type="text" name="reason" value="Test fine reason"><br><br>
    <button type="submit" name="test_fine" style="background:green;color:white;padding:8px 16px;">Submit Fine Test</button>
</form>

<h3>Test 3: Survey Insert</h3>
<form method="POST" style="background:#cce5ff;padding:15px;margin-bottom:20px;">
    Citizen ID: <input type="number" name="citizen_id" value="<?= $citizens[0]['citizen_id'] ?? 1 ?>"><br><br>
    Question: <input type="text" name="question" value="How satisfied are you with the overall civic complaint resolution service?" style="width:400px"><br><br>
    Response: <input type="text" name="response" value="Very Satisfied"><br><br>
    <button type="submit" name="test_survey" style="background:purple;color:white;padding:8px 16px;">Submit Survey Test</button>
</form>

<h3>Test 4: Announcement Insert</h3>
<form method="POST" style="background:#f8d7da;padding:15px;margin-bottom:20px;">
    Title: <input type="text" name="title" value="Test Announcement"><br><br>
    Content: <input type="text" name="content" value="Test content here"><br><br>
    Dept ID: <input type="number" name="dept_id" value="<?= $depts[0]['department_id'] ?? 1 ?>"><br><br>
    Muni ID: <input type="number" name="muni_id" value="<?= $munis[0]['municipality_id'] ?? 1 ?>"><br><br>
    <button type="submit" name="test_announcement" style="background:red;color:white;padding:8px 16px;">Submit Announcement Test</button>
</form>