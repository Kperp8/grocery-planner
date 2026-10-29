<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Nie znaleziono strony', ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <?php require __DIR__ . '/nav.html.php'; ?>
    <main>
        <h1>404</h1>
        <p>Nie znaleziono strony, której szukasz.</p>
    </main>
</body>
</html>