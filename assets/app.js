/* ==========================================================
   EVORAA CLOTHING - DYNAMIC CLIENT ENGINE (JS)
   Links presentation components to PHP/MySQL Administrative Suite API
   ========================================================== */

// --- 1. FRONTEND APP STATE MANAGER ---
class AppState {
    constructor() {
        this.products = [];
        this.announcements = [];
        this.banners = [];
        // Logged-in customer (persisted across page loads)
        this.currentUser = this.loadLocalItem("evoraa_current_user", null);
        this.cart = this.loadLocalItem("evoraa_cart", []);
        this.wishlist = this.loadLocalItem("evoraa_wishlist", []);
        this.appliedDiscount = this.loadLocalItem("evoraa_applied_discount", false);
        this.theme = localStorage.getItem("evoraa_theme") || "light";
        this.activeCategory = "ALL";
        this.activeView = "storefront";
        this.searchQuery = "";

        // Modal Selection State
        this.selectedProduct = null;
        this.selectedProductSize = null;
        this.selectedProductImageIndex = 0;
    }

    loadLocalItem(key, defaultValue) {
        const val = localStorage.getItem(key);
        if (!val) return defaultValue;
        try {
            return JSON.parse(val);
        } catch (e) {
            return defaultValue;
        }
    }

    saveLocalItem(key, val) {
        localStorage.setItem(key, JSON.stringify(val));
    }

    syncLocalState() {
        this.saveLocalItem("evoraa_cart", this.cart);
        this.saveLocalItem("evoraa_wishlist", this.wishlist);
        this.saveLocalItem("evoraa_applied_discount", this.appliedDiscount);
    }
}

const state = new AppState();

// --- 2. MAP DATABASE COLUMNS TO FRONTEND VARIABLES ---
function mapDbProduct(p) {
    return {
        id: parseInt(p.id),
        productCode: p.product_code,
        name: p.name,
        category: p.category,
        description: p.description,
        images: [p.image1, p.image2, p.image3, p.image4].filter(img => img && img.trim() !== ""),
        originalPrice: parseFloat(p.original_price),
        discountPrice: parseFloat(p.discount_price),
        discountActive: parseInt(p.discount_active) === 1,
        offerBadge: p.offer_badge,
        stock: {
            XS: parseInt(p.stock_xs),
            S: parseInt(p.stock_s),
            M: parseInt(p.stock_m),
            L: parseInt(p.stock_l),
            XL: parseInt(p.stock_xl)
        }
    };
}

// --- 3. DYNAMIC SERVER INTERACTION (AJAX FETCH) ---
async function fetchStorefrontData() {
    try {
        const res = await fetch("api.php?action=get_shop_data");
        if (!res.ok) throw new Error("HTTP error " + res.status);
        const data = await res.json();

        if (data.products) {
            state.products = data.products.map(mapDbProduct);
        }
        if (data.announcements) {
            state.announcements = data.announcements;
        }
        if (data.banners) {
            state.banners = data.banners;
        }

        // Populate and render UI
        initAnnouncementBar();
        renderHeroCarousel();
        renderProductGrid();
    } catch (e) {
        console.error("Storefront API failed: ", e);
        showCustomAlert("Connection to EVORAA servers interrupted.");
    }
}

// --- 4. CINEMATIC PRE-LOADER TIMING ENGINE (PREMIUM REVEAL) ---
function runCinematicPreloader() {
    const loader = document.getElementById("editorial-preloader");
    const content = document.getElementById("preloader-content");

    // Website main components structure
    const storefront = document.getElementById("storefront-view");

    const played = sessionStorage.getItem("evoraa_preloader_played");
    if (played === "true") {
        if (loader) loader.classList.add("hidden");
        if (storefront) storefront.classList.add("view-active");
        return;
    }

    // Website initial display state hidden-la vachikiren
    if (storefront) {
        storefront.classList.remove("view-active");
        storefront.classList.add("view-hidden");
    }

    // Step 1: Fade-in brand details and slogan
    setTimeout(() => {
        if (content) content.classList.add("fade-in");
    }, 400);

    // Step 2: Fade-out text layout smoothly before section split
    setTimeout(() => {
        if (content) {
            content.classList.remove("fade-in");
            content.classList.add("fade-out");
        }
    }, 2200);

    // Step 3: Trigger fluid split / fade transition window
    setTimeout(() => {
        if (loader) loader.classList.add("hidden");

        // Loader marayum pothe, website elements keela irunthu smooth-ah reveal aagum
        if (storefront) {
            storefront.classList.remove("view-hidden");
            storefront.classList.add("view-active");
        }
    }, 2800);

    // Step 4: Session allocation to release active locks
    setTimeout(() => {
        sessionStorage.setItem("evoraa_preloader_played", "true");
    }, 3600);
}

// --- 5. UI VIEW ROUTER ---
const VIEWS = {
    storefront: document.getElementById("storefront-view"),
    checkout: document.getElementById("checkout-view"),
    checkoutSuccess: document.getElementById("checkout-success-view"),
    contact: document.getElementById("contact-view"),
    policy: document.getElementById("policy-view"),
    account: document.getElementById("account-view")
};

function navigateTo(viewName) {
    state.activeView = viewName;

    Object.keys(VIEWS).forEach(key => {
        const el = VIEWS[key];
        if (!el) return;
        if (key === viewName) {
            el.style.display = "block";
            el.classList.add("view-active");
            el.classList.remove("view-hidden");
        } else {
            el.style.display = "none";
            el.classList.add("view-hidden");
            el.classList.remove("view-active");
        }
    });

    window.scrollTo({ top: 0, behavior: "smooth" });

    // Category sub-navigation bar display rule
    const catNavbar = document.querySelector(".category-navbar");
    if (catNavbar) {
        catNavbar.style.display = (viewName === "storefront") ? "flex" : "none";
    }

    // Refresh view states
    if (viewName === "storefront") {
        renderProductGrid();
    } else if (viewName === "checkout") {
        renderCheckoutView();
    }
}

// --- 5b. CUSTOMER ACCOUNT DASHBOARD ---
function updateProfileButton() {
    const btn = document.getElementById('profile-btn');
    if (!btn) return;
    if (state.currentUser) {
        btn.title = state.currentUser.name;
        btn.innerHTML = `<i class="fa-solid fa-user" style="color:var(--color-primary)"></i>`;
    } else {
        btn.innerHTML = `<i class="fa-regular fa-user"></i>`;
    }
}

