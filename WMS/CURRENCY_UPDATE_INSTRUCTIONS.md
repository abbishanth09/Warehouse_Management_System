# Currency Update Instructions

## How to Update Your Database to Sri Lankan Rupees (LKR)

### Option 1: Using phpMyAdmin (Recommended)
1. Open your browser and go to `http://localhost/phpmyadmin`
2. Login with your MySQL credentials
3. Select the `warehouse_management` database from the left sidebar
4. Click on the **SQL** tab at the top
5. Copy the entire contents of `database/update_currency_lkr.sql` 
6. Paste it into the SQL query box
7. Click **Go** to execute the queries

### Option 2: Using Command Line
1. Open Command Prompt in your WMS folder
2. Run: `mysql -u root -p warehouse_management < database/update_currency_lkr.sql`
3. Enter your MySQL password when prompted

## What This Update Does:
- Converts all product prices from USD to LKR (1 USD = 300 LKR)
- Updates order values to reflect LKR amounts
- Updates order items with correct LKR pricing
- Recalculates all totals automatically

## Examples of Price Changes:
- Laptop: $999.99 → Rs. 299,997.00
- Office Chair: $299.99 → Rs. 89,997.00
- Wireless Mouse: $29.99 → Rs. 8,997.00
- Steel Desk: $599.99 → Rs. 179,997.00

## Verification:
After running the update, all prices in your WMS will display with "Rs." prefix instead of "$".

Your application is now ready to work with Sri Lankan Rupees! 🇱🇰
