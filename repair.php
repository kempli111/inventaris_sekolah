<?php
session_start();
include "config.php";

$message = "";

// Proses form perbaikan via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => ''];

    if (isset($_POST['add_repair'])) {
        $item_id = intval($_POST['item_id']);
        $maintenance_date = $_POST['maintenance_date'];
        $description = $_POST['description'];
        $cost = floatval($_POST['cost']);
        $technician = $_POST['technician'];

        // Update status barang menjadi dalam perbaikan
        $stmt = $conn->prepare("UPDATE items SET repair_status = 'dalam_perbaikan', status = 'tidakbisadipinjam' WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $stmt->close();

        // Tambah data maintenance
        $stmt = $conn->prepare("INSERT INTO maintenance (item_id, maintenance_date, description, cost, technician, status) VALUES (?, ?, ?, ?, ?, 'dalam_perbaikan')");
        $stmt->bind_param("issds", $item_id, $maintenance_date, $description, $cost, $technician);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Data perbaikan berhasil ditambahkan';
        }
        $stmt->close();
        echo json_encode($response);
        exit;
    }

    if (isset($_POST['complete_repair'])) {
        $item_id = intval($_POST['item_id']);
        $maintenance_id = intval($_POST['maintenance_id']);

        // Update status maintenance menjadi selesai
        $stmt = $conn->prepare("UPDATE maintenance SET status = 'selesai' WHERE id = ?");
        $stmt->bind_param("i", $maintenance_id);
        
        if ($stmt->execute()) {
            // Update status item dan repair status
            $stmt_item = $conn->prepare("UPDATE items SET repair_status = 'diperbaiki', item_condition = 'baik', status = 'bisadipinjam' WHERE id = ?");
            $stmt_item->bind_param("i", $item_id);
            
            if ($stmt_item->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Perbaikan selesai';
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Gagal mengupdate status barang';
            }
            $stmt_item->close();
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Gagal menyelesaikan perbaikan';
        }
        $stmt->close();

        echo json_encode($response);
        exit;
    }

    if (isset($_POST['get_data'])) {
        $data = [];
        
        // Data barang rusak
        $sql_broken = "
            SELECT i.*, c.name as category_name, r.name as room_name
            FROM items i 
            JOIN categories c ON i.category_id = c.id 
            JOIN rooms r ON i.room_id = r.id 
            WHERE i.item_condition IN ('rusak ringan', 'rusak berat') 
            AND (i.repair_status IS NULL OR i.repair_status NOT IN ('diperbaiki', 'dalam_perbaikan'))
            AND i.id NOT IN (SELECT item_id FROM maintenance WHERE status = 'dalam_perbaikan')";
        $result = $conn->query($sql_broken);
        $data['broken'] = $result->fetch_all(MYSQLI_ASSOC);

        // Data sedang diperbaiki
        $sql_repairing = "
            SELECT i.*, c.name as category_name, r.name as room_name,
                   m.id as maintenance_id, m.maintenance_date, m.description,
                   m.cost, m.technician, m.status as maintenance_status
            FROM items i 
            JOIN categories c ON i.category_id = c.id 
            JOIN rooms r ON i.room_id = r.id 
            JOIN maintenance m ON i.id = m.item_id
            WHERE m.status = 'dalam_perbaikan'";
        $result = $conn->query($sql_repairing);
        $data['repairing'] = $result->fetch_all(MYSQLI_ASSOC);

        // Data history perbaikan
        $sql_history = "
            SELECT i.name, i.item_condition, c.name as category_name, r.name as room_name,
                   m.maintenance_date, m.description, m.cost, m.technician, m.status
            FROM maintenance m
            JOIN items i ON m.item_id = i.id
            JOIN categories c ON i.category_id = c.id
            JOIN rooms r ON i.room_id = r.id
            WHERE m.status = 'selesai'
            ORDER BY m.maintenance_date DESC";
        $result = $conn->query($sql_history);
        $data['history'] = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode($data);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaikan Barang</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #469ced;
            --success-color: #4CAF50;
            --info-color: #2196F3;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            margin: 20px auto;
            max-width: 1200px;
            padding: 20px;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(70, 156, 237, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-container {
            margin-bottom: 30px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background-color: #fff;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid rgba(70, 156, 237, 0.2);
        }
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        tr:hover {
            background-color: rgba(70, 156, 237, 0.05);
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
            transition: all 0.3s ease;
        }
        .btn-repair {
            background-color: var(--primary-color);
        }
        .btn-complete {
            background-color: var(--success-color);
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            animation: slideIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(-100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }
        .close:hover {
            color: var(--primary-color);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        .nav-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .nav-btn:hover {
            background-color: #3b8cdb;
            transform: translateY(-1px);
        }
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: none;
        }
        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        .message.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        .tab-container {
            margin-bottom: 20px;
        }
        .tab-button {
            padding: 10px 20px;
            background-color: #fff;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            transition: all 0.3s;
        }
        .tab-button.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        /* Style input search pada tiap menu */
        .search-box {
            margin-bottom: 10px;
        }
        .search-box input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="item.php" class="nav-btn">← Kembali ke Daftar Barang</a>
        <div class="card">
            <h1 style="color: var(--primary-color); text-align: center;">Perbaikan Barang</h1>
            <div id="message" class="message"></div>

            <div class="tab-container">
                <button class="tab-button active" data-tab="broken">Barang Rusak</button>
                <button class="tab-button" data-tab="repairing">Sedang Diperbaiki</button>
                <button class="tab-button" data-tab="history">Riwayat Perbaikan</button>
            </div>

            <!-- Tab Barang Rusak -->
            <div id="broken" class="tab-content active">
                <!-- Search box untuk filter Barang Rusak -->
                <div class="search-box">
                    <input type="text" id="search_broken" placeholder="Cari Barang Rusak...">
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Ruangan</th>
                                <th>Kondisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="broken-items">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Sedang Diperbaiki -->
            <div id="repairing" class="tab-content">
                <!-- Search box untuk filter Sedang Diperbaiki -->
                <div class="search-box">
                    <input type="text" id="search_repairing" placeholder="Cari Sedang Diperbaiki...">
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Ruangan</th>
                                <th>Tanggal Perbaikan</th>
                                <th>Teknisi</th>
                                <th>Biaya</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="repairing-items">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Riwayat Perbaikan -->
            <div id="history" class="tab-content">
                <!-- Search box untuk filter Riwayat Perbaikan -->
                <div class="search-box">
                    <input type="text" id="search_history" placeholder="Cari Riwayat Perbaikan...">
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Ruangan</th>
                                <th>Tanggal Perbaikan</th>
                                <th>Deskripsi</th>
                                <th>Teknisi</th>
                                <th>Biaya</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="history-items">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form Perbaikan -->
    <div id="repairModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Form Perbaikan Barang</h2>
            <form id="repairForm">
                <input type="hidden" id="item_id" name="item_id">
                <div class="form-group">
                    <label>Nama Barang:</label>
                    <input type="text" id="item_name" readonly>
                </div>
                <div class="form-group">
                    <label>Tanggal Perbaikan:</label>
                    <input type="date" name="maintenance_date" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi Perbaikan:</label>
                    <textarea name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Biaya:</label>
                    <input type="number" name="cost" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Teknisi:</label>
                    <input type="text" name="technician" required>
                </div>
                <button type="submit" name="add_repair" class="btn btn-repair">Simpan</button>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Load data dari server
            function loadData() {
                $.post('repair.php', { get_data: true }, function(data) {
                    // Barang rusak
                    let brokenHtml = '';
                    data.broken.forEach(function(item) {
                        brokenHtml += `
                            <tr>
                                <td>${item.name}</td>
                                <td>${item.category_name}</td>
                                <td>${item.room_name}</td>
                                <td>${item.item_condition}</td>
                                <td>
                                    <button class="btn btn-repair" onclick="showRepairForm(${item.id}, '${item.name}')">
                                        Perbaiki
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    $('#broken-items').html(brokenHtml);

                    // Sedang diperbaiki
                    let repairingHtml = '';
                    data.repairing.forEach(function(item) {
                        repairingHtml += `
                            <tr>
                                <td>${item.name}</td>
                                <td>${item.category_name}</td>
                                <td>${item.room_name}</td>
                                <td>${item.maintenance_date}</td>
                                <td>${item.technician}</td>
                                <td>${new Intl.NumberFormat('id-ID').format(item.cost)}</td>
                                <td>
                                    <button class="btn btn-complete" onclick="completeRepair(${item.id}, ${item.maintenance_id})">
                                        Selesai
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    $('#repairing-items').html(repairingHtml);

                    // History
                    let historyHtml = '';
                    data.history.forEach(function(item) {
                        historyHtml += `
                            <tr>
                                <td>${item.name}</td>
                                <td>${item.category_name}</td>
                                <td>${item.room_name}</td>
                                <td>${item.maintenance_date}</td>
                                <td>${item.description}</td>
                                <td>${item.technician}</td>
                                <td>${new Intl.NumberFormat('id-ID').format(item.cost)}</td>
                                <td><span class="status-complete">Selesai</span></td>
                            </tr>
                        `;
                    });
                    $('#history-items').html(historyHtml);
                });
            }

            // Load awal data
            loadData();

            // Event tab switching
            $('.tab-button').click(function() {
                $('.tab-button').removeClass('active');
                $(this).addClass('active');
                
                const tabId = $(this).data('tab');
                $('.tab-content').removeClass('active');
                $(`#${tabId}`).addClass('active');
            });

            // Form submission untuk perbaikan
            $('#repairForm').submit(function(e) {
                e.preventDefault();
                $.post('repair.php', $(this).serialize() + '&add_repair=1', function(response) {
                    if (response.status === 'success') {
                        showMessage(response.message, 'success');
                        modal.style.display = "none";
                        $('#repairForm')[0].reset();
                        loadData();
                    } else {
                        showMessage(response.message, 'error');
                    }
                });
            });

            // Pencarian client-side untuk masing-masing menu
            $('#search_broken').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $("#broken-items tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            $('#search_repairing').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $("#repairing-items tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            $('#search_history').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $("#history-items tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });

        // Modal handling
        var modal = document.getElementById("repairModal");
        var span = document.getElementsByClassName("close")[0];

        function showRepairForm(itemId, itemName) {
            document.getElementById("item_id").value = itemId;
            document.getElementById("item_name").value = itemName;
            modal.style.display = "block";
        }

        function completeRepair(itemId, maintenanceId) {
            $.post('repair.php', {
                complete_repair: true,
                item_id: itemId,
                maintenance_id: maintenanceId
            }, function(response) {
                if (response.status === 'success') {
                    showMessage(response.message, 'success');
                    loadData();
                } else {
                    showMessage(response.message, 'error');
                }
            });
        }

        function showMessage(message, type) {
            const messageDiv = $('#message');
            messageDiv.removeClass('success error').addClass(type).text(message).fadeIn();
            setTimeout(() => messageDiv.fadeOut(), 3000);
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
