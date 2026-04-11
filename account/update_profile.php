<?php
/**
 * Update Profile Handler
 *
 * @package DataForge
 * @module  Account
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/auth_functions.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/csrf.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken('profile.php');

    $user = getCurrentUser();
    if (!$user) {
        setFlash('error', 'Error de sesión.');
        header('Location: ../auth/login.php');
        exit();
    }

    $userId    = (int) $user['id'];
    $username  = $_POST['username'] ?? '';
    $email     = $_POST['email'] ?? '';
    $colorMode = $_POST['color_mode'] ?? 'dark';

    // Handle Profile Picture
    $profilePic = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['profile_pic']['tmp_name'];
        $fileName      = $_FILES['profile_pic']['name'];
        $fileSize      = $_FILES['profile_pic']['size'];
        $fileType      = $_FILES['profile_pic']['type'];
        $fileNameCmps  = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg', 'webp'];

        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Upload directory
            $uploadFileDir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            // Clean previous avatar if it exists
            $currentUserDb = getUserById($userId);
            if (!empty($currentUserDb['profile_pic']) && file_exists($uploadFileDir . $currentUserDb['profile_pic'])) {
                @unlink($uploadFileDir . $currentUserDb['profile_pic']);
            }

            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $profilePic = $newFileName;
            } else {
                setFlash('error', 'Error intentando mover la imagen al directorio destino.');
                header('Location: profile.php');
                exit();
            }
        } else {
            setFlash('error', 'Extensión de imagen no permitida. Sube JPG, PNG, GIF o WEBP.');
            header('Location: profile.php');
            exit();
        }
    }

    $result = updateUserProfile($userId, $username, $email, $colorMode, $profilePic);

    if ($result['success']) {
        setFlash('success', $result['message']);
    } else {
        setFlash('error', $result['message']);
    }
    
    header('Location: profile.php');
    exit();
} else {
    header('Location: profile.php');
    exit();
}
