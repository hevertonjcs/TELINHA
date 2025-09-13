<?php
// criar_pix.php - Integração com SourcePay (Pix)

// Recebe dados do frontend
$data = json_decode(file_get_contents("php://input"), true);
$valor = isset($data["valor"]) ? floatval($data["valor"]) : 0;
$nome = isset($data["nome"]) ? trim($data["nome"]) : '';
$documento = isset($data["documento"]) ? preg_replace('/\D/', '', $data["documento"]) : '';
$email = isset($data["email"]) ? trim($data["email"]) : '';

if ($valor <= 0 || !$nome || !$documento || !$email) {
    header("Content-Type: application/json");
    echo json_encode(["erro" => "Dados incompletos ou valor inválido"]);
    exit;
}

// SourcePay exige amount em centavos
$valorCentavos = intval(round($valor * 100));

// Credenciais (suas chaves)
$publicKey = "pk_t2mGz4QxqHbA4Z9003PXmDfUxyJl0RxPOpFeodFCwajYDe9h";
$secretKey = "sk_UzNR4r4Q-W2KdBYoFwm5thXRX3JFlYOnE9C2VfjGFtUGAmzs";

// Monta Basic Auth
$auth = base64_encode($publicKey . ":" . $secretKey);

// Endpoint da SourcePay
$api_url = "https://api.sourcepay.com.br/v1/transactions";

// Monta payload (item genérico "Doação")
$payload = [
    "amount" => $valorCentavos,
    "currency" => "BRL",
    "paymentMethod" => "pix",
    "items" => [[
        "title" => "Doação",
        "unitPrice" => $valorCentavos,
        "quantity" => 1,
        "tangible" => false
    ]],
    "customer" => [
        "name" => $nome,
        "email" => $email,
        "document" => [
            "number" => $documento,
            "type" => strlen($documento) > 11 ? "cnpj" : "cpf"
        ]
    ]
];

// Requisição CURL
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Authorization: Basic $auth",
        "Content-Type: application/json",
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

// Normaliza retorno (SourcePay envia QR Code dentro de pix.qrCode e pix.qrCodeBase64)
$pixData = $dataResp["pix"] ?? [];

echo json_encode([
    "id" => $dataResp["id"] ?? null,
    "status" => $dataResp["status"] ?? null,
    "amount" => $valor,
    "qr_code_text" => $pixData["qrCode"] ?? null,
    "qr_code_image" => $pixData["qrCodeBase64"] ?? null,
    "resposta_completa" => $dataResp // debug
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
