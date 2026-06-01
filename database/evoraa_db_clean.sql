-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2026 at 03:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `evoraa_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(2, 'Main Admin', 'admin@evoraa.com', '$2y$10$w85Ibe8S/THeuL9F6G0EaO6CstgS5V3TidWb6N53/gY3rQoZHe6Z.', '2026-05-31 06:17:04');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `text` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `text`, `created_at`) VALUES
(1, 'FREE ISLANDWIDE DELIVERY ON ORDERS OVER 15,000 LKR', '2026-05-31 04:33:51'),
(2, 'NEW ARRIVALS: EXPLORE THE DROP OF CURATED MINIMALISM', '2026-05-31 04:33:51'),
(3, 'SUBSCRIBE TODAY FOR AN IMMEDIATE 10% COUPON CODE', '2026-05-31 04:33:51');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `link_path` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image_path`, `link_path`, `title`, `created_at`) VALUES
(1, 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=1600&auto=format&fit=crop', 'shop.php', 'EXPLORE THE DROP', '2026-05-31 04:33:51'),
(7, 'uploads/banner_6a1c370d05658.jpg', 'shop.php', 'banner', '2026-05-31 13:26:37');

-- --------------------------------------------------------

--
-- Table structure for table `contact_inquiries`
--

CREATE TABLE `contact_inquiries` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_inquiries`
--

-- No seed contact inquiries

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

-- No seed customers; real customers will register on the live site

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text NOT NULL,
  `approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `customer_name`, `rating`, `comment`, `approved`, `created_at`) VALUES
(1, 'Aisha M.', 5, 'Absolutely obsessed with the Aura Linen Dress. The quality is unmatched and delivery was so fast. EVORAA is my new go-to label!', 1, '2026-05-31 05:37:18'),
(2, 'Dilini R.', 5, 'The silk bias cut skirt is everything I dreamed of. Luxury feel at a really great price point.', 1, '2026-05-31 05:37:18'),
(3, 'Priya S.', 4, 'Love the packaging and the attention to detail. The knit set fits perfectly. Will definitely order again.', 1, '2026-05-31 05:37:18');

-- --------------------------------------------------------

--
-- Table structure for table `hero_images`
--

CREATE TABLE `hero_images` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_hash` varchar(64) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `delivery_tier` varchar(50) NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

-- No seed orders; real orders will be placed on the live site

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `size` varchar(10) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

-- No seed order items

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `image1` varchar(255) NOT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL,
  `image4` varchar(255) DEFAULT NULL,
  `original_price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) NOT NULL,
  `discount_active` tinyint(1) DEFAULT 0,
  `offer_badge` varchar(50) DEFAULT NULL,
  `stock_xs` int(11) DEFAULT 0,
  `stock_s` int(11) DEFAULT 0,
  `stock_m` int(11) DEFAULT 0,
  `stock_l` int(11) DEFAULT 0,
  `stock_xl` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `name`, `category`, `description`, `image1`, `image2`, `image3`, `image4`, `original_price`, `discount_price`, `discount_active`, `offer_badge`, `stock_xs`, `stock_s`, `stock_m`, `stock_l`, `stock_xl`, `created_at`) VALUES
