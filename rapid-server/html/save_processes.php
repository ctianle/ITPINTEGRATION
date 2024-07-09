<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $process_numbers = htmlspecialchars($_POST['processes']);
    $process_numbers_array = explode(',', $process_numbers);

    // Mapping of numbers to process names
    $process_mapping = [
        1 => 'notepad',
        2 => 'calculatorapp',
        3 => 'discord',
        4 => 'chrome',
        5 => 'firefox',
        // Add more mappings as needed
    ];

    $selected_processes = [];
    foreach ($process_numbers_array as $number) {
        $number = trim($number);
        if (array_key_exists($number, $process_mapping)) {
            $selected_processes[] = $process_mapping[$number];
        }
    }

    $processes = implode(',', $selected_processes);
    file_put_contents('processes.txt', $processes);
    header('Location: kill_processes_admin.php');  // Redirect back to the form page after saving
    exit;
}
?>
