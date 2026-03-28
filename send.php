<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 1. KONEKSI: Masukkan AMQP URL dari CloudAMQP di bawah ini
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

// 2. DEKLARASI QUEUE: Membuat antrean bernama 'notification_queue'
// durable: true artinya pesan tetap aman jika server restart [cite: 8, 93]
$channel->queue_declare('notification_queue', false, true, false, false);

// 3. ISI PESAN: Simulasi data order (Notification Service Case Study) [cite: 129, 130]
$data = [
    'order_id' => 'TRX-' . rand(1000, 9999),
    'customer' => 'Gilang',
    'status'   => 'Pembayaran Berhasil',
    'message'  => 'Kirim notifikasi ke Seller dan Customer via Email atau SMS'
];
$payload = json_encode($data);

$msg = new AMQPMessage($payload, [
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT // Pesan tersimpan di disk [cite: 8, 91]
]);

// 4. PUBLISH: Mengirim pesan ke antrean [cite: 11, 47]
$channel->basic_publish($msg, '', 'notification_queue');

echo " [x] Publisher: Berhasil mengirim pesan Order " . $data['order_id'] . " ke Antrean.\n";

$channel->close();
$connection->close();