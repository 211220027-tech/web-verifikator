<?php
// Mengambil data dari form jika ada
$private_key         = $_POST['private_key'] ?? "";
$public_key          = $_POST['public_key'] ?? "";
$document_to_sign    = $_POST['document_to_sign'] ?? "Transfer ke Budi: Rp 100.000";
$signature_result    = $_POST['signature_result'] ?? "";
$document_to_verify  = $_POST['document_to_verify'] ?? "Transfer ke Budi: Rp 100.000";
$signature_to_verify = $_POST['signature_to_verify'] ?? "";
$verify_status       = "";

// Konfigurasi Standar Kriptografi 
$config = array(
    "digest_alg"       => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);

// FIX UNTUK ERROR BUGS DI XAMPP WINDOWS (Pencarian file openssl.cnf otomatis)
$possible_paths = array(
    'D:/Xampp/php/extras/ssl/openssl.cnf',
    'D:/Xampp/apache/conf/openssl.cnf',
    'C:/xampp/php/extras/ssl/openssl.cnf',
    'C:/xampp/apache/conf/openssl.cnf'
);
foreach($possible_paths as $path) {
    if(file_exists($path)) {
         $config['config'] = $path; // Konfigurasi path jika ditemukan
         break;
    }
}

// Menangani permintaan tombol dari Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // =============================================
    // 1. GENERATE KEY PAIR
    // =============================================
    if ($action === 'generate') {
        $res = openssl_pkey_new($config);
        if ($res === false) {
            die("<div style='font-family:sans-serif; text-align:center; padding: 50px;'><h3 style='color:red;'>Error Pembuatan Kunci OpenSSL: " . openssl_error_string() . "</h3><p>Pastikan file openssl.cnf berada di folder XAMPP yang tepat.</p></div>");
        }
        
        // Mengekstrak private key
        openssl_pkey_export($res, $private_key, null, $config);
        
        // Mengekstrak public key
        $keyDetails = openssl_pkey_get_details($res);
        $public_key = $keyDetails["key"];
        
        // Bersihkan hasil sebelumnya jika men-generate kunci yang baru
        $signature_result = "";
        $signature_to_verify = "";
    }
    
    // =============================================
    // 2. TANDA TANGANI DOKUMEN (SIGN)
    // =============================================
    if ($action === 'sign') {
        if (empty($private_key)) {
            $verify_status = "<div class='bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded-r-lg font-medium shadow-sm'>⚠️ Error: Lakukan Step 1 (Generate Key) terlebih dahulu!</div>";
        } else {
            // Memberi tanda tangan (signature) dengan Private Key
            openssl_sign($document_to_sign, $signature, $private_key, OPENSSL_ALGO_SHA256);
            $signature_result = base64_encode($signature);
            
            // Auto input ke bagian verifikasi
            $document_to_verify = $document_to_sign;
            $signature_to_verify = $signature_result;
        }
    }
    
    // =============================================
    // 3. VERIFIKASI KEASLIAN DOKUMEN
    // =============================================
    if ($action === 'verify') {
        if (empty($public_key) || empty($signature_to_verify)) {
            $verify_status = "<div class='bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded-r-lg font-medium shadow-sm'>⚠️ Error: Public Key atau Signature belum ada!</div>";
        } else {
            // Ubah signature base64 kembali ke format binary
            $binary_signature = base64_decode($signature_to_verify);
            
            // Verifikasi menggunakan Public Key
            $is_valid = openssl_verify($document_to_verify, $binary_signature, $public_key, OPENSSL_ALGO_SHA256);

            if ($is_valid === 1) {
                $verify_status = "
                <div class='bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 px-6 py-5 rounded-r-xl shadow-md flex items-start gap-4 transition-all'>
                    <div class='text-3xl mt-1'>✅</div>
                    <div>
                        <h4 class='text-xl font-bold text-emerald-900 mb-1'>DOKUMEN VALID! (ASLI)</h4>
                        <p class='text-emerald-700 text-sm'>Integritas data terjaga. Dokumen tidak mengalami perubahan sejak ditandatangani.</p>
                    </div>
                </div>";
            } elseif ($is_valid === 0) {
                $verify_status = "
                <div class='bg-rose-50 border-l-4 border-rose-500 text-rose-800 px-6 py-5 rounded-r-xl shadow-md flex items-start gap-4 transition-all'>
                    <div class='text-3xl mt-1'>❌</div>
                    <div>
                        <h4 class='text-xl font-bold text-rose-900 mb-1'>VERIFIKASI GAGAL! (MITM TERDETEKSI)</h4>
                        <p class='text-rose-700 text-sm'>Awas! Dokumen telah diubah atau dipalsukan oleh pihak ketiga.</p>
                    </div>
                </div>";
            } else {
                $verify_status = "<div class='bg-red-100 text-red-700 px-4 py-3 rounded-lg'>OpenSSL Error: " . openssl_error_string() . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas Kriptografi - Verifikator Dokumen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Custom scrollbar untuk textarea agar lebih rapi */
        textarea::-webkit-scrollbar { width: 8px; }
        textarea::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 8px; }
        textarea::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        textarea::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-slate-200 text-slate-800 antialiased min-h-screen py-10 px-4 sm:px-6 lg:px-8">

    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center p-3 bg-indigo-600 rounded-2xl shadow-lg mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight mb-3">Verifikator Dokumen Digital</h1>
            <p class="text-slate-500 max-w-2xl mx-auto text-lg">Simulasi <strong>Digital Signature</strong> (RSA 2048-bit & SHA-256) untuk mendeteksi serangan <em>Man-in-the-Middle (MITM)</em>.</p>
        </div>

        <form method="POST" action="" class="space-y-8">
            
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden transition-shadow hover:shadow-md">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-4">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full text-sm font-black">1</span>
                        Generate RSA Keys
                    </h2>
                    <button type="submit" name="action" value="generate" class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold py-2 px-5 rounded-xl transition-all shadow-sm focus:ring-4 focus:ring-slate-200">
                        🔄 Generate Key Pair Baru
                    </button>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold mb-2 text-rose-600 text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"></path></svg>
                            Private Key (RAHASIA)
                        </label>
                        <textarea name="private_key" class="w-full h-40 bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-mono text-slate-600 focus:outline-none focus:ring-2 focus:ring-rose-500/20" readonly placeholder="Klik generate untuk membuat kunci..."><?= htmlspecialchars($private_key) ?></textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Gunakan untuk menandatangani dokumen.</p>
                    </div>
                    <div>
                        <label class="block font-semibold mb-2 text-emerald-600 text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z"></path></svg>
                            Public Key (DIBAGIKAN)
                        </label>
                        <textarea name="public_key" class="w-full h-40 bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-mono text-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/20" readonly placeholder="Klik generate untuk membuat kunci..."><?= htmlspecialchars($public_key) ?></textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Digunakan penerima untuk verifikasi keaslian.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden transition-shadow hover:shadow-md">
                <div class="bg-blue-50/50 px-6 py-4 border-b border-blue-100 flex items-center gap-3">
                    <span class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-700 rounded-full text-sm font-black">2</span>
                    <h2 class="text-lg font-bold text-slate-800">Tanda Tangani Dokumen (Sign)</h2>
                </div>
                
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block font-semibold mb-2 text-slate-700 text-sm">Teks Dokumen Asli</label>
                        <textarea name="document_to_sign" class="w-full h-20 border border-slate-300 rounded-xl p-4 font-medium text-slate-700 shadow-inner focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"><?= htmlspecialchars($document_to_sign) ?></textarea>
                    </div>
                    
                    <button type="submit" name="action" value="sign" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-6 rounded-xl transition-all shadow-md hover:shadow-lg focus:ring-4 focus:ring-blue-200 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Buat Digital Signature
                    </button>
                    
                    <div>
                        <label class="block font-semibold mb-2 text-sm text-slate-500">Hasil Signature (Base64)</label>
                        <textarea name="signature_result" class="w-full h-24 bg-slate-100 border border-slate-200 rounded-xl p-3 font-mono text-xs text-blue-800 focus:outline-none" readonly placeholder="Hasil tanda tangan digital akan muncul di sini..."><?= htmlspecialchars($signature_result) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden transition-shadow hover:shadow-md">
                <div class="bg-emerald-50/50 px-6 py-4 border-b border-emerald-100 flex items-center gap-3">
                    <span class="flex items-center justify-center w-8 h-8 bg-emerald-100 text-emerald-700 rounded-full text-sm font-black">3</span>
                    <h2 class="text-lg font-bold text-slate-800">Verifikasi Keaslian (MITM Simulation)</h2>
                </div>

                <div class="p-6">
                    <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl mb-6 text-amber-800 text-sm flex gap-3 items-start">
                        <svg class="w-6 h-6 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <strong class="block mb-1 font-bold text-amber-900">Tugas Simulasi MITM:</strong> 
                            Cobalah mengubah teks kata <strong>"Budi"</strong> di bawah menjadi <strong>"Andi"</strong>. Lalu klik tombol verifikasi di bawah untuk membuktikan PHP akan mendeteksi dokumen sebagai manipulasi.
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="block font-semibold mb-2 text-slate-700 text-sm">Dokumen yang Diterima <span class="font-normal text-slate-400">(Bisa dimodifikasi untuk uji coba)</span></label>
                            <textarea name="document_to_verify" class="w-full h-20 border-2 border-slate-200 rounded-xl p-4 font-medium text-slate-800 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all"><?= htmlspecialchars($document_to_verify) ?></textarea>
                        </div>
                        <div>
                            <label class="block font-semibold mb-2 text-sm text-slate-500">Signature yang Diterima</label>
                            <textarea name="signature_to_verify" class="w-full h-24 border border-slate-200 rounded-xl p-3 font-mono text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"><?= htmlspecialchars($signature_to_verify) ?></textarea>
                        </div>
                    </div>

                    <button type="submit" name="action" value="verify" class="mt-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg shadow-emerald-600/30 w-full transition-all flex items-center justify-center gap-2 text-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Verifikasi Dokumen Sekarang
                    </button>

                    <?php if(!empty($verify_status)): ?>
                        <div class="mt-8 animate-fade-in-up">
                            <?= $verify_status ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </form>
        
        <div class="text-center mt-12 mb-6">
            <p class="text-sm text-slate-400">Modul Praktikum Kriptografi & Keamanan Data</p>
        </div>
    </div>
</body>
</html>