-- Create database
CREATE DATABASE IF NOT EXISTS finance_dashboard;
USE finance_dashboard;

-- Create expense_categories table
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default expense categories
INSERT INTO expense_categories (category_name) VALUES
('Rent'),
('Food'),
('Transport'),
('Utilities'),
('Healthcare'),
('Entertainment'),
('Shopping'),
('Other');

-- Create income_categories table
CREATE TABLE IF NOT EXISTS income_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO income_categories (category_name) VALUES
('Salary'),
('Freelance'),
('Business'),
('Investment'),
('Other');

-- Create incomes table
CREATE TABLE IF NOT EXISTS incomes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    date DATE NOT NULL,
    category VARCHAR(100) DEFAULT 'Other',
    payment_mode VARCHAR(50) DEFAULT 'Cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    date DATE NOT NULL,
    category VARCHAR(100) DEFAULT 'Other',
    payment_mode VARCHAR(50) DEFAULT 'Cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create reports table
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month VARCHAR(50) NOT NULL UNIQUE,
    opening_balance DECIMAL(10, 2) NOT NULL DEFAULT 0,
    income DECIMAL(10, 2) NOT NULL DEFAULT 0,
    expenses DECIMAL(10, 2) NOT NULL DEFAULT 0,
    closing_balance DECIMAL(10, 2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO incomes (description, amount, date) VALUES
('Salary', 5000.00, '2026-01-01'),
('Freelance Project', 1500.00, '2026-01-10');

INSERT INTO expenses (description, amount, date) VALUES
('Rent', 1200.00, '2026-01-01'),
('Groceries', 300.00, '2026-01-05'),
('Utilities', 150.00, '2026-01-08');

-- Insert sample reports (previous months)
INSERT INTO reports (month, opening_balance, income, expenses, closing_balance) VALUES
('NOV-2025', 1000.00, 80600.00, 0.00, 81600.00),
('DEC-2025', 81600.00, 1860480.00, 0.00, 1942080.00);

-- Create salary_logs table
CREATE TABLE IF NOT EXISTS salary_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    month VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'Paid',
    payment_date DATE NOT NULL,
    payment_mode VARCHAR(50) DEFAULT 'Bank Transfer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
