<?php
include "config.php";
session_start();

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Permintaan tidak valid.'];

// Pastikan user sudah login dan punya izin melakukan undo (misal: admin)
// Sesuaikan dengan logika otorisasi aplikasi Anda
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}
$current_user_id = $_SESSION['user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_id'])) {
    $log_id = intval($_POST['log_id']);

    // Ambil detail log
    $stmt = $conn->prepare("SELECT * FROM logs WHERE id = ?");
    $stmt->bind_param("i", $log_id);
    $stmt->execute();
    $log_entry = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($log_entry) {
        $action = $log_entry['action'];
        $table_name = $log_entry['table_name'];
        $record_id = $log_entry['record_id'];
        $old_data = json_decode($log_entry['old_data'], true); // Data sebelum aksi yang di-undo
        $new_data = json_decode($log_entry['new_data'], true); // Data setelah aksi yang di-undo

        // Mulai transaksi database
        $conn->begin_transaction();
        $undo_success = false;
        $undo_message = "Tidak ada aksi undo yang sesuai atau diperlukan.";

        try {
            switch ($action) {
                case 'add_item':
                case 'add_room':
                case 'add_maintenance_record':
                    // Undo "tambah" berarti "hapus" record yang baru ditambahkan
                    if ($record_id) {
                        $stmt_undo = $conn->prepare("DELETE FROM `$table_name` WHERE id = ?");
                        $stmt_undo->bind_param("i", $record_id);
                        if ($stmt_undo->execute()) {
                            $undo_success = true;
                            $undo_message = ucfirst(str_replace('_', ' ', $action)) . " (ID: $record_id) berhasil dibatalkan (dihapus).";
                        } else {
                            throw new Exception("Gagal menghapus record `$table_name` ID $record_id: " . $conn->error);
                        }
                        $stmt_undo->close();
                    } else {
                         throw new Exception("ID Record tidak tersedia untuk aksi tambah log ID $log_id.");
                    }
                    break;

                case 'edit_item':
                case 'edit_room':
                case 'update_item_status':
                case 'start_item_repair': // Undo start repair -> kembalikan item ke kondisi sebelum 'dalam_perbaikan'
                case 'item_repair_completed': // Undo complete repair -> kembalikan item ke kondisi sebelum 'diperbaiki'/'baik'
                case 'update_item_quantity_after_loan': // Undo update quantity -> kembalikan quantity lama
                    // Undo "edit" atau "update status" berarti mengembalikan data lama
                    if ($record_id && $old_data !== null) {
                        // Bangun query UPDATE dari $old_data
                        $update_fields = [];
                        $bind_params = [];
                        $bind_types = '';

                        foreach ($old_data as $key => $value) {
                            if ($key === 'id') continue; // Hindari mengupdate ID itu sendiri
                            $update_fields[] = "`$key` = ?";
                            $bind_params[] = $value;
                             // Tentukan tipe binding (s=string, i=integer, d=double, b=blob). Pakai 's' untuk JSON
                            if (is_int($value)) $bind_types .= 'i';
                            elseif (is_float($value)) $bind_types .= 'd';
                            elseif ($value === null) $bind_types .= 's'; // NULL bisa dianggap string untuk binding
                            else $bind_types .= 's';
                        }

                        if (!empty($update_fields)) {
                             $sql_undo = "UPDATE `$table_name` SET " . implode(', ', $update_fields) . " WHERE id = ?";
                            $bind_params[] = $record_id; // Tambahkan ID untuk klausa WHERE
                            $bind_types .= 'i'; // Tipe binding untuk ID

                            $stmt_undo = $conn->prepare($sql_undo);
                            $stmt_undo->bind_param($bind_types, ...$bind_params);

                            if ($stmt_undo->execute()) {
                                $undo_success = true;
                                $undo_message = ucfirst(str_replace('_', ' ', $action)) . " (ID: $record_id) berhasil dibatalkan (dikembalikan ke data lama).";
                            } else {
                                throw new Exception("Gagal mengupdate record `$table_name` ID $record_id: " . $conn->error);
                            }
                            $stmt_undo->close();
                        } else {
                            $undo_message = "Tidak ada data lama yang bisa dikembalikan untuk aksi edit ID $record_id log ID $log_id.";
                            $undo_success = true; // Dianggap sukses karena tidak ada perubahan yang perlu dibatalkan
                        }

                    } else {
                        throw new Exception("ID Record atau data lama tidak tersedia untuk aksi edit/update status log ID $log_id.");
                    }
                    break;

                case 'delete_item':
                case 'delete_room':
                case 'bulk_delete_item': // Undo bulk delete item -> add back item(s)
                    // Undo "hapus" berarti menambah kembali data lama
                     if ($old_data !== null) {
                        // $old_data bisa berupa array tunggal (delete item/room) atau array of arrays (bulk delete item)
                        $items_to_reinsert = [];
                        if ($action === 'delete_item') {
                             $items_to_reinsert[] = $old_data;
                        } else if ($action === 'delete_room') {
                            // old_data for delete_room has structure {'room' => [...], 'items' => [...]}
                             if (isset($old_data['room'])) $items_to_reinsert[] = $old_data['room']; // Re-insert the room
                             if (isset($old_data['items']) && is_array($old_data['items'])) {
                                 $items_to_reinsert = array_merge($items_to_reinsert, $old_data['items']); // Re-insert associated items
                             }
                             // Set table_name to 'rooms' for the log, even though items are re-inserted
                             $table_name = 'rooms'; // Log refers to rooms table
                        } else if ($action === 'bulk_delete_item') {
                             // old_data for bulk_delete_item is an array of item data
                             $items_to_reinsert = $old_data; // This might need adjustment based on how bulk delete was logged
                             // Assuming old_data for bulk_delete_item log is a single item record
                             $items_to_reinsert = [$old_data]; // Re-insert the single item
                             // Note: Bulk delete log currently records one log entry per item.
                             // This case handles the undo for one item from a bulk delete.
                              $table_name = 'items'; // Log refers to items table
                        }

                        $reinserted_count = 0;
                        foreach ($items_to_reinsert as $item_data) {
                            if (empty($item_data) || !is_array($item_data)) continue;

                            $insert_fields = [];
                            $insert_values_placeholder = [];
                            $bind_params = [];
                            $bind_types = '';

                            foreach ($item_data as $key => $value) {
                                // Jika primary key (misal 'id') disertakan dan AUTO_INCREMENT, hapus dari list insert
                                // Biarkan database generate ID baru.
                                // Jika tidak AUTO_INCREMENT atau ingin mempertahankan ID lama, perlu cek duplikasi.
                                // Untuk kemudahan dan menghindari duplikasi PK, kita biarkan DB generate ID.
                                if ($key === 'id') continue; // Assume ID is auto-increment

                                $insert_fields[] = "`$key`";
                                $insert_values_placeholder[] = "?";
                                $bind_params[] = $value;
                                // Tentukan tipe binding (s, i, d, b)
                                if (is_int($value)) $bind_types .= 'i';
                                elseif (is_float($value)) $bind_types .= 'd';
                                elseif ($value === null) $bind_types .= 's';
                                else $bind_types .= 's';
                            }

                             // Handle image file re-creation if needed (complex)
                            // For now, only re-insert db record, image file might be lost.

                            if (!empty($insert_fields)) {
                                $sql_undo = "INSERT INTO `$table_name` (" . implode(', ', $insert_fields) . ") VALUES (" . implode(', ', $insert_values_placeholder) . ")";
                                $stmt_undo = $conn->prepare($sql_undo);
                                if ($bind_types) { // Only bind if there are parameters
                                     $stmt_undo->bind_param($bind_types, ...$bind_params);
                                }

                                if ($stmt_undo->execute()) {
                                    $reinserted_count++;
                                    // Note: New ID is $conn->insert_id; might need logging this?
                                } else {
                                     // Jika gagal insert salah satu item/ruangan, throw error
                                    throw new Exception("Gagal menambah kembali record `$table_name` (ID asli: " . ($item_data['id'] ?? '- ') . "): " . $conn->error);
                                }
                                 $stmt_undo->close();
                            }
                        }

                        if ($reinserted_count > 0) {
                            $undo_success = true;
                             $undo_message = ucfirst(str_replace('_', ' ', $action)) . " (ID asli: $record_id) berhasil dibatalkan ($reinserted_count record ditambah kembali).";
                        } else {
                             $undo_message = "Tidak ada data lama yang valid untuk menambah kembali record log ID $log_id.";
                             // Decide if this is an error or just nothing to reinsert
                             // For now, treat as success if old_data was empty/invalid
                             $undo_success = true;
                        }

                    } else {
                         throw new Exception("Data lama (old_data) tidak tersedia untuk aksi hapus log ID $log_id.");
                    }
                    break;

                case 'approve_loan': // Undo Setuju -> Tolak
                     // Aksi asli: Status dipinjam, stok berkurang
                     // Undo: Status jadi ditolak, stok bertambah (dikembalikan)
                     if ($record_id && $new_data !== null && isset($new_data['status']) && $new_data['status'] === 'dipinjam' && isset($new_data['item_id']) && isset($new_data['jumlah'])) {
                         $peminjaman_id = $record_id;
                         $item_id = $new_data['item_id'];
                         $jumlah = $new_data['jumlah'];
                         $target_status = 'ditolak';
                         $tanggal_kembali = date('Y-m-d H:i:s'); // Set tanggal kembali saat ditolak

                         // Update status peminjaman menjadi ditolak
                         $stmt_loan = $conn->prepare("UPDATE peminjaman SET status = ?, tanggal_kembali = ? WHERE id = ?");
                         $stmt_loan->bind_param("ssi", $target_status, $tanggal_kembali, $peminjaman_id);
                         if (!$stmt_loan->execute()) {
                             throw new Exception("Gagal mengupdate status peminjaman ID $peminjaman_id menjadi '$target_status': " . $conn->error);
                         }
                         $stmt_loan->close();

                         // Tambahkan jumlah item kembali ke stok
                         $stmt_item = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
                         $stmt_item->bind_param("ii", $jumlah, $item_id);
                         if (!$stmt_item->execute()) {
                              // Jika update stok gagal setelah update status peminjaman, rollback keduanya
                              throw new Exception("Gagal mengembalikan stok item ID $item_id ($jumlah unit): " . $conn->error);
                         }
                         $stmt_item->close();

                         $undo_success = true;
                         $undo_message = "Peminjaman ID $peminjaman_id berhasil dibatalkan (status menjadi '$target_status', stok item bertambah $jumlah). ";

                     } else {
                          throw new Exception("Data log peminjaman tidak lengkap atau status tidak sesuai untuk aksi 'approve_loan' log ID $log_id.");
                     }
                    break;

                case 'reject_loan': // Undo Tolak -> Diajukan
                    // Aksi asli: Status ditolak, stok bertambah (dikembalikan saat ditolak)
                    // Undo: Status jadi diajukan, stok berkurang (diambil kembali dari stok virtual)
                     if ($record_id && $new_data !== null && isset($new_data['status']) && $new_data['status'] === 'ditolak' && isset($new_data['item_id']) && isset($new_data['jumlah'])) {
                         $peminjaman_id = $record_id;
                         $item_id = $new_data['item_id'];
                         $jumlah = $new_data['jumlah'];
                         $target_status = 'diajukan';

                         // Update status peminjaman menjadi diajukan
                         $stmt_loan = $conn->prepare("UPDATE peminjaman SET status = ?, tanggal_kembali = NULL WHERE id = ?"); // Hapus tanggal_kembali saat kembali ke diajukan
                         $stmt_loan->bind_param("si", $target_status, $peminjaman_id);
                          if (!$stmt_loan->execute()) {
                             throw new Exception("Gagal mengupdate status peminjaman ID $peminjaman_id menjadi '$target_status': " . $conn->error);
                         }
                         $stmt_loan->close();

                         // Kurangi jumlah item dari stok
                         $stmt_item = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
                         $stmt_item->bind_param("ii", $jumlah, $item_id);
                         if (!$stmt_item->execute()) {
                              // Jika update stok gagal, rollback keduanya
                              throw new Exception("Gagal mengurangi stok item ID $item_id ($jumlah unit) saat undo reject: " . $conn->error);
                         }
                         $stmt_item->close();

                         $undo_success = true;
                         $undo_message = "Peminjaman ID $peminjaman_id berhasil dibatalkan (status menjadi '$target_status', stok item berkurang $jumlah). ";

                     } else {
                          throw new Exception("Data log peminjaman tidak lengkap atau status tidak sesuai untuk aksi 'reject_loan' log ID $log_id.");
                     }
                    break;

                case 'confirm_return': // Undo Kembali -> Dipinjam
                    // Aksi asli: Status dikembalikan, stok bertambah
                    // Undo: Status jadi dipinjam, stok berkurang (diambil kembali dari stok)
                     if ($record_id && $new_data !== null && isset($new_data['status']) && $new_data['status'] === 'dikembalikan' && isset($new_data['item_id']) && isset($new_data['jumlah'])) {
                         $peminjaman_id = $record_id;
                         $item_id = $new_data['item_id'];
                         $jumlah = $new_data['jumlah'];
                         $target_status = 'dipinjam';

                         // Update status peminjaman menjadi dipinjam (kembali ke status sebelum dikembalikan)
                         $stmt_loan = $conn->prepare("UPDATE peminjaman SET status = ?, tanggal_kembali = NULL WHERE id = ?"); // Hapus tanggal_kembali saat kembali ke dipinjam
                         $stmt_loan->bind_param("si", $target_status, $peminjaman_id);
                          if (!$stmt_loan->execute()) {
                             throw new Exception("Gagal mengupdate status peminjaman ID $peminjaman_id menjadi '$target_status': " . $conn->error);
                         }
                         $stmt_loan->close();

                         // Kurangi jumlah item dari stok
                         $stmt_item = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
                         $stmt_item->bind_param("ii", $jumlah, $item_id);
                          if (!$stmt_item->execute()) {
                              // Jika update stok gagal, rollback keduanya
                              throw new Exception("Gagal mengurangi stok item ID $item_id ($jumlah unit) saat undo return: " . $conn->error);
                         }
                         $stmt_item->close();

                         $undo_success = true;
                         $undo_message = "Peminjaman ID $peminjaman_id berhasil dibatalkan (status menjadi '$target_status', stok item berkurang $jumlah). ";

                     } else {
                          throw new Exception("Data log peminjaman tidak lengkap atau status tidak sesuai untuk aksi 'confirm_return' log ID $log_id.");
                     }
                    break;

                case 'complete_maintenance_record': // Undo Selesai Perbaikan (Maintenance record)
                     // Aksi asli: Status maintenance selesai
                     // Undo: Status maintenance kembali ke 'dalam_perbaikan'
                      if ($record_id && $old_data !== null && isset($old_data['status'])) {
                          $stmt_undo = $conn->prepare("UPDATE maintenance SET status = ? WHERE id = ?");
                          $stmt_undo->bind_param("si", $old_data['status'], $record_id); // Kembalikan ke status lama
                          if ($stmt_undo->execute()) {
                              $undo_success = true;
                              $undo_message = "Status maintenance record ID $record_id berhasil dibatalkan (dikembalikan ke status '".$old_data['status']."').";
                          } else {
                              throw new Exception("Gagal mengupdate status maintenance record ID $record_id: " . $conn->error);
                          }
                          $stmt_undo->close();
                     } else {
                          throw new Exception("Data log maintenance record tidak lengkap untuk aksi 'complete_maintenance_record' log ID $log_id.");
                     }
                     break;

                default:
                    // Aksi lain yang tidak bisa di-undo atau tidak perlu di-undo oleh skrip ini
                    $undo_message = "Aksi '" . htmlspecialchars($action) . "' tidak dapat dibatalkan secara otomatis oleh skrip ini log ID $log_id.";
                    $undo_success = false; // Tidak bisa di-undo
                    break;
            }

            // Jika aksi undo berhasil, catat aksi undo itu sendiri
            // Penting: Menambahkan kolom 'undone_log_id' di tabel logs akan sangat membantu
            // untuk melacak log mana yang sudah dibatalkan.
            // Untuk penyederhanaan, kita catat aksi undo sebagai aksi baru.
             if ($undo_success) {
                 // Catat aksi undo di log
                 $stmt_log_undo = $conn->prepare("INSERT INTO logs (user_id, action, table_name, record_id, old_data, new_data) VALUES (?, ?, ?, ?, ?, ?)");
                 $undo_log_action = 'undo_' . $action; // Prefix 'undo_' to the original action
                 // Optional: Store reference to the undone log ID in new_data of the undo log
                 $undo_log_new_data = ['undone_log_id' => $log_id];
                 $undo_log_new_data_json = json_encode($undo_log_new_data);

                 // Store old state of the undone log as new_data in the undo log entry
                 $undone_log_old_state = ['action' => $action, 'table' => $table_name, 'record_id' => $record_id, 'old_data' => $old_data, 'new_data' => $new_data];
                 $undone_log_old_state_json = json_encode($undone_log_old_state);

                 // Bind parameters for the undo log entry
                 // Use 's' for JSON fields
                 $stmt_log_undo->bind_param("isssss", $current_user_id, $undo_log_action, $table_name, $record_id, $undone_log_old_state_json, $undo_log_new_data_json);
                 $stmt_log_undo->execute(); // Execute the log for the undo action itself
                 $stmt_log_undo->close();

                 $conn->commit(); // Commit transaksi
                 $response['status'] = 'success';
                 $response['message'] = $undo_message;
             } else {
                 $conn->rollback(); // Rollback transaksi jika aksi undo tidak berhasil
                 $response['message'] = "Gagal membatalkan aksi: " . $undo_message; // Use the specific undo message
             }

        } catch (Exception $e) {
            $conn->rollback(); // Rollback transaksi jika terjadi exception
            $response['message'] = "Terjadi kesalahan saat membatalkan aksi log ID $log_id: " . $e->getMessage();
             // Log the exception itself for debugging?
        }

    } else {
        $response['message'] = "Log ID $log_id tidak ditemukan.";
    }

} else {
    $response['message'] = "Permintaan tidak valid atau log ID tidak diberikan.";
}

echo json_encode($response);
$conn->close();
?> 