<?php
require_once __DIR__ . "/db.php";

$input = json_decode(file_get_contents("php://input"), true) ?? [];

$name = trim($input["name"] ?? "");
$email = strtolower(trim($input["email"] ?? ""));
$password = (string)($input["password"] ?? "");

if ($name === "" || $email === "" || $password === "") {
  json_response(["ok" => false, "error" => "name, email, password are required"], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_response(["ok" => false, "error" => "Invalid email"], 400);
}
if (strlen($password) < 8) {
  json_response(["ok" => false, "error" => "Password must be at least 8 characters"], 400);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
  $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
  $stmt->execute([$name, $email, $hash]);
  json_response(["ok" => true]);
} catch (PDOException $e) {
  json_response(["ok" => false, "error" => "Email already exists"], 409);
}
