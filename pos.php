<?php
// pos.php - Modern Point of Sale

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'config.php';

$success_msg = "";
$error_msg = ""; 
$last_sale_id = null; // Variable to hold the ID of the successful transaction

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get inputs from the hidden JS form
    $med_ids = $_POST['medicine_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $amt_paid = $_POST['amount_paid'] ?? 0;
    $grand_total = 0;

    if (empty($med_ids)) {
        $error_msg = "Please add items to the cart before processing.";
    } else {
        // STEP 1: Calculate grand total BAGO database inserts
        for ($x = 0; $x < count($med_ids); $x++) {
            $chosen_med_id = $med_ids[$x];
            $chosen_qty = $quantities[$x];

            $med_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price FROM medicines WHERE medicine_id='$chosen_med_id'"));
            $item_price = $med_info['price'];
            $grand_total += ($item_price * $chosen_qty);
        }

        // STEP 2: Check kung sapat bayad
        if ($amt_paid < $grand_total) {
            $error_msg = "Kulang ang bayad! Total ay ₱" . number_format($grand_total, 2) . ".";
        } else {
            // STEP 3: Proseso database inserts
            $sukli = $amt_paid - $grand_total;

            $sale_sql = "INSERT INTO sales (user_id, total_amount, amount_paid, change_amount) 
                         VALUES ('{$_SESSION['user_id']}', '$grand_total', '$amt_paid', '$sukli')";
            mysqli_query($conn, $sale_sql);
            
            // Save the new sale ID so we can generate the print button
            $new_sale_id = mysqli_insert_id($conn);
            $last_sale_id = $new_sale_id;

            for ($x = 0; $x < count($med_ids); $x++) {
                $chosen_med_id = $med_ids[$x];
                $chosen_qty = $quantities[$x];

                $med_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price FROM medicines WHERE medicine_id='$chosen_med_id'"));
                $item_price = $med_info['price'];

                mysqli_query($conn, "INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price) 
                                     VALUES ('$new_sale_id', '$chosen_med_id', '$chosen_qty', '$item_price')");

                mysqli_query($conn, "UPDATE medicines SET stock_quantity = stock_quantity - '$chosen_qty' 
                                     WHERE medicine_id='$chosen_med_id'");
            }

            $success_msg = "Payment Successful! Total: ₱" . number_format($grand_total, 2) . " | Change: ₱" . number_format($sukli, 2);
        }
    }
}

// Fetch all available medicines for the JavaScript frontend
$available_meds = mysqli_query($conn, "SELECT * FROM medicines WHERE stock_quantity > 0 ORDER BY name ASC");
$med_list_for_js = [];
while ($row = mysqli_fetch_assoc($available_meds)) {
    $med_list_for_js[] = $row;
}
$staff_name = $_SESSION['username'] ?? 'Staff';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - HopeMed Pharmacy</title>
    <style>
        :root {
            --primary: #0C7B7B;
            --primary-light: #17A880;
            --bg-main: #F2F6FA;
            --text-main: #0E1C2B;
            --text-muted: #6B7E91;
            --border: rgba(12,123,123,0.12);
            --danger: #D63E3E;
            --font-main: system-ui, -apple-system, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: var(--font-main); }
        body { display: flex; height: 100vh; background-color: var(--bg-main); overflow: hidden; }

        .pos-container { flex: 1; display: flex; overflow: hidden; }
        
        /* Products Panel */
        .products-panel { flex: 1; display: flex; flex-direction: column; border-right: 1px solid var(--border); }
        .products-header { padding: 1.5rem; background: white; border-bottom: 1px solid var(--border); }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .header-top h1 { font-size: 1.5rem; color: var(--text-main); }
        .search-bar { width: 100%; padding: 12px 16px; background: var(--bg-main); border: 1px solid var(--border); border-radius: 12px; font-size: 0.875rem; outline: none; }
        .search-bar:focus { border-color: var(--primary); }
        
        .products-grid-container { flex: 1; padding: 1.5rem; overflow-y: auto; background: var(--bg-main); }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
        
        .med-card { background: white; padding: 1rem; border-radius: 16px; border: 1px solid var(--border); cursor: pointer; transition: all 0.2s; text-align: left; }
        .med-card:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(12,123,123,0.05); }
        .med-category { display: inline-block; font-size: 0.65rem; padding: 4px 8px; border-radius: 6px; background: rgba(12,123,123,0.1); color: var(--primary); margin-bottom: 8px; font-weight: 600; }
        .med-name { font-size: 0.875rem; font-weight: 600; color: var(--text-main); margin-bottom: 4px; }
        .med-price { font-size: 1.125rem; font-weight: 700; color: var(--primary); }
        .med-stock { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; }

        /* Cart Panel */
        .cart-panel { width: 350px; background: white; display: flex; flex-direction: column; }
        .cart-header { padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .cart-header h2 { font-size: 1.25rem; color: var(--text-main); }
        
        .cart-items { flex: 1; padding: 1rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; }
        .cart-empty { text-align: center; color: var(--text-muted); padding-top: 2rem; font-size: 0.875rem; }
        
        .cart-item { background: var(--bg-main); padding: 12px; border-radius: 12px; }
        .item-top { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .item-name { font-size: 0.875rem; font-weight: 500; color: var(--text-main); }
        .btn-remove { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem; }
        .btn-remove:hover { color: var(--danger); }
        
        .item-bottom { display: flex; justify-content: space-between; align-items: center; }
        .qty-controls { display: flex; align-items: center; gap: 8px; background: white; padding: 4px; border-radius: 8px; }
        .btn-qty { background: none; border: none; width: 24px; height: 24px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--text-muted); }
        .btn-qty:hover { background: var(--bg-main); color: var(--primary); }
        .item-qty { font-size: 0.875rem; font-weight: 600; width: 20px; text-align: center; }
        .item-subtotal { font-weight: 700; color: var(--primary); font-size: 0.875rem; }

        /* Checkout Area */
        .checkout-area { padding: 1.5rem; border-top: 1px solid var(--border); background: white; }
        .total-row { display: flex; justify-content: space-between; font-size: 1.125rem; font-weight: 700; color: var(--text-main); margin-bottom: 1rem; }
        .cash-input { width: 100%; padding: 12px; background: var(--bg-main); border: 1px solid var(--border); border-radius: 12px; font-size: 1rem; outline: none; margin-bottom: 1rem; }
        .btn-checkout { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-checkout:hover { background: #0a6363; }
        .btn-checkout:disabled { background: var(--text-muted); cursor: not-allowed; }

        /* Messages */
        .alert-box { padding: 12px 16px; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 500; }
        .alert-success { background: #F0FBF7; color: #17A880; border: 1px solid rgba(23,168,128,0.2); }
        .alert-error { background: #FFF0F0; color: var(--danger); border: 1px solid rgba(214,62,62,0.2); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="pos-container">
        <div class="products-panel">
            <div class="products-header">
                <div class="header-top">
                    <h1>Point of Sale</h1>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Staff: <strong style="color: var(--primary);"><?php echo htmlspecialchars($staff_name); ?></strong></span>
                </div>
                
                <?php if (!empty($success_msg)): ?>
                    <div class="alert-box alert-success" style="display: flex; justify-content: space-between; align-items: center;">
                        <span><?php echo $success_msg; ?></span>
                        <?php if ($last_sale_id): ?>
                            <a href="sale_details.php?id=<?php echo $last_sale_id; ?>" target="_blank" style="background: var(--primary-light); color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.75rem; font-weight: 600; transition: background 0.2s;">🖨️ Print Receipt</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_msg)): ?>
                    <div class="alert-box alert-error"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <input type="text" id="searchInput" class="search-bar" placeholder="Search medicines by name..." onkeyup="renderProducts()">
            </div>

            <div class="products-grid-container">
                <div class="products-grid" id="productsGrid">
                    </div>
            </div>
        </div>

        <div class="cart-panel">
            <div class="cart-header">
                <h2>Cart</h2>
                <span id="cartCount" style="font-size: 0.875rem; color: var(--text-muted); font-weight: 600;">0 items</span>
            </div>

            <div class="cart-items" id="cartItems">
                </div>

            <div class="checkout-area">
                <div class="total-row">
                    <span>Total</span>
                    <span style="color: var(--primary);">₱<span id="cartTotal">0.00</span></span>
                </div>

                <label style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px; display: block;">Cash Tendered (₱)</label>
                <input type="number" id="cashInput" class="cash-input" placeholder="0.00" step="0.01" onkeyup="checkCash()">

                <form id="checkoutForm" method="POST">
                    <div id="hiddenInputs"></div>
                    <input type="hidden" name="amount_paid" id="hiddenAmountPaid">
                    <button type="button" id="btnProcess" class="btn-checkout" onclick="submitCheckout()" disabled>Process Payment</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Load PHP data into Javascript
        const products = <?php echo json_encode($med_list_for_js); ?>;
        let cart = [];

        function renderProducts() {
            const grid = document.getElementById('productsGrid');
            const search = document.getElementById('searchInput').value.toLowerCase();
            grid.innerHTML = '';

            const filtered = products.filter(p => p.name.toLowerCase().includes(search));

            filtered.forEach(p => {
                const card = document.createElement('div');
                card.className = 'med-card';
                card.onclick = () => addToCart(p);
                card.innerHTML = `
                    <span class="med-category">${p.category}</span>
                    <div class="med-name">${p.name}</div>
                    <div class="med-price">₱${parseFloat(p.price).toFixed(2)}</div>
                    <div class="med-stock">${p.stock_quantity} in stock</div>
                `;
                grid.appendChild(card);
            });
        }

        function addToCart(product) {
            const existing = cart.find(i => i.medicine_id === product.medicine_id);
            if (existing) {
                if (existing.qty < product.stock_quantity) {
                    existing.qty++;
                } else {
                    alert('Cannot exceed available stock!');
                }
            } else {
                cart.push({ ...product, qty: 1 });
            }
            renderCart();
        }

        function updateQty(id, delta) {
            const item = cart.find(i => i.medicine_id === id);
            if (!item) return;

            const product = products.find(p => p.medicine_id === id);
            
            item.qty += delta;
            if (item.qty > product.stock_quantity) item.qty = product.stock_quantity;
            if (item.qty <= 0) cart = cart.filter(i => i.medicine_id !== id);
            
            renderCart();
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            let total = 0;
            let itemsCount = 0;

            if (cart.length === 0) {
                container.innerHTML = '<div class="cart-empty">Cart is empty.<br><span style="font-size: 0.75rem; opacity: 0.7;">Click a product to add.</span></div>';
            } else {
                container.innerHTML = '';
                cart.forEach(item => {
                    const subtotal = item.price * item.qty;
                    total += subtotal;
                    itemsCount += item.qty;

                    const div = document.createElement('div');
                    div.className = 'cart-item';
                    div.innerHTML = `
                        <div class="item-top">
                            <span class="item-name">${item.name}</span>
                            <button class="btn-remove" onclick="updateQty('${item.medicine_id}', -999)">×</button>
                        </div>
                        <div class="item-bottom">
                            <div class="qty-controls">
                                <button class="btn-qty" onclick="updateQty('${item.medicine_id}', -1)">-</button>
                                <span class="item-qty">${item.qty}</span>
                                <button class="btn-qty" onclick="updateQty('${item.medicine_id}', 1)">+</button>
                            </div>
                            <span class="item-subtotal">₱${subtotal.toFixed(2)}</span>
                        </div>
                    `;
                    container.appendChild(div);
                });
            }

            document.getElementById('cartTotal').innerText = total.toFixed(2);
            document.getElementById('cartCount').innerText = `${itemsCount} items`;
            checkCash();
        }

        function checkCash() {
            const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            const cash = parseFloat(document.getElementById('cashInput').value) || 0;
            const btn = document.getElementById('btnProcess');
            
            if (cart.length > 0 && cash >= total && total > 0) {
                btn.disabled = false;
            } else {
                btn.disabled = true;
            }
        }

        function submitCheckout() {
            const hiddenInputs = document.getElementById('hiddenInputs');
            hiddenInputs.innerHTML = ''; // Clear previous

            // Build hidden inputs for PHP POST structure
            cart.forEach(item => {
                hiddenInputs.innerHTML += `<input type="hidden" name="medicine_id[]" value="${item.medicine_id}">`;
                hiddenInputs.innerHTML += `<input type="hidden" name="quantity[]" value="${item.qty}">`;
            });

            document.getElementById('hiddenAmountPaid').value = document.getElementById('cashInput').value;
            
            // Submit form
            document.getElementById('checkoutForm').submit();
        }

        // Initialize grid on load
        renderProducts();
        renderCart();
    </script>
</body>
</html>