<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        $image = $data['image'] ?? '';

        if (!$name || !$email || !$password) {
            $res = ['success' => false, 'message' => 'Campos incompletos'];
        } else {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $res = ['success' => false, 'message' => 'El email ya está registrado'];
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password, image) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashedPassword, $image]);

                $res = [
                    'success' => true,
                    'message' => 'Usuario registrado exitosamente'
                ];
            }
        }

        $response->getBody()->write(json_encode($res));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (!$email || !$password) {
            $res = ['success' => false, 'message' => 'Email y contraseña requeridos'];
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $res = [
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'image' => $user['image']
                    ],
                    'token' => bin2hex(random_bytes(16))
                ];
                // (opcional) guardar el token en la BD
                $this->pdo->prepare("UPDATE users SET token = ? WHERE id = ?")
                    ->execute([$res['token'], $user['id']]);
            } else {
                $res = ['success' => false, 'message' => 'Credenciales incorrectas'];
            }
        }

        $response->getBody()->write(json_encode($res));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
