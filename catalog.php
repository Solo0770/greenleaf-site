<?php
$host = 'localhost';
$db = 'greenleaf';
$user = 'root';
$pass = '';
$charset = 'utf8';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Помилка зʼєднання з БД: " . $e->getMessage());
}

$maxPriceQuery = $pdo->query("SELECT MAX(price) AS max_price FROM products");
$maxPrice = $maxPriceQuery->fetch()['max_price'] ?? 10000;

$where = [];
$params = [];

$category = $_GET['category'] ?? '';
$min_price = is_numeric($_GET['min_price'] ?? '') ? floatval($_GET['min_price']) : 0;
$max_price = is_numeric($_GET['max_price'] ?? '') ? floatval($_GET['max_price']) : $maxPrice;

if ($min_price < 0) {
    $min_price = 0;
}
if ($max_price < 0) {
    $max_price = 0;
}
if ($min_price > $maxPrice) {
    $min_price = $maxPrice;
}
if ($max_price > $maxPrice) {
    $max_price = $maxPrice;
}

if (!empty($category)) {
    $where[] = 'category = :category';
    $params[':category'] = $category;
}

$where[] = 'price >= :min_price';
$where[] = 'price <= :max_price';
$params[':min_price'] = $min_price;
$params[':max_price'] = $max_price;

