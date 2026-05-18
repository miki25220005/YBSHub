# 🚌 YBS Hub

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/javascript-%23323330.svg?style=for-the-badge&logo=javascript&logoColor=%23F7DF1E)
![TailwindCSS](https://img.shields.io/badge/tailwindcss-%2338B2AC.svg?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Leaflet.js](https://img.shields.io/badge/Leaflet-199900?style=for-the-badge&logo=Leaflet&logoColor=white)

Welcome to **YBS Hub** - The ultimate public transit guide for the Yangon Bus Service (YBS). 

YBS Hub is a comprehensive web-based application designed to help commuters navigate Yangon's bus network with ease. It features interactive maps, direct and indirect route calculations, gate directories, and a secure administrative dashboard for managing transit data.

## ✨ Features

- 🗺️ **Interactive Maps:** Visual mapping of bus stops and routes using Leaflet.js.
- 🔍 **Route Search:** Find forward and reverse paths for any YBS bus route.
- 🎯 **Destination Finder:** Intelligent algorithm to find direct and 2-step indirect routes between any two gates.
- 📍 **Gate Details:** Explore complete bus station directories and connecting routes.
- 🛡️ **Admin Dashboard:** Secure backend to manage buses, gates, townships, routes, popups, and banner advertisements.
- 📱 **Fully Responsive:** Optimized for both mobile and desktop experiences.

## 🛠️ Project Structure

This application follows a clean, modular structure:

- `/Version 1.10/assets/` - Static files (CSS, JS, Images, Custom SVGs).
- `/Version 1.10/config/` - Core configuration files (e.g., `database.php`).
- `/Version 1.10/core/` - Backend logic, analytics, and autocomplete scripts.
- `/Version 1.10/includes/` - Shared UI components (Headers, footers, ad popups).
- `/Version 1.10/Admin/` - The secure administrative portal for data entry.
- `/Version 1.10/api/` - REST endpoints for async fetching and map data.

## 🚀 Setup Instructions

To run this project locally (e.g., using Laragon, XAMPP, or WAMP):

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/miki25220005/YBSHub.git
   ```
2. **Move to Web Root:** Place the project folder into your local web server's root directory (e.g., `www/YBSHub` or `htdocs/YBSHub`).
3. **Database Setup:**
   - Import the YBS database SQL file into your MySQL/MariaDB server.
   - Rename `Version 1.10/config/database.example.php` to `database.php`.
   - Update the `$host`, `$user`, `$password`, and `$database` variables in `database.php` to match your local database credentials.
4. **Launch:** Open your browser and navigate to `http://localhost/YBSHub/Version 1.10/` (or your appropriate local path).

## 📜 License
This project is open-source. Feel free to explore, contribute, or use it as inspiration!