async function renderAccountDashboard() {
    const container = document.getElementById('account-view');
    if (!container) return;

    if (!state.currentUser) {
        container.innerHTML = `<div style="text-align:center;padding:80px 20px;"><p style="color:var(--color-text-muted)">Please log in to view your account.</p><button class="btn-premium" onclick="document.getElementById('profile-btn').click()" style="margin-top:20px;">LOG IN</button></div>`;
        return;
    }

    container.innerHTML = `<div style="max-width:900px;margin:40px auto;padding:0 20px;"><div style="text-align:center;margin-bottom:40px;"><h2 style="font-family:var(--font-serif-lux);font-size:28px;letter-spacing:3px;text-transform:uppercase;">My Account</h2><p style="color:var(--color-text-muted);margin-top:6px;">Welcome back, ${state.currentUser.name}</p></div><div id="account-content"><p style="text-align:center;color:var(--color-text-muted);">Loading your orders...</p></div></div>`;

    try {
        const res = await fetch('api.php?action=get_customer_dashboard', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                customer_id: state.currentUser.id,
                email: state.currentUser.email,
                wishlist_ids: state.wishlist
            })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);

        const orders = data.orders || [];
        const wishlist = data.wishlist || [];

        // Status badge colors
        const statusColor = { 'Pending': '#ecdb54', 'Receipt Uploaded': '#5aa0ff', 'Approved': '#52a373', 'Shipped': '#be7eff', 'Cancelled': '#cc5a5a' };

        // Build orders HTML
        let ordersHtml = '';
        if (orders.length === 0) {
            ordersHtml = `<p style="color:var(--color-text-muted);text-align:center;padding:30px 0;">No orders yet. Start shopping!</p>`;
        } else {
            ordersHtml = orders.map(o => {
                const color = statusColor[o.status] || '#8c8c8c';
                const items = (o.items || []).map(i => `<span style="font-size:12px;">${i.product_name} [${i.size}] ×${i.qty}</span>`).join('<br>');
                return `<div style="border:1px solid var(--color-border);padding:20px;margin-bottom:15px;background:var(--color-surface);">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
                        <div>
                            <div style="font-size:11px;color:var(--color-text-muted);margin-bottom:4px;">${o.created_at}</div>
                            <div style="font-size:12px;">${items}</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="color:${color};font-size:11px;font-weight:600;text-transform:uppercase;border:1px solid ${color};padding:3px 10px;margin-bottom:8px;">${o.status}</div>
                            <div style="font-family:var(--font-serif-lux);color:var(--color-primary);">${parseFloat(o.total).toLocaleString()} LKR</div>
                        </div>
                    </div>
                </div>`;
            }).join('');
        }

        // Build wishlist HTML
        let wishlistHtml = '';
        if (wishlist.length === 0) {
            wishlistHtml = `<p style="color:var(--color-text-muted);text-align:center;padding:30px 0;">Your wishlist is empty.</p>`;
        } else {
            wishlistHtml = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:15px;">` +
                wishlist.map(p => {
                    const price = p.discount_active == 1 ? p.discount_price : p.original_price;
                    return `<div style="border:1px solid var(--color-border);background:var(--color-surface);cursor:pointer;" onclick="openQuickView(${p.id})">
                        <img src="${p.image1}" style="width:100%;height:200px;object-fit:cover;" onerror="this.style.display='none'">
                        <div style="padding:10px;">
                            <div style="font-size:12px;font-weight:500;">${p.name}</div>
                            <div style="font-size:11px;color:var(--color-primary);margin-top:4px;">${parseFloat(price).toLocaleString()} LKR</div>
                        </div>
                    </div>`;
                }).join('') + `</div>`;
        }

        document.getElementById('account-content').innerHTML = `
            <div style="margin-bottom:40px;">
                <h3 style="font-family:var(--font-serif-lux);font-size:18px;letter-spacing:2px;text-transform:uppercase;border-bottom:1px solid var(--color-border);padding-bottom:12px;margin-bottom:20px;">My Orders</h3>
                ${ordersHtml}
            </div>
            <div style="margin-bottom:40px;">
                <h3 style="font-family:var(--font-serif-lux);font-size:18px;letter-spacing:2px;text-transform:uppercase;border-bottom:1px solid var(--color-border);padding-bottom:12px;margin-bottom:20px;">My Wishlist</h3>
                ${wishlistHtml}
            </div>
            <div style="text-align:center;margin-top:20px;">
                <button onclick="logoutCustomer()" style="background:none;border:1px solid var(--color-error);color:var(--color-error);padding:10px 24px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:2px;cursor:pointer;">LOG OUT</button>
            </div>`;
    } catch (err) {
        document.getElementById('account-content').innerHTML = `<p style="color:var(--color-error);text-align:center;">Could not load account data. Please try again.</p>`;
    }
}

function logoutCustomer() {
    state.currentUser = null;
    localStorage.removeItem("evoraa_current_user");
    updateProfileButton();
    navigateTo('storefront');
}

// --- 6. HERO BANNER CAROUSEL ---
function renderHeroCarousel() {
    const carousel = document.getElementById("hero-carousel");
    const heroSection = document.getElementById("hero-carousel-section");
    const heroContent = document.querySelector(".hero-content");
    const heroOverlay = document.querySelector(".hero-overlay");
    if (!carousel) return;

    const banners = state.banners || [];
    if (banners.length === 0) {
        // No banners — show default static hero text
        if (heroContent) heroContent.style.display = "flex";
        return;
    }

    // Banners exist — hide the static hero text to avoid overlap
    if (heroContent) heroContent.style.display = "none";
    if (heroOverlay) heroOverlay.style.display = "none";

    // Build slides — full cover, z-index 0 so they sit behind everything
    carousel.innerHTML = "";
    carousel.style.cssText = "position:absolute;inset:0;z-index:0;";

    banners.forEach((b, i) => {
        const imgSrc = b.image_path || "";
        const slide = document.createElement("div");
        slide.className = "hero-slide" + (i === 0 ? " active" : "");
        slide.style.cssText = `
            position: absolute; inset: 0;
            background: url('${imgSrc}') center/cover no-repeat;
            opacity: ${i === 0 ? "1" : "0"};
            transition: opacity 1s ease;
        `;
        carousel.appendChild(slide);
    });

    // Auto-rotate banners every 5s
    if (banners.length > 1) {
        let current = 0;
        setInterval(() => {
            const slides = carousel.querySelectorAll(".hero-slide");
            slides[current].style.opacity = "0";
            current = (current + 1) % slides.length;
            slides[current].style.opacity = "1";
        }, 5000);
    }
}

// --- 7. TOP ANNOUNCEMENT BAR ROTATOR ---
let announcementInterval = null;
let currentAnnouncementIndex = 0;

