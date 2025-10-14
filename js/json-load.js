// Jsone load for the featured products section
// This script fetches the products from a JSON file, groups them by category,
// and displays the first product from each category in a card format.
document.addEventListener('DOMContentLoaded', function () {
    fetch('Json/normalized_products.json')
        .then(res => res.json())
        .then(products => {
            // Group products by category and pick only the first product from each
            const grouped = {};
            products.forEach(p => {
                if (Array.isArray(p.image) && p.image.length > 0) {
                    const cat = p.category || 'Other';
                    if (!grouped[cat]) grouped[cat] = p;
                }
            });

            renderProducts(grouped);
        });

    function escapeHtml(txt) {
        return (txt || '').replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[m]));
    }

    function renderProducts(grouped) {
        const wrapper = document.getElementById('featured-products-wrapper');
        wrapper.innerHTML = '';

        Object.entries(grouped).forEach(([category, product]) => {
            // Create product card for each category
            const card = document.createElement('div');
            card.className = 'featured-product-card';

            const mainImg = product.image[1]?.replace('./', '');
            const altImg = product.image[2]?.replace('./', '');
            const hasAlt = !!altImg;

            card.innerHTML = `
                <div class="fp-image-wrap">
                    <img 
                        src="${mainImg}" 
                        alt="${escapeHtml(product.name || product.model)}" 
                        class="fp-img main"
                        loading="lazy"
                    >
                    ${hasAlt ? `
                    <img 
                        src="${altImg}" 
                        alt="${escapeHtml(product.name || product.model)} alternate view" 
                        class="fp-img alt"
                        loading="lazy"
                    >` : ''}
                    <span class="fp-badge">Featured</span>
                </div>
                <div class="fp-content">
                    <h5>${escapeHtml(product.name || product.model)}</h5>
                    <div class="fp-meta">${product.brand ? escapeHtml(product.brand) + ' — ' : ''}${escapeHtml(product.category || '')}</div>
                    <p>${escapeHtml(product.summary || (product.description?.slice(0, 200) + '...') || '')}</p>
                    <a onclick="window.location.href='product-details.html?category=${encodeURIComponent(product.category)}&brand=${encodeURIComponent(product.brand)}&model=${encodeURIComponent(product.model)}'" 
                    class="fp-link">View Details</a>
                </div>
            `;
            wrapper.appendChild(card);
        });

    }
});
// Loading the FQA section 
fetch('Json/faq.json')
    .then(response => response.json())
    .then(data => {
        const accordionContainer = document.querySelector('.accordion-section');
        accordionContainer.innerHTML = '';

        data.forEach((faq, index) => {
            const sectionTitle = document.createElement('div');
            sectionTitle.className = 'accordion-section-title';
            sectionTitle.setAttribute('data-tab', `#accordion-a${index + 1}`);
            sectionTitle.textContent = faq.question;

            const sectionContent = document.createElement('div');
            sectionContent.className = 'accordion-section-content';
            sectionContent.id = `accordion-a${index + 1}`;
            sectionContent.style.display = 'none';
            sectionContent.style.overflow = 'hidden';
            sectionContent.style.maxHeight = '0';
            sectionContent.style.transition = 'max-height 0.6s cubic-bezier(.4,0,.2,1), opacity 0.4s cubic-bezier(.4,0,.2,1)';
            sectionContent.style.opacity = '0';
            sectionContent.innerHTML = `
                <p style="padding:20px">${faq.answer}</p>
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; margin-top: 5px;">
                          <span style="color: #e63946;">${faq.cta.text}</span>
                          <br>
                          <a href="${faq.cta.link}" style="color: #e63946; font-weight: bold; text-decoration: underline;">${faq.cta.linkText}</a>
                        </div>
                    `;

            sectionTitle.addEventListener('click', () => {
                const isVisible = sectionContent.style.display === 'block' || sectionContent.style.maxHeight !== '0px';
                if (isVisible) {
                    sectionContent.style.maxHeight = '0';
                    sectionContent.style.opacity = '0';
                    setTimeout(() => {
                        sectionContent.style.display = 'none';
                    }, 400);
                } else {
                    sectionContent.style.display = 'block';
                    // Wait for display:block to apply before animating
                    setTimeout(() => {
                        sectionContent.style.maxHeight = sectionContent.scrollHeight + 'px';
                        sectionContent.style.opacity = '1';
                    }, 10);
                }
            });

            accordionContainer.appendChild(sectionTitle);
            accordionContainer.appendChild(sectionContent);
        });
    });

// Top bar message json load
// Dynamically load topbar messages from JSON and display them all (no rotation)
fetch('Json/topbar-messages.json')
    .then(res => res.json())
    .then(messages => {
        const container = document.getElementById('topbar-messages');
        container.innerHTML = messages.map(msg => `
                <div class="topbar-widget me-5">
                <a href="${msg.link}">
                    <img src="${msg.icon}" alt="">
                    ${msg.text}
                </a>
                </div>
            `).join('');
    });

// Dynamically load Swiper slides from JSON
fetch('Json/slides.json')
    .then(res => res.json())
    .then(slides => {
        const wrapper = document.getElementById('dynamic-swiper-wrapper');
        wrapper.innerHTML = ""; // Clear any existing slides
        slides.forEach(slide => {
            wrapper.innerHTML += `
                                    <div class="swiper-slide">
                                        <div class="swiper-inner" >
                                            <div class="slider-img"></div>
                                            <div class="sw-caption">
                                                <div class="container relative z-2" data-0="opacity:1" data-300="opacity:0">
                                                    <div class="row align-items-center g-4">
                                                        <div class="col-lg-6">
                                                            <div class="relative z-2">
                                                                <div class="slide-title">
                                                                    <a href="products.html">
                                                                        <h3 class="subtitle s2 mb-3 wow fadeInUp" data-wow-delay=".0s">${slide.title}</h3>
                                                                        <h1 class="mb-3 cam-style" style="color: #223035; margin-left:-10px; text-align: left;">${slide.headline}</h1>
                                                                    </a>
                                                                </div>
                                                                <a class="btn-main fx-slide mb10 mb-3" href="${slide.learnMoreLink}"><span>${slide.learnMoreText}</span></a>&nbsp;&nbsp;
                                                                <a class="btn-main fx-slide mb10 mb-3" href="${slide.callLink}"><span>${slide.callText}</span></a>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="relative">
                                                                <a href="products.html"><img src="${slide.image}" class="w-100 h-50 relative z-2" alt=""></a>
                                                                <div class="abs w-70 h-70 abs-centered"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    `;
        });
        // Initialize Swiper AFTER slides are injected
        if (window.Swiper) {
            new Swiper('.swiper', {
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev'
                }
            });
        }
    });