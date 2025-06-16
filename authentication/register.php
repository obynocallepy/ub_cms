<?php
$registrationMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require '../server/config.php';

  $username = $link->real_escape_string(trim($_POST['username'] ?? ''));
  $email = $link->real_escape_string(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirm-password'] ?? '';
  $role = in_array($_POST['role'] ?? '', ['admin', 'user']) ? $_POST['role'] : '';

  if (!$username || !$email || !$password || !$confirmPassword || !$role) {
    $registrationMessage = "All fields are required.";
  } elseif ($password !== $confirmPassword) {
    $registrationMessage = "Passwords do not match.";
  } else {
    $check = $link->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
    if ($check->num_rows > 0) {
      $registrationMessage = "Username or email already exists.";
    } else {
      $passwordHash = password_hash($password, PASSWORD_BCRYPT);
      $sql = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$passwordHash', '$role')";
      if ($link->query($sql)) {
        header("Location: login.php?registered=1");
        exit;
      } else {
        $registrationMessage = "Registration failed. Please try again.";
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>University CMS - Register</title>
  <link rel="stylesheet" href="../css/admin-styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    .message-area {
      display: none;
      margin-bottom: 1rem;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 500;
      text-align: center;
      animation: fadeIn 0.4s ease-in;
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
  </style>
</head>

<body class="login-page">
  <div class="login-container">
    <div class="login-logo">
      <img src="/image/university logo.jpeg" alt="University Logo" />
    </div>

    <div class="login-form-container">
      <h1>Register</h1>

      <?php if ($registrationMessage): ?>
        <div class="message-area error"><?= htmlspecialchars($registrationMessage) ?></div>
      <?php endif; ?>

      <form id="registration-form" method="POST" action="register.php">
        <div class="form-group">
          <label for="reg-username">Username</label>
          <div class="input-with-icon">
            <i class="fas fa-user"></i>
            <input type="text" id="reg-username" name="username" required />
          </div>
        </div>

        <div class="form-group">
          <label for="reg-email">Email</label>
          <div class="input-with-icon">
            <i class="fas fa-envelope"></i>
            <input type="email" id="reg-email" name="email" required />
          </div>
        </div>

        <div class="form-group">
          <label for="reg-role">Select Role</label>
          <div class="input-with-icon">
            <i class="fas fa-users-cog"></i>
            <select id="reg-role" name="role" required>
              <option value="" disabled selected>Select role</option>
              <option value="admin">Admin</option>
              <option value="user">User</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="reg-password">Password</label>
          <div class="input-with-icon">
            <i class="fas fa-lock"></i>
            <input type="password" id="reg-password" name="password" required />
          </div>
        </div>

        <div class="form-group">
          <label for="reg-confirm-password">Confirm Password</label>
          <div class="input-with-icon">
            <i class="fas fa-lock"></i>
            <input type="password" id="reg-confirm-password" name="confirm-password" required />
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn primary full-width">Register</button>
        </div>

        <div class="forgot-password">
          Already have an account? <a href="login.php">Login here</a>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const form = document.getElementById("registration-form");
      const messageArea = document.querySelector(".message-area");

      form.addEventListener("submit", function (e) {
        const username = document.getElementById("reg-username").value.trim();
        const email = document.getElementById("reg-email").value.trim();
        const password = document.getElementById("reg-password").value;
        const confirmPassword = document.getElementById("reg-confirm-password").value;
        const role = document.getElementById("reg-role").value;

        // Clear message
        if (messageArea) {
          messageArea.style.display = "none";
          messageArea.textContent = "";
          messageArea.className = "message-area";
        }

        let error = "";
        if (!username || !email || !password || !confirmPassword || !role) {
          error = "All fields are required.";
        } else if (password !== confirmPassword) {
          error = "Passwords do not match.";
        }

        if (error) {
          e.preventDefault();
          displayMessage(error, "error");
        }
      });

      function displayMessage(message, type) {
        if (!messageArea) return;
        messageArea.textContent = message;
        messageArea.className = `message-area ${type}`;
        messageArea.style.display = "block";

        setTimeout(() => {
          messageArea.style.display = "none";
          messageArea.textContent = "";
          messageArea.className = "message-area";
        }, 5000);
      }
    });
  </script>
</body>

</html>