function initAnnouncementBar() {
    const container = document.getElementById("announcement-slider");
    const prevBtn = document.getElementById("prev-announcement-btn");
    const nextBtn = document.getElementById("next-announcement-btn");

    if (!container) return;

    function renderActiveAnnouncement() {
        container.innerHTML = "";
        const list = state.announcements.length ? state.announcements : ["WELCOME TO EVORAA CLOTHING"];

        list.forEach((text, index) => {
            const activeClass = index === currentAnnouncementIndex ? "active" : "";
            const div = document.createElement("div");
            div.className = `announcement-text ${activeClass}`;
            div.textContent = text;
            container.appendChild(div);
        });
    }

    function slideNext() {
        const len = state.announcements.length || 1;
        currentAnnouncementIndex = (currentAnnouncementIndex + 1) % len;
        renderActiveAnnouncement();
    }

    function slidePrev() {
        const len = state.announcements.length || 1;
        currentAnnouncementIndex = (currentAnnouncementIndex - 1 + len) % len;
        renderActiveAnnouncement();
    }

    prevBtn?.addEventListener("click", () => {
        clearInterval(announcementInterval);
        slidePrev();
        startAutoRotation();
    });

    nextBtn?.addEventListener("click", () => {
        clearInterval(announcementInterval);
        slideNext();
        startAutoRotation();
    });

    function startAutoRotation() {
        announcementInterval = setInterval(slideNext, 5000);
    }

    renderActiveAnnouncement();
    startAutoRotation();
}

// --- 7. THEME MANAGER ---
function initThemeManager() {
    const toggleBtn = document.getElementById("theme-toggle-btn");
    const icon = document.getElementById("theme-icon");

    function applyTheme(theme) {
        document.documentElement.setAttribute("data-theme", theme);
        state.theme = theme;
        localStorage.setItem("evoraa_theme", theme);
        if (icon) {
            icon.className = theme === "dark" ? "fa-solid fa-sun" : "fa-solid fa-moon";
        }
    }

    toggleBtn?.addEventListener("click", () => {
        applyTheme(state.theme === "light" ? "dark" : "light");
    });

    applyTheme(state.theme);
}

// --- 8. PRODUCT GRID CATALOGUE DISPLAY ---
function renderProductGrid() {
    const grid = document.getElementById("main-product-grid");
    const filterStatusContainer = document.getElementById("filter-status-container");
    const filterStatusText = document.getElementById("filter-status-text");
    const collectionTitle = document.getElementById("active-collection-title");

    if (!grid) return;
    grid.innerHTML = "";

    let items = state.products;

    // Filter by category selection
    if (state.activeCategory !== "ALL") {
        items = items.filter(p => p.category.toUpperCase() === state.activeCategory.toUpperCase());
        filterStatusContainer?.classList.remove("view-hidden");
        if (filterStatusText) filterStatusText.textContent = `Category: ${state.activeCategory}`;
        if (collectionTitle) collectionTitle.textContent = state.activeCategory;
    } else {
        filterStatusContainer?.classList.add("view-hidden");
        if (collectionTitle) collectionTitle.textContent = "CURATED CATALOGUE";
    }

    // Filter by active search query
    if (state.searchQuery.trim() !== "") {
        const query = state.searchQuery.toLowerCase();
        items = items.filter(p =>
            p.name.toLowerCase().includes(query) ||
            p.description.toLowerCase().includes(query) ||
            p.category.toLowerCase().includes(query)
        );
        filterStatusContainer?.classList.remove("view-hidden");
        if (filterStatusText) filterStatusText.textContent = `Search query: "${state.searchQuery}"`;
    }

    // Zero State
    if (items.length === 0) {
        grid.innerHTML = `
            <div class="empty-storefront" style="grid-column: 1 / -1; text-align: center; padding: 80px 20px;">
                <i class="fa-solid fa-shirt" style="font-size: 40px; color: hsl(var(--hsl-accent)); margin-bottom: 15px;"></i>
                <h4 class="empty-storefront-title" style="font-family: var(--font-serif-lux); font-size: 22px; font-weight: 400; margin-bottom: 8px;">No Garments Located</h4>
                <p style="font-size: 13px; color: hsl(var(--hsl-text-secondary));">We are tailoring a new release drop. Please check again shortly.</p>
            </div>
        `;
        return;
    }

    items.forEach(product => {
        const card = document.createElement("article");
        card.className = "product-card reveal-element";

        const isWishlisted = state.wishlist.includes(product.id) ? "active" : "";
        const heartIcon = state.wishlist.includes(product.id) ? "fa-solid fa-heart" : "fa-regular fa-heart";

        // Discount details
        let pricingHtml = "";
        if (product.discountActive && product.discountPrice < product.originalPrice) {
            pricingHtml = `
                <span class="price-discount">${product.discountPrice.toLocaleString()} LKR</span>
                <span class="price-original">${product.originalPrice.toLocaleString()} LKR</span>
            `;
        } else {
            pricingHtml = `<span class="price-regular">${product.originalPrice.toLocaleString()} LKR</span>`;
        }

        // Badge markup
        let badgeHtml = "";
        if (product.offerBadge && product.offerBadge.trim() !== "") {
            const badgeClass = product.offerBadge.toUpperCase() === "SALE" ? "badge-sale" : "";
            badgeHtml = `<span class="badge-offer ${badgeClass}">${product.offerBadge}</span>`;
        }

        card.innerHTML = `
            <div class="product-image-container">
                ${badgeHtml}
                <button class="wishlist-heart-btn ${isWishlisted}" data-id="${product.id}" aria-label="Add to wishlist">
                    <i class="${heartIcon}"></i>
                </button>
                <img src="${product.images[0] || 'assets/logo.png'}" class="product-img scroll-zoom-img" alt="${product.name}" onerror="this.src='assets/logo.png'">
                <div class="product-quick-buy-overlay">
                    <button class="quick-view-btn" data-id="${product.id}">QUICK VIEW</button>
                </div>
            </div>
            <div class="product-details">
                <div class="product-category-tag">${product.category}</div>
                <h4 class="product-name">${product.name}</h4>
                <div class="price-container">
                    ${pricingHtml}
                </div>
            </div>
        `;

        grid.appendChild(card);
    });

    bindCatalogInteractions();
    initScrollAnimations();
}

function bindCatalogInteractions() {
    // Quick view details buttons
    document.querySelectorAll(".quick-view-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = parseInt(btn.getAttribute("data-id"));
            openQuickView(id);
        });
    });

    // Wishlist hover heart triggers
    document.querySelectorAll(".wishlist-heart-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const id = parseInt(btn.getAttribute("data-id"));
            toggleWishlistItem(id);
            renderProductGrid();
        });
    });
}

// --- 9. WISHLIST DATA ARRAY OPS ---
function toggleWishlistItem(id) {
    const idx = state.wishlist.indexOf(id);
    if (idx === -1) {
        state.wishlist.push(id);
        showCustomAlert("Garment pinned to wishlist.");
    } else {
        state.wishlist.splice(idx, 1);
        showCustomAlert("Garment removed from wishlist.");
    }
    state.syncLocalState();
}

// --- 10. PRODUCT DETAIL QUICKVIEW LAYOUT ---
const quickviewModal = document.getElementById("product-quickview-modal");

