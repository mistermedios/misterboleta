<?php require_once __DIR__ . '/../includes/header.php'; ?>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Descubre los Mejores Eventos</h1>
            <p>Compra tus boletas para conciertos, deportes, theater y más. Segurança garantizada.</p>
            
            <form action="<?= e(url('public/index.php')) ?>" method="GET" class="search-box">
                <input type="text" name="search" placeholder="Buscar eventos, artistas, lugares..." value="<?= e($search) ?>">
                <button type="submit"><i class="fa-solid fa-search"></i> Buscar</button>
            </form>
        </div>
    </div>
</section>

<section class="categories">
    <div class="container">
        <h2 class="section-title"><span>Categorías</span></h2>
        <div class="categories-grid">
            <?php foreach ($categories as $key => $name): ?>
                <?php $parts = explode(' ', $name, 2); ?>
                <a href="<?= e(url('public/index.php?category=' . urlencode($key))) ?>" class="category-card <?= $category == $key ? 'active' : '' ?>">
                    <div class="category-icon"><?= explode(' ', $name)[0] ?></div>
                    <h3><?= e($parts[1] ?? $name) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="events-section">
    <div class="container">
        <div class="flex-between mb-3">
            <h2 class="section-title">
                <?php if ($category): ?>
                    <span><?= $categories[$category] ?? 'Eventos' ?></span>
                <?php elseif ($search): ?>
                    <span>Resultados de "<?= e($search) ?>"</span>
                <?php else: ?>
                    <span>Próximos Eventos</span>
                <?php endif; ?>
            </h2>
            <?php if ($category || $search): ?>
                <a href="<?= e(url('public/index.php')) ?>" class="btn btn-outline">Ver todos</a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($events)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎫</div>
                <h3>No se encontraron eventos</h3>
                <p>Intenta con otros términos de búsqueda o categorías.</p>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $event): 
                    $minPrice = getMinPrice($pdo, $event['id']);
                ?>
                    <div class="event-card" onclick="window.location.href='<?= e(url('public/event-detail.php?id=' . $event['id'])) ?>'">
                        <div class="event-image">
                            <img src="<?= e($event['image'] ?: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800') ?>" alt="<?= e($event['title']) ?>">
                            <?php if ($event['featured']): ?>
                                <span class="event-badge featured">Destacado</span>
                            <?php else: ?>
                                <span class="event-badge"><?= ucfirst($event['category']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="event-content">
                            <div class="event-date">
                                <i class="fa-regular fa-calendar"></i> 
                                <?= formatDateTime($event['event_date']) ?>
                            </div>
                            <h3 class="event-title"><?= e($event['title']) ?></h3>
                            <div class="event-venue">
                                <i class="fa-solid fa-location-dot"></i>
                                <?= e($event['venue']) ?> - <?= e($event['city']) ?>
                            </div>
                            <div class="event-footer">
                                <div class="event-price">
                                    desde $<?= number_format($minPrice, 0, ',', '.') ?>
                                    <span>/boleta</span>
                                </div>
                                <a href="<?= e(url('public/event-detail.php?id=' . $event['id'])) ?>" class="btn btn-sm btn-primary">Comprar</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
