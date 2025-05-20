<?php
define("IN_SITE", true);
require_once("../core/DB.php");
require_once("../core/helpers.php");

$ch = curl_init('/apikey');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
curl_close($ch);
        $result = json_decode($result, true);

if(!isset($result)){
    die('Không thể lấy lịch sử giao dịch');
}
foreach ($result['transactions'] as $data) {
    if($data['amount'] == 0) continue;
    $comment        = $data['description'];                 // NỘI DUNG CHUYỂN TIỀN
    $tranId         = $data['transactionID'];                  // MÃ GIAO DỊCH
    // $balance        = $data['balance'];                 //số dư
    $amount         = $data['amount'];                  // SỐ TIỀN CHUYỂN
    $transDate      = $data['transactionDate'];            //time
    $sdt        = parse_order_id($comment, 'ungho');          // TÁCH NỘI DUNG CHUYỂN TIỀN
    // XỬ LÝ AUTO
     $order = $NNL->get_row("SELECT * FROM `orders` WHERE `phone` = '$sdt' AND `amount` = '$amount' AND `status` = 'pending'");

     if ($order) {
        // Check if created_at is older than 30 minutes
        if ((time() - $order['created_at']) > 1800) { // 1800 seconds = 30 minutes
            $NNL->update("orders", [
                'status' => 'failed',
                'updated_at' => time()
            ], " `id` = '" . $order['id'] . "' ");
            continue; // Skip to next transaction
        }

        $donate = $NNL->update("orders", [
            'status' => 'completed',
            'updated_at' => time()
        ], " `id` = '" . $order['id'] . "' ");

        if ($donate) {
            exit(json_encode(array('status' => '2', 'msg' => 'Bạn đã ủng hộ số tiền ' . $amount . 'đ vào vào dự án.')));
        } else {
            echo 'Update lỗi: ' . mysqli_error($NNL->conn);
        }
    }

}

