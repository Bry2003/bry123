<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['signup'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Prepare and bind to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Email already registered.');</script>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert the new user with a prepared statement
            $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $hashedPassword);

            if ($stmt->execute()) {
                echo "<script>alert('Signup successful. Please login.');</script>";
            } else {
                echo "<script>alert('Error: " . $stmt->error . "');</script>";
            }
        }
    }

    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Prepare and bind to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Check if the user is locked
            $now = new DateTime();
            $locked_until = $user['locked_until'] ? new DateTime($user['locked_until']) : null;

            // If the account is locked, check the time difference
            if ($locked_until && $locked_until > $now) {
                // Calculate the remaining lock time
                $lock_time = $locked_until->diff($now);
                $seconds_left = $lock_time->s + ($lock_time->i * 60) + ($lock_time->h * 3600);
                echo "<script>alert('Account locked. Please try again after " . (20 - $seconds_left) . " seconds.');</script>";
            
            } else {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = $user['email'];
                    // Reset attempt count
                    $conn->query("UPDATE users SET attempt_count = 0 WHERE email = '$email'");
                    echo "<script>window.location.href = 'dashboard.php';</script>";
                } else {
                    // Update attempt count and lock account after 3 failed attempts
                    $attempt_count = $user['attempt_count'] + 1;
                    if ($attempt_count >= 3) {
                        $locked_until = new DateTime();
                        $locked_until->modify("+20 seconds");
                        $conn->query("UPDATE users SET attempt_count = 0, locked_until = '" . $locked_until->format('Y-m-d H:i:s') . "' WHERE email = '$email'");
                        echo "<script>alert('Account locked due to too many failed attempts. Please try again later.');</script>";
                    } else {
                        $conn->query("UPDATE users SET attempt_count = $attempt_count WHERE email = '$email'");
                        echo "<script>alert('Incorrect password.');</script>";
                    }
                }
            }
        } else {
            echo "<script>alert('No user found with this email.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mc&B</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('luxury-letters-mcb-golden-logo-260nw-2263100365.webp') no-repeat center center fixed;
            background-size: cover; 
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
            display: flex;
            flex-direction: column; 
            align-items: center; 
        }

        .form-toggle {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .form-toggle button {
            background: none;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            border-bottom: 2px solid transparent;
        }

        .form-toggle .active {
            border-bottom: 2px solid #2980b9;
        }

        form {
            display: none;
        }

        form.active {
            display: block;
        }

        input[type="email"], input[type="password"], button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            display: block; 
            margin-left: auto; 
            margin-right: auto; 
        }

        button {
            background: #2980b9;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #1f6c99;
        }

        .switch-link {
            text-align: center;
            margin-top: 10px;
        }

        .switch-link a {
            color: #2980b9;
            cursor: pointer;
            text-decoration: none;
        }

        h1 {
            margin-bottom: 20px;
            text-align: center;
            color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mc&B</h1>
        <div class="form-toggle">
            <button id="login-toggle" class="active">Login</button>
            <button id="signup-toggle">Signup</button>
        </div>

        <form id="login-form" class="active" method="post">
            <h3>Login</h3>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
            <div class="switch-link">Not a member? <a id="switch-to-signup">Sign up now</a></div>
        </form>

        <form id="signup-form" method="post">
            <h3>Sign up</h3>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="signup">Sign up</button>
            <div class="switch-link">Already a member? <a id="switch-to-login">Login now</a></div>
        </form>
    </div>

    <script>
        const loginToggle = document.getElementById('login-toggle');
        const signupToggle = document.getElementById('signup-toggle');
        const loginForm = document.getElementById('login-form');
        const signupForm = document.getElementById('signup-form');
        const switchToSignup = document.getElementById('switch-to-signup');
        const switchToLogin = document.getElementById('switch-to-login');

        loginToggle.addEventListener('click', () => {
            loginToggle.classList.add('active');
            signupToggle.classList.remove('active');
            loginForm.classList.add('active');
            signupForm.classList.remove('active');
        });

        signupToggle.addEventListener('click', () => {
            signupToggle.classList.add('active');
            loginToggle.classList.remove('active');
            signupForm.classList.add('active');
            loginForm.classList.remove('active');
        });

        switchToSignup.addEventListener('click', () => {
            signupToggle.click();
        });

        switchToLogin.addEventListener('click', () => {
            loginToggle.click();
        });
    </script>
</body>
</html>
