-- PokéVault Database Schema (MariaDB / MySQL)
-- Run this once on the Netcup / Plesk MariaDB database.

SET NAMES utf8mb4;

-- 1. Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    public_binder_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. User Cards Collection (Sammlung, Trade-Binder & Grading)
CREATE TABLE IF NOT EXISTS user_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_api_id VARCHAR(100) NOT NULL,
    set_id VARCHAR(100) NOT NULL,
    card_number VARCHAR(20) NOT NULL,
    card_name VARCHAR(150) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    storage_type ENUM('vault', 'trade_binder') DEFAULT 'vault',
    card_condition ENUM('Mint', 'Near Mint', 'Excellent', 'Good', 'Light Played', 'Played', 'Poor') DEFAULT 'Near Mint',
    language VARCHAR(10) DEFAULT 'DE',
    is_graded BOOLEAN DEFAULT FALSE,
    grading_company VARCHAR(50) NULL,
    grading_score DECIMAL(3,1) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_cards_user (user_id),
    INDEX idx_user_cards_set (set_id),
    INDEX idx_user_cards_api_id (card_api_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Price History Tracker
CREATE TABLE IF NOT EXISTS price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_api_id VARCHAR(100) NOT NULL,
    price_eur DECIMAL(10,2) NOT NULL,
    recorded_at DATE NOT NULL,
    UNIQUE KEY unique_card_date (card_api_id, recorded_at),
    INDEX idx_price_history_card (card_api_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Wishlist (fehlende Karten, die ein Nutzer sich für ein Set gemerkt hat)
CREATE TABLE IF NOT EXISTS wishlist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_api_id VARCHAR(100) NOT NULL,
    set_id VARCHAR(100) NOT NULL,
    card_number VARCHAR(20) NOT NULL,
    card_name VARCHAR(150) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_card (user_id, card_api_id),
    INDEX idx_wishlist_user (user_id),
    INDEX idx_wishlist_set (set_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Trades (Tausch-Thread zwischen zwei Nutzern, per Nickname - nie per E-Mail)
CREATE TABLE IF NOT EXISTS trades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_a_id INT NOT NULL,
    user_b_id INT NOT NULL,
    status ENUM('open', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
    user_a_confirmed BOOLEAN NOT NULL DEFAULT FALSE,
    user_b_confirmed BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_a_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_b_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_trades_user_a (user_a_id),
    INDEX idx_trades_user_b (user_b_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Trade-Nachrichten (Absprache zu Ablauf/Versand innerhalb eines Tauschs)
CREATE TABLE IF NOT EXISTS trade_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trade_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_trade_messages_trade (trade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
