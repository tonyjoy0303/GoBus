# 🚌 GoBus – Smart Bus Booking System

**GoBus** is a complete bus booking solution built using **PHP**, **HTML**, **CSS**, **JavaScript**, and **Bootstrap**. It provides an intuitive ticket booking interface for passengers, a powerful admin panel for bus operators, a conductor module for real-time ticket scanning via **QR code**, and supports **secure online payments**.

---

## 🚀 Features

### 🔹 Passenger (User) Features
- 🔍 Search buses by route and date
- 🪑 Choose seats from interactive seat layout
- 💳 Pay online and book tickets securely
- 🎟️ Receive e-ticket with QR code
- 👤 User registration, login, and booking history

### 🔹 Admin Features
- 🔐 Login-secured admin panel
- 🚌 Add/Edit/Delete buses, routes, schedules
- 📅 Trip scheduling and price management
- 📊 View all bookings and revenue reports
- 👥 Manage users and conductors

### 🔹 Conductor Features
- 📲 Login with conductor credentials
- 🔍 Scan passenger QR codes via webcam or mobile
- ✅ Confirm or reject ticket based on trip
- 📋 View list of passengers per bus trip

### 🔹 Payment Integration
- 💳 Integrated with secure **Razorpay**
- 📜 Transaction history linked with each booking
- ✅ Real-time payment verification
- 📧 E-ticket emailed upon successful payment *(optional)*

---

## 🧰 Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: Core PHP
- **Database**: MySQL
- **QR Code**: PHP QR Code Generator
- **QR Scanning**: JavaScript + Webcam API / Mobile camera
- **Payment Gateway**: Razorpay / Stripe / PayPal *(based on what you used)*

---

## ⚙️ Setup Instructions

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/gobus.git
cd gobus
