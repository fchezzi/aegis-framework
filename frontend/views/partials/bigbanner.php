<?php
/**
 * Partial: BigBanner - Carrossel
 * Autocontido
 */

require_once ROOT_PATH . 'frontend/controllers/FrontendBigbannerController.php';
$controller = new FrontendBigbannerController();
$items = $controller->getActive();
if (empty($items)) return;
$carouselId = uniqid('bigbanner-');
?>

<section class="c-bigbanner" id="<?= $carouselId ?>">
    <div class="c-bigbanner__carousel">
        <div class="c-bigbanner__slides">
            <?php foreach ($items as $index => $item): ?>
                <div class="c-bigbanner__slide <?= $index === 0 ? 'active' : '' ?>">
                    <?php if (!empty($item['iamge'])): ?>
                        <img src="<?= url($item['iamge']) ?>" class="c-bigbanner__image" />
                    <?php endif; ?>
                    <div class="c-bigbanner__content">
                        <h1 class="c-bigbanner__title"><?= htmlspecialchars($item['title'] ?? '') ?></h1>
                        <h1 class="c-bigbanner__title"><?= htmlspecialchars($item['subtitle'] ?? '') ?></h1>
                        <?php if (!empty($item['cta']) && !empty($item['cta_link'])): ?>
                            <?php 
                                $isExternal = strpos($item['cta_link'], 'http') === 0 || strpos($item['cta_link'], '//') === 0;
                                $target = $isExternal ? ' target="_blank" rel="noopener noreferrer"' : '';
                            ?>
                            <a href="<?= htmlspecialchars($item['cta_link']) ?>" class="c-bigbanner__cta"<?= $target ?>><?= htmlspecialchars($item['cta']) ?></a>
                        <?php endif; ?>
                        <?php if (!empty($item['cta_link']) && !empty($item['cta_link'])): ?>
                            <?php 
                                $isExternal = strpos($item['cta_link'], 'http') === 0 || strpos($item['cta_link'], '//') === 0;
                                $target = $isExternal ? ' target="_blank" rel="noopener noreferrer"' : '';
                            ?>
                            <a href="<?= htmlspecialchars($item['cta_link']) ?>" class="c-bigbanner__cta"<?= $target ?>><?= htmlspecialchars($item['cta_link']) ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($items) > 1): ?>
            <button class="c-bigbanner__nav c-bigbanner__nav--prev"><i data-lucide="chevron-left"></i></button>
            <button class="c-bigbanner__nav c-bigbanner__nav--next"><i data-lucide="chevron-right"></i></button>
            <div class="c-bigbanner__indicators">
                <?php foreach ($items as $index => $item): ?>
                    <button class="c-bigbanner__indicator <?= $index === 0 ? 'active' : '' ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function() {
    const carousel = document.querySelector('.c-bigbanner');
    if (!carousel) return;

    const slides = carousel.querySelectorAll('.c-bigbanner__slide');
    const indicators = carousel.querySelectorAll('.c-bigbanner__indicator');
    const prevBtn = carousel.querySelector('.c-bigbanner__nav--prev');
    const nextBtn = carousel.querySelector('.c-bigbanner__nav--next');
    let currentSlide = 0;
    const totalSlides = slides.length;
    let autoplayInterval;

    function showSlide(index) {
        if (index >= totalSlides) index = 0;
        if (index < 0) index = totalSlides - 1;
        currentSlide = index;
        slides.forEach((s, i) => s.classList.toggle('active', i === currentSlide));
        indicators.forEach((ind, i) => ind.classList.toggle('active', i === currentSlide));
    }

    function nextSlide() { showSlide(currentSlide + 1); }
    function prevSlide() { showSlide(currentSlide - 1); }

    function startAutoplay() {
        stopAutoplay();
        autoplayInterval = setInterval(nextSlide, 5000);
    }

    function stopAutoplay() {
        if (autoplayInterval) clearInterval(autoplayInterval);
    }

    if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); stopAutoplay(); startAutoplay(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); stopAutoplay(); startAutoplay(); });
    indicators.forEach((ind, i) => ind.addEventListener('click', () => { showSlide(i); stopAutoplay(); startAutoplay(); }));

    carousel.addEventListener('mouseenter', stopAutoplay);
    carousel.addEventListener('mouseleave', startAutoplay);

    let touchStartX = 0, touchEndX = 0;
    carousel.addEventListener('touchstart', e => touchStartX = e.changedTouches[0].screenX);
    carousel.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        if (touchEndX < touchStartX - 50) nextSlide();
        if (touchEndX > touchStartX + 50) prevSlide();
    });

    if (totalSlides > 1) startAutoplay();
})();
</script>
