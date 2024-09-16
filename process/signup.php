<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sign Up</title>
    <link rel="stylesheet" href="process.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            text-align: center;
        }
        .modal-success {
            background-color: #d4edda;
            color: #155724;
        }
        .modal-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'process-nav.php'; ?>
    <div class="container1">
        <h2>User Sign Up</h2>
        <form action="signup.php" method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirm Password:</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>
            </div>
            <button type="submit" name="signup">Sign Up</button>
        </form>
        <p id="error-message" style="color: red;"></p>
    </div>
    <?php
    if (isset($_POST['signup'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];

        if ($password !== $confirmPassword) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showPopup('Passwords do not match.', 'error');
                });
            </script>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $conn = mysqli_connect("localhost", "root", "", "cinemadb");

            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }

            $checkUser = "SELECT * FROM users WHERE username='$username' OR email='$email'";
            $result = mysqli_query($conn, $checkUser);

            if (mysqli_num_rows($result) > 0) {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showPopup('Username or Email is already in use.', 'error');
                    });
                </script>";
            } else {
                $sql = "INSERT INTO users (username, email, password_hash) VALUES ('$username', '$email', '$hashedPassword')";
                if (mysqli_query($conn, $sql)) {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showPopup('Sign up successful!<br>Username: $username<br>Email: $email', 'success');
                        });
                    </script>";
                } else {
                    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
                }
            }

            mysqli_close($conn);
        }
    }
    ?>

    <div id="popupModal" class="modal">
        <div id="popupContent" class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <p id="popup-message"></p>
            <button onclick="closeModal()">Okay</button>
        </div>
    </div>

    <script>
        function validateForm() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirmPassword").value;

            if (password !== confirmPassword) {
                document.getElementById("error-message").textContent = "Passwords do not match.";
                return false;
            } else {
                document.getElementById("error-message").textContent = "";
                return true;
            }
        }

        function showPopup(message, type) {
            var modal = document.getElementById("popupModal");
            var content = document.getElementById("popupContent");
            document.getElementById("popup-message").innerHTML = message;
            if (type === 'success') {
                content.classList.add('modal-success');
                content.classList.remove('modal-error');
            } else {
                content.classList.add('modal-error');
                content.classList.remove('modal-success');
            }
            modal.style.display = "block";
        }

        function closeModal() {
            var modal = document.getElementById("popupModal");
            modal.style.display = "none";
            if (document.getElementById("popup-message").innerHTML.includes("Sign up successful")) {
                window.location.href = "login.php";
            }
        }
    </script>
</body>
</html>
