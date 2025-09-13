<?php
// criar_pix.php usando Basic Auth PixUp

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
$api_url = "https://api.pixupbr.com/v2/pix/qrcode"; // Produção
$client_id = "agaeverton_7784613094820550";
$client_secret = "b60859bd9cd8c895f049ef0d89bd024f9408e187c6412f3500f53198a15bf5bf";

// Monta header Basic Auth
$credentials = base64_encode($client_id . ":" . $client_secret);
$headers = [
    "Authorization: Basic $credentials",
    "Content-Type: application/json",
    "Accept: application/json"
];

// Monta payload conforme doc PixUp
$payload = [
    "amount" => number_format($valor, 2, '.', ''), // 10.00
    "payer_name" => $nome,
    "payer_document" => $documento,
    "payer_email" => $email,
    "description" => "Doação via Pix"
];

// Inicia CURL
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => $headers,
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

// Decodifica resposta
$dataResp = json_decode($response, true);

if (!$dataResp || isset($dataResp["error"]) || isset($dataResp["erro"])) {
    echo json_encode([
        "erro" => "Resposta inválida da API PixUp",
        "detalhe" => $response
    ]);
    exit;
}

// Retorna os dados importantes
echo json_encode([
    "id" => $dataResp["id"] ?? null,
    "status" => $dataResp["status"] ?? null,
    "amount" => $dataResp["amount"] ?? $valor,
    "qr_code_text" => $dataResp["qr_code"] ?? ($dataResp["pix"]["qr_code"] ?? null),
    "qr_code_image" => $dataResp["qr_code_base64"] ?? ($dataResp["pix"]["qr_code_base64"] ?? null),
    "resposta_completa" => $dataResp // debug
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
