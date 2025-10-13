# Na Porta - E-commerce Platform

**Na Porta** (Brazilian Portuguese: "At the Door") is a comprehensive dual-platform (mobile + desktop) e-commerce web application designed specifically for the Brazilian market, focusing on household essentials delivery.

## 🌟 Overview

Na Porta bridges the gap between local suppliers and modern consumers by digitizing the everyday essentials market in Brazil. The platform provides a seamless, safe, and reliable way to order water, gas, cleaning supplies, and groceries with real-time tracking and instant notifications.

## 🚀 Key Features

### 🛒 Customer Features
- **Responsive Design**: Mobile-first approach with desktop optimization
- **Product Catalog**: Water, gas, cleaning supplies, and groceries
- **Smart Shopping Cart**: Real-time updates and stock validation
- **Brazilian Payment Integration**: PIX, Credit Card, and Boleto support
- **Address Management**: CEP integration with ViaCEP API
- **Order Tracking**: Real-time status updates and delivery notifications
- **User Profiles**: Complete account management with order history

### 🔧 Admin Features
- **Dashboard**: Comprehensive analytics and system overview
- **Product Management**: Full CRUD operations with image upload
- **Order Management**: Status tracking and fulfillment workflow
- **User Management**: Customer accounts and activity monitoring
- **Inventory Control**: Stock levels and low-stock alerts
- **Reports & Analytics**: Sales reports and performance metrics
- **Coupon System**: Discount codes and promotional campaigns

### 🇧🇷 Brazilian-Specific Features
- **CPF/CNPJ Validation**: Brazilian tax ID validation
- **CEP Integration**: Automatic address lookup via ViaCEP
- **PIX Payment**: Instant Brazilian payment method
- **LGPD Compliance**: Brazilian data protection law compliance
- **Portuguese Interface**: Complete localization
- **Brazilian Currency**: Real (BRL) formatting and calculations

## 🛠 Technology Stack

### Frontend
- **Framework**: PHP with MDBootstrap (Material Design)
- **Styling**: MDBootstrap + Custom CSS
- **JavaScript**: Vanilla JS with modern ES6+ features
- **Icons**: Font Awesome 6
- **Responsive**: Mobile-first design approach

### Backend
- **Language**: PHP 8+
- **Database**: MySQL 8+
- **Architecture**: MVC-inspired structure
- **Security**: CSRF protection, password hashing, input sanitization

### Infrastructure
- **Server**: Apache/Nginx compatible
- **Hosting**: Vercel-ready (or any PHP hosting)
- **Database**: MySQL/MariaDB
- **File Storage**: Local file system with upload management

## 📁 Project Structure

```
Na Porta/
├── admin/                  # Admin panel
│   ├── assets/            # Admin-specific assets
│   ├── includes/          # Admin headers/footers
│   ├── index.php          # Admin dashboard
│   ├── login.php          # Admin authentication
│   └── ...               # Other admin pages
├── api/                   # API endpoints
│   ├── cart.php          # Shopping cart operations
│   ├── lgpd.php          # LGPD consent handling
│   └── ...               # Other API endpoints
├── assets/                # Frontend assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Static images
├── config/                # Configuration files
│   ├── database.php      # Database connection
│   └── config.php        # Site configuration
├── database/              # Database files
│   └── schema.sql        # Database schema
├── includes/              # Shared includes
│   ├── header.php        # Site header
│   ├── footer.php        # Site footer
│   └── functions.php     # Utility functions
├── pages/                 # Frontend pages
│   ├── auth/             # Authentication pages
│   ├── account/          # User account pages
│   ├── home.php          # Homepage
│   ├── products.php      # Product catalog
│   ├── cart.php          # Shopping cart
│   └── checkout.php      # Checkout process
├── uploads/               # User uploaded files
└── index.php             # Entry point
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer (optional, for dependencies)

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd "Na Porta"
   ```

2. **Database Setup**
   - Create a MySQL database named `na_porta_db`
   - Import the schema: `mysql -u username -p na_porta_db < database/schema.sql`
   - Update database credentials in `config/database.php`

3. **Configuration**
   - Copy `config/config.php` and update settings
   - Set up payment gateway credentials (Mercado Pago)
   - Configure email settings for notifications
   - Set proper file permissions for `uploads/` directory

4. **Web Server Configuration**
   - Point document root to the project directory
   - Ensure mod_rewrite is enabled (Apache)
   - Configure virtual host if needed

