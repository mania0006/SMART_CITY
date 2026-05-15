<!DOCTYPE html>
<html>
<head><title>Form Test</title></head>
<body>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1 style='color:green'>✅ FORM SUBMITTED!</h1>";
    echo "<pre>"; print_r($_POST); echo "</pre>";
} else {
    echo "<h1>Page loaded - form not submitted yet</h1>";
}
?>

<form method="POST" action="">
    <input type="text" name="test_input" value="hello world">
    <button type="submit" name="test_btn">SUBMIT TEST</button>
</form>

</body>
</html>