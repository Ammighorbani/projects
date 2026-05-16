<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Database connection settings
$db_host = "localhost";  // Fixed: removed the wrong [localhost](http://localhost)
$db_username = "jobtogo";
$db_password = "Ammighorbani12";
$db_database = "ammighorbani";

// Create connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle actions: Save new, Update existing, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete note
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM note WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = "Note deleted successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Update existing note
    if (isset($_POST['action']) && $_POST['action'] === 'update' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $content = trim($_POST['content'] ?? '');
        if ($content !== '') {
            $stmt = $conn->prepare("UPDATE note SET content = ? WHERE id = ?");
            $stmt->bind_param("si", $content, $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['message'] = "Note updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    // Save new note (default action)
    if (!isset($_POST['action'])) {
        $content = trim($_POST['content'] ?? '');
        if ($content !== '') {
            $stmt = $conn->prepare("INSERT INTO note (content) VALUES (?)");
            $stmt->bind_param("s", $content);
            $stmt->execute();
            $stmt->close();
            $_SESSION['message'] = "Note saved successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Handle edit request: load note for editing
$edit_note = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT id, content FROM note WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_note = $result->fetch_assoc();
    $stmt->close();
}

// Get success message
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Fetch all notes
$sql = "SELECT id, content FROM note ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Simple Notes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f4f4; }
        textarea { width: 100%; height: 150px; padding: 12px; font-size: 16px; box-sizing: border-box; }
        input[type="submit"] { padding: 10px 20px; font-size: 16px; margin-right: 10px; cursor: pointer; }
        .btn-edit { background: #28a745; color: white; border: none; }
        .btn-delete { background: #dc3545; color: white; border: none; }
        .btn-cancel { background: #6c757d; color: white; border: none; }
        .message { background: #d4edda; color: #155724; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .note { 
            background: white; 
            padding: 20px; 
            margin: 20px 0; 
            border-left: 5px solid #007cba; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            position: relative;
        }
        .actions { margin-top: 15px; text-align: right; }
        h1, h2 { color: #333; }
    </style>
</head>
<body>

<h1>My Notes</h1>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Form: New note or Edit existing -->
<form method="post">
    <textarea name="content" placeholder="<?= $edit_note ? 'Edit your note...' : 'Write your note here...' ?>" required><?= $edit_note ? htmlspecialchars($edit_note['content']) : '' ?></textarea>
    <br><br>

    <?php if ($edit_note): ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $edit_note['id'] ?>">
        <input type="submit" value="Update Note" class="btn-edit">
        <a href="<?= $_SERVER['PHP_SELF'] ?>"><input type="button" value="Cancel" class="btn-cancel"></a>
    <?php else: ?>
        <input type="submit" value="Save Note">
    <?php endif; ?>
</form>

<hr>

<h2>Saved Notes (newest first)</h2>

<?php if ($result->num_rows == 0): ?>
    <p>No notes yet. Write one above!</p>
<?php else: ?>
    <?php while($note = $result->fetch_assoc()): ?>
        <div class="note">
            <?= nl2br(htmlspecialchars($note['content'])) ?>
            
            <div class="actions">
                <a href="?edit=<?= $note['id'] ?>">
                    <button type="button" class="btn-edit">Edit</button>
                </a>
                
                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this note?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $note['id'] ?>">
                    <button type="submit" class="btn-delete">Delete</button>
                </form>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<?php $conn->close(); ?>

</body>
</html>