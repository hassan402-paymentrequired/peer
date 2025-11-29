# Starpick - Fantasy Football Platform

A modern fantasy football platform built with Laravel 12 and React, featuring peer-to-peer competitions, tournaments, and real-time football statistics.

## 🚀 Features

### Core Features

- **Peer Competitions**: Create and join peer-to-peer fantasy football contests
- **Tournaments**: Participate in daily tournaments with multiple participants
- **Real-time Statistics**: Live player statistics and match updates
- **Wallet System**: Integrated wallet for entry fees and prize distributions
- **Phone Authentication**: SMS-based authentication system for Nigerian users
- **Push Notifications**: Real-time notifications for match updates and results

### User Features

- **Squad Building**: Select main and substitute players for competitions
- **Live Scoring**: Real-time point calculation based on player performance
- **Competition History**: Track completed contests and winnings
- **Leaderboards**: View rankings and top performers
- **Withdrawal System**: Request withdrawals of winnings

### Admin Features (API)

- **Competition Management**: Oversee peer contests and tournaments
- **User Management**: Manage user accounts and transactions
- **Statistics Dashboard**: Monitor platform performance and user engagement

## 🛠 Tech Stack

### Backend

- **Laravel 12** - PHP framework
- **MySQL** - Primary database
- **JWT Authentication** - Token-based authentication
- **Laravel Reverb** - WebSocket server for real-time features
- **Queue System** - Background job processing
- **SMS Integration** - Termii SMS service for notifications

### Frontend

- **React 19** - Frontend framework
- **Inertia.js** - Full-stack framework
- **TypeScript** - Type-safe JavaScript
- **Tailwind CSS 4** - Utility-first CSS framework
- **Radix UI** - Accessible component primitives
- **Lucide React** - Icon library

### Additional Services

- **Flutterwave** - Payment processing
- **Sports API** - Football data and statistics
- **Web Push** - Browser push notifications

## 📋 Prerequisites

- PHP 8.2 or higher
- Node.js 18 or higher
- MySQL 8.0 or higher
- Composer
- npm or yarn

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd starpick
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
```

### 4. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configure Environment Variables

Edit `.env` file with your configuration:

```env
# Application
APP_NAME=Starpick
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=starpick_server
DB_USERNAME=root
DB_PASSWORD=your_password

# SMS Configuration (Termii)
SMS_DRIVER=termii
TERMII_API_URL=https://v3.api.termii.com
TERMII_API_KEY=your_termii_api_key
TERMII_SENDER_ID=your_sender_id

# Payment (Flutterwave)
FLW_PUBLIC_KEY=your_flutterwave_public_key
FLW_SECRET_KEY=your_flutterwave_secret_key
FLW_ENCRYPTION_KEY=your_encryption_key

# Sports API
SPORT_API_KEY=your_sports_api_key

# JWT
JWT_SECRET=your_jwt_secret

# Broadcasting (Laravel Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Push Notifications
VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
```

### 6. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 7. Build Frontend Assets

```bash
npm run build
```

## 🏃‍♂️ Running the Application

### Development Mode

```bash
# Start all services (recommended)
composer run dev

# Or start services individually:
php artisan serve                    # Laravel server
php artisan queue:listen            # Queue worker
php artisan reverb:start            # WebSocket server
npm run dev                         # Vite dev server
```

### Production Mode

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📁 Project Structure

```
├── app/
│   ├── Http/Controllers/           # API and web controllers
│   │   ├── Auth/                  # Authentication controllers
│   │   ├── Peer/                  # Peer competition controllers
│   │   └── Tournament/            # Tournament controllers
│   ├── Models/                    # Eloquent models
│   ├── Services/                  # Business logic services
│   ├── Notifications/             # Notification classes
│   ├── Jobs/                      # Background jobs
│   └── Utils/                     # Helper utilities
├── resources/
│   ├── js/
│   │   ├── components/            # Reusable React components
│   │   ├── pages/                 # Page components
│   │   ├── layouts/               # Layout components
│   │   └── types/                 # TypeScript type definitions
│   └── views/                     # Blade templates
├── database/
│   ├── migrations/                # Database migrations
│   └── seeders/                   # Database seeders
└── routes/
    ├── web.php                    # Web routes
    ├── auth.php                   # Authentication routes
    └── api.php                    # API routes
```

## 🎮 Key Features Explained

### Peer Competitions

Users can create peer-to-peer fantasy football contests where:

- Entry fees are collected from participants
- Winners receive prize money based on sharing ratios
- Real-time scoring based on actual player performance

### Squad Building

- Select main and substitute players for each position
- Star rating system for player difficulty/reward balance
- Formation-based team building

### Wallet System

- Integrated wallet for managing funds
- Support for deposits via Flutterwave
- Withdrawal requests with admin approval
- Transaction history tracking

### Phone Authentication

- SMS-based registration and login
- Nigerian phone number validation
- OTP verification system
- Password reset via SMS

## 🔧 Configuration

### SMS Service (Termii)

Configure SMS settings in `config/sms.php`:

```php
'termii' => [
    'api_url' => env('TERMII_API_URL'),
    'api_key' => env('TERMII_API_KEY'),
    'sender_id' => env('TERMII_SENDER_ID'),
],
```

### Payment Integration (Flutterwave)

Payment settings are configured in the environment variables and integrated throughout the wallet system.

### Sports Data

The application fetches live football data including:

- Fixtures and match schedules
- Player statistics
- Team information
- League standings

## 🧪 Testing

```bash
# Run PHP tests
php artisan test

# Run frontend linting
npm run lint

# Type checking
npm run types
```

## 📱 API Documentation

### Authentication Endpoints

- `POST /register` - User registration
- `POST /login` - User login
- `POST /logout` - User logout
- `POST /forgot-password` - Password reset request

### Peer Competition Endpoints

- `GET /peers` - List available peers
- `POST /peers` - Create new peer
- `GET /peers/{peer}` - View peer details
- `POST /peers/{peer}/join` - Join peer competition

### Tournament Endpoints

- `GET /tournaments` - List tournaments
- `POST /tournaments/{tournament}/join` - Join tournament

### Wallet Endpoints

- `GET /wallet` - Get wallet balance
- `POST /wallet/fund` - Fund wallet
- `POST /wallet/withdraw` - Request withdrawal

## 🚀 Deployment

### Production Checklist

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Configure production database
3. Set up SSL certificates
4. Configure web server (Nginx/Apache)
5. Set up process manager (Supervisor) for queues
6. Configure cron jobs for scheduled tasks
7. Set up monitoring and logging

### Queue Workers

```bash
# Supervisor configuration for queue workers
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### Scheduled Tasks

Add to crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards for PHP
- Use TypeScript for all frontend code
- Write tests for new features
- Update documentation as needed

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:

- Create an issue in the repository
- Contact the development team
- Check the documentation

## 🔄 Recent Updates

### Phone Authentication Migration

- Migrated from email-based to phone-based authentication
- Added SMS notifications via Termii
- Updated all authentication flows
- Enhanced user registration process

### Enhanced Competition Display

- Improved completed peers page with winner information
- Added comprehensive statistics display
- Enhanced UI/UX for better user experience

---

**Built with ❤️ for fantasy football enthusiasts**
