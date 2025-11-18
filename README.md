# Final Thesis System - Disaster Management Platform

## Overview

The **Final Thesis System** is a comprehensive web-based disaster management platform designed specifically for Biliran province in the Philippines. The system provides real-time weather monitoring, hazard visualization, and disaster response coordination to help local authorities and residents prepare for and respond to natural disasters effectively.

## System Architecture

### Technology Stack

**Frontend:**
- HTML5, CSS3, JavaScript
- Alpine.js (reactive framework)
- Chart.js (data visualization)
- Leaflet.js (interactive maps)
- Tailwind CSS (styling framework)

**Backend:**
- PHP 8.x
- MySQL/MariaDB (database)
- WebSocket (real-time messaging)
- RESTful API architecture

**External Services:**
- Open Meteo API (weather data)
- PAGASA API (weather advisories)
- Leaflet Heatmap Plugin (hazard visualization)

### Directory Structure

```
final-thesis-system/
├── .htaccess                    # Apache configuration
├── index.php                    # Entry point (forbidden access)
├── login.php                    # Authentication entry
├── dashboard.php               # Main dashboard
├── statistics.php              # Statistical analysis
├── heatmap.php                 # Hazard heatmap visualization
├── chloropleth.php             # Choropleth map visualization
├── barangay.php                # Barangay management
├── municipality.php            # Municipality management
├── hazard_types.php            # Hazard type management
├── hazard_data.php             # Hazard data management
├── municipality_users.php      # User management
├── profile.php                 # User profile management
├── chat.php                    # Real-time messaging
├── logout.php                  # Session termination
├── session_check.php           # Session validation
├── access_control.php          # Role-based access control
├── db.php                      # Database connection
├── flash.php                   # Flash message system
├── pagasa-monitor.js           # PAGASA advisory monitoring
├── train.py                    # GeoJSON processing utility
├── websocket_server.php       # WebSocket server
├── assets/                     # Static assets
│   ├── css/                    # Stylesheets
│   ├── js/                     # JavaScript files
│   ├── images/                 # Image assets
│   └── data/                   # GeoJSON data files
├── api/                        # REST API endpoints
│   ├── users/                  # User management APIs
│   ├── municipality/           # Municipality APIs
│   ├── barangay/               # Barangay APIs
│   ├── hazard/                 # Hazard management APIs
│   ├── hazard_type/            # Hazard type APIs
│   ├── statistics/             # Statistical APIs
│   └── messages/               # Messaging APIs
├── pagasa_advisories/          # Downloaded PAGASA advisories
├── uploads/                    # User uploaded files
├── db/                         # Database schema and dumps
├── Mail/                       # Email system
├── ajax/                       # AJAX handlers
└── vendor/                     # Composer dependencies
```

## Core Features

### 1. Dashboard
**Location:** `dashboard.php`
**Features:**
- Real-time weather monitoring (temperature, precipitation, humidity, wind)
- 5-day weather forecast
- Automated rainfall warning system
- Tropical cyclone tracking via embedded maps
- Time-based greetings for users
- Role-based navigation

**Weather Data Sources:**
- Open Meteo API for current conditions and forecasts
- PAGASA monitoring for severe weather bulletins
- Embedded Windy.com for hurricane tracking

### 2. Statistics Module
**Location:** `statistics.php`
**Features:**
- Population exposure analysis by hazard type
- Historical disaster data visualization
- Interactive charts (population distribution, disaster history)
- PDF report generation
- Municipality and barangay-specific filtering

**Supported Hazard Types:**
- Flood
- Landslide
- Storm Surge

### 3. Heatmap Visualization
**Location:** `heatmap.php`
**Features:**
- Interactive hazard intensity mapping
- Real-time heatmap generation from GeoJSON data
- Municipality and barangay filtering
- Multiple map layer options (satellite, dark, street, terrain)
- Dynamic radius adjustment based on zoom level

