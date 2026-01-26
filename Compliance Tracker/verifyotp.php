<?php
session_start();

// Check if the user is coming from a valid login attempt
if (!isset($_SESSION['otp']) || !isset($_SESSION['email'])) {
    $_SESSION['status'] = "Unauthorized access.";
    header("Location: bankfront.php");
    exit();
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $enteredOtp = trim($_POST['otp']);

    // Validate OTP format: must be a 6-digit number
    if (!preg_match('/^\d{6}$/', $enteredOtp)) {
        $_SESSION['status'] = "Invalid OTP format. Please enter a 6-digit number.";
        header("Location: verify_otp.php");
        exit();
    }

    // Verify the OTP
    if ($enteredOtp == $_SESSION['otp']) {
        unset($_SESSION['otp']); // Clear OTP after successful verification
        $_SESSION['status'] = "Logged in successfully";

        // Redirect to loggedin.php
        header("Location: loggedin.php");
        exit();
    } else {
        $_SESSION['status'] = "Invalid OTP. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - FrontBank</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header, footer {
            background-color: #007bff;
            color: white;
            padding: 1rem;
            text-align: center;
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .form-container {
            text-align: center;
        }

        table {
            margin: 0 auto;
            border-collapse: collapse;
            width: 100%;
            max-width: 500px;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
        }

        th, td {
            padding: 1rem;
            border: 1px solid #ccc;
        }

        th {
            background-color: #f0f0f0;
        }

        .btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 4px;
        }

        .btn:hover {
            background-color: #0052a3;
        }

        .error-message {
            color: red;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <h1>Verify Identity</h1>
        </div>
    </header>
    <main>
        <div class="form-container">
            <h2>Enter the 6-Digit Code</h2>
            <?php if (isset($_SESSION['status'])): ?>
                <p class="error-message"><?php echo htmlspecialchars($_SESSION['status']); ?></p>
                <?php unset($_SESSION['status']); ?>
            <?php endif; ?>
            <form action="verify_otp.php" method="POST">
                <table>
                    <thead>
                        <tr>
                            <th colspan="2">Verify Identity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><label for="otp">One-Time Password (OTP):</label></td>
                            <td><input type="text" id="otp" name="otp" maxlength="6" placeholder="Enter 6-digit code" required></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: center;">
                                <button type="submit" class="btn">Verify OTP</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </main>
    <footer>
        <p>&copy; 2023 FrontBank. All rights reserved.</p>
    </footer>
</body>
</html>