function openQuickView(id) {
    const product = state.products.find(p => p.id === id);
    if (!product) return;

    state.selectedProduct = product;
    state.selectedProductSize = null;
    state.selectedProductImageIndex = 0;

    const mainImg = document.getElementById("quickview-main-image");
    const category = document.getElementById("quickview-category");
    const name = document.getElementById("quickview-name");
    const desc = document.getElementById("quickview-desc");
    const priceBox = document.getElementById("quickview-price-container");
    const sizeGrid = document.getElementById("quickview-size-selector");
    const galleryNav = document.getElementById("quickview-gallery-nav");
    const heartBtn = document.getElementById("quickview-wishlist-btn");
    const heartIcon = document.getElementById("quickview-wishlist-icon");

    if (mainImg) mainImg.src = product.images[0] || "assets/logo.png";
    if (category) category.textContent = product.category;
    if (name) name.textContent = product.name;
    if (desc) desc.textContent = product.description;

    // Prices load
    if (priceBox) {
        if (product.discountActive && product.discountPrice < product.originalPrice) {
            priceBox.innerHTML = `
                <span class="price-discount" style="font-size: 20px;">${product.discountPrice.toLocaleString()} LKR</span>
                <span class="price-original" style="font-size: 15px; margin-left: 10px;">${product.originalPrice.toLocaleString()} LKR</span>
            `;
        } else {
            priceBox.innerHTML = `<span class="price-regular" style="font-size: 20px;">${product.originalPrice.toLocaleString()} LKR</span>`;
        }
    }

    // Gallery navigation dots
    if (galleryNav) {
        galleryNav.innerHTML = "";
        product.images.forEach((img, idx) => {
            const dot = document.createElement("button");
            dot.className = `gallery-dot ${idx === 0 ? 'active' : ''}`;
            dot.addEventListener("click", () => {
                document.querySelectorAll(".gallery-dot").forEach(d => d.classList.remove("active"));
                dot.classList.add("active");
                if (mainImg) mainImg.src = img;
                state.selectedProductImageIndex = idx;
            });
            galleryNav.appendChild(dot);
        });
    }

    // Dynamic stock checking sizing matrix
    if (sizeGrid) {
        sizeGrid.innerHTML = "";
        ["XS", "S", "M", "L", "XL"].forEach(size => {
            const stockCount = product.stock[size] || 0;
            const sizeBtn = document.createElement("button");
            sizeBtn.className = "size-btn";

            if (stockCount <= 0) {
                sizeBtn.classList.add("out-of-stock");
                sizeBtn.innerHTML = `<span>${size}</span><span class="size-stock-label">OUT</span>`;
            } else {
                sizeBtn.innerHTML = `<span>${size}</span><span class="size-stock-label">${stockCount} left</span>`;
                sizeBtn.addEventListener("click", () => {
                    document.querySelectorAll(".size-btn").forEach(b => b.classList.remove("active"));
                    sizeBtn.classList.add("active");
                    state.selectedProductSize = size;
                });
            }
            sizeGrid.appendChild(sizeBtn);
        });
    }

    // Wishlist Toggle in Modal
    if (heartBtn && heartIcon) {
        if (state.wishlist.includes(product.id)) {
            heartBtn.classList.add("active");
            heartIcon.className = "fa-solid fa-heart";
        } else {
            heartBtn.classList.remove("active");
            heartIcon.className = "fa-regular fa-heart";
        }
    }

    quickviewModal?.classList.add("active");
}

function closeQuickView() {
    quickviewModal?.classList.remove("active");
    state.selectedProduct = null;
    state.selectedProductSize = null;
}

// --- 11. SHOPPING BAG SLIDING DRAWER ---
const cartOverlay = document.getElementById("shopping-cart-overlay");
const cartBadge = document.getElementById("cart-count-badge");
const cartDrawerList = document.getElementById("cart-drawer-items-list");
const cartDrawerSubtotal = document.getElementById("cart-drawer-subtotal");

function openCartDrawer() {
    renderCartDrawer();
    cartOverlay?.classList.add("active");
}

function closeCartDrawer() {
    cartOverlay?.classList.remove("active");
}

function renderCartDrawer() {
    if (!cartDrawerList) return;
    cartDrawerList.innerHTML = "";

    let totalQty = 0;
    let subtotal = 0;

    state.cart.forEach(item => {
        totalQty += item.qty;
        const product = state.products.find(p => p.id === item.productId);
        if (!product) return;

        const price = (product.discountActive && product.discountPrice < product.originalPrice) ? product.discountPrice : product.originalPrice;
        const lineTotal = price * item.qty;
        subtotal += lineTotal;

        const row = document.createElement("div");
        row.className = "cart-item";
        row.innerHTML = `
            <img src="${product.images[0] || 'assets/logo.png'}" class="cart-item-img" alt="${product.name}" onerror="this.src='assets/logo.png'">
            <div class="cart-item-info">
                <h4 class="cart-item-name">${product.name}</h4>
                <div class="cart-item-meta">Size: ${item.size}</div>
                <div class="cart-item-qty-price">
                    <div class="qty-selector">
                        <button class="qty-btn dec-qty-btn" data-id="${item.productId}" data-size="${item.size}">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                        <span class="qty-val">${item.qty}</span>
                        <button class="qty-btn inc-qty-btn" data-id="${item.productId}" data-size="${item.size}">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <span class="cart-item-price">${lineTotal.toLocaleString()} LKR</span>
                </div>
                <button class="cart-item-remove" data-id="${item.productId}" data-size="${item.size}">Remove</button>
            </div>
        `;
        cartDrawerList.appendChild(row);
    });

    if (cartBadge) cartBadge.textContent = totalQty;
    if (cartDrawerSubtotal) cartDrawerSubtotal.textContent = `${subtotal.toLocaleString()} LKR`;

    // Drawer Empty State
    if (state.cart.length === 0) {
        cartDrawerList.innerHTML = `
            <div class="cart-empty-state" style="text-align:center; padding:50px 20px;">
                <i class="fa-solid fa-bag-shopping" style="font-size:36px; color:hsl(var(--hsl-accent)); margin-bottom:15px; display:block;"></i>
                <p style="font-size:13px; color:hsl(var(--hsl-text-secondary)); margin-bottom:15px;">Your luxury shopping bag is empty.</p>
                <button class="btn-premium" id="cart-continue-shopping-btn" style="font-size:10px; width:auto; padding:10px 25px; margin:0 auto;">SHOP THE DROP</button>
            </div>
        `;
        document.getElementById("cart-continue-shopping-btn")?.addEventListener("click", () => {
            closeCartDrawer();
            navigateTo("storefront");
        });
    }

    bindCartDrawerActions();
}

