# Changelog

All notable changes to the **YBS Hub** project will be documented in this file.

## [1.0.0] - Beta Release (October 2024)

### Added
- **Core Architecture:** Restructured the entire codebase into a clean MVC-like structure (`/assets`, `/config`, `/core`, `/includes`) for better maintainability.
- **Admin Dashboard:** Fully modernized admin panel with a premium, glassmorphism-inspired Tailwind CSS design.
- **Interactive Maps:** Integrated Leaflet maps for bus stops and route tracking across `bus_details.php`, `gate_details.php`, and `GateList.php`.
- **Dynamic Bus Gate Icons:** Custom-designed, scalable SVG icons for bus gates and markers.
- **Advanced Search Algorithm:** Optimized the `Destination.php` logic to prevent duplicate bus results on multi-step routes.
- **Position Management:** Added dynamic ordering functionality for bus stops along a route in the Admin panel.
- **Global Advertising System:** Integrated a sitewide popup and banner advertisement manager.
- **Device Tracking & Analytics:** Implemented basic user analytics to log page views and device types.

### Security
- Isolated database credentials into a secure `/config/database.php` file.
- Separated public and administrative headers/footers to prevent unauthorized access to admin functionalities.