### 4. Choropleth Mapping
**Location:** `chloropleth.php`
**Features:**
- Administrative boundary visualization
- Population risk assessment by hazard type
- Color-coded risk levels (Low, Moderate, High, Very High)
- Interactive info panels
- Time-period filtering

### 5. Data Management

#### Barangay Management
**Location:** `barangay.php`
**Features:**
- CRUD operations for barangay data
- Geographic boundary management
- Population data tracking

#### Municipality Management
**Location:** `municipality.php`
**Features:**
- Administrative division management
- Geographic boundary visualization
- Population statistics

#### Hazard Types Management
**Location:** `hazard_types.php`
**Features:**
- Hazard classification system
- Risk level definitions
- Impact assessment parameters

#### Hazard Data Management
**Location:** `hazard_data.php`
**Features:**
- Hazard incident recording
- Geographic data association
- Historical data maintenance

### 6. User Management
**Location:** `municipality_users.php`
**Features:**
- Multi-role user system (Administrator, User)
- Profile management
- Password security
- Municipality-based access control

### 7. Real-time Communication
**Location:** `chat.php`
**Features:**
- WebSocket-based messaging
- Real-time conversation updates
- User presence indicators
- Message history
- Browser notifications

### 8. Profile Management
**Location:** `profile.php`
**Features:**
- User profile editing
- Password change functionality
- Profile picture upload
- Municipality assignment

## Security Features

### Role-Based Access Control (RBAC)
**Implementation:** `access_control.php`
**User Roles:**
- **Administrator:** Full system access including user management, data modification, and advanced analytics
- **User:** Limited access to dashboard, statistics, and basic data viewing

**Permission Matrix:**
| Feature | Administrator | User |
|---------|---------------|------|
| Dashboard | ✅ Read | ✅ Read |
| Statistics | ✅ Read | ✅ Read |
| Heatmap | ✅ Read | ✅ Read |
| Chloropleth | ✅ Read | ❌ No access |
| Hazard Types | ✅ Full CRUD | ❌ No access |
| Hazard Data | ✅ Full CRUD | ✅ Read |
| Barangay | ✅ Full CRUD | ✅ Full CRUD |
| Municipality | ✅ Full CRUD | ❌ No access |
| Municipality Users | ✅ Full CRUD | ❌ No access |

### Session Management
- Secure session validation (`session_check.php`)
- Automatic session timeout
- CSRF protection
- Secure logout functionality

### Data Validation
- Input sanitization
- SQL injection prevention (PDO prepared statements)
- File upload security
- Email validation

## Data Sources

### Geographic Data
- **Municipality Boundaries:** GeoJSON format for Biliran province municipalities
- **Barangay Boundaries:** Detailed administrative boundaries
- **Hazard Zones:** Classified risk areas for floods, landslides, and storm surges

### Weather Data
- **Open Meteo API:** Current weather conditions, forecasts, and historical data
- **PAGASA Integration:** Severe weather bulletins and advisories
- **Rainfall Monitoring:** 24-hour accumulation tracking with automated warnings

### Population Data
- **Census Data:** Population statistics by barangay and municipality
- **Risk Assessment:** Population exposure calculations for different hazard types

## API Architecture

### RESTful Endpoints

#### User Management
- `GET /api/users/current.php` - Get current user info
- `POST /api/users/update.php` - Update user profile
- `POST /api/users/change_password.php` - Change password
- `GET /api/users/list.php` - List all users (admin only)

#### Geographic Data
- `GET /api/municipality/list.php` - List municipalities
- `GET /api/barangay/list_by_municipality.php` - Get barangays by municipality

#### Hazard Data
- `GET /api/hazard/list.php` - List hazard incidents
- `POST /api/hazard/create.php` - Create hazard record
- `PUT /api/hazard/update.php` - Update hazard record
- `DELETE /api/hazard/delete.php` - Delete hazard record

