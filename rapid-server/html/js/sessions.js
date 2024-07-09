const sessions = []; // Initialize sessions array to store session data

function fetchData() {
    fetch('../process/fetch_session.php')
        .then(response => response.json())
        .then(responseData => {
            responseData.forEach(item => {
                const dateOnly = item.StartTime.split(' ')[0]; // Assuming StartTime is in 'YYYY-MM-DD HH:mm:ss' format


                const session = {
                    _id: item._id ,
                    session_id: `${item.SessionId}`,
                    name: item.SessionName,
                    status: item.Status,
                    date: dateOnly,
                    start_time: item.StartTime.split(' ')[1].substring(0, 5), // Extract time part
                    end_time: item.EndTime.split(' ')[1].substring(0, 5), // Extract time part
                    duration: item.Duration + ' Min'
                };
                sessions.push(session); // Push session to the sessions array
            });
            console.log('Session:', sessions);
            
            displayTableData(currentPage);
            setupPagination();
        })
        .catch(error => console.error('Error fetching data:', error));
}

window.onload = fetchData;

const rowsPerPage = 10;
let currentPage = 1;

function displayTableData(page, data) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedData = sessions.slice(start, end);

    paginatedData.sort((a, b) => a.status.localeCompare(b.status));

    const tableBody = document.getElementById('table-body');
    tableBody.innerHTML = '';
    paginatedData.forEach((row, index) => {
        tableBody.innerHTML += `
        <tr>
        <th scope="row">${row.session_id}</th>
        <td class="name">${row.name}</td>
        <td><a href="monitoring_session.php?session_id=${row.session_id}"><div class="status ${row.status}">${row.status}</div></a></td>
        <td>${row.date}</td>
        <td>${row.start_time}</td>
        <td>${row.end_time}</td>
        <td>${row.duration}</td>
        <td>
        <div class="action d-flex flex-column flex-md-row align-items-center">
        <button type="button" class="btn btn-primary btn-sm mb-2 mb-md-0 me-md-2" onclick="editSession(${start + index})">Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteDocument('${row.session_id}')">Delete</button>
        </div>
        </td>
        </tr>
        `;
    });
}

function setupPagination() {
    const totalPages = Math.ceil(sessions.length / rowsPerPage);
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';

    for (let i = 1; i <= totalPages; i++) {
        pagination.innerHTML += `
        <li class="page-item ${i === currentPage ? 'active' : ''}">
        <a class="page-link" href="#">${i}</a>
        </li>
        `;
    }

    document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            currentPage = parseInt(e.target.textContent);
            displayTableData(currentPage);
            setupPagination();
        });
    });
}

function editSession(index) {
    currentEditIndex = index;
    const session = sessions[index];

    document.getElementById('editSessionName').value = session.name;
    document.getElementById('editSessionStatus').value = session.status;
    document.getElementById('editSessionDate').value = session.date;
    document.getElementById('editSessionStartTime').value = session.start_time;
    document.getElementById('editSessionEndTime').value = session.end_time;
    document.getElementById('editSessionDuration').value = session.duration;

    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

document.getElementById('editForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const sessionId = sessions[currentEditIndex].session_id;
    const sessionName = document.getElementById('editSessionName').value;

    // Construct the URL with query parameters
    const url = `../process/update_session.php?SessionId=${sessionId}&SessionName=${sessionName}`;

    // Redirect to the URL
    window.location.href = url;
});

function deleteDocument(documentId) {
    const SessionId = documentId;
    window.location.href = `../process/delete_session.php?id=${documentId}`;
}


displayTableData(currentPage);
setupPagination();
