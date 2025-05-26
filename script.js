document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".update-status").forEach(button => {
        button.addEventListener("click", function() {
            let id = this.dataset.id;
            let status = this.dataset.status;

            if (!confirm(`Apakah Anda yakin ingin mengubah status menjadi "${status}"?`)) {
                return;
            }

            fetch("update_status.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${id}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        alert("Status berhasil diperbarui!");
                        location.reload(); // Reload halaman setelah update
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => console.error("Error:", error));
        });
    });
});