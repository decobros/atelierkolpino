<?php
session_start();
// ============================================================================
// 1. НАСТРОЙКИ И АВТОРИЗАЦИЯ
// ============================================================================
$adminUser = 'usanov';
$adminPass = '$olo2Usa!';
$storeDir = __DIR__ . '/store/';
// Обработка входа / выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}
if (isset($_POST['login_submit'])) {
    if ($_POST['username'] === $adminUser && $_POST['password'] === $adminPass) {
        $_SESSION['is_admin'] = true;
    }
    header("Location: admin.php");
    exit;
}
// ============================================================================
// 2. БЭКЕНД: AJAX АПИ ДЛЯ ТРЕХ СКРИПТОВ (РАБОТАЕТ ТОЛЬКО ЕСЛИ АВТОРИЗОВАН)
// ============================================================================
if (isset($_GET['action']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Content-Type: application/json');
    ini_set('memory_limit', '1024M'); // Гигабайт памяти для больших PNG и JPG
    
    $action = $_GET['action'];
    // --- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ---
    function getOriginalsList($storeDir) {
        $files = [];
        $folders = glob($storeDir . '*', GLOB_ONLYDIR);
        foreach ($folders as $folder) {
            // ТЕПЕРЬ СКАНИРУЕМ И PNG ТОЖЕ!
            $images = glob($folder . '/*.{jpg,jpeg,JPG,JPEG,png,PNG}', GLOB_BRACE);
            foreach ($images as $img) { if (strpos(basename($img), 'thumb_') !== 0) $files[] = $img; }
        }
        return $files;
    }
    function getThumbsList($storeDir) {
        $files = [];
        $folders = glob($storeDir . '*', GLOB_ONLYDIR);
        foreach ($folders as $folder) {
            $images = glob($folder . '/thumb_*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE);
            foreach ($images as $img) { $files[] = $img; }
        }
        return $files;
    }
    // --- РОУТИНГ AJAX ---
    
    // Получение списков
    if ($action === 'list_originals') { echo json_encode(['status' => 'ok', 'files' => getOriginalsList($storeDir)]); exit; }
    if ($action === 'list_thumbs') { echo json_encode(['status' => 'ok', 'files' => getThumbsList($storeDir)]); exit; }
    
    // ПРОЦЕСС 1: Удаление миниатюры
    if ($action === 'do_remove_thumb' && isset($_POST['filepath'])) {
        $file = $_POST['filepath'];
        if (file_exists($file) && unlink($file)) {
            echo json_encode(['status' => 'success', 'msg' => basename($file) . " удален."]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => basename($file) . " ошибка удаления."]);
        }
        exit;
    }
    // ПРОЦЕСС 2: Генерация миниатюры
    if ($action === 'do_generate_thumb' && isset($_POST['filepath'])) {
        $thumbWidth = 400; $quality = 85;
        $img = $_POST['filepath'];
        $folder = dirname($img); $name = basename($img);
        
        // Превью всегда должно быть JPG, даже если оригинал пока еще PNG
        $baseNameNoExt = pathinfo($name, PATHINFO_FILENAME);
        $thumbName = 'thumb_' . $baseNameNoExt . '.jpg';
        $thumb = $folder . '/' . $thumbName;
        if (file_exists($thumb)) { echo json_encode(['status' => 'skip', 'msg' => "$name: Уже есть."]); exit; }
        
        $type = exif_imagetype($img);
        if ($type == IMAGETYPE_JPEG) { $src = @imagecreatefromjpeg($img); }
        elseif ($type == IMAGETYPE_PNG) { $src = @imagecreatefrompng($img); }
        else { echo json_encode(['status' => 'error', 'msg' => "$name: Неподдерживаемый формат."]); exit; }
        if (!$src) { echo json_encode(['status' => 'error', 'msg' => "$name: Ошибка файла."]); exit; }
        $origW = imagesx($src); $origH = imagesy($src);
        $thumbHeight = floor($origH * ($thumbWidth / $origW));
        
        $canvas = imagecreatetruecolor($thumbWidth, $thumbHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white); // Фон белый для прозрачных PNG
        
        imagecopyresampled($canvas, $src, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $origW, $origH);
        imagejpeg($canvas, $thumb, $quality);
        
        imagedestroy($src); imagedestroy($canvas);
        echo json_encode(['status' => 'success', 'msg' => "$thumbName создан."]); exit;
    }
    // ПРОЦЕСС 3: Оптимизация оригинала (PNG/JPG -> ресайз по ширине в JPG)
    if ($action === 'do_optimize_original' && isset($_POST['filepath'])) {
        $targetWidth = 1800; // Здесь изменяем ширину изображения
        $quality = 90; // Здесь изменяем качество изображения JPG
        
        $img = $_POST['filepath']; $name = basename($img);
        $type = exif_imagetype($img);
        
        if ($type == IMAGETYPE_JPEG) { $src = @imagecreatefromjpeg($img); $isPng = false; }
        elseif ($type == IMAGETYPE_PNG) { $src = @imagecreatefrompng($img); $isPng = true; }
        else { echo json_encode(['status' => 'error', 'msg' => "$name: Неподдерживаемый формат."]); exit; }
        if (!$src) { echo json_encode(['status' => 'error', 'msg' => "$name: Ошибка открытия файла."]); exit; }
        
        $origW = imagesx($src); $origH = imagesy($src);
        
        // Формируем имя для нового JPG
        $baseNameNoExt = pathinfo($name, PATHINFO_FILENAME);
        $newJpgPath = dirname($img) . '/' . $baseNameNoExt . '.jpg';
        
        // 1. Если это УЖЕ JPG и ширина уже равна целевой - пропускаем
        if (!$isPng && $origW == $targetWidth) { 
            echo json_encode(['status' => 'skip', 'msg' => "$name: Ширина уже $targetWidth px."]); 
            exit; 
        }
        
        // Вычисляем пропорциональную высоту
        $targetHeight = floor($origH * ($targetWidth / $origW));
        
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white); // Белый фон спасет от черных дыр у PNG
        
        // Ресайз
        imagecopyresampled($canvas, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $origW, $origH);
        $msg = "$name: Изменена ширина до $targetWidth px и конвертировано в JPG.";
        
        // Сохраняем результат в формате JPG
        imagejpeg($canvas, $newJpgPath, $quality);
        imagedestroy($src); imagedestroy($canvas);
        
        // Если мы переконвертировали PNG в JPG и файл JPG создался успешно - УДАЛЯЕМ старый PNG
        if ($isPng && file_exists($newJpgPath)) {
            unlink($img);
        }
        
        echo json_encode(['status' => 'success', 'msg' => $msg]); exit;
    }
    
    exit;
}
// ============================================================================
// 3. ФРОНТЕНД (ИНТЕРФЕЙС ПАНЕЛИ)
// ============================================================================
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель Управления Ателье</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        body { background: #f0f2f5; color: #333; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; }
        
        /* Авторизация */
        .login-box { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); width: 100%; max-width: 400px; text-align: center; margin: auto; }
        .login-box input { width: 100%; padding: 15px; margin-bottom: 15px; border-radius: 12px; border: 1px solid #ddd; font-size: 16px; outline: none; }
        
        /* Главная панель */
        .admin-header { width: 100%; max-width: 900px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .admin-header h1 { color: #FF4500; font-size: 24px; }
        .logout-btn { background: #333; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: bold; transition: 0.2s; }
        .logout-btn:hover { background: #000; }
        .tool-card { background: white; width: 100%; max-width: 900px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 30px; border-left: 5px solid #FF4500; }
        .tool-title { font-size: 20px; font-weight: 700; margin-bottom: 10px; color: #222; display: flex; align-items: center; gap: 10px; }
        .tool-desc { color: #666; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
        
        .btn-action { display: block; width: 100%; max-width: 300px; padding: 15px; background: linear-gradient(90deg, #FF4500 0%, #FF8C00 100%); color: white; font-size: 16px; font-weight: bold; text-align: center; border: none; border-radius: 50px; cursor: pointer; box-shadow: 0 4px 15px rgba(255, 69, 0, 0.3); transition: transform 0.1s; margin-bottom: 15px; }
        .btn-action:active { transform: scale(0.97); }
        .btn-action:disabled { background: #ccc; box-shadow: none; cursor: not-allowed; }
        .btn-red { background: linear-gradient(90deg, #E53935 0%, #F44336 100%); box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3); }
        .progress-box { display: none; }
        .progress-wrapper { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; position: relative; margin-bottom: 10px; }
        .progress-fill { height: 100%; width: 0%; background: #4CAF50; transition: width 0.2s; }
        .progress-text { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; font-size: 12px; font-weight: bold; color: #333; }
        
        .log-box { width: 100%; height: 150px; background: #1e1e1e; border-radius: 8px; padding: 10px; overflow-y: auto; font-family: monospace; font-size: 12px; color: #ddd; display: flex; flex-direction: column; gap: 4px; }
        .green { color: #4CAF50; } .red { color: #f44336; } .gray { color: #888; } .orange { color: #FF9800; }
    </style>
</head>
<body>
<?php if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true): ?>
    <!-- ФОРМА АВТОРИЗАЦИИ -->
    <div class="login-box">
        <h2 style="margin-bottom: 25px; color: #FF4500;">Вход в ПУ</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit" name="login_submit" class="btn-action" style="max-width: 100%;">Войти</button>
        </form>
    </div>
<?php else: ?>
    <!-- ПАНЕЛЬ УПРАВЛЕНИЯ -->
    <div class="admin-header">
        <h1>Фото-пульт Ателье</h1>
        <a href="?logout=1" class="logout-btn">Выйти</a>
    </div>
    <!-- БЛОК 1: УДАЛЕНИЕ МИНИАТЮР -->
    <div class="tool-card" style="border-color: #F44336;">
        <div class="tool-title">🗑️ Удаление старых миниатюр</div>
        <div class="tool-desc">Первый шаг: Безопасно находит и пакетно удаляет все уменьшенные копии (файлы с приставкой thumb_), освобождая место перед оптимизацией. Оригиналы затронуты не будут.</div>
        <button id="btn-rem" class="btn-action btn-red" onclick="runTool('list_thumbs', 'do_remove_thumb', 'btn-rem', 'box-rem', 'fill-rem', 'txt-rem', 'log-rem')">Удалить миниатюры</button>
        <div id="box-rem" class="progress-box">
            <div class="progress-wrapper"><div id="fill-rem" class="progress-fill"></div><div id="txt-rem" class="progress-text">0 / 0</div></div>
            <div id="log-rem" class="log-box"></div>
        </div>
    </div>
    <!-- БЛОК 2: ОПТИМИЗАЦИЯ ОРИГИНАЛОВ -->
    <div class="tool-card">
        <div class="tool-title">🎨 Оптимизация оригиналов (Ширина 1800px + JPG)</div>
        <div class="tool-desc">Второй шаг: Приводит оригиналы (JPG и PNG) к ширине 1800px (высота масштабируется пропорционально). Все PNG автоматически конвертируются в высококачественный JPG, старые файлы PNG удаляются.</div>
        <button id="btn-opt" class="btn-action" onclick="runTool('list_originals', 'do_optimize_original', 'btn-opt', 'box-opt', 'fill-opt', 'txt-opt', 'log-opt')">Оптимизировать оригиналы</button>
        <div id="box-opt" class="progress-box">
            <div class="progress-wrapper"><div id="fill-opt" class="progress-fill" style="background:#FF9800;"></div><div id="txt-opt" class="progress-text">0 / 0</div></div>
            <div id="log-opt" class="log-box"></div>
        </div>
    </div>
    <!-- БЛОК 3: ГЕНЕРАЦИЯ МИНИАТЮР -->
    <div class="tool-card" style="border-color: #4CAF50;">
        <div class="tool-title">⚡ Генерация легких миниатюр</div>
        <div class="tool-desc">Третий шаг: Создает новые супер-быстрые копии шириной 400px из обновленных оригиналов. Обязательно для моментальной загрузки сетки товаров на главной странице.</div>
        <button id="btn-gen" class="btn-action" style="background: linear-gradient(90deg, #4CAF50 0%, #8BC34A 100%);" onclick="runTool('list_originals', 'do_generate_thumb', 'btn-gen', 'box-gen', 'fill-gen', 'txt-gen', 'log-gen')">Создать миниатюры</button>
        <div id="box-gen" class="progress-box">
            <div class="progress-wrapper"><div id="fill-gen" class="progress-fill"></div><div id="txt-gen" class="progress-text">0 / 0</div></div>
            <div id="log-gen" class="log-box"></div>
        </div>
    </div>
    <!-- AJAX ДВИЖОК -->
    <script>
    async function runTool(listAction, processAction, btnId, boxId, fillId, txtId, logId) {
        const btn = document.getElementById(btnId);
        const box = document.getElementById(boxId);
        const fill = document.getElementById(fillId);
        const txt = document.getElementById(txtId);
        const logBox = document.getElementById(logId);
        
        btn.disabled = true; box.style.display = 'block'; logBox.innerHTML = '';
        const log = (msg, cls='') => { logBox.innerHTML += `<div class="${cls}">> ${msg}</div>`; logBox.scrollTop = logBox.scrollHeight; }
        
        log('Сканирование директорий...', 'orange');
        try {
            let res = await fetch(`?action=${listAction}`);
            let data = await res.json();
            
            if (!data.files || data.files.length === 0) {
                log('Файлов для обработки не найдено!', 'green');
                btn.disabled = false; return;
            }
            let files = data.files; let total = files.length;
            log(`Найдено файлов: ${total}. Начинаем процесс...`, 'orange');
            
            let success = 0, skip = 0, errCount = 0;
            for (let i = 0; i < total; i++) {
                let formData = new FormData();
                formData.append('filepath', files[i]);
                try {
                    let procRes = await fetch(`?action=${processAction}`, { method: 'POST', body: formData });
                    let procData = await procRes.json();
                    
                    if (procData.status === 'success') { log('✔ ' + procData.msg, 'green'); success++; }
                    else if (procData.status === 'skip') { log('⏭ ' + procData.msg, 'gray'); skip++; }
                    else { log('✖ ' + procData.msg, 'red'); errCount++; }
                } catch(e) { log('✖ Мелкий сбой сети на файле.', 'red'); errCount++; }
                let perc = Math.round(((i+1)/total)*100);
                fill.style.width = perc + '%'; txt.innerHTML = `${i+1} / ${total} (${perc}%)`;
            }
            log(`ПОЛНОСТЬЮ ЗАВЕРШЕНО! Успешно: ${success} | Пропуск: ${skip} | Ошибок: ${errCount}`, 'orange');
        } catch (e) {
            log('Критическая ошибка системы связи.', 'red');
        }
        btn.disabled = false;
    }
    </script>
<?php endif; ?>
</body>
</html>