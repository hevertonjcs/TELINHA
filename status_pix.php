<?php
// status_pix.php - Verificar status de uma transação Pix (SourcePay)

if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("Content-Type: application/json");
    echo json_encode(["erro" => "ID da transação não informado"]);
    exit;
}

$transactionId = $_GET["id"];

// Suas credenciais SourcePay
$publicKey = "pk_t2mGz4QxqHbA4Z9003PXmDfUxyJl0RxPOpFeodFCwajYDe9h";
$secretKey = "sk_UzNR4r4Q-W2KdBYoFwm5thXRX3JFlYOnE9C2VfjGFtUGAmzs";

// Monta Basic Auth
$auth = base64_encode($publicKey . ":" . $secretKey);

// Endpoint da SourcePay para consultar a transação
$api_url = "https://api.sourcepay.com.br/v1/transactions/" . urlencode($transactionId);

// Requisição CURL
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Basic $auth",
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

header("Content-Type: application/json");

if ($err) {
    echo json_encode(["erro" => "Erro ao conectar API SourcePay: $err"]);
    exit;
}

$dataResp = json_decode($response, true);

if (!$dataResp || isset($dataResp["error"])) {
    echo json_encode([
        "erro" => "Resposta inválida da API SourcePay",
        "detalhe" => $response
    ]);
    exit;
}

// Retorna somente dados relevantes para o JS
echo json_encode([
    "id" => $dataResp["id"] ?? null,
    "status" => $dataResp["status"] ?? null,
    "amount" => $dataResp["amount"] ?? null,
    "resposta_completa" => $dataResp // debug
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
