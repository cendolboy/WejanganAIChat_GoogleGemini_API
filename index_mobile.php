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
    $apiKey = 'YOUR_API_KEY'; // Ganti dengan API Key Anda
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #FFE5D9;
        }
    </style>
</head>
<body class="flex justify-center items-center h-screen">
    <div class="w-96 bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-purple-700 p-4 flex items-center">
            <img alt="AI Profile" class="w-12 h-12 rounded-full mr-4" src="https://onicaze.github.io/alphabase/images/team/paamos.jpg" />
            <div class="text-white">
                <div class="font-bold">Wejangan Pa Amos</div>
                <div class="text-sm">Online</div>
            </div>
        </div>
        <div class="p-4 space-y-4 overflow-y-auto max-h-80">
            <?php foreach ($chatHistory as $chat): ?>
                <div class="flex justify-start">
                    <div class="text-xs text-gray-500">Sekarang</div>
                    <div class="ml-2 bg-gray-100 text-gray-700 p-2 rounded-lg">
                        <?php echo $chat['question']; ?>
                    </div>
                </div>
                <div class="flex justify-end">
                    <div class="bg-green-500 text-white p-2 rounded-lg">
                        <div><?php echo $chat['answer']; ?></div>
                    </div>
                    <div class="text-xs text-gray-500 ml-2">Sekarang</div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="p-4 flex items-center border-t">
            <form id="chatForm" action="" method="post" class="flex-1 flex items-center">
                <input class="flex-1 p-2 border rounded-lg focus:outline-none" placeholder="Ketik pertanyaan Anda..." type="text" id="input" name="input" required onkeypress="checkEnter(event)" />
                <button type="submit" class="ml-2 text-purple-700">
                    <i class="fas fa-paper-plane text-xl"></i>
                </button>
            </form>
            <button class="ml-2" onclick="document.getElementById('resetForm').submit();">
                <i class="fas fa-redo text-yellow-500 text-xl"></i>
            </button>
        </div>
    </div>
    <form id="resetForm" action="" method="post" style="display: none;">
        <input type="hidden" name="reset" value="1">
    </form>

    <script>
        function checkEnter(event) {
            if (event.key === "Enter") {
                event.preventDefault(); // Mencegah pengiriman form default
                document.getElementById('chatForm').submit(); // Kirim form
            }
        }
    </script>
</body>
</html>
