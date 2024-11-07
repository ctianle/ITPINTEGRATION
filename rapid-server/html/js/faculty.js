let faculty = [];
let currentEditIndex = -1;

function fetchData() {
    fetch('../process/fetch_faculty.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Raw data received:', data); // Log the raw data received
            
            // Check if data is an array
            if (!Array.isArray(data)) {
                throw new Error('Data is not an array');
            }

            // Process the JSON data here
            console.log('Data processed:', data); // Log processed data for further inspection
            faculty = data.map(item => ({
                _id: item._id,
                user_id: item.UserId,
                user_type: item.UserType,
                name: item.UserName,
                email: item.Email,
                password_hash: item.PasswordHash
            }));

            // Example functions to display data
            displayTableData(currentPage);
            setupPagination();
        })
        .catch(error => {
            console.error('Error fetching or processing data:', error);
            // Handle error display or fallback behavior here
        });
}

const rowsPerPage = 10;
let currentPage = 1;

function displayTableData(page) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedData = faculty.slice(start, end);

    const tableBody = document.getElementById('table-body');
    tableBody.innerHTML = '';
    paginatedData.forEach((row, index) => {
        tableBody.innerHTML += `
        <tr>
            <th scope="row">${row.user_id}</th>
            <td class="user_type">${row.user_type}</td>
            <td class="name">${row.name}</td>
            <td>${row.email}</td>
            <td>${row.password_hash}</td>
            <td>
                <div class="action d-flex flex-column flex-md-row align-items-center">
                    <button type="button" class="btn btn-primary btn-sm mb-2 mb-md-0 me-md-2" onclick="editUser(${start + index})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteDocument('${row.user_id}')">Delete</button>
                </div>
            </td>
        </tr>
        `;
    });
}

function setupPagination() {
    const totalPages = Math.ceil(faculty.length / rowsPerPage);
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

function editUser(index) {
    currentEditIndex = index;  // Track the current editing index
    const user = faculty[index];

    if (!user) {
        console.error('User not found for index:', index);
        return;
    }

    document.getElementById('editUserId').value = user.user_id;  // Correctly set the user_id
    const userTypeSelect = document.getElementById('editUserType');
    userTypeSelect.value = user.user_type;  // Ensure the user type is set

    // Set other fields
    document.getElementById('editUserName').value = user.name;
    document.getElementById('editUserEmail').value = user.email;
    //document.getElementById('editPassword').value = user.password_hash;

    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();  // Show the modal
}

document.getElementById('editForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const userId = document.getElementById('editUserId').value;
    const userType = document.getElementById('editUserType').value;
    const userName = document.getElementById('editUserName').value;
    const userEmail = document.getElementById('editUserEmail').value;
    const passwordHash = document.getElementById('editPassword').value;

    const formData = {
        userId: userId,
        userType: userType,
        userName: userName,
        userEmail: userEmail,
        passwordHash: passwordHash
    };

    fetch('../process/update_faculty.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Update response:', data);
        fetchData(); // Update the UI after successful update
        $('#editModal').modal('hide'); // Close the modal after update
    })
    .catch(error => {
        console.error('Error updating user:', error);
        // Handle error display or fallback behavior here
    });
});

function deleteDocument(userId) {
    if (!confirm('Are you sure you want to delete this user?')) {
        return;
    }

    fetch(`../process/delete_faculty.php?id=${userId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Delete response:', data);
        fetchData(); // Update the UI after successful delete
    })
    .catch(error => {
        console.error('Error deleting user:', error);
        // Handle error display or fallback behavior here
    });
}

fetchData(); // Initial fetch on page load