function addToCart(productId, size, qty = 1) {
    if (!size) {
        showCustomAlert("Please select a garment size.");
        return;
    }

    const product = state.products.find(p => p.id === productId);
    if (!product) return;

    const availableStock = product.stock[size] || 0;
    const existing = state.cart.find(c => c.productId === productId && c.size === size);

    if (existing) {
        if (existing.qty + qty > availableStock) {
            showCustomAlert(`Stock cap reached. Only ${availableStock} units left in size ${size}.`);
            return;
        }
        existing.qty += qty;
    } else {
        if (qty > availableStock) {
            showCustomAlert(`Stock cap reached. Only ${availableStock} units left in size ${size}.`);
            return;
        }
        state.cart.push({ productId, size, qty });
    }

    state.syncLocalState();
    closeQuickView();
    renderCartDrawer();
    openCartDrawer();
    showCustomAlert("Garment added to bag.");
}

function bindCartDrawerActions() {
    // Plus increment Qty
    document.querySelectorAll(".inc-qty-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = parseInt(btn.getAttribute("data-id"));
            const size = btn.getAttribute("data-size");
            const product = state.products.find(p => p.id === id);
            const item = state.cart.find(c => c.productId === id && c.size === size);

            if (item && product) {
                const limit = product.stock[size] || 0;
                if (item.qty + 1 > limit) {
                    showCustomAlert(`Stock cap reached. Only ${limit} units left in size ${size}.`);
                    return;
                }
                item.qty++;
                state.syncLocalState();
                renderCartDrawer();
            }
        });
    });

    // Minus decrement Qty
    document.querySelectorAll(".dec-qty-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = parseInt(btn.getAttribute("data-id"));
            const size = btn.getAttribute("data-size");
            const item = state.cart.find(c => c.productId === id && c.size === size);

            if (item) {
                item.qty--;
                if (item.qty <= 0) {
                    state.cart = state.cart.filter(c => !(c.productId === id && c.size === size));
                }
                state.syncLocalState();
                renderCartDrawer();
            }
        });
    });

    // Remove item fully
    document.querySelectorAll(".cart-item-remove").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = parseInt(btn.getAttribute("data-id"));
            const size = btn.getAttribute("data-size");
            state.cart = state.cart.filter(c => !(c.productId === id && c.size === size));
            state.syncLocalState();
            renderCartDrawer();
            showCustomAlert("Garment removed from shopping bag.");
        });
    });
}

// --- 12. CHECKOUT OPERATIONS & SECURE SERVER SUBMISSION ---
let selectedDeliveryTier = "Standard"; // Standard / Express
let selectedPaymentMethod = "COD"; // COD / Bank Transfer

function renderCheckoutView() {
    const list = document.getElementById("checkout-summary-items-list");
    const subtotalText = document.getElementById("checkout-calc-subtotal");
    const shippingText = document.getElementById("checkout-calc-shipping");
    const totalText = document.getElementById("checkout-calc-total");

    if (!list) return;
    list.innerHTML = "";

    if (state.cart.length === 0) {
        list.innerHTML = `<p style="font-size: 13px; color: hsl(var(--hsl-text-secondary));">No items selected.</p>`;
        if (subtotalText) subtotalText.textContent = "0 LKR";
        if (totalText) totalText.textContent = "0 LKR";
        return;
    }

    let subtotal = 0;

    state.cart.forEach(item => {
        const product = state.products.find(p => p.id === item.productId);
        if (!product) return;

        const price = (product.discountActive && product.discountPrice < product.originalPrice) ? product.discountPrice : product.originalPrice;
        const lineTotal = price * item.qty;
        subtotal += lineTotal;

        const row = document.createElement("div");
        row.className = "checkout-summary-item";
        row.style.display = "flex";
        row.style.justifyContent = "space-between";
        row.style.fontSize = "12px";
        row.style.marginBottom = "8px";
        row.style.color = "hsl(var(--hsl-text-secondary))";
        row.innerHTML = `
            <span>${product.name} (x${item.qty}) - Size: ${item.size}</span>
            <span>${lineTotal.toLocaleString()} LKR</span>
        `;
        list.appendChild(row);
    });

    // Discount calculations
    let discount = 0;
    if (state.appliedDiscount) {
        discount = Math.round(subtotal * 0.10);
        const discRow = document.createElement("div");
        discRow.className = "checkout-summary-item";
        discRow.style.display = "flex";
        discRow.style.justifyContent = "space-between";
        discRow.style.fontSize = "12px";
        discRow.style.marginBottom = "8px";
        discRow.style.color = "hsl(var(--hsl-accent-dark))";
        discRow.innerHTML = `
            <span>Mailing list welcome discount (10%)</span>
            <span>-${discount.toLocaleString()} LKR</span>
        `;
        list.appendChild(discRow);
    }

    // Shipping calculations (free above 15000)
    let shipping = 0;
    if (subtotal < 15000) {
        shipping = selectedDeliveryTier === "Express" ? 700 : 350;
    }

    const total = subtotal + shipping - discount;

    if (subtotalText) subtotalText.textContent = `${subtotal.toLocaleString()} LKR`;
    if (shippingText) shippingText.textContent = shipping === 0 ? "FREE" : `${shipping.toLocaleString()} LKR`;
    if (totalText) totalText.textContent = `${total.toLocaleString()} LKR`;
}

