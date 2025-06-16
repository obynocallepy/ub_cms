<?php
// admin.php
require_once 'server/auth.php'; // This will handle session and admin check
require_once 'server/config.php'; // Database connection

// Define upload directory for news images
define('NEWS_IMAGE_UPLOAD_DIR', 'image/news/');
// Define upload directory for slider images
define('SLIDER_IMAGE_UPLOAD_DIR', 'image/sliders/');


// Function to sanitize input
function sanitize_input($data) {
    global $link; // Use the global $link variable from config.php
    if (is_array($data)) { // Handle arrays if necessary (e.g., for checkboxes)
        return array_map('sanitize_input', $data);
    }
    return mysqli_real_escape_string($link, htmlspecialchars(strip_tags(trim($data))));
}

// --- Helper function to build a hierarchical menu array ---
function build_menu_tree($items, $parentId = NULL) {
    $branch = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $children = build_menu_tree($items, $item['id']);
            if ($children) {
                $item['children'] = $children;
            }
            $branch[] = $item;
        }
    }
    return $branch;
}

// --- Handle Menu Item CRUD Operations ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])&& (($_POST['action'] == 'add_menu_item') || ($_POST['action'] == 'edit_menu_item') || ($_POST['action'] == 'delete_menu_item'))) {
    $action = sanitize_input($_POST['action']);

    if ($action == 'add_menu_item' || $action == 'edit_menu_item') {
        $title = sanitize_input($_POST['menu_title']);
        $url = sanitize_input($_POST['menu_url']);
        $parent_id = !empty($_POST['menu_parent']) ? sanitize_input($_POST['menu_parent']) : NULL;
        $item_order = sanitize_input($_POST['menu_order']);
        $status = sanitize_input($_POST['menu_status']);

        if ($action == 'add_menu_item') {
            $sql = "INSERT INTO menu_items (title, url, parent_id, item_order, status) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssiis", $title, $url, $parent_id, $item_order, $status);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = "Menu item added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding menu item: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit_menu_item' && isset($_POST['menu_id'])) {
            $id = sanitize_input($_POST['menu_id']);
            $sql = "UPDATE menu_items SET title=?, url=?, parent_id=?, item_order=?, status=? WHERE id=?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssiisi", $title, $url, $parent_id, $item_order, $status, $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = "Menu item updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating menu item: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action == 'delete_menu_item' && isset($_POST['menu_id'])) {
        $id = sanitize_input($_POST['menu_id']);
        $sql = "DELETE FROM menu_items WHERE id=?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Menu item deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting menu item: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: admin.php#menu-management");
    exit();
}


// --- Handle News CRUD Operations ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && (($_POST['action'] == 'add_news') || ($_POST['action'] == 'edit_news') || ($_POST['action'] == 'delete_news'))) {
    $action = sanitize_input($_POST['action']);

    if ($action == 'add_news' || $action == 'edit_news') {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $category = sanitize_input($_POST['category']);
        $available_at = sanitize_input($_POST['availableAt']);
        $expire_at = !empty($_POST['expireAt']) ? sanitize_input($_POST['expireAt']) : null;
        $status = isset($_POST['status']) ? sanitize_input($_POST['status']) : 'draft';
        $image_url = ''; // Initialize image URL

        // Handle image upload
        if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] == 0) {
            if (!is_dir(NEWS_IMAGE_UPLOAD_DIR)) {
                mkdir(NEWS_IMAGE_UPLOAD_DIR, 0777, true);
            }
            $image_name = basename($_FILES["news_image"]["name"]);
            $imageFileType = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
            $new_image_name = uniqid('news_', true) . '.' . $imageFileType;
            $target_file = NEWS_IMAGE_UPLOAD_DIR . $new_image_name;

            // Allow certain file formats
            $allowed_types = array("jpg", "png", "jpeg", "gif");
            if (!in_array($imageFileType, $allowed_types)) {
                $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed for news images.";
                header("Location: admin.php#news");
                exit();
            }

            if (move_uploaded_file($_FILES["news_image"]["tmp_name"], $target_file)) {
                $image_url = $target_file;
            } else {
                $_SESSION['error'] = "Sorry, there was an error uploading your news image.";
                header("Location: admin.php#news");
                exit();
            }
        } elseif ($action == 'edit_news' && isset($_POST['current_news_image_url'])) {
            // If no new image uploaded, retain current one
            $image_url = sanitize_input($_POST['current_news_image_url']);
        }


        if ($action == 'add_news') {
            $sql = "INSERT INTO news (title, description, category, available_at, expire_at, status, image_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssssi", $title, $description, $category, $available_at, $expire_at, $status, $image_url, $_SESSION['id']);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = "News article added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding news article: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit_news' && isset($_POST['id'])) {
            $id = sanitize_input($_POST['id']);
            
            // Fetch current image_url if a new image was uploaded
            if (!empty($image_url) && isset($_FILES['news_image']) && $_FILES['news_image']['error'] == 0) {
                $sql_select_old_image = "SELECT image_url FROM news WHERE id = ?";
                if ($stmt_select = mysqli_prepare($link, $sql_select_old_image)) {
                    mysqli_stmt_bind_param($stmt_select, "i", $id);
                    mysqli_stmt_execute($stmt_select);
                    mysqli_stmt_bind_result($stmt_select, $old_image_url);
                    mysqli_stmt_fetch($stmt_select);
                    mysqli_stmt_close($stmt_select);

                    // Delete old image file if it exists and is different from the new one
                    if (!empty($old_image_url) && file_exists($old_image_url) && $old_image_url != $image_url) {
                        unlink($old_image_url);
                    }
                }
            } else {
                // If no new image uploaded, keep the existing one from the hidden field
                $image_url = sanitize_input($_POST['current_news_image_url']);
            }

            $sql = "UPDATE news SET title=?, description=?, category=?, available_at=?, expire_at=?, status=?, image_url=? WHERE id=?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssssi", $title, $description, $category, $available_at, $expire_at, $status, $image_url, $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = "News article updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating news article: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action == 'delete_news' && isset($_POST['id'])) {
        $id = sanitize_input($_POST['id']);

        // Fetch image_url to delete the file
        $sql_select_image = "SELECT image_url FROM news WHERE id = ?";
        if ($stmt_select = mysqli_prepare($link, $sql_select_image)) {
            mysqli_stmt_bind_param($stmt_select, "i", $id);
            mysqli_stmt_execute($stmt_select);
            mysqli_stmt_bind_result($stmt_select, $image_to_delete);
            mysqli_stmt_fetch($stmt_select);
            mysqli_stmt_close($stmt_select);

            // Delete the image file from the server
            if (!empty($image_to_delete) && file_exists($image_to_delete)) {
                unlink($image_to_delete);
            }
        }

        $sql = "DELETE FROM news WHERE id=?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "News article deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting news article: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    }
    // Redirect to prevent form resubmission
    header("Location: admin.php#news");
    exit();
}

