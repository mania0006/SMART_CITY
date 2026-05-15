<?php
require_once '../config/config.php';
requireAdmin();

$citizens = $pdo->query("SELECT *, (SELECT COUNT(*) FROM Complaint WHERE citizen_id = Citizen.citizen_id) AS total_complaints FROM Citizen ORDER BY registered_date DESC")->fetchAll();
$officers = $pdo->query("SELECT o.*, d.name AS dept_name FROM Officer o JOIN Department d ON o.department_id = d.department_id ORDER BY o.officer_id")->fetchAll();

include '../includes/header.php';
?>

<div class="container">
    <?php showFlash(); ?>
    <div class="page-header">
        <h2><i class="fas fa-users"></i> Manage Users</h2>
    </div>

    <div class="tab-bar" id="userTabs">
        <a href="#" class="tab active" onclick="showTab('citizens')">👤 Citizens (<?= count($citizens) ?>)</a>
        <a href="#" class="tab" onclick="showTab('officers')">👮 Officers (<?= count($officers) ?>)</a>
    </div>

    <div id="citizens" class="tab-content">
        <div class="card">
            <table class="table">
                <thead>
                    <tr><th>#</th><th>Name</th><th>CNIC</th><th>Phone</th><th>Email</th><th>Registered</th><th>Complaints</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($citizens as $u): ?>
                    <tr>
                        <td><?= $u['citizen_id'] ?></td>
                        <td><?= clean($u['name']) ?></td>
                        <td><?= clean($u['cnic']) ?></td>
                        <td><?= clean($u['phone']) ?></td>
                        <td><?= clean($u['email']) ?></td>
                        <td><?= $u['registered_date'] ?></td>
                        <td><?= $u['total_complaints'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="officers" class="tab-content" style="display:none;">
        <div class="card">
            <table class="table">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Role</th><th>Department</th><th>Contact</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($officers as $o): ?>
                    <tr>
                        <td><?= $o['officer_id'] ?></td>
                        <td><?= clean($o['name']) ?></td>
                        <td><?= clean($o['role']) ?></td>
                        <td><?= clean($o['dept_name']) ?></td>
                        <td><?= clean($o['contact']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showTab(tab) {
    document.getElementById('citizens').style.display = tab === 'citizens' ? 'block' : 'none';
    document.getElementById('officers').style.display = tab === 'officers' ? 'block' : 'none';
    document.querySelectorAll('.tab').forEach((t,i) => t.classList.toggle('active', i === (tab==='citizens'?0:1)));
    return false;
}
</script>

<?php include '../includes/footer.php'; ?>