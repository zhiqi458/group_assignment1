<?php include 'db.php'; ?>
<link rel="stylesheet" href="style.css">
<div class="header">
<h2>Customer Menu</h2>
</div>
<form action="admin_add.php?action=place_order" method="POST">
    <table border="1">
        <?php
        $items = $conn->query("SELECT * FROM items WHERE stock > 0");
        while($item = $items->fetch_assoc()) {
            echo "<tr>
                    <td>
                        <strong>{$item['name']}</strong><br>{$item['description']}<br>\${$item['price']}
                    </td>
                    <td>
                        Qty: <input type='number' name='qty[{$item['id']}]' value='0' min='0'><br>
                        Remark: <input type='text' name='remark[{$item['id']}]'>
                        <input type='hidden' name='price[{$item['id']}]' value='{$item['price']}'>
                        <input type='hidden' name='item_name[{$item['id']}]' value='{$item['name']}'>
                    </td>
                  </tr>";
        }
        ?>
    </table>
    <br>
    Table Number: <input type="number" name="table_number" required>
    <button type="submit">Proceed to Checkout</button>
</form>