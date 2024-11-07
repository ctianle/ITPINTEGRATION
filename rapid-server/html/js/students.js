let students = [];
let sessions = [];
let currentPages = {}; // Object to hold current page for each session
const rowsPerPage = 5;
const sessionsPerPage = 4; // Max 4 cards per page for sessions
let currentSessionPage = 1; // Track current page for sessions

function fetchData() {
    fetch('../process/fetch_student.php')
        .then(response => response.json())
        .then(data => {
            students = data.map(item => ({
                _id: item._id,
                student_id: item.student_id,
                name: item.name,
                email: item.email,
                session_id: item.session_id
            }));
            fetchSessions();
        })
        .catch(error => console.error('Error fetching data:', error));
}

function fetchSessions() {
    fetch('../process/fetch_session_all.php')
        .then(response => response.json())
        .then(responseData => {
            sessions = responseData;
            displaySessionTables();
        })
        .catch(error => console.error('Error fetching sessions:', error));
}

function displaySessionTables() {
    const tablesContainer = document.getElementById('tables-container');
    tablesContainer.innerHTML = ''; // Clear existing tables

    // Calculate the number of total pages for sessions
    const totalSessionPages = Math.ceil(sessions.length / sessionsPerPage);
    
    // Calculate the sessions to display on the current page
    const startSessionIndex = (currentSessionPage - 1) * sessionsPerPage;
    const endSessionIndex = startSessionIndex + sessionsPerPage;
    const displayedSessions = sessions.slice(startSessionIndex, endSessionIndex);

    displayedSessions.forEach(session => {
        const sessionStudents = students.filter(student => student.session_id === session.SessionId);
        const totalPages = Math.ceil(sessionStudents.length / rowsPerPage);
        
        console.log(`Session: ${session.SessionId}, Total Students: ${sessionStudents.length}, Total Pages: ${totalPages}`);

        if (!currentPages[session.SessionId]) {
            currentPages[session.SessionId] = 1; 
        }

        const tableHtml = `
        <div class="col-md-6 mb-3">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Student List for ${session.SessionName}</h5>
                   <form id="uploadForm_${session.SessionId}" class="mb-3" enctype="multipart/form-data" onsubmit="uploadCSV(event, '${session.SessionId}')">
                        <input type="file" name="file" accept=".csv" required class="mb-3">
                        <button type="submit" class="btn btn-info">Upload Student List (.CSV)</button>
                    </form>
                    <div class="table-responsive">
                        <table class="table students">
                            <thead>
                                <tr>
                                    <th scope="col">Student ID</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Session ID</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody id="table-body-${session.SessionId}">
                                ${renderTableRows(sessionStudents, currentPages[session.SessionId])}
                            </tbody>
                        </table>
                        <nav aria-label="Page navigation">
                            <ul class="pagination" id="pagination-${session.SessionId}"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        `;

        tablesContainer.innerHTML += tableHtml;

        // Set up pagination for this session
        setupPagination(totalPages, session.SessionId);
    });

    // Setup pagination for the session cards
    setupSessionPagination(totalSessionPages);
}

function setupSessionPagination(totalSessionPages) {
    const sessionPagination = document.getElementById('session-pagination');
    sessionPagination.innerHTML = '';

    for (let i = 1; i <= totalSessionPages; i++) {
        sessionPagination.innerHTML += `
        <li class="page-item ${i === currentSessionPage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="changeSessionPage(${i})">${i}</a>
        </li>
        `;
    }
}

function changeSessionPage(page) {
    const totalSessionPages = Math.ceil(sessions.length / sessionsPerPage);
    
    if (page < 1 || page > totalSessionPages) {
        console.warn('Invalid session page number:', page);
        return;
    }

    currentSessionPage = page; // Update current page for sessions
    displaySessionTables(); // Refresh session tables
}


