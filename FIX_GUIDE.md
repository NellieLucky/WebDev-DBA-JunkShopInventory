# 🔧 INVENTORY DATA NOT SHOWING - STEP BY STEP FIX GUIDE

## 📋 STEP 1: Run the Diagnostic Script (5 minutes)

1. **Open your browser** and go to:
   ```
   http://localhost/WebDev-DBA-JunkShopInventory/diagnose.php
   ```

2. **Check all the results**:
   - ✓ Green checks = Working fine
   - ✗ Red X = Problem found
   - ⚠ Orange warning = Action needed

3. **Take a screenshot** of the results and note which items failed.

---

## 📊 STEP 2: Based on Diagnostic Results

### **SCENARIO A: Database Connection Failed (Red ✗)**

**Problem:** Cannot connect to SQL Server

**Fix:**
1. Open [db_connect.php](db_connect.php)
2. Verify these settings:
   ```php
   $serverName = "localhost\\SQLEXPRESS";
   $connectionOptions = [
       "Database" => "JSDatabase",
       "TrustServerCertificate" => true,
       "Encrypt" => false,
   ];
   ```

3. **Check if SQL Server is running:**
   - On Windows: Open Services (services.msc)
   - Look for "SQL Server (SQLEXPRESS)"
   - Right-click → Start (if stopped)

4. **Verify Database exists:**
   - Open SQL Server Management Studio (SSMS)
   - Check if "JSDatabase" exists in the database list
   - If not, run [JSDatabase.sql](JSDatabase.sql) to create it

5. **Refresh diagnostic page** to verify connection

---

### **SCENARIO B: Category Table is Empty (Orange ⚠)**

**Problem:** No categories exist in the database

**Fix:**
1. Go to the **Inventory Page**: http://localhost/WebDev-DBA-JunkShopInventory/Inventory.html
2. Click **"Add Item"** button
3. In the Category dropdown, you may see an option to add a category
4. Enter category names like:
   - Aluminum
   - Copper
   - Plastic
   - Paper
   - Glass
   - Steel

**Or add categories directly via SQL:**
1. Open SQL Server Management Studio
2. Run this query:
   ```sql
   INSERT INTO Category (Category_Name) VALUES 
   ('Aluminum'),
   ('Copper'),
   ('Plastic'),
   ('Paper'),
   ('Glass'),
   ('Steel');
   ```

---

### **SCENARIO C: Inventory Table is Empty (Orange ⚠)**

**Problem:** No items in inventory yet

**Fix:**
1. Categories must exist first (complete Scenario B first)
2. Go to **Inventory Page**: http://localhost/WebDev-DBA-JunkShopInventory/Inventory.html
3. Click **"Add Item"** button
4. Fill in the form:
   - Item Name: (e.g., "Aluminum Cans")
   - Category: Select one
   - Quantity: Enter a number
   - Buying Price: Enter price
   - Selling Price: Enter price
   - Qty Type: Select "by kilo" or "by pieces"
5. Click **"Save Item"**
6. Repeat for 3-5 items

---

### **SCENARIO D: API Error (Red ✗ on API test)**

**Problem:** inventory_api.php is returning an error

**Fix:**
1. Open browser **Developer Tools** (Press F12)
2. Go to **Console** tab
3. Go to **Inventory Page**: http://localhost/WebDev-DBA-JunkShopInventory/Inventory.html
4. Look for **red error messages** in console
5. Screenshot the error and follow the specific error message

**Common Errors:**
- "Cannot find procedure sp_GetTop5Sales" → Need to create stored procedures
- "Column 'qty_type' does not exist" → Database schema is missing this column
- SQL Connection error → Back to Scenario A

---

## 🖥️ STEP 3: Clear Browser Cache

Sometimes old data is cached:

1. **Close the Inventory page completely**
2. **Clear browser cache**:
   - Chrome/Edge: Ctrl+Shift+Delete
   - Firefox: Ctrl+Shift+Delete
   - Select "All time" and check only "Cookies and cached images"
   - Click "Clear"

3. **Hard refresh the page**:
   - Ctrl+F5 (Windows)
   - Cmd+Shift+R (Mac)

4. **Go back to Inventory page**

---

## 🔍 STEP 4: Check Browser Console

The console shows exactly what's happening:

1. Open Inventory page: http://localhost/WebDev-DBA-JunkShopInventory/Inventory.html
2. Press **F12** to open Developer Tools
3. Click **Console** tab
4. Look for messages starting with:
   - 🔄 = Loading process
   - 📡 = Network request
   - 📦 = Data received
   - ✓ = Success
   - ❌ = Error

5. **If you see green ✓ messages:**
   - Data is loading successfully
   - Check if table is rendered below

6. **If you see red ❌ messages:**
   - Note the exact error
   - Follow that specific error's solution above

---

## 🚀 STEP 5: Verify Data is Showing

Once you complete the above steps:

1. Go to: http://localhost/WebDev-DBA-JunkShopInventory/Inventory.html
2. You should see:
   - ✓ Items listed in the table
   - ✓ Categories displayed
   - ✓ Stats cards updated (Total Items, Total Weight, Total Value)
   - ✓ No "No items found" message

---

## 📞 TROUBLESHOOTING CHECKLIST

- [ ] Database connection is working (Green ✓ in diagnostic)
- [ ] Categories exist in database (Green ✓ in diagnostic)
- [ ] Inventory items exist (Green ✓ in diagnostic)
- [ ] Browser console shows "✓ Successfully loaded X items" (NO red ❌ errors)
- [ ] Page is accessed via http://localhost/ (not file://)
- [ ] Browser cache is cleared
- [ ] Page is hard refreshed (Ctrl+F5)

---

## 📁 Key Files to Check

- **[db_connect.php](db_connect.php)** - Database connection settings
- **[inventory_api.php](inventory_api.php)** - API that fetches data
- **[Inventory.js](Inventory.js)** - Frontend logic (has enhanced debugging now)
- **[Inventory.html](Inventory.html)** - Frontend page

---

## ✅ When Everything Works

You should see:
1. ✓ No errors in browser console
2. ✓ Inventory table populated with items
3. ✓ Stats cards show actual numbers
4. ✓ Search function works
5. ✓ Add/Edit/Delete buttons appear

---

**Need help? Check the diagnostic page again at:**
```
http://localhost/WebDev-DBA-JunkShopInventory/diagnose.php
```
