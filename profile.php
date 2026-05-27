<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 3;
    $_SESSION['username'] = 'Діана';
    $_SESSION['role'] = 'user';
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['favorite']) && isset($_POST['recipe_id'])) {
    $rid = $_POST['recipe_id'];
    $check = $pdo->prepare("SELECT * FROM Favorites WHERE recipe_id = ? AND user_id = ?");
    $check->execute([$rid, $user_id]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO Favorites (recipe_id, user_id) VALUES (?, ?)")
            ->execute([$rid, $user_id]);
    } else {
        $pdo->prepare("DELETE FROM Favorites WHERE recipe_id = ? AND user_id = ?")
            ->execute([$rid, $user_id]);
    }
    header('Location: profile.php');
    exit;
}

$user = $pdo->prepare("SELECT * FROM Users WHERE idUsers = ?");
$user->execute([$user_id]);
$user = $user->fetch(PDO::FETCH_ASSOC);

$favorites = $pdo->prepare("
    SELECT r.*, c.name AS category 
    FROM Favorites f
    JOIN Recipes r ON f.recipe_id = r.recipe_id
    JOIN Categories c ON r.category_id = c.category_id
    WHERE f.user_id = ?
    ORDER BY f.added_at DESC
");
$favorites->execute([$user_id]);
$favorites = $favorites->fetchAll(PDO::FETCH_ASSOC);

$ratings = $pdo->prepare("
    SELECT rt.value, rt.created_at, r.title, r.recipe_id
    FROM Ratings rt
    JOIN Recipes r ON rt.recipe_id = r.recipe_id
    WHERE rt.user_id = ?
    ORDER BY rt.created_at DESC
");
$ratings->execute([$user_id]);
$ratings = $ratings->fetchAll(PDO::FETCH_ASSOC);

$comments = $pdo->prepare("
    SELECT cm.*, r.title AS recipe_title
    FROM Comments cm
    JOIN Recipes r ON cm.recipe_id = r.recipe_id
    WHERE cm.user_id = ?
    ORDER BY cm.created_at DESC
    LIMIT 10
");
$comments->execute([$user_id]);
$comments = $comments->fetchAll(PDO::FETCH_ASSOC);

// Історія активності - об'єднуємо коментарі, оцінки та обрані
$activity = [];

foreach($comments as $c) {
    $activity[] = [
        'type' => 'comment',
        'text' => 'Залишив(ла) коментар до рецепту "' . $c['recipe_title'] . '"',
        'date' => $c['created_at'],
        'recipe_id' => $c['recipe_id'],
        'icon' => '💬'
    ];
}

foreach($ratings as $r) {
    $activity[] = [
        'type' => 'rating',
        'text' => 'Оцінив(ла) рецепт "' . $r['title'] . '" на ' . $r['value'] . ' зірок',
        'date' => $r['created_at'],
        'recipe_id' => $r['recipe_id'],
        'icon' => '⭐'
    ];
}

// Сортуємо по даті
usort($activity, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$activity = array_slice($activity, 0, 10);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Особистий кабінет</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fff9f0; }
        .navbar { background-color: #c0392b !important; }
        .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .profile-header { background-color: #c0392b; color: white; padding: 40px 0; }
        .activity-item { border-left: 3px solid #c0392b; padding-left: 15px; margin-bottom: 15px; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🍽️ Кулінарний блог</a>
        <div>
            <a href="profile.php" class="btn btn-outline-light me-2">Кабінет</a>
            <a href="logout.php" class="btn btn-outline-light">Вийти</a>
        </div>
    </div>
</nav>

<div class="profile-header">
    <div class="container">
        <h2>👤 <?= htmlspecialchars($user['Username']) ?></h2>
        <p class="mb-0">📧 <?= htmlspecialchars($user['email']) ?></p>
        <p class="mb-0">🔑 Роль: <?= $user['role'] == 'admin' ? 'Адміністратор' : 'Користувач' ?></p>
        <p class="mb-0">📅 Зареєстрований: <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
        <div class="mt-2">
            <span class="badge bg-light text-dark me-2">⭐ Оцінок: <?= count($ratings) ?></span>
            <span class="badge bg-light text-dark me-2">💬 Коментарів: <?= count($comments) ?></span>
            <span class="badge bg-light text-dark">❤️ Обраних: <?= count($favorites) ?></span>
        </div>
    </div>
</div>

<div class="container mt-4">

    <!-- Обрані рецепти -->
    <h4 class="mb-3">❤️ Обрані рецепти</h4>
    <?php if(empty($favorites)): ?>
        <div class="alert alert-light mb-4">
            Ви ще не додали жодного рецепту до обраних.
            <a href="index.php">Переглянути рецепти →</a>
        </div>
    <?php else: ?>
        <div class="row mb-4">
            <?php foreach($favorites as $fav): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <?php if($fav['image_url']): ?>
                        <img src="<?= htmlspecialchars($fav['image_url']) ?>" 
                             class="card-img-top" style="height:150px; object-fit:cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="badge bg-warning text-dark mb-2">
                            <?= htmlspecialchars($fav['category']) ?>
                        </span>
                        <h6 class="card-title"><?= htmlspecialchars($fav['title']) ?></h6>
                        <p class="text-muted small">⏱️ <?= $fav['cooking_time'] ?> хв</p>
                        <a href="recipe.php?id=<?= $fav['recipe_id'] ?>" 
                           class="btn btn-outline-danger btn-sm">Переглянути</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row">

        <!-- Мої оцінки -->
        <div class="col-md-4">
            <h4 class="mb-3">🌟 Мої оцінки</h4>
            <?php if(empty($ratings)): ?>
                <div class="alert alert-light">Ви ще не оцінювали рецепти.</div>
            <?php else: ?>
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Страва</th>
                        <th>Оцінка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ratings as $r): ?>
                    <tr>
                        <td>
                            <a href="recipe.php?id=<?= $r['recipe_id'] ?>">
                                <?= htmlspecialchars($r['title']) ?>
                            </a>
                        </td>
                        <td>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <span style="color: <?= $i <= $r['value'] ? '#f39c12' : '#ddd' ?>; font-size:16px;">★</span>
                            <?php endfor; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Мої коментарі -->
        <div class="col-md-4">
            <h4 class="mb-3">💬 Мої коментарі</h4>
            <?php if(empty($comments)): ?>
                <div class="alert alert-light">Ви ще не залишали коментарів.</div>
            <?php else: ?>
                <?php foreach($comments as $comment): ?>
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <p class="mb-1 small fw-bold">
                            <a href="recipe.php?id=<?= $comment['recipe_id'] ?>">
                                <?= htmlspecialchars($comment['recipe_title']) ?>
                            </a>
                        </p>
                        <p class="mb-0 small text-muted">
                            <?= htmlspecialchars(mb_substr($comment['body'], 0, 60)) ?>...
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Історія активності -->
        <div class="col-md-4">
            <h4 class="mb-3">📋 Історія активності</h4>
            <?php if(empty($activity)): ?>
                <div class="alert alert-light">Активності ще немає.</div>
            <?php else: ?>
                <?php foreach($activity as $act): ?>
                <div class="activity-item">
                    <p class="mb-0">
                        <span class="me-1"><?= $act['icon'] ?></span>
                        <a href="recipe.php?id=<?= $act['recipe_id'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars($act['text']) ?>
                        </a>
                    </p>
                    <small class="text-muted">
                        <?= date('d.m.Y H:i', strtotime($act['date'])) ?>
                    </small>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>
</body>
</html>