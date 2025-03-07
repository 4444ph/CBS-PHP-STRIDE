<header>
    <div class="container">
        <div class="logo">
            <a href="index.php">E-Commerce</a>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="cart.php">Cart (<?php echo getCartCount(); ?>)</a></li>
                    <li><a href="orders.php">My Orders</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin/dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

