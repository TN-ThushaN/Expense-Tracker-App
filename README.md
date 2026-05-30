# 💰 Expense Tracker App

A full-stack personal finance management web application built using **PHP, MySQL, JavaScript, and Chart.js**.
This project helps users track income and expenses, manage budgets, and visualize spending patterns through interactive charts.

---

## 🚀 Features

### 👤 User System

* User registration and login system
* Secure password hashing
* Session-based authentication

### 💸 Expense Management

* Add income and expense transactions
* Edit and delete records
* Category-based tracking (Food, Travel, Bills, Shopping, etc.)
* Date-wise transaction history

### 📊 Dashboard Analytics

* Total income, expenses, and balance overview
* Category-wise spending breakdown
* Monthly financial summary

### 💰 Budget Management

* Set monthly budget limits
* Track budget usage
* Alerts when budget is exceeded

### 📈 Data Visualization

* Pie chart for category-wise expenses
* Bar chart for monthly spending comparison
* Interactive charts using Chart.js

### 🎨 UI/UX Features

* Responsive design (mobile + desktop)
* Clean and modern dashboard UI
* Easy navigation and user-friendly interface

---

## 🛠️ Tech Stack

**Frontend:**

* HTML5
* CSS3
* JavaScript
* Chart.js

**Backend:**

* PHP (Core PHP)
* MySQL

**Tools:**

* XAMPP / Laragon
* VS Code
* Git & GitHub

---

## 🗃️ Database Structure

### Users Table

* id
* name
* email
* password
* created_at

### Transactions Table

* id
* user_id
* type (income/expense)
* category
* amount
* note
* date

### Budget Table

* id
* user_id
* month
* limit_amount

---

## ⚙️ Installation Guide

1. Clone the repository

```bash
git clone https://github.com/your-username/expense-tracker.git
```

2. Move project to server directory

```
htdocs (XAMPP) / www (Laragon)
```

3. Import database

* Open phpMyAdmin
* Create database: `expense_tracker`
* Import SQL file

4. Configure database connection

```php
$host = "localhost";
$user = "root";
$pass = "";
$db = "expense_tracker";
```

5. Run project

```
http://localhost/expense-tracker
```

---

## 📸 Screenshots

> Add screenshots here:

* Dashboard
* Add Expense page
* Charts view
* Login page

---

## 🎯 Project Purpose

This project was developed to:

* Improve full-stack development skills
* Practice real-world CRUD operations
* Learn authentication and session handling
* Understand data visualization in web applications
* Build a portfolio-ready project for internships

---

## 👨‍💻 Developer

**Thushan**
📍 Sri Lanka
🎓 Undergraduate – Data Science

---

## 📌 Future Improvements

* Mobile app version (React Native)
* AI-based spending insights
* Export reports as PDF
* Multi-currency support
* Email notifications

---

⭐ If you like this project, don’t forget to star the repository!
