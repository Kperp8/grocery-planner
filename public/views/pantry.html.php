<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Spiżarnia', ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <?php require __DIR__ . '/nav.html.php'; ?>
    <main>
        <h1>Spiżarnia</h1>
    </main>
</body>
</html>