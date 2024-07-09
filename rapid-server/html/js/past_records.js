const data = [
    // Sample data entries
];

// Adding more fake data with precise status based on date
for (let i = 1; i <= 15; i++) {
    const currentDate = new Date();
    const sessionDate = new Date(currentDate.getFullYear(), 5, i);

    let status = "planned";
    if (currentDate.getFullYear() === sessionDate.getFullYear() &&
        currentDate.getMonth() === sessionDate.getMonth() &&
        currentDate.getDate() === sessionDate.getDate()) {
        status = "ongoing";
    } else if (currentDate > sessionDate) {
        status = "complete";
    }
    
    data.push({
        session_id: `#00${1234 + i}`,
        name: `ICT2108_Test_${i}`,
        status: status,
        date: `${sessionDate.getDate()} Jun, ${sessionDate.getFullYear()}`
    });
}

const rowsPerPage = 10;
let currentPage = 1;
let sortField = 'session_id';

function sortData(field) {
    data.sort((a, b) => {
        if (field === 'date') {
            return new Date(a.date) - new Date(b.date);
        } else {
            return a[field].localeCompare(b[field]);
        }
    });
}

function displayTableData(page) {
    sortData(sortField);
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedData = data.slice(start, end);
    
    const tableBody = document.getElementById('table-body');
    tableBody.innerHTML = '';
    paginatedData.forEach(row => {
        tableBody.innerHTML += `
        <tr>
        <th scope="row">${row.session_id}</th>
        <td class="name">${row.name}</td>
        <td><div class="status ${row.status}"><a href="monitoring_session.php?session_id=${row.session_id}&name=${encodeURIComponent(row.name)}&duration=${row.duration}">${row.status}</a></div></td>
        <td>${row.date}</td>
        <td>
        <div class="action d-flex flex-column flex-md-row align-items-center">
        </div>
        </td>
        </tr>
        `;
    });
}

function setupPagination() {
    const totalPages = Math.ceil(data.length / rowsPerPage);
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

document.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        sortField = e.target.getAttribute('data-sort');
        displayTableData(currentPage);
        setupPagination();
    });
});

displayTableData(currentPage);
setupPagination();
