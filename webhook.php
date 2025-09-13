<?php
// webhook.php
header("Content-Type: application/json");

// Captura o JSON enviado pela PixUp
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Log para debug (salva em arquivo)
file_put_contents("pixup_webhook.log", date("Y-m-d H:i:s") . " - " . $input . PHP_EOL, FILE_APPEND);

// Caso o JSON esteja vazio
if (!$data) {
    http_response_code(400);
    echo json_encode(["erro" => "Payload inválido"]);
    exit;
}

/**
 * Exemplo esperado (ajuste conforme PixUp envia):
 * {
 *   "id": "123456",
 *   "status": "paid",
 *   "amount": 20.00,
 *   "payer": {
 *       "name": "João da Silva",
 *       "document": "12345678909",
 *       "email": "teste@email.com"
 *   }
 * }
 */

// ====== Aqui você faria update no banco ======
$id     = $data["id"] ?? null;
$status = $data["status"] ?? null;
$amount = $data["amount"] ?? null;

if ($id && $status === "paid") {
    // Exemplo: atualizar doação no banco
    // require_once("db.php");
    // $pdo->prepare("UPDATE doacoes SET status = 'pago' WHERE transaction_id = ?")->execute([$id]);
}

// Resposta obrigatória
http_response_code(200);
echo json_encode(["ok" => true]);
