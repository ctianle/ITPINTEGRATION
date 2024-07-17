<!-- Temporary Image Upload Form -->
<h1 style="color: black; font-size: 18px;">Upload Screen</h1>
<form action="/process/insert_screenshot.php" method="post" enctype="multipart/form-data">
    <input type="file" name="image" accept="image/*" required>
    <label for="session_id_screenshot">Session ID:</label>
    <select name="session_id" id="session_id_screenshot" required>
        <?php for ($i = 1; $i <= 10; $i++): ?>
            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
        <?php endfor; ?>
    </select>
    <label for="student_id_screenshot">Student ID:</label>
    <select name="student_id" id="student_id_screenshot" required>
        <?php for ($i = 1; $i <= 10; $i++): ?>
            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
        <?php endfor; ?>
    </select>
    <input type="hidden" name="uuid" value="Screenshots">
    <input type="submit" value="Upload Image">
</form>

<h1 style="color: black; font-size: 18px;">Upload Webcam</h1>
<form action="/process/insert_snapshot.php" method="post" enctype="multipart/form-data">
    <input type="file" name="image" accept="image/*" required>
    <label for="session_id_snapshot">Session ID:</label>
    <select name="session_id" id="session_id_snapshot" required>
        <?php for ($i = 1; $i <= 10; $i++): ?>
            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
        <?php endfor; ?>
    </select>
    <label for="student_id_snapshot">Student ID:</label>
    <select name="student_id" id="student_id_snapshot" required>
        <?php for ($i = 1; $i <= 10; $i++): ?>
            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
        <?php endfor; ?>
    </select>
    <input type="hidden" name="uuid" value="Snapshots">
    <input type="submit" value="Upload Image">
</form>

<h1 style="color: black; font-size: 18px;">Upload Process CSV</h1>
<form action="/process/insert_student_process.php" method="post" enctype="multipart/form-data">
    <input type="file" name="csv" accept=".csv" required>
    <!-- Remove hidden inputs for StudentId and SessionId -->
    <input type="submit" value="Upload CSV">
</form>
