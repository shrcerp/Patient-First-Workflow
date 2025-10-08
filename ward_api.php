<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$hospital_id = 1;
if(isset($_GET["dev"])){
}else{
    if(isset($_SESSION['hospital_id'])){
        $hospital_id = $_SESSION['hospital_id'];
    }
}

if(!isset($_SESSION['user_id'])){
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}

$employee_id = $_SESSION['user_id'];

if($hospital_id == 1){
    $host = "10.150.65.50";
    $dbname = "mednet";
    $username = "sarvodaya";
    $password = "sarvodaya";
}else if($hospital_id == 2){
    $host = "10.0.27.43";
    $dbname = "mednet";
    $username = "sarvodaya";
    $password = "sarvodaya";
} 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Hospital DB connection failed: ".$e->getMessage()
    ]);
    exit;
}

include("config.php");
$sql1 = "SELECT ward_id FROM cordinator_mapping WHERE employee_id = '$employee_id'";
$result1 = mysqli_query($conn, $sql1);
$row1 = mysqli_fetch_assoc($result1);

if(!$row1 || empty($row1['ward_id'])){
    echo json_encode([
        "status" => "success",
        "wards" => []
    ]);
    exit;
}

$ward_ids = explode(',', $row1['ward_id']);
$ward_ids_str = implode(',', $ward_ids);

$sql2 = "SELECT ward_name FROM master_ward WHERE id IN ($ward_ids_str)";
$result2 = mysqli_query($conn, $sql2);

$wards = [];
while($row2 = mysqli_fetch_assoc($result2)){
    $wards[] = $row2['ward_name'];
}

echo json_encode([
    "status" => "success",
    "wards" => $wards
]);
