# YBS Hub

Welcome to **YBS Hub** - The ultimate public transit guide for the Yangon Bus Service (YBS). 

## About The Project
YBS Hub is a web-based application designed to help commuters navigate Yangon's bus network with ease. It features interactive maps, bus route searches, gate directories, and a comprehensive administrative dashboard for managing the transit data.

## Version
**Current Version:** 1.0.0 (Beta)

## Project Structure
This application follows a clean, modular structure:

- `/assets/` - Contains all static files (CSS, JS, Images, SVGs).
- `/config/` - Contains core configuration files (e.g., `database.php`).
- `/core/` - Contains backend logic, analytics, and autocomplete scripts.
- `/includes/` - Shared UI components (Public headers/footers, Admin headers/footers, popups).
- `/Admin/` - The secure administrative portal for data entry and management.

## Setup Instructions
To run this project locally (e.g., using Laragon, XAMPP, or WAMP):

1. **Clone/Copy the Project:** Place the project folder into your local web server's root directory (e.g., `www/YBSRoute` or `htdocs/YBSRoute`).
2. **Database Setup:**
   - Import the YBS database SQL file into your MySQL/MariaDB server.
   - Open `/config/database.php`.
   - Update the `$db_host`, `$db_user`, `$db_pass`, and `$db_name` variables to match your local database credentials.
3. **Launch:** Open your browser and navigate to `http://localhost/YBSRoute`.

## Features
- **Route Search:** Find forward and reverse paths for any YBS bus.
- **Destination Finder:** Intelligent algorithm to find direct and 2-step indirect routes between gates.
- **Interactive Maps:** Visual mapping of bus stops using Leaflet.js.
- **Admin Dashboard:** Secure backend to manage buses, gates, townships, routes, and advertisements.
