<?php 
session_start();
require_once 'db.php'; 

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? 0;
$time_filter = $_GET['time'] ?? 0;
$ingredient_filter = $_GET['ingredient'] ?? '';

$categories = $pdo->query("SELECT * FROM Categories")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT r.*, c.name AS category, u.Username AS author
        FROM Recipes r 
        JOIN Categories c ON r.category_id = c.category_id
        JOIN Users u ON r.user_id = u.idUsers
        WHERE r.status = 'published'";

$params = [];

if (!empty($search)) {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $sql .= " AND r.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($time_filter)) {
    $sql .= " AND r.cooking_time <= ?";
    $params[] = $time_filter;
}

if (!empty($ingredient_filter)) {
    $sql .= " AND r.ingredients LIKE ?";
    $params[] = "%$ingredient_filter%";
}

$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Кулінарний блог</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fff9f0; }
        .navbar { background-color: #c0392b !important; }
        .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .card:hover { transform: translateY(-3px); }
        .badge-category { background-color: #e67e22; }
        .search-section { background-color: #fff; padding: 20px 0; border-bottom: 1px solid #eee; }
        .filter-btn { border-radius: 20px; margin: 3px; }
        .filter-btn.active { background-color: #c0392b; border-color: #c0392b; color: white; }
        .card-img-top { height: 200px; object-fit: cover; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🍽️ Кулінарний блог</a>
        <div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <span class="text-white me-3">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="profile.php" class="btn btn-outline-light me-2">Кабінет</a>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a href="add_recipe.php" class="btn btn-warning me-2">+ Додати рецепт</a>
                    <a href="admin.php" class="btn btn-outline-light me-2">Адмін</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline-light">Вийти</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-light me-2">Увійти</a>
                <a href="register.php" class="btn btn-warning">Реєстрація</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="search-section">
    <div class="container">
        <form method="GET" class="d-flex gap-2 mb-3">
            <input type="text" name="search" class="form-control" 
                   placeholder="🔍 Пошук рецептів..." 
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-danger px-4">Знайти</button>
            <?php if($search || $category_filter || $time_filter || $ingredient_filter): ?>
                <a href="index.php" class="btn btn-outline-secondary">✕ Скинути</a>
            <?php endif; ?>
        </form>

        <!-- Фільтр за категорією -->
        <div class="mb-2">
            <span class="me-2 text-muted small fw-bold">Категорія:</span>
            <a href="?<?= $search ? 'search='.urlencode($search).'&' : '' ?><?= $time_filter ? 'time='.$time_filter : '' ?>" 
               class="btn btn-sm filter-btn <?= !$category_filter ? 'active' : 'btn-outline-secondary' ?>">Всі</a>
            <?php foreach($categories as $cat): ?>
                <a href="?category=<?= $cat['category_id'] ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $time_filter ? '&time='.$time_filter : '' ?>" 
                   class="btn btn-sm filter-btn <?= $category_filter == $cat['category_id'] ? 'active' : 'btn-outline-secondary' ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Фільтр за часом -->
        <div class="mb-2">
            <span class="me-2 text-muted small fw-bold">⏱️ Час:</span>
            <a href="?<?= $category_filter ? 'category='.$category_filter.'&' : '' ?><?= $search ? 'search='.urlencode($search) : '' ?>" 
               class="btn btn-sm filter-btn <?= !$time_filter ? 'active' : 'btn-outline-secondary' ?>">Будь-який</a>
            <a href="?<?= $category_filter ? 'category='.$category_filter.'&' : '' ?><?= $search ? 'search='.urlencode($search).'&' : '' ?>time=30" 
               class="btn btn-sm filter-btn <?= $time_filter == 30 ? 'active' : 'btn-outline-secondary' ?>">до 30 хв</a>
            <a href="?<?= $category_filter ? 'category='.$category_filter.'&' : '' ?><?= $search ? 'search='.urlencode($search).'&' : '' ?>time=60" 
               class="btn btn-sm filter-btn <?= $time_filter == 60 ? 'active' : 'btn-outline-secondary' ?>">до 60 хв</a>
            <a href="?<?= $category_filter ? 'category='.$category_filter.'&' : '' ?><?= $search ? 'search='.urlencode($search).'&' : '' ?>time=120" 
               class="btn btn-sm filter-btn <?= $time_filter == 120 ? 'active' : 'btn-outline-secondary' ?>">до 2 год</a>
        </div>

        <!-- Фільтр за інгредієнтами -->
        <div>
            <span class="me-2 text-muted small fw-bold">🥕 Інгредієнт:</span>
            <a href="?<?= $category_filter ? 'category='.$category_filter.'&' : '' ?><?= $time_filter ? 'time='.$time_filter.'&' : '' ?><?= $search ? 'search='.urlencode($search) : '' ?>" 
               class="btn btn-sm filter-btn <?= !$ingredient_filter ? 'active' : 'btn-outline-secondary' ?>">Всі</a>
            <?php 
            $popular_ingredients = ['курка', 'яйця', 'картопля', 'морква', 'цибуля', 'шоколад', 'борошно', 'буряк'];
            foreach($popular_ingredients as $ing): ?>
                <a href="?<?= $category_filter ? 'category='.$category_filter.'&' : '' ?><?= $time_filter ? 'time='.$time_filter.'&' : '' ?><?= $search ? 'search='.urlencode($search).'&' : '' ?>ingredient=<?= urlencode($ing) ?>" 
                   class="btn btn-sm filter-btn <?= $ingredient_filter == $ing ? 'active' : 'btn-outline-secondary' ?>">
                    <?= $ing ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container mt-4">
    <?php if($search || $ingredient_filter): ?>
        <p class="text-muted">
            <?php if($search): ?>Пошук: <strong>"<?= htmlspecialchars($search) ?>"</strong><?php endif; ?>
            <?php if($ingredient_filter): ?> | Інгредієнт: <strong><?= htmlspecialchars($ingredient_filter) ?></strong><?php endif; ?>
        </p>
    <?php endif; ?>
    
    <div class="row">
        <?php if(empty($recipes)): ?>
            <div class="col-12 text-center py-5">
                <h4 class="text-muted">😔 Рецептів не знайдено</h4>
                <a href="index.php" class="btn btn-danger mt-3">Показати всі рецепти</a>
            </div>
        <?php else: ?>
            <?php foreach($recipes as $recipe): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if($recipe['image_url']): ?>
                        <img src="<?= htmlspecialchars($recipe['image_url']) ?>" 
                             class="card-img-top" alt="<?= htmlspecialchars($recipe['title']) ?>">
                    <?php else: ?>
                        <div style="height:200px; background:#f0e6d3; display:flex; align-items:center; justify-content:center;">
                            <span style="font-size:50px;">🍽️</span>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="badge badge-category text-white mb-2">
                            <?= htmlspecialchars($recipe['category']) ?>
                        </span>
                        <h5 class="card-title mt-1">
                            <?= htmlspecialchars($recipe['title']) ?>
                        </h5>
                        <p class="card-text text-muted">
                            <?= htmlspecialchars($recipe['description']) ?>
                        </p>
                        <p class="mb-1">⏱️ <?= $recipe['cooking_time'] ?> хв</p>
                        <?php if($recipe['servings']): ?>
                            <p class="mb-1">🍽️ <?= $recipe['servings'] ?> порцій</p>
                        <?php endif; ?>
                        <p class="text-muted small">👨‍🍳 <?= htmlspecialchars($recipe['author']) ?></p>
                        <a href="recipe.php?id=<?= $recipe['recipe_id'] ?>" 
                           class="btn btn-outline-danger btn-sm">
                            Читати рецепт →
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>