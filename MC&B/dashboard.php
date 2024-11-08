


<a href="logout.php">Logout</a>

<?php




session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // Redirect to login page if user is not logged in
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome Page</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h1>
    <p>You have successfully logged in.</p>
    <!-- Display an image -->
    <img src="photofunky.gif" alt="Welcome Image" style="max-width: 100%; height: auto;">
</body>
</html>
