<?php
session_start(); // Memulai sesi untuk menyimpan riwayat chat

// Menghapus riwayat chat jika tombol hapus ditekan
if (isset($_POST['reset'])) {
    unset($_SESSION['chat_history']); // Menghapus riwayat chat
}

// Inisialisasi riwayat chat jika belum ada
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = []; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['reset'])) {
    $input = $_POST['input'];
    $apiKey = 'AIzaSyAAIdccTxZtmP5ZeBvNHyYFitdX83eKVPk';
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $apiKey;

    $data = json_encode([
        'contents' => [
            ['parts' => [['text' => $input]]]
        ]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === FALSE) {
        $output = 'Error: Gagal menghubungi API.';
    } else {
        $result = json_decode($response, true);
        
        // Periksa apakah respons mengandung bagian yang diharapkan
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $rawText = $result['candidates'][0]['content']['parts'][0]['text'];
            $output = nl2br(htmlspecialchars($rawText));
        } else {
            $output = 'Tidak ada respons yang diterima. Kode HTTP: ' . $httpCode;
        }
    }

    // Simpan pertanyaan dan jawaban ke riwayat chat
    $_SESSION['chat_history'][] = ['question' => htmlspecialchars($input), 'answer' => $output];
}

// Ambil riwayat chat untuk ditampilkan
$chatHistory = $_SESSION['chat_history'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat dengan AI</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .container-fluid {
            max-width: 90%;
            margin: auto;
            padding-bottom: 80px;
            position: relative;
        }
        .message {
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
        }
        .message.user {
            align-items: flex-end;
        }
        .message.ai {
            align-items: flex-start;
        }
        .balloon {
            display: inline-block;
            padding: 10px;
            border-radius: 10px;
            color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            animation: pop 0.5s ease-in-out;
        }
        .user-balloon {
            background-color: #34495e;
        }
        .ai-balloon {
            background-color: #27ae60;
        }
        .fixed-form {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: white;
            padding: 10px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
        }
        .floating-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            cursor: pointer;
        }
        .info-button {
            position: fixed;
            top: 20px;
            right: 80px;
            background-color: #34495e;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            cursor: pointer;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: none; /* Sembunyikan secara default */
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Pastikan overlay di atas semua konten */
        }
        .loading-message {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }
        @keyframes pop {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-message">
            Tunggu Dulu Ya Aku Baca Primbon Dulu...
        </div>
    </div>

    <div class="container-fluid mt-5">
        <h3>Tanya Pa Amos</h3>
        <hr/>
        <div class="mt-3" style="overflow-y: auto; max-height: 70vh;">
            <?php foreach ($chatHistory as $chat): ?>
                <div class="message user">
                    <strong>Saya <img src="https://cdn-icons-png.freepik.com/512/8664/8664801.png" alt="User" style="width: 50px; border-radius: 50%;"></strong>
                    <br>
                    <div class="balloon user-balloon animate__animated animate__bounce"><?php echo $chat['question']; ?></div>
                </div>
                <div class="message ai">
                    <strong><img src="https://onicaze.github.io/alphabase/images/team/paamos.jpg" alt="AI" style="width: 50px; border-radius: 50%;"> Wejangan Pa Amos</strong>
                    <br>
                    <div class="balloon ai-balloon animate__animated animate__bounce"><?php echo $chat['answer']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <br><br><br>
        
        <div class="fixed-form">
            <form id="chatForm" action="" method="post" style="flex-grow: 1; display: flex; align-items: center;">
                <div class="form-group mb-0 flex-grow-1">
                    <input type="text" class="form-control" id="input" name="input" required placeholder="Ketik pertanyaan Anda..." onkeypress="checkEnter(event)">
                </div>
                <button type="submit" class="btn btn-primary ml-2">Kirim</button>
            </form>
        </div>
    </div>

    <button class="floating-button" onclick="document.getElementById('resetForm').submit();">
        &#x21bb; <!-- Simbol refresh -->
    </button>
    <button class="info-button" onclick="showInfo();">
        &#x1F6C8; <!-- Simbol informasi -->
    </button>
    <form id="resetForm" action="" method="post" style="display: none;">
        <input type="hidden" name="reset" value="1">
    </form>

    <script>
        function showInfo() {
            const infoHtml = `
                <div class="modal" tabindex="-1" role="dialog" id="infoModal">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Informasi</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal()">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <img src="https://onicaze.github.io/alphabase/images/team/paamos.jpg" class="img-fluid" alt="Pa Amos">
                                <p>Curhat Sama Pa Amos dibuat pada hari Selasa 24 September 2024 oleh Pa Amos menggunakan kunci API Google AI Studio</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', infoHtml);
            $('#infoModal').modal('show');
        }

        function closeModal() {
            document.getElementById('infoModal').remove();
        }
        
        function checkEnter(event) {
            if (event.key === "Enter") {
                event.preventDefault(); // Mencegah pengiriman form default
                showLoading(); // Tampilkan loading screen
                document.getElementById('chatForm').submit(); // Kirim form
            }
        }

        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex'; // Tampilkan overlay
        }

        // Scroll ke bawah setiap kali ada pesan baru
        function scrollToBottom() {
            window.scrollTo(0, document.body.scrollHeight);
        }

        // Panggil fungsi saat halaman dimuat
        window.onload = scrollToBottom;
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
