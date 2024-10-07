const sessions = []; // Initialize sessions array to store session data

// Fetch session data from the server
function fetchData() {
  fetch("../process/fetch_session.php")
    .then((response) => response.json())
    .then((responseData) => {
      responseData.forEach((item) => {
        const dateOnly = item.StartTime.split(" ")[0]; // Assuming StartTime is in 'YYYY-MM-DD HH:mm:ss' format

        // Create a session object
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

      displayTableData(currentPage); // Render table with session data
      setupPagination(); // Setup pagination based on the data
    })
    .catch((error) => console.error("Error fetching data:", error));
}

const rowsPerPage = 10;
let currentPage = 1;
let sortField = "session_id"; // Default sort field

// Function to display table data based on the current page
function displayTableData(page) {
  sortData(sortField); // Sort data before displaying

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
      </tr>`;
  });
}

// Setup pagination buttons and event listeners
function setupPagination() {
  const totalPages = Math.ceil(sessions.length / rowsPerPage);
  const pagination = document.getElementById("pagination");
  pagination.innerHTML = "";

  for (let i = 1; i <= totalPages; i++) {
    pagination.innerHTML += `
      <li class="page-item ${i === currentPage ? "active" : ""}">
        <a class="page-link" href="#">${i}</a>
      </li>`;
  }

  // Add click event listeners to pagination buttons
  document.querySelectorAll(".page-link").forEach((link) => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      currentPage = parseInt(e.target.textContent);
      displayTableData(currentPage);
      setupPagination();
    });
  });
}

// Function to sort the data based on a given field
function sortData(field) {
  sessions.sort((a, b) => {
    if (field === "date") {
      return new Date(b.date) - new Date(a.date); // Sort by newest date first (descending)
    } else {
      return a[field].localeCompare(b[field]); // Sort other fields alphabetically
    }
  });
}

// Function to edit session details
function editSession(index) {
  const session = sessions[index];
  
  // Populate modal form with session data
  document.getElementById("editSessionName").value = session.name;
  document.getElementById("editSessionDate").value = session.date;
  document.getElementById("editSessionStartTime").value = session.start_time;
  document.getElementById("editSessionEndTime").value = session.end_time;
  document.getElementById("editSessionDuration").value = session.duration;
  document.getElementById("editBlacklist").value = session.BlacklistedApps;
  document.getElementById("editWhitelist").value = session.WhitelistedApps;

  // Show modal
  const editModal = new bootstrap.Modal(document.getElementById("editModal"));
  editModal.show();
}

// Function to delete a session
function deleteDocument(documentId) {
  window.location.href = `../process/delete_session.php?id=${documentId}`;
}

// Function to set up the dropdown menu sorting
function setupDropdown() {
  const dropdownMenu = document.getElementById("sessionDropdownMenu");
  document.getElementById("sessionDropdownMenu").addEventListener("click", (e) => {
  if (e.target.classList.contains("dropdown-item")) {
    e.preventDefault();
    sortField = e.target.getAttribute("data-sort"); // Set the sort field based on the selected item
    displayTableData(currentPage); // Call displayTableData to re-render with sorted data
    setupPagination();
  }
  });
}

// Ensure DOM is fully loaded before running scripts
window.onload = () => {
  fetchData();
  setupDropdown(); // Attach the event listener after the DOM is loaded
};