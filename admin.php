<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Видалити рецепт
if (isset($_POST['delete_recipe'])) {
    $rid = $_POST['recipe_id'];
    $pdo->prepare("DELETE FROM Comments WHERE recipe_id = ?")->execute([$rid]);
    $pdo->prepare("DELETE FROM Likes WHERE recipe_id = ?")->execute([$rid]);
    $pdo->prepare("DELETE FROM Ratings WHERE recipe_id = ?")->execute([$rid]);
    $pdo->prepare("DELETE FROM Favorites WHERE recipe_id = ?")->execute([$rid]);
    $pdo->prepare("DELETE FROM Recipes WHERE recipe_id = ?")->execute([$rid]);
    header('Location: admin.php');
    exit;
}

// Видалити користувача
if (isset($_POST['delete_user'])) {
    $uid = $_POST['user_id'];
    $pdo->prepare("DELETE FROM Comments WHERE user_id = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM Likes WHERE user_id = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM Ratings WHERE user_id = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM Favorites WHERE user_id = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM Recipes WHERE user_id = ?")->execute([$uid]);
    $pdo->prepare("DELETE FROM Users WHERE idUsers = ?")->execute([$uid]);
    header('Location: admin.php');
    exit;
}

// Додати категорію
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    if (!empty($name)) {
        $pdo->prepare("INSERT INTO Categories (name) VALUES (?)")->execute([$name]);
    }
    header('Location: admin.php');
    exit;
}

// Статистика
$totalRecipes = $pdo->query("SELECT COUNT(*) FROM Recipes")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM Comments")->fetchColumn();
$totalRatings = $pdo->query("SELECT COUNT(*) FROM Ratings")->fetchColumn();

// Всі рецепти
$recipes = $pdo->query("
    SELECT r.*, c.name AS category, u.Username AS author
    FROM Recipes r
    JOIN Categories c ON r.category_id = c.category_id
    JOIN Users u ON r.user_id = u.idUsers
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Всі користувачі
$users = $pdo->query("SELECT * FROM Users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Всі категорії
$categories = $pdo->query("SELECT * FROM Categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Адмін панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #c0392b !important; }
        .stat-card { border-left: 4px solid #c0392b; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🍽️ Кулінарний блог</a>
        <div>
            <a href="index.php" class="btn btn-outline-light me-2">На сайт</a>
            <a href="logout.php" class="btn btn-outline-light">Вийти</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4">⚙️ Адміністративна панель</h2>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted">Рецептів</h6>
                    <h2><?= $totalRecipes ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted">Користувачів</h6>
                    <h2><?= $totalUsers ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted">Коментарів</h6>
                    <h2><?= $totalComments ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="text-muted">Оцінок</h6>
                    <h2><?= $totalRatings ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Категорії -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">📂 Категорії</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?php foreach($categories as $cat): ?>
                        <span class="badge bg-warning text-dark me-2 mb-2 p-2">
                            <?= htmlspecialchars($cat['name']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-6">
                    <form method="POST" class="d-flex gap-2">
                        <input type="text" name="category_name" class="form-control" placeholder="Нова категорія">
                        <button name="add_category" class="btn btn-danger">Додати</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Рецепти -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">📝 Всі рецепти</h5></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Назва</th>
                        <th>Категорія</th>
                        <th>Автор</th>
                        <th>Статус</th>
                        <th>Дія</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recipes as $recipe): ?>
                    <tr>
                        <td><?= $recipe['recipe_id'] ?></td>
                        <td>
                            <a href="recipe.php?id=<?= $recipe['recipe_id'] ?>">
                                <?= htmlspecialchars($recipe['title']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($recipe['category']) ?></td>
                        <td><?= htmlspecialchars($recipe['author']) ?></td>
                        <td>
                            <span class="badge <?= $recipe['status'] == 'published' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $recipe['status'] == 'published' ? 'Опубліковано' : 'Чернетка' ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Видалити рецепт?')">
                                <input type="hidden" name="recipe_id" value="<?= $recipe['recipe_id'] ?>">
                                <button name="delete_recipe" class="btn btn-danger btn-sm">Видалити</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Користувачі -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">👥 Всі користувачі</h5></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Ім'я</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Дата реєстрації</th>
                        <th>Дія</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?= $user['idUsers'] ?></td>
                        <td><?= htmlspecialchars($user['Username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="badge <?= $user['role'] == 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                <?= $user['role'] == 'admin' ? 'Адмін' : 'Користувач' ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <?php if($user['role'] !== 'admin'): ?>
                            <form method="POST" onsubmit="return confirm('Видалити користувача?')">
                                <input type="hidden" name="user_id" value="<?= $user['idUsers'] ?>">
                                <button name="delete_user" class="btn btn-danger btn-sm">Видалити</button>
                            </form>
                            <?php else: ?>
                                <span class="text-muted small">захищено</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>