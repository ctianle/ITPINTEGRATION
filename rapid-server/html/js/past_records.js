const sessions = []; // Initialize sessions array to store session data

// Function to fetch session data from the server
function fetchData() {
  fetch("../process/fetch_session_past.php")
    .then((response) => response.json())
    .then((responseData) => {
      responseData.forEach((item) => {
        const dateOnly = item.StartTime.split(" ")[0]; // Assuming StartTime is in 'YYYY-MM-DD HH:mm:ss' format

        const session = {
          _id: item._id,
          session_id: `${item.SessionId}`,
          name: item.SessionName,
          status: item.Status,
          date: dateOnly,
          start_time: item.StartTime.split(" ")[1].substring(0, 5), // Extract time part
          end_time: item.EndTime.split(" ")[1].substring(0, 5), // Extract time part
          duration: item.Duration,
          BlacklistedApps: item.BlacklistedApps || [], // Include BlacklistedApps array from MongoDB document
          WhitelistedApps: item.WhitelistedApps || [], // Include WhitelistedApps array from MongoDB document
        };
        sessions.push(session); // Push session to the sessions array
      });

      displayTableData(currentPage);
      setupPagination();
    })
    .catch((error) => console.error("Error fetching data:", error));
}

const rowsPerPage = 10;
let currentPage = 1;
let sortField = "session_id"; // Initialize sortField here to avoid ReferenceError

// Function to display table data based on the current page
function displayTableData(page) {
  sortData(sortField);
  const start = (page - 1) * rowsPerPage;
  const end = start + rowsPerPage;
  const paginatedData = sessions.slice(start, end);

  const tableBody = document.getElementById("table-body");
  tableBody.innerHTML = "";
  paginatedData.forEach((row, index) => {
    tableBody.innerHTML += `
        <tr>
          <th scope="row">${row.session_id}</th>
          <td class="name">${row.name}</td>
          <td><a href="monitoring_session.php?session_id=${
            row.session_id
          }"><div class="status ${row.status}">${row.status}</div></a></td>
          <td>${row.date}</td>
          <td>${row.start_time}</td>
          <td>${row.end_time}</td>
          <td>${row.duration}</td>
          <td>
            <div class="action d-flex flex-column flex-md-row align-items-center">
              <button type="button" class="btn btn-primary btn-sm mb-2 mb-md-0 me-md-2" onclick="editSession(${
                start + index
              })">Edit</button>
              <button class="btn btn-danger btn-sm" onclick="deleteDocument('${
                row.session_id
              }')">Delete</button>
            </div>
          </td>
        </tr>
      `;
  });
}

// Function to set up pagination
function setupPagination() {
  const totalPages = Math.ceil(sessions.length / rowsPerPage);
  const pagination = document.getElementById("pagination");
  pagination.innerHTML = "";

  for (let i = 1; i <= totalPages; i++) {
    pagination.innerHTML += `
      <li class="page-item ${i === currentPage ? "active" : ""}">
        <a class="page-link" href="#">${i}</a>
      </li>
    `;
  }

  document.querySelectorAll(".page-link").forEach((link) => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      currentPage = parseInt(e.target.textContent);
      displayTableData(currentPage);
      setupPagination();
    });
  });
}

// Function to sort the session data
function sortData(field) {
  sessions.sort((a, b) => {
    if (field === "date") {
      return new Date(b.date) - new Date(a.date); // Sort by newest date (descending order)
    } else {
      return a[field].localeCompare(b[field]); // Sort other fields in ascending order
    }
  });
}

// Function to edit session details
function editSession(index) {
  currentEditIndex = index;
  const session = sessions[index];

  document.getElementById("editSessionName").value = session.name;
  document.getElementById("editSessionDate").value = session.date;
  document.getElementById("editSessionStartTime").value = session.start_time;
  document.getElementById("editSessionEndTime").value = session.end_time;
  document.getElementById("editSessionDuration").value = session.duration;
  document.getElementById("editBlacklist").value = session.BlacklistedApps.join(
    ", "
  );
  document.getElementById("editWhitelist").value = session.WhitelistedApps.join(
    ", "
  );

  const editModal = new bootstrap.Modal(document.getElementById("editModal"));
  editModal.show();
}

// Function to delete a session document
function deleteDocument(documentId) {
  window.location.href = `../process/delete_session.php?id=${documentId}`;
}

// Function to set up the dropdown menu sorting
function setupDropdown() {
  const dropdownMenu = document.getElementById("recordsDropdownMenu");
  document.getElementById("recordsDropdownMenu").addEventListener("click", (e) => {
  if (e.target.classList.contains("dropdown-item")) {
    e.preventDefault();
    sortField = e.target.getAttribute("data-sort"); // Set the sort field based on the selected item
    displayTableData(currentPage); // Call displayTableData to re-render with sorted data
    setupPagination();
  }
  });
}

document.getElementById("editForm").addEventListener("submit", function (event) {
  event.preventDefault();

  const sessionId = sessions[currentEditIndex].session_id;
  const sessionName = document.getElementById("editSessionName").value;
  const sessionDate = document.getElementById("editSessionDate").value;
  const startTime = document.getElementById("editSessionStartTime").value;
  const endTime = document.getElementById("editSessionEndTime").value;
  const duration = document.getElementById("editSessionDuration").value;
  
  const blacklist = document
    .getElementById("editBlacklist")
    .value.split(",")
    .map((item) => item.trim())
    .filter((item) => item.length > 0);
    
  const whitelist = document
    .getElementById("editWhitelist")
    .value.split(",")
    .map((item) => item.trim())
    .filter((item) => item.length > 0);

  const url = `../process/update_session.php?SessionId=${sessionId}&SessionName=${encodeURIComponent(
    sessionName
  )}&Date=${encodeURIComponent(sessionDate)}&StartTime=${encodeURIComponent(
    startTime
  )}&EndTime=${encodeURIComponent(endTime)}&Duration=${encodeURIComponent(
    duration
  )}&Blacklist=${encodeURIComponent(
    JSON.stringify(blacklist)
  )}&Whitelist=${encodeURIComponent(JSON.stringify(whitelist))}`;

  window.location.href = url;
});

// Ensure DOM is fully loaded before running scripts
window.onload = () => {
  fetchData();
  setupDropdown(); // Attach the event listener after the DOM is loaded
};