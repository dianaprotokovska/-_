<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM Categories")->fetchAll(PDO::FETCH_ASSOC);
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $cooking_time = $_POST['cooking_time'];
    $status = $_POST['status'];
    $ingredients = json_encode(array_filter(array_map('trim', explode("\n", $_POST['ingredients']))));
    $steps = json_encode(array_filter(array_map('trim', explode("\n", $_POST['steps']))));

    $stmt = $pdo->prepare("INSERT INTO Recipes (user_id, category_id, title, description, ingredients, steps, cooking_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $category_id, $title, $description, $ingredients, $steps, $cooking_time, $status]);
    $success = 'Рецепт успішно додано!';
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Додати рецепт</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-color: #fff9f0; }</style>
</head>
<body>
<nav class="navbar navbar-dark" style="background-color:#c0392b">
    <div class="container">
        <a class="navbar-brand" href="index.php">🍽️ Кулінарний блог</a>
    </div>
</nav>
<div class="container mt-4">
    <h2>Додати новий рецепт</h2>
    <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Назва страви</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Категорія</label>
            <select name="category_id" class="form-select">
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= $cat['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Опис</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Інгредієнти (кожен з нового рядка)</label>
            <textarea name="ingredients" class="form-control" rows="5" placeholder="буряк&#10;капуста&#10;морква"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Кроки приготування (кожен з нового рядка)</label>
            <textarea name="steps" class="form-control" rows="5" placeholder="Нарізати овочі&#10;Зварити бульйон"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Час приготування (хвилини)</label>
            <input type="number" name="cooking_time" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Статус</label>
            <select name="status" class="form-select">
                <option value="published">Опубліковано</option>
                <option value="draft">Чернетка</option>
            </select>
        </div>
        <button type="submit" class="btn btn-danger">Додати рецепт</button>
        <a href="index.php" class="btn btn-outline-secondary ms-2">Скасувати</a>
    </form>
</div>
</body>
</html>