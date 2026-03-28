<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

// 1. KONEKSI: Samakan URL-nya dengan yang ada di send.php
$urlStr = 'amqps://hruypzua:2MKmAIzQHhnD_PaD_4qWiI3SblPJn2yD@chameleon.lmq.cloudamqp.com/hruypzua'; 
$url = parse_url($urlStr);

$connection = new AMQPStreamConnection(
    $url['host'], 
    5672, 
    $url['user'], 
    $url['pass'], 
    substr($url['path'], 1)
);
$channel = $connection->channel();

$channel->queue_declare('notification_queue', false, true, false, false);

echo " [*] Consumer standby menunggu pesan... (Tekan CTRL+C untuk berhenti)\n";

// 2. CALLBACK: Fungsi yang dijalankan saat pesan diterima [cite: 48]
$callback = function ($msg) {
    $data = json_decode($msg->body, true);
    echo " [v] Consumer menerima: " . $data['order_id'] . " | Status: " . $data['status'] . "\n";
    
    // Simulasi proses background agar aplikasi tetap responsif [cite: 82, 84]
    echo "     => Sedang mengirim notifikasi ke " . $data['customer'] . "...\n";
    sleep(3); 
    
    echo "     => Selesai! Notifikasi terkirim.\n";

    // 3. ACKNOWLEDGMENT (ACK): Memberitahu broker bahwa pesan sukses diproses [cite: 49, 60]
    $msg->ack();
};

// Batasi consumer hanya memproses 1 pesan di satu waktu agar efisien [cite: 96]
$channel->basic_qos(null, 1, null);

// 4. CONSUME: Mulai mengambil pesan dari antrean [cite: 9]
$channel->basic_consume('notification_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}