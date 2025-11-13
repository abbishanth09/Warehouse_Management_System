# Background Image Setup Instructions

## Current Setup
Your login form now uses a locally imported SVG background image located at:
`assets/images/warehouse-background.svg`

## How to Replace with Your Own Image

### Option 1: Replace the SVG (Recommended)
1. Find a high-quality warehouse image (JPG, PNG, or SVG)
2. Rename it to `warehouse-background.svg` (or keep the same extension)
3. Place it in the `assets/images/` folder
4. Update the CSS in `login.php` if you changed the file extension

### Option 2: Add a New Image
1. Place your image in `assets/images/` folder
2. Update the CSS in `login.php` line ~53:
   ```css
   background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.6)), 
               url('assets/images/YOUR_IMAGE_NAME.jpg') no-repeat center center fixed;
   ```

## Recommended Image Specifications
- **Resolution**: 1920x1080 or higher
- **Format**: JPG, PNG, or SVG
- **Theme**: Warehouse, logistics, or industrial setting
- **Quality**: High resolution for crisp display

## Image Sources (Free to Use)
- **Unsplash**: https://unsplash.com/s/photos/warehouse
- **Pexels**: https://www.pexels.com/search/warehouse/
- **Pixabay**: https://pixabay.com/images/search/warehouse/

## Current Features
- ✅ Local image (no internet dependency)
- ✅ Professional warehouse-themed SVG
- ✅ Dark overlay for better text readability
- ✅ Responsive design
- ✅ Glass morphism effect on login container

Your login page now has a professional, warehouse-themed background that loads from your local server!
