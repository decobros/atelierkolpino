<?php
// Если $searchArray не передан перед загрузкой хедера, инициализируем пустой, 
// чтобы скрипт поиска не сломался
if (!isset($searchArray)) {
    $searchArray = [];
}
?>
<!-- ХЕДЕР -->
<header class="main-header" id="main-header">
    <div class="header-container">
        <div class="header-top">
            <div class="location" id="user-location">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                Санкт-Петербург, г. Колпино
            </div>
            <nav class="top-menu">
                <ul id="menu-list">
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="services.php">Услуги</a></li>
                    <li><a href="prices.php">Цены</a></li>
                    <li><a href="about.php">Наше Ателье</a></li>
                    <li><a href="https://yandex.ru/maps/org/atelye_tatyany_usanovoy/150845542058/reviews/" target="_blank">Отзывы</a></li>
                    <li><a href="https://yandex.ru/maps/26081/kolpino/?ll=30.594795%2C59.750636&mode=routes&rtext=~59.750635%2C30.594795&rtt=auto&ruri=~ymapsbm1%3A%2F%2Forg%3Foid%3D150845542058&z=14" target="_blank">Как нас найти</a></li>
                </ul>
            </nav>
            <div class="contact-phone">+7 (931) 257-19-71</div>
        </div>
        <div class="header-bottom">
            <div class="logo-and-burger">
                <button id="burger-btn" class="burger-btn"><span class="burger-lines"></span></button>
                <a href="index.php" class="logo" id="main-logo"><img src="img/logo_2.svg" alt="Ателье" class="header-logo-img"></a>
            </div>
            
            <div class="search-and-nav" style="flex-grow: 1; display: flex; flex-direction: column; align-items: center;">
                <div class="search-wrapper" style="width: 100%;">
                    <input type="text" class="search-input" id="main-search" placeholder="Поиск услуг и товаров..." autocomplete="off">
                    <!-- Умное выпадающее окно -->
                    <div class="search-dropdown" id="search-dropdown">
                        <ul id="results-list" class="popular-queries"></ul>
                    </div>
                </div> 
                
                <!-- === НОВЫЕ МАНДАРИНКИ ДЛЯ МОБИЛОК === -->
                <div class="mobile-mandarin-nav">
                    <a href="index.php" class="mandarin-btn">
                        <img src="img/1.svg" alt="index">
                        <span></span>
                    </a>
                    <a href="services.php" class="mandarin-btn">
                        <img src="img/2.svg" alt="services">
                        <span></span>
                    </a>
                    <a href="about.php" class="mandarin-btn">
                        <img src="img/3.svg" alt="about">
                        <span></span>
                    </a>
                </div>
            </div>
        </div> 
    </div>
</header>