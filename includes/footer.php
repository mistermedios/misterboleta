    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>MisterBoleta</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 15px;">
                        Tu plataforma de confianza para comprar y vender boletas para los mejores eventos de Colombia.
                    </p>
                    <div class="flex gap-1">
                        <a href="#" style="color: var(--accent); font-size: 1.3rem;"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#" style="color: var(--accent); font-size: 1.3rem;"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" style="color: var(--accent); font-size: 1.3rem;"><i class="fa-brands fa-twitter"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Explorar</h3>
                    <ul>
                        <li><a href="<?= e(url('public/index.php')) ?>">Todos los Eventos</a></li>
                        <li><a href="<?= e(url('public/index.php?category=concierto')) ?>">Conciertos</a></li>
                        <li><a href="<?= e(url('public/index.php?category=deporte')) ?>">Deportes</a></li>
                        <li><a href="<?= e(url('public/index.php?category=teatro')) ?>">Teatro</a></li>
                        <li><a href="<?= e(url('public/index.php?category=festival')) ?>">Festivales</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Cuenta</h3>
                    <ul>
                        <li><a href="<?= e(url('user/login.php')) ?>">Iniciar Sesion</a></li>
                        <li><a href="<?= e(url('user/register.php')) ?>">Registrarse</a></li>
                        <li><a href="<?= e(url('user/my-tickets.php')) ?>">Mis Boletas</a></li>
                        <li><a href="<?= e(url('user/orders.php')) ?>">Mis Ordenes</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contacto</h3>
                    <ul>
                        <li><i class="fa-solid fa-phone"></i> +57 300 123 4567</li>
                        <li><i class="fa-solid fa-envelope"></i> info@misterboleta.com</li>
                        <li><i class="fa-solid fa-location-dot"></i> Medellín, Colombia</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 MisterBoleta. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="<?= e(assetUrl('js/main.js')) ?>"></script>
</body>
</html>
