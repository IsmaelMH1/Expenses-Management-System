<?php
require_once __DIR__ . "/db.php";

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$email = strtolower(trim($input["email"] ?? ""));
$password = (string)($input["password"] ?? "");

if ($email === "" || $password === "") {
  json_response(["ok" => false, "error" => "email and password are required"], 400);
}

$stmt = $pdo->prepare("SELECT id, name, password_hash FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user["password_hash"])) {
  json_response(["ok" => false, "error" => "Invalid credentials"], 401);
}

$_SESSION["user_id"] = (int)$user["id"];
$_SESSION["name"] = $user["name"];

json_response(["ok" => true, "name" => $user["name"]]);
