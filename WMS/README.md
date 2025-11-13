# Warehouse Management System (WMS)

A comprehe### **Step 5: Access the Application**
1. Open your web browser
2. Navigate to: `http://localhost/WMS/`
3. You'll be redirected to the login page
4. Use these credentials:
   - **Username**: `admin`
   - **Password**: `admin`
5. After login, you'll see the dashboard with sample data

## 🔐 **Login System**

The application includes a secure login system:
- **Login URL**: `http://localhost/WMS/login.php`
- **Default Credentials**: 
  - Username: `admin`
  - Password: `admin`
- **Features**:
  - Session management
  - Automatic redirect to intended page after login
  - Session timeout (24 hours)
  - Secure logout functionality
  - Login required for all pagese web-based Warehouse Management System built with PHP, MySQL, HTML, CSS, and JavaScript for CST 226-2 Web Application Development Assignment 3.

## 🚀 Features

- **Dashboard Overview**: Real-time statistics and key metrics
- **Inventory Management**: Complete product listing with search and filter capabilities
- **Product Management**: Add, edit, and delete products with validation
- **Reports & Analytics**: Comprehensive reporting with category breakdowns and insights
- **Low Stock Alerts**: Automatic warnings for products below minimum stock levels
- **Responsive Design**: Mobile-friendly interface
- **Real-time Search**: Dynamic filtering and sorting
- **Data Validation**: Client-side and server-side validation for data integrity

## 📋 System Requirements

- **Web Server**: Apache (included with XAMPP)
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Browser**: Modern web browser (Chrome, Firefox, Safari, Edge)

## 🛠️ Installation & Setup

### Step 1: Download and Setup XAMPP
1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP on your system
3. Start Apache and MySQL services from XAMPP Control Panel

### Step 2: Setup Project Files
1. Extract the WMS project files to `C:\xampp\htdocs\WMS\`
2. Ensure all files are in the correct directory structure

### Step 3: Create Database
1. Open phpMyAdmin in your browser: `http://localhost/phpmyadmin`
2. Create a new database named `warehouse_management`
3. Import the database schema:
   - Navigate to the SQL tab in phpMyAdmin
   - Copy and paste the contents of `database/schema.sql`
   - Click "Go" to execute the SQL commands

### Step 4: Configure Database Connection
1. Open `config/database.php`
2. Verify the database settings:
   - Host: `localhost`
   - Database: `warehouse_management`
   - Username: `root`
   - Password: `` (empty for default XAMPP setup)

### Step 5: Access the Application
1. Open your web browser
2. Navigate to: `http://localhost/WMS/`
3. The application should load with sample data

## 📁 Project Structure

```
WMS/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       └── script.js          # JavaScript functionality
├── config/
│   └── database.php           # Database connection
├── database/
│   └── schema.sql            # Database schema and sample data
├── includes/
│   ├── functions.php         # Common PHP functions
│   ├── header.php           # Header template
│   └── footer.php           # Footer template
├── index.php                # Home/Dashboard page
├── inventory.php            # Inventory listing page
├── product_form.php         # Add/Edit product form
├── reports.php              # Reports and analytics
└── README.md               # This file
```

## 🎯 Application Pages

### 1. Dashboard (index.php)
- Overview statistics and key metrics
- Low stock alerts
- Recent product additions
- Quick action buttons

### 2. Inventory Management (inventory.php)
- Complete product listing
- Search and filter functionality
- Sort by various columns
- Edit and delete products
- Low stock highlighting

### 3. Product Form (product_form.php)
- Add new products
- Edit existing products
- Form validation
- Auto-SKU generation
- Category management

### 4. Reports (reports.php)
- Category-wise breakdown
- High-value products
- Low stock reports
- Recent activity
- Printable format

## 🔧 Features & Functionality

### Search & Filter
- Real-time search by product name, SKU, or description
- Filter by category
- Quick filter for low stock items

### Validation
- Required field validation
- SKU format validation (3-20 alphanumeric characters)
- Price and quantity validation
- Duplicate SKU prevention

### Low Stock Management
- Configurable minimum stock levels
- Visual alerts for low stock items
- Low stock dashboard widget
- Dedicated low stock report

### User Experience
- Clean, professional design
- Responsive layout for mobile devices
- Intuitive navigation
- Success/error messaging
- Confirmation dialogs for destructive actions

## 🎨 Design Features

- **Modern UI**: Clean, professional interface with card-based layout
- **Color Coding**: Visual indicators for different states (low stock, categories)
- **Icons**: Font-based icons for better visual appeal
- **Responsive**: Mobile-friendly design that works on all devices
- **Print Support**: Print-optimized reports page

## 🔐 Security Features

