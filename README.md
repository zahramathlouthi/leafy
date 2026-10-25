# 🌿 Leafy (Uber-Cueillette)

A web platform that connects **farmers** and **seasonal harvest workers** in Tunisia. Farmers publish harvest offers (fruit type, governorate, number of workers needed), workers browse and apply, and farmers accept or refuse applicants and rate the work afterwards.

> University project built with PHP, MySQL and vanilla JavaScript. The interface is in French.

## Features

### For farmers (`agriculteur`)
- Dashboard with statistics and recent offers
- Create harvest offers and manage them (`Mes offres`)
- Review applicants and accept or refuse them
- Evaluate workers after the job is done
- Profile management

### For workers (`ouvrier`)
- Browse available harvest offers with filters
- Apply to an offer (no duplicate applications, closed or full offers are blocked)
- Track applications and their status
- History of completed jobs and earnings
- Profile with photo upload

### General
- Separate registration and login for each role
- Session-based access control: each role can only reach its own pages
- Offers organised by fruit type (olives, citrus, tomatoes, dates, grapes, apples, pears, figs) and by the 24 Tunisian governorates

## Tech stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP (PDO) |
| Database | MySQL (via XAMPP) |
| Frontend | HTML, CSS, JavaScript |
| Local server | XAMPP (Apache + MySQL) |

## Project structure

```
Leafy/
├── index.php              # Landing page (choose farmer or worker)
├── database.sql           # Database schema and reference data
├── css/style.css          # Styles
├── js/main.js             # Client-side scripts
├── includes/
│   ├── db.php             # Database connection (PDO)
│   └── session.php        # Session and role helpers
└── php/
    ├── auth/              # Login, logout, registration (farmer / worker)
    ├── agriculteur/       # Farmer pages
    ├── ouvrier/           # Worker pages
    └── photo.php          # Serves worker profile photos
```

## Database

The database `uber_cueillette` contains six tables:

`type_fruit`, `gouvernorat`, `agriculteur`, `ouvrier`, `offre`, `candidature`

## Run it locally

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Copy the project folder to `C:\xampp\htdocs\leafy`.
3. Open phpMyAdmin and import `database.sql` (it creates the database and tables).
4. Check `includes/db.php`. The default settings are host `127.0.0.1`, port **3307**, user `root`, empty password. If your MySQL runs on the default port 3306, change the port there.
5. Open <http://localhost/leafy> in your browser.

## Screenshots

<!-- Add screenshots to a "screenshots" folder, then uncomment: -->
<!-- ![Home page](screenshots/home.png) -->
<!-- ![Farmer dashboard](screenshots/farmer-dashboard.png) -->
<!-- ![Worker offers](screenshots/worker-offers.png) -->

## Possible improvements

- Add a `.env`-based configuration instead of hardcoded database settings
- Email or SMS notifications when an application is accepted
- Map view of offers by governorate
- Multilingual interface (French / Arabic / English)

## Author

**Zahra Mathlouthi** — Business Intelligence student, ISG Tunis
[GitHub](https://github.com/zahramathlouthi)
