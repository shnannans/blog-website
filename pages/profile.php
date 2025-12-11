<?php
require "../inc/check_session.inc.php";

// Handle profile picture upload
$successMsg = "";
$errorMsg = "";

// Handle About Me update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['about_me'])) {
    $about_me = trim($_POST['about_me']);
    
    // Character limit check (500 characters)
    if (strlen($about_me) > 500) {
        $errorMsg = "About Me text exceeds the maximum of 500 characters.";
    } else {
        // Update database with new about_me
        $stmt = $conn->prepare("UPDATE user_info SET about_me = ? WHERE member_id = ?");
        $stmt->bind_param("si", $about_me, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $successMsg = "Profile updated successfully!";
            // Update session variable
            $_SESSION['about_me'] = $about_me;
        } else {
            $errorMsg = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle profile picture upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['croppedImage'])) {
    $target_dir = "../uploads/profile_pics/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0775, true);
    }
    
    // Generate a unique filename
    $new_filename = "user_" . $_SESSION['user_id'] . "_" . time() . ".jpg";
    $target_file = $target_dir . $new_filename;
    $upload_path = "uploads/profile_pics/" . $new_filename; // Path to store in DB
    
    // Get the image data from the form (removing the data URL part)
    $image_parts = explode(";base64,", $_POST['croppedImage']);
    $image_base64 = base64_decode($image_parts[1]);
    
    // Save the image file
    if (file_put_contents($target_file, $image_base64)) {
        // Update database with new profile pic path
        $stmt = $conn->prepare("UPDATE user_info SET profile_pic = ? WHERE member_id = ?");
        $stmt->bind_param("si", $upload_path, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $successMsg = "Profile picture updated successfully!";
            $_SESSION['profile_pic'] = $upload_path;
        } else {
            $errorMsg = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    } else {
        $errorMsg = "There was an error saving your image.";
    }
}

// Fetch the about_me field if not already in session
if (!isset($_SESSION['about_me'])) {
    $stmt = $conn->prepare("SELECT about_me FROM user_info WHERE member_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($about_me);
    $stmt->fetch();
    $stmt->close();
    
    $_SESSION['about_me'] = $about_me ?? '';
}

// Get current profile picture
$profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : "image/default_pfp.jpg";
$about_me = isset($_SESSION['about_me']) ? $_SESSION['about_me'] : '';

// Get follower/following counts
$counts_stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM user_follows WHERE follower_id = ?) AS following_count,
        (SELECT COUNT(*) FROM user_follows WHERE following_id = ?) AS followers_count
");
$counts_stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$counts_stmt->execute();
$counts_stmt->bind_result($following_count, $followers_count);
$counts_stmt->fetch();
$counts_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Profile</title>
    <?php include "../inc/head.inc.php"; ?>
    <!-- Add Cropper.js CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <style>
        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }
        .avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-info {
            margin-top: 20px;
        }
        .profile-row {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .btn-upload {
            display: none;
        }
        .change-photo-btn {
            margin-top: 10px;
        }
        /* Image editor styles */
        .image-editor {
            display: none;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .img-container {
            max-height: 400px;
            margin-bottom: 20px;
            position: relative;
        }
        .img-container img {
            display: block;
            max-width: 100%;
        }
        .cropper-container {
            margin-bottom: 20px;
        }
        /* Custom styles for circular cropper */
        .cropper-view-box,
        .cropper-face {
            border-radius: 50%;
        }
        .cropper-view-box {
            box-shadow: 0 0 0 1px #39f;
            outline: 0;
        }
        /* Darker background outside the view box */
        .cropper-modal {
            background-color: rgba(0, 0, 0, 0.7);
        }
        /* Line guides */
        .cropper-line {
            background-color: rgba(255, 255, 255, 0.5);
        }
        .cropper-point {
            background-color: #39f;
        }
        .editor-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        /* Show circular preview */
        .preview-container {
            width: 150px;
            height: 150px;
            overflow: hidden;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 3px solid #ddd;
        }
        #imagePreview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .zoom-instructions {
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            margin: 10px 0 20px;
        }
        /* About Me styles */
        .about-me-section {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .about-me-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            text-align: left !important;
        }
        .about-me-header h4 {
            margin: 0;
            text-align: left !important;
        }
        .about-me-display {
            padding: 0 !important;
            margin: 0 !important; 
            white-space: pre-wrap;
            text-align: left !important;
            text-indent: 0 !important;
            display: inline-block;
            width: 100%;
        }
        .about-me-display * {
            text-align: left !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
            text-indent: 0 !important;
        }
        .about-me-edit {
            display: none;
            text-align: left !important;
        }
        .about-me-edit textarea {
            text-align: left !important;
            width: 100%;
        }
        .char-counter {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
        }
        .about-me-actions {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .follow-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 15px 0;
        }
        .follow-stat {
            text-align: center;
        }
        .follow-count {
            font-weight: bold;
            font-size: 1.2rem;
        }
        .follow-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        #editAboutBtn {
            color: #004085; /* darker than Bootstrap's default */
            border-color: #004085;
        }
    </style>
