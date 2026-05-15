<?php
require_once '../config/config.php';
requireCitizen();

$categories = $pdo->query("SELECT * FROM Category")->fetchAll();
$wards      = $pdo->query("SELECT w.*, m.name AS municipality FROM Ward w JOIN Municipality m ON w.municipality_id = m.municipality_id")->fetchAll();
$departments= $pdo->query("SELECT * FROM Department")->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description  = clean($_POST['description'] ?? '');
    $category_id  = (int)($_POST['category_id'] ?? 0);
    $dept_id      = (int)($_POST['dept_id'] ?? 0);
    $ward_id      = (int)($_POST['ward_id'] ?? 0);
    $address      = clean($_POST['address'] ?? '');
    $latitude     = (float)($_POST['latitude'] ?? 0);
    $longitude    = (float)($_POST['longitude'] ?? 0);

    if (empty($description) || !$category_id || !$dept_id || !$ward_id) {
        $error = 'Please fill in all required fields.';
    } else {
        // Insert location
        $loc = $pdo->prepare("INSERT INTO Location (address, latitude, longitude, ward_id) VALUES (?,?,?,?)");
        $loc->execute([$address, $latitude, $longitude, $ward_id]);
        $location_id = $pdo->lastInsertId();

        // Insert complaint
        $comp = $pdo->prepare("INSERT INTO Complaint (description, citizen_id, category_id, location_id, assigned_dept_id, submitted_date, status) VALUES (?,?,?,?,?,CURDATE(),'submitted')");
        $comp->execute([$description, getUserId(), $category_id, $location_id, $dept_id]);
        $complaint_id = $pdo->lastInsertId();

        // Handle file upload
        if (!empty($_FILES['media']['name'])) {
            $ext      = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $filename = 'complaint_' . $complaint_id . '_' . time() . '.' . $ext;
            $filepath = UPLOAD_PATH . $filename;
            if (move_uploaded_file($_FILES['media']['tmp_name'], $filepath)) {
                $type = in_array(strtolower($ext), ['jpg','jpeg','png','gif']) ? 'image' : 'video';
                $media = $pdo->prepare("INSERT INTO Evidence_Media (file_path, type, complaint_id) VALUES (?,?,?)");
                $media->execute([$filename, $type, $complaint_id]);
            }
        }

        setFlash('success', "Complaint #$complaint_id submitted successfully! We will notify you on updates.");
        redirect(APP_URL . '/complaints/track.php');
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php showFlash(); ?>

    <div class="page-header">
        <h2><i class="fas fa-plus-circle"></i> Submit a Complaint</h2>
        <p>Report a civic issue in your area</p>
    </div>

    <?php if ($error): ?>
        <div class="flash error">❌ <?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data" class="form">

            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Issue Details</h3>
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>">
                                <?= clean($cat['type']) ?> (Priority: <?= clean($cat['priority_level']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department *</label>
                    <select name="dept_id" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['department_id'] ?>"><?= clean($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" rows="4" placeholder="Describe the civic issue in detail..." required></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-map-marker-alt"></i> Location</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ward / Area *</label>
                        <select name="ward_id" required>
                            <option value="">-- Select Ward --</option>
                            <?php foreach ($wards as $w): ?>
                                <option value="<?= $w['ward_id'] ?>"><?= clean($w['area_name']) ?> (<?= clean($w['municipality']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Street / Address</label>
                        <input type="text" name="address" placeholder="e.g. Main University Road, Block A">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Latitude (optional)</label>
                        <input type="number" step="0.0001" name="latitude" placeholder="e.g. 24.9333">
                    </div>
                    <div class="form-group">
                        <label>Longitude (optional)</label>
                        <input type="number" step="0.0001" name="longitude" placeholder="e.g. 67.1167">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-paperclip"></i> Evidence (Optional)</h3>
                <div class="form-group">
                    <label>Upload Photo or Video</label>
                    <input type="file" name="media" accept="image/*,video/*">
                    <small>Max 5MB. Supported: JPG, PNG, MP4</small>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= APP_URL ?>/dashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Complaint
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>