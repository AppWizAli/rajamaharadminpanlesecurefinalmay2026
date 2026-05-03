<?php
session_start();
include "config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_apk'])) {
    $string = $_POST['apk_str']; // Capture the string value

    if (isset($_FILES['apk_file']) && $_FILES['apk_file']['error'] == 0) {
        $upload_dir = 'uploads/apk/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $apk_file_name = basename($_FILES["apk_file"]["name"]);
        $apk_file = $upload_dir . $apk_file_name;
        $apkFileType = strtolower(pathinfo($apk_file, PATHINFO_EXTENSION));

        // Check if file is APK
        if ($apkFileType == 'apk') {

            // Check if there is an existing APK record
            $sql = "SELECT apk_url FROM apk_files LIMIT 1"; 
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                // Update existing APK and string
                $row = $result->fetch_assoc();
                $old_apk = $row['apk_url'];

                // Delete the old APK file if it exists
                if (file_exists($old_apk)) {
                    unlink($old_apk); 
                }

                // Prepare the update query to update both APK URL and string
                $update_sql = $conn->prepare("UPDATE apk_files SET apk_url = ?, string = ? WHERE apk_url = ?");
                $update_sql->bind_param("sss", $apk_file, $string, $old_apk);
                
                if ($update_sql->execute()) {
                    echo "The APK and string have been updated in the database.";
                } else {
                    echo "Error: " . $update_sql->error;
                }
            } else {
                // Insert new APK and string into the database
                $insert_sql = $conn->prepare("INSERT INTO apk_files (string, apk_url) VALUES (?, ?)");
                $insert_sql->bind_param("ss", $string, $apk_file);
                
                if ($insert_sql->execute()) {
                    echo "The APK file and string have been uploaded and recorded in the database.";
                } else {
                    echo "Error: " . $insert_sql->error;
                }
            }

            // Move the uploaded file to the server directory
            if (move_uploaded_file($_FILES["apk_file"]["tmp_name"], $apk_file)) {
                echo "The APK file has been uploaded successfully.";
            } else {
                echo "Sorry, there was an error uploading the APK file.";
            }
        } else {
            echo "Only APK files are allowed.";
        }
    } else {
        echo "No APK file was uploaded or there was an error with the upload.";
    }
}

$conn->close();
?>
