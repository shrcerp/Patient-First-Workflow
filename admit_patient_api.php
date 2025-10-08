<?php
// Database connection
$conn = mysqli_connect('10.150.65.50', "sarvodaya", "sarvodaya", "mednet");

if (!$conn) {
    echo json_encode(["status" => "failed", "message" => "Database connection failed"]);
    exit;
}

$filter_for_patient = isset($_GET['panel_group']) ? $_GET['panel_group'] : null;

$sql = "SELECT 
    PD.FULL_NAME AS name,
    PD.MRN AS patient_code,
    HM.BILLING_CATEGORY AS category,
    CASE 
        WHEN HM.STATUS = 'ADMITTED' THEN 'Admitted'
        WHEN HM.STATUS = 'PREPARE_DISCHARGED' THEN 'Discharged'
        ELSE HM.STATUS
    END AS status,
    IF(TH.INSURANCE_PROVIDER_ID > 0, CPM.PANEL_GROUP_NAME, BPC.PANEL_GROUP_NAME) AS group_name
FROM mednet.HOSPITALIZATION_MASTER HM
JOIN mednet.TXN_HEADER TH ON TH.TXN_HEADER_ID = HM.TXN_HEADER_ID
JOIN mednetpatientdata.PATIENT_REGISTRATION PD ON PD.PATIENT_ID = HM.PATIENT_ID
LEFT JOIN mednet.CASHLESS_PROVIDER_MASTER CPM 
    ON CPM.PERSON_ID = TH.INSURANCE_PROVIDER_ID AND TH.COMPANY_ID = CPM.COMPANY_ID
LEFT JOIN mednet.BILLING_PATIENT_CATEGORY BPC 
    ON BPC.PATIENT_CATEGORY = TH.PATIENT_CATEGORY AND TH.COMPANY_ID = BPC.COMPANY_ID AND IFNULL(TH.INSURANCE_PROVIDER_ID, 0) = 0
WHERE HM.STATUS IN ('ADMITTED', 'PREPARE_DISCHARGED')";

$result = mysqli_query($conn, $sql);

if ($result) {
    $patients = [];

    while ($row = mysqli_fetch_assoc($result)) { 
        if ($filter_for_patient && strtolower($row['group_name']) !== strtolower($filter_for_patient)) {
            continue;
        }

        $patients[] = [
            'name' => $row['name'],
            "panel_group" => $row['group_name'],
            'patient_code' => $row['patient_code'],
            'category' => $row['category'],
            'status' => $row['status']
        ];
    }

    // echo "<pre>";
    // print_r($patients);
    echo json_encode(["status" => "success", "patients" => $patients]);
} else {
    echo json_encode(["status" => "failed", "message" => "Query execution failed", "error" => mysqli_error($conn)]);
}

mysqli_close($conn);