#### Statistics
- `GET /api/statistics/population_hazard.php` - Population exposure by hazard
- `GET /api/statistics/historical_disasters.php` - Historical disaster data
- `GET /api/statistics/population_distribution.php` - Population distribution analysis

#### Messaging
- `GET /api/messages/conversations.php` - Get user conversations
- `GET /api/messages/list.php` - Get messages with specific user
- `POST /api/messages/send.php` - Send message

## Automated Systems

### PAGASA Advisory Monitor
**Location:** `pagasa-monitor.js`
**Features:**
- Automated monitoring of PAGASA weather bulletins
- PDF advisory downloads
- Real-time notification system
- Duplicate detection and state persistence

### Rainfall Warning System
**Location:** Dashboard JavaScript
**Features:**
- 24-hour rainfall accumulation tracking
- Automated warning levels (Moderate, High, Critical)
- Audio alerts with different severity sounds
- Affected barangay identification
- Automated suspension recommendations

### WebSocket Server
**Location:** `websocket_server.php`
**Features:**
- Real-time messaging infrastructure
- User presence tracking
- Message broadcasting
- Connection management

## Installation and Setup

### Prerequisites
- PHP 8.0+
- MySQL/MariaDB 5.7+
- Apache/Nginx web server
- Node.js (for development)
- Composer (PHP dependency management)

### Database Setup
```sql
-- Import the database schema
mysql -u root -p < db/final_thesis_system.sql
```

### Configuration
1. Update database credentials in `db.php`
2. Configure Apache virtual host pointing to project root
3. Set up WebSocket server for real-time messaging
4. Configure email settings in `Mail/` directory

### Dependencies Installation
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (if any)
npm install
```

### WebSocket Server Setup
```bash
# Start the WebSocket server
php websocket_server.php
```

### PAGASA Monitor Setup
```bash
# Start PAGASA advisory monitoring
node pagasa-monitor.js
```

## Usage Guide

### For Administrators
1. **Login** with administrator credentials
2. **Dashboard Overview:** Monitor real-time weather and system status
3. **Data Management:** Add/update municipalities, barangays, and hazard data
4. **User Management:** Create and manage user accounts
5. **Analytics:** Review statistics and generate reports

### For Regular Users
1. **Login** with user credentials
2. **Dashboard:** View weather information and warnings
3. **Statistics:** Analyze hazard exposure data
4. **Heatmaps:** Visualize hazard intensity
5. **Communication:** Chat with other users and administrators

## Maintenance and Monitoring

### Log Files
- Web server access/error logs
- PHP error logs
- Database query logs
- WebSocket connection logs

### Backup Strategy
- Daily database backups
- File system backups (uploads, advisories)
- Configuration backups

### Performance Monitoring
- API response times
- Database query performance
- WebSocket connection health
- Memory usage monitoring

## Future Enhancements

### Planned Features
- Mobile application development
- SMS alert system integration
- Advanced predictive analytics using machine learning
- Integration with emergency response systems
- Multi-language support
- Offline capability for critical features

### Technical Improvements
- API rate limiting and caching
- Database query optimization
- Frontend performance optimization
- Enhanced security measures
- Automated testing suite

## Contributing

### Development Guidelines
1. Follow PSR-12 coding standards for PHP
2. Use semantic versioning for releases
3. Maintain comprehensive documentation
4. Implement proper error handling
5. Write unit tests for critical functions

### Code Quality
- Regular code reviews
- Automated testing
- Performance profiling
- Security audits

## License

This project is developed as part of an academic thesis and is intended for educational and research purposes in disaster management for Biliran province, Philippines.

## Contact Information

For technical support or inquiries about the system:
- **Project:** Final Thesis System
- **Purpose:** Disaster Management Platform for Biliran Province
- **Technology:** Web-based PHP/MySQL Application
- **Features:** Real-time weather monitoring, hazard visualization, emergency communication

---

**Note:** This system is designed specifically for the geographical and administrative context of Biliran province. Customization may be required for deployment in other locations.
