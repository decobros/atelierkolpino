<?php
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'www.atelierkolpino.ru') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://atelierkolpino.ru' . $_SERVER['REQUEST_URI']);
    exit();
}
$pageTitle = "Прайс-лист на услуги Ателье в Колпино";
$pageDesc = "Актуальные цены на услуги нашего ателье. Стоимость подшива джинсов, ремонта курток, замена молний, замены фурнитуры и других работ.";
// ОПТИМИЗАЦИЯ: Кэшируем CSV товаров в ОТДЕЛЬНЫЙ файл, чтобы не ломать Главную
$cacheFile = __DIR__ . '/store/prices_search_cache.json';
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
                $products[] = ['id' => $id, 'title' => $title];
            }
        }
        fclose($handle);
        file_put_contents($cacheFile, json_encode($products, JSON_UNESCAPED_UNICODE));
    }
}
// Формируем массив для глобального поиска
$searchArray = [];
foreach ($products as $p) {
    $searchArray[] = ['cat' => 'каталог', 'name' => $p['title'], 'id' => $p['id']];
}
ini_set('auto_detect_line_endings', TRUE);
$pricesCsv = __DIR__ . '/store/prices.csv';
$categories = [];
$popularServices = [];
if (file_exists($pricesCsv) && ($handle = fopen($pricesCsv, "r")) !== FALSE) {
    fgetcsv($handle, 10000, ";"); 
    while (($row = fgetcsv($handle, 10000, ";")) !== FALSE) {
        if (count($row) < 3) continue;
        
        $cat = trim($row[0] ?? ''); 
        $service = trim($row[1] ?? ''); 
        $price = trim($row[2] ?? '');
        
        if ($price !== '' && mb_strpos($price, '₽') === false) { $price .= ' ₽'; }
        $isPopular = trim($row[3] ?? '') === '1';
        
        if ($cat !== '' && $service !== '') {
            $item = ['name' => $service, 'price' => $price];
            $categories[$cat][] = $item;
            
            $searchArray[] = ['cat' => $cat, 'name' => $service];
            
            if ($isPopular) { 
                $popularServices[] = ['cat' => mb_strtolower($cat), 'name' => $service . ' (' . mb_strtolower($cat) . ')', 'price' => $price]; 
            }
        }
    }
    fclose($handle);
}
ksort($categories);
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
    <title>Прайс-лист услуг</title>
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
    <!-- ПОДКЛЮЧАЕМ ЕДИНЫЙ ХЕДЕР -->
    <?php include __DIR__ . '/includes/header.php'; ?>
    <!-- ПОДКЛЮЧАЕМ ЕДИНЫЙ САЙДБАР -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <!-- ОСНОВНОЙ КОНТЕНТ -->
    <main class="price-page-wrapper">
        <h1 class="page-title">Прайс-лист на услуги</h1>
        
        <div id="noResultsMsg" class="nomatch-msg">По вашему запросу ничего не найдено.</div>
        
        <div id="priceListContainer">
            
            <?php if (!empty($popularServices)): ?>
                <details class="price-accordion popular-accordion" open>
                    <summary>⭐ Самые популярные услуги</summary>
                    <ul class="service-list">
                        <?php foreach ($popularServices as $item): ?>
                            <li class="service-item" data-search="<?php echo mb_strtolower($item['name'] . ' ' . $item['cat']); ?>">
                                <a href="https://max.ru/u/f9LHodD0cOJyV0CKX2_Ml36Zbsccjh9n3RhXnr3ICoEjuUrWv4HaYFongz0" target="_blank" class="service-row-link">
                                    <span class="service-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <div class="service-right">
                                        <span class="service-price"><?php echo $item['price']; ?></span>
                                        <span class="book-btn">Записаться</span>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
            
            <?php foreach ($categories as $catName => $services): ?>
                <details class="price-accordion standard-accordion">
                    <summary><?php echo htmlspecialchars($catName); ?></summary>
                    <ul class="service-list">
                        <?php foreach ($services as $srv): ?>
                            <li class="service-item" data-search="<?php echo mb_strtolower($srv['name'] . ' ' . $catName); ?>">
                                <a href="https://max.ru/u/f9LHodD0cOJyV0CKX2_Ml36Zbsccjh9n3RhXnr3ICoEjuUrWv4HaYFongz0" target="_blank" class="service-row-link">
                                    <span class="service-name"><?php echo htmlspecialchars($srv['name']); ?></span>
                                    <div class="service-right">
                                        <span class="service-price"><?php echo htmlspecialchars($srv['price']); ?></span>
                                        <span class="book-btn">Записаться</span>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endforeach; ?>
            
        </div>
    </main>
    <!-- СКРИПТ: УМНЫЙ ФИЛЬТР ТОЛЬКО ДЛЯ ПРАЙС-ЛИСТА -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            
            const mainSearch = document.getElementById("main-search");
            if(mainSearch) mainSearch.id = "localPriceSearch";
            
            const accordions = document.querySelectorAll(".price-accordion");
            accordions.forEach(targetAcc => {
                targetAcc.addEventListener("click", (e) => {
                    if (e.target.tagName === 'SUMMARY' || e.target.closest('summary')) {
                        if (!targetAcc.hasAttribute('open')) {
                            accordions.forEach(acc => { if (acc !== targetAcc) acc.removeAttribute("open"); });
                            setTimeout(() => { targetAcc.scrollIntoView({ behavior: "smooth", block: "start" }); }, 100);
                        }
                    }
                });
            });
            const searchInput = document.getElementById("localPriceSearch");
            const noResultsMsg = document.getElementById("noResultsMsg");
            
            // Специальный словарь для ПРАЙС-ЛИСТА
            const rootDictionary = [ 
                { match: "брюк", root: "брюк" }, { match: "джинс", root: "джинс" }, 
                { match: "куртк", root: "курт" }, { match: "пальт", root: "пальт" }, 
                { match: "пиджак", root: "пидж" }, { match: "плать", root: "плат" }, 
                { match: "рубаш", root: "рубаш" }, { match: "юбк", root: "юб" }, 
                { match: "юбоч", root: "юб" }, { match: "ушить", root: "уши" },
                { match: "ушив", root: "уши" },
                
                // Намерения создания перенаправляем на корень "пошив" (для поиска услуг по пошиву)
                { match: "сшит", root: "пошив" },
                { match: "пошив", root: "пошив" },
                { match: "изготов", root: "пошив" },
                { match: "лекал", root: "лекал" },
                // ИГНОРИРУЕМ слова-паразиты покупки. Если юзер ввел "хочу ушить", скрипт найдет просто "ушить".
                { match: "хочу", root: "IGNORE" },
                { match: "купи", root: "IGNORE" },
                { match: "купл", root: "IGNORE" },
                { match: "заказ", root: "IGNORE" },
                { match: "приобр", root: "IGNORE" }
            ];
            
            if (searchInput) {
                searchInput.addEventListener("input", function() {
                    let rawQuery = this.value.toLowerCase().trim();
                    
                    let queryWords = rawQuery.split(" ").filter(w => w.length > 0);
                    
                    // Подменяем корни
                    queryWords = queryWords.map(word => {
                        for (let item of rootDictionary) { if (word.includes(item.match)) return item.root; }
                        return word;
                    });
                    // Удаляем слова-намерения из фильтрации (чтобы не мешали искать)
                    queryWords = queryWords.filter(word => word !== "IGNORE");
                    
                    let anyFound = false;
                    accordions.forEach(accordion => {
                        let hasMatchInAccordion = false;
                        const items = accordion.querySelectorAll(".service-item");
                        
                        items.forEach(item => {
                            const itemData = item.getAttribute("data-search");
                            const isMatch = queryWords.length > 0 && queryWords.every(qWord => itemData.includes(qWord));
                            if (isMatch) { 
                                item.style.display = "block"; 
                                hasMatchInAccordion = true; 
                                anyFound = true; 
                            } else { 
                                item.style.display = "none"; 
                            }
                        });
                        
                        // Если ввели только слово "хочу" или вообще стерли текст:
                        if (rawQuery === "" || queryWords.length === 0) {
                            accordion.style.display = "block"; 
                            items.forEach(i => i.style.display = "block");
                            if (accordion.classList.contains("popular-accordion")) { accordion.setAttribute("open", ""); } 
                            else { accordion.removeAttribute("open"); }
                        } else {
                            if (hasMatchInAccordion) { 
                                accordion.style.display = "block"; 
                                accordion.setAttribute("open", ""); 
                            } else { 
                                accordion.style.display = "none"; 
                                accordion.removeAttribute("open"); 
                            }
                        }
                    });
                    
                    if(noResultsMsg) {
                        noResultsMsg.style.display = (!anyFound && rawQuery !== "" && queryWords.length > 0) ? "block" : "none";
                    }
                });
            }
            
            const urlParams = new URLSearchParams(window.location.search);
            const searchQuery = urlParams.get('search');
            if (searchQuery && searchInput) {
                searchInput.value = searchQuery;
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    </script>
    <!-- ПОДКЛЮЧАЕМ ЕДИНЫЙ ПОДВАЛ И ПАНЕЛЬ -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>