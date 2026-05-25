# Milestone Goal Manager

Milestone is a PHP and MySQL web app for turning personal goals into trackable tasks, visible progress, and weekly momentum. It helps users create goals, connect tasks to those goals, record achievements, and review analytics that show what needs attention next.

## Features

- User registration, login, sessions, and optional remember-me authentication
- Goal creation with categories such as health, work, personal, religion, and other
- Task management for standalone tasks or tasks linked to goals
- Automatic goal progress based on completed linked tasks
- Achievements journal for completed goals, completed tasks, and manual wins
- Analytics dashboard with completion rates, weekly output, stuck goals, overdue tasks, and next-action guidance
- English and Arabic language support
- Light and dark theme preference
- Responsive Bootstrap-based interface

## Tech Stack

- PHP
- MySQL
- PDO
- JavaScript
- Bootstrap 5
- Bootstrap Icons
- CSS
- XAMPP or any local PHP/MySQL environment

## Project Structure

```text
assets/        Images and SVG assets
css/           Main stylesheets
js/            Frontend JavaScript for auth, dashboard, tasks, analytics, and preferences
lang/          Translation JSON files
pages/         PHP pages, managers, auth handlers, and API-style process files
```

## Requirements

- PHP 8 or newer recommended
- MySQL or MariaDB
- XAMPP, WAMP, Laragon, or similar local server
- A browser with JavaScript enabled

## Setup

1. Place the project folder inside your web server root.

   For XAMPP, this is usually:

   ```text
   C:\xampp\htdocs\
   ```

2. Start Apache and MySQL from the XAMPP control panel.

3. Create a database named:

   ```sql
   CREATE DATABASE nafsi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

4. Create the required core tables.

   ```sql
   USE nafsi_db;

   CREATE TABLE users (
       user_id INT AUTO_INCREMENT PRIMARY KEY,
       username VARCHAR(50) NOT NULL UNIQUE,
       email VARCHAR(255) NOT NULL UNIQUE,
       password_hash VARCHAR(255) NOT NULL,
       status TINYINT NOT NULL DEFAULT 1,
       role_id INT NOT NULL DEFAULT 3,
       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

   CREATE TABLE user_tokens (
       token_id INT AUTO_INCREMENT PRIMARY KEY,
       user_id INT NOT NULL,
       token_hash VARCHAR(64) NOT NULL,
       expires_at DATETIME NOT NULL,
       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
       INDEX idx_user_tokens_user (user_id),
       INDEX idx_user_tokens_hash (token_hash)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

   CREATE TABLE goals (
       goal_id INT AUTO_INCREMENT PRIMARY KEY,
       user_id INT NOT NULL,
       goal_name VARCHAR(255) NOT NULL,
       goal_status VARCHAR(20) NOT NULL DEFAULT 'pending',
       goal_category VARCHAR(40) NOT NULL DEFAULT 'other',
       done_at DATETIME NULL,
       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
       INDEX idx_goals_user (user_id)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   ```

   The app creates the `tasks` and `achievements` tables automatically when their managers run.

5. Check the database connection in `pages/connect.php`.

   Default local settings are:

   ```php
   private $dsn = "mysql:host=localhost;dbname=nafsi_db";
   private $username = "root";
   private $password = "";
   ```

6. Open the app in your browser:

   ```text
   http://localhost/practices/Milestone%20Goal%20Manager%20Project/pages/index.php
   ```

## Main Pages

- `pages/index.php` - login and registration
- `pages/dashboard.php` - goals dashboard
- `pages/tasks.php` - task manager
- `pages/achievements.php` - achievements journal
- `pages/analytics.php` - progress analytics and next-action insights

## How It Solves a Real Problem

Many people can write goals, but they struggle to convert those goals into consistent action. Milestone focuses on the practical gap between intention and progress by connecting goals to tasks, surfacing overdue work, showing stuck goals, and keeping a record of completed wins.

## Suggested Next Improvements

- Add weekly focus planning
- Add goal templates for students, freelancers, health, learning, and personal projects
- Add edit support for goals and tasks
- Add reminders or email notifications
- Add a profile/settings page
- Add database migrations instead of creating tables inside managers
- Add tests for validation, authentication, and task/goal status syncing

## License

This project is currently not licensed. Add a license before sharing or publishing it publicly.
