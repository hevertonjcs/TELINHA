<?php
// criar_pix.php (corrigido para autenticação OAuth2 PixUp)

// Recebe dados do frontend
$data = json_decode(file_get_contents("php://input"), true);
$valor = isset($data["valor"]) ? floatval($data["valor"]) : 0;
$nome = isset($data["nome"]) ? $data["nome"] : '';
$documento = isset($data["documento"]) ? $data["documento"] : '';
$email = isset($data["email"]) ? $data["email"] : '';

if ($valor <= 0 || !$nome || !$documento || !$email) {
    header("Content-Type: application/json");
    echo json_encode(["erro" => "Dados incompletos ou valor inválido"]);
    exit;
}

// Configurações PixUp
$auth_url = "https://api.pixupbr.com/oauth/token";  // endpoint para gerar token
$api_url  = "https://api.pixupbr.com/v2/pix/qrcode"; // endpoint para gerar qr code
$client_id = "agaeverton_7784613094820550";   // sua PUBLICKEY
$client_secret = "b60859bd9cd8c895f049ef0d89bd024f9408e187c6412f3500f53198a15bf5bf"; // sua SECRETKEY

// ===== 1) Gera o token OAuth2 =====
$auth_payload = [
    "grant_type" => "client_credentials",
    "client_id" => $client_id,
    "client_secret" => $client_secret
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $auth_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($auth_payload),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/x-www-form-urlencoded"
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    header("Content-Type: application/json");
    echo json_encode(["erro" => "Erro ao conectar API de autenticação PixUp: $err"]);
    exit;
}

$tokenResp = json_decode($response, true);

if (!isset($tokenResp["access_token"])) {
    header("Content-Type: application/json");
    echo json_encode([
        "erro" => "Falha ao obter token OAuth2",
        "detalhe" => $response
    ]);
    exit;
}

$access_token = $tokenResp["access_token"];

// ===== 2) Monta payload para gerar QR Code =====
$payload = [
    "amount" => number_format($valor, 2, '.', ''), // sempre no formato 10.00
    "payer_name" => $nome,
    "payer_document" => $documento,
    "payer_email" => $email,
    "description" => "Doação via Pix"
];

// ===== 3) Chama a API do QR Code =====
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $access_token",
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
    echo json_encode(["erro" => "Erro ao conectar API PixUp: $err"]);
    exit;
}

$dataResp = json_decode($response, true);

if (!$dataResp || isset($dataResp["error"]) || isset($dataResp["erro"])) {
    echo json_encode([
        "erro" => "Resposta inválida da API PixUp",
        "detalhe" => $response
    ]);
    exit;
}

// ===== 4) Retorna os dados =====
echo json_encode([
    "id" => $dataResp["id"] ?? null,
    "status" => $dataResp["status"] ?? null,
    "amount" => $dataResp["amount"] ?? $valor,
    "qr_code_text" => $dataResp["qr_code"] ?? null,
    "qr_code_image" => $dataResp["qr_code_base64"] ?? null,
    "resposta_completa" => $dataResp // útil para debug
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
