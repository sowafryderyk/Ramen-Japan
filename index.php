<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

$page = $_GET['page'] ?? 'menu';
$allowedPages = ['login', 'register', 'menu', 'orders'];
if (!is_string($page) || !in_array($page, $allowedPages, true)) {
    http_response_code(404);
    $page = 'menu';
}

$errors = [];
$oldEmail = '';
$user = $_SESSION['user'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if (!verify_csrf()) {
            $errors[] = 'Formularz wygasł lub jest nieprawidłowy. Odśwież stronę i spróbuj ponownie.';
        } elseif ($action === 'register') {
            $page = 'register';
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $oldEmail = $email;

            if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
                $errors[] = 'Imię i nazwisko musi mieć od 2 do 80 znaków.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                $errors[] = 'Podaj poprawny adres e-mail (maksymalnie 190 znaków).';
            }
            if (strlen($password) < 10 || strlen($password) > 72) {
                $errors[] = 'Hasło musi mieć od 10 do 72 znaków.';
            }

            if (!$errors) {
                try {
                    $pdo = db();
                    $statement = $pdo->prepare(
                        'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
                    );
                    $statement->execute([
                        'name' => $name,
                        'email' => $email,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ]);
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id' => (int) $pdo->lastInsertId(),
                        'name' => $name,
                        'email' => $email,
                    ];
                    redirect('menu');
                } catch (PDOException $exception) {
                    if ($exception->getCode() === '23000') {
                        $errors[] = 'Konto z tym adresem e-mail już istnieje.';
                    } else {
                        throw $exception;
                    }
                }
            }
        } elseif ($action === 'login') {
            $page = 'login';
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $oldEmail = $email;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || $password === '') {
                $errors[] = 'Podaj poprawny adres e-mail i hasło.';
            } else {
                $statement = db()->prepare(
                    'SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1'
                );
                $statement->execute(['email' => $email]);
                $account = $statement->fetch();

                if (!$account || !password_verify($password, $account['password_hash'])) {
                    $errors[] = 'Nieprawidłowy adres e-mail lub hasło.';
                } else {
                    if (password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT)) {
                        $update = db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                        $update->execute([
                            'hash' => password_hash($password, PASSWORD_DEFAULT),
                            'id' => $account['id'],
                        ]);
                    }
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id' => (int) $account['id'],
                        'name' => $account['name'],
                        'email' => $account['email'],
                    ];
                    redirect('menu');
                }
            }
        } elseif ($action === 'logout') {
            unset($_SESSION['user']);
            session_regenerate_id(true);
            set_flash('Wylogowano z konta.');
            redirect('login');
        } elseif ($action === 'place_order') {
            require_login();

            $name = trim((string) $user['name']);
            $address = trim((string) ($_POST['address'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $itemId = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);

            if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
                $errors[] = 'Podaj imię i nazwisko (od 2 do 80 znaków).';
            }
            if (mb_strlen($address) < 8 || mb_strlen($address) > 255) {
                $errors[] = 'Podaj pełny adres dostawy (od 8 do 255 znaków).';
            }
            if (!preg_match('/^\+?[0-9 ()-]{7,20}$/', $phone)) {
                $errors[] = 'Podaj poprawny numer telefonu.';
            }
            if ($itemId === false || $itemId === null || $quantity === false || $quantity === null || $quantity < 1 || $quantity > 10) {
                $errors[] = 'Wybierz danie i liczbę porcji od 1 do 10.';
            }

            if (!$errors) {
                $pdo = db();
                $statement = $pdo->prepare(
                    'SELECT id, name, price FROM menu_items WHERE id = :id AND available = 1'
                );
                $statement->execute(['id' => $itemId]);
                $item = $statement->fetch();

                if (!$item) {
                    $errors[] = 'Wybrane danie nie jest już dostępne. Odśwież menu.';
                } else {
                    $priceInGrosze = (int) str_replace('.', '', (string) $item['price']);
                    $total = sprintf(
                        '%d.%02d',
                        intdiv($priceInGrosze * $quantity, 100),
                        ($priceInGrosze * $quantity) % 100
                    );

                    $pdo->beginTransaction();
                    try {
                        $order = $pdo->prepare(
                            'INSERT INTO orders (user_id, customer_name, address, phone, total)
                             VALUES (:user_id, :customer_name, :address, :phone, :total)'
                        );
                        $order->execute([
                            'user_id' => $user['id'],
                            'customer_name' => $name,
                            'address' => $address,
                            'phone' => $phone,
                            'total' => $total,
                        ]);
                        $orderItem = $pdo->prepare(
                            'INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, quantity)
                             VALUES (:order_id, :menu_item_id, :item_name, :unit_price, :quantity)'
                        );
                        $orderItem->execute([
                            'order_id' => $pdo->lastInsertId(),
                            'menu_item_id' => $item['id'],
                            'item_name' => $item['name'],
                            'unit_price' => $item['price'],
                            'quantity' => $quantity,
                        ]);
                        $pdo->commit();
                        set_flash('Zamówienie zostało złożone. Możesz sprawdzić jego status w historii.');
                        redirect('orders');
                    } catch (Throwable $exception) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $exception;
                    }
                }
            }
            $page = 'menu';
        } else {
            http_response_code(400);
            $errors[] = 'Nieprawidłowe żądanie.';
        }
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        http_response_code(503);
        $page = 'database_error';
    }
}

