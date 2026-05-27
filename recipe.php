<?php
session_start();
require_once 'db.php';

$id = $_GET['id'] ?? 0;

if (isset($_POST['rating']) && isset($_SESSION['user_id'])) {
    $value = (int)$_POST['rating'];
    if ($value >= 1 && $value <= 5) {
        $check = $pdo->prepare("SELECT * FROM Ratings WHERE recipe_id = ? AND user_id = ?");
        $check->execute([$id, $_SESSION['user_id']]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO Ratings (recipe_id, user_id, value) VALUES (?, ?, ?)")
                ->execute([$id, $_SESSION['user_id'], $value]);
        } else {
            $pdo->prepare("UPDATE Ratings SET value = ? WHERE recipe_id = ? AND user_id = ?")
                ->execute([$value, $id, $_SESSION['user_id']]);
        }
    }
    header("Location: recipe.php?id=$id");
    exit;
}

if (isset($_POST['comment']) && isset($_SESSION['user_id'])) {
    $body = trim($_POST['body']);
    if (!empty($body)) {
        $pdo->prepare("INSERT INTO Comments (recipe_id, user_id, body, created_at) VALUES (?, ?, ?, NOW())")
            ->execute([$id, $_SESSION['user_id'], $body]);
    }
    header("Location: recipe.php?id=$id");
    exit;
}

if (isset($_POST['favorite']) && isset($_SESSION['user_id'])) {
    $check = $pdo->prepare("SELECT * FROM Favorites WHERE recipe_id = ? AND user_id = ?");
    $check->execute([$id, $_SESSION['user_id']]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO Favorites (recipe_id, user_id) VALUES (?, ?)")
            ->execute([$id, $_SESSION['user_id']]);
    } else {
        $pdo->prepare("DELETE FROM Favorites WHERE recipe_id = ? AND user_id = ?")
            ->execute([$id, $_SESSION['user_id']]);
    }
    header("Location: recipe.php?id=$id");
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.*, c.name AS category, u.Username AS author
    FROM Recipes r
    JOIN Categories c ON r.category_id = c.category_id
    JOIN Users u ON r.user_id = u.idUsers
    WHERE r.recipe_id = ?
");
$stmt->execute([$id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) { header('Location: index.php'); exit; }

$ratingStmt = $pdo->prepare("SELECT AVG(value) as avg_rating, COUNT(*) as count FROM Ratings WHERE recipe_id = ?");
$ratingStmt->execute([$id]);
$rating = $ratingStmt->fetch(PDO::FETCH_ASSOC);

$userRating = 0;
if (isset($_SESSION['user_id'])) {
    $urStmt = $pdo->prepare("SELECT value FROM Ratings WHERE recipe_id = ? AND user_id = ?");
    $urStmt->execute([$id, $_SESSION['user_id']]);
    $ur = $urStmt->fetch(PDO::FETCH_ASSOC);
    $userRating = $ur ? $ur['value'] : 0;
}

$isFavorite = false;
if (isset($_SESSION['user_id'])) {
    $favCheck = $pdo->prepare("SELECT * FROM Favorites WHERE recipe_id = ? AND user_id = ?");
    $favCheck->execute([$id, $_SESSION['user_id']]);
    $isFavorite = $favCheck->fetch() ? true : false;
}

$comments = $pdo->prepare("
    SELECT cm.*, u.Username 
    FROM Comments cm 
    JOIN Users u ON cm.user_id = u.idUsers
    WHERE cm.recipe_id = ?
    ORDER BY cm.created_at DESC
");
$comments->execute([$id]);

$ingredients = json_decode($recipe['ingredients'], true);
$steps = json_decode($recipe['steps'], true);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($recipe['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fff9f0; }
        .navbar { background-color: #c0392b !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🍽️ Кулінарний блог</a>
        <div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn btn-outline-light me-2">Кабінет</a>
                <a href="logout.php" class="btn btn-outline-light">Вийти</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-light me-2">Увійти</a>
                <a href="register.php" class="btn btn-warning">Реєстрація</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <span class="badge bg-warning text-dark mb-2"><?= htmlspecialchars($recipe['category']) ?></span>
            <h1><?= htmlspecialchars($recipe['title']) ?></h1>
            <p class="text-muted">
                👨‍🍳 <?= htmlspecialchars($recipe['author']) ?> | 
                ⏱️ <?= $recipe['cooking_time'] ?> хв |
                🍽️ <?= $recipe['servings'] ?? 4 ?> порцій
            </p>
            <p><?= htmlspecialchars($recipe['description']) ?></p>

            <!-- Кнопка обране -->
            <?php if(isset($_SESSION['user_id'])): ?>
            <form method="POST" class="mb-3">
                <button name="favorite" class="btn <?= $isFavorite ? 'btn-warning' : 'btn-outline-warning' ?>">
                    <?= $isFavorite ? '★ В обраних' : '☆ Додати до обраних' ?>
                </button>
            </form>
            <?php endif; ?>

            <!-- Рейтинг -->
            <div class="mb-4">
                <h5>Оцінка: 
                    <?php 
                    $avg = round($rating['avg_rating'] ?? 0, 1);
                    for($i = 1; $i <= 5; $i++): ?>
                        <span style="color: <?= $i <= $avg ? '#f39c12' : '#ddd' ?>; font-size:22px;">★</span>
                    <?php endfor; ?>
                    <small class="text-muted">(<?= $avg ?>/5, <?= $rating['count'] ?> оцінок)</small>
                </h5>
                <?php if(isset($_SESSION['user_id'])): ?>
                <form method="POST" class="mt-2">
                    <p class="mb-1">Ваша оцінка:</p>
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <button type="submit" name="rating" value="<?= $i ?>" 
                                class="btn btn-sm <?= $userRating == $i ? 'btn-warning' : 'btn-outline-warning' ?> me-1">
                            <?= $i ?>★
                        </button>
                    <?php endfor; ?>
                </form>
                <?php else: ?>
                    <p class="text-muted small"><a href="login.php">Увійдіть</a> щоб оцінити рецепт</p>
                <?php endif; ?>
            </div>

            <h4>Інгредієнти:</h4>
            <ul>
                <?php foreach($ingredients as $ing): ?>
                    <li><?= htmlspecialchars($ing) ?></li>
                <?php endforeach; ?>
            </ul>

            <h4>Приготування:</h4>
            <ol>
                <?php foreach($steps as $step): ?>
                    <li class="mb-2"><?= htmlspecialchars($step) ?></li>
                <?php endforeach; ?>
            </ol>

            <h4 class="mt-4">Коментарі:</h4>
            <?php if(isset($_SESSION['user_id'])): ?>
            <form method="POST" class="mb-3">
                <textarea name="body" class="form-control mb-2" rows="3" placeholder="Ваш коментар..."></textarea>
                <button name="comment" class="btn btn-danger btn-sm">Додати коментар</button>
            </form>
            <?php else: ?>
                <p><a href="login.php">Увійдіть</a>, щоб залишити коментар</p>
            <?php endif; ?>

            <?php while($comment = $comments->fetch(PDO::FETCH_ASSOC)): ?>
            <div class="card mb-2">
                <div class="card-body py-2">
                    <strong><?= htmlspecialchars($comment['Username']) ?></strong>
                    <span class="text-muted small ms-2"><?= $comment['created_at'] ?></span>
                    <p class="mb-0 mt-1"><?= htmlspecialchars($comment['body']) ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
</body>
</html>