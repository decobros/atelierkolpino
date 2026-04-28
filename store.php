<?php
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'www.atelierkolpino.ru') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://atelierkolpino.ru' . $_SERVER['REQUEST_URI']);
    exit();
}
ini_set('auto_detect_line_endings', TRUE);
// Читаем ID товара
$product_id = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']) : '';
if (empty($product_id)) { die("Товар не найден."); }
$csvFile = __DIR__ . '/store/data.csv';
$productData = null;
$allProducts = []; 
// 1. Чтение всех данных из CSV 
if (file_exists($csvFile) && ($handle = fopen($csvFile, "r")) !== FALSE) {
    fgetcsv($handle, 10000, ";");
    while (($row = fgetcsv($handle, 10000, ";")) !== FALSE) {
        $currentId = trim($row[0] ?? '');
        $type = trim($row[1] ?? '');
        $title = trim($row[2] ?? 'Без названия');
        $price = trim($row[3] ?? '');
        $price_2 = trim($row[4] ?? ''); 
        
        if ($price !== '' && is_numeric($price)) { $price = number_format((float)$price, 0, '', ' ') . ' ₽'; }
        elseif ($price !== '' && mb_strpos($price, '₽') === false) { $price .= ' ₽'; }
        
        if ($price_2 !== '' && $price_2 !== '0' && is_numeric($price_2)) { 
            $price_2 = number_format((float)$price_2, 0, '', ' ') . ' ₽'; 
        }
        
        // Поиск целевого товара
        if ($currentId === $product_id) {
            $size = trim($row[5] ?? ''); 
            $height = trim($row[6] ?? ''); 
            $desc = trim($row[7] ?? ''); 
            
            $productData = [
                'id'    => $currentId,
                'type'  => $type,
                'title' => $title,
                'price' => $price,
                'price_2' => $price_2,
                'size'  => $size,
                'height'=> $height, 
                'desc'  => $desc
            ];
        }
        
        // Собираем остальные карточки, если это товары или услуги
        if (count($row) >= 4) {
            $otherFolder = __DIR__ . '/store/' . $currentId;
            $otherImages = [];
            if (is_dir($otherFolder)) {
                $oImages = glob($otherFolder . '/*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE);
                $thumbs = [];
                $originals = [];
                foreach($oImages as $img) {
                    if (strpos(basename($img), 'thumb_') === 0) { 
                        $thumbs[] = 'store/' . $currentId . '/' . basename($img);
                    } else {
                        $originals[] = 'store/' . $currentId . '/' . basename($img);
                    }
                }
                $otherImages = !empty($thumbs) ? $thumbs : $originals;
            }
            if(empty($otherImages)) { $otherImages[] = 'img/no-photo.jpg'; }
            $allProducts[] = [
                'id' => $currentId, 
                'title' => $title, 
                'price' => $price, 
                'price_2' => $price_2, 
                'images' => $otherImages
            ];
        }
    }
    fclose($handle);
}
if (!$productData) { die("<h1>Товар {$product_id} не найден. Проверьте ID.</h1>"); }
// --- УМНАЯ СОРТИРОВКА ДЛЯ НИЖНИХ ТОВАРОВ ---
$similarProducts = [];
$curTitle = mb_strtolower($productData['title'], 'UTF-8');
$isMale = (mb_strpos($curTitle, 'мужск') !== false);
$isFemale = (mb_strpos($curTitle, 'женск') !== false);
// Вычленяем корень первого слова из названия (шорт, блузк, брюк и т.д.)
$words = explode(' ', preg_replace('/[^a-zа-яё]/ui', ' ', $curTitle));
$curType = '';
foreach($words as $w) {
    if (mb_strlen($w, 'UTF-8') > 2) {
        $curType = rtrim(mb_strtolower($w, 'UTF-8'), 'аиеёоуыэюя'); 
        break;
    }
}
// Отфильтровываем сам открытый товар (чтобы не дублировался)
foreach ($allProducts as $p) {
    if ($p['id'] !== $productData['id']) {
        $similarProducts[] = $p;
    }
}
// Применяем логику сортировки
usort($similarProducts, function($a, $b) use ($isMale, $isFemale, $curType) {
    $tA = mb_strtolower($a['title'], 'UTF-8');
    $tB = mb_strtolower($b['title'], 'UTF-8');
    
    $aMale = (mb_strpos($tA, 'мужск') !== false);
    $aFemale = (mb_strpos($tA, 'женск') !== false);
    $bMale = (mb_strpos($tB, 'мужск') !== false);
    $bFemale = (mb_strpos($tB, 'женск') !== false);
    
    $aTypeMatched = ($curType !== '' && mb_strpos($tA, $curType) !== false);
    $bTypeMatched = ($curType !== '' && mb_strpos($tB, $curType) !== false);
    
    $getScore = function($isTypeMatch, $isTargetGenderMatch, $isTargetGender) {
        if ($isTargetGender) {
            if ($isTypeMatch && $isTargetGenderMatch) return 3; // Те же "шорты", тот же пол
            if ($isTargetGenderMatch) return 2;                 // Другая вещь, но тот же пол
            if ($isTypeMatch) return 1;                         // Те же "шорты", но чужой пол
        } else {
            if ($isTypeMatch) return 3; // Унисекс: точное совпадение вещи
        }
        return 0; // Совершенно иная вещь и пол (либо услуга)
    };
    
    $scoreA = $getScore($aTypeMatched, ($isMale ? $aMale : ($isFemale ? $aFemale : false)), ($isMale || $isFemale));
    $scoreB = $getScore($bTypeMatched, ($isMale ? $bMale : ($isFemale ? $bFemale : false)), ($isMale || $isFemale));
    
    if ($scoreA !== $scoreB) return $scoreB <=> $scoreA; // Сортируем от большего балла к меньшему
    return $a['id'] <=> $b['id']; // При равном счете сохраняем порядок по номеру ID
});
// 2. Чтение галереи изображений конкретного товара
$folderPath = __DIR__ . '/store/' . $product_id;
$images = [];
if (is_dir($folderPath)) {
    $allFiles = glob($folderPath . '/*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE);
    foreach ($allFiles as $file) {
        if (strpos(basename($file), 'thumb_') !== 0) {
            $images[] = 'store/' . $product_id . '/' . basename($file);
        }
    }
}
if (empty($images)) $images[] = 'img/no-photo.jpg'; 
// --- ДИНАМИЧЕСКОЕ SEO ДЛЯ ТОВАРА ---
$pageTitle = $productData['title'] . " - Ателье Татьяны Усановой";
$pageDesc = !empty($productData['desc']) 
    ? mb_substr($productData['desc'], 0, 150) . '...' 
    : "Заказать или узнать подробнее о '{$productData['title']}' по цене {$productData['price']} в нашем ателье.";
$pageImage = 'https://atelierkolpino.ru/' . ltrim($images[0], '/');
$msgText = "Здравствуйте, Татьяна! Меня интересует: " . $productData['title'] . " (" . $productData['price'] . ")";
$waText = urlencode($msgText);
// 3. ФОРМИРОВАНИЕ ПОИСКОВОГО МАССИВА
$searchArray = [];
$searchArray[] = ['cat' => 'каталог', 'name' => $productData['title'], 'id' => $productData['id']];
foreach ($allProducts as $p) {
    if ($p['id'] !== $productData['id']) {
        $searchArray[] = ['cat' => 'каталог', 'name' => $p['title'], 'id' => $p['id']];
    }
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
    <title><?php echo htmlspecialchars($productData['title']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="description" content="<?= htmlspecialchars($pageDesc ?? 'Ателье Татьяны Усановой: профессиональный ремонт, реставрация и индивидуальный пошив одежды любой сложности в Колпино.') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? 'Ателье Татьяны Усановой') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDesc ?? 'Ателье Татьяны Усановой. Профессиональный ремонт и пошив одежды в Колпино.') ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://atelierkolpino.ru<?= $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:image" content="<?= htmlspecialchars($pageImage ?? 'https://atelierkolpino.ru/img/apple-icon.jpg') ?>">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="store-wrapper">
        <div class="product-page-layout">
            
            <div class="product-gallery">
                <div class="main-img-box">
                    <?php foreach ($images as $index => $img): ?>
                        <img id="d-main-img-<?php echo $index; ?>" src="<?php echo $img; ?>" alt="Фото" class="<?php echo $index === 0 ? 'active' : ''; ?>">
                    <?php endforeach; ?>
                </div>
                <div class="gallery-thumbs-vertical">
                    <div class="thumbs-viewport">
                        <div class="thumbs-inner">
                            <?php foreach ($images as $index => $img): ?>
                                <div class="gallery-thumb-v <?php echo $index === 0 ? 'active' : ''; ?>" data-target="d-main-img-<?php echo $index; ?>">
                                    <img src="<?php echo $img; ?>" alt="Превью" loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="thumb-scroll-down" id="thumb-scroll"></div>
                </div>
                <div class="mobile-gallery-swipe" id="mobile-gallery-swipe">
                    <?php foreach ($images as $index => $img): ?>
                        <div class="m-img-pinch-wrap">
                            <img src="<?php echo $img; ?>" alt="Фото" <?php echo $index > 0 ? 'loading="lazy" decoding="async"' : ''; ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="product-details">
                <h1 class="product-title"><?php echo htmlspecialchars($productData['title']); ?></h1>
                <div class="p-price-wrap" style="margin-bottom: 25px;">
                    <?php if (!empty($productData['price_2'])): ?>
                        <span class="product-price-big new-price"><?php echo htmlspecialchars($productData['price_2']); ?></span>
                        <span class="product-price-big old-price"><?php echo htmlspecialchars($productData['price']); ?></span>
                    <?php else: ?>
                        <span class="product-price-big"><?php echo htmlspecialchars($productData['price']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($productData['size']) || !empty($productData['height'])): ?>
                <div class="product-params-row">
                    <?php if (!empty($productData['size'])): ?>
                    <div class="param-box">
                        <span class="param-label">Размер:</span>
                        <span class="param-val"><?php echo htmlspecialchars($productData['size']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($productData['height'])): ?>
                    <div class="param-box">
                        <span class="param-label">Рост:</span>
                        <span class="param-val"><?php echo htmlspecialchars($productData['height']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($productData['desc'])): ?>
                <div class="product-desc"><?php echo htmlspecialchars($productData['desc']); ?></div>
                <?php endif; ?>
                
                <button class="mobile-mega-contact-btn" id="mega-contact-btn">
                    Связаться с Ателье
                </button>
                
                <div class="desktop-contacts">
                    <a href="https://max.ru/u/f9LHodD0cOJyV0CKX2_Ml36Zbsccjh9n3RhXnr3ICoEjuUrWv4HaYFongz0" target="_blank" class="contact-btn btn-max">
                        <svg width="24" height="24" viewBox="0 0 512 512" fill="currentColor" fill-rule="evenodd"><path d="M146.72 37.6l218.46 0c60.16,0 109.22,49.06 109.22,109.22l0 218.46c0,60.16 -49.06,109.22 -109.22,109.22l-218.46 0c-60.16,0 -109.22,-49.06 -109.22,-109.22l0 -218.46c0,-60.16 49.06,-109.22 109.22,-109.22zm38.35 359.82c-16.75,21.53 -69.77,38.36 -72.09,9.57 0,-21.61 -4.78,-39.87 -10.2,-59.8 -6.46,-24.56 -13.8,-51.91 -13.8,-91.53 0,-94.65 77.67,-165.85 169.68,-165.85 92.09,0 164.25,74.71 164.25,166.72 0.31,90.59 -72.78,164.33 -163.37,164.81 -32.77,0 -48,-4.78 -74.47,-23.92zm-11.65 -148.46c7.74,-48.64 42.66,-79.66 87.47,-77.35 46.44,2.66 82.19,42.11 80.37,88.59 -3.12,46.38 -42.79,81.73 -89.22,79.5 -14.53,-1.17 -28.52,-6.16 -40.5,-14.44 -7.26,7.26 -18.9,16.67 -23.52,15.55 -9.65,-2.55 -20.97,-51.59 -14.6,-91.85z"/></svg>
                        Max Messenger
                    </a>
                    <a href="https://t.me/atelierkolpino" target="_blank" class="contact-btn btn-tg">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.19-.08-.05-.19-.02-.27 0-.12.03-1.99 1.28-5.64 3.75-.54.37-1.03.55-1.46.54-.48-.01-1.39-.27-2.07-.5-.83-.27-1.49-.42-1.43-.88.03-.24.38-.48 1.06-.74 4.14-1.8 6.91-3 8.31-3.6 3.96-1.68 4.79-2.09 5.34-2.1.12 0 .39.03.54.17.11.12.16.29.15.46z"/></svg>Telegram
                    </a>
                    <a href="https://wa.me/79312571971?text=<?php echo $waText; ?>" target="_blank" class="contact-btn btn-wa">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm5.46 14.1c-.24.67-1.35 1.23-1.89 1.3-.51.07-1.15.17-3.32-.71-2.6-1.05-4.26-3.7-4.39-3.87-.13-.17-1.05-1.4-1.05-2.68s.67-1.91.9-2.17c.23-.26.5-.32.67-.32.17 0 .35 0 .5.02.16.02.38-.06.59.43.21.5.72 1.76.78 1.89.06.13.11.28.02.45-.08.17-.13.28-.26.43-.13.15-.28.32-.38.45-.13.15-.28.31-.12.59.16.28.72 1.2 1.54 2.01.99.99 1.88 1.3 2.16 1.43.28.13.44.11.61-.06.17-.17.72-.84.92-1.13.2-.29.4-.24.66-.15.26.11 1.64.77 1.93.91.28.15.48.22.54.34.08.13.08.77-.16 1.44z"/></svg>WhatsApp
                    </a>
                    <a href="tel:+79312571971" class="contact-btn btn-call">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>+7 (931) 257-19-71
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (!empty($similarProducts)): ?>
        <div class="similar-products-block">
            <h2 class="mobile-catalog-title">Другие товары и услуги Ателье:</h2>
            <div class="product-grid">
                <?php foreach ($similarProducts as $p): ?>
                    <a href="store.php?id=<?php echo urlencode($p['id']); ?>" class="product-card">
                        <div class="card-img-wrapper">
                            <?php foreach ($p['images'] as $index => $imgSrc): ?>
                                <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" class="prod-img <?php echo $index === 0 ? 'active' : ''; ?>" <?php echo $index > 0 ? 'loading="lazy" decoding="async"' : ''; ?>>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                            <div class="card-price-wrap">
                                <?php if (!empty($p['price_2'])): ?>
                                    <span class="card-price new-price"><?php echo htmlspecialchars($p['price_2']); ?></span>
                                    <span class="card-price old-price"><?php echo htmlspecialchars($p['price']); ?></span>
                                <?php else: ?>
                                    <span class="card-price" style="color: var(--brand-text-color);"><?php echo htmlspecialchars($p['price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>