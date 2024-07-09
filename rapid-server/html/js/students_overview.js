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

const rowsPerPage = 5;
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

// Initialize functions
fetchData();
