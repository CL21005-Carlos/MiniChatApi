<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ChatController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Método para enviar un mensaje
    public function sendMessage(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $message = trim($data['message'] ?? '');
        $senderId = trim($data['senderId'] ?? '');
        $receiverId = trim($data['receiverId'] ?? '');

        if (!$message || !$senderId || !$receiverId) {
            $res = ['success' => false, 'message' => 'Campos incompletos'];
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO chat (message, senderId, receiverId, timestamp) VALUES (?, ?, ?, NOW())");
            $success = $stmt->execute([$message, $senderId, $receiverId]);

            if ($success) {
                $res = ['success' => true, 'message' => 'Mensaje enviado'];
            } else {
                $res = ['success' => false, 'message' => 'Error al enviar el mensaje'];
            }
        }

        $response->getBody()->write(json_encode($res));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Método para obtener mensajes entre dos usuarios ordenados por fecha
    public function getMessages(Request $request, Response $response, array $args): Response
    {
        $senderId = $args['senderId'] ?? '';
        $receiverId = $args['receiverId'] ?? '';

        if (!$senderId || !$receiverId) {
            $res = ['success' => false, 'message' => 'Faltan IDs de usuarios'];
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM chat 
                WHERE (senderId = :senderId AND receiverId = :receiverId) 
                   OR (senderId = :receiverId AND receiverId = :senderId)
                ORDER BY timestamp ASC"
            );
            $stmt->execute([
                ':senderId' => $senderId,
                ':receiverId' => $receiverId
            ]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $res = ['success' => true, 'messages' => $messages];
        }

        $response->getBody()->write(json_encode($res));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
