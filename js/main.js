document.addEventListener("DOMContentLoaded", () => {
    
    // --- 1. Работа со Splash Screen ---
    const splashScreen = document.getElementById("splash-screen");
    const enterBtn = document.getElementById("enter-btn");
    const tabHome = document.getElementById("tab-home");
    
    if (splashScreen) {
        if (sessionStorage.getItem('enteredAtelier') === 'true' || window.location.search.includes('page=store')) {
            splashScreen.style.display = "none"; 
            document.body.style.overflow = ""; 
        } else {
            document.body.style.overflow = "hidden";
            if (enterBtn) {
                enterBtn.addEventListener("click", () => {
                    sessionStorage.setItem('enteredAtelier', 'true'); 
                    document.body.style.overflow = "";
                    splashScreen.style.opacity = "0"; 
                    setTimeout(() => { splashScreen.style.display = "none"; }, 500);
                });
            }
        }
    }
    // --- 2. Подсветка кнопок нижней панели ---
    const currentPath = window.location.pathname;
    document.querySelectorAll('.mobile-tab-bar .tab-item').forEach(tab => {
        tab.classList.remove('active');
        const href = tab.getAttribute('href');
        if (href && currentPath.includes(href)) {
            tab.classList.add('active');
        }
    });
    if (currentPath.endsWith('/') || currentPath.endsWith('index.php')) {
        if(tabHome) tabHome.classList.add('active');
    }
    // --- 3. Растворение хедера (мобильные) ---
    const header = document.getElementById("main-header");
    if (header && window.innerWidth <= 768) {
        window.addEventListener("scroll", () => {
            let scrollTop = window.scrollY || document.documentElement.scrollTop;
            if (scrollTop <= 0) { header.style.setProperty('--scroll-progress', 0); return; }
            let progress = Math.min(scrollTop / 50, 1);
            header.style.setProperty('--scroll-progress', progress);
        }, { passive: true });
    }
    // --- 4. Сайдбар для ПК ---
    const burgerBtn = document.getElementById("burger-btn");
    const sidebar = document.getElementById("main-sidebar");
    const overlay = document.getElementById("sidebar-overlay");
    const level1Items = document.querySelectorAll("#sidebar-menu-level-1 li");
    const level2 = document.getElementById("sidebar-level-2");
    const submenus = document.querySelectorAll(".submenu");
    
    function toggleSidebar() {
        sidebar.classList.toggle("active");
        overlay.classList.toggle("active");
        burgerBtn.classList.toggle("active");
        level2.classList.remove("active");
        submenus.forEach(s => s.style.display = "none");
        document.body.style.overflow = sidebar.classList.contains("active") ? "hidden" : "";
    }
    
    if (burgerBtn && sidebar && overlay) {
        burgerBtn.addEventListener("click", toggleSidebar);
        overlay.addEventListener("click", toggleSidebar);
        level1Items.forEach(item => {
            item.addEventListener("mouseenter", () => {
                submenus.forEach(s => s.style.display = "none");
                const targetId = item.getAttribute("data-target");
                const targetSubmenu = document.getElementById(targetId);
                if (targetSubmenu) { targetSubmenu.style.display = "block"; level2.classList.add("active"); }
            });
        });
        sidebar.addEventListener("mouseleave", () => { level2.classList.remove("active"); });
    }
    // --- 5. БАННЕРЫ НА ГЛАВНОЙ ---
    const dBannerContainer = document.getElementById("d-banner-container");
    if (dBannerContainer && window.innerWidth > 768) {
        const slides = dBannerContainer.querySelectorAll(".d-banner-slide");
        if (slides.length > 1) {
            let currentSlide = 0;
            setInterval(() => {
                slides[currentSlide].classList.remove("active");
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add("active");
            }, 3000); 
        }
    }
    const mBannerTrack = document.getElementById("m-banner-track");
    if (mBannerTrack && window.innerWidth <= 768) {
        const mSlides = Array.from(mBannerTrack.querySelectorAll(".m-banner-slide"));
        if (mSlides.length > 1) {
            let mCurrent = 0;
            setInterval(() => {
                if (mBannerTrack.matches(':active')) return; 
                mCurrent = (mCurrent + 1) % mSlides.length;
                const nextSlideLeft = mSlides[mCurrent].offsetLeft;
                mBannerTrack.scrollTo({ left: nextSlideLeft, behavior: 'smooth' });
            }, 3000);
        }
    }
    // --- 6. Умный поиск (ГЛОБАЛЬНЫЙ ДЛЯ ХЕДЕРА) ---
    const mainSearchInput = document.getElementById("main-search");
    const searchDropdown = document.getElementById("search-dropdown");
    const resultsList = document.getElementById("results-list");
    
    if (mainSearchInput && searchDropdown) {
        const rootDict = [ 
            { match: "брюк", root: "брюк" }, { match: "джинс", root: "джинс" }, 
            { match: "куртк", root: "курт" }, { match: "пальт", root: "пальт" }, 
            { match: "пиджак", root: "пидж" }, { match: "плать", root: "плат" }, 
            { match: "рубаш", root: "рубаш" }, { match: "юбк", root: "юб" }, 
            { match: "юбоч", root: "юб" }, { match: "ушить", root: "уши" },
            { match: "ушив", root: "уши" },
            { match: "купи", root: "каталог" }, 
            { match: "купл", root: "каталог" },
            { match: "заказ", root: "каталог" },
            { match: "сшит", root: "каталог" },
            { match: "пошив", root: "каталог" },
            { match: "изготов", root: "каталог" },
            { match: "приобр", root: "каталог" },
            { match: "лекал", root: "каталог" },
            { match: "хочу", root: "каталог" }
        ];
        mainSearchInput.addEventListener("focus", () => { 
            if (mainSearchInput.value.trim().length > 1 && mainSearchInput.id !== 'localPriceSearch') {
                searchDropdown.style.display = "block"; 
            }
        });
        
        document.addEventListener("click", (e) => {
            if (!mainSearchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.style.display = "none";
            }
        });
        
        mainSearchInput.addEventListener("input", (e) => {
            if(mainSearchInput.id === 'localPriceSearch') {
                searchDropdown.style.display = "none";
                return;
            }
            let rawQuery = e.target.value.toLowerCase().trim();
            
            if (rawQuery.length > 1 && typeof appSearchData !== 'undefined') {
                searchDropdown.style.display = "block";
                resultsList.innerHTML = "";
                
                let queryWords = rawQuery.split(" ").filter(w => w.length > 0);
                queryWords = queryWords.map(word => {
                    for (let item of rootDict) { if (word.includes(item.match)) return item.root; }
                    return word;
                });
                
                const filtered = appSearchData.filter(item => {
                    const searchString = (item.name + " " + item.cat).toLowerCase();
                    return queryWords.every(qWord => searchString.includes(qWord));
                });
                
                const topResults = filtered.slice(0, 8);
                if (topResults.length > 0) {
                    topResults.forEach(item => {
                        const li = document.createElement("li");
                        li.style.borderBottom = "1px solid #f5f5f5";
                        
                        if (item.id) {
                            li.innerHTML = `<a href="store.php?id=${item.id}" style="color:inherit; text-decoration:none; display:block; padding: 10px 5px;">🛍️ ${item.name}</a>`;
                        } else {
                            li.innerHTML = `<a href="prices.php?search=${encodeURIComponent(rawQuery)}" style="color:inherit; text-decoration:none; display:block; padding: 10px 5px;">✂️ ${item.name} <span style="color:#aaa; font-size:12px;">(${item.cat})</span></a>`;
                        }
                        resultsList.appendChild(li);
                    });
                } else { 
                    resultsList.innerHTML = "<li style='padding: 10px 5px; color:#888;'>Ничего не найдено</li>"; 
                }
            } else {
                searchDropdown.style.display = "none";
            }
        });
    }
    // --- 7. КАРТОЧКИ ТОВАРОВ НА ГЛАВНОЙ (ПК) ---
    if (window.innerWidth > 768) {
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            let timer; let currentImgIndex = 0;
            const images = card.querySelectorAll('.card-img-wrapper > .prod-img'); 
            if (images.length > 1) {
                card.addEventListener('mouseenter', () => {
                    timer = setInterval(() => {
                        images[currentImgIndex].classList.remove('active');
                        currentImgIndex = (currentImgIndex + 1) % images.length;
                        images[currentImgIndex].classList.add('active');
                    }, 1500); 
                });
                card.addEventListener('mouseleave', () => {
                    clearInterval(timer);
                    images[currentImgIndex].classList.remove('active');
                    currentImgIndex = 0;
                    images[0].classList.add('active'); 
                });
            }
        });
    }
    // --- 8. ШТОРКА КОНТАКТОВ ---
    const contactTabBtn = document.getElementById("contact-tab-btn");
    const contactSheet = document.getElementById("contact-sheet");
    const contactOverlay = document.getElementById("contact-sheet-overlay");
    const closeZone = document.getElementById("close-sheet-zone");
    const megaContactBtn = document.getElementById("mega-contact-btn");
    
    if (contactSheet && contactOverlay) {
        function toggleSheet() {
            contactSheet.classList.toggle("active");
            contactOverlay.classList.toggle("active");
            if (contactTabBtn) contactTabBtn.classList.toggle("active"); 
            if (!splashScreen || splashScreen.style.display === 'none') {
                document.body.style.overflow = contactSheet.classList.contains("active") ? "hidden" : "";
            }
        }
        
        if(contactTabBtn) contactTabBtn.addEventListener("click", toggleSheet);
        if(megaContactBtn) megaContactBtn.addEventListener("click", toggleSheet);
        
        contactOverlay.addEventListener("click", toggleSheet);
        if(closeZone) closeZone.addEventListener("click", toggleSheet);
        
        let startY;
        contactSheet.addEventListener("touchstart", e => startY = e.touches[0].clientY, {passive: true});
        contactSheet.addEventListener("touchmove", e => { if (e.touches[0].clientY - startY > 50) toggleSheet(); }, {passive: true});
    }
    /* =========================================================================================
       9. СТРАНИЦА ТОВАРА (STORE.PHP) - ПК И МОБИЛКА (ZOOM ON HOVER) + ПЕРЕКЛЮЧЕНИЕ
    ========================================================================================= */
    const storeGallery = document.querySelector('.product-gallery');
    if (storeGallery) {
        const mainImgBox = document.querySelector('.main-img-box');
        const mainImgs = Array.from(document.querySelectorAll('.main-img-box img'));
        const dThumbs = document.querySelectorAll('.gallery-thumb-v');
        
        // --- 9.1 ПК Версия: Переключение по миниатюрам ---
        if (dThumbs.length > 0 && mainImgBox) {
            dThumbs.forEach(thumb => {
                thumb.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetImg = document.getElementById(targetId);
                    if (!targetImg) return;
                    
                    mainImgBox.style.transform = 'none';
                    mainImgs.forEach(i => {
                        i.style.transform = 'none';
                        i.classList.remove('active');
                    });
                    dThumbs.forEach(t => t.classList.remove('active'));
                    
                    targetImg.classList.add('active');
                    this.classList.add('active');
                });
            });
        }
        
        // --- 9.2 ПК Версия: Zoom on Hover ---
        if (mainImgBox) {
            const ZOOM_SCALE = 2; 
            mainImgBox.addEventListener('mouseenter', () => {
                const activeImg = document.querySelector('.main-img-box img.active');
                if (activeImg) {
                    activeImg.style.transition = 'transform 0.1s ease-out';
                    activeImg.style.transform = `scale(${ZOOM_SCALE})`;
                }
            });
            mainImgBox.addEventListener('mousemove', (e) => {
                const activeImg = document.querySelector('.main-img-box img.active');
                if (!activeImg) return;
                const rect = mainImgBox.getBoundingClientRect();
                const xPos = ((e.clientX - rect.left) / rect.width) * 100;
                const yPos = ((e.clientY - rect.top) / rect.height) * 100;
                activeImg.style.transformOrigin = `${xPos}% ${yPos}%`;
            });
            mainImgBox.addEventListener('mouseleave', () => {
                const activeImg = document.querySelector('.main-img-box img.active');
                if (activeImg) {
                    activeImg.style.transition = 'transform 0.3s ease-out';
                    activeImg.style.transform = 'scale(1)';
                    setTimeout(() => { activeImg.style.transformOrigin = '50% 50%'; }, 300);
                }
            });
        }
        // --- 9.3 МОБИЛЬНЫЙ ЗУМ (Щипок и возврат) ---
        const mPinchWrappers = document.querySelectorAll('.m-img-pinch-wrap img');
        mPinchWrappers.forEach(img => {
            let mScale = 1;
            let mStartDist = 0;
            let initialOriginX = 50;
            let initialOriginY = 50;
            
            img.addEventListener('touchstart', (e) => {
                if (e.touches.length === 2) {
                    e.preventDefault();
                    
                    const rect = img.getBoundingClientRect();
                    const centerX = (e.touches[0].clientX + e.touches[1].clientX) / 2;
                    const centerY = (e.touches[0].clientY + e.touches[1].clientY) / 2;
                    
                    initialOriginX = ((centerX - rect.left) / rect.width) * 100;
                    initialOriginY = ((centerY - rect.top) / rect.height) * 100;
                    
                    img.style.transformOrigin = `${initialOriginX}% ${initialOriginY}%`;
                    img.style.transition = 'none'; 
                    mStartDist = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                }
            }, { passive: false });
            
            img.addEventListener('touchmove', (e) => {
                if (e.touches.length === 2) {
                    e.preventDefault(); 
                    const currentDist = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                    
                    mScale = currentDist / mStartDist;
                    mScale = Math.min(Math.max(1, mScale), 3); 
                    
                    img.style.transform = `scale(${mScale})`;
                    img.style.zIndex = mScale > 1 ? '50' : '1';
                }
            }, { passive: false });
            
            img.addEventListener('touchend', (e) => {
                if (e.touches.length < 2) {
                    mScale = 1;
                    img.style.transition = 'transform 0.3s ease-out';
                    img.style.transform = 'scale(1)';
                    img.style.zIndex = '1';
                    setTimeout(() => { img.style.transformOrigin = '50% 50%'; }, 300);
                }
            });
        });
        // --- 9.4 ПРОКРУТКА МИНИАТЮР (Если больше 7 штук) ---
        const thumbsInner = document.querySelector('.thumbs-inner');
        const scrollBtn = document.getElementById('thumb-scroll');
        const allThumbsArray = document.querySelectorAll('.gallery-thumb-v');
        if (thumbsInner && scrollBtn && allThumbsArray.length > 7) {
            scrollBtn.classList.add('visible'); 
            
            let currentShift = 0;
            const maxClicks = allThumbsArray.length - 7;
            
            const updateThumbScroll = () => {
                const singleThumbHeight = allThumbsArray[0].getBoundingClientRect().height;
                const shiftPixels = currentShift * (singleThumbHeight + 10); // 10px - это gap
                thumbsInner.style.transform = `translateY(-${shiftPixels}px)`;
            };
            scrollBtn.addEventListener('click', () => {
                currentShift++;
                if (currentShift > maxClicks) {
                    currentShift = 0; 
                }
                updateThumbScroll();
            });
            window.addEventListener('resize', updateThumbScroll);
        }
    }
});