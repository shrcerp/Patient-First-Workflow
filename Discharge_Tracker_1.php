<?php
session_start();
include("config.php");
if (!isset($_SESSION['employee_id'])) {
  header("Location: Login_form.php");
  exit;
}

if (!isset($_SESSION['is_Login']) || $_SESSION['is_Login'] == 0) {
  session_destroy();
  header("Location: Login_form.php");
  exit;
}

if (isset($_SESSION['expiry_time']) && time() > $_SESSION['expiry_time']) {
  session_unset();
  session_destroy();
  header("Location: Login_form.php");
  exit;
}

$userId = $_SESSION['user_id'];
$employee_id  = $_SESSION['employee_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Discharge Tracker</title>
  <link rel="stylesheet" href="./css/style_dischar_tracker.css" />
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


</head>

<body data-user-id="<?php echo $_SESSION['user_id']; ?>" data-employee-id="<?php echo $_SESSION['employee_id']; ?>">

  <div class="container-section">

    <!-- Header -->
    <div class="header">
      <h1>PATIENT FIRST WORKFLOW</h1>
      <div class="search-container">
        <input type="text" class="search-box" placeholder="Search Patient...">
        <button class="sh-button" id="dropdownToggle">SH</button>
        <ul class="dropdown-list" style="display: none;">
          <li><a href="#">Profile</a></li>
          <li><a href="#" id="logoutBtn">Logout</a></li>
        </ul>
      </div>
    </div>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner" style="display: none;">
      <div class="spinner"></div>
      <p>Loading patients...</p>
    </div>

    <!-- Ward Dropdown -->
    <select id="wardDropdown" style="width:100%;">
      <option class="ward-option" value="">All Wards</option>
    </select>

    <!-- Main Tabs -->
    <div class="tabs-section">
      <div class="main-tabs">
        <div class="main-tab" id="admitTab">
          <strong>Admit Patients</strong>
          <span class="notification-badge">0</span>
        </div>
        <div class="main-tab active" id="dischargeTab">
          <strong>Discharge Patients</strong>
          <span class="notification-badge">0</span>
        </div>
      </div>
    </div>

    <!-- Sub Tabs -->
    <div class="sub-tabs">
      <button id="openFilterPanel" class="filter-btn">+ Filter</button>
      <div class="sub-tab active" data-status="all">All</div>
      <div class="sub-tab" data-status="expected-delay">Est. Delay <span class="badge">0</span></div>
      <div class="sub-tab" data-status="delay">Delay <span class="badge">0</span></div>
      <div class="sub-tab" data-status="ongoing">Ongoing <span class="badge">0</span></div>
    </div>


    <!-- Drawer Filter Panel -->
    <div id="filterPanel" class="filter-panel">
      <h3>
        <span>Filters</span>
        <button class="close-btn" id="closeFilter">&times;</button>
      </h3>

      <div class="filter-container" style="display: flex;">
        <!-- Left Column: Headings -->
        <div class="filter-headings" style="flex: 1; display: flex; flex-direction: column; gap: 15px;">
          <div class="filter-option" data-target="#categoryOptions">Category</div>
          <div class="filter-option" data-target="#stageOptions">Stage</div>
        </div>

        <!-- Right Column: Options -->
        <div class="filter-options" style="flex: 2; position: relative;">
          <!-- Category Options -->
          <div class="option-list" id="categoryOptions">
            <div class="option-item" data-value="tpa">TPA</div>
            <div class="option-item" data-value="cash">Cash</div>
            <div class="option-item" data-value="CGHS CREDIT OFFLINE">CGHS CREDIT OFFLINE</div>
            <div class="option-item" data-value="CT CREDIT">CT CREDIT</div>
            <div class="option-item" data-value="Clinical Trials Credit">Clinical Trials Credit</div>
            <div class="option-item" data-value="PSU CREDIT">PSU CREDIT</div>
            <div class="option-item" data-value="NGO">NGO</div>
            <div class="option-item" data-value="COAL FIELD CREDIT">COAL FIELD CREDIT</div>
            <div class="option-item" data-value="NHM">NHM</div>
            <div class="option-item" data-value="ECHS">ECHS</div>
            <div class="option-item" data-value="ESI">ESI</div>
            <div class="option-item" data-value="RAILWAYS">RAILWAYS</div>
            <div class="option-item" data-value="CGHS PSU">CGHS PSU</div>
            <div class="option-item" data-value="HARYANA GOVT">HARYANA GOVT</div>
            <div class="option-item" data-value="CGHS">CGHS</div>
            <div class="option-item" data-value="Northern Railway">Northern Railway</div>
            <div class="option-item" data-value="CORPORATE">CORPORATE</div>
            <div class="option-item" data-value="PSU">PSU</div>
          </div>

          <!-- Stage Options -->
          <div class="option-list" id="stageOptions">
            <div class="option-item" data-value="Initiate">Initiate</div>
            <div class="option-item" data-value="Summary Finalise">Summary Finalise</div>
            <div class="option-item" data-value="Pharmacy">Pharmacy</div>
            <div class="option-item" data-value="Lab">Lab</div>
            <div class="option-item" data-value="Radiology">Radiology</div>
            <div class="option-item" data-value="File Sent">File Sent</div>
            <div class="option-item" data-value="File Recieve">File Recieve</div>
            <div class="option-item" data-value="Bill Lock">Bill Lock</div>
            <div class="option-item" data-value="Acknowledgement">Acknowledgement</div>
            <div class="option-item" data-value="Tpa Sent">TPA Sent</div>
            <div class="option-item" data-value="Tpa Recieve">TPA Recieve</div>
            <div class="option-item" data-value="Feedback">Feedback</div>
          </div>
        </div>
      </div>

      <button id="applyFilter" class="apply-btn">Apply Filter</button>
    </div>



    <!-- Loading Spinner -->
    <div id="loadingSpinner" style="display:none; text-align:center; margin:15px 0;">
      <div class="spinner"></div>
      <p>Loading patients...</p>
    </div>

    <!-- Admit Patients Table -->
    <div id="admitPatientsContainer" style="margin-top:15px; display:none;">
      <table id="admitPatientsTable" class="patient-table">
        <thead>
          <tr>
            <th>Sno</th>
            <th>Name of the Patient & IP</th>
            <th>Category</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <!-- API Data will be injected here -->
        </tbody>
      </table>
    </div>


    <!-- Overlay -->
    <div id="overlay" class="overlay"></div>

    <!-- Content for Discharge Patients -->
    <div class="content"><!-- Patient cards load here --></div>

    <!-- Hidden Template -->
    <div class="appointment-card" id="detailed-card" style="display:none;">
      <div class="patient-header">
        <div class="patient-info">
          <div class="patient-avatar">
            <img src="Discharge Tracker-icons/User Sarvodaya-icon.png" alt="avatar" style="width:15px;height:15px;" />
          </div>
          <div class="patient-details">
            <h3>Patient Name <span class="patient-id">(Code)</span></h3>
          </div>
        </div>
        <div class="status-badge">
          <div class="status-icon">
            <img src="Discharge Tracker-icons/time Sarvodaya-icon.png" alt="status" style="width:16px;height:16px;" />
          </div>
          <strong></strong>
        </div>
      </div>
      <div class="appointments"><!-- Timeline will come here --></div>
    </div>
  </div>


  <script src="discharge_app.js?v=<?php echo rand(1000, 9999); ?>"></script>
  <script>
    document.addEventListener("keydown", function(e) {
      if (e.key === "PrintScreen") {
        document.body.style.filter = "blur(10px)";
        setTimeout(() => document.body.style.filter = "none", 2000);
      }
    });

    document.addEventListener("keyup", function(e) {
      if (e.key === "PrintScreen") {
        navigator.clipboard.writeText("");
        alert("Screenshots are disabled on this site!");
      }
    });

    document.addEventListener('contextmenu', e => e.preventDefault());
    document.onkeydown = function(e) {
      if (e.keyCode == 123 || (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0))) {
        return false;
      }
    }


  </script>


</body>

</html>