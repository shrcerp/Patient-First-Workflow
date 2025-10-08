<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once "vendor/autoload.php";

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include("config.php");
session_start();

// Parse JSON input
$input = json_decode(file_get_contents("php://input"), true);
$action = $input['action'] ?? '';
$employee_id = $input['employee_id'] ?? '';
$password = $input['password_hash'] ?? '';

// ----------- SESSION EXPIRY CHECK -----------
if (isset($_SESSION['employee_id'], $_SESSION['expiry_time'])) {
    if (time() > $_SESSION['expiry_time']) {
        $expiredEmployeeID = $_SESSION['employee_id'];

        $update = "UPDATE auth_discharge_login_api SET is_Login=0 WHERE employee_id=?";
        $stmt = mysqli_prepare($conn, $update);
        mysqli_stmt_bind_param($stmt, "s", $expiredEmployeeID);
        mysqli_stmt_execute($stmt);

        session_unset();
        session_destroy();

        echo json_encode([
            "status" => "error",
            "message" => "Session expired. Please log in again."
        ]);
        exit;
    }
}

// ----------- LOGIN LOGIC -----------
if (!empty($employee_id) && !empty($password)) {
    $sql = "SELECT * FROM auth_discharge_login_api WHERE employee_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password_hash'])) {
            $expiry_time = time() + 3600;

            $payload = [
                "iss" => "localhost",
                "aud" => "localhost",
                "iat" => time(),
                "exp" => $expiry_time,
                "data" => [
                    "id" => $user['id'],
                    "employee_id" => $user['employee_id'],
                ]
            ];

            $jwt = JWT::encode($payload, $jwt_secret, 'HS256');

            $update = "UPDATE auth_discharge_login_api SET is_Login=1 WHERE employee_id=?";
            $stmt2 = mysqli_prepare($conn, $update);
            mysqli_stmt_bind_param($stmt2, "s", $employee_id);
            mysqli_stmt_execute($stmt2);

            $_SESSION['token'] = $jwt;
            $_SESSION['employee_id'] = $employee_id;
            $_SESSION['is_Login'] = 1;
            $_SESSION['expiry_time'] = $expiry_time;
            $_SESSION['user_id'] = $user['id'];

            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "data" => [
                    "id" => $user['id'],
                    "code" => 101,
                     "token" => $jwt 
                ]
            ]);
            exit;
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid password"]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }
}

// ----------- LOGOUT LOGIC -----------
elseif ($action === 'logout') {
    if (empty($employee_id)) {
        echo json_encode(["status" => "error", "message" => "Employee ID required for logout"]);
        exit;
    }

    $sql = "SELECT is_Login FROM auth_discharge_login_api WHERE employee_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if ($user['is_Login'] == 1) {
            $update = "UPDATE auth_discharge_login_api SET is_Login=0 WHERE employee_id=?";
            $stmt2 = mysqli_prepare($conn, $update);
            mysqli_stmt_bind_param($stmt2, "s", $employee_id);
            mysqli_stmt_execute($stmt2);

            session_unset();
            session_destroy();

            echo json_encode(["status" => "success", "message" => "Logout successful"]);
            exit;
        } else {
            echo json_encode(["status" => "error", "message" => "User already logged out"]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }
}

// ----------- INVALID REQUEST -----------
else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}
