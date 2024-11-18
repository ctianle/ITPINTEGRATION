<?php
$allowed_roles = ['admin', 'invigilator'];
include('../auth_check.php');
function getWebcam()
{
    // Initialise DB Variables.
    $db_user = getenv('DB_ROOT_USERNAME');
    $db_password = getenv('DB_ROOT_PASSWORD');
    $dbName = getenv('DB_NAME');

    // MongoDB connection setup
    $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
    $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

    // Get URL parameters for pagination
    $student_id = $_GET['student_id'];
    $session_id = (int)$_GET['session_id'];
    $limit = 10; // Number of images per page
    $pageSnapshots = isset($_GET['page_snapshots']) ? (int)$_GET['page_snapshots'] : 1; // Current page
    $offsetSnapshots = ($pageSnapshots - 1) * $limit; // Offset for the query

    // Query MongoDB for webcam snapshots
    $filter = [
        'UUID' => ['$regex' => "^$student_id-"],
        'ProctorSessionID' => $session_id,
    ];
    // Update the query for pagination
    $snapshotQuery = new MongoDB\Driver\Query($filter, [
        'sort' => ['timestamp' => -1],
        'limit' => $limit,
        'skip' => $offsetSnapshots, // Use the correct offset for pagination
    ]);
    $snapshotCursor = $manager->executeQuery("$dbName.Snapshots", $snapshotQuery);

    // Fetch results into an array
    $rows = [];
    foreach ($snapshotCursor as $document) {
        $rows[] = $document;
    }

    // Initialize carousel items and indicators
    $carouselItems = '';
    $carouselIndicators = '';
    $activeClass = 'active';
    $slideIndex = 0;
    $paginationHTML = '';
    
    // Iterate through fetched results to generate carousel items
    foreach ($rows as $row) {
        $imageData = $row->content;
        $dateTimeUTC = $row->timestamp->toDateTime()->setTimezone(new DateTimeZone('UTC'));
        $dateTimeGMT8 = $dateTimeUTC->setTimezone(new DateTimeZone('Asia/Singapore'));
        $formattedDateTime = $dateTimeGMT8->format('Y-m-d H:i:s');

        // Generate carousel item HTML
        $carouselItems .= '<div class="carousel-item ' . $activeClass . '">';
        $carouselItems .= '<img src="data:image/png;base64,' . $imageData . '" class="d-block w-100 carousel-img" alt="Screenshot" data-bs-toggle="modal" data-bs-target="#modalWebcam' . $slideIndex . '">';
        $carouselItems .= '<div class="carousel-caption d-none d-md-block">';
        $carouselItems .= '<div class="caption-text-container">'; // Container for text and shadow
        $carouselItems .= '<h5>Snapshot ' . ($slideIndex + 1) . '</h5>';
        $carouselItems .= '<p>Captured at: ' . $formattedDateTime . ' GMT+8</p>';
        $carouselItems .= '</div>';
        $carouselItems .= '</div>';
        $carouselItems .= '</div>';

        // Generate indicator HTML
        $carouselIndicators .= '<button type="button" data-bs-target="#carouselWebcamCaptions" data-bs-slide-to="' . $slideIndex . '" class="' . $activeClass . '" aria-label="Slide ' . ($slideIndex + 1) . '"></button>';

        // Clear active class after the first item
        $activeClass = '';
        $slideIndex++;
    }

    if ($carouselItems != null) {
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
        echo '</div>'; // Close carousel div
    
        // Modals for enlarged images
        $slideIndex = 0; // Reset slide index for modal IDs
        foreach ($rows as $row) {
            $imageData = $row->content;
            $dateTimeUTC = $row->timestamp->toDateTime()->setTimezone(new DateTimeZone('UTC'));
            $dateTimeGMT8 = $dateTimeUTC->setTimezone(new DateTimeZone('Asia/Singapore'));
            $formattedDateTime = $dateTimeGMT8->format('Y-m-d H:i:s');
    
    
            echo '<div class="modal fade" id="modalWebcam' . $slideIndex . '" tabindex="-1" aria-labelledby="modalWebcamLabel' . $slideIndex . '" aria-hidden="true">';
            echo '<div class="modal-dialog modal-dialog-centered modal-lg">';
            echo '<div class="modal-content">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="modalWebcamLabel' . $slideIndex . '">Webcam Snapshot ' . ($slideIndex + 1) . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<img src="data:image/png;base64,' . $imageData . '" class="img-fluid" alt="Webcam Snapshot" style="max-width: 100%; max-height: 80vh;">';
            echo '<h2>Captured at: ' . $formattedDateTime . " GMT+8". '</h2>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
    
            $slideIndex++;
        }
    
        $pageSnapshots = isset($_GET['page_snapshots']) ? (int)$_GET['page_snapshots'] : 1; // Current page for snapshots
        $offsetSnapshots = ($pageSnapshots - 1) * $limit; // Offset for snapshots query
        // Pagination for Snapshots (store HTML separately)
        $totalCountQuerySnapshots = new MongoDB\Driver\Query($filter);
        $totalCountCursorSnapshots = $manager->executeQuery("$dbName.Snapshots", $totalCountQuerySnapshots);
        $totalCountSnapshots = count(iterator_to_array($totalCountCursorSnapshots));
        $totalPagesSnapshots = ceil($totalCountSnapshots / $limit);

        $snapshotPaginationHTML = '<div class="pagination" style="margin-top: 20px;">';
        if ($pageSnapshots > 1) {
            $snapshotPaginationHTML .= '<a href="?student_id=' . $student_id . '&session_id=' . $session_id . '&page_snapshots=' . ($pageSnapshots - 1) . '">Previous</a>';
        }
        if ($pageSnapshots < $totalPagesSnapshots) {
            $snapshotPaginationHTML .= '<a href="?student_id=' . $student_id . '&session_id=' . $session_id . '&page_snapshots=' . ($pageSnapshots + 1) . '">Next</a>';
        }
        $snapshotPaginationHTML .= '</div>';

        
        // Return the snapshots pagination separately
        return [
            'snapshotPaginationHTML' => $snapshotPaginationHTML
        ];
    }
    
}
?>
