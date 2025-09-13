<?php
// status_pix.php
header("Content-Type: application/json");

// ====== CREDENCIAIS PIXUP ======
$client_id = "J7TRNP_2107212690294754";
$client_secret = "c3363e4ca3bcb0db46f4f11910a2a7a7722f4e3d06c59eb175a03d099d946e33";

// Monta autenticação Basic Auth
$auth = base64_encode($client_id . ":" . $client_secret);

// ====== CAPTURA O ID DA TRANSAÇÃO ======
$id = $_GET["id"] ?? null;

if (!$id) {
    echo json_encode(["erro" => "ID da transação não informado"]);
    exit;
}

// ====== CONSULTA NA API PIXUP ======
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.pixupbr.com/v2/pix/$id",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Basic " . $auth,
        "Accept: application/json"
    ]
]);

$response = curl_exec($ch);
$err = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ====== TRATA RESPOSTA ======
if ($err) {
    echo json_encode(["erro" => "Erro ao conectar API: $err"]);
    exit;
}

$dataResp = json_decode($response, true);

if ($http_code !== 200) {
    echo json_encode([
        "erro" => "Falha ao consultar pagamento",
        "http_code" => $http_code,
        "detalhe" => $response
    ]);
    exit;
}

// ====== RETORNA STATUS ======
echo json_encode([
    "id"     => $dataResp["id"] ?? $id,
    "status" => $dataResp["status"] ?? "desconhecido",
    "amount" => $dataResp["amount"] ?? null
]);