- **Input Sanitization**: All user inputs are sanitized to prevent XSS
- **SQL Injection Prevention**: Prepared statements for all database queries
- **Data Validation**: Both client-side and server-side validation
- **Error Handling**: Proper error handling and user feedback

## 📊 Sample Data

The system comes with pre-loaded sample data including:
- 8 different products across multiple categories
- Various product types (Electronics, Furniture, Office Supplies)
- Different stock levels to demonstrate low stock alerts
- Realistic pricing and quantities

## 🧪 Testing

### Test Cases to Verify:
1. **Add Product**: Create a new product with all required fields
2. **Edit Product**: Modify an existing product's information
3. **Delete Product**: Remove a product with confirmation
4. **Search**: Test search functionality with various keywords
5. **Filter**: Filter products by category
6. **Low Stock**: Verify low stock alerts appear correctly
7. **Validation**: Test form validation with invalid data
8. **Reports**: Generate and view various reports

## 🐛 Troubleshooting

### Common Issues:

1. **Database Connection Error**
   - Verify MySQL is running in XAMPP
   - Check database credentials in `config/database.php`
   - Ensure database `warehouse_management` exists

2. **Page Not Found (404)**
   - Verify files are in `C:\xampp\htdocs\WMS\`
   - Check Apache is running in XAMPP
   - Access via `http://localhost/WMS/` not `file://`

3. **Styling Issues**
   - Check if `assets/css/style.css` exists
   - Verify file permissions
   - Clear browser cache

4. **JavaScript Not Working**
   - Check if `assets/js/script.js` exists
   - Check browser console for errors
   - Verify JavaScript is enabled

## 📚 Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **Server**: Apache (XAMPP)
- **Design**: Responsive CSS Grid/Flexbox
- **Icons**: Unicode/Emoji characters

## 🎓 Academic Notes

This project fulfills the requirements for CST 226-2 Web Application Development Assignment 3:

- ✅ Dynamic web application using PHP, MySQL, HTML, CSS, JavaScript
- ✅ Minimum 3 pages (4 pages implemented)
- ✅ Information display page with list/update/delete functionality
- ✅ Add/update/delete page with validation
- ✅ MySQL database interaction
- ✅ Clean, consistent design
- ✅ Well-organized, commented code
- ✅ User-friendly interface

## 📝 Future Enhancements

Potential improvements for future versions:
- User authentication and roles
- Advanced reporting with charts
- Barcode scanning integration
- Import/export functionality
- Audit trails and history
- Email notifications for low stock
- Advanced search with filters
- Backup and restore functionality

## 📞 Support

For questions or issues:
1. Check the troubleshooting section above
2. Verify all installation steps were followed correctly
3. Ensure XAMPP services are running
4. Check browser console for JavaScript errors

---

**Developed for CST 226-2 Web Application Development - Assignment 3**  
**Evaluation Period: August 4-7, 2025**

## 📦 Publishing to GitHub (safe step-by-step)

Follow these steps to publish this project to your GitHub account while keeping secrets out of the repository.

1) Prepare the repo locally

   - Ensure `.gitignore` exists (it already excludes `config/database.php`, `logs/`, `vendor/`, etc.).
   - Make sure `config/database.php.example` is present and contains placeholder values.

2) Verify no secrets are staged or committed

   - If you have a `config/database.php` with real credentials, do NOT add it. If it exists locally, keep it untracked.

3) Initialize git and create the initial commit (PowerShell)

```powershell
cd C:\xampp\htdocs\WMS
git init
git add .
git commit -m "Initial import — add .gitignore and example configs; keep real secrets out of repo"
```

4) Create a GitHub repository

   - Option A (GitHub website): Create a new repo at https://github.com/new — choose Private if unsure. Copy the remote URL.
   - Option B (GitHub CLI): If you have `gh` installed, run:

```powershell
gh repo create YOUR_USER/YOUR_REPO --private --source=. --remote=origin --push
```

5) Add remote and push (if you used the website option)

```powershell
git remote add origin https://github.com/YOUR_USER/YOUR_REPO.git
git branch -M main
git push -u origin main
```

6) If you accidentally committed secrets — remove them from history

   - Use the BFG Repo Cleaner (simple) or `git-filter-repo` (recommended). Example with BFG:

```powershell
# Run from outside the repo directory
# bfg --delete-files config/database.php
git reflog expire --expire=now --all
git gc --prune=now --aggressive
git push --force
```

7) Final checklist

   - Verify the repository on github.com contains no secrets (search for `password` or `DB_HOST`).
   - Add a `LICENSE` if you want to publish terms.
   - Update this README with any additional project notes.

If you want, I can run the `git init` and initial commit commands for you here, or create a `CONTRIBUTING.md` and small publish checklist in the repo. Which would you like next?
