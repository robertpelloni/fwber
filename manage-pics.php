<?php
require_once('_init.php');
require_once('PhotoManager.php');

// Ensure the user is logged in
if (!validateSessionOrCookiesReturnLoggedIn()) {
    header('Location: /signin.php');
    exit();
}

$photoManager = new PhotoManager($pdo);
$userId = getUserIdByEmail($_SESSION['email']);
$photos = $photoManager->getPhotos($userId);

$publicPhotos = array_filter($photos, function($photo) { return !$photo['is_private']; });
$privatePhotos = array_filter($photos, function($photo) { return $photo['is_private']; });

?>
<!doctype html>
<html lang="en">
<head>
    <title><?php echo getSiteName(); ?> - Manage Pictures</title>
	<?php include("head.php");?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
	<?php include("h.php");?>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">Manage Your Pictures</h1>

        <!-- Public Photos Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Public Pictures</h2>
            <p class="text-gray-600 mb-4">These photos are visible on your public profile.</p>
            <div id="public-photos-grid" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($publicPhotos as $photo): ?>
                    <div class="relative group" id="photo-<?php echo $photo['id']; ?>">
                        <img src="/fwberImageStore/<?php echo $photo['filename']; ?>" class="w-full h-48 object-cover rounded-lg">
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="delete-photo-btn text-white text-2xl" data-photo-id="<?php echo $photo['id']; ?>"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4">
                <form class="upload-form" data-is-private="0">
                    <input type="file" name="photo" class="file-input">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Upload Public Photo</button>
                </form>
            </div>
        </div>

        <!-- Private Photos Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Private Pictures</h2>
            <p class="text-gray-600 mb-4">These photos are only visible to users you have matched with.</p>
            <div id="private-photos-grid" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($privatePhotos as $photo): ?>
                    <div class="relative group" id="photo-<?php echo $photo['id']; ?>">
                        <img src="/fwberImageStore/<?php echo $photo['filename']; ?>" class="w-full h-48 object-cover rounded-lg">
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="delete-photo-btn text-white text-2xl" data-photo-id="<?php echo $photo['id']; ?>"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4">
                <form class="upload-form" data-is-private="1">
                    <input type="file" name="photo" class="file-input">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Upload Private Photo</button>
                </form>
            </div>
        </div>

    </div>
    <?php include("f.php");?>
    <script src="/js/jquery-3.4.1.min.js"></script>
    <script>
    $(document).ready(function() {
        // Handle photo uploads
        $('.upload-form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const fileInput = form.find('.file-input')[0];
            const isPrivate = form.data('is-private');

            if (fileInput.files.length === 0) {
                alert('Please select a file to upload.');
                return;
            }

            const formData = new FormData();
            formData.append('photo', fileInput.files[0]);
            formData.append('is_private', isPrivate);

            $.ajax({
                url: '/api/upload-photo.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        location.reload(); // Simple reload for now
                    } else {
                        alert('Upload failed: ' + response.error);
                    }
                },
                error: function() {
                    alert('An error occurred during upload.');
                }
            });
        });

        // Handle photo deletions
        $(document).on('click', '.delete-photo-btn', function() {
            const photoId = $(this).data('photo-id');
            if (!confirm('Are you sure you want to delete this photo?')) {
                return;
            }

            $.ajax({
                url: '/api/delete-photo.php',
                type: 'POST',
                data: { photo_id: photoId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#photo-' + photoId).remove();
                    } else {
                        alert('Deletion failed: ' + response.error);
                    }
                },
                error: function() {
                    alert('An error occurred during deletion.');
                }
            });
        });
    });
    </script>
</body>
</html>