$sql = 'SELECT * FROM products';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="uk">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Каталог - GreenLeaf</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/cart.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/vue@3"></script>
  <script defer src="js/cart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const maxPrice = <?= $maxPrice ?>;
      const minInput = document.querySelector('input[name="min_price"]');
      const maxInput = document.querySelector('input[name="max_price"]');

      [minInput, maxInput].forEach(input => {
        input.addEventListener('input', () => {
          const value = parseFloat(input.value);
          if (value > maxPrice) {
            input.value = maxPrice;
          }
          if (value < 0) {
            input.value = 0;
          }
        });
      });
    });
  </script>
  <style>

    .filter-form {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 1rem;
      margin: 2rem auto;
      padding: 1rem 2rem;
      border-radius: 12px;
      background: transparent;
      box-shadow: none;
      border: none;
    }

    .filter-form select,
    .filter-form input[type=number],
    .filter-form button {
      padding: 0.8rem 1rem;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 10px;
      outline: none;
      transition: all 0.3s ease;
      background: #fff;
      font-family: inherit;
    }

    .filter-form select:focus,
    .filter-form input[type=number]:focus {
      border-color: #4caf50;
      box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.15);
    }

    .filter-form button {
      background: linear-gradient(135deg, #4caf50, #45a049);
      color: white;
      border: none;
      cursor: pointer;
      font-weight: 600;
      padding: 0.8rem 1.4rem;
    }

    .filter-form button:hover {
      background: linear-gradient(135deg, #3d8b40, #379c3f);
    }

    .catalog-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 2rem;
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
      justify-content: start;
    }

    .product-item {
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      overflow: hidden;
      transition: box-shadow 0.3s ease, transform 0.3s ease;
      display: flex;
      flex-direction: column;
      height: 100%;
      width: 100%;
    }

.catalog {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  padding: 3rem 0;
  text-align: center;
}


    .catalog h1 {
      font-size: 2.5rem;
      margin-bottom: 2rem;
    }

    @media (min-width: 900px) {
      .catalog-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }
  </style>
</head>

<body>
  <div id="app">
    <header>
      <nav class="navbar">
        <a href="index.php" class="logo">GreenLeaf</a>
        <ul class="nav-links">
          <li><a href="index.php">Головна</a></li>
          <li><a href="catalog.php">Каталог</a></li>
          <li><a href="about.php">Про нас</a></li>
        </ul>
      </nav>
    </header>
    <div class="site-wrapper" :class="{ blurred: showCart && !showCheckout || showCheckout }">
      <main>
        <section class="catalog">
          <h1>Каталог рослин</h1>
          <form method="get" class="filter-form">
            <select name="category">
              <option value="">Усі категорії</option>
              <option value="хвойні" <?=($_GET['category'] ?? '') === 'хвойні' ? 'selected' : ''?>>Хвойні</option>
              <option value="декоративно-листяні" <?=($_GET['category'] ?? '') === 'декоративно-листяні' ? 'selected' : ''?>>Декоративно-листяні</option>
              <option value="дерева" <?=($_GET['category'] ?? '') === 'дерева' ? 'selected' : ''?>>Дерева</option>
            </select>
            <input type="number" name="min_price" placeholder="Ціна від" min="0" value="<?=htmlspecialchars($_GET['min_price'] ?? 0)?>">
            <input type="number" name="max_price" placeholder="Ціна до" max="<?= $maxPrice ?>" value="<?=htmlspecialchars($_GET['max_price'] ?? $maxPrice)?>">
            <button type="submit">Застосувати</button>
          </form>
          <?php if (count($products) === 0): ?>
            <p class="no-products">Нічого не знайдено за вибраними фільтрами.</p>
          <?php else: ?>
          <div class="grid catalog-grid">
            <?php foreach ($products as $p): ?>
              <div class="product-item" id="product<?= $p['id'] ?>">
                <img src="<?=htmlspecialchars($p['image'])?>" alt="<?=htmlspecialchars($p['name'])?>">
                <input type="checkbox" id="toggle-overlay<?= $p['id'] ?>" class="toggle-overlay">
                <label for="toggle-overlay<?= $p['id'] ?>" class="overlay-btn">Деталі</label>
                <div class="overlay">
                  <h3>Детальна інформація</h3>
                  <p><?=nl2br(htmlspecialchars($p['description']))?></p>
                </div>
                <div class="product-info">
                  <h2><?=htmlspecialchars($p['name'])?></h2>
                  <p><?=htmlspecialchars($p['short_description'])?></p>
                  <span class="price">₴<?=number_format($p['price'], 0, '.', ' ')?></span>
                  <button :class="isInCart('product<?= $p['id'] ?>') ? 'btn-in-cart' : 'btn-add-cart'" @click="toggleCart({ id: 'product<?= $p['id'] ?>', name: '<?=htmlspecialchars($p['name'])?>', price: <?= $p['price'] ?>, image: '<?=htmlspecialchars($p['image'])?>' })">
                    <span v-if="isInCart('product<?= $p['id'] ?>')">✅</span>
                    <span v-else>👜</span>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </section>
      </main>
      <footer>
        <p>© 2025 GreenLeaf. Всі права захищені.</p>
      </footer>
      <button class="cart-modern" @click="showCart = true">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M6 6h15l-1.5 9h-13z" />
          <circle cx="9" cy="21" r="1" />
          <circle cx="18" cy="21" r="1" />
        </svg>
        <span>Кошик ({{ totalQuantity }})</span>
      </button>
    </div>
    <div class="cart-overlay" v-if="showCart && !showCheckout" @click.self="showCart = false">
      <div class="cart-window">
        <div class="cart-header">
          <h2>Кошик</h2>
          <button class="close-btn" @click="showCart = false">✖</button>
        </div>
        <div class="cart-content" v-if="cart.length > 0">
          <div class="cart-item" v-for="(item, index) in cart" :key="item.id">
            <div class="item-image">
              <img :src="item.image || 'images/placeholder.jpg'" alt="Товар">
            </div>
            <div class="item-details">
              <p class="item-name">{{ item.name }}</p>
              <div class="quantity-control">
                <button @click="decreaseQuantity(index)">−</button>
                <span>{{ item.quantity }}</span>
                <button @click="increaseQuantity(index)">+</button>
              </div>
            </div>
            <div class="item-price">₴{{ item.price * item.quantity }}</div>
            <button class="remove-item" @click="removeItem(index)">🗑</button>
          </div>
        </div>
        <div v-else class="cart-empty">Кошик порожній</div>
        <div class="cart-footer" v-if="cart.length > 0">
          <div class="total">
            <span>Разом:</span>
            <strong>₴{{ totalPrice }}</strong>
          </div>
          <div class="cart-buttons">
            <button class="btn-checkout" @click="showCheckout = true">Оформити замовлення</button>
          </div>
        </div>
      </div>
    </div>
    <div class="checkout-modal" v-if="showCheckout" @click.self="closeCheckout">
      <div class="checkout-content">
        <div class="checkout-left">
          <h2>Оформлення замовлення</h2>
          <div v-if="step === 1" class="step">
            <h3>1. Ваші дані</h3>
            <input type="text" placeholder="Прізвище, ім’я, по батькові" v-model="user.name" required>
            <div class="phone-wrapper">
              <span class="prefix">+380</span>
              <input type="tel" id="phoneInput" v-model="user.phone" placeholder="___ ___ ___" pattern="[0-9]{9}" maxlength="9" required>
            </div>
            <button @click="nextStep">Далі</button>
          </div>
          <div v-if="step === 2" class="step">
            <h3>2. Доставка</h3>
            <select v-model="delivery.method" required>
              <option disabled value="">Оберіть спосіб доставки</option>
              <option value="nova">Нова Пошта</option>
              <option value="ukr">Укрпошта</option>
              <option value="pickup">Самовивіз</option>
            </select>
            <input v-if="delivery.method !== 'pickup'" type="text" placeholder="Адреса доставки" v-model="delivery.address">
            <div class="step-buttons">
              <button @click="prevStep">Назад</button>
              <button @click="nextStep">Далі</button>
            </div>
          </div>
          <div v-if="step === 3" class="step">
            <h3>3. Оплата</h3>
            <div class="payment-options">
              <label class="payment-block">
                <input type="radio" v-model="payment" value="card" />
                <span>💳 Карткою</span>
              </label>
              <label class="payment-block">
                <input type="radio" v-model="payment" value="cash" />
                <span>💵 Готівкою при отриманні</span>
              </label>
            </div>
            <div class="step-buttons">
              <button @click="prevStep">Назад</button>
              <button @click="submitOrder">Оформити замовлення</button>
            </div>
          </div>
          <div v-if="step === 4" class="step">
            <h3>✅ Дякуємо за замовлення!</h3>
            <p>Очікуйте дзвінка нашого менеджера.</p>
            <button @click="closeCheckout">Закрити</button>
          </div>
        </div>
        <div class="checkout-right">
          <h3>Склад замовлення</h3>
          <ul>
            <li v-for="item in cart" :key="item.id">
              <img :src="item.image || 'images/placeholder.jpg'" alt="" />
              <span>{{ item.name }}</span>
              <span>x{{ item.quantity }}</span>
              <span>₴{{ item.price * item.quantity }}</span>
            </li>
          </ul>
          <div class="checkout-total">
            <strong>Разом: ₴{{ totalPrice }}</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>