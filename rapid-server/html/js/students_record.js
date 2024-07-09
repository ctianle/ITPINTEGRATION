const student = {
    session_id: '#001234',
    name: 'John Doe',
    avatar: '../images/misc/avatar.jpg'
};

const records = [];
const quizNames = ['ICT2104_Q1', 'ICT2104_Q2', 'ICT2104_Q3', 'ICT2104_Q4', 'ICT2104_Q5'];
const statuses = ['flagged', 'clear'];
const startDate = new Date(2023, 0, 1); // January 1, 2023

// Generate dynamic records data
for (let i = 1; i <= 12; i++) { // Increase the number for more records
    const sessionId = `#00${1234 + i}`;
    const quizName = quizNames[Math.floor(Math.random() * quizNames.length)];
    const status = statuses[Math.floor(Math.random() * statuses.length)];
    const date = new Date(startDate);
    date.setDate(date.getDate() + i);

    records.push({
        session_id: sessionId,
        quiz_name: quizName,
        status: status,
        date: date.toISOString().split('T')[0]
    });
}

// Custom sort order: flagged -> clear
const sortOrder = { flagged: 1, clear: 2 };
records.sort((a, b) => sortOrder[a.status] - sortOrder[b.status]);

const rowsPerPage = 10;
let currentPage = 1;

function displayStudentInfo() {
    document.getElementById('student-session-id').textContent = student.session_id;
    document.getElementById('student-name').textContent = student.name;
    document.querySelector('.student-img').style.backgroundImage = `url('${student.avatar}')`;
}

function displayRecordsTable(page) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedRecords = records.slice(start, end);

    const tableBody = document.getElementById('records-table-body');
    tableBody.innerHTML = '';
    paginatedRecords.forEach(row => {
        tableBody.innerHTML += `
        <tr>
            <th scope="row">${row.session_id}</th>
            <td class="name"><img src="../images/misc/quiz.jpg" alt="Quiz Image" class="quiz-name-img">${row.quiz_name}</td>
            <td><div class="status ${row.status}">&nbsp;</div></td>
            <td>${row.date}</td>
        </tr>
        `;
    });
}

function setupPagination() {
    const totalPages = Math.ceil(records.length / rowsPerPage);
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
            displayRecordsTable(currentPage);
            setupPagination();
        });
    });
}

displayStudentInfo();
displayRecordsTable(currentPage);
setupPagination();
