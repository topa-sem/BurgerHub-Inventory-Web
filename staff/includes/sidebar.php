<?php
// /staff/includes/sidebar.php
?>
<aside class="sidebar">
    <div class="sidebar-brand">BurgerHub</div>
    <nav class="nav">
        <a class="<?php echo (basename($_SERVER['PHP_SELF'])=='index.php')?'active':'';?>" href="index.php">Home</a>
        <a class="<?php echo (basename($_SERVER['PHP_SELF'])=='sales.php')?'active':'';?>" href="sales.php">Sales</a>
        <a class="<?php echo (basename($_SERVER['PHP_SELF'])=='stocks.php')?'active':'';?>" href="stocks.php">Stocks</a>
        <a class="<?php echo (basename($_SERVER['PHP_SELF'])=='orders.php')?'active':'';?>" href="orders.php">Orders</a>
        <a href="logout.php" class="logout">Log out</a>
    </nav>
</aside>
