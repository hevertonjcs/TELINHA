<?php
// criar_pix.php (atualizado para PixUp com dados do doador)

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
$api_url = "https://api.pixupbr.com/v2/pix/qrcode";
$client_id = "J7TRNP_2107212690294754";  // sua PUBLICKEY
$client_secret = "c3363e4ca3bcb0db46f4f11910a2a7a7722f4e3d06c59eb"; // SECRETKEY

// Monta header de autenticação Basic
$auth_str = base64_encode($client_id . ":" . $client_secret);
$headers = [
    "Authorization: Basic $auth_str",
    "Content-Type: application/json",
    "Accept: application/json"
];

// Monta payload conforme documentação PixUp
$payload = [
    "amount" => $valor,
    "payer" => [
        "name" => $nome,
        "document" => $documento,
        "email" => $email
    ]
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

if (!$dataResp || isset($dataResp["erro"])) {
    echo json_encode(["erro" => "Resposta inválida da API PixUp", "detalhe" => $response]);
    exit;
}

// Retorna os dados importantes
echo json_encode([
    "id" => $dataResp["id"] ?? null,
    "status" => $dataResp["status"] ?? null,
    "amount" => $dataResp["amount"] ?? $valor,
    "qr_code_text" => $dataResp["pix"]["qr_code"] ?? null,
    "qr_code_image" => $dataResp["pix"]["qr_code_base64"] ?? null
]);
