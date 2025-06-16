<?php
session_start();
// Correct path to config.php, assuming it's in the same directory as login.php
require_once '../server/config.php';

$loginMessage = "";



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $identity = trim($_POST['identity'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($identity) || empty($password)) {
    $loginMessage = "Please enter both username/email and password.";
  } else {
    // Use $link for database connection, as defined in config.php
    $stmt = $link->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $identity, $identity);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
      if (password_verify($password, $user['password'])) {
        // Password is correct, set session variables
        $_SESSION['loggedin'] = true; // Set this for consistency with auth.php
        $_SESSION['id'] = $user['id']; // Store user ID
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Only redirect to admin panel if the user has 'admin' role
        if ($user['role'] === 'admin') {
          header("Location: ../admin.php"); // Redirect to the main admin panel
          exit;
        } else if ($user['role'] === 'user') {
          header("Location: ../index.php"); // Redirect to the main admin panel
          exit;
        } else {
          // If not an admin, destroy session and show an error
          session_destroy(); // Clear session for non-admin attempts
          $loginMessage = "You do not have administrative privileges to access this panel.";
        }
      } else {
        $loginMessage = "Incorrect password.";
      }
    } else {
      $loginMessage = "No user found with that username or email.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>University CMS - Login</title>
  <link rel="stylesheet" href="../css/admin-styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    /* Ensure these styles are correctly linked or embedded */
    .message-area {
      display: none;
      margin-bottom: 1rem;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 500;
      text-align: center;
      transition: all 0.3s ease-in-out;
    }

    .message-area.error {
      display: block;
      background-color: #ffe6e6;
      color: #d8000c;
      border: 1px solid #d8000c;
      box-shadow: 0 0 5px rgba(216, 0, 12, 0.2);
    }

    .message-area.success {
      display: block;
      background-color: #e6ffea;
      color: #1d7a32;
      border: 1px solid #1d7a32;
      box-shadow: 0 0 5px rgba(29, 122, 50, 0.2);
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-5px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .message-area {
      animation: fadeIn 0.4s ease-in;
    }

    /* Additional styles from previous response for login page appearance */
    body.login-page {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background-color: #f8f9fa;
      margin: 0;
      /* Ensure no default body margin */
    }

    .login-container {
      background-color: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
      text-align: center;
      /* Center content within the container */
    }

    .login-logo img {
      max-width: 150px;
      /* Adjust as needed */
      height: auto;
      margin-bottom: 20px;
    }

    .login-form-container h1 {
      margin-bottom: 20px;
      font-size: 1.8rem;
      color: #333;
    }

    .form-group {
      margin-bottom: 15px;
      text-align: left;
      /* Align form labels/inputs left */
    }

    .input-with-icon {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-with-icon i {
      position: absolute;
      left: 10px;
      color: #aaa;
    }

    .input-with-icon input {
      width: 100%;
      padding: 10px 10px 10px 35px;
      /* Adjust padding for icon */
      border: 1px solid #ddd;
      border-radius: 5px;
    }

    .remember-me {
      display: flex;
      align-items: center;
      margin-top: 15px;
    }

    .remember-me input[type="checkbox"] {
      margin-right: 8px;
    }

    .form-actions {
      margin-top: 20px;
    }

    .btn.primary.full-width {
      width: 100%;
      padding: 10px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1.1rem;
    }

    .btn.primary.full-width:hover {
      background-color: #0056b3;
    }

    .forgot-password {
      margin-top: 15px;
      font-size: 0.9rem;
    }

    .forgot-password a {
      color: #007bff;
      text-decoration: none;
    }

    .forgot-password a:hover {
      text-decoration: underline;
    }
  </style>


</head>

<body class="login-page">
  <div class="login-container">
    <div class="login-logo">
      <img src="image/university logo.jpeg" alt="University Logo" />
    </div>

    <div class="login-form-container">
      <h1>Admin Login</h1>
      <?php if ($loginMessage): ?>
        <div class="message-area error"><?= htmlspecialchars($loginMessage) ?></div>
      <?php endif; ?>

      <form id="login-form" method="POST" action="login.php">
        <div class="form-group">
          <label for="identity">Username or Email</label>
          <div class="input-with-icon">
            <i class="fas fa-user"></i>
            <input type="text" id="identity" name="identity" required />
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-with-icon">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" required />
          </div>
        </div>

        <div class="form-group remember-me">
          <input type="checkbox" id="remember" name="remember" />
          <label for="remember">Remember me</label>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn primary full-width">Login</button>
        </div>

        <div class="forgot-password">
          <a href="#">Forgot Password?</a>
        </div>
      </form>
    </div>
  </div>
</body>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("login-form");

    const messageArea = document.querySelector(".message-area");

    form.addEventListener("submit", function (e) {
      const identity = document.getElementById("identity").value.trim();
      const password = document.getElementById("password").value;

      // Clear previous messages
      if (messageArea) {
        messageArea.style.display = "none";
        messageArea.textContent = "";
        messageArea.className = "message-area";
      }

      let error = "";

      if (!identity || !password) {
        error = "Please fill in both Username/Email and Password.";
      }

      if (error) {
        e.preventDefault(); // Stop form submission
        displayMessage(error, "error");
      }
    });

    function displayMessage(message, type) {
      if (!messageArea) return;
      messageArea.textContent = message;
      messageArea.className = `message-area ${type}`;
      messageArea.style.display = "block";

      // This setTimeout is for client-side messages.
      // If the page reloads due to PHP processing, the PHP-generated message will take precedence.
      setTimeout(() => {
        messageArea.style.display = "none";
        messageArea.textContent = "";
        messageArea.className = "message-area";
      }, 5000);
    }
  });
</script>

</html>