$isAuthenticated = isset($_SESSION['user']);
if (in_array($page, ['menu', 'orders'], true)) {
    require_login();
}
$user = $_SESSION['user'] ?? null;
$menuItems = [];
$orders = [];

try {
    if ($isAuthenticated && $page === 'menu') {
        $menuItems = db()->query(
            'SELECT id, name, description, ingredients, price FROM menu_items WHERE available = 1 ORDER BY id'
        )->fetchAll();
    } elseif ($isAuthenticated && $page === 'orders') {
        $statement = db()->prepare(
            'SELECT o.id, o.customer_name, o.address, o.total, o.status, o.created_at,
                    GROUP_CONCAT(CONCAT(oi.item_name, " × ", oi.quantity) ORDER BY oi.id SEPARATOR ", ") AS items
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = :user_id
             GROUP BY o.id
             ORDER BY o.created_at DESC'
        );
        $statement->execute(['user_id' => $user['id']]);
        $orders = $statement->fetchAll();
    }
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(503);
    $page = 'database_error';
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$pageTitles = [
    'login' => 'Miło Cię widzieć',
    'register' => 'Dołącz do nas',
    'menu' => 'Ramen, na który masz ochotę',
    'orders' => 'Twoje zamówienia',
    'database_error' => 'Aplikacja chwilowo niedostępna',
];
$title = $pageTitles[$page] ?? 'Ramen Japan';
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17251f">
    <title><?= e($title) ?> | Ramen Japan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php?page=menu" aria-label="Ramen Japan — strona główna">
        <span class="brand-mark">湯</span>
        <span>RAMEN <em>JAPAN</em></span>
    </a>
    <nav aria-label="Nawigacja główna">
        <?php if ($isAuthenticated): ?>
            <a href="index.php?page=menu">Menu</a>
            <a href="index.php?page=orders">Zamówienia</a>
            <span class="nav-greeting">Cześć, <?= e($user['name']) ?></span>
            <form method="post" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="logout">
                <button class="nav-logout" type="submit">Wyloguj</button>
            </form>
        <?php else: ?>
            <a href="index.php?page=login">Logowanie</a>
            <a class="nav-signup" href="index.php?page=register">Załóż konto</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <?php if ($flash): ?>
        <div class="notice <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="notice error" role="alert">
            <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($page === 'login' || $page === 'register'): ?>
        <section class="auth-layout">
            <div class="auth-art">
                <span class="eyebrow">JAPOŃSKA KUCHNIA · SŁOTWINA</span>
                <h1><?= $page === 'login' ? 'Ciepło, które zaczyna się od miski.' : 'Twoja miska. Twój rytuał.' ?></h1>
                <p>Wybierz swój ulubiony ramen, zamów kilka chwil dla siebie i sprawdź, kiedy zapukamy do drzwi.</p>
                <div class="steam-bowl" aria-hidden="true"><span>ら</span></div>
                <span class="art-caption">mała podróż, wielki smak</span>
            </div>
            <div class="auth-panel">
                <span class="eyebrow"><?= $page === 'login' ? 'WITAJ PONOWNIE' : 'PIERWSZA MISKA JEST BLISKO' ?></span>
                <h2><?= $page === 'login' ? 'Zaloguj się' : 'Załóż konto' ?></h2>
                <p class="form-intro"><?= $page === 'login' ? 'Wróć do swoich ulubionych smaków.' : 'Załóż konto i zamawiaj ramen na Słotwinę.' ?></p>
                <form method="post" class="form-stack">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= e($page) ?>">
                    <?php if ($page === 'register'): ?>
                        <label for="name">Imię i nazwisko</label>
                        <input id="name" name="name" type="text" autocomplete="name" minlength="2" maxlength="80" required value="<?= e((string) ($_POST['name'] ?? '')) ?>">
                    <?php endif; ?>
                    <label for="email">Adres e-mail</label>
                    <input id="email" name="email" type="email" autocomplete="email" maxlength="190" required value="<?= e($oldEmail) ?>">
                    <label for="password">Hasło</label>
                    <input id="password" name="password" type="password" autocomplete="<?= $page === 'login' ? 'current-password' : 'new-password' ?>" minlength="<?= $page === 'register' ? '10' : '1' ?>" maxlength="72" required>
                    <?php if ($page === 'register'): ?>
                        <span class="field-hint">Co najmniej 10 znaków.</span>
                    <?php endif; ?>
                    <button class="button button-dark" type="submit"><?= $page === 'login' ? 'Zaloguj się' : 'Utwórz konto' ?><span aria-hidden="true">→</span></button>
                </form>
                <p class="switch-auth">
                    <?= $page === 'login' ? 'Nie masz jeszcze konta?' : 'Masz już konto?' ?>
                    <a href="index.php?page=<?= $page === 'login' ? 'register' : 'login' ?>"><?= $page === 'login' ? 'Zarejestruj się' : 'Zaloguj się' ?></a>
                </p>
            </div>
        </section>
    <?php elseif ($page === 'menu'): ?>
        <section class="hero">
            <div class="hero-copy">
                <span class="eyebrow">ŚWIEŻO GOTOWANE · DOSTAWA NA SŁOTWINĘ</span>
                <h1>Znajdź swoją<br><span>miseczkę szczęścia.</span></h1>
                <p>Bulion gotowany z cierpliwością. Makaron, który ma sprężystość. Smaki, do których chce się wracać.</p>
                <a class="button button-dark" href="#menu">Poznaj nasze menu <span aria-hidden="true">↓</span></a>
            </div>
            <div class="hero-art" role="img" aria-label="Ilustracja parującej miski ramenu">
                <span class="hero-stamp">一杯<br>一旅</span>
                <div class="hero-bowl"><span class="bowl-egg">◉</span><span class="bowl-noodles">〰 〰<br>〰 〰</span></div>
                <span class="hero-note">dobre rzeczy<br>wymagają czasu</span>
            </div>
        </section>
        <section id="menu" class="menu-section">
            <div class="section-heading">
                <div><span class="eyebrow">Z NASZEJ KUCHNI</span><h2>Wybierz swoją miskę</h2></div>
                <span class="delivery-note"><span class="dot"></span> Dostarczamy na Słotwinę</span>
            </div>
            <?php if (!$menuItems): ?>
                <p class="empty-state">Menu jest chwilowo niedostępne.</p>
            <?php else: ?>
                <div class="menu-grid">
                    <?php foreach ($menuItems as $index => $item): ?>
                        <article class="dish-card">
                            <div class="dish-illustration dish-<?= $index % 4 ?>" aria-hidden="true"><span><?= ['麺', '味', '辛', '豚'][$index % 4] ?></span></div>
                            <div class="dish-content">
                                <div class="dish-title"><h3><?= e($item['name']) ?></h3><span><?= number_format((float) $item['price'], 2, ',', ' ') ?> zł</span></div>
                                <p><?= e($item['description']) ?></p>
                                <div class="ingredients"><?= e($item['ingredients']) ?></div>
                                <form method="post" class="order-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="place_order">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <label class="sr-only" for="quantity-<?= (int) $item['id'] ?>">Liczba porcji — <?= e($item['name']) ?></label>
                                    <input id="quantity-<?= (int) $item['id'] ?>" type="number" name="quantity" min="1" max="10" value="1" required>
                                    <label class="sr-only" for="address-<?= (int) $item['id'] ?>">Adres dostawy — <?= e($item['name']) ?></label>
                                    <input id="address-<?= (int) $item['id'] ?>" type="text" name="address" placeholder="Adres dostawy" minlength="8" maxlength="255" required>
                                    <label class="sr-only" for="phone-<?= (int) $item['id'] ?>">Telefon — <?= e($item['name']) ?></label>
                                    <input id="phone-<?= (int) $item['id'] ?>" type="tel" name="phone" placeholder="Telefon" pattern="[+]?[0-9]{7,15}" required>
                                    <button class="button button-small" type="submit">Zamów <span aria-hidden="true">→</span></button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php elseif ($page === 'orders'): ?>
        <section class="orders-section">
            <div class="section-heading">
                <div><span class="eyebrow">TWOJA HISTORIA</span><h1>Smaki, do których wracasz</h1></div>
                <a class="text-link" href="index.php?page=menu">← Wróć do menu</a>
            </div>
            <?php if (!$orders): ?>
                <div class="empty-state"><p>Nie masz jeszcze żadnego zamówienia.</p><a class="button button-dark" href="index.php?page=menu">Wybierz pierwszą miskę</a></div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <article class="order-card">
                            <div class="order-date"><span class="eyebrow">ZAMÓWIENIE #<?= (int) $order['id'] ?></span><time><?= e(date('d.m.Y · H:i', strtotime($order['created_at']))) ?></time></div>
                            <div class="order-details"><strong><?= e($order['items'] ?? 'Ramen') ?></strong><span><?= e($order['address']) ?></span></div>
                            <div class="order-summary"><span class="status"><?= e($order['status']) ?></span><strong><?= number_format((float) $order['total'], 2, ',', ' ') ?> zł</strong></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="database-error">
            <span class="eyebrow">JESZCZE CHWILKA</span>
            <h1>Trwają przygotowania do otwarcia.</h1>
            <p>Nie możemy teraz połączyć się z bazą. Upewnij się, że MySQL działa i baza <code>ramen_japan</code> została utworzona.</p>
        </section>
    <?php endif; ?>
</main>
<footer><a class="brand" href="index.php?page=menu"><span class="brand-mark">湯</span><span>RAMEN <em>JAPAN</em></span></a><span>Rozgrzewamy Słotwinę, jedna miska na raz.</span></footer>
</body>
</html>
