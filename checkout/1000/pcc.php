<?php
header('Content-Type: application/json');

$apiToken = 'mbnsQwXer5d58rzrSMKv7S87HoVFbne8aDNEQUPSGQzfb43LeUa6oRBLqBpj';
$apiUrl = 'https://api.goatpayments.com.br/api/public/v1/transactions/';

if (empty($_GET['transaction_hash'] )) {
    echo json_encode(['error' => 'Hash da transação não fornecido.']);
    exit;
}

$transactionHash = $_GET['transaction_hash'];

$url = $apiUrl . $transactionHash . '?api_token=' . $apiToken;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE );
curl_close($ch);

if ($httpCode == 200 ) {
    $data = json_decode($response, true);
    // Retorna um status simplificado para o frontend
    if (isset($data['payment_status']) && $data['payment_status'] === 'paid') {
        echo json_encode(['status' => 'paid']);
    } else {
        echo json_encode(['status' => 'waiting_payment']);
    }
} else {
    echo json_encode(['error' => 'Erro ao verificar o status do pagamento.']);
}
