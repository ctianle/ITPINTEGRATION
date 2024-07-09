const rowsPerPage = 18;
let currentPage = 1;
let timerInterval; // To store the interval for the countdown timer
// Function to fetch data based on session_id
function fetchData(sessionId) {
    const url = `../process/fetch_student_monitor.php?session_id=${sessionId}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            students = data.map(item => ({
                id: item.student_id,
                name: item.name,
                avatar: `https://randomuser.me/api/portraits/${item.gender === 'male' ? 'men' : 'women'}/${item.student_id}.jpg`, // Adjust avatar URL as per your backend
                status: getRandomStatus()
            }));

            displayStudents(currentPage);
            setupPagination();
        })
        .catch(error => console.error('Error fetching data:', error));
}

const sessions = []; // Initialize sessions array to store session data
function fetchSession(sessionId) {
    const url = `../process/fetch_session_monitor.php?session_id=${sessionId}`;
    fetch(url)
        .then(response => response.json())
        .then(responseData => {
            responseData.forEach(item => {
                const dateOnly = item.StartTime.split(' ')[0]; // Assuming StartTime is in 'YYYY-MM-DD HH:mm:ss' format

                const session = {
                    _id: item._id,
                    session_id: `${item.SessionId}`,
                    name: item.SessionName,
                    status: item.Status,
                    date: dateOnly,
                    start_time: item.StartTime.split(' ')[1].substring(0, 5), // Extract time part
                    end_time: item.EndTime.split(' ')[1].substring(0, 5), // Extract time part
                    duration: item.Duration
                };
                sessions.push(session); // Push session to the sessions array
            
            });
            if (sessions.length > 0) {
               
                // Update session heading dynamically
                document.getElementById('session-heading').textContent = `Monitoring Session: ${sessions[0].name}`;
                
                // Calculate current time
                const now = new Date();
                const currentTime = now.getHours() * 60 + now.getMinutes(); // Current time in minutes since midnight

                // Calculate session start time in minutes since midnight
                const sessionStartTime = parseInt(sessions[0].start_time.split(':')[0]) * 60 + parseInt(sessions[0].start_time.split(':')[1]);
                // Calculate session end time in minutes since midnight
                const sessionEndTime = parseInt(sessions[0].end_time.split(':')[0]) * 60 + parseInt(sessions[0].end_time.split(':')[1]);

                // Check if current time is within session start and end times
                if (currentTime >= sessionStartTime && currentTime <= sessionEndTime) {
                    // Start timer if current time is within session times
                    setupTimer(sessions[0].duration * 60); // Setup timer based on session duration (in seconds)
                } else {
                    console.log('Session is not active now.');
                    // Optionally handle the case when the session is not active now
                }
            } else {
                console.error('No sessions found.');
            }
        
        })
        .catch(error => console.error('Error fetching data:', error));
}


function getRandomStatus() {
    const statuses = ['clear', 'suspicious'];
    return statuses[Math.floor(Math.random() * statuses.length)];
}

function displayStudents(page) {
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedStudents = students.slice(start, end);

    const container = document.getElementById('students-container');
    container.innerHTML = '';
    paginatedStudents.forEach(student => {
        const studentDiv = document.createElement('div');
        studentDiv.className = 'col-6 col-sm-4 col-md-3 col-lg-2 text-center mb-4 student-div';

        studentDiv.innerHTML = `
            <div class="student-avatar ${student.status}" style="background-image: url('${student.avatar}');"></div>
            <p class="student-name">${student.name}</p>
        `;

        studentDiv.addEventListener('click', () => {
            window.location.href = `student_overview.php?student_id=${student.id}&session_id=${sessions[0].session_id}`;
        });

        container.appendChild(studentDiv);

        // Add hover effect
        studentDiv.addEventListener('mouseenter', () => {
            studentDiv.classList.add('hovered');
        });

        studentDiv.addEventListener('mouseleave', () => {
            studentDiv.classList.remove('hovered');
        });
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
            displayStudents(currentPage);
            setupPagination();
        });
    });
}

function setupTimer(duration) {
    const timerText = document.getElementById('timer');
    let timeRemaining = duration;

    // Check if there's a saved time remaining in localStorage
    const savedTimeRemaining = localStorage.getItem('timeRemaining');
    if (savedTimeRemaining) {
        timeRemaining = parseInt(savedTimeRemaining, 10);
    }

    timerInterval = setInterval(() => {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        timerText.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            timerText.textContent = 'Time\'s up!';
            localStorage.removeItem('timeRemaining');
        } else {
            localStorage.setItem('timeRemaining', timeRemaining);
            timeRemaining--;
        }
    }, 1000);
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const sessionId = urlParams.get('session_id');

    if (sessionId) {
        fetchData(sessionId);
        fetchSession(sessionId);
    }
});

// Clear timerInterval when leaving the page to prevent memory leaks
window.addEventListener('beforeunload', () => {
    clearInterval(timerInterval);
});