// --- Handle Event CRUD Operations ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && (($_POST['action'] == 'add_event') || ($_POST['action'] == 'edit_event') || ($_POST['action'] == 'delete_event'))) {
    $action = sanitize_input($_POST['action']);

    if ($action == 'add_event' || $action == 'edit_event') {
        $title = sanitize_input($_POST['event_title']);
        $date = sanitize_input($_POST['event_date']);
        $location = sanitize_input($_POST['event_location']);
        $description = sanitize_input($_POST['event_description']);
        $status = isset($_POST['event_status']) ? sanitize_input($_POST['event_status']) : 'active';

        if ($action == 'add_event') {
            $sql = "INSERT INTO events (title, description, date, location, status, created_by) VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $date, $location, $status, $_SESSION['id']);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = "Event added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding event: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit_event' && isset($_POST['event_id'])) {
            $id = sanitize_input($_POST['event_id']);
            $sql = "UPDATE events SET title=?, description=?, date=?, location=?, status=? WHERE id=?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $date, $location, $status, $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = "Event updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating event: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action == 'delete_event' && isset($_POST['event_id'])) {
        $id = sanitize_input($_POST['event_id']);
        $sql = "DELETE FROM events WHERE id=?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Event deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting event: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: admin.php#events");
    exit();
}

// --- Handle Slider CRUD Operations ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && (($_POST['action'] == 'add_slider') || ($_POST['action'] == 'edit_slider') || ($_POST['action'] == 'delete_slider'))) {
    $action = sanitize_input($_POST['action']);

    if ($action == 'add_slider' || $action == 'edit_slider') {
        $title = sanitize_input($_POST['slider_title']);
        $link_url = sanitize_input($_POST['slider_link_url']);
        $status = isset($_POST['slider_status']) ? sanitize_input($_POST['slider_status']) : 'active';
        $image_url = '';

        // Handle image upload
        if (isset($_FILES['slider_image']) && $_FILES['slider_image']['error'] == 0) {
            if (!is_dir(SLIDER_IMAGE_UPLOAD_DIR)) {
                mkdir(SLIDER_IMAGE_UPLOAD_DIR, 0777, true);
            }
            $image_name = basename($_FILES["slider_image"]["name"]);
            $imageFileType = strtolower(pathinfo($image_name,PATHINFO_EXTENSION));
            $new_image_name = uniqid('slider_', true) . '.' . $imageFileType;
            $target_file = SLIDER_IMAGE_UPLOAD_DIR . $new_image_name;

            // Allow certain file formats
            $allowed_types = array("jpg", "png", "jpeg", "gif");
            if(!in_array($imageFileType, $allowed_types)) {
                $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed for slider images.";
                header("Location: admin.php#sliders");
                exit();
            }

            if (move_uploaded_file($_FILES["slider_image"]["tmp_name"], $target_file)) {
                $image_url = $target_file;
            } else {
                $_SESSION['error'] = "Sorry, there was an error uploading your slider image.";
                header("Location: admin.php#sliders");
                exit();
            }
        } elseif ($action == 'edit_slider' && isset($_POST['current_image_url'])) {
            // If no new image uploaded, retain current one
            $image_url = sanitize_input($_POST['current_image_url']);
        }

        if ($action == 'add_slider') {
            $sql = "INSERT INTO newssliders (title, image_url, link_url, status) VALUES (?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssss", $title, $image_url, $link_url, $status);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = "Slider added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding slider: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action == 'edit_slider' && isset($_POST['slider_id'])) {
            $id = sanitize_input($_POST['slider_id']);
            
            // Fetch current image_url if a new image was uploaded
            if (!empty($image_url) && isset($_FILES['slider_image']) && $_FILES['slider_image']['error'] == 0) {
                $sql_select_old_image = "SELECT image_url FROM newssliders WHERE id = ?";
                if ($stmt_select = mysqli_prepare($link, $sql_select_old_image)) {
                    mysqli_stmt_bind_param($stmt_select, "i", $id);
                    mysqli_stmt_execute($stmt_select);
                    mysqli_stmt_bind_result($stmt_select, $old_image_url);
                    mysqli_stmt_fetch($stmt_select);
                    mysqli_stmt_close($stmt_select);

                    // Delete old image file if it exists and is different from the new one
                    if (!empty($old_image_url) && file_exists($old_image_url) && $old_image_url != $image_url) {
                        unlink($old_image_url);
                    }
                }
            } else {
                // If no new image uploaded, keep the existing one from the hidden field
                $image_url = sanitize_input($_POST['current_image_url']);
            }

            $sql = "UPDATE newssliders SET title=?, image_url=?, link_url=?, status=? WHERE id=?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssssi", $title, $image_url, $link_url, $status, $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = "Slider updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating slider: " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action == 'delete_slider' && isset($_POST['slider_id'])) {
        $id = sanitize_input($_POST['slider_id']);

        // Fetch image_url to delete the file
        $sql_select_image = "SELECT image_url FROM newssliders WHERE id = ?";
        if ($stmt_select = mysqli_prepare($link, $sql_select_image)) {
            mysqli_stmt_bind_param($stmt_select, "i", $id);
            mysqli_stmt_execute($stmt_select);
            mysqli_stmt_bind_result($stmt_select, $image_to_delete);
            mysqli_stmt_fetch($stmt_select);
            mysqli_stmt_close($stmt_select);

            // Delete the image file from the server
            if (!empty($image_to_delete) && file_exists($image_to_delete)) {
                unlink($image_to_delete);
            }
        }

        $sql = "DELETE FROM newssliders WHERE id=?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Slider deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting slider: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: admin.php#sliders");
    exit();
}

// --- Fetch Dashboard Counts ---
$news_count = 0;
$events_count = 0;
$sliders_count = 0;

$sql_news_count = "SELECT COUNT(*) FROM news";
if ($result = mysqli_query($link, $sql_news_count)) {
    $row = mysqli_fetch_array($result);
    $news_count = $row[0];
    mysqli_free_result($result);
}

$sql_events_count = "SELECT COUNT(*) FROM events";
if ($result = mysqli_query($link, $sql_events_count)) {
    $row = mysqli_fetch_array($result);
    $events_count = $row[0];
    mysqli_free_result($result);
}

$sql_sliders_count = "SELECT COUNT(*) FROM newssliders";
if ($result = mysqli_query($link, $sql_sliders_count)) {
    $row = mysqli_fetch_array($result);
    $sliders_count = $row[0];
    mysqli_free_result($result);
}

// --- Fetch Menu Items for display ---
$menu_items_raw = [];
$sql_menu = "SELECT id, title, url, parent_id, item_order, status FROM menu_items ORDER BY parent_id ASC, item_order ASC";
if ($result = mysqli_query($link, $sql_menu)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $menu_items_raw[] = $row;
    }
    mysqli_free_result($result);
}
$menu_tree = build_menu_tree($menu_items_raw);


// --- Fetch News Articles for display ---
$news_articles = [];
$search_news_query = isset($_GET['search_news']) ? sanitize_input($_GET['search_news']) : '';
$news_where_clause = '';
if (!empty($search_news_query)) {
    $news_where_clause = " WHERE title LIKE ? OR description LIKE ?";
}