5. **Admin Account**
   - Default admin credentials are created during schema import
   - Username: `admin`
   - Password: `admin123`
   - **Change these immediately after first login**

### Environment Configuration

Update `config/config.php` with your settings:

```php
// Site Configuration
define('SITE_NAME', 'Na Porta');
define('SITE_URL', 'http://your-domain.com');
define('SITE_EMAIL', 'contato@naporta.com.br');

// Payment Configuration
define('MERCADO_PAGO_ACCESS_TOKEN', 'your-token');
define('MERCADO_PAGO_PUBLIC_KEY', 'your-public-key');

// Email Configuration
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_USERNAME', 'your-email');
define('SMTP_PASSWORD', 'your-password');
```

## 🔐 Security Features

- **CSRF Protection**: All forms include CSRF tokens
- **Password Security**: Bcrypt hashing with salt
- **Input Sanitization**: All user inputs are sanitized
- **SQL Injection Prevention**: Prepared statements throughout
- **Session Security**: Secure session management
- **File Upload Security**: Type and size validation
- **LGPD Compliance**: Cookie consent and data protection

## 📱 Mobile Optimization

- **Responsive Design**: Works seamlessly on all devices
- **Touch-Friendly**: Large buttons and touch targets
- **Fast Loading**: Optimized images and minimal JavaScript
- **Offline Support**: Basic offline functionality (future enhancement)
- **PWA Ready**: Progressive Web App capabilities

## 🎨 Design System

### Color Palette
- **Primary**: #1976d2 (Blue)
- **Secondary**: #424242 (Dark Gray)
- **Success**: #4caf50 (Green)
- **Warning**: #ff9800 (Orange)
- **Danger**: #f44336 (Red)

### Typography
- **Primary Font**: Roboto
- **Headings**: Bold weights (700)
- **Body Text**: Regular weight (400)
- **UI Elements**: Medium weight (500)

### Components
- **Cards**: Rounded corners with subtle shadows
- **Buttons**: Material Design with hover effects
- **Forms**: Clean inputs with validation states
- **Navigation**: Sticky header with dropdown menus

## 🔌 API Endpoints

### Cart Operations
- `POST /api/cart.php` - Add/update/remove cart items
- Actions: `add`, `update`, `remove`, `clear`

### LGPD Compliance
- `POST /api/lgpd.php` - Handle cookie consent

### Address Lookup
- Integration with ViaCEP API for Brazilian addresses
- Automatic form filling based on postal code

## 📊 Analytics & Reporting

### Admin Dashboard Metrics
- Total users and active customers
- Product inventory and low-stock alerts
- Order statistics and revenue tracking
- Daily/monthly performance reports

### User Analytics
- Order history and spending patterns
- Favorite products and categories
- Delivery preferences and addresses

## 🚀 Deployment

### Production Checklist
- [ ] Update all configuration files
- [ ] Set up SSL certificate
- [ ] Configure production database
- [ ] Set up backup procedures
- [ ] Configure monitoring and logging
- [ ] Test payment integration
- [ ] Verify email functionality
- [ ] Set up CDN for static assets (optional)

### Performance Optimization
- Enable gzip compression
- Set up browser caching headers
- Optimize images and assets
- Use database indexing
- Implement query optimization

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards
- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic and business rules
- Write secure code with input validation
- Test thoroughly before submitting

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- **Email**: contato@naporta.com.br
- **Documentation**: Check the `/docs` folder
- **Issues**: Use GitHub Issues for bug reports

## 🔮 Future Enhancements

### Planned Features
- **Mobile App**: Native iOS and Android applications
- **Real-time Chat**: Customer support integration
- **Subscription Service**: Recurring orders and deliveries
- **Multi-vendor**: Support for multiple suppliers
- **Advanced Analytics**: Machine learning recommendations
- **Inventory Automation**: Smart restocking alerts
- **Delivery Tracking**: GPS integration for real-time tracking

### Technical Improvements
- **API Modernization**: RESTful API with JWT authentication
- **Microservices**: Service-oriented architecture
- **Caching**: Redis integration for performance
- **Queue System**: Background job processing
- **Testing**: Comprehensive unit and integration tests

## 🙏 Acknowledgments

- **MDBootstrap**: For the beautiful UI components
- **Font Awesome**: For the comprehensive icon library
- **ViaCEP**: For Brazilian address lookup service
- **Mercado Pago**: For payment processing integration
- **Brazilian Community**: For feedback and requirements

---

**Na Porta** - Bringing Brazilian household essentials to your door with technology, safety, and convenience. 🏠🚚✨
