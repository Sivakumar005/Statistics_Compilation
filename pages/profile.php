<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../includes/db.php';
require_once '../includes/config.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch existing profile data
$profile_query = "SELECT * FROM user_profiles WHERE user_id = ?";
$profile_stmt = $mysqli->prepare($profile_query);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $bio = trim($_POST['bio']);
    $location = trim($_POST['location']);
    $phone = trim($_POST['phone']);
    $website = trim($_POST['website']);

    // Handle profile picture upload
    $profile_picture = $profile['profile_picture'] ?? null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            
            // Create profiles directory if it doesn't exist
            $profiles_dir = __DIR__ . '/../uploads/profiles/';
            if (!file_exists($profiles_dir)) {
                if (!mkdir($profiles_dir, 0777, true)) {
                    $error = "Failed to create upload directory. Please check permissions.";
                    error_log("Failed to create directory: " . $profiles_dir);
                }
            }
            
            if (empty($error) && is_writable($profiles_dir)) {
                $upload_path = $profiles_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    // Delete old profile picture if exists
                    if ($profile_picture && file_exists($profiles_dir . $profile_picture)) {
                        unlink($profiles_dir . $profile_picture);
                    }
                    $profile_picture = $new_filename;
                } else {
                    $error = "Error uploading profile picture. Error code: " . $_FILES['profile_picture']['error'];
                }
            } else {
                $error = "Upload directory is not writable. Please check permissions.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
        }
    }

    if (empty($error)) {
        if ($profile) {
            // Update existing profile
            $update_query = "UPDATE user_profiles SET 
                           full_name = ?, 
                           bio = ?, 
                           location = ?, 
                           phone = ?, 
                           website = ?,
                           profile_picture = COALESCE(?, profile_picture),
                           updated_at = NOW()
                           WHERE user_id = ?";
            $update_stmt = $mysqli->prepare($update_query);
            $update_stmt->bind_param("ssssssi", $full_name, $bio, $location, $phone, $website, $profile_picture, $user_id);
        } else {
            // Create new profile
            $insert_query = "INSERT INTO user_profiles 
                           (user_id, full_name, bio, location, phone, website, profile_picture, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $insert_stmt = $mysqli->prepare($insert_query);
            $insert_stmt->bind_param("issssss", $user_id, $full_name, $bio, $location, $phone, $website, $profile_picture);
        }

        if (($profile && $update_stmt->execute()) || (!$profile && $insert_stmt->execute())) {
            $message = "Profile updated successfully!";
            // Refresh profile data
            $profile_stmt->execute();
            $profile_result = $profile_stmt->get_result();
            $profile = $profile_result->fetch_assoc();
        } else {
            $error = "Error updating profile: " . $mysqli->error;
        }
    }
}

// Get the base URL for profile pictures
$profile_picture_base_url = '../uploads/profiles/';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Statistics Compilation</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="../includes/styles.css" rel="stylesheet">
    <style>
        /* Profile picture styles */
        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .profile-picture-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
        .profile-info {
            display: none;
        }
        .profile-form {
            display: none;
        }
        .profile-info.active {
            display: block;
        }
        .profile-form.active {
            display: block;
        }
        .info-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            width: 150px;
            font-weight: 600;
            color: #4b5563;
        }
        .info-value {
            flex: 1;
            color: #111827;
        }
        .edit-icon {
            color: #4f46e5;
            cursor: pointer;
            transition: color 0.2s;
        }
        .edit-icon:hover {
            color: #4338ca;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>

    <!-- Sidebar and Main Content -->
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 main-content p-8" id="mainContent">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Profile Settings</h1>
                <button id="editProfileBtn" class="flex items-center space-x-2 text-indigo-600 hover:text-indigo-700">
                    <i class="fas fa-edit"></i>
                    <span>Edit Profile</span>
                </button>
            </div>

            <!-- Profile View -->
            <div id="profileInfo" class="profile-info active bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center space-x-6 mb-8">
                        <img src="<?php echo $profile && $profile['profile_picture'] ? $profile_picture_base_url . htmlspecialchars($profile['profile_picture']) : 'https://via.placeholder.com/150'; ?>" 
                             alt="Profile Picture" 
                             class="profile-picture-preview">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($profile['full_name'] ?? 'Your Name'); ?></h2>
                            <p class="text-gray-500"><?php echo htmlspecialchars($profile['location'] ?? 'Your Location'); ?></p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="info-item">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['full_name'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Bio</span>
                            <span class="info-value"><?php echo nl2br(htmlspecialchars($profile['bio'] ?? 'No bio added yet')); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Location</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['location'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['phone'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Website</span>
                            <span class="info-value">
                                <?php if (!empty($profile['website'])): ?>
                                    <a href="<?php echo htmlspecialchars($profile['website']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-700">
                                        <?php echo htmlspecialchars($profile['website']); ?>
                                    </a>
                                <?php else: ?>
                                    Not set
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Form -->
            <div id="profileForm" class="profile-form bg-white p-6 rounded-lg shadow-md">
                <?php if (!empty($message)): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Profile Picture -->
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <img id="profilePreview" 
                                 src="<?php echo $profile && $profile['profile_picture'] ? $profile_picture_base_url . htmlspecialchars($profile['profile_picture']) : 'https://via.placeholder.com/150'; ?>" 
                                 alt="" 
                                 class="profile-picture-preview">
                            <label for="profile_picture" class="absolute bottom-0 right-0 bg-indigo-600 text-white p-2 rounded-full cursor-pointer hover:bg-indigo-700">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" 
                                   id="profile_picture" 
                                   name="profile_picture" 
                                   accept="image/*" 
                                   class="hidden" 
                                   onchange="previewImage(this)">
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Profile Picture</h3>
                            <p class="text-sm text-gray-500">Upload a new profile picture (JPG, PNG)</p>
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" 
                               name="full_name" 
                               id="full_name" 
                               value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>" 
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <!-- Bio -->
                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                        <textarea name="bio" 
                                  id="bio" 
                                  rows="4" 
                                  class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                        <input type="text" 
                               name="location" 
                               id="location" 
                               value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>" 
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="tel" 
                               name="phone" 
                               id="phone" 
                               value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" 
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <!-- Website -->
                    <div>
                        <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                        <input type="url" 
                               name="website" 
                               id="website" 
                               value="<?php echo htmlspecialchars($profile['website'] ?? ''); ?>" 
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <!-- Submit and Cancel Buttons -->
                    <div class="flex space-x-4">
                        <button type="submit" class="flex-1 py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                            Save Changes
                        </button>
                        <button type="button" id="cancelEditBtn" class="flex-1 py-2 px-4 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="../includes/scripts.js"></script>
    <script>
    // Profile picture preview
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Edit profile functionality
    document.getElementById('editProfileBtn').addEventListener('click', function() {
        document.getElementById('profileInfo').classList.remove('active');
        document.getElementById('profileForm').classList.add('active');
        this.style.display = 'none';
    });

    // Cancel edit functionality
    document.getElementById('cancelEditBtn').addEventListener('click', function() {
        document.getElementById('profileInfo').classList.add('active');
        document.getElementById('profileForm').classList.remove('active');
        document.getElementById('editProfileBtn').style.display = 'flex';
    });
    </script>
</body>
</html> 