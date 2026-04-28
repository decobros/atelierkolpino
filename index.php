<?php
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'www.atelierkolpino.ru') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://atelierkolpino.ru' . $_SERVER['REQUEST_URI']);
    exit();
}
$pageTitle = "Ателье Татьяны Усановой - Ремонт и пошив одежды в Колпино";
$pageDesc = "Профессиональное ателье в Колпино. Ремонт джинсов, курток, платьев. Индивидуальный пошив. Опытные мастера и современное оборудование.";
// ОПТИМИЗАЦИЯ: Кэширование чтения CSV (живет 300 секунд = 5 минут)
$cacheFile = __DIR__ . '/store/products_cache.json';
$csvFile = __DIR__ . '/store/data.csv';
$products = [];
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 300) {
    // Читаем из быстрого кэша
    $products = json_decode(file_get_contents($cacheFile), true);
} else {
    // Читаем и парсим CSV, если кэш устарел или его нет
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
        // Записываем результат в кэш
        file_put_contents($cacheFile, json_encode($products, JSON_UNESCAPED_UNICODE));
    }
}
// ЛОГИКА БАННЕРОВ (Проверка наличия файлов 1-8)
$mobileBanners = [];
$desktopBanners = [];
for ($i = 1; $i <= 8; $i++) {
    $numStr = str_pad($i, 2, '0', STR_PAD_LEFT); // 01, 02, 03...
    
    $mobFile = "banners/{$numStr}s.jpg";
    $mobileBanners[] = file_exists(__DIR__ . '/' . $mobFile) ? $mobFile : null;
    
    $deskFile = "banners/{$numStr}w.jpg";
    $desktopBanners[] = file_exists(__DIR__ . '/' . $deskFile) ? $deskFile : null;
}
// Данные для JS умного поиска
$searchArray = [];
foreach ($products as $p) {
    $searchArray[] = ['cat' => 'каталог', 'name' => $p['title'], 'id' => $p['id']];
}
// Добавляем услуги из прайса в глобальный поиск
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
    <title>Ателье Татьяны Усановой</title>
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
    <!-- СПЛЭШ ЭКРАН -->
    <div class="splash-screen" id="splash-screen">
        <div class="splash-content">
            <img src="img/logo_1.svg" alt="Ателье" class="splash-logo-img">
         <!-- <img src="img/schedule.svg" alt="Ателье" class="splash-schedule-img"> -->
            <button class="enter-btn" id="enter-btn">Зайти в Ателье</button>
        </div>
    </div>
    <!-- ПОДКЛЮЧАЕМ ЕДИНЫЙ ХЕДЕР -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    <!-- ПОДКЛЮЧАЕМ ЕДИНЫЙ САЙДБАР -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <!-- ОСНОВНОЙ КОНТЕНТ (Растягивается через flex) -->
    <main class="content-wrapper">
        
        <!-- МОБИЛЬНЫЙ БАННЕР (Показывается только на экранах < 768px) -->
        <section class="mobile-banners-section">
            <div class="m-banner-track" id="m-banner-track">
                <?php foreach ($mobileBanners as $index => $imgSrc): $num = str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?>
                    <?php if ($imgSrc): ?>
                        <a href="promo.php?id=<?php echo $index + 1; ?>" class="m-banner-slide" style="display: block;">
                            <img src="<?php echo $imgSrc; ?>" <?php echo $index > 0 ? 'loading="lazy" decoding="async"' : ''; ?> alt="Баннер <?php echo $num; ?>">
                        </a>
                    <?php else: ?>
                        <div class="m-banner-slide">
                            <div class="banner-placeholder">
                                <span>Здесь может быть Ваша реклама</span>
                                <span class="b-number"><?php echo $num; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
        <!-- ДЕСКТОПНЫЙ БАННЕР (Показывается только на экранах > 768px) -->
        <section class="desktop-banners-section" id="d-banner-container">
            <?php foreach ($desktopBanners as $index => $imgSrc): $num = str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?>
                <?php if ($imgSrc): ?>
                    <a href="promo.php?id=<?php echo $index + 1; ?>" class="d-banner-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="display: block;">
                        <img src="<?php echo $imgSrc; ?>" <?php echo $index > 0 ? 'loading="lazy" decoding="async"' : ''; ?> alt="Баннер <?php echo $num; ?>">
                    </a>
                <?php else: ?>
                    <div class="d-banner-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                        <div class="banner-placeholder">
                            <span>Здесь может быть Ваша реклама — Баннер <?php echo $num; ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>
        <!-- === ДОБАВЛЕННЫЙ ЗАГОЛОВОК ДЛЯ МОБИЛЬНОЙ ВЕРСИИ === -->
        <h2 class="mobile-catalog-title">Товары и услуги Ателье</h2>
        <!-- СЕТКА ТОВАРОВ -->
        <section class="store-section">
            <div class="product-grid">
                <?php foreach ($products as $prod): ?>
                    <a href="store.php?id=<?php echo urlencode($prod['id']); ?>" class="product-card">
                        <div class="card-img-wrapper">
                            <?php foreach ($prod['images'] as $index => $imgSrc): ?>
                                <!-- Ленивая загрузка для всех фото, кроме первой (облегчает старт) -->
                                <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($prod['title']); ?>" class="prod-img <?php echo $index === 0 ? 'active' : ''; ?>" <?php echo $index > 0 ? 'loading="lazy" decoding="async"' : ''; ?>>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-info">
                            <div class="card-price-wrap">
                                <?php if (!empty($prod['price_2'])): ?>
                                    <span class="card-price new-price"><?php echo htmlspecialchars($prod['price_2']); ?></span>
                                    <span class="card-price old-price"><?php echo htmlspecialchars($prod['price']); ?></span>
                                <?php else: ?>
                                    <span class="card-price" style="color: var(--brand-text-color);"><?php echo htmlspecialchars($prod['price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="card-title"><?php echo htmlspecialchars($prod['title']); ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <!-- ПОДКЛЮЧАЕМ ЕДИНЫЙ ПОДВАЛ И ПАНЕЛЬ -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>