function initCheckoutFlow() {
    const stdBtn = document.getElementById("ship-standard-btn");
    const expBtn = document.getElementById("ship-express-btn");

    const codBtn = document.getElementById("pay-cod-btn");
    const transferBtn = document.getElementById("pay-transfer-btn");
    const bankPanel = document.getElementById("bank-details-panel");
    const placeOrderBtn = document.getElementById("complete-checkout-btn");

    stdBtn?.addEventListener("click", () => {
        stdBtn.classList.add("active");
        expBtn?.classList.remove("active");
        selectedDeliveryTier = "Standard";
        renderCheckoutView();
    });

    expBtn?.addEventListener("click", () => {
        expBtn.classList.add("active");
        stdBtn?.classList.remove("active");
        selectedDeliveryTier = "Express";
        renderCheckoutView();
    });

    codBtn?.addEventListener("click", () => {
        codBtn.classList.add("active");
        transferBtn?.classList.remove("active");
        if (bankPanel) bankPanel.style.display = "none";
        selectedPaymentMethod = "COD";
    });

    transferBtn?.addEventListener("click", () => {
        transferBtn.classList.add("active");
        codBtn?.classList.remove("active");
        if (bankPanel) bankPanel.style.display = "block";
        selectedPaymentMethod = "Bank Transfer";
    });

    // --- FIX: Register / Login Tab Switcher ---
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = this.getAttribute('data-tab');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            if (tab === 'login') {
                document.getElementById('login-form').style.display = 'block';
                document.getElementById('register-form').style.display = 'none';
            } else if (tab === 'register') {
                document.getElementById('login-form').style.display = 'none';
                document.getElementById('register-form').style.display = 'block';
            }
        });
    });

    // --- FIX: Register Form Submit ---
    document.getElementById('register-form')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const name = document.getElementById('register-name').value.trim();
        const email = document.getElementById('register-email').value.trim();
        const password = document.getElementById('register-password').value;
        const successMsg = document.getElementById('register-success-msg');
        try {
            const res = await fetch('api.php?action=register_customer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, password })
            });
            const data = await res.json();
            if (data.success) {
                if (successMsg) {
                    successMsg.textContent = '✓ Account created! You can now log in.';
                    successMsg.style.display = 'block';
                }
                this.reset();
                // Switch to login tab after 1.5s
                setTimeout(() => {
                    document.querySelector('.tab-btn[data-tab="login"]')?.click();
                    if (successMsg) successMsg.style.display = 'none';
                }, 1500);
            } else {
                alert(data.error || 'Registration failed. Please try again.');
            }
        } catch (err) {
            console.error(err);
            alert('Network error during registration.');
        }
    });

    document.getElementById('login-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        const payload = { email, password };
        try {
            const res = await fetch('api.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                if (data.role === 'admin') {
                    // Admin: open admin panel in new tab, stay on main site
                    window.open(data.redirect, '_blank');
                    document.getElementById('profile-modal-container')?.classList.remove('active');
                } else {
                    // Save customer session
                    state.currentUser = { id: data.id, name: data.name, email: data.email };
                    localStorage.setItem("evoraa_current_user", JSON.stringify(state.currentUser));
                    document.getElementById('profile-modal-container')?.classList.remove('active');
                    updateProfileButton();
                    navigateTo('account');
                }
            } else {
                alert(data.error || 'Login failed');
            }
        } catch (err) {
            console.error(err);
            alert('Network error while logging in');
        }
    });

    // Handle place order POST trigger
    placeOrderBtn?.addEventListener("click", async () => {
        const email = document.getElementById("checkout-email").value.trim();
        const phone = document.getElementById("checkout-phone").value.trim();
        const name = document.getElementById("checkout-name").value.trim();
        const address = document.getElementById("checkout-address").value.trim();
        const city = document.getElementById("checkout-city").value.trim();
        const zip = document.getElementById("checkout-zip").value.trim();

        if (!email || !phone || !name || !address || !city) {
            showCustomAlert("Please fill in all contact & shipping details.");
            return;
        }

        if (state.cart.length === 0) {
            showCustomAlert("Your shopping bag is empty.");
            return;
        }

        placeOrderBtn.disabled = true;
        placeOrderBtn.textContent = "REGISTERING TRANSACTION SPECIFICATIONS...";

        const orderData = {
            name,
            email,
            phone,
            address,
            city,
            zip,
            delivery_tier: selectedDeliveryTier,
            payment_method: selectedPaymentMethod,
            cart: state.cart.map(item => ({
                id: item.productId,
                size: item.size,
                qty: item.qty
            }))
        };

        try {
            const res = await fetch("api.php?action=checkout", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(orderData)
            });

            const data = await res.json();

            if (data.success) {
                // Clear state
                state.cart = [];
                state.syncLocalState();
                renderCartDrawer();

                // Load success view
                document.getElementById("success-order-hash").textContent = data.order_hash;
                const instructions = document.getElementById("success-transfer-instructions");

                if (data.payment_method === "Bank Transfer" && instructions) {
                    instructions.style.display = "block";
                    setupReceiptUploader("success-receipt-upload", "success-receipt-filename", "success-submit-receipt-btn", "success-receipt-msg", data.order_hash);
                } else if (instructions) {
                    instructions.style.display = "none";
                }

                navigateTo("checkoutSuccess");
            } else {
                showCustomAlert(data.error || "An error occurred during checkout processing.");
            }
        } catch (e) {
            console.error(e);
            showCustomAlert("Network connection timeout. Order not processed.");
        } finally {
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = "PLACE ORDER";
        }
    });

    // Home return button
    document.getElementById("success-home-btn")?.addEventListener("click", () => {
        navigateTo("storefront");
    });
}

// --- 13. BANK DEPOSIT SLIP UPLOADER SUITE ---
function setupReceiptUploader(fileInputId, filenameSpanId, submitBtnId, messageDivId, orderHash) {
    const fileInput = document.getElementById(fileInputId);
    const filenameSpan = document.getElementById(filenameSpanId);
    const submitBtn = document.getElementById(submitBtnId);
    const msgDiv = document.getElementById(messageDivId);

    if (!fileInput || !submitBtn || !msgDiv) return;

    // Reset fields
    fileInput.value = "";
    if (filenameSpan) filenameSpan.textContent = "";
    msgDiv.textContent = "";
    msgDiv.style.color = "inherit";

    fileInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (file) {
            if (filenameSpan) filenameSpan.textContent = `File selected: ${file.name}`;
        }
    });

    submitBtn.onclick = async () => {
        const file = fileInput.files[0];
        if (!file) {
            showCustomAlert("Please select a receipt document first.");
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = "UPLOADING TRANSACTION SLIP...";

        const formData = new FormData();
        formData.append("order_hash", orderHash);
        formData.append("receipt", file);

        try {
            const res = await fetch("api.php?action=upload_receipt", {
                method: "POST",
                body: formData
            });

            const data = await res.json();
            if (data.success) {
                msgDiv.textContent = data.message;
                msgDiv.style.color = "#00B074";
                fileInput.value = "";
                if (filenameSpan) filenameSpan.textContent = "";
                showCustomAlert("Slip submitted successfully.");
            } else {
                msgDiv.textContent = data.error || "Failed to upload slip.";
                msgDiv.style.color = "hsl(var(--hsl-accent-dark))";
            }
        } catch (e) {
            msgDiv.textContent = "Upload failed due to connection error.";
            msgDiv.style.color = "hsl(var(--hsl-accent-dark))";
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = "SUBMIT RECEIPT";
        }
    };
}

// --- 14. REAL-TIME CUSTOMER ORDER STATUS TRACKER ---
function initOrderTracker() {
    const form = document.getElementById("order-tracker-form");
    const resultPanel = document.getElementById("tracker-result-panel");
    const errorMsg = document.getElementById("tracker-error-msg");

    form?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const hash = document.getElementById("track-hash-input").value.trim();
        if (!hash) return;

        resultPanel.style.display = "none";
        errorMsg.style.display = "none";

        try {
            const res = await fetch(`api.php?action=track_order&hash=${hash}`);
            const data = await res.json();

            if (data.success) {
                const order = data.order;
                document.getElementById("tracker-status-text").textContent = order.status;
                document.getElementById("tracker-cust-name").textContent = order.customer_name;
                document.getElementById("tracker-cust-email").textContent = order.customer_email;
                document.getElementById("tracker-delivery-tier").textContent = order.delivery_tier + " Delivery";
                document.getElementById("tracker-pay-method").textContent = order.payment_method;
                document.getElementById("tracker-grand-total").textContent = parseFloat(order.total).toLocaleString() + " LKR";

                // Setup receipt upload directly in order status query if Bank Transfer chosen and status is not finalized yet
                const slipSection = document.getElementById("tracker-bank-slip-section");
                const uploadBtn = document.getElementById("tracker-submit-receipt-btn");
                const statusDiv = document.getElementById("tracker-receipt-msg");

                if (order.payment_method === "Bank Transfer" && (order.status === "Pending" || order.status === "Receipt Uploaded")) {
                    if (slipSection) slipSection.style.display = "block";
                    if (uploadBtn) uploadBtn.style.display = "block";
                    setupReceiptUploader("tracker-receipt-upload", "tracker-receipt-filename", "tracker-submit-receipt-btn", "tracker-receipt-msg", hash);
                } else {
                    if (slipSection) slipSection.style.display = "none";
                    if (uploadBtn) uploadBtn.style.display = "none";
                }

                resultPanel.style.display = "block";
            } else {
                errorMsg.textContent = data.error || "Order reference hash not found.";
                errorMsg.style.display = "block";
            }
        } catch (e) {
            errorMsg.textContent = "Error querying data. Please check internet connection.";
            errorMsg.style.display = "block";
        }
    });
}

