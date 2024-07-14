let students = [];
let currentEditIndex = -1;

function fetchData() {
    fetch('../process/fetch_student.php')
        .then(response => response.json())
        .then(data => {
            students = data.map(item => {
                const student = {
                    _id: item._id,
                    student_id: item.student_id,
                    name: item.name,
                    email: item.email,
                    session_id: item.session_id
                };
                return student;
            });

            displayTableData(currentPage);
            setupPagination();
        })
        .catch(error => console.error('Error fetching data:', error));
}

// Fetch sessions from MongoDB and populate the dropdown
function fetchSessions() {
    fetch('../process/fetch_session_all.php')
        .then(response => response.json())
        .then(responseData => {
            const sessionDropdown = document.getElementById('sessionDropdown');
            sessionDropdown.innerHTML = ''; // Clear existing options

            responseData.forEach(item => {
                const option = document.createElement('option');
                option.value = item.SessionId;
                option.text = item.SessionId +" : " + item.SessionName;
                sessionDropdown.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching sessions:', error));
}

const rowsPerPage = 10;
let currentPage = 1;

function displayTableData(page) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedData = students.slice(start, end);

    const tableBody = document.getElementById('table-body');
    tableBody.innerHTML = '';
    paginatedData.forEach((row, index) => {
        tableBody.innerHTML += `
        <tr>
            <th scope="row">${row.student_id}</th>
            <td class="name">${row.name}</td>
            <td>${row.email}</td>
            <td>${row.session_id}</td>
             <td>
            <div class="action d-flex flex-column flex-md-row align-items-center">
                <button type="button" class="btn btn-primary btn-sm mb-2 mb-md-0 me-md-2" onclick="editStudent(${start + index})">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteDocument('${row.student_id}')">Delete</button>
            </div>
            </td>
        </tr>
        `;
    });
}

function setupPagination() {
    const totalPages = Math.ceil(students.length / rowsPerPage);
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
    const studentId = document.getElementById('editStudentId').value;
    const studentName = document.getElementById('editStudentName').value;
    const studentEmail = document.getElementById('editStudentEmail').value;
    const sessionId = document.getElementById('editSessionId').value;

    // Construct the URL with query parameters
    const url = `../process/update_student.php?studentId=${studentId}&name=${studentName}&email=${studentEmail}&sessionId=${sessionId}`;

    // Redirect to the URL
    window.location.href = url;
});

function deleteDocument(documentId) {
    const studentId = documentId;
    window.location.href = `../process/delete_student.php?id=${studentId}`;
}

// Initialize functions
fetchData();
fetchSessions();
