<?php
// postback.php
// ✅ Recebe notificações da SourcePay sobre mudanças de status

header("Content-Type: application/json");

// Captura o corpo da requisição
$input = file_get_contents("php://input");
$data  = json_decode($input, true);

// Log completo (debug) - cuidado em produção!
file_put_contents("postback_log.txt", 
    "==== " . date("Y-m-d H:i:s") . " ====" . PHP_EOL .
    "Headers: " . json_encode(getallheaders()) . PHP_EOL .
    "Body: " . $input . PHP_EOL . PHP_EOL, 
    FILE_APPEND
);

// Validação do payload
if (!$data || !isset($data["id"])) {
    http_response_code(400);
    echo json_encode(["erro" => "Payload inválido"]);
    exit;
}

// Extrai dados principais
$transactionId = $data["id"];
$status        = $data["status"]        ?? "desconhecido";
$amount        = $data["amount"]        ?? 0;
$paymentMethod = $data["paymentMethod"] ?? "desconhecido";

// 🔑 Configuração do banco (ideal mover para config.php)
$db_host = "localhost";
$db_name = "orfanato";
$db_user = "usuario";
$db_pass = "senha";

// Salvar no banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO pagamentos (transaction_id, status, amount, payment_method, atualizado_em)
        VALUES (:id, :status, :amount, :paymentMethod, NOW())
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            amount = VALUES(amount),
            payment_method = VALUES(payment_method),
            atualizado_em = NOW()
    ");

    $stmt->execute([
        ":id"            => $transactionId,
        ":status"        => $status,
        ":amount"        => $amount,
        ":paymentMethod" => $paymentMethod
    ]);

} catch (Exception $e) {
    file_put_contents("postback_error_log.txt", 
        date("Y-m-d H:i:s") . " - Erro DB: " . $e->getMessage() . PHP_EOL, 
        FILE_APPEND
    );
}

// Sempre responder 200 OK para a SourcePay
http_response_code(200);
echo json_encode([
    "ok"      => true,
    "id"      => $transactionId,
    "status"  => $status,
    "amount"  => $amount
]);
