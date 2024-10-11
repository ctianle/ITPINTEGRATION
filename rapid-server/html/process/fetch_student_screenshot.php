<?php
function getScreenshot()
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
    $session_id = $_GET['session_id'];
    $limit = 10; // Number of screenshots per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
    $offset = ($page - 1) * $limit; // Offset for the query

    // Query MongoDB for screenshots
    $filter = [
        'uuid' => ['$regex' => "^$student_id-"],
        //'StudentId' => (int) $student_id, // Uncomment and use as needed
        //'SessionId' => (int) $session_id, // Uncomment and use as needed
    ];
    // Update this part for pagination
    $pageScreenshots = isset($_GET['page_screenshots']) ? (int)$_GET['page_screenshots'] : 1; // Current page for screenshots
    $offsetScreenshots = ($pageScreenshots - 1) * $limit; // Offset for screenshots query

    // Ensure that you apply the offset in the screenshots query
    $screenshotQuery = new MongoDB\Driver\Query($filter, [
        'sort' => ['timestamp' => -1],
        'limit' => $limit,
        'skip' => $offsetScreenshots, // Use the correct offset for pagination
    ]);
    $screenshotCursor = $manager->executeQuery("$dbName.Screenshots", $screenshotQuery);

    // Fetch results into an array
    $rows = [];
    foreach ($screenshotCursor as $document) {
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
        $carouselItems .= '<img src="data:image/png;base64,' . $imageData . '" class="d-block w-100 carousel-img" alt="Screenshot" data-bs-toggle="modal" data-bs-target="#modalScreenshot' . $slideIndex . '">';
        $carouselItems .= '<div class="carousel-caption d-none d-md-block">';
        $carouselItems .= '<div class="caption-text-container">'; // Container for text and shadow
        $carouselItems .= '<h5>Screenshot ' . ($slideIndex + 1) . '</h5>';
        $carouselItems .= '<p>Captured at: ' . $formattedDateTime . ' GMT+8</p>';
        $carouselItems .= '</div>';
        $carouselItems .= '</div>';
        $carouselItems .= '</div>';

        // Generate indicator HTML
        $carouselIndicators .= '<button type="button" data-bs-target="#carouselScreenshotCaptions" data-bs-slide-to="' . $slideIndex . '" class="' . $activeClass . '" aria-label="Slide ' . ($slideIndex + 1) . '"></button>';

        // Clear active class after the first item
        $activeClass = '';
        $slideIndex++;
    }

    if ($carouselItems != null) {
        // Output the complete carousel structure
        echo '<div id="carouselScreenshotCaptions" class="carousel slide" data-bs-ride="carousel">';
        echo '<div class="carousel-indicators">';
        echo $carouselIndicators;
        echo '</div>';
        echo '<div class="carousel-inner">';
        echo $carouselItems;
        echo '</div>';
        echo '<button class="carousel-control-prev" type="button" data-bs-target="#carouselScreenshotCaptions" data-bs-slide="prev">';
        echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
        echo '<span class="visually-hidden">Previous</span>';
        echo '</button>';
        echo '<button class="carousel-control-next" type="button" data-bs-target="#carouselScreenshotCaptions" data-bs-slide="next">';
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
    
            echo '<div class="modal fade" id="modalScreenshot' . $slideIndex . '" tabindex="-1" aria-labelledby="modalScreenshotLabel' . $slideIndex . '" aria-hidden="true">';
            echo '<div class="modal-dialog modal-dialog-centered modal-lg">';
            echo '<div class="modal-content">';
            echo '<div class="modal-header">';
            echo '<h5 class="modal-title" id="modalScreenshotLabel' . $slideIndex . '">Screenshot ' . ($slideIndex + 1) . '</h5>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '</div>';
            echo '<div class="modal-body">';
            echo '<img src="data:image/png;base64,' . $imageData . '" class="img-fluid" alt="Screenshot" style="max-width: 100%; max-height: 80vh;">';
            echo '<h2>Captured at: ' . $formattedDateTime . " GMT+8" . '</h2>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
    
            $slideIndex++;
        }
        
        // Pagination for Screenshots (store HTML separately)
        $pageScreenshots = isset($_GET['page_screenshots']) ? (int)$_GET['page_screenshots'] : 1; // Current page for screenshots
        $offsetScreenshots = ($pageScreenshots - 1) * $limit; // Offset for screenshots query

        // Pagination (store HTML separately)
        // Pagination for Screenshots (store HTML separately)
        $totalCountQueryScreenshots = new MongoDB\Driver\Query($filter);
        $totalCountCursorScreenshots = $manager->executeQuery("$dbName.Screenshots", $totalCountQueryScreenshots);
        $totalCountScreenshots = count(iterator_to_array($totalCountCursorScreenshots));
        $totalPagesScreenshots = ceil($totalCountScreenshots / $limit);

        $screenshotPaginationHTML = '<div class="pagination" style="margin-top: 20px;">';
        if ($pageScreenshots > 1) {
            $screenshotPaginationHTML .= '<a href="?student_id=' . $student_id . '&session_id=' . $session_id . '&page_screenshots=' . ($pageScreenshots - 1) . '">Previous</a>';
        }
        if ($pageScreenshots < $totalPagesScreenshots) {
            $screenshotPaginationHTML .= '<a href="?student_id=' . $student_id . '&session_id=' . $session_id . '&page_screenshots=' . ($pageScreenshots + 1) . '">Next</a>';
        }
        
        $screenshotPaginationHTML .= '</div>';

        // Return the screenshots pagination separately
        return [
            'screenshotPaginationHTML' => $screenshotPaginationHTML
        ];
    }
}
?>