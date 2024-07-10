<?php
function getWebcam()
{
    // Initialise DB Variables.
    $db_user = getenv('DB_ROOT_USERNAME');
    $db_password = getenv('DB_ROOT_PASSWORD');
    $dbName = getenv('DB_NAME');

    // MongoDB connection setup
    $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
    $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

    // Get URL parameters
    $student_id = $_GET['student_id'];
    $session_id = $_GET['session_id'];

    // Query MongoDB for webcam snapshots filtered by student_id, session_id, and uuid
    $filter = [
        'StudentId' => (int) $student_id, // Assuming student_id is an integer
        'SessionId' => (int) $session_id, // Assuming session_id is an integer
        'uuid' => 'Snapshots' // Assuming uuid is a string
    ];
    $query = new MongoDB\Driver\Query($filter, ['sort' => ['date_time' => -1]]);
    $cursor = $manager->executeQuery("$dbName.Snapshots", $query);

    // Fetch all results into an array
    $rows = [];
    foreach ($cursor as $document) {
        $rows[] = $document;
    }

    // Initialize carousel items and indicators
    $carouselItems = '';
    $carouselIndicators = '';
    $activeClass = 'active';
    $slideIndex = 0;

    // Iterate through fetched results to generate carousel items
    foreach ($rows as $row) {
        $imageData = $row->data; // Assuming 'data' contains the base64 image data
        $dateTime = $row->date_time->toDateTime()->format('Y-m-d H:i:s'); // Format date_time field

        // Generate carousel item HTML
        $carouselItems .= '<div class="carousel-item ' . $activeClass . '">';
        $carouselItems .= '<img src="data:image/png;base64,' . $imageData . '" class="d-block w-100 carousel-img" alt="Webcam Snapshot" data-bs-toggle="modal" data-bs-target="#modalWebcam' . $slideIndex . '">';
        $carouselItems .= '<div class="carousel-caption d-none d-md-block">';
        $carouselItems .= '<div class="caption-text-container">'; // Container for text and shadow
        $carouselItems .= '<h5>Webcam Snapshot ' . ($slideIndex + 1) . '</h5>';
        $carouselItems .= '<p>Captured at: ' . $dateTime . '</p>'; // Display formatted date_time
        $carouselItems .= '</div>'; // End caption-text-container
        $carouselItems .= '</div>'; // End carousel-caption
        $carouselItems .= '</div>'; // End carousel-item

        // Generate indicator HTML
        $carouselIndicators .= '<button type="button" data-bs-target="#carouselWebcamCaptions"';
        $carouselIndicators .= ' data-bs-slide-to="' . $slideIndex . '" class="' . $activeClass . '" aria-label="Slide ' . ($slideIndex + 1) . '"></button>';

        // Clear active class after the first item
        $activeClass = '';
        $slideIndex++;
    }

    // Output the complete carousel structure
    echo '<div id="carouselWebcamCaptions" class="carousel slide" data-bs-ride="carousel">';
    echo '<div class="carousel-indicators">';
    echo $carouselIndicators;
    echo '</div>';
    echo '<div class="carousel-inner">';
    echo $carouselItems;
    echo '</div>';
    echo '<button class="carousel-control-prev" type="button" data-bs-target="#carouselWebcamCaptions" data-bs-slide="prev">';
    echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
    echo '<span class="visually-hidden">Previous</span>';
    echo '</button>';
    echo '<button class="carousel-control-next" type="button" data-bs-target="#carouselWebcamCaptions" data-bs-slide="next">';
    echo '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
    echo '<span class="visually-hidden">Next</span>';
    echo '</button>';
    echo '</div>';

    // Modals for enlarged images
    $slideIndex = 0; // Reset slide index for modal IDs
    foreach ($rows as $row) {
        $imageData = $row->data;
        $dateTime = $row->date_time->toDateTime()->format('Y-m-d H:i:s'); // Format date_time field

        echo '<div class="modal fade" id="modalWebcam' . $slideIndex . '" tabindex="-1" aria-labelledby="modalWebcamLabel' . $slideIndex . '" aria-hidden="true">';
        echo '<div class="modal-dialog modal-dialog-centered modal-lg">'; // Use modal-lg class for larger modal
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<h5 class="modal-title" id="modalWebcamLabel' . $slideIndex . '">Webcam Snapshot ' . ($slideIndex + 1) . '</h5>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        echo '</div>';
        echo '<div class="modal-body">';
        echo '<img src="data:image/png;base64,' . $imageData . '" class="img-fluid" alt="Webcam Snapshot" style="max-width: 100%; max-height: 80vh;">'; // Adjust max-height for the image
        echo '<h2>Captured at: ' . $dateTime . '</h2>'; // Display formatted date_time
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        $slideIndex++;
    }
}
?>