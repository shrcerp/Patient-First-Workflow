$(document).ready(function () {
  const employee_id = document.body.dataset.employeeId;

  let allPatients = [];
  let selectedFilter = "all";

  // Auto Refresh Every 5 Minutes
  setInterval(() => {
    const currentWard = $("#wardDropdown").val() || "";
    fetchPatients(currentWard, selectedFilter);
  }, 300000);

  // Search Filter
  $(".search-box").on("input", function () {
    const query = $(this).val().toLowerCase();
    if ($(".main-tab.active").text().includes("Admit Patients")) {
      $("#admitPatientsTable tbody tr").each(function () {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.includes(query));
      });
    } else {
      $(".patient-card, .appointment-card").each(function () {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.includes(query));
      });
    }
  });

  // Dropdown & Logout
  $("#dropdownToggle").click((e) => {
    e.stopPropagation();
    $(".dropdown-list").toggle();
  });

  $(document).click(() => $(".dropdown-list").hide());
  $("#logoutBtn").click((e) => {
    e.preventDefault();
    e.stopPropagation();
    $.ajax({
      url: "https://app.sarvodayahospital.com/patient_first_workflow/discharge_login_api.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({
        employee_id: employee_id,
        action: "logout",
      }),
      success: () => {
        alert("Logged out!");
        window.location.href = "Login_form.php";
      },
      error: () => alert("Logout failed."),
    });
  });

  // Ward Dropdown Fetch
  function fetchWards() {
    $.ajax({
      url: "https://app.sarvodayahospital.com/patient_first_workflow/ward_api.php",
      method: "POST",
      dataType: "json",
      success: function (response) {
        const wardDropdown = $("#wardDropdown");
        wardDropdown.empty();
        wardDropdown.append('<option value="">All Wards</option>');
        if (
          response.status === "success" &&
          response.wards &&
          response.wards.length > 0
        ) {
          response.wards.forEach(function (ward) {
            wardDropdown.append(`<option value="${ward}">${ward}</option>`);
          });
        }
      },
      error: function () {
        console.error("Ward API failed");
      },
    });
  }
  fetchWards();

  // Ward Dropdown Change
  $("#wardDropdown").on("change", function () {
    const selectedWard = $(this).val();
    fetchPatients(selectedWard, selectedFilter);
  });

  // Fetch Patients
  function fetchPatients(selectedWard = "", filter = selectedFilter) {
    $("#loadingSpinner").fadeIn(200);
    $(".content");

    $.ajax({
      url: "data_fetch_discharg.php",
      method: "POST",
      data: {
        ward: selectedWard,
      },
      dataType: "json",
      success: function (response) {
        $("#loadingSpinner").fadeOut(200);
        $(".content").css("opacity", "1");
        if (response.status === "success") {
          allPatients = response.patients;
          renderPatients(allPatients, filter);
        } else {
          $(".content").html("<p>No patients found.</p>");
        }
      },
      error: function () {
        $("#loadingSpinner").fadeOut(200);
        $(".content").css("opacity", "1");
        $(".content").html("<p>API call failed.</p>");
      },
    });
  }

  // Render Patients
  function renderPatients(patients, filter = "all") {
    const container = $(".content");
    container.empty();
    let expectedDelay = 0,
      delay = 0,
      ongoing = 0;

    patients.forEach((patient) => {
      const statusClass = getCurrentStageStatus(patient.timeline)
        .toLowerCase()
        .replace(/\s+/g, "-");
      if (statusClass === "expected-delay") expectedDelay++;
      else if (statusClass === "delay") delay++;
      else ongoing++;
    });

    $(".sub-tab[data-status='expected-delay'] .badge").text(expectedDelay);
    $(".sub-tab[data-status='delay'] .badge").text(delay);
    $(".sub-tab[data-status='ongoing'] .badge").text(ongoing);
    $(".main-tab:contains('Discharge Patients') .notification-badge").text(
      patients.length
    );

    const filteredPatients = patients.filter((patient) => {
      const statusClass = getCurrentStageStatus(patient.timeline)
        .toLowerCase()
        .replace(/\s+/g, "-");
      return filter === "all" || statusClass === filter;
    });

    filteredPatients.forEach((patient, index) => {
      const status = getCurrentStageStatus(patient.timeline);
      const statusClass = status.toLowerCase().replace(/\s+/g, "-");
      const discharge = patient.discharge_status[0] || {};

      const card = $(`
            <div class="patient-card ${statusClass}" 
                 data-index="${index}" 
                 data-patient-id="${patient.patient_id}" 
                 data-expanded="false"
                 data-ward="${patient.ward}"> 
                <div class="patient-header">
                    <div class="patient-info">
                        <div class="patient-avatar"><img src="Discharge Tracker-icons/User Sarvodaya-icon.png" style="width:15px;height:15px;"/></div>
                        <div class="patient-details"><h3>${patient.patient_name
        } <span class="patient-id">(${patient.patient_code
        })</span></h3></div>
                    </div>
                    <div class="status-badge ${statusClass}">
                        <div class="status-icon"><img src="Discharge Tracker-icons/time Sarvodaya-icon.png" style="width:16px;height:16px;"/></div>
                        <strong>${status}</strong>
                    </div>
                </div>
                <div class="patient-details-section">
                    <div class="detail-item1">
                        <span class="detail-label"><strong>Category</strong></span><span>:</span>
                        <span class="detail-value">${discharge.category || "-"
        }</span>
                    </div>
                    <div class="detail-item2">
                        <span class="detail-label"><strong>P/U</strong></span><span>:</span>
                        <span class="detail-value">${discharge.pu_status || "-"
        }</span>
                    </div>
                    <div class="detail-item1">
                        <span class="detail-label"><strong>Current Stage</strong></span><span>:</span>
                        <span class="detail-value">${discharge.current_stage || "-"
        }</span>
                    </div>
                    <div class="detail-item2">
                        <span class="detail-label"><strong>Est. Time</strong></span><span>:</span>
                        <span class="detail-value">${getCurrentStageDelayTime(
          patient.timeline
        )} min</span>
                    </div>
                </div>
            </div>
        `);

      container.append(card);
    });

    addClickListeners(filteredPatients);
  }

  // Get Current Stage Status
  function getCurrentStageStatus(timeline) {
    const currentStage = timeline.find((t) => t.stage_status === "Current");
    if (currentStage) return currentStage.status;
    return timeline[timeline.length - 1]?.status || "On Going";
  }

  // Get Current Stage Delay Time
  function getCurrentStageDelayTime(timeline) {
    const currentStage = timeline.find((t) => t.stage_status === "Current");
    if (currentStage) return currentStage.delay_time || "-";
    return timeline[timeline.length - 1]?.delay_time || "-";
  }

  // Click Listeners
  function addClickListeners(patients) {
    $(".patient-card, .appointment-card")
      .off("click")
      .on("click", function (e) {
        const appointmentDiv = $(e.target).closest(".appointment");
        if (appointmentDiv.length) {
          const stageName = appointmentDiv.find("h4").text().toLowerCase();
          if (["lab", "radiology", "pharmacy"].includes(stageName)) {
            return;
          }
        }

        // ---------- Close tooltip if open ----------
        if (currentTooltip) {
          currentTooltip.remove();
          currentTooltip = null;
          currentTooltipStage = null;
        }

        e.stopPropagation();

        const clickedCard = $(this);
        const index = parseInt(clickedCard.data("index"));
        const patient = patients[index];
        const isExpanded = clickedCard.attr("data-expanded") === "true";

        if (isExpanded) {
          renderPatients(allPatients, selectedFilter);
        } else {
          const detailedCard = buildDetailedCard(
            clickedCard,
            patient,
            patient.timeline,
            index
          );
          detailedCard.attr("data-expanded", "true");
          addClickListeners(patients);
        }
      });
  }

  // Build Detailed Card
  function buildDetailedCard(card, patient, timeline, index) {
    const detailedCard = $("#detailed-card").clone();
    detailedCard
      .removeAttr("id")
      .addClass("appointment-card")
      .css("display", "block");
    detailedCard
      .attr("data-patient-id", patient.patient_id)
      .attr("data-index", index);
    detailedCard
      .find("h3")
      .html(
        `${patient.patient_name} <span class="patient-id">(${patient.patient_code})</span>`
      );

    // ---------- Status Badge ----------
    const status = getCurrentStageStatus(timeline);
    const statusClass = status.toLowerCase().replace(" ", "-");
    const badgeDiv = detailedCard.find(".status-badge");
    badgeDiv.removeClass("expected-delay delay ongoing").addClass(statusClass);
    badgeDiv.find("strong").text(status);

    // ---------- Timeline Div ----------
    const timelineDiv = detailedCard.find(".appointments");
    timelineDiv.empty();

    const stages = [
      "Initiate",
      "Summary Finalise",
      "Pharmacy",
      "Lab",
      "Radiology",
      "File Sent",
      "File Recieve",
      "Bill Lock",
      "Acknowledgement",
      "Tpa Sent",
      "Tpa Recieve",
      "Feedback",
    ];

    const medData = patient.Mediclearance_Data?.elements || {};
    const hasPharmacyData = medData.pharmacy && medData.pharmacy.INDENT_NO;
    const hasLabData = medData.lab && medData.lab.pending;
    const hasRadiologyData =
      medData.radiology && medData.radiology.pending_radio;
    const hoverStages = ["lab", "radiology", "pharmacy"];

    stages.forEach((stageName, i) => {
      const lowerStage = stageName.toLowerCase();

      // ---------- Lab, Radiology, Pharmacy show only if actual data exists ----------
      if (hoverStages.includes(lowerStage)) {
        const showStage =
          (lowerStage === "lab" && hasLabData) ||
          (lowerStage === "radiology" && hasRadiologyData) ||
          (lowerStage === "pharmacy" && hasPharmacyData);
        if (!showStage) return;
      } else {
        // ---------- Other stages: skip if Not Started ----------
        const dbStageCheck = timeline.find(
          (t) => t.stage_name.trim().toLowerCase() === lowerStage
        );
        if (dbStageCheck?.stage_status === "Not Started") return;
      }

      const dbStage = timeline.find(
        (t) => t.stage_name.trim().toLowerCase() === lowerStage
      );

      let color = "transparent";
      let stageClass = "";

      if (dbStage) {
        if (dbStage.stage_status === "Completed") {
          color = "#4caf50"; // Green for completed
          stageClass = "completed";
        } else if (dbStage.stage_status === "Current") {
          // Current stage: use status color
          switch (dbStage.status?.toLowerCase()) {
            case "delay":
              color = "#e31936";
              stageClass = "delay";
              break;
            case "expected delay":
              color = "#ff9800";
              stageClass = "expected-delay";
              break;
            case "ongoing":
              color = "#4caf50";
              stageClass = "ongoing";
              break;
          }
        }
      }

      const nextStage = stages[i + 1]
        ? timeline.find(
          (t) =>
            t.stage_name.trim().toLowerCase() ===
            stages[i + 1].trim().toLowerCase()
        )
        : null;

      if (nextStage?.stage_status && nextStage.stage_status !== "Not Started") {
        switch (nextStage.status?.toLowerCase()) {
          case "delay":
            stageClass += " from-delay";
            break;
          case "expected delay":
            stageClass += " from-expected-delay";
            break;
          case "ongoing":
            stageClass += " from-ongoing";
            break;
        }
      }

      const isHoverStage = hoverStages.includes(lowerStage);

      // ---------- Prepare tooltip content ----------
      let tooltipContent = "";
      if (isHoverStage) {
        if (lowerStage === "lab" && hasLabData) {
          tooltipContent = `Pending: ${medData.lab.pending}`;
        }
        if (lowerStage === "pharmacy" && hasPharmacyData) {
          tooltipContent = `Indent No: ${medData.pharmacy.INDENT_NO}`;
        }
        if (lowerStage === "radiology" && hasRadiologyData) {
          tooltipContent = `Pending: ${medData.radiology.pending_radio}`;
        }
      }
      tooltipContent = tooltipContent.replace(/"/g, "&quot;");

      // ---------- Append timeline card ----------
      timelineDiv.append(`
  <div class="appointment ${stageClass}" data-set="${stageName}">
  ${isHoverStage ? `<div class="static-tooltip">${tooltipContent}</div>` : ""}

      <div class="appointment-icon">
          <img class="icon-cercul" src="Discharge Tracker-icons/${i + 1}.${stageName}.png"
               style="width:25px;height:25px; border:2px solid ${color}; border-radius:50%;"> 
      </div>
      <h4>${stageName}</h4>
      <div class="appointment-date" >${dbStage?.stage_finish_time || "-"}</div>
      ${!["pharmacy", "lab", "radiology"].includes(lowerStage)
          ? `<div class="appointment-time">Est: ${dbStage?.delay_time || "-"} | Time Taken: ${dbStage?.time_diff || "0"}min</div>`
          : ""
        }
  </div>
`);

    });

    card.replaceWith(detailedCard);
    return detailedCard;
  }

  // ---------- Hover tooltip ----------
  let currentTooltip = null;
  let currentTooltipStage = null;

  $(document).on("click", ".appointment", function (e) {
    const stageName = $(this).find("h4").text().toLowerCase();

    if (["lab", "radiology", "pharmacy"].includes(stageName)) {
      e.stopPropagation();
      e.preventDefault();

      if (currentTooltipStage === stageName) {
        if (currentTooltip) {
          currentTooltip.remove();
          currentTooltip = null;
          currentTooltipStage = null;
          return;
        }
      }

      if (currentTooltip) {
        currentTooltip.remove();
        currentTooltip = null;
        currentTooltipStage = null;
      }

      const tooltipContent = $(this).find(".static-tooltip").html();
      if (!tooltipContent) return;

      const tooltip = $(`<div class="tooltip-popup">${tooltipContent}</div>`);
      $("body").append(tooltip);

      tooltip.css({
        top: $(this).offset().top - tooltip.outerHeight() - 10,
        left: $(this).offset().left,
        position: "absolute",
        background: "#fff",
        border: "1px solid #ccc",
        padding: "5px",
        "z-index": 9999,
        "white-space": "pre-wrap",
        "max-width": "300px",
        "box-shadow": "0 0 10px rgba(0,0,0,0.1)",
        "border-radius": "4px",
      });

      currentTooltip = tooltip;
      currentTooltipStage = stageName;

    }
  });

  // Clicking anywhere else removes the tooltip
  $(document).on("click", function (e) {
    if (
      currentTooltip &&
      !$(e.target).closest(".tooltip-popup, .appointment").length
    ) {
      currentTooltip.remove();
      currentTooltip = null;
      currentTooltipStage = null;
    }
  });

 
  // Status Filter Tabs
  $(".sub-tab").on("click", function () {
    $(".sub-tab").removeClass("active");
    $(this).addClass("active");
    selectedFilter = $(this).data("status");
    renderPatients(allPatients, selectedFilter);
  });

  // Initial Fetch
  fetchPatients();

  // ----------------- Switch Discharge Patients -----------------
  $(".main-tab:contains('Discharge Patients')").on("click", function () {
    $(".main-tab").removeClass("active");
    $(this).addClass("active");
    $("#admitPatientsContainer").hide();
    $(".content").show();
    fetchPatients();
  });

  // ----------------- Admit Patients -----------------
  $("#admitTab").on("click", function () {
    $(".main-tab").removeClass("active");
    $(this).addClass("active");

    $(".content").hide();
    $("#admitPatientsContainer").show();
    $("#loadingSpinner").show();

    const selectedCategories = $("#categoryFilter").val() || [];

    $.ajax({
      url: "https://app.sarvodayahospital.com/patient_first_workflow/admit_patient_api.php",
      method: "GET",
      dataType: "json",
      success: function (response) {
        $("#loadingSpinner").hide();
        const tbody = $("#admitPatientsTable tbody");
        tbody.empty();
        tbody.append("<tr><td colspan='4'>Coming Soon</td></tr>");
        return;
        if (response.status === "success" && response.patients?.length > 0) {
          let patientsToShow = response.patients;

          if (selectedCategories.length > 0) {
            patientsToShow = patientsToShow.filter((patient) =>
              selectedCategories.some((cat) =>
                patient.category.toLowerCase().includes(cat.toLowerCase())
              )
            );
          }

          patientsToShow.forEach((patient, index) => {
            let row = `
            <tr>
              <td>${index + 1}</td>
              <td>${patient.name} (${patient.patient_code})</td>
              <td>${patient.panel_group}</td>
              <td>${patient.status}</td>
            </tr>
          `;
            tbody.append(row);
          });

          $(".main-tab:contains('Admit Patients') .notification-badge").text(
            patientsToShow.length
          );
        } else {
          tbody.append("<tr><td colspan='4'>No patients found</td></tr>");
        }
      },
      error: function () {
        $("#loadingSpinner").hide();
        alert("Failed to fetch data from API.");
      },
    });
  });

  // Show Category list by default
  $("#categoryOptions").show();
  // Open/Close Drawer
  $("#openFilterPanel").click(() => $("#filterPanel").addClass("active"));
  $("#closeFilter").click(() => $("#filterPanel").removeClass("active"));

  // Show options on heading click
 $(".filter-headings .filter-option").click(function () {
    let target = $(this).data("target");
    $(".option-list").not(target).hide();
    $(target).show();  
});

  // Select/Unselect options
  $(".option-item").click(function () {
    $(this).toggleClass("active");
  });

  // Apply Filter
  $("#applyFilter").click(function () {
    const selectedCategories = $("#categoryOptions .option-item.active")
      .map((i, el) => $(el).data("value"))
      .get();
    const selectedStages = $("#stageOptions .option-item.active")
      .map((i, el) => $(el).data("value"))
      .get();

    // Trigger tabs if needed
    if ($("#admitTab").hasClass("active")) $("#admitTab").trigger("click");
    if ($(".main-tab:contains('Discharge Patients')").hasClass("active")) {
      filterDischargePatients(selectedStages, selectedCategories);
    }

    $("#filterPanel").removeClass("active");
    $("#overlay").removeClass("active");
  });

  // ----------------- Discharge Patients Filter -----------------
  function filterDischargePatients(
    selectedStages = [],
    selectedCategories = []
  ) {
    let filteredPatients = [...allPatients];

    // Category filter
    if (selectedCategories.length > 0) {
      const catLowerArr = selectedCategories.map((c) => c.toLowerCase());
      filteredPatients = filteredPatients.filter((patient) => {
        const cat = (
          patient.category ||
          patient.panel_group ||
          ""
        ).toLowerCase();
        return catLowerArr.some((c) => cat.includes(c));
      });
    }

    // Stage filter
    if (selectedStages.length > 0) {
      const stageLowerArr = selectedStages.map((s) => s.toLowerCase());
      filteredPatients = filteredPatients.filter((patient) => {
        const currentStages = (
          patient.discharge_status?.[0]?.current_stage || ""
        )
          .toLowerCase()
          .split(",")
          .map((s) => s.trim());
        return stageLowerArr.some((stageLower) => {
          if (!currentStages.includes(stageLower)) return false;
          const stageObj = patient.timeline.find(
            (t) => t.stage_name?.toLowerCase() === stageLower
          );
          if (!stageObj) return false;
          const statusLower = (stageObj.status || "").toLowerCase();
          return ["ongoing", "delay", "expected delay"].includes(statusLower);
        });
      });
    }

    if (filteredPatients.length === 0) {
      $(".content").html("<p>No patients found.</p>");
    } else {
      renderPatients(filteredPatients);
    }
  }

});


 

