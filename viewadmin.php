<?php
include "config.php";

// --- Logika Hapus Pengguna ---
if (isset($_POST['delete_user_id'])) {
    $delete_id = $_POST['delete_user_id'];
    // Tambahkan log action sebelum delete (jika diperlukan)
    // if (isset($_SESSION['user_id'])) { log_action($conn, $_SESSION['user_id'], 'delete', 'users', $delete_id); }
    $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete->bind_param("i", $delete_id);
    if ($stmt_delete->execute()) {
        // Redirect kembali ke halaman ini setelah berhasil hapus
        header("Location: viewadmin.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error; // Tampilkan error jika gagal
    }
    $stmt_delete->close();
}

// --- Logika Edit Pengguna ---
$edit_user_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt_select_edit = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt_select_edit->bind_param("i", $edit_id);
    $stmt_select_edit->execute();
    $result_select_edit = $stmt_select_edit->get_result();
    if ($result_select_edit->num_rows > 0) {
        $edit_user_data = $result_select_edit->fetch_assoc();
    } else {
        // Jika ID tidak valid, redirect kembali tanpa parameter edit
        header("Location: viewadmin.php");
        exit();
    }
    $stmt_select_edit->close();
}

// Tangani submission form edit
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Ambil data lama untuk logging jika diperlukan
    // $old_data = null;
    // $stmt_old = $conn->prepare("SELECT username, email, role FROM users WHERE id = ?");
    // $stmt_old->bind_param("i", $user_id);
    // $stmt_old->execute();
    // $result_old = $stmt_old->get_result();
    // if ($result_old->num_rows > 0) { $old_data = $result_old->fetch_assoc(); }
    // $stmt_old->close();

    $stmt_update = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
    $stmt_update->bind_param("sssi", $username, $email, $role, $user_id);

    if ($stmt_update->execute()) {
        // Tambahkan log action setelah update (jika diperlukan)
        // $new_data = ['username' => $username, 'email' => $email, 'role' => $role];
        // if (isset($_SESSION['user_id'])) { log_action($conn, $_SESSION['user_id'], 'update', 'users', $user_id, $old_data, $new_data); }

        // Redirect kembali setelah berhasil update
        header("Location: viewadmin.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error; // Tampilkan error jika gagal
    }
    $stmt_update->close();
}

// Tangani parameter pencarian dan pagination
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query untuk mengambil data user
$sql = "SELECT id, username, email, role FROM users";
$count_sql = "SELECT COUNT(id) AS total FROM users";

// Tambahkan kondisi pencarian jika ada input search
if (!empty($search)) {
    $sql .= " WHERE username LIKE ? OR email LIKE ?";
    $count_sql .= " WHERE username LIKE ? OR email LIKE ?";
}

$sql .= " LIMIT ?, ?"; // Tambahkan LIMIT dan OFFSET

// Persiapkan dan jalankan query hitung total data
$count_stmt = $conn->prepare($count_sql);

// Bind parameter untuk query hitung jika ada pencarian
if (!empty($search)) {
    $search_param = "%" . $search . "%";
    $count_stmt->bind_param("ss", $search_param, $search_param);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);
$count_stmt->close();

// Persiapkan dan jalankan query data dengan LIMIT dan OFFSET
$stmt = $conn->prepare($sql);

// Bind parameter untuk query data (pencarian dan pagination)
if (!empty($search)) {
    $search_param = "%" . $search . "%";
    $stmt->bind_param("ssii", $search_param, $search_param, $start, $limit);
} else {
    $stmt->bind_param("ii", $start, $limit);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengguna</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .aksi a {
            margin-right: 10px;
            text-decoration: none;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .edit {
            background-color: blue;
        }
        .hapus {
            background-color: red;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            display: inline-block;
            padding: 8px 16px;
            text-decoration: none;
            color: #007bff;
            border: 1px solid #ddd;
            margin: 0 4px;
            border-radius: 5px;
        }
        .pagination a.active {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
         .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        .search-form {
            margin-bottom: 20px;
        }
        .search-form input[type="text"] {
            padding: 8px;
            margin-right: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
         .search-form input[type="submit"] {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .search-form input[type="submit"]:hover {
            background-color: #0056b3;
        }
        /* Gaya untuk form edit */
        .edit-form-container {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
        }
        .edit-form-container h3 {
            margin-top: 0;
            margin-bottom: 15px;
        }
        .edit-form-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .edit-form-container input[type="text"],
        .edit-form-container input[type="email"],
        .edit-form-container select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Penting untuk padding dan border */
        }
        .edit-form-container button {
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
         .edit-form-container button[type="reset"] {
            background-color: #dc3545;
        }
        .edit-form-container button:hover {
            opacity: 0.9;
        }

    </style>
</head>
<body>

<h2>Daftar Pengguna</h2>

<!-- Form Pencarian -->
<form method="get" action="" class="search-form">
    <input type="text" name="search" placeholder="Cari pengguna..." value="<?php echo htmlspecialchars($search); ?>">
    <input type="submit" value="Cari">
</form>

<!-- Form Edit Pengguna -->
<?php if ($edit_user_data): ?>
    <div class="edit-form-container">
        <h3>Edit Pengguna: <?php echo htmlspecialchars($edit_user_data['username']); ?></h3>
        <form method="post" action="viewadmin.php">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user_data['id']); ?>">
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($edit_user_data['username']); ?>" required>
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_user_data['email']); ?>" required>
            </div>
            <div>
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="admin" <?php echo ($edit_user_data['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="user" <?php echo ($edit_user_data['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                </select>
            </div>
            <button type="submit" name="update_user">Simpan Perubahan</button>
            <button type="reset">Batal</button> <!-- Tombol batal, bisa ditambahkan JS untuk menyembunyikan form -->
            <a href="viewadmin.php" style="margin-left: 10px; text-decoration: none;">Batal Edit</a> <!-- Link untuk membatalkan edit -->
        </form>
    </div>
<?php endif; ?>

<table>
    <tr>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Aksi</th>
    </tr>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['role']); ?></td>
                <td class="aksi">
                    <a href="?edit_id=<?php echo $row['id']; ?>" class="edit">Edit</a>
                    <!-- Form untuk Hapus -->
                    <form method="post" action="viewadmin.php" style="display:inline;">
                        <input type="hidden" name="delete_user_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna <?php echo htmlspecialchars($row['username']); ?>?')" style="border:none; cursor:pointer;">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="4" style="text-align:center;">Tidak ada data pengguna</td>
        </tr>
    <?php endif; ?>
</table>

<!-- Pagination -->
<div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . htmlspecialchars($search) : ''; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
</div>

</body>
</html>