(1, 'prod-001', 'Aura Linen Halter Maxi Dress', 'MAXI DRESSES', 'An elegant, flowing maxi dress crafted from premium organic Sri Lankan linen. Features a sophisticated halter neckline, low open back, and dual side slits for liquid-like movement. Perfect for sunset soirées.', 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?q=80&w=600&auto=format&fit=crop', 'https://images.unsplash.com/photo-1539008835657-9e8e9680fe0a?q=80&w=600&auto=format&fit=crop', NULL, NULL, 12500.00, 10625.00, 1, '15% OFF', 5, 8, 12, 6, 4, '2026-05-31 04:33:51'),
(2, 'prod-002', 'Luxe Crepe Pleated Pants', 'PANTS', 'Tailored to high-fashion perfection. These high-waisted crepe pants feature structural front pleats, a wide-leg silhouette, and concealed pocket enclosures. Offers both structure and luxury drape.', 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?q=80&w=600&auto=format&fit=crop', 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?q=80&w=600&auto=format&fit=crop', NULL, NULL, 8900.00, 8900.00, 0, 'NEW DROP', 4, 10, 15, 8, 2, '2026-05-31 04:33:51'),
(3, 'prod-003', 'Silk Satin Bias Cut Skirt', 'SKIRTS', 'Cut on the bias to drape effortlessly over curves. Crafted from liquid-finish silk satin with a delicate elasticated waistband for maximum comfort. Features a soft cream palette that matches any premium capsule closet.', 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?q=80&w=600&auto=format&fit=crop', 'https://images.unsplash.com/photo-1609357518652-6cf0416f0cbe?q=80&w=600&auto=format&fit=crop', NULL, NULL, 9500.00, 7500.00, 1, 'SALE', 3, 6, 8, 5, 3, '2026-05-31 04:33:51'),
(4, 'prod-004', 'Ethereal Knit Wrap Top', 'TOPS', 'Meticulously spun from fine gauge cotton-silk yarn. Designed with elongated wraps that cinch the waist and a soft plunge ribbed neckline. Lightweight, breathable, and highly premium.', 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=600&auto=format&fit=crop', 'https://images.unsplash.com/photo-1509631179647-0177331693ae?q=80&w=600&auto=format&fit=crop', NULL, NULL, 6200.00, 6200.00, 0, '', 8, 12, 12, 10, 5, '2026-05-31 04:33:51'),
(5, 'prod-005', 'Rosmead Linen Classic Shirt', 'SHIRTS', 'The ultimate relaxed tailored shirt. Features structural horn buttons, a classic drop-shoulder style, and oversized utility breast pockets. Extremely versatile, double-hemmed for longevity.', 'https://images.unsplash.com/photo-1607345366928-199ea26cfe3e?q=80&w=600&auto=format&fit=crop', 'https://images.unsplash.com/photo-1548624149-f7b3be6894fd?q=80&w=600&auto=format&fit=crop', NULL, NULL, 7900.00, 7900.00, 0, 'BUY 1 GET 1', 6, 10, 15, 12, 6, '2026-05-31 04:33:51'),
(6, 'prod-006', 'Structured Denim Trench', 'DENIM', 'A showstopping minimalist trench jacket. Features mid-weight premium raw Japanese denim, a double-breasted button panel, and a coordinated wrap belt. Tailored to wow at first glance.', 'https://images.unsplash.com/photo-1611312449412-6cefac5dc3e4?q=80&w=600&auto=format&fit=crop', 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?q=80&w=600&auto=format&fit=crop', NULL, NULL, 18500.00, 18500.00, 0, 'CLASSIC', 2, 5, 6, 4, 2, '2026-05-31 04:33:51'),
(7, 'prod-007', 'Atelier Ribbed Knit Set', 'SETS', 'A coordinated two-piece top and skirt set crafted from a heavy ribbed viscose blend. Fits seamlessly, offering a luxurious weight that retains its shape and moves elegantly.', 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?q=80&w=600&auto=format&fit=crop', 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?q=80&w=600&auto=format&fit=crop', NULL, NULL, 16900.00, 13520.00, 1, '20% OFF', 3, 8, 8, 6, 4, '2026-05-31 04:33:51'),
(8, 'prod-009', 'Evoraa Premium Tee', 'TOPS', 'A premium everyday tee from Evoraa. Soft, breathable fabric with a clean minimal cut.', 'product1.png', NULL, NULL, NULL, 2500.00, 1990.00, 1, NULL, 10, 15, 20, 5, 2, '2026-05-31 06:20:27'),
(10, 'prod-008', 'Denim', 'DRESSES', 'Drape Styles', 'uploads/products/prod-008/image1.jpeg', 'uploads/products/prod-008/image2.jpg', NULL, NULL, 10000.00, 9000.00, 1, '50% OFF', 1, 1, 1, 1, 1, '2026-05-31 12:07:22'),
(13, 'prod-010', 'Top', 'TOPS', 'blue bathik', 'uploads/products/prod-010/image1.jpg', 'uploads/products/prod-010/image2.jpg', 'uploads/products/prod-010/image3.jpg', '', 2000.00, 1999.00, 1, '', 1, 0, 0, 0, 0, '2026-05-31 16:34:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hero_images`
--
ALTER TABLE `hero_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_hash` (`order_hash`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hero_images`
--
ALTER TABLE `hero_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
