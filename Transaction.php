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
                <label>Customer Name</label>
                <input type="text" id="customerName" class="customer-input" placeholder="Customer Name...">
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <p class="summary-label">Total No. of Items (By Piece)</p>
                    <h2 class="summary-value" id="totalPieceItems">0</h2>
                </div>

                <div class="summary-card">
                    <p class="summary-label">Total No. of Items (By Weight)</p>
                    <h2 class="summary-value" id="totalWeightItems">0</h2>
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
                            <th id="invoicePriceHeader">PRICE</th>
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

    <script src="Transaction.js?v=<?php echo time(); ?>"></script>
</body>
</html>
