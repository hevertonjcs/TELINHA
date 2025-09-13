<?php
// webhook.php - Webhook da SourcePay para atualização de pagamentos Pix

// Lê o corpo da requisição
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Log básico para debug
file_put_contents(__DIR__ . "/webhook_log.txt", date("Y-m-d H:i:s") . " - " . $input . PHP_EOL, FILE_APPEND);

// Verifica se veio um ID de transação
if (!$data || !isset($data["id"])) {
    http_response_code(400);
    echo json_encode(["erro" => "Payload inválido"]);
    exit;
}

$transactionId = $data["id"];
$status = $data["status"] ?? "desconhecido";
$valor = $data["amount"] ?? null;

// Aqui você poderia atualizar o status no seu banco de dados
// Exemplo:
try {
    // require_once 'includes/db.php'; // habilite se tiver conexão com banco
    // $stmt = $conn->prepare("UPDATE transacoes SET status = ? WHERE transaction_id = ?");
    // $stmt->bind_param("ss", $status, $transactionId);
    // $stmt->execute();

    // Por enquanto só salva no log
    file_put_contents(__DIR__ . "/webhook_updates.txt", "Transação {$transactionId} -> Status: {$status}" . PHP_EOL, FILE_APPEND);
} catch (Exception $e) {
    file_put_contents(__DIR__ . "/webhook_errors.txt", "Erro DB: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
}

// Retorna OK para a SourcePay
http_response_code(200);
echo json_encode(["success" => true, "transactionId" => $transactionId, "status" => $status]);
