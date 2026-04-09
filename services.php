<?php
$pageTitle = "Услуги Ателье";
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'www.atelierkolpino.ru') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://atelierkolpino.ru' . $_SERVER['REQUEST_URI']);
    exit();
}
$pageTitle = "Услуги Ателье - Ремонт, реставрация и пошив одежды";
$pageDesc = "Полный спектр услуг ателье: подгонка одежды по фигуре, замена молний, перекрой одежды, реставрация изделий и пошив на заказ в Колпино.";
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
                    $thumbs = []; $originals = [];
                    foreach ($images as $img) {
                        if (strpos(basename($img), 'thumb_') === 0) { $thumbs[] = 'store/' . $id . '/' . basename($img); } 
                        else { $originals[] = 'store/' . $id . '/' . basename($img); }
                    }
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
$searchArray = [];
foreach ($products as $p) { $searchArray[] = ['cat' => 'каталог', 'name' => $p['title'], 'id' => $p['id']]; }
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
    <style>
        .info-page-block { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); margin-bottom: 20px; }
        .info-page-title { font-size: 28px; color: var(--brand-text-color); margin-bottom: 25px; line-height: 1.3; }
        .info-page-text  { font-size: 16px; line-height: 1.6; color: #444; }
        .info-page-text p { margin-bottom: 15px; }
        .info-page-text ul { padding-left: 20px; margin-bottom: 20px; }
        .info-page-text li { margin-bottom: 10px; padding-left: 5px; }
        .info-page-text li::marker { color: var(--brand-text-color); font-size: 1.2em; }
        
        .fake-img-box { width: 100%; aspect-ratio: 16/9; background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%); border-radius: 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 25px 0; color: #888; font-weight: bold; text-align: center; border: 2px dashed #ccc; }
        
        /* Исправление отступов каталога для информационных страниц */
        .info-catalog-wrapper { margin-top: 40px; padding-top: 30px; border-top: 1px solid #e0e0e0; }
        .info-catalog-title { display: block !important; font-size: 22px; font-weight: 700; color: var(--brand-text-color); margin-bottom: 20px; padding-left: 10px; }
    </style>
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
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="content-wrapper" style="padding-top: 6px;">
        
        <div class="info-page-block">
            <h1 class="info-page-title">Наши услуги</h1>
            
            <div class="info-page-text">
                <p>Ателье Татьяны Усановой предлагает полный спектр услуг по ремонту, реставрации и индивидуальному пошиву одежды. Мы работаем с любыми тканями: от легкого шелка и струящегося шифона до плотного денима, натуральной кожи и меха.</p>
                
                <div class="fake-img-box">
                    <span>ЗАГЛУШКА ИЗОБРАЖЕНИЯ</span>
                    <span style="font-size: 13px; font-weight: normal; margin-top: 5px;">Имя: services_1.jpg<br>Рекомендуемый размер: Горизонтальное фото (16:9)</span>
                </div>
                <h3 style="color: #333; margin: 25px 0 15px 0;">Ремонт и подгонка одежды по фигуре:</h3>
                <ul>
                    <li><strong>Укорачивание:</strong> брюк (с сохранением фабричного варенного края), юбок, платьев, рукавов рубашек и пиджаков.</li>
                    <li><strong>Подгонка по фигуре:</strong> ушивание или расшивание одежды по боковым и средним швам. Мы сделаем так, чтобы вещь сидела как влитая!</li>
                    <li><strong>Замена фурнитуры:</strong> замена молний на куртках, пуховиках, юбках и джинсах, установка кнопок, люверсов и крючков.</li>
                    <li><strong>Штопка и штуковка:</strong> художественная (незаметная) штопка протертых джинсов, устранение разрывов ткани, замена изношенных элементов воротников и манжет.</li>
                </ul>
                <h3 style="color: #333; margin: 25px 0 15px 0;">Сложные работы с кожей и мехом:</h3>
                <p>Натуральная кожа требует особого подхода, профессионального инструмента и специальных тефлоновых лапок. Мы осуществляем:</p>
                <ul>
                    <li>Замену деталей на кожаных куртках (потертые воротники, рукава, карманы).</li>
                    <li>Устранение разрывов на коже методом склеивания (жидкая кожа) или наложения декоративных заплат.</li>
                    <li>Перекрой старых шуб и дубленок под актуальные современные фасоны, замену подкладки.</li>
                </ul>
                <div class="fake-img-box">
                    <span>ЗАГЛУШКА ИЗОБРАЖЕНИЯ</span>
                    <span style="font-size: 13px; font-weight: normal; margin-top: 5px;">Имя: services_2.jpg<br>Рекомендуемый размер: Горизонтальное фото (16:9)</span>
                </div>
                
                <p>Если в этом списке вы не нашли нужную вам услугу, просто свяжитесь с нами любым удобным способом. Скорее всего, мы сможем решить вашу проблему!</p>
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