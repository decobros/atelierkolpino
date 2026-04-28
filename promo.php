<?php
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'www.atelierkolpino.ru') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://atelierkolpino.ru' . $_SERVER['REQUEST_URI']);
    exit();
}
$pageTitle = "Акции и Новости - Ателье";
// Узнаем, на какой баннер кликнул пользователь (от 1 до 8)
$promo_id = isset($_GET['id']) ? preg_replace('/[^0-9]/', '', $_GET['id']) : '1';
// ОПТИМИЗАЦИЯ: Кэширование чтения CSV (живет 300 секунд)
$cacheFile = __DIR__ . '/store/products_cache.json';
$csvFile = __DIR__ . '/store/data.csv';
$products = [];
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 300) {
    $products = json_decode(file_get_contents($cacheFile), true);
} else {
    if (file_exists($csvFile) && ($handle = fopen($csvFile, "r")) !== FALSE) {
        fgetcsv($handle, 1000, ";");
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            if (count($data) >= 4) {
                $id = trim($data[0]); $title = trim($data[2]); 
                $price = trim($data[3]); 
                $price_2 = trim($data[4] ?? ''); // Читаем 4-ю колонку
                
                if ($price !== '' && is_numeric($price)) { $price = number_format((float)$price, 0, '', ' ') . ' ₽'; }
                elseif ($price !== '' && mb_strpos($price, '₽') === false) { $price .= ' ₽'; }
                
                if ($price_2 !== '' && $price_2 !== '0' && is_numeric($price_2)) { $price_2 = number_format((float)$price_2, 0, '', ' ') . ' ₽'; }
                else { $price_2 = ''; }
                $folderPath = __DIR__ . '/store/' . $id;
                $productImages = [];
                if (is_dir($folderPath)) {
                    $images = glob($folderPath . '/*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE);
                    $thumbs = [];
                    $originals = [];
                    foreach ($images as $img) {
                        if (strpos(basename($img), 'thumb_') === 0) {
                            $thumbs[] = 'store/' . $id . '/' . basename($img);
                        } else {
                            $originals[] = 'store/' . $id . '/' . basename($img);
                        }
                    }
                    // ГЕНИАЛЬНЫЙ ФИКС: Если есть миниатюры - берем ТОЛЬКО их! Если нет - берем оригиналы.
                    $productImages = !empty($thumbs) ? $thumbs : $originals;
                }
                if (empty($productImages)) { $productImages[] = 'img/no-photo.jpg'; }
                
                $products[] = ['id' => $id, 'title' => $title, 'price' => $price, 'price_2' => $price_2, 'images' => $productImages];
            }
        }
        fclose($handle);
        file_put_contents($cacheFile, json_encode($products, JSON_UNESCAPED_UNICODE));
    }
}
// Данные для JS умного поиска
$searchArray = [];
foreach ($products as $p) {
    $searchArray[] = ['cat' => 'каталог', 'name' => $p['title'], 'id' => $p['id']];
}
$pricesCsvTemp = __DIR__ . '/store/prices.csv';
if (file_exists($pricesCsvTemp) && ($hTemp = fopen($pricesCsvTemp, "r")) !== FALSE) {
    fgetcsv($hTemp, 10000, ";");
    while (($rTemp = fgetcsv($hTemp, 10000, ";")) !== FALSE) {
        if (count($rTemp) >= 3 && trim($rTemp[0]) !== '' && trim($rTemp[1]) !== '') {
            $searchArray[] = ['cat' => trim($rTemp[0]), 'name' => trim($rTemp[1])];
        }
    }
    fclose($hTemp);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Ателье">
    
    <link rel="apple-touch-icon" href="img/apple-icon.jpg?v=2">
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link rel="manifest" href="manifest.json">
    
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="css/style.css">
    <!-- SEO и Open Graph (Превью для мессенджеров и соцсетей) -->
    <meta name="description" content="<?= htmlspecialchars($pageDesc ?? 'Ателье Татьяны Усановой: профессиональный ремонт, реставрация и индивидуальный пошив одежды любой сложности в Колпино.') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? 'Ателье Татьяны Усановой') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDesc ?? 'Ателье Татьяны Усановой. Профессиональный ремонт и пошив одежды в Колпино.') ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://atelierkolpino.ru<?= $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:image" content="<?= htmlspecialchars($pageImage ?? 'https://atelierkolpino.ru/img/apple-icon.jpg') ?>">
    <!-- ======================================================= -->
</head>
<body>
    <!-- ПОДКЛЮЧАЕМ ЕДИНЫЙ ХЕДЕР И САЙДБАР -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <!-- ОСНОВНОЙ КОНТЕНТ -->
    <main class="content-wrapper" style="padding-top: 6px;">
        <?php
        // Массив с текстами акций
        $promoData = [
            '1' => [
                'title' => 'Режим работы Ателье',
                'text'  => '<p style="margin-bottom: 15px;">Ждем вас в нашем Ателье в удобное для вас время!</p><table style="width: 100%; max-width: 400px; border-collapse: collapse; font-size: 15px;"><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Вторник<br>Среда<br>Четверг<br>Пятница</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 19:00</td></tr><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Суббота</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 17:00</td></tr><tr><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #FF4500;">Воскресенье<br>Понедельник</td><td style="padding: 10px 0; text-align: left; color: #FF4500;">выходной</td></tr></table>'
            ],
            '2' => [
                'title' => 'Режим работы Ателье',
                'text'  => '<p style="margin-bottom: 15px;">Ждем вас в нашем Ателье в удобное для вас время!</p><table style="width: 100%; max-width: 400px; border-collapse: collapse; font-size: 15px;"><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Вторник<br>Среда<br>Четверг<br>Пятница</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 19:00</td></tr><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Суббота</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 17:00</td></tr><tr><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #FF4500;">Воскресенье<br>Понедельник</td><td style="padding: 10px 0; text-align: left; color: #FF4500;">выходной</td></tr></table>'
            ],
            '3' => [
                'title' => 'Режим работы Ателье',
                'text'  => '<p style="margin-bottom: 15px;">Ждем вас в нашем Ателье в удобное для вас время!</p><table style="width: 100%; max-width: 400px; border-collapse: collapse; font-size: 15px;"><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Вторник<br>Среда<br>Четверг<br>Пятница</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 19:00</td></tr><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Суббота</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 17:00</td></tr><tr><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #FF4500;">Воскресенье<br>Понедельник</td><td style="padding: 10px 0; text-align: left; color: #FF4500;">выходной</td></tr></table>'
            ],
            '4' => [
                'title' => 'Режим работы Ателье',
                'text'  => '<p style="margin-bottom: 15px;">Ждем вас в нашем Ателье в удобное для вас время!</p><table style="width: 100%; max-width: 400px; border-collapse: collapse; font-size: 15px;"><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Вторник<br>Среда<br>Четверг<br>Пятница</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 19:00</td></tr><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Суббота</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 17:00</td></tr><tr><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #FF4500;">Воскресенье<br>Понедельник</td><td style="padding: 10px 0; text-align: left; color: #FF4500;">выходной</td></tr></table>'
            ],
            '5' => [
                'title' => 'Режим работы Ателье',
                'text'  => '<p style="margin-bottom: 15px;">Ждем вас в нашем Ателье в удобное для вас время!</p><table style="width: 100%; max-width: 400px; border-collapse: collapse; font-size: 15px;"><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Вторник<br>Среда<br>Четверг<br>Пятница</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 19:00</td></tr><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Суббота</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 17:00</td></tr><tr><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #FF4500;">Воскресенье<br>Понедельник</td><td style="padding: 10px 0; text-align: left; color: #FF4500;">выходной</td></tr></table>'
            ],
            '6' => [
                'title' => 'Режим работы Ателье',
                'text'  => '<p style="margin-bottom: 15px;">Ждем вас в нашем Ателье в удобное для вас время!</p><table style="width: 100%; max-width: 400px; border-collapse: collapse; font-size: 15px;"><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Вторник<br>Среда<br>Четверг<br>Пятница</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 19:00</td></tr><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Суббота</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 17:00</td></tr><tr><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #FF4500;">Воскресенье<br>Понедельник</td><td style="padding: 10px 0; text-align: left; color: #FF4500;">выходной</td></tr></table>'
            ],
            '7' => [
                'title' => 'Режим работы Ателье',
                'text'  => '<p style="margin-bottom: 15px;">Ждем вас в нашем Ателье в удобное для вас время!</p><table style="width: 100%; max-width: 400px; border-collapse: collapse; font-size: 15px;"><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Вторник<br>Среда<br>Четверг<br>Пятница</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 19:00</td></tr><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Суббота</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 17:00</td></tr><tr><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #FF4500;">Воскресенье<br>Понедельник</td><td style="padding: 10px 0; text-align: left; color: #FF4500;">выходной</td></tr></table>'
            ],
            '8' => [
                'title' => 'Режим работы Ателье',
                'text'  => '<p style="margin-bottom: 15px;">Ждем вас в нашем Ателье в удобное для вас время!</p><table style="width: 100%; max-width: 400px; border-collapse: collapse; font-size: 15px;"><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Вторник<br>Среда<br>Четверг<br>Пятница</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 19:00</td></tr><tr style="border-bottom: 1px solid #eee;"><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #333;">Суббота</td><td style="padding: 10px 0; text-align: left; color: #555;">10:00 - 17:00</td></tr><tr><td style="padding: 10px 20px 10px 0; font-weight: 600; text-align: left; color: #FF4500;">Воскресенье<br>Понедельник</td><td style="padding: 10px 0; text-align: left; color: #FF4500;">выходной</td></tr></table>'
            ]
        ];
        // Проверяем, есть ли текст для запрошенного ID. Если нет, показываем первый.
        $currentPromo = isset($promoData[$promo_id]) ? $promoData[$promo_id] : $promoData['1'];
        ?>
        <!-- БЛОК ТЕКСТА АКЦИИ -->
        <div style="background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); margin-bottom: 0px;">
            <h1 style="font-size: 28px; color: var(--brand-text-color); margin-bottom: 20px; line-height: 1.3;">
                <?php echo $currentPromo['title']; ?>
            </h1>
            <div style="font-size: 16px; line-height: 1.6; color: #444;">
                <?php echo $currentPromo['text']; ?>
            </div>
        </div>
        <!-- === СЕТКА ТОВАРОВ === -->
        <div class="similar-products-block">
          <!-- Заголовок (мы убрали класс mobile-catalog-title, чтобы он показывался и на ПК) -->
            <h2 class="mobile-catalog-title" style="margin-bottom: 12px; padding-left: 16px;">Товары и услуги Ателье:</h2>
              <section class="store-section">
                 <div class="product-grid">
                    <?php foreach ($products as $prod): ?>
                        <a href="store.php?id=<?php echo urlencode($prod['id']); ?>" class="product-card">
                            <div class="card-img-wrapper">
                                <?php foreach ($prod['images'] as $index => $imgSrc): ?>
                                    <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($prod['title']); ?>" class="prod-img <?php echo $index === 0 ? 'active' : ''; ?>" <?php echo $index > 0 ? 'loading="lazy" decoding="async"' : ''; ?>>
                                <?php endforeach; ?>
                            </div>

                            <div class="card-info">
                                <h3 class="card-title"><?php echo htmlspecialchars($prod['title']); ?></h3>
                                <div class="card-price-wrap">
                                    <?php if (!empty($prod['price_2'])): ?>
                                        <span class="card-price new-price"><?php echo htmlspecialchars($prod['price_2']); ?></span>
                                        <span class="card-price old-price"><?php echo htmlspecialchars($prod['price']); ?></span>
                                    <?php else: ?>
                                        <span class="card-price" style="color: var(--brand-text-color);"><?php echo htmlspecialchars($prod['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
    <!-- ПОДКЛЮЧАЕМ ЕДИНЫЙ ПОДВАЛ И ПАНЕЛЬ -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>