// --- 15. MAILING LIST EXCLUSIVE DISCOUNT MODAL ---
function initVisitorDiscountPopup() {
    const popup = document.getElementById("new-visitor-popup");
    const closeBtn = document.getElementById("discount-popup-close-btn");
    const signupForm = document.getElementById("visitor-discount-form");
    const successView = document.getElementById("visitor-discount-success-view");
    const applyShopBtn = document.getElementById("discount-apply-shop-btn");

    if (!popup) return;

    const dismissed = localStorage.getItem("evoraa_discount_dismissed");
    if (!dismissed) {
        setTimeout(() => {
            popup.classList.add("active");
        }, 3500);
    }

    function closePopup() {
        popup.classList.remove("active");
        localStorage.setItem("evoraa_discount_dismissed", "true");
    }

    closeBtn?.addEventListener("click", closePopup);
    applyShopBtn?.addEventListener("click", closePopup);

    signupForm?.addEventListener("submit", (e) => {
        e.preventDefault();
        const email = document.getElementById("visitor-email-input").value.trim();
        if (email) {
            signupForm.style.display = "none";
            if (successView) successView.style.display = "block";
            state.appliedDiscount = true;
            state.syncLocalState();
            showCustomAlert("Welcome coupon validated! 10% has been applied to checkout.");
        }
    });
}

// --- 16. BRAND CUSTOM MINIMALIST ALERT TOAST ---
function showCustomAlert(message) {
    const alertDiv = document.createElement("div");
    alertDiv.style.position = "fixed";
    alertDiv.style.bottom = "30px";
    alertDiv.style.left = "50%";
    alertDiv.style.transform = "translateX(-50%) translateY(20px)";
    alertDiv.style.zIndex = "300000";
    alertDiv.style.backgroundColor = "#1A1A1A";
    alertDiv.style.color = "#FAF9F6";
    alertDiv.style.padding = "14px 28px";
    alertDiv.style.fontSize = "11px";
    alertDiv.style.fontFamily = "var(--font-sans-alt)";
    alertDiv.style.letterSpacing = "2px";
    alertDiv.style.textTransform = "uppercase";
    alertDiv.style.boxShadow = "0 10px 30px rgba(0,0,0,0.15)";
    alertDiv.style.opacity = "0";
    alertDiv.style.transition = "opacity 0.4s ease, transform 0.4s ease";
    alertDiv.textContent = message;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.style.opacity = "1";
        alertDiv.style.transform = "translateX(-50%) translateY(0)";
    }, 50);

    setTimeout(() => {
        alertDiv.style.opacity = "0";
        alertDiv.style.transform = "translateX(-50%) translateY(20px)";
        setTimeout(() => alertDiv.remove(), 400);
    }, 3500);
}

// --- 17. MICRO-ANIMATIONS & OBSERVER ---
function initScrollAnimations() {
    if (!("IntersectionObserver" in window)) return;

    // Mask reveal typography sliding up
    const textObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("active");
                textObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll(".reveal-text").forEach(el => {
        textObserver.observe(el);
    });

    // Slow zoom hover scaling images reveal
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("active");
                imageObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.05 });

    document.querySelectorAll(".scroll-zoom-img").forEach(el => {
        imageObserver.observe(el);
    });
}

// --- 18. HAMBURGER SLIDEOUT NAVIGATION DRAWER ---
function initNavigationMenu() {
    const hamburgerBtn = document.getElementById("hamburger-menu-btn");
    const closeMenuBtn = document.getElementById("hamburger-menu-close-btn");
    const menuOverlay = document.getElementById("hamburger-menu-overlay");

    if (!hamburgerBtn || !closeMenuBtn || !menuOverlay) return;

    hamburgerBtn.addEventListener("click", () => menuOverlay.classList.add("active"));
    closeMenuBtn.addEventListener("click", () => menuOverlay.classList.remove("active"));

    menuOverlay.addEventListener("click", (e) => {
        if (e.target === menuOverlay) menuOverlay.classList.remove("active");
    });

    menuOverlay.querySelectorAll(".menu-link-item").forEach(link => {
        link.addEventListener("click", () => {
            const action = link.getAttribute("data-action");
            const cat = link.getAttribute("data-category");

            menuOverlay.querySelectorAll(".menu-link-item").forEach(item => item.classList.remove("active"));
            link.classList.add("active");

            if (action === "home") {
                state.searchQuery = "";
                state.activeCategory = "ALL";
                document.querySelectorAll(".category-link").forEach(l => {
                    l.classList.toggle("active", l.getAttribute("data-category") === "ALL");
                });
                navigateTo("storefront");
            } else if (cat) {
                state.activeCategory = cat;
                document.querySelectorAll(".category-link").forEach(l => {
                    l.classList.toggle("active", l.getAttribute("data-category") === cat);
                });
                navigateTo("storefront");
            } else if (action === "new-arrivals") {
                state.searchQuery = "";
                state.activeCategory = "ALL";
                state.products.sort((a, b) => b.id - a.id); // Sort by primary key desc for new drops
                navigateTo("storefront");
            }

            menuOverlay.classList.remove("active");
        });
    });
}

