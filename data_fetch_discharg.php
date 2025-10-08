<?php

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set("Asia/Calcutta");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

function get_pharmacy($ip_array)
{
    $host = "10.150.65.50";
    $dbname = "mednet";
    $username = "sarvodaya";
    $password = "sarvodaya";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database connection failed: " . $e->getMessage()]);
        exit;
    }

    if (empty($ip_array)) return json_encode([]);

    $placeholders = implode(',', array_fill(0, count($ip_array), '?'));

    $sql = "SELECT 
                REGN_NUMBER,
                REFERENCE_TYPE,
                PATIENT_NAME,
                PATIENT_AGE,
                MRN,
                REQUESTED_DATE,
                STATUS,
                PATIENT_CATEGORY,
                INDENT_NO,
                TXN_TYPE, 
                COUNT(*) AS nos 
            FROM mednet.PHARMACY_INDENT_MASTER 
            WHERE STATUS IN ('Requested', 'Partial_Medicine') 
              AND REGN_NUMBER IN ($placeholders)
            GROUP BY REGN_NUMBER";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ip_array);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Return associative array indexed by IP
    $result = [];
    foreach ($rows as $r) {
        $result[$r['REGN_NUMBER']] = $r;
    }

    return json_encode($result);
}

 function get_lab($ip_array)
{
    $host = "10.150.65.50";
    $dbname = "mednet";
    $username = "sarvodaya";
    $password = "sarvodaya";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database connection failed: " . $e->getMessage()]);
        exit;
    }

    if (empty($ip_array)) return json_encode([]);

    $placeholders = implode(',', array_fill(0, count($ip_array), '?'));

    $sql = "SELECT 
                HM.REGISTRATION_ID_BY_TOKEN,
                CASE 
                    WHEN PIL.REFERENCE_TYPE = 'HOSP' THEN 'IPD'
                    WHEN PIL.REFERENCE_TYPE = 'VISIT_NOTES' THEN 'OPD'
                    ELSE 'WALKIN'
                END AS 'PIL.REFERENCE_TYPE',
                GROUP_CONCAT(DISTINCT PIL.INVESTIGATION_NAME) AS pending_lab
            FROM mednetlab.PATIENT_INVESTIGATION_LEVEL1 PIL  
            JOIN mednet.HOSPITALIZATION_MASTER HM ON HM.TXN_HEADER_ID=PIL.TXN_HEADER_ID   
            WHERE PIL.STATUS IN('COLLECTED','REQUESTED') 
              AND PIL.REFERENCE_TYPE = 'HOSP' 
              AND PIL.DIAGNOSTICS_TYPE='LAB' 
              AND HM.REGISTRATION_ID_BY_TOKEN IN ($placeholders)
              AND PIL.IS_BED_SIDE_INVESTIGATION <> 'N'
            GROUP BY HM.REGISTRATION_ID_BY_TOKEN";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ip_array);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Map results by IP
    $result = [];
    foreach ($rows as $r) {
        $result[$r['REGISTRATION_ID_BY_TOKEN']] = $r;
    }

    return json_encode($result);
}

function get_radiology($ip_array)
{
    $host = "10.150.65.50";
    $dbname = "mednet";
    $username = "sarvodaya";
    $password = "sarvodaya";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database connection failed: " . $e->getMessage()]);
        exit;
    }

    if (empty($ip_array)) return json_encode([]);

    $placeholders = implode(',', array_fill(0, count($ip_array), '?'));

    $sql = "SELECT 
                HM.REGISTRATION_ID_BY_TOKEN,
                GROUP_CONCAT(DISTINCT PIL.INVESTIGATION_NAME) AS pending_radio,
                PIL.STATUS AS 'PIL.STATUS',
                PIL.DIAGNOSTIC_DEPARTMENT_NAME,
                LAB_NAME
            FROM mednetlab.PATIENT_INVESTIGATION_LEVEL1 PIL
            JOIN mednet.HOSPITALIZATION_MASTER HM ON HM.TXN_HEADER_ID = PIL.TXN_HEADER_ID
            WHERE PIL.DIAGNOSTICS_TYPE IN ('NON_LAB_WITH_PACS','NON_LAB')
              AND PIL.STATUS = 'REQUESTED'
              AND PIL.REFERENCE_TYPE = 'HOSP'
              AND PIL.DIAGNOSTIC_DEPARTMENT_NAME IN ('ULTRASOUND','X-RAY','CT SCAN','MRI')
              AND HM.REGISTRATION_ID_BY_TOKEN IN ($placeholders)
            GROUP BY HM.REGISTRATION_ID_BY_TOKEN";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ip_array);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Map results by IP
    $result = [];
    foreach ($rows as $r) {
        $result[$r['REGISTRATION_ID_BY_TOKEN']] = $r;
    }

    return json_encode($result);
}

