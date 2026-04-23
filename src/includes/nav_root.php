<?php require_once __DIR__ . '/csrf.php'; ?>
<nav>
    <div class="logo">
        <a href="#">
            <img src="src/img/cartehniklogo_valge.svg" alt="Cartehnik logo">
        </a>
    </div>
    <div class="nav-links">
        <a href="index.php">Avaleht</a>
        <a href="src/myydud_tooted/myyk.php">Müüdud Tooted</a>
        <a href="src/tehtud_tood/tehtud_tood.php">Tehtud Tööd</a>
        <div class="dropdown">
            <button class="dropbtn">Rehvid
                <i class="fa fa-caret-down"></i>
            </button>
            <div class="dropdown-content">
                <a href="src/rehv_myyk/rehv_myyk.php">Müüdud Rehvid</a>
                <a href="src/rehv_ladu/rehv_ladu.php">Rehvid Laos</a>
            </div>
        </div>
        <a href="src/kalender/kalender.php">Töögraafik</a>
        <a href="src/login/logout.php">
            <?php if (isset($_SESSION['username'])): ?>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?>,</span>
            <?php endif; ?>
            Logi välja
        </a>
    </div>
</nav>
