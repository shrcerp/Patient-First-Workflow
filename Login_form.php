<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jquery js  -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            min-height: 100vh;
        }

        .login-container {
            min-height: 100vh;
        }

        .login-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 30px;
        }

        .logo-container {
            text-align: center;
        }

        .logo-container img {
            max-height: 100px;
            width: auto;
        }

        .login-form {
            background: rgba(0, 143, 197, 0.25);
            padding: 40px;
            border-radius: 15px;
            border: 1px solid rgba(0, 143, 197, 0.25);
            max-width: 400px;
            width: 100%;
        }

        .login-form h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-weight: 600;
            font-size: 28px;
        }

        .form-label {
            color: #555;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid rgba(0, 143, 197, 0.2);
            border-radius: 8px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #008FC5;
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 0 0 3px rgba(0, 143, 197, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: #008FC5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .login-btn:hover {
            background: #007bb5;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 143, 197, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        /* Mobile responsiveness */
        @media (max-width: 576px) {
            .login-wrapper {
                gap: 20px;
                padding: 20px;
            }

            .logo-container img {
                max-height: 70px;
            }

            .login-form {
                padding: 30px 25px;
            }

            .login-form h2 {
                font-size: 24px;
                margin-bottom: 25px;
            }
        }

        @media (max-width: 375px) {
            .login-wrapper {
                gap: 15px;
                padding: 15px;
            }

            .logo-container img {
                max-height: 60px;
            }

            .login-form {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Main login container -->
    <div class="container-fluid login-container d-flex align-items-center justify-content-center">
        <div class="login-wrapper">
            <!-- Logo positioned above the login form -->
            <div class="logo-container">
                <img src="sarvodaya_logo.jpeg" alt="Logo" class="img-fluid">
            </div>

            <!-- Login form -->
            <div class="login-form">
                <h2>Login</h2>
                <form id="loginForm">
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input type="text" class="form-control" id="employee_id" placeholder="Enter your employee ID" name="employee_id" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" placeholder="Enter your password" name="password_hash" required>
                    </div>

                    <button type="submit" class="btn btn-primary login-btn">Login</button>
                </form>

                <!-- Login message -->
                <div id="loginMessage" class="mt-3"></div>
            </div>

        </div>
    </div>



    <!-- Login Script -->
    <script>
        $(document).ready(function() {
            $("#loginForm").on("submit", function(e) {
                e.preventDefault();

                let employee_id = $("#employee_id").val(); // updated to match input ID
                let password = $("#password").val();

                $.ajax({
                    url: "discharge_login_api.php",
                    type: "POST",
                    contentType: "application/json",
                    dataType: "json",
                    data: JSON.stringify({
                        employee_id: employee_id, // renamed from "email"
                        password_hash: password
                    }),
                    success: function(response) {
                        if (response.status === "success") {
                            localStorage.setItem("token", response.token);
                            localStorage.setItem("user", JSON.stringify(response.data));

                            window.location.href = "Discharge_Tracker_1.php";
                        } else {
                            $("#loginMessage").html('<div class="alert alert-danger">' + response.message + '</div>');
                        }
                    },
                    error: function(xhr) {
                        console.log("Error:", xhr.responseText);
                        $("#loginMessage").html('<div class="alert alert-danger">Server error: ' + xhr.responseText + '</div>');
                    }
                });
            });
        });
    </script>




    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>