// ------------------ Get Master Data ------------------
function get_master_data($conn)
{
    $sql = "SELECT * FROM discharge_stage ORDER BY position_key";
    $result = mysqli_query($conn, $sql);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}

$master_data = get_master_data($conn);

// ------------------ Main Logic ------------------
$response = ["status" => "error", "patients" => []];




$host = "10.150.65.50";
$dbname = "mednet";
$username = "sarvodaya";
$password = "sarvodaya";


// ------------------ DB Connection ------------------
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit;
}


try {
    $ward = isset($_POST['ward']) ? $_POST['ward'] : '';

    $sql = "
    SELECT  HM.TXN_HEADER_ID, TH.UPDATED_BY_NAME, TH.UPDATED_BY, HM.REGISTRATION_ID_BY_TOKEN AS IP, HM.STATUS, HM.HOSPITALIZATION_ID, PM.MRN, PM.FULL_NAME, PM.GENDER AS SEX, PM.MOBILE_PHONE_NO, HM.ADMITTANCE_DATE, IF(HM.DISCHARGE_INIT_DATE IS NULL, '', HM.DISCHARGE_INIT_DATE) AS DISCHARGE_INIT_DATE, HM.DISCHARGE_DATE, (TIMEDIFF(HM.DISCHARGE_DATE, HM.DISCHARGE_INIT_DATE)) AS planed_unplaned_time, IF((DATEDIFF(HM.DISCHARGE_DATE, HM.DISCHARGE_INIT_DATE)) = 0, 'U', 'P') AS planed_unplaned, IF((TIMEDIFF(HM.DISCHARGE_DATE, HM.DISCHARGE_INIT_DATE)) <= '12:00:00', 'U', 'P') AS planed_unplaned_t, PEI.DISCHARGE_PLANNED, HM.PRIMARY_DOCTOR, HM.DOCTOR_DEPARTMENT_NAME, HM.BILLING_CATEGORY, HM.WARD_NUMBER, HM.ROOM_NUMBER, HM.BED_NUMBER, WM.FLOOR, WM.WING, HM.BED_ID, HM.UPDATED_TIME, HM.RELEASE_BED_DATE, HM.RELEASE_BED_NAME, THB.FILE_SENT_TIME, THB.FILE_RECEIVED_BY, THB.FILE_RECEIVED_TIME, THB.BILL_LAST_LOCKED_BY, THB.BILL_LAST_LOCK_TIME, THB.DISCHARGE_SLIP_PRINTED_BY, THB.DISCHARGE_SLIP_PRINT_TIME, IF(TH.INSURANCE_PROVIDER_ID > 0, CPM.PANEL_GROUP_NAME, BPC.PANEL_GROUP_NAME) AS panel_group, IF(THB.IPD_CLOSE_TIME IS NULL, '', THB.IPD_CLOSE_TIME) AS IPD_CLOSE_TIME, IF(THB.FINAL_INS_BILL_SENT_TIME IS NULL, '', THB.FINAL_INS_BILL_SENT_TIME) AS FINAL_INS_BILL_SENT_TIME, IF(THB.FINAL_INS_BILL_APPROVE_TIME IS NULL, '', THB.FINAL_INS_BILL_APPROVE_TIME) AS FINAL_INS_BILL_APPROVE_TIME, IF(HM.RELEASE_BED_DATE IS NULL, '', HM.RELEASE_BED_DATE) AS RELEASE_BED_DATE, PEI.DISCHARGE_CONDITION AS dis_con, PEI.FINALIZED_DISCHARGE, PEI.DISCHARGE_PLANNED, PEI.DISCHARGE_SUMMARY_FINALIZED_DATE,
        CONCAT(
            FLOOR(HOUR(TIMEDIFF(HM.DISCHARGE_DATE, HM.ADMITTANCE_DATE)) / 24), 
            ' days ', 
            MOD(HOUR(TIMEDIFF(HM.DISCHARGE_DATE, HM.ADMITTANCE_DATE)), 24), 
            ' hours ', 
            MINUTE(TIMEDIFF(HM.DISCHARGE_DATE, HM.ADMITTANCE_DATE)), 
            ' minutes'
        ) AS FORMATTED_TAT,
        (HOUR(TIMEDIFF(HM.DISCHARGE_DATE, HM.ADMITTANCE_DATE))) AS hour_s
    FROM 
        mednet.HOSPITALIZATION_MASTER HM
    JOIN 
        mednet.TXN_HEADER TH ON TH.TXN_HEADER_ID = HM.TXN_HEADER_ID
    JOIN 
        mednetclinical.PATIENT_ENCOUNTER PE ON PE.HOSPITALIZATION_ID = HM.HOSPITALIZATION_ID
    JOIN 
        mednetclinical.PATIENT_ENCOUNTER_IPD PEI ON PEI.PATIENT_ENCOUNTER_ID = PE.PATIENT_ENCOUNTER_ID
    JOIN 
        mednet.WARD_MASTER WM ON HM.WARD_ID = WM.WARD_ID
    JOIN 
        mednetpatientdata.PATIENT_REGISTRATION PM ON HM.PATIENT_ID = PM.PATIENT_ID
    LEFT JOIN 
        mednet.TXN_HEADER_BILL_TRACKER THB ON HM.TXN_HEADER_ID = THB.TXN_HEADER_ID
    LEFT JOIN 
        mednet.CASHLESS_PROVIDER_MASTER CPM 
        ON CPM.PERSON_ID = TH.INSURANCE_PROVIDER_ID 
        AND TH.COMPANY_ID = CPM.COMPANY_ID 
        AND IFNULL(CPM.PERSON_ID, 0) > 0 
        AND IFNULL(TH.INSURANCE_PROVIDER_ID, 0) > 0
    LEFT JOIN 
        mednet.BILLING_PATIENT_CATEGORY BPC 
        ON BPC.PATIENT_CATEGORY = TH.PATIENT_CATEGORY 
        AND TH.COMPANY_ID = BPC.COMPANY_ID 
        AND IFNULL(TH.INSURANCE_PROVIDER_ID, 0) = 0
    WHERE 
        HM.STATUS = 'PREPARE_DISCHARGED' 
    ";

    if (!empty($ward)) {
        $sql .= " AND CONCAT(WM.FLOOR,'-',WM.WING) = :ward";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':ward', $ward);
    } else {
        $stmt = $pdo->prepare($sql);
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $time = date("Y-m-d H:i:s");

    $ip_array = [];

    if ($rows) {
        $patients = [];
        foreach ($rows as $row) {
            $ip_array[] = $row['IP'];
        }

        $pharmacyData  = json_decode(get_pharmacy($ip_array), true);
        $radiologyData = json_decode(get_radiology($ip_array), true);
        $labData       = json_decode(get_lab($ip_array), true);


        foreach ($rows as $row) {
            $ip = $row['IP'];
            $pharmacy  = $pharmacyData[$ip]  ?? [];
            $radiology = $radiologyData[$ip] ?? [];
            $lab       = $labData[$ip]       ?? [];

            $candidates = [];
            foreach ($master_data as $val) {
                $tracking = 0;
                $time_diff  = 0;
                $previous_stage_time = 0;
                $stage_time = 0;
                $stage_status = "Current";
                $stage_finish_time = "";

                if ($val["stage_key"]) {
                    $stage_finish_time = $row[$val["stage_key"]];
                    if (!$stage_finish_time) {
                        $stage_finish_time = "";
                    }
                }


                if ($val["previous_stage_key"] && $val["stage_key"]) {
                    //$stage_finish_time = $row[$val["stage_key"]];
                    if (!$row[$val["stage_key"]] && $row[$val["previous_stage_key"]]) {
                        $tracking = 1;
                        $previous_stage_time =  $row[$val["previous_stage_key"]];
                        $stage_time =  $time;
                        $time_diff = strtotime($time) - strtotime($row[$val["previous_stage_key"]]);
                        $time_diff = round($time_diff / 60);
                    } else {
                        if ($row[$val["stage_key"]] && $row[$val["previous_stage_key"]]) {

                            $stage_status = "Completed";
                            $time_diff = strtotime($row[$val["stage_key"]]) - strtotime($row[$val["previous_stage_key"]]);
                            $time_diff = round($time_diff / 60);
                            $previous_stage_time =  $row[$val["previous_stage_key"]];
                            $stage_time =  $row[$val["stage_key"]];
                        } else if (!$row[$val["previous_stage_key"]]) {
                            $stage_status = "Not Started";
                        }
                    }
                } else if (!$val["previous_stage_key"]) {
                    $stage_status = "Completed";
                }

                $status = "";
                if ($tracking == 1) {
                    if ($time_diff >=  $val["delay_time"]) {
                        $status = "Delay";
                    } else if ($time_diff >=  $val["expected_delay_time"]) {
                        $status = "Expected Delay";
                    } else {
                        $status = "Ongoing";
                    }
                } else {
                    if ($time_diff >  $val["delay_time"]) {
                        $status = "Delay";
                    } else {
                        $status = "Ongoing";
                    }
                }

                if ($val["stage_key"]) {
                    $candidates[] = [
                        "stage_name" => $val["stage_name"],
                        "created_at" => $val["stage_key"],
                        "previous_stage_key" => $val["previous_stage_key"],
                        "expected_delay_time" => $val["expected_delay_time"],
                        "delay_time" => $val["delay_time"],
                        "previous_stage_time" =>  $previous_stage_time,
                        "stage_time" =>  $stage_time,
                        "stage_finish_time" =>  $stage_finish_time,
                        "status" => $status,
                        "tracking" => $tracking,
                        "time" => $time,
                        "time_diff" => $time_diff,
                        "stage_status" => $stage_status,
                        "position_key" => $val['position_key']
                    ];
                }
            }


            $max_position_key = -1;
            foreach ($candidates as $stage) {
                if ($stage["stage_status"] === "Current" && $stage["position_key"] > $max_position_key) {
                    $max_position_key = $stage["position_key"];
                }
            }

            foreach ($candidates as &$stage) {
                if ($stage["position_key"] < $max_position_key) {
                    $stage["stage_status"] = "Completed";
                } elseif ($stage["position_key"] == $max_position_key) {
                    $stage["stage_status"] = "Current";
                }
            }


            $timeline = $candidates;
            $current_stage_names = [];

            $max_position_key = null;
            foreach ($timeline as $stage) {
                if ($stage["stage_status"] === "Current") {
                    if ($max_position_key === null || $stage["position_key"] > $max_position_key) {
                        $max_position_key = $stage["position_key"];
                    }
                }
            }
            foreach ($timeline as &$stage) {
                if ($stage["stage_status"] === "Current") {
                    if ($stage["position_key"] == $max_position_key) {
                        $current_stage_names[] = $stage['stage_name'];
                    }
                }
            }


            $current_stage = !empty($current_stage_names) ? implode(", ", array_unique($current_stage_names)) : "Initiate";
            $est_time = isset($stage_times[$current_stage]) ? $stage_times[$current_stage] : "00:30:00";
            $Mediclearance_Data = [
                "elements" => [
                    "radiology" => [
                        "pending_radio" => $radiology['pending_radio'] ?? null,
                    ],
                    "lab" => [
                        "pending" => $lab['pending_lab'] ?? null,

                    ],
                    "pharmacy" => [
                        "INDENT_NO" => $pharmacy['INDENT_NO'] ?? null,
                    ]
                ]

            ];

            $patients[] = [
                "patient_id"         => $row['HOSPITALIZATION_ID'],
                "patient_code"       => $row['MRN'],
                "patient_ip"       => $row['IP'],
                "patient_name"       => $row['FULL_NAME'],
                "gender"             => $row['SEX'],
                "mobile"             => $row['MOBILE_PHONE_NO'],
                "doctor"             => $row['PRIMARY_DOCTOR'],
                "department"         => $row['DOCTOR_DEPARTMENT_NAME'],
                "ward"               => $row['WARD_NUMBER'],
                "room"               => $row['ROOM_NUMBER'],
                "bed"                => $row['BED_NUMBER'],
                "floor"              => $row['FLOOR'],
                "wing"               => $row['WING'],
                "billing_category"   => $row['BILLING_CATEGORY'],
                "panel_group"        => $row['panel_group'],
                "admit_date"         => $row['ADMITTANCE_DATE'],
                "discharge_date"     => $row['DISCHARGE_DATE'],
                "planned_unplanned"  => ($row['planed_unplaned'] === 'U' ? 'Unplanned' : 'Planned'),
                "planned_unplanned_t" => ($row['planed_unplaned_t'] === 'U' ? 'Unplanned' : 'Planned'),
                "discharge_condition" => $row['dis_con'],
                "tat"                => $row['FORMATTED_TAT'],
                "hour_s"             => $row['hour_s'],
                "discharge_status"   => [[
                    "category"      => $row['BILLING_CATEGORY'],
                    "pu_status"     => ($row['planed_unplaned'] === 'U' ? 'Unplanned' : 'Planned'),
                    "current_stage" => trim(implode(',', array_unique(array_filter([
                        $current_stage,
                        $pharmacy['INDENT_NO'] ?? null,
                        $radiology['pending_radio'] ?? null,
                        $lab['pending_lab'] ?? null
                    ])))),

                    "est_time"      => $est_time
                ]],
                "timeline"           => $timeline,
                "Mediclearance_Data"             => $Mediclearance_Data
            ];
        }
        $response['status'] = "success";
        $response['patients'] = $patients;
        $response['stage_times'] = $stage_times ?? [];
    } else {
        $response['message'] = "No records found";
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
