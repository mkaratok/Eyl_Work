# Kaçlira.com Price Comparison Platform

This is the frontend implementation for Kaçlira.com, a modern price comparison platform built with Nuxt 3 and Tailwind CSS. The platform allows users to compare prices from multiple sellers like n11 and PTT AV M to find the best deals.

## Features

- **Modern UI/UX**: Clean, responsive design with dark mode support
- **Price Comparison**: Compare prices from multiple sellers for the same products
- **Product Catalog**: Browse products with search and filtering capabilities
- **Seller Integration**: Real-time pricing from top Turkish e-commerce platforms
- **Best Deal Finder**: Automatically identifies the best price across sellers
- **User Accounts**: Profile management and saved searches
- **Responsive Design**: Works on all device sizes
- **Internationalization**: Supports English and Turkish
- **Accessibility**: Screen reader support and keyboard navigation
- **Performance**: Optimized for fast loading and smooth interactions

## Tech Stack

- **Nuxt 3**: Vue.js framework for building universal applications
- **Tailwind CSS**: Utility-first CSS framework for styling
- **Vue I18n**: Internationalization support
- **Vitest**: Unit testing framework

## Project Structure

```
├── components/          # Reusable Vue components
├── layouts/             # Page layouts
├── pages/               # Application pages
├── assets/              # Static assets (CSS, images)
├── plugins/             # Nuxt plugins
├── tests/               # Unit tests
├── nuxt.config.ts       # Nuxt configuration
├── tailwind.config.js   # Tailwind CSS configuration
└── package.json         # Project dependencies and scripts
```

## Pages

- **Homepage** (`/`): Welcome page explaining how price comparison works
- **Price Comparison** (`/products`): Product listing with price comparison from sellers like n11 and PTT AV M
- **Product Detail** (`/product/:id`): Detailed price comparison for a specific product
- **Categories** (`/categories`): Browse products by category
- **Best Deals** (`/deals`): Special offers and biggest price differences
- **About** (`/about`): Company information and values
- **User Profile** (`/user`): Account management
- **Admin Panel** (`/admin`): Admin dashboard
- **Seller Panel** (`/seller`): Seller dashboard

## Getting Started

1. Install dependencies:
   ```bash
   npm install
   ```

2. Start the development server:
   ```bash
   npm run dev
   ```

3. Build for production:
   ```bash
   npm run build
   ```

4. Run tests:
   ```bash
   npm run test
   ```

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions.

## Project Status

✅ **COMPLETED** - This project is fully functional and ready for production deployment.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a pull request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.