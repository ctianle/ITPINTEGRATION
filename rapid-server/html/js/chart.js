document.addEventListener('DOMContentLoaded', function () {
    fetch('../process/session_chart.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const completedCount = data.completed || 0;
            const plannedCount = data.planned || 0;
            const ongoingCount = data.ongoing || 0;

            // Create arrays for labels and data points excluding zeros
            const xValues = [];
            const yValues = [];
            const barColors = [];

            if (completedCount > 0) {
                xValues.push("Completed");
                yValues.push(completedCount);
                barColors.push("#808080"); // Completed color
            }
            if (plannedCount > 0) {
                xValues.push("Planned");
                yValues.push(plannedCount);
                barColors.push("#FFA500"); // Planned color
            }
            if (ongoingCount > 0) {
                xValues.push("Ongoing");
                yValues.push(ongoingCount);
                barColors.push("#FEDC56"); // Ongoing color
            }

            // Check if there are any valid data points to display
            if (xValues.length === 0) {
                console.log('No session data available to display.');
                return;
            }

            new Chart("myChart", {
                type: "doughnut",
                data: {
                    labels: xValues,
                    datasets: [{
                        backgroundColor: barColors,
                        data: yValues
                    }]
                },
                options: {
                    responsive: false,
                    title: {
                        display: true,
                        text: "Recent Sessions",
                        fontSize: 20
                    },
                    legend: {
                        labels: {
                            fontSize: 10  // Adjust legend label size if needed
                        }
                    },
                    plugins: {
                        datalabels: {
                            color: '#000',
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            formatter: (value, context) => {
                                return value;
                            }
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            alert('Error fetching data: ' + error.message);
        });
});
