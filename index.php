<?php
include'db.php';
?>

<h1>Welcome to Our Restaurant</h1>
<a href="cart.php">View Cart</a>

<h2>Our Menu</h2>
<div style="display:flex;">
    <?php while($item = $items->fetch_assoc()): ?>
    <div style="border:1px solid #ccc; padding:10px; margin:10px; width:200px;">
        <h3><?php echo $item['name']; ?></h3>
        <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
        <form action="cart.php" method="POST">
            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
            <input type="number" name="quantity" placeholder="Quantity" required><br>
            <input type="text" name="remark" placeholder="Remark"><br>
            <button type="submit" style="background:green; color:white;">Add to Cart</button>
        </form>
    </div>
    <?php endwhile; ?>
</div>