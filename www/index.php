<?php
try {
    $pdo = new PDO("mysql:host=mysql;dbname=testdb", "lemp_user", "lemp_password");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("INSERT IGNORE INTO users (id, name) VALUES (1,'Jan Kowalski'),(2,'Anna Nowak')");
    $rows = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $status = "✔ połączono"; $cls = "ok";
} catch (PDOException $e) {
    $status = "✘ brak połączenia"; $cls = "err"; $rows = [];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>LEMP Stack</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 40px; }
    table { border-collapse: collapse; margin-bottom: 24px; }
    td, th { border: 1px solid #ddd; padding: 8px 16px; }
    th { background: #f0f0f0; }
    .ok { color: green; } .err { color: red; }
  </style>
</head>
<body>
  <h2>LEMP Stack – Lab 14</h2>

  <table>
    <tr><th>Usługa</th><th>Status</th></tr>
    <tr><td>Nginx</td><td class="ok">✔ działa</td></tr>
    <tr><td>PHP <?= phpversion() ?></td><td class="ok">✔ działa</td></tr>
    <tr><td>MySQL</td><td class="<?= $cls ?>"><?= $status ?></td></tr>
    <tr><td>phpMyAdmin</td><td class="ok">✔ <a href="http://localhost:6001">localhost:6001</a></td></tr>
  </table>

  <?php if ($rows): ?>
  <h3>Tabela users (testdb)</h3>
  <table>
    <tr><th>id</th><th>name</th><th>created_at</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr><td><?= $r['id'] ?></td><td><?= $r['name'] ?></td><td><?= $r['created_at'] ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</body>
</html>