$news_per_page = 10; // Can be dynamic from settings
$current_news_page = isset($_GET['news_page']) ? (int)$_GET['news_page'] : 1;
$news_offset = ($current_news_page - 1) * $news_per_page;

// Added image_url to SELECT
$sql_news = "SELECT id, title, category, available_at, expire_at, status, description, image_url FROM news" . $news_where_clause . " ORDER BY created_at DESC LIMIT ?, ?";
if ($stmt = mysqli_prepare($link, $sql_news)) {
    if (!empty($search_news_query)) {
        $param_search = "%" . $search_news_query . "%";
        mysqli_stmt_bind_param($stmt, "ssii", $param_search, $param_search, $news_offset, $news_per_page);
    } else {
        mysqli_stmt_bind_param($stmt, "ii", $news_offset, $news_per_page);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $news_articles[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$sql_total_news = "SELECT COUNT(*) FROM news" . $news_where_clause;
if ($stmt_total = mysqli_prepare($link, $sql_total_news)) {
    if (!empty($search_news_query)) {
        $param_search = "%" . $search_news_query . "%";
        mysqli_stmt_bind_param($stmt_total, "ss", $param_search, $param_search);
    }
    mysqli_stmt_execute($stmt_total);
    mysqli_stmt_bind_result($stmt_total, $total_news_rows);
    mysqli_stmt_fetch($stmt_total);
    mysqli_stmt_close($stmt_total);
} else {
    $total_news_rows = 0; // Fallback
}
$total_news_pages = ceil($total_news_rows / $news_per_page);

// --- Fetch Events for display ---
$events_list = [];
$search_event_query = isset($_GET['search_event']) ? sanitize_input($_GET['search_event']) : '';
$event_where_clause = '';
if (!empty($search_event_query)) {
    $event_where_clause = " WHERE title LIKE ? OR description LIKE ? OR location LIKE ?";
}

$events_per_page = 10; // Can be dynamic from settings
$current_event_page = isset($_GET['event_page']) ? (int)$_GET['event_page'] : 1;
$event_offset = ($current_event_page - 1) * $events_per_page;

$sql_events = "SELECT id, title, date, location, status, description FROM events" . $event_where_clause . " ORDER BY date DESC LIMIT ?, ?";
if ($stmt = mysqli_prepare($link, $sql_events)) {
    if (!empty($search_event_query)) {
        $param_search = "%" . $search_event_query . "%";
        mysqli_stmt_bind_param($stmt, "sssii", $param_search, $param_search, $param_search, $event_offset, $events_per_page);
    } else {
        mysqli_stmt_bind_param($stmt, "ii", $event_offset, $events_per_page);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $events_list[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$sql_total_events = "SELECT COUNT(*) FROM events" . $event_where_clause;
if ($stmt_total = mysqli_prepare($link, $sql_total_events)) {
    if (!empty($search_event_query)) {
        $param_search = "%" . $search_event_query . "%";
        mysqli_stmt_bind_param($stmt_total, "sss", $param_search, $param_search, $param_search);
    }
    mysqli_stmt_execute($stmt_total);
    mysqli_stmt_bind_result($stmt_total, $total_event_rows);
    mysqli_stmt_fetch($stmt_total);
    mysqli_stmt_close($stmt_total);
} else {
    $total_event_rows = 0; // Fallback
}
$total_event_pages = ceil($total_event_rows / $events_per_page);

// --- Fetch Sliders for display ---
$sliders_list = [];
$sql_sliders = "SELECT id, title, image_url, link_url, status FROM newssliders ORDER BY created_at DESC";
if ($result = mysqli_query($link, $sql_sliders)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sliders_list[] = $row;
    }
    mysqli_free_result($result);
}

// Close connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>University CMS - Admin Panel</title>
  <link rel="stylesheet" href="css/admin-styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <!-- Sortable.js library for drag and drop functionality -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <style>
    /* Basic styling for messages */
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
    }
    /* Add some styling for the slider image preview */
    .slider-image-preview, .news-image-preview {
        max-width: 100px;
        max-height: 60px;
        object-fit: cover;
        border-radius: 4px;
    }
    /* Styles for the dynamically added forms in modals */
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        margin-bottom: .5rem;
        font-weight: bold;
    }
    .form-control {
        width: 100%;
        padding: .375rem .75rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        border-radius: .25rem;
        transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    }
    textarea.form-control {
        min-height: 80px;
    }
    .btn {
        display: inline-block;
        font-weight: 400;
        color: #212529;
        text-align: center;
        vertical-align: middle;
        cursor: pointer;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: .375rem .75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: .25rem;
        transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    }
    .btn-primary {
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
    }
    .btn-success {
        color: #fff;
        background-color: #28a745;
        border-color: #28a745;
    }
    .btn-danger {
        color: #fff;
        background-color: #dc3545;
        border-color: #dc3545;
    }
    .btn-secondary {
        color: #fff;
        background-color: #6c757d;
        border-color: #6c757d;
    }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        padding-top: 1rem;
        border-top: 1px solid #dee2e6;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .data-table th, .data-table td {
        padding: 8px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }
    .data-table th {
        background-color: #f2f2f2;
    }
    .data-table tbody tr:hover {
        background-color: #f5f5f5;
    }
    .pagination {
        display: flex;
        justify-content: center;
        padding: 20px 0;
    }
    .pagination .btn {
        margin: 0 5px;
    }
    .page-numbers button {
        background-color: #e9ecef;
        border: 1px solid #dee2e6;
        color: #007bff;
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 4px;
    }
    .page-numbers button.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    .search-box {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .search-box input[type="text"] {
        flex-grow: 1;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .search-box button {
        padding: 8px 12px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .search-box button:hover {
        background-color: #0056b3;
    }
    .sidebar-nav ul li a{
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <aside class="sidebar">
      <div class="sidebar-header">
      <img src="image/university logo.jpeg" height="40" weight="150" alt="University Logo">
        <h2>Admin Panel</h2>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="active"><a href="#dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li><a href="#menu-management"><i class="fas fa-bars"></i> Menu Management</a></li>
          <li><a href="#sliders"><i class="fas fa-images"></i> Sliders</a></li>
          <li><a href="#news" id="getNews"><i class="fas fa-newspaper"></i> News</a></li>
          <li><a href="#events"><i class="fas fa-calendar-alt"></i> Events</a></li>
          <li><a href="#settings"><i class="fas fa-cog"></i> Settings</a></li>
          <li><a href="server/logout.php" id="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
          <li><a href="index.php"><i class="fas fa-images"></i> <span>Website</span></a></li>
        </ul>
      </nav>
    </aside>

    <main class="content">
      <header class="content-header">
        <div class="toggle-sidebar">
          <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
        </div>
        <div class="user-info">
         <b> <span>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span></b>
          <img src="image/university logo.jpeg" alt="Admin" class="user-avatar">
        </div>
      </header>

      <div class="content-body">
        <?php
        // Display session messages (success/error)
        if (isset($_SESSION['message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        <!-- Dashboard Section -->
        <section id="dashboard" class="content-section active">
          <h1>Dashboard</h1>
          <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
                <div class="stat-info">
                    <h3>News Articles</h3>
                    <p id="news-count"><?php echo $news_count; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <h3>Events</h3>
                    <p id="event-count"><?php echo $events_count; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-images"></i></div>
                <div class="stat-info">
                    <h3>Sliders</h3>
                    <p id="slider-count"><?php echo $sliders_count; ?></p>
                </div>
            </div>
        </div>
        
          <div class="recent-activity">
            <h2>Recent Activity</h2>
            <ul class="activity-list">
              <li>
                <span class="activity-time">2 hours ago</span>
                <span class="activity-desc">New news article published: "University Receives Research Grant"</span>
              </li>
              <li>
                <span class="activity-time">5 hours ago</span>
                <span class="activity-desc">Event updated: "Annual Science Fair"</span>
              </li>
              <li>
                <span class="activity-time">1 day ago</span>
                <span class="activity-desc">New slider image added to homepage</span>
              </li>
              <li>
                <span class="activity-time">2 days ago</span>
                <span class="activity-desc">Menu structure updated</span>
              </li>
            </ul>
          </div>
        </section>

        <!-- Menu Management Section -->
        <section id="menu-management" class="content-section">
          <h1>Menu Management</h1>
          <div class="menu-builder">
            <div class="menu-tools">
              <button id="add-menu-item-btn" class="btn primary"
                data-bs-toggle="modal"
                data-bs-target="#menuItemModal"
                data-action="add"
              ><i class="fas fa-plus"></i> Add Menu Item</button>
              <button id="save-menu" class="btn success"><i class="fas fa-save"></i> Save Changes</button>
            </div>
            <div class="menu-structure">
              <h3>Menu Structure</h3>
              <p class="help-text">Drag and drop items to rearrange. Click on an item to edit.</p>
              <table class="data-table">
                <thead>
                    <tr>
                        <th width="40"></th>
                        <th>Title</th>
                        <th>URL</th>
                        <th>Parent</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="menu-tree" class="sortable-tree">
                    <?php
                    // Function to render menu items recursively
                    function renderMenuItems($items, $level = 0) {
                        foreach ($items as $item) {
                            $indent = str_repeat('&mdash;', $level * 2); // Indent for sub-items
                            $parent_title = '';
                            if ($item['parent_id'] !== NULL) {
                                // Find parent title (this is inefficient, better to pass map or join)
                                global $menu_items_raw;
                                $parent = array_filter($menu_items_raw, function($p) use ($item) {
                                    return $p['id'] == $item['parent_id'];
                                });
                                $parent_title = !empty($parent) ? htmlspecialchars(reset($parent)['title']) : 'N/A';
                            }
                            ?>
                            <tr data-id="<?php echo $item['id']; ?>" data-parent-id="<?php echo htmlspecialchars($item['parent_id'] ?? ''); ?>">
                                <td><i class="fas fa-grip-vertical"></i></td>
                                <td><?php echo $indent . htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars($item['url']); ?></td>
                                <td><?php echo $parent_title; ?></td>
                                <td><?php echo htmlspecialchars($item['item_order']); ?></td>
                                <td><?php echo htmlspecialchars($item['status']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-menu-item-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#menuItemModal"
                                        data-action="edit"
                                        data-id="<?php echo $item['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                        data-url="<?php echo htmlspecialchars($item['url']); ?>"
                                        data-parent-id="<?php echo htmlspecialchars($item['parent_id'] ?? ''); ?>"
                                        data-order="<?php echo htmlspecialchars($item['item_order']); ?>"
                                        data-status="<?php echo htmlspecialchars($item['status']); ?>"
                                    ><i class="fas fa-edit"></i> Edit</button>
                                    <form action="admin.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="delete_menu_item">
                                        <input type="hidden" name="menu_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger delete-menu-item-btn" onclick="return confirm('Are you sure you want to delete this menu item and its children?');"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                            if (isset($item['children'])) {
                                renderMenuItems($item['children'], $level + 1);
                            }
                        }
                    }
                    renderMenuItems($menu_tree);
                    ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Sliders Section -->
        <section id="sliders" class="content-section">
          <h1>Sliders Management</h1>
          <div class="slider-management">
              <div class="slider-tools">
                  <button id="add-slider-btn" class="btn primary"
                    data-bs-toggle="modal"
                    data-bs-target="#sliderModal"
                    data-action="add"
                  >
                      <i class="fas fa-plus"></i> Add Slider
                  </button>
              </div>
              <p class="help-text">Drag and drop items to rearrange the order of sliders.</p>
              <div class="slider-list sortable-list" id="slider-items">
                  <?php if (!empty($sliders_list)): ?>
                      <?php foreach ($sliders_list as $slider): ?>
                          <div class="slider-item" data-id="<?php echo $slider['id']; ?>">
                              <div class="slider-info">
                                  <img src="<?php echo htmlspecialchars($slider['image_url']); ?>" alt="<?php echo htmlspecialchars($slider['title']); ?>" class="slider-image-preview">
                                  <span><?php echo htmlspecialchars($slider['title']); ?></span>
                                  <small>(<?php echo htmlspecialchars($slider['status']); ?>)</small>
                              </div>
                              <div class="slider-actions">
                                  <button class="btn btn-sm btn-primary edit-slider-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#sliderModal"
                                    data-action="edit"
                                    data-id="<?php echo $slider['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($slider['title']); ?>"
                                    data-image="<?php echo htmlspecialchars($slider['image_url']); ?>"
                                    data-link="<?php echo htmlspecialchars($slider['link_url']); ?>"
                                    data-status="<?php echo htmlspecialchars($slider['status']); ?>"
                                  ><i class="fas fa-edit"></i> Edit</button>
                                  <form action="admin.php" method="POST" style="display:inline-block;">
                                      <input type="hidden" name="action" value="delete_slider">
                                      <input type="hidden" name="slider_id" value="<?php echo $slider['id']; ?>">
                                      <button type="submit" class="btn btn-sm btn-danger delete-slider-btn" onclick="return confirm('Are you sure you want to delete this slider?');"><i class="fas fa-trash"></i> Delete</button>
                                  </form>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <p>No sliders found.</p>
                  <?php endif; ?>
              </div>
          </div>
      </section>

        <!-- News Section -->
        <section id="news" class="content-section">
        <h1>News Management</h1>
        <div class="news-management">
            <div class="news-tools">
                <button id="add-news-btn" type="button"
                  class="btn btn-primary"
                  data-bs-toggle="modal"
                  data-bs-target="#newsModal"
                  data-action="add"
                >
                  <i class="fas fa-plus"></i> Add News Article
                </button>
                
                <div class="search-box">
                    <form action="admin.php#news" method="GET" style="display:flex;">
                        <input type="text" name="search_news" placeholder="Search news..." value="<?php echo htmlspecialchars($search_news_query); ?>">
                        <button type="submit" id="search-button"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
            <p class="help-text">Drag and drop rows to rearrange the order of news articles.</p>
            <div class="news-list">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="40"></th>
                            <th>Image</th> <!-- Added Image Column -->
                            <th>Title</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="news-items" class="sortable-table">
                        <?php if (!empty($news_articles)): ?>
                            <?php foreach ($news_articles as $news): ?>
                                <tr data-id="<?php echo $news['id']; ?>">
                                    <td><i class="fas fa-grip-vertical"></i></td>
                                    <td>
                                        <?php if (!empty($news['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($news['image_url']); ?>" alt="News Image" class="news-image-preview">
                                        <?php else: ?>
                                            <span>No Image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($news['title']); ?></td>
                                    <td><?php echo htmlspecialchars($news['category']); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($news['available_at']))); ?></td>
                                    <td><?php echo htmlspecialchars($news['status']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-news-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#newsModal"
                                            data-action="edit"
                                            data-id="<?php echo $news['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($news['title']); ?>"
                                            data-description="<?php echo htmlspecialchars($news['description']); ?>"
                                            data-category="<?php echo htmlspecialchars($news['category']); ?>"
                                            data-availableat="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($news['available_at']))); ?>"
                                            data-expireat="<?php echo htmlspecialchars($news['expire_at'] ? date('Y-m-d\TH:i', strtotime($news['expire_at'])) : ''); ?>"
                                            data-status="<?php echo htmlspecialchars($news['status']); ?>"
                                            data-image="<?php echo htmlspecialchars($news['image_url']); ?>" <!-- Added image data -->
                                        ><i class="fas fa-edit"></i> Edit</button>
                                        <form action="admin.php" method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="delete_news">
                                            <input type="hidden" name="id" value="<?php echo $news['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger delete-news-btn" onclick="return confirm('Are you sure you want to delete this news article?');"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No news articles found.</td> <!-- Updated colspan -->
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <button class="btn page-prev" <?php echo ($current_news_page <= 1) ? 'disabled' : ''; ?> onclick="window.location.href='admin.php?news_page=<?php echo $current_news_page - 1; ?><?php echo !empty($search_news_query) ? '&search_news=' . urlencode($search_news_query) : ''; ?>#news'"><i class="fas fa-chevron-left"></i></button>
                    <div class="page-numbers" id="news-pagination-numbers">
                        <?php for ($i = 1; $i <= $total_news_pages; $i++): ?>
                            <button class="btn <?php echo ($i == $current_news_page) ? 'active' : ''; ?>" onclick="window.location.href='admin.php?news_page=<?php echo $i; ?><?php echo !empty($search_news_query) ? '&search_news=' . urlencode($search_news_query) : ''; ?>#news'"><?php echo $i; ?></button>
                        <?php endfor; ?>
                    </div>
                    <button class="btn page-next" <?php echo ($current_news_page >= $total_news_pages) ? 'disabled' : ''; ?> onclick="window.location.href='admin.php?news_page=<?php echo $current_news_page + 1; ?><?php echo !empty($search_news_query) ? '&search_news=' . urlencode($search_news_query) : ''; ?>#news'"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </section>
        <!-- Events Section -->
        <section id="events" class="content-section">
          <h1>Events Management</h1>
          <div class="events-management">
              <div class="events-tools">
                  <button id="add-event-btn" class="btn primary"
                    data-bs-toggle="modal"
                    data-bs-target="#eventModal"
                    data-action="add"
                  >
                      <i class="fas fa-plus"></i> Add Event
                  </button>
                  <div class="search-box">
                      <form action="admin.php#events" method="GET" style="display:flex;">
                          <input type="text" name="search_event" placeholder="Search events..." value="<?php echo htmlspecialchars($search_event_query); ?>">
                          <button type="submit" id="search-button"><i class="fas fa-search"></i></button>
                      </form>
                  </div>
              </div>
              <p class="help-text">Drag and drop rows to rearrange the order of events.</p>
              <div class="events-list">
                  <table class="data-table">
                      <thead>
                          <tr>
                              <th width="40"></th>
                              <th>Title</th>
                              <th>Date</th>
                              <th>Location</th>
                              <th>Status</th>
                              <th>Actions</th>
                          </tr>
                      </thead>
                      <tbody id="event-items" class="sortable-table">
                          <?php if (!empty($events_list)): ?>
                              <?php foreach ($events_list as $event): ?>
                                  <tr data-id="<?php echo $event['id']; ?>">
                                      <td><i class="fas fa-grip-vertical"></i></td>
                                      <td><?php echo htmlspecialchars($event['title']); ?></td>
                                      <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($event['date']))); ?></td>
                                      <td><?php echo htmlspecialchars($event['location']); ?></td>
                                      <td><?php echo htmlspecialchars($event['status']); ?></td>
                                      <td>
                                          <button class="btn btn-sm btn-primary edit-event-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#eventModal"
                                            data-action="edit"
                                            data-id="<?php echo $event['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($event['title']); ?>"
                                            data-description="<?php echo htmlspecialchars($event['description']); ?>"
                                            data-date="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['date']))); ?>"
                                            data-location="<?php echo htmlspecialchars($event['location']); ?>"
                                            data-status="<?php echo htmlspecialchars($event['status']); ?>"
                                          ><i class="fas fa-edit"></i> Edit</button>
                                          <form action="admin.php" method="POST" style="display:inline-block;">
                                              <input type="hidden" name="action" value="delete_event">
                                              <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                              <button type="submit" class="btn btn-sm btn-danger delete-event-btn" onclick="return confirm('Are you sure you want to delete this event?');"><i class="fas fa-trash"></i> Delete</button>
                                          </form>
                                      </td>
                                  </tr>
                              <?php endforeach; ?>
                          <?php else: ?>
                              <tr>
                                  <td colspan="6">No events found.</td>
                              </tr>
                          <?php endif; ?>
                      </tbody>
                  </table>
                  <div class="pagination">
                      <button class="btn page-prev" <?php echo ($current_event_page <= 1) ? 'disabled' : ''; ?> onclick="window.location.href='admin.php?event_page=<?php echo $current_event_page - 1; ?><?php echo !empty($search_event_query) ? '&search_event=' . urlencode($search_event_query) : ''; ?>#events'"><i class="fas fa-chevron-left"></i></button>
                      <div class="page-numbers" id="event-pagination-numbers">
                          <?php for ($i = 1; $i <= $total_event_pages; $i++): ?>
                              <button class="btn <?php echo ($i == $current_event_page) ? 'active' : ''; ?>" onclick="window.location.href='admin.php?event_page=<?php echo $i; ?><?php echo !empty($search_event_query) ? '&search_event=' . urlencode($search_event_query) : ''; ?>#events'"><?php echo $i; ?></button>
                          <?php endfor; ?>
                      </div>
                      <button class="btn page-next" <?php echo ($current_event_page >= $total_event_pages) ? 'disabled' : ''; ?> onclick="window.location.href='admin.php?event_page=<?php echo $current_event_page + 1; ?><?php echo !empty($search_event_query) ? '&search_event=' . urlencode($search_event_query) : ''; ?>#events'"><i class="fas fa-chevron-right"></i></button>
                  </div>
              </div>
          </div>
      </section>

        <!-- Settings Section -->
        <section id="settings" class="content-section">
          <h1>Settings</h1>
          <div class="settings-panel">
            <form id="settings-form">
              <div class="form-group">
                <label for="site-title">Site Title</label>
                <input type="text" id="site-title" name="site-title" value="University Name">
              </div>
              <div class="form-group">
                <label for="site-description">Site Description</label>
                <textarea id="site-description" name="site-description" rows="3">A leading institution for higher education and research.</textarea>
              </div>
              <div class="form-group">
                <label for="admin-email">Admin Email</label>
                <input type="email" id="admin-email" name="admin-email" value="admin@university.edu">
              </div>
              <div class="form-group">
                <label for="items-per-page">Items Per Page</label>
                <input type="number" id="items-per-page" name="items-per-page" min="5" max="50" value="10">
              </div>
              <div class="form-group">
                <label>Theme</label>
                <div class="theme-options">
                  <div class="theme-option active">
                    <div class="theme-preview light"></div>
                    <span>Light</span>
                  </div>
                  <div class="theme-option">
                    <div class="theme-preview dark"></div>
                    <span>Dark</span>
                  </div>
                  <div class="theme-option">
                    <div class="theme-preview blue"></div>
                    <span>Blue</span>
                  </div>
                </div>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn success">Save Settings</button>
                <button type="reset" class="btn">Reset</button>
              </div>
            </form>
          </div>
        </section>
      </div>
    </main>
  </div>

  <!-- Menu Item Modal -->
  <div
    class="modal fade"
    id="menuItemModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="menuItemModalTitle"
    aria-hidden="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="menuItemModalTitle">Add Menu Item</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <div class="container-fluid">
            <form id="menuItemModalForm" action="admin.php" method="POST">
              <input type="hidden" name="action" id="menuItemAction">
              <input type="hidden" name="menu_id" id="menuItemId">

              <div class="form-group">
                <label for="inputMenuTitle">Title</label>
                <input
                  type="text"
                  class="form-control"
                  name="menu_title"
                  id="inputMenuTitle"
                  placeholder="Menu Title"
                  required
                />
              </div>

              <div class="form-group">
                <label for="inputMenuUrl">URL</label>
                <input
                  type="text"
                  class="form-control"
                  name="menu_url"
                  id="inputMenuUrl"
                  placeholder="e.g., index.php or #about"
                  required
                />
              </div>

              <div class="form-group">
                <label for="inputMenuParent">Parent Menu</label>
                <select
                  class="form-control"
                  name="menu_parent"
                  id="inputMenuParent"
                >
                  <option value="">None (Top Level)</option>
                  <?php
                  // Populate parent options from existing menu items
                  // This needs to be fetched from the DB again or passed from above
                  foreach ($menu_items_raw as $item) {
                      if ($item['parent_id'] === NULL) { // Only top-level items can be parents initially
                          echo '<option value="' . htmlspecialchars($item['id']) . '">' . htmlspecialchars($item['title']) . '</option>';
                      }
                  }
                  ?>
                </select>
              </div>

              <div class="form-group">
                <label for="inputMenuOrder">Order</label>
                <input
                  type="number"
                  class="form-control"
                  name="menu_order"
                  id="inputMenuOrder"
                  value="0"
                  min="0"
                />
              </div>

              <div class="form-group">
                <label for="inputMenuStatus">Status</label>
                <select
                  class="form-control"
                  name="menu_status"
                  id="inputMenuStatus"
                  required
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  data-bs-dismiss="modal"
                >
                  Close
                </button>
                <button type="submit" class="btn btn-primary">Save Menu Item</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- News Modal -->
  <div
    class="modal fade"
    id="newsModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="newsModalTitle"
    aria-hidden="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="newsModalTitle">Add News Article</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <div class="container-fluid">
            <form id="newsModalForm" action="admin.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" id="newsAction">
              <input type="hidden" name="id" id="newsId">
              <input type="hidden" name="current_news_image_url" id="currentNewsImageUrl"> <!-- Hidden field for current image URL -->

              <div class="form-group">
                <label for="inputNewsTitle">Title</label>
                <input
                  type="text"
                  class="form-control"
                  name="title"
                  id="inputNewsTitle"
                  placeholder="News Title"
                  required
                />
              </div>

              <div class="form-group">
                <label for="inputNewsImage">News Image</label>
                <input
                  type="file"
                  class="form-control"
                  name="news_image"
                  id="inputNewsImage"
                  accept="image/*"
                />
                <div id="newsImagePreviewContainer" style="margin-top: 10px;">
                    <img id="newsImagePreview" src="" alt="Image Preview" style="max-width: 150px; height: auto; display: none;">
                </div>
              </div>

              <div class="form-group">
                <label for="inputNewsAvailableAt">Available from</label>
                <input
                  type="datetime-local"
                  class="form-control"
                  name="availableAt"
                  id="inputNewsAvailableAt"
                  required
                />
              </div>

              <div class="form-group">
                <label for="inputNewsExpireAt">Expires At</label>
                <input
                  type="datetime-local"
                  class="form-control"
                  name="expireAt"
                  id="inputNewsExpireAt"
                />
              </div>

              <div class="form-group">
                <label for="inputNewsCategory">Category</label>
                <select
                  class="form-control"
                  name="category"
                  id="inputNewsCategory"
                  required
                >
                  <option value="">Select one</option>
                  <option value="Academics">Academics</option>
                  <option value="Research">Research</option>
                  <option value="Campus Life">Campus Life</option>
                  <option value="Sports">Sports</option>
                  <option value="General">General</option>
                </select>
              </div>

              <div class="form-group">
                <label for="inputNewsStatus">Status</label>
                <select
                  class="form-control"
                  name="status"
                  id="inputNewsStatus"
                  required
                >
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                  <option value="archived">Archived</option>
                </select>
              </div>
              

              <div class="form-group">
                <label for="inputNewsDescription">Description:</label>
                <textarea
                  rows="5"
                  class="form-control"
                  name="description"
                  id="inputNewsDescription"
                  placeholder="News Description"
                ></textarea>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  data-bs-dismiss="modal"
                >
                  Close
                </button>
                <button type="submit" class="btn btn-primary">Save News</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Event Modal -->
  <div
    class="modal fade"
    id="eventModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="eventModalTitle"
    aria-hidden="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="eventModalTitle">Add Event</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <div class="container-fluid">
            <form id="eventModalForm" action="admin.php" method="POST">
              <input type="hidden" name="action" id="eventAction">
              <input type="hidden" name="event_id" id="eventId">
              <div class="form-group">
                <label for="inputEventTitle">Title</label>
                <input
                  type="text"
                  class="form-control"
                  name="event_title"
                  id="inputEventTitle"
                  placeholder="Event Title"
                  required
                />
              </div>

              <div class="form-group">
                <label for="inputEventDate">Date</label>
                <input
                  type="datetime-local"
                  class="form-control"
                  name="event_date"
                  id="inputEventDate"
                  required
                />
              </div>

              <div class="form-group">
                <label for="inputEventLocation">Location</label>
                <input
                  type="text"
                  class="form-control"
                  name="event_location"
                  id="inputEventLocation"
                  placeholder="Event Location"
                  required
                />
              </div>

              <div class="form-group">
                <label for="inputEventStatus">Status</label>
                <select
                  class="form-control"
                  name="event_status"
                  id="inputEventStatus"
                  required
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>
              

              <div class="form-group">
                <label for="inputEventDescription">Description:</label>
                <textarea
                  rows="5"
                  class="form-control"
                  name="event_description"
                  id="inputEventDescription"
                  placeholder="Event Description"
                ></textarea>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  data-bs-dismiss="modal"
                >
                  Close
                </button>
                <button type="submit" class="btn btn-primary">Save Event</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Slider Modal -->
  <div
    class="modal fade"
    id="sliderModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="sliderModalTitle"
    aria-hidden="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="sliderModalTitle">Add Slider</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <div class="container-fluid">
            <form id="sliderModalForm" action="admin.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" id="sliderAction">
              <input type="hidden" name="slider_id" id="sliderId">
              <input type="hidden" name="current_image_url" id="currentSliderImageUrl">

              <div class="form-group">
                <label for="inputSliderTitle">Title</label>
                <input
                  type="text"
                  class="form-control"
                  name="slider_title"
                  id="inputSliderTitle"
                  placeholder="Slider Title"
                  required
                />
              </div>

              <div class="form-group">
                <label for="inputSliderImage">Image</label>
                <input
                  type="file"
                  class="form-control"
                  name="slider_image"
                  id="inputSliderImage"
                  accept="image/*"
                />
                <div id="sliderImagePreviewContainer" style="margin-top: 10px;">
                    <img id="sliderImagePreview" src="" alt="Image Preview" style="max-width: 150px; height: auto; display: none;">
                </div>
              </div>

              <div class="form-group">
                <label for="inputSliderLinkUrl">Link URL</label>
                <input
                  type="url"
                  class="form-control"
                  name="slider_link_url"
                  id="inputSliderLinkUrl"
                  placeholder="https://example.com"
                />
              </div>

              <div class="form-group">
                <label for="inputSliderStatus">Status</label>
                <select
                  class="form-control"
                  name="slider_status"
                  id="inputSliderStatus"
                  required
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  data-bs-dismiss="modal"
                >
                  Close
                </button>
                <button type="submit" class="btn btn-primary">Save Slider</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality (from original HTML)
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const adminContainer = document.querySelector('.admin-container');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                adminContainer.classList.toggle('sidebar-hidden');
            });
        }

        // Active section highlighting in sidebar (from original HTML)
        const sidebarLinks = document.querySelectorAll('.sidebar-nav ul li a');
        const contentSections = document.querySelectorAll('.content-section');

        sidebarLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // e.preventDefault(); // Prevent default hash navigation for now to allow PHP refresh
                
                sidebarLinks.forEach(l => l.parentElement.classList.remove('active'));
                this.parentElement.classList.add('active');

                const targetId = this.getAttribute('href').substring(1);
                contentSections.forEach(section => {
                    if (section.id === targetId) {
                        section.classList.add('active');
                    } else {
                        section.classList.remove('active');
                    }
                });
            });
        });

        // Set initial active section based on URL hash
        const initialHash = window.location.hash || '#dashboard';
        const initialLink = document.querySelector(`.sidebar-nav ul li a[href="${initialHash}"]`);
        if (initialLink) {
            sidebarLinks.forEach(l => l.parentElement.classList.remove('active'));
            initialLink.parentElement.classList.add('active');
            contentSections.forEach(section => {
                if ('#' + section.id === initialHash) {
                    section.classList.add('active');
                } else {
                    section.classList.remove('active');
                }
            });
        }

        // --- Menu Item Modal Logic ---
        const menuItemModal = document.getElementById('menuItemModal');
        menuItemModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const action = button.getAttribute('data-action');
            const modalTitle = menuItemModal.querySelector('#menuItemModalTitle');
            const menuItemActionInput = menuItemModal.querySelector('#menuItemAction');
            const menuItemIdInput = menuItemModal.querySelector('#menuItemId');
            const inputMenuTitle = menuItemModal.querySelector('#inputMenuTitle');
            const inputMenuUrl = menuItemModal.querySelector('#inputMenuUrl');
            const inputMenuParent = menuItemModal.querySelector('#inputMenuParent');
            const inputMenuOrder = menuItemModal.querySelector('#inputMenuOrder');
            const inputMenuStatus = menuItemModal.querySelector('#inputMenuStatus');

            // Reset form fields
            menuItemIdInput.value = '';
            inputMenuTitle.value = '';
            inputMenuUrl.value = '';
            inputMenuParent.value = ''; // Reset parent selection
            inputMenuOrder.value = 0;
            inputMenuStatus.value = 'active';

            if (action === 'add') {
                modalTitle.textContent = 'Add Menu Item';
                menuItemActionInput.value = 'add_menu_item';
            } else if (action === 'edit') {
                modalTitle.textContent = 'Edit Menu Item';
                menuItemActionInput.value = 'edit_menu_item';
                menuItemIdInput.value = button.getAttribute('data-id');
                inputMenuTitle.value = button.getAttribute('data-title');
                inputMenuUrl.value = button.getAttribute('data-url');
                inputMenuParent.value = button.getAttribute('data-parent-id');
                inputMenuOrder.value = button.getAttribute('data-order');
                inputMenuStatus.value = button.getAttribute('data-status');
            }
        });


        // --- News Modal Logic ---
        const newsModal = document.getElementById('newsModal');
        newsModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const action = button.getAttribute('data-action');
            const modalTitle = newsModal.querySelector('#newsModalTitle');
            const newsActionInput = newsModal.querySelector('#newsAction');
            const newsIdInput = newsModal.querySelector('#newsId');
            const inputNewsTitle = newsModal.querySelector('#inputNewsTitle');
            const inputNewsDescription = newsModal.querySelector('#inputNewsDescription');
            const inputNewsCategory = newsModal.querySelector('#inputNewsCategory');
            const inputNewsAvailableAt = newsModal.querySelector('#inputNewsAvailableAt');
            const inputNewsExpireAt = newsModal.querySelector('#inputNewsExpireAt');
            const inputNewsStatus = newsModal.querySelector('#inputNewsStatus');
            const inputNewsImage = newsModal.querySelector('#inputNewsImage');
            const newsImagePreview = newsModal.querySelector('#newsImagePreview');
            const currentNewsImageUrl = newsModal.querySelector('#currentNewsImageUrl');

            // Reset file input and preview
            inputNewsImage.value = '';
            newsImagePreview.style.display = 'none';
            newsImagePreview.src = '';
            currentNewsImageUrl.value = ''; // Clear hidden field

            if (action === 'add') {
                modalTitle.textContent = 'Add News Article';
                newsActionInput.value = 'add_news';
                newsIdInput.value = '';
                inputNewsTitle.value = '';
                inputNewsDescription.value = '';
                inputNewsCategory.value = '';
                inputNewsAvailableAt.value = '';
                inputNewsExpireAt.value = '';
                inputNewsStatus.value = 'draft';
            } else if (action === 'edit') {
                modalTitle.textContent = 'Edit News Article';
                newsActionInput.value = 'edit_news';
                newsIdInput.value = button.getAttribute('data-id');
                inputNewsTitle.value = button.getAttribute('data-title');
                inputNewsDescription.value = button.getAttribute('data-description');
                inputNewsCategory.value = button.getAttribute('data-category');
                inputNewsAvailableAt.value = button.getAttribute('data-availableat');
                inputNewsExpireAt.value = button.getAttribute('data-expireat');
                inputNewsStatus.value = button.getAttribute('data-status');
                
                const imageUrl = button.getAttribute('data-image');
                if (imageUrl) {
                    newsImagePreview.src = imageUrl;
                    newsImagePreview.style.display = 'block';
                    currentNewsImageUrl.value = imageUrl; // Store current image URL
                }
            }
        });

        // Image file input change listener for news image preview
        const inputNewsImage = document.getElementById('inputNewsImage');
        if (inputNewsImage) {
            inputNewsImage.addEventListener('change', function() {
                const newsImagePreview = document.getElementById('newsImagePreview');
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        newsImagePreview.src = e.target.result;
                        newsImagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    newsImagePreview.src = '';
                    newsImagePreview.style.display = 'none';
                }
            });
        }


        // --- Event Modal Logic ---
        const eventModal = document.getElementById('eventModal');
        eventModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const action = button.getAttribute('data-action');
            const modalTitle = eventModal.querySelector('#eventModalTitle');
            const eventActionInput = eventModal.querySelector('#eventAction');
            const eventIdInput = eventModal.querySelector('#eventId');
            const inputEventTitle = eventModal.querySelector('#inputEventTitle');
            const inputEventDate = eventModal.querySelector('#inputEventDate');
            const inputEventLocation = eventModal.querySelector('#inputEventLocation');
            const inputEventDescription = eventModal.querySelector('#inputEventDescription');
            const inputEventStatus = eventModal.querySelector('#inputEventStatus');

            if (action === 'add') {
                modalTitle.textContent = 'Add Event';
                eventActionInput.value = 'add_event';
                eventIdInput.value = '';
                inputEventTitle.value = '';
                inputEventDate.value = '';
                inputEventLocation.value = '';
                inputEventDescription.value = '';
                inputEventStatus.value = 'active';
            } else if (action === 'edit') {
                modalTitle.textContent = 'Edit Event';
                eventActionInput.value = 'edit_event';
                eventIdInput.value = button.getAttribute('data-id');
                inputEventTitle.value = button.getAttribute('data-title');
                inputEventDate.value = button.getAttribute('data-date');
                inputEventLocation.value = button.getAttribute('data-location');
                inputEventDescription.value = button.getAttribute('data-description');
                inputEventStatus.value = button.getAttribute('data-status');
            }
        });

        // --- Slider Modal Logic ---
        const sliderModal = document.getElementById('sliderModal');
        sliderModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const action = button.getAttribute('data-action');
            const modalTitle = sliderModal.querySelector('#sliderModalTitle');
            const sliderActionInput = sliderModal.querySelector('#sliderAction');
            const sliderIdInput = sliderModal.querySelector('#sliderId');
            const inputSliderTitle = sliderModal.querySelector('#inputSliderTitle');
            const inputSliderLinkUrl = sliderModal.querySelector('#inputSliderLinkUrl');
            const inputSliderImage = sliderModal.querySelector('#inputSliderImage');
            const sliderImagePreview = sliderModal.querySelector('#sliderImagePreview');
            const currentSliderImageUrl = sliderModal.querySelector('#currentSliderImageUrl');
            const inputSliderStatus = sliderModal.querySelector('#inputSliderStatus');

            // Reset file input and preview
            inputSliderImage.value = '';
            sliderImagePreview.style.display = 'none';
            sliderImagePreview.src = '';
            currentSliderImageUrl.value = ''; // Clear hidden field

            if (action === 'add') {
                modalTitle.textContent = 'Add Slider';
                sliderActionInput.value = 'add_slider';
                sliderIdInput.value = '';
                inputSliderTitle.value = '';
                inputSliderLinkUrl.value = '';
                inputSliderStatus.value = 'active';
            } else if (action === 'edit') {
                modalTitle.textContent = 'Edit Slider';
                sliderActionInput.value = 'edit_slider';
                sliderIdInput.value = button.getAttribute('data-id');
                inputSliderTitle.value = button.getAttribute('data-title');
                inputSliderLinkUrl.value = button.getAttribute('data-link');
                inputSliderStatus.value = button.getAttribute('data-status');
                
                const imageUrl = button.getAttribute('data-image');
                if (imageUrl) {
                    sliderImagePreview.src = imageUrl;
                    sliderImagePreview.style.display = 'block';
                    currentSliderImageUrl.value = imageUrl; // Store current image URL
                }
            }
        });

        // Image file input change listener for slider image preview
        const inputSliderImage = document.getElementById('inputSliderImage');
        if (inputSliderImage) {
            inputSliderImage.addEventListener('change', function() {
                const sliderImagePreview = document.getElementById('sliderImagePreview');
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        sliderImagePreview.src = e.target.result;
                        sliderImagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    sliderImagePreview.src = '';
                    sliderImagePreview.style.display = 'none';
                }
            });
        }

        // Sortable.js initialization (from original HTML, needs backend saving)
        // Note: To make drag-and-drop persistent, you'd need to send an AJAX request
        // to a PHP endpoint on the 'onEnd' event, which would update an 'order' column in your database.
        // This is a more advanced feature not implemented in this direct PHP submission example.
        const newsItems = document.getElementById('news-items');
        if (newsItems) {
            new Sortable(newsItems, {
                animation: 150,
                ghostClass: 'blue-background-class',
                // onEnd: function (evt) {
                //     const order = Array.from(evt.from.children).map(item => item.dataset.id);
                //     console.log('News order changed:', order);
                //     // AJAX call to update order in DB
                // }
            });
        }
        const eventItems = document.getElementById('event-items');
        if (eventItems) {
            new Sortable(eventItems, {
                animation: 150,
                ghostClass: 'blue-background-class',
                // onEnd: function (evt) {
                //     const order = Array.from(evt.from.children).map(item => item.dataset.id);
                //     console.log('Event order changed:', order);
                //     // AJAX call to update order in DB
                // }
            });
        }
        const sliderItems = document.getElementById('slider-items');
        if (sliderItems) {
            new Sortable(sliderItems, {
                animation: 150,
                ghostClass: 'blue-background-class',
                // onEnd: function (evt) {
                //     const order = Array.from(evt.from.children).map(item => item.dataset.id);
                //     console.log('Slider order changed:', order);
                //     // AJAX call to update order in DB
                // }
            });
        }
        // Menu item sorting (within its parent)
        const menuTree = document.getElementById('menu-tree');
        if (menuTree) {
            new Sortable(menuTree, {
                animation: 150,
                ghostClass: 'blue-background-class',
                group: 'nested', // For nested lists
                fallbackOnBody: true,
                swapThreshold: 0.65,
                // onEnd: function (evt) {
                //     console.log('Menu order changed:', evt.item);
                //     // AJAX call to update menu structure in DB
                // }
            });
        }
    });
  </script>
</body>
</html>