function renderTableRows(sessionStudents, currentPage) {
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;

    // Check if the start index is within bounds
    if (startIndex >= sessionStudents.length) {
        return '<tr><td colspan="5" class="text-center">No more records available.</td></tr>';
    }

    return sessionStudents.slice(startIndex, endIndex).map(row => `
        <tr>
            <th scope="row">${row.student_id}</th>
            <td class="name">${row.name}</td>
            <td>${row.email}</td>
            <td>${row.session_id}</td>
            <td>
                <div class="action d-flex flex-column flex-md-row align-items-center">
                    <button type="button" class="btn btn-primary btn-sm mb-2 mb-md-0 me-md-2" onclick="editStudent(${students.indexOf(row)})">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteDocument('${row.student_id}', '${row.session_id}')">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function uploadCSV(event, sessionId) {
    event.preventDefault();
    
    const form = document.getElementById(`uploadForm_${sessionId}`);
    const formData = new FormData(form);
    
    // Add sessionId to formData
    formData.append('sessionId', sessionId);

    fetch(`../process/insert_student.php?sessionId=${sessionId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        return response.text().then(text => {
            if (!response.ok) {
                throw new Error(text); // Throw an error with the response text
            }
            return JSON.parse(text); // Try to parse the text as JSON
        });
    })
    .then(data => {
        alert('Upload successful!');
        fetchData(); // Refresh the data after upload
    })
    .catch(error => {
        console.error('Error uploading CSV:', error);
    });
}


function editStudent(index) {
    currentEditIndex = index;
    const student = students[index];

    document.getElementById('editStudentId').value = student.student_id;
    document.getElementById('editStudentName').value = student.name;
    document.getElementById('editStudentEmail').value = student.email;
    document.getElementById('editSessionId').value = student.session_id;

    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

document.getElementById('editForm').addEventListener('submit', function(event) {
    event.preventDefault();

    // Gather form data
    const studentId = document.getElementById('editStudentId').value;
    const studentName = document.getElementById('editStudentName').value;
    const studentEmail = document.getElementById('editStudentEmail').value;
    const sessionId = document.getElementById('editSessionId').value;

    // Construct the data object to send
    const data = {
        studentId: studentId,
        name: studentName,
        email: studentEmail,
        sessionId: sessionId
    };

    // Send a JSON POST request
    fetch('../process/update_student.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to update student data.');
        }
        return response.json();
    })
    .then(data => {
        // Handle success response
        alert('Student information updated successfully.');
        // Redirect to the students page or reload as needed
        window.location.href = '../students.php'; // Replace with your actual URL
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating student information: ' + error.message);
    });
});

function setupPagination(totalPages, sessionId) {
    const pagination = document.getElementById(`pagination-${sessionId}`);
    pagination.innerHTML = '';

    for (let i = 1; i <= totalPages; i++) {
        pagination.innerHTML += `
        <li class="page-item ${i === currentPages[sessionId] ? 'active' : ''}">
            <a class="page-link" href="#" onclick="changePage(${i}, '${sessionId}')">${i}</a>
        </li>
        `;
    }
}

function changePage(page, sessionId) {
    // Ensure sessionId is the correct type
    const sessionIdNum = Number(sessionId); // Ensure this is treated as a number

    // Filter students by session ID
    const sessionStudents = students.filter(student => student.session_id === sessionIdNum);
    const totalStudents = sessionStudents.length;
    const totalPages = Math.ceil(totalStudents / rowsPerPage);

    // Validate the page number
    if (page < 1 || page > totalPages) {
        console.warn('Invalid page number:', page);
        return; // Ignore invalid page requests
    }

    // Set the current page
    currentPages[sessionId] = page; // Update current page for the session

    // Render the table rows for the current page
    document.getElementById(`table-body-${sessionId}`).innerHTML = renderTableRows(sessionStudents, currentPages[sessionId]);
    
    // Update pagination UI
    setupPagination(totalPages, sessionId);
}

function deleteDocument(documentId, sessionId) {
    // Prepare the data to send in the POST request
    const data = {
        studentId: documentId,
        sessionId: sessionId
    };

    // Send the POST request using fetch
    fetch('../process/delete_student.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Document deleted successfully');
        } else {
            alert(`Error: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the document');
    });
}

// Call fetchData on page load
fetchData();
