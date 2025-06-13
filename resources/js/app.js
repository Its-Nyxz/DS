// import "./bootstrap";
import "flowbite";
// import Alpine from "alpinejs";

// window.Alpine = Alpine;

// Alpine.start();
import { DataTable } from "simple-datatables";
import Swal from "sweetalert2";
// import "select2";
window.Swal = Swal;
window.DataTable = DataTable;
import ApexCharts from "apexcharts";
window.ApexCharts = ApexCharts;

window.confirmAlert = function (message, confirmButtonText, callback) {
    const theme = getThemeMode();

    Swal.fire({
        title: "Apakah Anda yakin?",
        text: message || "Data akan dihapus secara permanen!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: confirmButtonText || "Ya, hapus!",
        cancelButtonText: "Batal",
        theme: theme === "dark" ? "dark" : "light", // bg-gray-800 vs white
    }).then((result) => {
        if (result.isConfirmed) {
            // Callback function to execute after confirmation
            if (typeof callback === "function") {
                callback();
            }
        }
    });
};
window.confirmRemove = function (message, callback) {
    const theme = getThemeMode();

    Swal.fire({
        title: "Apakah Anda yakin?",
        text: message || "Data akan dihapus secara permanen!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Ya",
        cancelButtonText: "Tidak",
        theme: theme === "dark" ? "dark" : "light", // bg-gray-800 vs white
    }).then((result) => {
        if (result.isConfirmed) {
            // Callback function to execute after confirmation
            if (typeof callback === "function") {
                callback();
            }
        }
    });
};

window.feedback = function (title, message, icon) {
    const theme = getThemeMode();

    let timerInterval;
    Swal.fire({
        title: title,
        html: message,
        icon: icon,
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
        theme: theme === "dark" ? "dark" : "light", // bg-gray-800 vs white
        willClose: () => {
            clearInterval(timerInterval);
        },
    }).then((result) => {
        /* Read more about handling dismissals below */
        if (result.dismiss === Swal.DismissReason.timer) {
            console.log("I was closed by the timer");
        }
    });
};

window.rupiah = function (angka) {
    const numberString = angka.toString();
    const sisa = numberString.length % 3;
    let rupiah = numberString.substr(0, sisa);
    const ribuan = numberString.substr(sisa).match(/\d{3}/g);

    if (ribuan) {
        const separator = sisa ? "." : "";
        rupiah += separator + ribuan.join(".");
    }
    return "Rp " + rupiah + ",00";
};

window.confirmRejectWithReason = function (
    message,
    confirmButtonText,
    callback
) {
    const theme = getThemeMode();

    Swal.fire({
        title: "Apakah Anda yakin ingin menolak?",
        text: message || "Masukkan alasan penolakan:",
        input: "textarea", // ✅ Inputan alasan
        inputPlaceholder: "Tulis alasan penolakan di sini...",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: confirmButtonText || "Tolak",
        cancelButtonText: "Batal",
        theme: theme === "dark" ? "dark" : "light", // bg-gray-800 vs white
        inputValidator: (value) => {
            if (!value) {
                return "Alasan wajib diisi!";
            }
        },
    }).then((result) => {
        if (result.isConfirmed) {
            // Callback function after input
            if (typeof callback === "function") {
                callback(result.value); // Pass alasan ke callback
            }
        }
    });
};

window.successAlert = function (message, title) {
    const finalMessage = message || "Berhasil disimpan!";
    const finalTitle = title || "Sukses";

    window.feedback(finalTitle, finalMessage, "success");
};

window.errorAlert = function (message, title) {
    const finalMessage = message || "Terjadi kesalahan!";
    const finalTitle = title || "Gagal";

    window.feedback(finalTitle, finalMessage, "error");
};

window.warningAlert = function (message, title) {
    const finalMessage = message || "Terjadi kesalahan yang tidak diketahui!";
    const finalTitle = title || "Peringatan";

    window.feedback(finalTitle, finalMessage, "warning");
};

// Tambahkan fungsi ini
window.getThemeMode = function () {
    const html = document.documentElement;
    return html.classList.contains("dark") ? "dark" : "light";
};