// --- 19. OVERLAY PORTAL SEARCH ---
function initSearchOverlay() {
    const searchBtn = document.getElementById("search-btn");
    const closeSearchBtn = document.getElementById("search-close-btn");
    const searchOverlay = document.getElementById("search-overlay-modal");
    const searchInputField = document.getElementById("search-input-field");

    if (!searchBtn || !closeSearchBtn || !searchOverlay || !searchInputField) return;

    searchBtn.addEventListener("click", () => {
        searchOverlay.classList.add("active");
        setTimeout(() => searchInputField.focus(), 150);
    });

    function closeOverlay() {
        searchOverlay.classList.remove("active");
    }

    closeSearchBtn.addEventListener("click", closeOverlay);
    searchOverlay.addEventListener("click", (e) => {
        if (e.target === searchOverlay) closeOverlay();
    });

    // Real-time search query filtering
    searchInputField.addEventListener("input", (e) => {
        state.searchQuery = e.target.value;
        if (state.activeView !== "storefront") {
            navigateTo("storefront");
        } else {
            renderProductGrid();
        }
    });

    // Suggestion Quick tags selection
    document.querySelectorAll(".suggestion-tag").forEach(tag => {
        tag.addEventListener("click", () => {
            const query = tag.getAttribute("data-tag");
            searchInputField.value = query;
            state.searchQuery = query;
            if (state.activeView !== "storefront") {
                navigateTo("storefront");
            } else {
                renderProductGrid();
            }
            closeOverlay();
        });
    });
}

// --- 20. LIFE CYCLE CONSTRUCTOR ---
document.addEventListener("DOMContentLoaded", () => {
    // Run preloader instantly
    runCinematicPreloader();

    // Fetch live datasets
    fetchStorefrontData();

    // Restore customer session UI
    updateProfileButton();

    // Bind sub-managers
    initThemeManager();
    initVisitorDiscountPopup();
    initCheckoutFlow();
    initNavigationMenu();
    initSearchOverlay();
    initOrderTracker();

    // Brand logo home link
    document.getElementById("brand-logo-link")?.addEventListener("click", (e) => {
        e.preventDefault();
        navigateTo("storefront");
    });

    // Call to Action Hero button
    document.getElementById("hero-cta-btn")?.addEventListener("click", () => {
        document.getElementById("main-product-grid")?.scrollIntoView({ behavior: "smooth" });
    });

    // Category navbar selectors
    document.querySelectorAll(".category-link").forEach(link => {
        link.addEventListener("click", () => {
            document.querySelectorAll(".category-link").forEach(l => l.classList.remove("active"));
            link.classList.add("active");
            state.activeCategory = link.getAttribute("data-category") || "ALL";

            if (state.activeView !== "storefront") {
                navigateTo("storefront");
            } else {
                renderProductGrid();
            }
        });
    });

    // Clear filters button
    document.getElementById("clear-all-filters-btn")?.addEventListener("click", () => {
        state.searchQuery = "";
        state.activeCategory = "ALL";
        document.querySelectorAll(".category-link").forEach(l => {
            l.classList.toggle("active", l.getAttribute("data-category") === "ALL");
        });
        renderProductGrid();
    });

    // Footer Collection triggers
    document.querySelectorAll(".footer-cat-trigger").forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const cat = link.getAttribute("data-category");
            state.activeCategory = cat;
            document.querySelectorAll(".category-link").forEach(l => {
                l.classList.toggle("active", l.getAttribute("data-category") === cat);
            });
            navigateTo("storefront");
        });
    });

    // Cart overlay slider drawers
    document.getElementById("cart-btn")?.addEventListener("click", openCartDrawer);
    document.getElementById("shopping-cart-close-btn")?.addEventListener("click", closeCartDrawer);
    document.getElementById("shopping-cart-overlay")?.addEventListener("click", (e) => {
        if (e.target === document.getElementById("shopping-cart-overlay")) closeCartDrawer();
    });

    // Drawer Proceed to Checkout
    document.getElementById("cart-drawer-checkout-btn")?.addEventListener("click", () => {
        if (state.cart.length === 0) {
            showCustomAlert("Your shopping bag is empty.");
            return;
        }
        closeCartDrawer();
        navigateTo("checkout");
    });

    // Detail QuickView Modal controls
    document.getElementById("quickview-close-btn")?.addEventListener("click", closeQuickView);
    document.getElementById("product-quickview-modal")?.addEventListener("click", (e) => {
        if (e.target === document.getElementById("product-quickview-modal")) closeQuickView();
    });

    // Add to Bag from QuickView
    document.getElementById("quickview-add-to-cart-btn")?.addEventListener("click", () => {
        if (state.selectedProduct) {
            addToCart(state.selectedProduct.id, state.selectedProductSize, 1);
        }
    });

    // Wishlist toggle from QuickView
    document.getElementById("quickview-wishlist-btn")?.addEventListener("click", () => {
        if (state.selectedProduct) {
            toggleWishlistItem(state.selectedProduct.id);
            const heartBtn = document.getElementById("quickview-wishlist-btn");
            const heartIcon = document.getElementById("quickview-wishlist-icon");
            if (heartBtn && heartIcon) {
                const active = state.wishlist.includes(state.selectedProduct.id);
                heartBtn.classList.toggle("active", active);
                heartIcon.className = active ? "fa-solid fa-heart" : "fa-regular fa-heart";
            }
            renderProductGrid();
        }
    });

    // Size Chart Modals
    const sizeGuideModal = document.getElementById("size-guide-modal-container");
    document.getElementById("quickview-size-guide-link")?.addEventListener("click", () => {
        sizeGuideModal?.classList.add("active");
    });
    document.getElementById("size-guide-close-btn")?.addEventListener("click", () => {
        sizeGuideModal?.classList.remove("active");
    });
    sizeGuideModal?.addEventListener("click", (e) => {
        if (e.target === sizeGuideModal) sizeGuideModal.classList.remove("active");
    });

    // Order Tracking Modal / Account trigger
    const profileModal = document.getElementById("profile-modal-container");
    document.getElementById("profile-btn")?.addEventListener("click", () => {
        if (state.currentUser) {
            // Logged in — go to account dashboard
            navigateTo('account');
            renderAccountDashboard();
            return;
        }
        // Not logged in — show login/register modal
        document.getElementById("order-tracker-form")?.reset();
        const trackerRes = document.getElementById("tracker-result-panel");
        const trackerErr = document.getElementById("tracker-error-msg");
        if (trackerRes) trackerRes.style.display = "none";
        if (trackerErr) trackerErr.style.display = "none";
        profileModal?.classList.add("active");
    });
    document.getElementById("profile-close-btn")?.addEventListener("click", () => {
        profileModal?.classList.remove("active");
    });

    // Contact form triggers
    document.getElementById("footer-link-contact")?.addEventListener("click", (e) => {
        e.preventDefault();
        navigateTo("contact");
    });
    document.getElementById("contact-form")?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const name = document.getElementById("contact-name").value.trim();
        const email = document.getElementById("contact-email").value.trim();
        const message = document.getElementById("contact-message").value.trim();
        try {
            const res = await fetch("api.php?action=submit_contact", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ name, email, message })
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById("contact-form").reset();
                showCustomAlert("Your message has been sent. We will get back to you shortly.");
            } else {
                showCustomAlert(data.error || "Failed to send message.");
            }
        } catch (err) {
            showCustomAlert("Network error. Please try again.");
        }
    });

    // Policy page triggers
    document.getElementById("footer-link-policy")?.addEventListener("click", (e) => {
        e.preventDefault();
        navigateTo("policy");
    });
});