</head>
<body> 
    <?php include "../inc/login_nav.inc.php"; ?>
    
    <div class="container profile-container">
        <!-- Success/Error messages -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $successMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $errorMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        
        <!-- Profile Information Section -->
        <main class="profile-section" id="profileSection" aria-label="Blooger: User Profile">
            <h1 class="visually-hidden">User Dashboard</h1>
            <div class="profile-header">
                <!-- Profile picture -->
                <div class="avatar-container">
                    <img src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="avatar">
                </div>
                
                <!-- Explicit change photo button -->
                <button class="btn btn-sm btn-primary change-photo-btn" id="changePhotoBtn">
                    <i class="fas fa-camera"></i> Change Profile Picture
                </button>
                
                <h2 class="mt-3"><?php echo htmlspecialchars($_SESSION['fname'] . " " . $_SESSION['lname']); ?></h2>
                <p class="text-muted">@<?php echo htmlspecialchars($_SESSION['username']); ?></p>
                
                <!-- Follow stats -->
                <div class="follow-stats">
                    <a href="followers.php?user_id=<?= $_SESSION['user_id'] ?>&tab=following" class="follow-stat text-decoration-none">
                        <div class="follow-count"><?= $following_count ?></div>
                        <div class="follow-label">Following</div>
                    </a>
                    <a href="followers.php?user_id=<?= $_SESSION['user_id'] ?>&tab=followers" class="follow-stat text-decoration-none">
                        <div class="follow-count"><?= $followers_count ?></div>
                        <div class="follow-label">Followers</div>
                    </a>
                </div>
            </div>
            
            <div class="profile-info">
                <div class="profile-row">
                    <strong>Member ID:</strong> <?php echo htmlspecialchars($_SESSION['user_id']); ?>
                </div>
                <div class="profile-row">
                    <strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['fname'] . " " . $_SESSION['lname']); ?>
                </div>
                <div class="profile-row">
                    <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?>
                </div>
            </div>
            
            <!-- About Me Section -->
            <div class="about-me-section">
                <div class="about-me-header">
                    <h3>About Me</h3>
                    <button id="editAboutBtn" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
                
                <!-- Display About Me -->
                <div id="aboutMeDisplay" class="about-me-display">
                    <?php if (empty($about_me)): ?>
                        <span class="text-muted" style="text-align: left !important; display: block;">Tell others about yourself...</span>
                    <?php else: ?>
                        <span style="text-align: left !important; display: block;"><?php echo nl2br(htmlspecialchars($about_me)); ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Edit About Me -->
                <div id="aboutMeEdit" class="about-me-edit">
                    <form method="POST" action="">
                        <div class="form-group">
                            <textarea id="aboutMeText" name="about_me" class="form-control" rows="5" maxlength="500" style="text-align: left !important;" placeholder="Share something about yourself..."><?php echo htmlspecialchars($about_me); ?></textarea>
                            <div class="char-counter">
                                <span id="charCount">0</span>/500 characters
                            </div>
                        </div>
                        <div class="about-me-actions">
                            <button type="button" id="cancelEditBtn" class="btn btn-sm btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-success">Save</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <a href="delete_profile.php" class="btn btn-danger">
                    <i class="material-symbols-outlined">delete</i> Delete Account
                </a>
            </div>
        </main>
        
        <!-- Image Editor Section (Hidden by default) -->
        <div class="image-editor" id="imageEditor">
            <h3 class="text-center mb-4">Edit Profile Picture</h3>
            
            <!-- File Upload Input (Hidden) -->
            <input type="file" id="imageInput" accept="image/*" class="d-none">
            
            <!-- Live Preview -->
            <div class="preview-container mb-4">
                <img id="imagePreview" src="../<?php echo htmlspecialchars($profile_pic); ?>" alt="Preview">
            </div>
            
            <!-- Instructions for zooming -->
            <div class="zoom-instructions">
                <i class="fas fa-info-circle"></i> Use mouse wheel to zoom in/out. Drag image to reposition.
            </div>
            
            <!-- Image Container for Cropper -->
            <div class="img-container">
                <img id="imageToCrop" src="#" alt="Upload an image">
            </div>
            
            <!-- Editor Controls -->
            <div class="editor-controls">
                <button id="rotateLeftBtn" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> Rotate Left
                </button>
                <button id="rotateRightBtn" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Rotate Right
                </button>
                <button id="saveImageBtn" class="btn btn-success">
                    <i class="fas fa-check"></i> Save
                </button>
                <button id="cancelImageEditBtn" class="btn btn-danger">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
            
            <!-- Hidden Form for Submitting Cropped Image -->
            <form id="cropForm" method="POST" enctype="multipart/form-data" style="display:none;">
                <input type="hidden" name="croppedImage" id="croppedImageInput">
            </form>
        </div>
    </div>
    
    <?php include "../inc/footer.inc.php"; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Force left alignment for About Me text
        document.querySelectorAll('.about-me-display, .about-me-display *').forEach(function(el) {
            el.style.textAlign = 'left';
            el.style.textIndent = '0';
            el.style.marginLeft = '0';
            el.style.paddingLeft = '0';
        });
        
        // Elements for Profile Picture Editor
        const profileSection = document.getElementById('profileSection');
        const imageEditor = document.getElementById('imageEditor');
        const changePhotoBtn = document.getElementById('changePhotoBtn');
        const imageInput = document.getElementById('imageInput');
        const imageToCrop = document.getElementById('imageToCrop');
        const imagePreview = document.getElementById('imagePreview');
        const rotateLeftBtn = document.getElementById('rotateLeftBtn');
        const rotateRightBtn = document.getElementById('rotateRightBtn');
        const saveImageBtn = document.getElementById('saveImageBtn');
        const cancelImageEditBtn = document.getElementById('cancelImageEditBtn');
        const cropForm = document.getElementById('cropForm');
        const croppedImageInput = document.getElementById('croppedImageInput');
        
        // Elements for About Me section
        const editAboutBtn = document.getElementById('editAboutBtn');
        const aboutMeDisplay = document.getElementById('aboutMeDisplay');
        const aboutMeEdit = document.getElementById('aboutMeEdit');
        const aboutMeText = document.getElementById('aboutMeText');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const charCount = document.getElementById('charCount');
        
        let cropper;
        
        // About Me character counter
        if (aboutMeText) {
            // Initialize character count
            charCount.textContent = aboutMeText.value.length;
            
            // Update character count on input
            aboutMeText.addEventListener('input', function() {
                charCount.textContent = this.value.length;
                
                // Change color when approaching limit
                if (this.value.length > 450) {
                    charCount.style.color = '#dc3545';
                } else {
                    charCount.style.color = '#6c757d';
                }
            });
        }
        
        // Edit About Me button
        if (editAboutBtn) {
            editAboutBtn.addEventListener('click', function() {
                aboutMeDisplay.style.display = 'none';
                aboutMeEdit.style.display = 'block';
                editAboutBtn.style.display = 'none';
            });
        }
        
        // Cancel Edit About Me button
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', function() {
                aboutMeDisplay.style.display = 'block';
                aboutMeEdit.style.display = 'none';
                editAboutBtn.style.display = 'inline-block';
            });
        }
        
        // Change photo button click
        if (changePhotoBtn) {
            changePhotoBtn.addEventListener('click', function() {
                imageInput.click();
            });
        }
        
        // File input change
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                const files = e.target.files;
                
                if (files && files.length > 0) {
                    const file = files[0];
                    
                    // Only process image files
                    if (!file.type.match('image.*')) {
                        alert('Please select an image file.');
                        return;
                    }
                    
                    // FileReader to load the image
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Set the image source
                        imageToCrop.src = e.target.result;
                        
                        // Show image editor, hide profile
                        profileSection.style.display = 'none';
                        imageEditor.style.display = 'block';
                        
                        // Initialize cropper after image is loaded
                        imageToCrop.onload = function() {
                            if (cropper) {
                                cropper.destroy();
                            }
                            
                            cropper = new Cropper(imageToCrop, {
                                aspectRatio: 1, // Square
                                viewMode: 1,    // Restrict the crop box to not exceed the size of the canvas
                                guides: true,   // Show grid lines
                                dragMode: 'move', // Allow moving the image
                                cropBoxResizable: true, // Allow resizing the crop box
                                cropBoxMovable: true,   // Allow moving the crop box
                                minContainerWidth: 250,
                                minContainerHeight: 250,
                                minCropBoxWidth: 100,
                                minCropBoxHeight: 100,
                                // Enable mouse wheel zoom
                                wheelZoomRatio: 0.1,
                                // Update preview on crop box change
                                crop: function(event) {
                                    updatePreview();
                                },
                                ready: function() {
                                    updatePreview();
                                }
                            });
                        };
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        }
        
        // Update preview image
        function updatePreview() {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({
                    width: 300,
                    height: 300,
                    fillColor: '#fff',
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });
                
                if (canvas) {
                    imagePreview.src = canvas.toDataURL('image/jpeg', 0.9);
                }
            }
        }
        
        // Rotate buttons
        if (rotateLeftBtn) {
            rotateLeftBtn.addEventListener('click', function() {
                if (cropper) {
                    cropper.rotate(-90);
                    updatePreview();
                }
            });
        }
        
        if (rotateRightBtn) {
            rotateRightBtn.addEventListener('click', function() {
                if (cropper) {
                    cropper.rotate(90);
                    updatePreview();
                }
            });
        }
        
        // Save button
        if (saveImageBtn) {
            saveImageBtn.addEventListener('click', function() {
                if (cropper) {
                    // Get the cropped canvas data
                    const canvas = cropper.getCroppedCanvas({
                        width: 300,   // Max width
                        height: 300,  // Max height
                        fillColor: '#fff', // When the cropped area is empty, it will be filled with this color
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high'
                    });
                    
                    if (canvas) {
                        // Convert canvas to data URL and set as input value
                        const imageData = canvas.toDataURL('image/jpeg', 0.9); // Format and quality
                        croppedImageInput.value = imageData;
                        
                        // Submit the form
                        cropForm.submit();
                    }
                }
            });
        }
        
        // Cancel button
        if (cancelImageEditBtn) {
            cancelImageEditBtn.addEventListener('click', function() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                
                // Reset file input
                imageInput.value = '';
                
                // Show profile, hide editor
                profileSection.style.display = 'block';
                imageEditor.style.display = 'none';
            });
        }
    });
    </script>
</body>
</html>