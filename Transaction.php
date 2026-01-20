<?php
// ScrapTrack Transaction Management - PHP Backend
session_start();

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// DB connection
require_once __DIR__ . '/db_connect.php';

// Handle new customer form submission (same behavior as Dashboard)
$message = '';
$last_added_customer = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $type = trim($_POST['customer_type'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        $message = 'Customer name is required.';
    } else {
        $sql = "{call sp_AddCustomer(?, ?, ?, ?)}";
        $params = [$name, $type ?: null, $contact ?: null, $address ?: null];
        $stmt = sqlsrv_prepare($conn, $sql, $params);

        if ($stmt && sqlsrv_execute($stmt)) {
            $message = 'Customer added successfully!';
            $last_added_customer = $name;
        } else {
            $message = 'Failed to add customer. Please try again.';
        }
    }
}

// Get user information
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'ExoticNellie69';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction - ScrapTrack</title>
    <link rel="stylesheet" href="Transaction.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <img src="Logos-Icons/3.png" alt="ScrapTrack Logo" class="logo-icon">
            <span class="logo-text">ScrapTrack</span>
        </div>

        <nav class="nav-menu">
            <div class="nav-section">
                <p class="nav-label">General</p>
                <a href="Dashboard.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="Inventory.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Inventory</span>
                </a>
                <a href="Transaction.php" class="nav-item active">
                    <span class="nav-icon">💳</span>
                    <span>Transaction</span>
                </a>
                <a href="TransactionRecords.php" class="nav-item">
                    <span class="nav-icon">📋</span>
                    <span>Transaction Records</span>
                </a>
                <a href="EmployeeManagement.php" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Employee Management</span>
                </a>
            </div>

            <div class="nav-section">
                <p class="nav-label">Settings</p>
                <a href="AccountSettings.html" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span>Account Settings</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <span class="nav-icon">🚪</span>
                    <span>Log Out</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <img src="Logos-Icons/3.png" alt="ScrapTrack" class="footer-logo">
            <span class="footer-text">ScrapTrack</span>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div>
                <h1>Transaction</h1>
                <p class="subtitle">Record transaction here</p>
            </div>
        </header>

        <!-- Transaction Form -->
        <section class="transaction-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Operation</label>
                    <select id="operationType" class="form-select">
                        <option value="">(Receiving or Dispatching)</option>
                        <option value="receiving">Receiving</option>
                        <option value="dispatching">Dispatching</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Item <span class="stock-info" id="stockInfo">(Select an item to see stock)</span></label>
                    <select id="itemSelect" class="form-select">
                        <option value="">Loading items...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" id="quantity" class="form-input" placeholder="Quantity" min="1">
                </div>

                <button class="btn-add" id="addItemBtn">Add</button>
            </div>
        </section>

        <!-- Transaction Details -->
        <section class="transaction-details">
            <div class="details-header">
                <h3 id="operationTitle">Receiving / Dispatching</h3>
                <p class="details-subtitle">Review the list here before saving</p>
            </div>

            <div class="customer-section">
                <label>Customer</label>
                <div style="display:flex; gap:12px; align-items:center;">
                    <select id="customerSelect" class="customer-input">
                        <option value="">-- Select a Customer --</option>
                    </select>
                    <button type="button" class="btn-add" onclick="openCustomerModal()">Add New Customer</button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <p class="summary-label">Total No. of Items</p>
                    <h2 class="summary-value" id="totalItems">0</h2>
                </div>
                <div class="summary-card">
                    <p class="summary-label">Total Value</p>
                    <h2 class="summary-value" id="totalValue">₱0.00</h2>
                </div>
            </div>

            <!-- Transaction Table -->
            <div class="table-container">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>ITEM</th>
                            <th>CATEGORY</th>
                            <th>QTY TYPE</th>
                            <th>CURRENT QUANTITY</th>
                            <th>EXCHANGE QUANTITY</th>
                            <th>BUYING / SELLING PRICE</th>
                            <th>EXCHANGE AMOUNT</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="transactionTableBody">
                        <!-- Transaction items will be added here dynamically -->
                    </tbody>
                </table>
            </div>

            <!-- Action Buttons -->
            <div class="action-bar">
                <button class="btn-save" id="saveBtn">Save</button>
                <button class="btn-clear" id="clearBtn">Clear</button>
            </div>
        </section>
    </main>

    <!-- Invoice Modal -->
    <div class="modal" id="invoiceModal">
        <div class="modal-backdrop" onclick="closeInvoice()"></div>
        <div class="invoice-container" id="invoiceContainer">
            <div class="invoice-content">
                <!-- Invoice Header -->
                <div class="invoice-header">
                    <div class="invoice-logo">
                        <img src="Logos-Icons/4.png" alt="ScrapTrack" class="invoice-logo-img">
                        <h2>ScrapTrack</h2>
                    </div>
                    <div class="invoice-title">
                        <h1>INVOICE</h1>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="invoice-details">
                    <div class="invoice-info">
                        <p><strong>Date:</strong> <span id="invoiceDate"><?php echo date('m/d/Y'); ?></span></p>
                        <p><strong>Transaction ID:</strong> <span id="invoiceId">-</span></p>
                        <p><strong>Invoiced To:</strong> <span id="invoiceTo">-</span></p>
                        <p><strong># Qty by Piece:</strong> <span id="invoiceQtyPiece">0</span></p>
                    </div>
                    <div class="invoice-info">
                        <p><strong>Trans. Type:</strong> <span id="invoiceType">-</span></p>
                        <p><strong>No. of Items:</strong> <span id="invoiceItems">0</span></p>
                        <p><strong># Qty by Weight:</strong> <span id="invoiceQtyWeight">0</span></p>
                    </div>
                </div>

                <!-- Invoice Table -->
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>ITEM</th>
                            <th>BUYING PRICE</th>
                            <th>QUANTITY</th>
                            <th>AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceTableBody">
                        <!-- Invoice items will be inserted here -->
                    </tbody>
                </table>

                <!-- Invoice Total -->
                <div class="invoice-total">
                    <div class="total-row">
                        <span class="total-label">Total Amount:</span>
                        <span class="total-amount" id="invoiceTotal">₱0.00</span>
                    </div>
                </div>

                <!-- Invoice Buttons -->
                <div class="invoice-buttons no-print">
                    <button class="btn-exit" onclick="closeInvoice()">Exit</button>
                    <button class="btn-print" onclick="printInvoice()">Print</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Expose last added customer name to JS for auto-select after load
        window.lastAddedCustomerName = <?php echo json_encode($last_added_customer); ?>;
    </script>
    <script src="Transaction.js"></script>
    <script>
        // Customer modal functions (mirroring Dashboard)
        function openCustomerModal() {
            var m = document.getElementById('customerModal');
            if (m) m.style.display = 'block';
        }

        function closeCustomerModal() {
            var m = document.getElementById('customerModal');
            if (m) m.style.display = 'none';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('customerModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>

    <!-- Customer Modal (copied from Dashboard) -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Customer</h2>
                <span class="close" onclick="closeCustomerModal()">&times;</span>
            </div>
            <form method="POST" action="Transaction.php">
                <div class="modal-body">
                    <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="customer_name">Customer Name *</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_type">Customer Type</label>
                        <select id="customer_type" name="customer_type">
                            <option value="">Select Type</option>
                            <option value="Regular">Regular</option>
                            <option value="VIP">VIP</option>
                            <option value="Wholesale">Wholesale</option>
                            <option value="Retail">Retail</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeCustomerModal()">Cancel</button>
                    <button type="submit" name="add_customer" class="btn-submit">Add Customer</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* Scoped modal styles to avoid conflict with invoice modal */
        #customerModal.modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        #customerModal .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        #customerModal .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #customerModal .modal-header h2 {
            margin: 0;
            color: #333;
        }

        #customerModal .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        #customerModal .close:hover {
            color: #000;
        }

        #customerModal .modal-body {
            padding: 20px;
        }

        #customerModal .form-group {
            margin-bottom: 15px;
        }

        #customerModal .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        #customerModal .form-group input,
        #customerModal .form-group select,
        #customerModal .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        #customerModal .form-group textarea {
            resize: vertical;
        }

        #customerModal .modal-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        #customerModal .btn-cancel {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        #customerModal .btn-submit {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        #customerModal .btn-submit:hover {
            background-color: #0056b3;
        }

        #customerModal .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        #customerModal .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        #customerModal .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</body>
</html>
