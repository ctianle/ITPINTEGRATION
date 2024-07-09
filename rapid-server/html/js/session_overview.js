function fetchAndDisplaySessions() {
    fetch('../process/overview_session.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            let previousSessions = '';
            let upcomingSessions = '';
            let previousCount = 0;
            let upcomingCount = 0;

            data.forEach(session => {
                const dateOnly = session.StartTime.split(' ')[0]; // Assuming StartTime is in 'YYYY-MM-DD HH:mm:ss' format
                const row = `
                    <tr class="session-row" data-session-id="${session.SessionId}" style="cursor: pointer;">
                        <th scope="row">#${session.SessionId}</th>
                        <td class="name">${session.SessionName}</td>
                        <td><div class="${session.Status}">&nbsp;</div></td>
                        <td>${dateOnly}</td>
                    </tr>
                `;

                if (new Date(session.StartTime) < new Date()) {
                    if (previousCount < 5) {
                        previousSessions += row;
                        previousCount++;
                    }
                } else {
                    if (upcomingCount < 5) {
                        upcomingSessions += row;
                        upcomingCount++;
                    }
                }
            });

            // Set HTML content for previous and upcoming sessions
            $('#previous-sessions').html(previousSessions);
            $('#upcoming-sessions').html(upcomingSessions);

            // Event delegation for click handling on session rows
            $(document).on('click', '.session-row', function() {
                const sessionId = $(this).data('session-id');
                // Redirect or handle click as needed
                window.location.href = `session_details.php?id=${sessionId}`;
            });
        })
        .catch(error => {
            console.error('Error fetching session data:', error);
            alert('Error fetching session data: ' + error.message);
        });
}

fetchAndDisplaySessions();
